<?php
namespace Xpayr\Gateway\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Xpayr\Gateway\Helper\Data as XpayrHelper;

class Network implements OptionSourceInterface
{
    private XpayrHelper $helper;

    public function __construct(XpayrHelper $helper)
    {
        $this->helper = $helper;
    }

    public function toOptionArray(): array
    {
        $rows = $this->helper->fetchNetworkCatalog();
        if (!$rows) {
            return [
                ['value' => 'bsc-testnet', 'label' => __('BSC Testnet')],
                ['value' => 'base-sepolia', 'label' => __('Base Sepolia')],
                ['value' => 'polygon-amoy', 'label' => __('Polygon Amoy')],
            ];
        }

        $options = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $network = trim((string) ($row['network_key'] ?? $row['network'] ?? ''));
            if ($network === '') {
                continue;
            }
            $name = trim((string) ($row['network_name'] ?? $network));
            $options[] = [
                'value' => $network,
                'label' => $name,
            ];
        }

        return $options ?: [
            ['value' => 'bsc-testnet', 'label' => __('BSC Testnet')],
        ];
    }
}

