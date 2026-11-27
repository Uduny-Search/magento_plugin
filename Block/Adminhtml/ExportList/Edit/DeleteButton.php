<?php
/**
 * Delete Button Block
 *
 * Renders the "Delete" button on the form.
 * Only shows when editing an existing record.
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Block\Adminhtml\ExportList\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * Get button data
     *
     * @return array
     */
    public function getButtonData(): array
    {
        $data = [];

        // Only show delete button when editing (entity_id exists)
        if ($this->getEntityId()) {
            $data = [
                'label' => __('Delete'),
                'class' => 'delete',
                'on_click' => sprintf(
                    "deleteConfirm('%s', '%s', {'data': {'form_key': window.FORM_KEY}})",
                    __('Are you sure you want to delete this export list?'),
                    $this->getDeleteUrl()
                ),
                'sort_order' => 20,
            ];
        }

        return $data;
    }

    /**
     * Get URL for delete action
     *
     * @return string
     */
    private function getDeleteUrl(): string
    {
        return $this->getUrl('*/*/delete', ['entity_id' => $this->getEntityId()]);
    }
}

