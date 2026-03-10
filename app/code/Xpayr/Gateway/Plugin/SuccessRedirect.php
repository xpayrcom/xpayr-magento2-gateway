<?php
namespace Xpayr\Gateway\Plugin;

use Magento\Checkout\Controller\Onepage\Success;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Controller\ResultFactory;

/**
 * Plugin that intercepts the checkout success page controller.
 *
 * If the checkout session contains xpayr_redirect_url (set by the
 * CreatePaymentSession observer), this plugin redirects the customer
 * to the XPayr payment page instead of showing the default success page.
 */
class SuccessRedirect
{
    /**
     * @var CheckoutSession
     */
    private CheckoutSession $checkoutSession;

    /**
     * @var ResultFactory
     */
    private ResultFactory $resultFactory;

    /**
     * @param CheckoutSession $checkoutSession
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        ResultFactory $resultFactory
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->resultFactory = $resultFactory;
    }

    /**
     * After the success controller executes, check if we need to redirect to XPayr.
     *
     * @param Success $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterExecute(Success $subject, $result)
    {
        $redirectUrl = $this->checkoutSession->getData('xpayr_redirect_url', true);
        if (!empty($redirectUrl)) {
            /** @var \Magento\Framework\Controller\Result\Redirect $redirect */
            $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $redirect->setUrl($redirectUrl);
            return $redirect;
        }

        return $result;
    }
}
