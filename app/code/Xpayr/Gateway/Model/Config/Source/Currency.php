<?php
namespace Xpayr\Gateway\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Xpayr\Gateway\Helper\Data as XpayrHelper;

class Currency implements OptionSourceInterface
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
                ['value' => 'USDC', 'label' => __('USDC')],
                ['value' => 'USDT', 'label' => __('USDT')],
            ];
        }

        $tokens = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            // API returns currencies nested under each network
            $currencies = $row['currencies'] ?? [];
            if (is_array($currencies)) {
                foreach ($currencies as $cur) {
                    if (!is_array($cur)) {
                        continue;
                    }
                    $symbol = strtoupper(trim((string) ($cur['symbol'] ?? '')));
                    if ($symbol !== '') {
                        $name = trim((string) ($cur['name'] ?? $symbol));
                        $tokens[$symbol] = $name;
                    }
                }
            }
            // Fallback for flat response format
            $flatSymbol = strtoupper(trim((string) ($row['symbol'] ?? '')));
            if ($flatSymbol !== '' && !isset($tokens[$flatSymbol])) {
                $tokens[$flatSymbol] = $flatSymbol;
            }
        }

        ksort($tokens, SORT_STRING);
        $options = [];
        foreach ($tokens as $symbol => $name) {
            $options[] = ['value' => $symbol, 'label' => $name];
        }

        return $options ?: [
            ['value' => 'USDC', 'label' => __('USDC')],
        ];
    }
}

