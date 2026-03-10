<?php
namespace Xpayr\Gateway\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\App\ObjectManager;
use Xpayr\Gateway\Helper\Data as XpayrHelper;
use Xpayr\Gateway\Logger\Logger;

class WebhookSync extends Value
{
    public function afterSave()
    {
        parent::afterSave();

        try {
            /** @var XpayrHelper $helper */
            $helper = ObjectManager::getInstance()->get(XpayrHelper::class);
            $enabled = (bool) $this->getValue();
            if ($enabled) {
                $helper->syncWebhookSecret();
            }
        } catch (\Throwable $e) {
            /** @var Logger $logger */
            $logger = ObjectManager::getInstance()->get(Logger::class);
            $logger->warning('Webhook auto-sync failed: ' . $e->getMessage());
        }

        return $this;
    }
}
