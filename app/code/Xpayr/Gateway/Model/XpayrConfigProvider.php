<?php
namespace Xpayr\Gateway\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Xpayr\Gateway\Helper\Data as XpayrHelper;

/**
 * Provides XPayr Gateway configuration to the checkout page JavaScript.
 *
 * This class injects the necessary payment method configuration into
 * window.checkoutConfig.payment so that the frontend JS component
 * can properly handle the payment flow and redirect.
 */
class XpayrConfigProvider implements ConfigProviderInterface
{
    /**
     * @var XpayrHelper
     */
    private XpayrHelper $helper;

    /**
     * @param XpayrHelper $helper
     */
    public function __construct(XpayrHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig(): array
    {
        $isActive = (bool) $this->helper->getConfig('active');

        return [
            'payment' => [
                'xpayr_gateway' => [
                    'isActive' => $isActive,
                    'title' => (string) $this->helper->getConfig('title'),
                    'network' => (string) $this->helper->getConfig('network'),
                    'currency' => (string) $this->helper->getConfig('currency'),
                ],
            ],
        ];
    }
}
