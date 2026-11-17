<?php
namespace Xpayr\Gateway\Model;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Xpayr\Gateway\Api\IpnManagementInterface;
use Xpayr\Gateway\Helper\Data as XpayrHelper;

class IpnManagement implements IpnManagementInterface
{
    private OrderRepositoryInterface $orderRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private XpayrHelper $helper;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        XpayrHelper $helper
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->helper = $helper;
    }

    public function post($param = null)
    {
        $raw = file_get_contents('php://input');
        $payload = json_decode((string) $raw, true);

        if (!is_array($payload)) {
            return json_encode(['error' => true, 'message' => 'Invalid JSON']);
        }

        $secret = trim((string) $this->helper->getConfig('webhook_secret'));
        // Encrypted values start with 0:X: prefix — decrypt them
        if (str_starts_with($secret, '0:')) {
            $encryptor = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Encryption\EncryptorInterface::class);
            $secret = $encryptor->decrypt($secret);
        }
        if ($secret === '') {
            // Fail closed: never accept an unsigned webhook.
            return json_encode(['error' => true, 'message' => 'Webhook secret not configured']);
        }
        {
            $headerSig = $_SERVER['HTTP_X_XPAYR_SIGNATURE'] ?? '';
            $headerSig = trim((string) $headerSig);
            if (strpos($headerSig, 'sha256=') === 0) {
                $headerSig = substr($headerSig, 7);
            }
            $expected = hash_hmac('sha256', (string) $raw, $secret);
            if ($headerSig === '' || !hash_equals($expected, $headerSig)) {
                return json_encode(['error' => true, 'message' => 'Invalid signature']);
            }
        }

        $eventType = (string) ($payload['type'] ?? '');
        $meta = $payload['data']['metadata'] ?? [];
        $incrementId = is_array($meta) ? (string) ($meta['magento_order_id'] ?? '') : '';

        if ($incrementId === '') {
            return json_encode(['error' => true, 'message' => 'Order reference missing']);
        }

        $criteria = $this->searchCriteriaBuilder
            ->addFilter('increment_id', $incrementId, 'eq')
            ->setPageSize(1)
            ->create();

        $orders = $this->orderRepository->getList($criteria)->getItems();
        $order = reset($orders);
        if (!$order) {
            return json_encode(['error' => true, 'message' => 'Order not found']);
        }

        if ($eventType === 'payment.completed') {
            $paidStatus = (string) $this->helper->getConfig('status_order_paid');
            if ($paidStatus === '') {
                $paidStatus = 'processing';
            }
            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)->setStatus($paidStatus);
            $order->addCommentToStatusHistory(__('XPayr payment completed (webhook).'));
        } elseif ($eventType === 'payment.failed') {
            $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED)->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
            $order->addCommentToStatusHistory(__('XPayr payment failed (webhook).'));
        } elseif ($eventType === 'payment.expired') {
            $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED)->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
            $order->addCommentToStatusHistory(__('XPayr payment expired (webhook).'));
        } else {
            $order->addCommentToStatusHistory(__('XPayr webhook event: %1', $eventType));
        }

        $this->orderRepository->save($order);

        return json_encode(['error' => false, 'message' => 'ok']);
    }
}
