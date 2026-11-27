<?php
/**
 * Generic Button Block
 *
 * Base class for form buttons.
 * Provides common functionality like getting entity ID from request.
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Block\Adminhtml\ExportList\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\UrlInterface;
use UnusualDope\Uduny\Api\ExportListRepositoryInterface;

class GenericButton
{
    /**
     * @var Context
     */
    protected Context $context;

    /**
     * @var ExportListRepositoryInterface
     */
    protected ExportListRepositoryInterface $exportListRepository;

    /**
     * @var UrlInterface
     */
    protected UrlInterface $urlBuilder;

    /**
     * Constructor
     *
     * @param Context $context
     * @param ExportListRepositoryInterface $exportListRepository
     */
    public function __construct(
        Context $context,
        ExportListRepositoryInterface $exportListRepository
    ) {
        $this->context = $context;
        $this->exportListRepository = $exportListRepository;
        $this->urlBuilder = $context->getUrlBuilder();
    }

    /**
     * Get entity ID from request
     *
     * @return int|null
     */
    public function getEntityId(): ?int
    {
        $id = $this->context->getRequest()->getParam('entity_id');
        return $id ? (int) $id : null;
    }

    /**
     * Generate URL by route and parameters
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->urlBuilder->getUrl($route, $params);
    }
}

