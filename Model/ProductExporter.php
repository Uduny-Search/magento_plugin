<?php
/**
 * Product Exporter Service
 *
 * Builds the product collection from an ExportList and formats items in the
 * Uduny JSON feed structure, supporting:
 *   - Standard single-currency feeds (Option 1: no `prices` array)
 *   - Price-list feeds with 1+ locations/currencies (Option 2 & multi-currency/multi-store)
 *   - Configurable products with child `variants`, `combinations`, and per-variant `prices`
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use UnusualDope\Uduny\Api\Data\ExportListInterface;

class ProductExporter
{
    private const EXCLUDED_ATTRIBUTES = [
        'manufacturer',
        'brand',
        'price',
        'special_price',
        'cost',
        'tier_price',
        'msrp',
        'weight',
        'tax_class_id',
        'status',
        'visibility',
        'quantity_and_stock_status',
    ];

    /**
     * @var ProductCollectionFactory
     */
    private ProductCollectionFactory $productCollectionFactory;

    /**
     * @var CategoryCollectionFactory
     */
    private CategoryCollectionFactory $categoryCollectionFactory;

    /**
     * @var AttributeCollectionFactory
     */
    private AttributeCollectionFactory $attributeCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var PriceContextResolver
     */
    private PriceContextResolver $priceContextResolver;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * Constructor
     */
    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        AttributeCollectionFactory $attributeCollectionFactory,
        StoreManagerInterface $storeManager,
        ResourceConnection $resourceConnection,
        PriceContextResolver $priceContextResolver,
        Config $config,
        Logger $logger
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->storeManager = $storeManager;
        $this->resourceConnection = $resourceConnection;
        $this->priceContextResolver = $priceContextResolver;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Export products based on export list configuration
     *
     * @param ExportListInterface $exportList
     * @return array<int, array<string, mixed>>
     */
    public function export(ExportListInterface $exportList): array
    {
        $this->logger->debug('Starting export for: ' . $exportList->getName());

        $storeId = (int) ($exportList->getStoreId() ?: $this->storeManager->getStore()->getId());

        $contexts = $this->priceContextResolver->getContexts($storeId);
        $primaryContext = $this->priceContextResolver->getPrimaryContext($storeId, $contexts);
        $includePrices = $this->priceContextResolver->shouldIncludePrices($storeId, $contexts);

        $exportableAttributes = $this->loadExportableAttributeCodes();

        // Build product collection
        $collection = $this->productCollectionFactory->create();
        $collection->setStoreId($storeId);
        $collection->addAttributeToSelect('*');
        $collection->addUrlRewrite();

        $this->applyStatusFilter($collection, $exportList->getStatusFilter());
        $this->applyStockFilter($collection, $exportList->getStockFilter());

        $categoryIds = $exportList->getCategoryIdsArray();
        if (!empty($categoryIds)) {
            $collection->addCategoriesFilter(['in' => $categoryIds]);
        }

        $excludeCategoryIds = $exportList->getExcludeCategoryIdsArray();
        if (!empty($excludeCategoryIds)) {
            $this->applyExcludeCategoryFilter($collection, $excludeCategoryIds);
        }

        $brandIds = $exportList->getBrandIdsArray();
        if (!empty($brandIds)) {
            $collection->addAttributeToFilter('manufacturer', ['in' => $brandIds]);
        }

        $collection->addAttributeToFilter(
            'visibility',
            ['in' => [
                Visibility::VISIBILITY_IN_CATALOG,
                Visibility::VISIBILITY_IN_SEARCH,
                Visibility::VISIBILITY_BOTH,
            ]]
        );

        $this->logger->debug('Collection size: ' . $collection->getSize());

        // Collect all parent products & configurable children so we can batch-load prices, stock, and categories
        $parentProducts = [];
        $childrenByParentId = [];
        $allProductIds = [];
        $allCategoryIds = [];

        foreach ($collection as $product) {
            $parentId = (int) $product->getId();
            $parentProducts[$parentId] = $product;
            $allProductIds[] = $parentId;

            foreach ((array) $product->getCategoryIds() as $catId) {
                $allCategoryIds[] = (int) $catId;
            }

            if ($product->getTypeId() === Configurable::TYPE_CODE) {
                $children = $this->getConfigurableChildren($product, $storeId, $exportList);
                $childrenByParentId[$parentId] = $children;
                foreach ($children as $child) {
                    $allProductIds[] = (int) $child->getId();
                }
            }
        }

        $indexedPrices = $this->priceContextResolver->loadIndexedPrices($allProductIds);
        $productWebsiteIdsMap = $this->priceContextResolver->loadProductWebsiteIds($allProductIds);
        $stockMap = $this->loadStockMap($allProductIds);
        $categoryNamesMap = $this->loadCategoryNamesMap($allCategoryIds, $storeId);
        $mediaBaseUrl = $this->storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

        $items = [];
        foreach ($parentProducts as $parentId => $product) {
            $children = $childrenByParentId[$parentId] ?? [];
            $items[] = $this->formatProduct(
                $product,
                $children,
                $storeId,
                $exportList->getStockFilter(),
                $mediaBaseUrl,
                $contexts,
                $primaryContext,
                $includePrices,
                $indexedPrices,
                $productWebsiteIdsMap,
                $stockMap,
                $categoryNamesMap,
                $exportableAttributes
            );
        }

        return $items;
    }

    /**
     * Apply product status filter
     */
    private function applyStatusFilter($collection, ?string $statusFilter): void
    {
        switch ($statusFilter) {
            case ExportListInterface::STATUS_ENABLED:
                $collection->addAttributeToFilter('status', Status::STATUS_ENABLED);
                break;
            case ExportListInterface::STATUS_DISABLED:
                $collection->addAttributeToFilter('status', Status::STATUS_DISABLED);
                break;
            case ExportListInterface::STATUS_ALL:
            default:
                break;
        }
    }

    /**
     * Apply stock availability filter
     */
    private function applyStockFilter($collection, ?string $stockFilter): void
    {
        switch ($stockFilter) {
            case ExportListInterface::STOCK_IN_STOCK:
                $collection->joinField(
                    'is_in_stock',
                    'cataloginventory_stock_item',
                    'is_in_stock',
                    'product_id=entity_id',
                    '{{table}}.stock_id=1',
                    'left'
                );
                $collection->addFieldToFilter('is_in_stock', 1);
                break;

            case ExportListInterface::STOCK_IN_STOCK_BACKORDER:
                $collection->joinField(
                    'is_in_stock',
                    'cataloginventory_stock_item',
                    'is_in_stock',
                    'product_id=entity_id',
                    '{{table}}.stock_id=1',
                    'left'
                );
                $collection->joinField(
                    'backorders',
                    'cataloginventory_stock_item',
                    'backorders',
                    'product_id=entity_id',
                    '{{table}}.stock_id=1',
                    'left'
                );
                $collection->addFieldToFilter(
                    [
                        ['attribute' => 'is_in_stock', 'eq' => 1],
                        ['attribute' => 'backorders', 'gt' => 0],
                    ]
                );
                break;

            case ExportListInterface::STOCK_ALL:
            default:
                break;
        }
    }

    /**
     * Apply category exclusion filter
     */
    private function applyExcludeCategoryFilter($collection, array $excludeCategoryIds): void
    {
        if (empty($excludeCategoryIds)) {
            return;
        }

        $connection = $collection->getConnection();
        $categoryProductTable = $collection->getTable('catalog_category_product');

        $subSelect = $connection->select()
            ->from($categoryProductTable, ['product_id'])
            ->where('category_id IN (?)', $excludeCategoryIds);

        $collection->getSelect()->where('e.entity_id NOT IN (?)', $subSelect);
    }

    /**
     * Load configurable child products filtered by status.
     *
     * @return ProductInterface[]
     */
    private function getConfigurableChildren(
        ProductInterface $parent,
        int $storeId,
        ExportListInterface $exportList
    ): array {
        /** @var Configurable $typeInstance */
        $typeInstance = $parent->getTypeInstance();
        $typeInstance->setStoreFilter($storeId, $parent);

        $usedProducts = $typeInstance->getUsedProducts($parent);
        $statusFilter = $exportList->getStatusFilter();

        $children = [];
        foreach ($usedProducts as $child) {
            if ($statusFilter === ExportListInterface::STATUS_ENABLED
                && (int) $child->getStatus() !== Status::STATUS_ENABLED
            ) {
                continue;
            }
            if ($statusFilter === ExportListInterface::STATUS_DISABLED
                && (int) $child->getStatus() !== Status::STATUS_DISABLED
            ) {
                continue;
            }
            $children[] = $child;
        }

        return $children;
    }

    /**
     * Format a product item in the Uduny JSON feed schema.
     *
     * @param ProductInterface $product
     * @param ProductInterface[] $children
     * @param int $storeId
     * @param string $mediaBaseUrl
     * @param array<int, array<string, mixed>> $contexts
     * @param array<string, mixed> $primaryContext
     * @param bool $includePrices
     * @param array<int, array<int, array{price: float, final_price: float}>> $indexedPrices
     * @param array<int, array{qty: float, is_in_stock: bool, backorders: int}> $stockMap
     * @param array<int, string> $categoryNamesMap
     * @param string[] $exportableAttributes
     * @return array<string, mixed>
     */
    private function formatProduct(
        ProductInterface $product,
        array $children,
        int $storeId,
        ?string $stockFilter,
        string $mediaBaseUrl,
        array $contexts,
        array $primaryContext,
        bool $includePrices,
        array $indexedPrices,
        array $productWebsiteIdsMap,
        array $stockMap,
        array $categoryNamesMap,
        array $exportableAttributes
    ): array {
        $productId = (int) $product->getId();
        $productUrl = (string) $product->getProductUrl();
        $imageUrl = $this->getProductImageUrl($product, $mediaBaseUrl);

        $rawDescription = (string) ($product->getData('description') ?: $product->getData('short_description') ?: '');
        $description = trim(preg_replace('/\s+/', ' ', strip_tags($rawDescription)) ?? '');

        // Categories as name strings (excluding root catalog nodes)
        $categories = [];
        foreach ((array) $product->getCategoryIds() as $catId) {
            $catIdInt = (int) $catId;
            if (isset($categoryNamesMap[$catIdInt])) {
                $categories[] = $categoryNamesMap[$catIdInt];
            }
        }
        $categories = array_values(array_unique($categories));

        // Brands as array of strings
        $brands = $this->extractBrands($product);

        // Configurable super attributes (for variants)
        $superAttributes = [];
        $superAttributeCodes = [];
        if ($product->getTypeId() === Configurable::TYPE_CODE) {
            /** @var Configurable $typeInstance */
            $typeInstance = $product->getTypeInstance();
            foreach ($typeInstance->getConfigurableAttributes($product) as $configurableAttr) {
                $attr = $configurableAttr->getProductAttribute();
                if ($attr) {
                    $code = (string) $attr->getAttributeCode();
                    $label = (string) ($configurableAttr->getLabel() ?: $attr->getStoreLabel($storeId) ?: $attr->getFrontendLabel() ?: ucfirst($code));
                    $superAttributes[$code] = $label;
                    $superAttributeCodes[] = $code;
                }
            }
        }

        // Additional descriptive attributes
        $attributes = $this->extractAttributes($product, $exportableAttributes, $superAttributeCodes);

        // Build variants for configurable products
        $variants = [];
        if ($product->getTypeId() === Configurable::TYPE_CODE && !empty($children)) {
            $variants = $this->buildVariants(
                $children,
                $superAttributes,
                $stockFilter,
                $productUrl,
                $imageUrl,
                $mediaBaseUrl,
                $contexts,
                $primaryContext,
                $includePrices,
                $indexedPrices,
                $productWebsiteIdsMap,
                $stockMap
            );
        }

        // Resolve parent prices and quantity
        if (!empty($variants)) {
            $quantity = $this->normalizeQuantity(array_sum(array_column($variants, 'quantity')));
            $primaryPrice = $this->resolveLowestVariantPrice($variants);
            $prices = $includePrices ? $this->aggregateParentPricesFromVariants($variants) : [];
        } else {
            $quantity = $this->normalizeQuantity($stockMap[$productId]['qty'] ?? 0.0);
            $primaryPrice = $this->priceContextResolver->resolvePriceForContext(
                $product,
                $primaryContext,
                $indexedPrices
            );
            $prices = $includePrices
                ? $this->priceContextResolver->buildPricesArray(
                    $product,
                    $contexts,
                    $indexedPrices,
                    $productWebsiteIdsMap
                )
                : [];
        }

        $item = [
            'id' => (string) $productId,
            'reference' => (string) $product->getSku(),
            'name' => (string) $product->getName(),
            'url' => $productUrl,
            'image' => $imageUrl,
            'description' => $description,
            'date_created' => (string) $product->getCreatedAt(),
            'price' => $primaryPrice['price'],
            'sale_price' => $primaryPrice['sale'],
            'quantity' => $quantity,
            'categories' => $categories,
            'brands' => $brands,
        ];

        if (!empty($attributes)) {
            $item['attributes'] = $attributes;
        }

        if ($includePrices) {
            // Guaranteed indexed JSON array: [{ location, currency, price, sale }, ...]
            $item['prices'] = array_values($prices);
        }

        if (!empty($variants)) {
            $item['variants'] = array_values($variants);
        }

        return $item;
    }

    /**
     * Build variants array for configurable product children.
     *
     * @param ProductInterface[] $children
     * @param array<string, string> $superAttributes [attribute_code => label]
     * @param string $parentUrl
     * @param string|null $parentImageUrl
     * @param string $mediaBaseUrl
     * @param array<int, array<string, mixed>> $contexts
     * @param array<string, mixed> $primaryContext
     * @param bool $includePrices
     * @param array<int, array<int, array{price: float, final_price: float}>> $indexedPrices
     * @param array<int, array{qty: float, is_in_stock: bool, backorders: int}> $stockMap
     * @return array<int, array<string, mixed>>
     */
    private function buildVariants(
        array $children,
        array $superAttributes,
        ?string $stockFilter,
        string $parentUrl,
        ?string $parentImageUrl,
        string $mediaBaseUrl,
        array $contexts,
        array $primaryContext,
        bool $includePrices,
        array $indexedPrices,
        array $productWebsiteIdsMap,
        array $stockMap
    ): array {
        $variants = [];

        foreach ($children as $child) {
            $childId = (int) $child->getId();
            $childStock = $stockMap[$childId] ?? ['qty' => 0.0, 'is_in_stock' => true, 'backorders' => 0];
            if ($stockFilter === ExportListInterface::STOCK_IN_STOCK && !$childStock['is_in_stock']) {
                continue;
            }
            if ($stockFilter === ExportListInterface::STOCK_IN_STOCK_BACKORDER
                && !$childStock['is_in_stock']
                && $childStock['backorders'] <= 0
            ) {
                continue;
            }

            $childPrice = $this->priceContextResolver->resolvePriceForContext(
                $child,
                $primaryContext,
                $indexedPrices
            );
            $childQty = $this->normalizeQuantity($childStock['qty']);
            $childImage = $this->getProductImageUrl($child, $mediaBaseUrl) ?: $parentImageUrl;

            $combinations = [];
            $queryParams = [];
            foreach ($superAttributes as $code => $groupLabel) {
                $val = $this->getAttributeDisplayValue($child, $code);
                if ($val !== null && $val !== '') {
                    $combinations[] = [
                        'group' => $groupLabel,
                        'value' => $val,
                    ];
                    $queryParams[$code] = $val;
                }
            }

            $variantUrl = $parentUrl;
            if (!empty($queryParams)) {
                $separator = strpos($parentUrl, '?') === false ? '?' : '&';
                $variantUrl = $parentUrl . $separator . http_build_query($queryParams);
            }

            $variant = [
                'variant_id' => (string) $childId,
                'name' => (string) $child->getName(),
                'price' => $childPrice['price'],
                'sale' => $childPrice['sale'],
                'quantity' => $childQty,
                'image' => $childImage,
                'url' => $variantUrl,
                'combinations' => $combinations,
            ];

            if ($includePrices) {
                $variant['prices'] = array_values(
                    $this->priceContextResolver->buildPricesArray(
                        $child,
                        $contexts,
                        $indexedPrices,
                        $productWebsiteIdsMap
                    )
                );
            }

            $variants[] = $variant;
        }

        return $variants;
    }

    /**
     * Pick lowest effective price/sale pair across variants for configurable parent.
     *
     * @param array<int, array<string, mixed>> $variants
     * @return array{price: float, sale: float}
     */
    private function resolveLowestVariantPrice(array $variants): array
    {
        $best = null;
        $bestEffective = null;

        foreach ($variants as $variant) {
            $price = (float) $variant['price'];
            $sale = (float) $variant['sale'];
            $effective = ($sale > 0.0 && $sale < $price) ? $sale : $price;

            if ($bestEffective === null || $effective < $bestEffective) {
                $bestEffective = $effective;
                $best = ['price' => $price, 'sale' => $sale];
            }
        }

        return $best ?? ['price' => 0.0, 'sale' => 0.0];
    }

    /**
     * Aggregate per-(location, currency) minimum prices for configurable parent from its variants.
     * Always returns an indexed array `[{ location, currency, price, sale }, ...]`.
     *
     * @param array<int, array<string, mixed>> $variants
     * @return array<int, array{location: string, currency: string, price: float, sale: float}>
     */
    private function aggregateParentPricesFromVariants(array $variants): array
    {
        $byKey = [];

        foreach ($variants as $variant) {
            foreach ((array) ($variant['prices'] ?? []) as $entry) {
                $key = $entry['location'] . ':' . $entry['currency'];
                $price = (float) $entry['price'];
                $sale = (float) $entry['sale'];
                $effective = ($sale > 0.0 && $sale < $price) ? $sale : $price;

                if (!isset($byKey[$key]) || $effective < $byKey[$key]['_effective']) {
                    $byKey[$key] = [
                        'location' => (string) $entry['location'],
                        'currency' => (string) $entry['currency'],
                        'price' => $price,
                        'sale' => $sale,
                        '_effective' => $effective,
                    ];
                }
            }
        }

        $result = [];
        foreach ($byKey as $row) {
            unset($row['_effective']);
            $result[] = $row;
        }

        return array_values($result);
    }

    /**
     * Extract brand names as a string array.
     *
     * @param ProductInterface $product
     * @return string[]
     */
    private function extractBrands(ProductInterface $product): array
    {
        $brands = [];
        foreach (['manufacturer', 'brand'] as $code) {
            $val = $this->getAttributeDisplayValue($product, $code);
            if ($val !== null && $val !== '') {
                $brands[] = $val;
            }
        }

        return array_values(array_unique($brands));
    }

    /**
     * Extract descriptive user-defined product attributes as key => value map.
     *
     * @param ProductInterface $product
     * @param string[] $exportableAttributeCodes
     * @param string[] $excludeCodes
     * @return array<string, string>
     */
    private function extractAttributes(
        ProductInterface $product,
        array $exportableAttributeCodes,
        array $excludeCodes
    ): array {
        $attributes = [];

        foreach ($exportableAttributeCodes as $code) {
            if (in_array($code, $excludeCodes, true)) {
                continue;
            }

            $val = $this->getAttributeDisplayValue($product, $code);
            if ($val !== null && $val !== '') {
                $attributes[$code] = $val;
            }
        }

        return $attributes;
    }

    /**
     * Get human-readable attribute value (resolves select/multiselect option labels).
     *
     * @param ProductInterface $product
     * @param string $code
     * @return string|null
     */
    private function getAttributeDisplayValue(ProductInterface $product, string $code): ?string
    {
        $raw = $product->getData($code);
        if ($raw === null || $raw === '' || $raw === false) {
            return null;
        }

        $attribute = $product->getResource()->getAttribute($code);
        if (!$attribute) {
            return is_scalar($raw) ? trim((string) $raw) : null;
        }

        if ($attribute->usesSource()) {
            $text = $product->getAttributeText($code);
            if (is_array($text)) {
                $joined = implode(', ', array_filter(array_map('strval', $text)));
                return $joined !== '' ? $joined : null;
            }
            if ($text !== false && $text !== null && (string) $text !== '') {
                return (string) $text;
            }
            return null;
        }

        if (is_scalar($raw)) {
            $str = trim((string) $raw);
            return $str !== '' ? $str : null;
        }

        return null;
    }

    /**
     * Load user-defined attribute codes eligible for the `attributes` object.
     *
     * @return string[]
     */
    private function loadExportableAttributeCodes(): array
    {
        $collection = $this->attributeCollectionFactory->create();
        $collection->addFieldToFilter('is_user_defined', 1);
        $collection->addFieldToFilter(
            ['is_visible_on_front', 'is_filterable', 'is_searchable'],
            [
                ['eq' => 1],
                ['gt' => 0],
                ['eq' => 1],
            ]
        );

        $codes = [];
        foreach ($collection as $attr) {
            $code = (string) $attr->getAttributeCode();
            if ($code !== '' && !in_array($code, self::EXCLUDED_ATTRIBUTES, true)) {
                $codes[] = $code;
            }
        }

        return array_values(array_unique($codes));
    }

    /**
     * Batch-load stock quantities and availability for product IDs.
     *
     * @param int[] $productIds
     * @return array<int, array{qty: float, is_in_stock: bool, backorders: int}>
     */
    private function loadStockMap(array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if (empty($productIds)) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('cataloginventory_stock_item');

        $select = $connection->select()
            ->from($table, ['product_id', 'qty', 'is_in_stock', 'backorders'])
            ->where('product_id IN (?)', $productIds)
            ->where('stock_id = ?', 1);

        $rows = $connection->fetchAll($select);
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['product_id']] = [
                'qty' => (float) $row['qty'],
                'is_in_stock' => (bool) $row['is_in_stock'],
                'backorders' => (int) $row['backorders'],
            ];
        }

        return $map;
    }

    /**
     * Batch-load category names for category IDs (excluding root levels <= 1).
     *
     * @param int[] $categoryIds
     * @param int $storeId
     * @return array<int, string>
     */
    private function loadCategoryNamesMap(array $categoryIds, int $storeId): array
    {
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));
        if (empty($categoryIds)) {
            return [];
        }

        $collection = $this->categoryCollectionFactory->create();
        $collection->setStoreId($storeId);
        $collection->addAttributeToSelect('name');
        $collection->addFieldToFilter('entity_id', ['in' => $categoryIds]);
        $collection->addFieldToFilter('level', ['gt' => 1]);

        $map = [];
        foreach ($collection as $category) {
            $name = trim((string) $category->getName());
            if ($name !== '') {
                $map[(int) $category->getId()] = $name;
            }
        }

        return $map;
    }

    /**
     * Get full product image URL or null when unavailable.
     */
    private function getProductImageUrl(ProductInterface $product, string $mediaBaseUrl): ?string
    {
        $image = $product->getData('image') ?: $product->getData('small_image') ?: $product->getData('thumbnail');
        if (!$image || $image === 'no_selection') {
            return null;
        }

        return rtrim($mediaBaseUrl, '/') . '/catalog/product/' . ltrim((string) $image, '/');
    }

    /**
     * Return integer when whole number (e.g. 45), otherwise float rounded to 2 decimals.
     *
     * @param float $qty
     * @return int|float
     */
    private function normalizeQuantity(float $qty)
    {
        $rounded = round($qty, 2);
        return (floor($rounded) === $rounded) ? (int) $rounded : $rounded;
    }
}
