<?php
namespace Xpayr\Gateway\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Registry;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Api\OrderRepositoryInterface;
use Xpayr\Gateway\Helper\Data as XpayrHelper;

class Redirect extends Template
{
    private Registry $registry;
    private OrderRepositoryInterface $orderRepository;
    private XpayrHelper $helper;

    public function __construct(
        Context $context,
        Registry $registry,
        OrderRepositoryInterface $orderRepository,
        XpayrHelper $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->orderRepository = $orderRepository;
        $this->helper = $helper;
    }

    public function getPaymentUrl(): string
    {
        $orderId = (int) $this->registry->registry('xpayr_last_success_order_id');
        if ($orderId <= 0) {
            return '';
        }

        $order = $this->orderRepository->get($orderId);
        $payment = $order->getPayment();

        $existingUrl = (string) $payment->getAdditionalInformation('xpayr_payment_url');
        if ($existingUrl !== '') {
            return $existingUrl;
        }

        $apiBase = rtrim((string) $this->helper->getConfig('api_base_url'), '/');
        $secretKey = $this->helper->getSecretKey();
        $network = strtolower(trim((string) $this->helper->getConfig('network')));
        $currency = strtoupper(trim((string) $this->helper->getConfig('currency')));

        if ($apiBase === '' || $secretKey === '' || $network === '' || $currency === '') {
            return '';
        }

        $successUrl = $this->getUrl('checkout/onepage/success');
        $cancelUrl = $this->getUrl('checkout/cart');

        $payload = [
            'amount' => (float) $order->getBaseGrandTotal(),
            'currency' => $currency,
            'network' => $network,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'ipn_callback_url' => $this->getUrl('rest/V1/xpayr/ipn'),
            'metadata' => [
                'source' => 'magento2',
                'magento_order_id' => $order->getIncrementId(),
                'magento_entity_id' => (int) $order->getEntityId(),
                'customer_email' => (string) $order->getCustomerEmail(),
            ],
        ];

        $response = $this->httpPostJson($apiBase . '/payments', $payload, $secretKey);
        if (empty($response['ok']) || empty($response['json']['payment_url']) || empty($response['json']['id'])) {
            return '';
        }

        $sessionId = (string) $response['json']['id'];
        $paymentUrl = (string) $response['json']['payment_url'];

        $payment->setAdditionalInformation('xpayr_session_id', $sessionId);
        $payment->setAdditionalInformation('xpayr_payment_url', $paymentUrl);
        if (!empty($response['json']['invoice_id'])) {
            $payment->setAdditionalInformation('xpayr_invoice_id', (string) $response['json']['invoice_id']);
        }
        $order->addCommentToStatusHistory(__('XPayr session created: %1', $sessionId));
        $this->orderRepository->save($order);

        return $paymentUrl;
    }

    public function getFailureUrl(): string
    {
        return $this->getUrl('checkout/cart');
    }

    private function httpPostJson(string $url, array $payload, string $secret): array
    {
        try {
            // Need to instantiate the curl client here since we can't easily inject it into a Block
            // without overriding the massive constructor or relying on ObjectManager.
            // Using ObjectManager directly in a block is discouraged but better than native curl for EQP.
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            /** @var \Magento\Framework\HTTP\Client\Curl $curl */
            $curl = $objectManager->create(\Magento\Framework\HTTP\Client\Curl::class);

            $curl->setHeaders([
                'Authorization' => 'Bearer ' . $secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]);

            $curl->setTimeout(30);
            $curl->post($url, json_encode($payload));

            $code = $curl->getStatus();
            $body = $curl->getBody();

            $json = json_decode((string) $body, true);

            return [
                'ok' => $code >= 200 && $code < 300 && is_array($json),
                'code' => $code,
                'json' => is_array($json) ? $json : null,
                'raw' => (string) $body,
            ];

        } catch (\Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage(), 'json' => null, 'code' => 500];
        }
    }
}
