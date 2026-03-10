<?php
namespace Xpayr\Gateway\Logger;

use Monolog\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    protected $loggerType = Logger::INFO;
    protected $fileName = '/var/log/xpayr-magento2.log';
}
