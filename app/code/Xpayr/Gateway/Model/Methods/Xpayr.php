<?php
namespace Xpayr\Gateway\Model\Methods;

use Magento\Payment\Model\Method\AbstractMethod;

class Xpayr extends AbstractMethod
{
    public const CODE = 'xpayr_gateway';

    protected $_code = self::CODE;
    protected $_isOffline = false;
    protected $_canAuthorize = false;
    protected $_canCapture = false;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
}
