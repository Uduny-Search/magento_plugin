<?php
/**
 * New Action Controller
 *
 * Forwards to the Edit controller for creating a new export list.
 * Named "NewAction" because "New" is a reserved PHP keyword.
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Controller\Adminhtml\Exportlist;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Forward;

class NewAction extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'UnusualDope_Uduny::exportlist';

    /**
     * Execute action
     *
     * Forward to edit action (same form, no entity_id)
     *
     * @return Forward
     */
    public function execute(): Forward
    {
        /** @var Forward $resultForward */
        $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
        return $resultForward->forward('edit');
    }
}

