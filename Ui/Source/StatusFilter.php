<?php
/**
 * Source Model: Status Filter Options
 *
 * Provides options for the "Status Filter" dropdown in the grid and form.
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Ui\Source;

use Magento\Framework\Data\OptionSourceInterface;
use UnusualDope\Uduny\Api\Data\ExportListInterface;

class StatusFilter implements OptionSourceInterface
{
    /**
     * Get options as array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => ExportListInterface::STATUS_ALL,
                'label' => __('All Products')
            ],
            [
                'value' => ExportListInterface::STATUS_ENABLED,
                'label' => __('Enabled Only')
            ],
            [
                'value' => ExportListInterface::STATUS_DISABLED,
                'label' => __('Disabled Only')
            ],
        ];
    }
}

