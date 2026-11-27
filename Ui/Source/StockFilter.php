<?php
/**
 * Source Model: Stock Filter Options
 *
 * Provides options for the "Stock Filter" dropdown in the grid and form.
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Ui\Source;

use Magento\Framework\Data\OptionSourceInterface;
use UnusualDope\Uduny\Api\Data\ExportListInterface;

class StockFilter implements OptionSourceInterface
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
                'value' => ExportListInterface::STOCK_ALL,
                'label' => __('All Products')
            ],
            [
                'value' => ExportListInterface::STOCK_IN_STOCK,
                'label' => __('In Stock Only')
            ],
            [
                'value' => ExportListInterface::STOCK_IN_STOCK_BACKORDER,
                'label' => __('In Stock + Backorder')
            ],
        ];
    }
}

