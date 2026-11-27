<?php
/**
 * Price List Export Mode Source Model
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class PricelistMode implements OptionSourceInterface
{
    public const MODE_AUTO = 'auto';
    public const MODE_ALWAYS = 'always';
    public const MODE_NEVER = 'never';

    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::MODE_AUTO,
                'label' => __('Auto-detect (include prices array only when multi-currency or multi-store)'),
            ],
            [
                'value' => self::MODE_ALWAYS,
                'label' => __('Always include prices array (even for 1 currency / 1 location)'),
            ],
            [
                'value' => self::MODE_NEVER,
                'label' => __('Disabled (standard single-currency feed without prices array)'),
            ],
        ];
    }
}
