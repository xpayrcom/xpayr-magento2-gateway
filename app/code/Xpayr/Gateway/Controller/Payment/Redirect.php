<?php
namespace Xpayr\Gateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Xpayr\Gateway\Helper\Data as XpayrHelper;

class Redirect extends Action
{
    private Registry $registry;
    private PageFactory $resultPageFactory;
    private CheckoutSession $checkoutSession;
    private OrderRepository $orderRepository;
    private XpayrHelper $helper;

    public function __construct(
        Context $context,
        Registry $registry,
        PageFactory $resultPageFactory,
        CheckoutSession $checkoutSession,
        OrderRepository $orderRepository,
        XpayrHelper $helper
    ) {
        parent::__construct($context);
        $this->registry = $registry;
        $this->resultPageFactory = $resultPageFactory;
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->helper = $helper;
    }

    public function execute()
    {
        if (empty($this->checkoutSession->getData('last_success_quote_id'))) {
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('checkout/cart');
        }

        $order = $this->checkoutSession->getLastRealOrder();
        if (!$order || !$order->getId()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('checkout/cart');
        }

        $this->registry->register('xpayr_last_success_quote_id', (int) $this->checkoutSession->getData('last_success_quote_id'));
        $this->registry->register('xpayr_last_success_order_id', (int) $order->getId());

        $statusPlaced = (string) $this->helper->getConfig('status_order_placed');
        if ($statusPlaced === '') {
            $statusPlaced = Order::STATE_PENDING_PAYMENT;
        }

        $model = $this->orderRepository->get((int) $order->getId());
        if ($model->getState() === Order::STATE_NEW || $model->getState() === Order::STATE_PENDING_PAYMENT) {
            $model->setState(Order::STATE_NEW)->setStatus($statusPlaced);
            $this->orderRepository->save($model);
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Redirecting to XPayr'));
        return $resultPage;
    }
}
