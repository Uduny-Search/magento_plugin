<?php
/**
 * Actions Column for Export List Grid
 *
 * This class adds the "Actions" dropdown to each row in the grid.
 * It provides Edit and Delete links for each export list.
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class ExportListActions extends Column
{
    /**
     * URL paths
     */
    private const URL_PATH_EDIT = 'uduny/exportlist/edit';
    private const URL_PATH_DELETE = 'uduny/exportlist/delete';

    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Prepare data source
     *
     * Adds action links to each row
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['entity_id'])) {
                    $name = $this->getData('name');

                    // Edit action
                    $item[$name]['edit'] = [
                        'href' => $this->urlBuilder->getUrl(
                            self::URL_PATH_EDIT,
                            ['entity_id' => $item['entity_id']]
                        ),
                        'label' => __('Edit'),
                    ];

                    // Delete action
                    $item[$name]['delete'] = [
                        'href' => $this->urlBuilder->getUrl(
                            self::URL_PATH_DELETE,
                            ['entity_id' => $item['entity_id']]
                        ),
                        'label' => __('Delete'),
                        'confirm' => [
                            'title' => __('Delete "%1"', $item['name'] ?? ''),
                            'message' => __('Are you sure you want to delete this export list?'),
                        ],
                        'post' => true,
                    ];
                }
            }
        }

        return $dataSource;
    }
}

