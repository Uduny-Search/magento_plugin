<?php
/**
 * Data Provider for Export List Form
 *
 * This class provides data to the UI Component form.
 * When editing, it loads the existing record.
 * When creating new, it provides default values.
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Ui\DataProvider\ExportList;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use UnusualDope\Uduny\Model\ResourceModel\ExportList\CollectionFactory;

class FormDataProvider extends AbstractDataProvider
{
    /**
     * @var DataPersistorInterface
     */
    private DataPersistorInterface $dataPersistor;

    /**
     * @var array|null
     */
    private ?array $loadedData = null;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
    }

    /**
     * Get data for the form
     *
     * @return array
     */
    public function getData(): array
    {
        if ($this->loadedData !== null) {
            return $this->loadedData;
        }

        $this->loadedData = [];

        // Load existing data if editing
        $items = $this->collection->getItems();
        foreach ($items as $item) {
            $this->loadedData[$item->getEntityId()] = $this->prepareFormData($item->getData());
        }

        // Check for persisted data (after validation error)
        $data = $this->dataPersistor->get('uduny_exportlist');
        if (!empty($data)) {
            $item = $this->collection->getNewEmptyItem();
            $item->setData($data);
            $this->loadedData[$item->getEntityId() ?? 'new'] = $this->prepareFormData($item->getData());
            $this->dataPersistor->clear('uduny_exportlist');
        }

        return $this->loadedData;
    }

    /**
     * Convert CSV ID fields into string arrays expected by ui-select components
     *
     * @param array $data
     * @return array
     */
    private function prepareFormData(array $data): array
    {
        foreach (['category_ids', 'exclude_category_ids', 'brand_ids'] as $field) {
            if (!isset($data[$field]) || $data[$field] === null || $data[$field] === '') {
                $data[$field] = [];
                continue;
            }

            if (is_array($data[$field])) {
                $data[$field] = array_values(array_filter(array_map('strval', $data[$field]), 'strlen'));
                continue;
            }

            $data[$field] = array_values(array_filter(
                array_map('trim', explode(',', (string) $data[$field])),
                'strlen'
            ));
        }

        return $data;
    }
}

