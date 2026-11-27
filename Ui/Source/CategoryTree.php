<?php
/**
 * Category Tree Option Source
 *
 * Provides hierarchical category tree options (with optgroup nesting)
 * for Magento's native ui-select category tree component.
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Ui\Source;

use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class CategoryTree implements OptionSourceInterface
{
    /**
     * @var CategoryCollectionFactory
     */
    private CategoryCollectionFactory $categoryCollectionFactory;

    /**
     * @var array|null
     */
    private ?array $categoriesTree = null;

    /**
     * Constructor
     *
     * @param CategoryCollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * Return hierarchical category tree options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        if ($this->categoriesTree !== null) {
            return $this->categoriesTree;
        }

        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'is_active', 'parent_id', 'position']);
        $collection->addAttributeToFilter('entity_id', ['neq' => CategoryModel::TREE_ROOT_ID]);
        $collection->addOrderField('position');

        $categoryById = [
            CategoryModel::TREE_ROOT_ID => [
                'value' => (string) CategoryModel::TREE_ROOT_ID,
            ],
        ];

        foreach ($collection as $category) {
            $categoryId = (int) $category->getId();
            $parentId = (int) $category->getParentId();

            foreach ([$categoryId, $parentId] as $id) {
                if (!isset($categoryById[$id])) {
                    $categoryById[$id] = ['value' => (string) $id];
                }
            }

            $categoryById[$categoryId]['is_active'] = (string) $category->getIsActive();
            $categoryById[$categoryId]['label'] = (string) $category->getName();
            $categoryById[$parentId]['optgroup'][] = &$categoryById[$categoryId];
        }

        $this->categoriesTree = $categoryById[CategoryModel::TREE_ROOT_ID]['optgroup'] ?? [];

        return $this->categoriesTree;
    }
}
