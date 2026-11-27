<?php
/**
 * Save and Continue Button Block
 *
 * Renders the "Save and Continue Edit" button on the form.
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Block\Adminhtml\ExportList\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class SaveAndContinueButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * Get button data
     *
     * @return array
     */
    public function getButtonData(): array
    {
        return [
            'label' => __('Save and Continue Edit'),
            'class' => 'save',
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => 'uduny_exportlist_form.uduny_exportlist_form',
                                'actionName' => 'save',
                                'params' => [
                                    true,
                                    [
                                        'back' => 'continue',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'sort_order' => 80,
        ];
    }
}

