<?php
namespace Xpayr\Gateway\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Xpayr\Gateway\Helper\Data as XpayrHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\HTTP\Client\Curl;

/**
 * Observer that fires after order placement.
 *
 * When the customer places an order using XPayr, this observer:
 * 1. Calls the XPayr API to create a payment session
 * 2. Stores the returned payment_url in the checkout session
 * 3. The SuccessRedirect plugin then picks up this URL and redirects
 */
class CreatePaymentSession implements ObserverInterface
{
    /**
     * @var XpayrHelper
     */
    private XpayrHelper $helper;

    /**
     * @var CheckoutSession
     */
    private CheckoutSession $checkoutSession;

    /**
     * @var Curl
     */
    private Curl $curlClient;

    /**
     * @param XpayrHelper $helper
     * @param CheckoutSession $checkoutSession
     * @param Curl $curlClient
     */
    public function __construct(
        XpayrHelper $helper,
        CheckoutSession $checkoutSession,
        Curl $curlClient
    ) {
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
        $this->curlClient = $curlClient;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        if (!$order || !$order->getId()) {
            return;
        }

        $payment = $order->getPayment();
        if (!$payment || $payment->getMethod() !== 'xpayr_gateway') {
            return;
        }

        // Check if we already have a payment URL
        $existingUrl = (string) $payment->getAdditionalInformation('xpayr_payment_url');
        if ($existingUrl !== '') {
            $this->checkoutSession->setData('xpayr_redirect_url', $existingUrl);
            return;
        }

        // Build API request
        $apiBase = rtrim((string) $this->helper->getConfig('api_base_url'), '/');
        $secretKey = trim((string) $this->helper->getConfig('secret_key'));
        $network = strtolower(trim((string) $this->helper->getConfig('network')));
        $currency = strtoupper(trim((string) $this->helper->getConfig('currency')));

        if ($apiBase === '' || $secretKey === '' || $network === '' || $currency === '') {
            return;
        }

        $payload = [
            'amount' => (float) $order->getBaseGrandTotal(),
            'currency' => $currency,
            'network' => $network,
            'success_url' => $this->helper->getStoreUrl('checkout/onepage/success'),
            'cancel_url' => $this->helper->getStoreUrl('checkout/cart'),
            'ipn_callback_url' => $this->helper->getStoreUrl('rest/V1/xpayr/ipn'),
            'metadata' => [
                'source' => 'magento2',
                'magento_order_id' => $order->getIncrementId(),
                'magento_entity_id' => (int) $order->getEntityId(),
                'customer_email' => (string) $order->getCustomerEmail(),
            ],
        ];

        $response = $this->httpPostJson($apiBase . '/payments', $payload, $secretKey);
        if (empty($response['ok']) || empty($response['json']['payment_url']) || empty($response['json']['id'])) {
            return;
        }

        $sessionId = (string) $response['json']['id'];
        $paymentUrl = (string) $response['json']['payment_url'];

        // Store in payment additional info
        $payment->setAdditionalInformation('xpayr_session_id', $sessionId);
        $payment->setAdditionalInformation('xpayr_payment_url', $paymentUrl);
        if (!empty($response['json']['invoice_id'])) {
            $payment->setAdditionalInformation('xpayr_invoice_id', (string) $response['json']['invoice_id']);
        }
        $order->addCommentToStatusHistory(__('XPayr session created: %1', $sessionId));

        // Set pending_payment status
        $statusPlaced = (string) $this->helper->getConfig('status_order_placed');
        if ($statusPlaced === '') {
            $statusPlaced = Order::STATE_PENDING_PAYMENT;
        }
        $order->setState(Order::STATE_NEW)->setStatus($statusPlaced);

        // Store redirect URL in checkout session for the plugin to pick up
        $this->checkoutSession->setData('xpayr_redirect_url', $paymentUrl);
    }

    /**
     * Perform an HTTP POST request with JSON payload
     *
     * @param string $url
     * @param array $payload
     * @param string $secret
     * @return array
     */
    private function httpPostJson(string $url, array $payload, string $secret): array
    {
        try {
            $this->curlClient->setHeaders([
                'Authorization' => 'Bearer ' . $secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]);

            $this->curlClient->setTimeout(30);
            $this->curlClient->post($url, json_encode($payload));

            $code = $this->curlClient->getStatus();
            $body = $this->curlClient->getBody();

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
