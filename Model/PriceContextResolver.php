<?php
/**
 * Price Context Resolver
 *
 * Resolves multi-store (website/country) and multi-currency price contexts
 * and computes regular + sale prices for products across all active contexts.
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use UnusualDope\Uduny\Model\Config\Source\PricelistMode;

class PriceContextResolver
{
    private const XML_PATH_DEFAULT_COUNTRY = 'general/country/default';
    private const XML_PATH_CURRENCY_BASE = 'currency/options/base';
    private const XML_PATH_CURRENCY_DEFAULT = 'currency/options/default';
    private const XML_PATH_CURRENCY_ALLOW = 'currency/options/allow';

    /**
     * Default ISO currency -> primary country fallback for secondary currencies
     * enabled on a single store view without a dedicated store view.
     */
    private const CURRENCY_COUNTRY_FALLBACK = [
        'USD' => 'US',
        'GBP' => 'GB',
        'CHF' => 'CH',
        'JPY' => 'JP',
        'CAD' => 'CA',
        'AUD' => 'AU',
        'NZD' => 'NZ',
        'SEK' => 'SE',
        'NOK' => 'NO',
        'DKK' => 'DK',
        'PLN' => 'PL',
        'CZK' => 'CZ',
        'HUF' => 'HU',
        'RON' => 'RO',
        'BGN' => 'BG',
        'BRL' => 'BR',
        'MXN' => 'MX',
        'CNY' => 'CN',
        'HKD' => 'HK',
        'SGD' => 'SG',
        'KRW' => 'KR',
        'INR' => 'IN',
        'TRY' => 'TR',
        'ZAR' => 'ZA',
        'AED' => 'AE',
        'SAR' => 'SA',
        'ILS' => 'IL',
        'EUR' => 'EU',
    ];

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var CurrencyFactory
     */
    private CurrencyFactory $currencyFactory;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var array<string, \Magento\Directory\Model\Currency>
     */
    private array $currencyCache = [];

    /**
     * Constructor
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        CurrencyFactory $currencyFactory,
        ResourceConnection $resourceConnection,
        Config $config
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->currencyFactory = $currencyFactory;
        $this->resourceConnection = $resourceConnection;
        $this->config = $config;
    }

    /**
     * Discover all active (location, currency) price contexts across Magento stores.
     *
     * @param int $primaryStoreId
     * @return array<int, array<string, mixed>>
     */
    public function getContexts(int $primaryStoreId): array
    {
        $stores = [];
        foreach ($this->storeManager->getStores(false) as $store) {
            if ($store->getIsActive()) {
                $stores[(int) $store->getId()] = $store;
            }
        }

        // Ensure primary store comes first
        if (isset($stores[$primaryStoreId])) {
            $primary = $stores[$primaryStoreId];
            unset($stores[$primaryStoreId]);
            $stores = [$primaryStoreId => $primary] + $stores;
        }

        $customMap = $this->config->getCurrencyLocationMap();
        $contexts = [];
        $seen = [];

        // Pass 1: Each active store view's own default currency + default country
        foreach ($stores as $storeId => $store) {
            $websiteId = (int) $store->getWebsiteId();
            $location = $this->getStoreCountry($storeId);
            $baseCurrency = $this->getStoreBaseCurrency($storeId);
            $defaultCurrency = $this->getStoreDefaultCurrency($storeId, $baseCurrency);

            $rate = $this->getConversionRate($baseCurrency, $defaultCurrency);
            if ($rate <= 0.0) {
                continue;
            }

            $key = $location . ':' . $defaultCurrency;
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $contexts[] = [
                    'store_id' => $storeId,
                    'website_id' => $websiteId,
                    'location' => $location,
                    'base_currency' => $baseCurrency,
                    'currency' => $defaultCurrency,
                    'rate' => $rate,
                    'is_primary' => ($storeId === $primaryStoreId),
                ];
            }
        }

        // Pass 2: Any additional allowed currencies on active store views
        foreach ($stores as $storeId => $store) {
            $websiteId = (int) $store->getWebsiteId();
            $storeLocation = $this->getStoreCountry($storeId);
            $baseCurrency = $this->getStoreBaseCurrency($storeId);
            $defaultCurrency = $this->getStoreDefaultCurrency($storeId, $baseCurrency);
            $allowedCurrencies = $this->getStoreAllowedCurrencies($storeId, $defaultCurrency);

            foreach ($allowedCurrencies as $currencyCode) {
                if ($currencyCode === $defaultCurrency) {
                    continue;
                }

                $rate = $this->getConversionRate($baseCurrency, $currencyCode);
                if ($rate <= 0.0) {
                    continue;
                }

                $location = $customMap[$currencyCode]
                    ?? self::CURRENCY_COUNTRY_FALLBACK[$currencyCode]
                    ?? $storeLocation;

                $key = $location . ':' . $currencyCode;
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $contexts[] = [
                        'store_id' => $storeId,
                        'website_id' => $websiteId,
                        'location' => $location,
                        'base_currency' => $baseCurrency,
                        'currency' => $currencyCode,
                        'rate' => $rate,
                        'is_primary' => false,
                    ];
                }
            }
        }

        return array_values($contexts);
    }

    /**
     * Get the primary price context for the export store.
     *
     * @param int $primaryStoreId
     * @param array<int, array<string, mixed>> $contexts
     * @return array<string, mixed>
     */
    public function getPrimaryContext(int $primaryStoreId, array $contexts): array
    {
        foreach ($contexts as $context) {
            if (!empty($context['is_primary'])) {
                return $context;
            }
        }

        if (!empty($contexts)) {
            return $contexts[0];
        }

        $store = $this->storeManager->getStore($primaryStoreId);
        $baseCurrency = $this->getStoreBaseCurrency($primaryStoreId);
        $defaultCurrency = $this->getStoreDefaultCurrency($primaryStoreId, $baseCurrency);

        return [
            'store_id' => $primaryStoreId,
            'website_id' => (int) $store->getWebsiteId(),
            'location' => $this->getStoreCountry($primaryStoreId),
            'base_currency' => $baseCurrency,
            'currency' => $defaultCurrency,
            'rate' => max($this->getConversionRate($baseCurrency, $defaultCurrency), 1.0),
            'is_primary' => true,
        ];
    }

    /**
     * Determine whether the export should include the `prices` array.
     *
     * - never:  Option 1 (no prices array)
     * - always: Option 2 (always emit prices array, even for 1 currency/location)
     * - auto:   Emit prices array only when > 1 currency or location context exists
     *
     * @param int $primaryStoreId
     * @param array<int, array<string, mixed>> $contexts
     * @return bool
     */
    public function shouldIncludePrices(int $primaryStoreId, array $contexts): bool
    {
        $mode = $this->config->getPricelistMode($primaryStoreId);

        if ($mode === PricelistMode::MODE_NEVER) {
            return false;
        }

        if ($mode === PricelistMode::MODE_ALWAYS) {
            return true;
        }

        return count($contexts) > 1;
    }

    /**
     * Resolve the current visitor's UdunyStoreContext ({ country, currency })
     * so it matches the exact (location, currency) exported in the JSON feed.
     *
     * @param int $storeId
     * @param string|null $currentCurrencyCode
     * @return array{country: string, currency: string}
     */
    public function resolveVisitorContext(int $storeId, ?string $currentCurrencyCode = null): array
    {
        $storeLocation = $this->getStoreCountry($storeId);
        $baseCurrency = $this->getStoreBaseCurrency($storeId);
        $defaultCurrency = $this->getStoreDefaultCurrency($storeId, $baseCurrency);

        $currency = $currentCurrencyCode !== null && trim($currentCurrencyCode) !== ''
            ? strtoupper(trim($currentCurrencyCode))
            : $defaultCurrency;

        if ($currency === $defaultCurrency) {
            return [
                'country' => $storeLocation,
                'currency' => $currency,
            ];
        }

        // Check if another active store view natively owns this currency
        $contexts = $this->getContexts($storeId);
        foreach ($contexts as $context) {
            if ($context['currency'] === $currency) {
                return [
                    'country' => (string) $context['location'],
                    'currency' => $currency,
                ];
            }
        }

        $customMap = $this->config->getCurrencyLocationMap();
        $country = $customMap[$currency]
            ?? self::CURRENCY_COUNTRY_FALLBACK[$currency]
            ?? $storeLocation;

        return [
            'country' => $country,
            'currency' => $currency,
        ];
    }

    /**
     * Batch-load indexed website prices for product IDs (NOT LOGGED IN customer group 0).
     *
     * @param int[] $productIds
     * @return array<int, array<int, array{price: float, final_price: float}>>
     */
    public function loadIndexedPrices(array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if (empty($productIds)) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('catalog_product_index_price');

        $select = $connection->select()
            ->from($table, ['entity_id', 'website_id', 'price', 'final_price'])
            ->where('entity_id IN (?)', $productIds)
            ->where('customer_group_id = ?', 0);

        $rows = $connection->fetchAll($select);
        $result = [];

        foreach ($rows as $row) {
            $entityId = (int) $row['entity_id'];
            $websiteId = (int) $row['website_id'];
            $result[$entityId][$websiteId] = [
                'price' => (float) $row['price'],
                'final_price' => (float) $row['final_price'],
            ];
        }

        return $result;
    }

    /**
     * Batch-load assigned website IDs for product IDs.
     *
     * @param int[] $productIds
     * @return array<int, int[]>
     */
    public function loadProductWebsiteIds(array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if (empty($productIds)) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('catalog_product_website');

        $select = $connection->select()
            ->from($table, ['product_id', 'website_id'])
            ->where('product_id IN (?)', $productIds);

        $rows = $connection->fetchAll($select);
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['product_id']][] = (int) $row['website_id'];
        }

        return $map;
    }

    /**
     * Resolve regular price and active sale price (0 when not discounted) for a product in a given context.
     *
     * @param ProductInterface $product
     * @param array<string, mixed> $context
     * @param array<int, array<int, array{price: float, final_price: float}>> $indexedPrices
     * @return array{price: float, sale: float}
     */
    public function resolvePriceForContext(
        ProductInterface $product,
        array $context,
        array $indexedPrices
    ): array {
        $productId = (int) $product->getId();
        $websiteId = (int) $context['website_id'];
        $rate = (float) $context['rate'];

        if (isset($indexedPrices[$productId][$websiteId])) {
            $regularBase = (float) $indexedPrices[$productId][$websiteId]['price'];
            $finalBase = (float) $indexedPrices[$productId][$websiteId]['final_price'];
            $saleBase = ($finalBase > 0.0 && $finalBase < $regularBase) ? $finalBase : 0.0;
        } else {
            $regularBase = (float) $product->getPrice();
            $saleBase = $this->resolveActiveSpecialPrice($product, $regularBase);
        }

        $price = round($regularBase * $rate, 2);
        $sale = $saleBase > 0.0 ? round($saleBase * $rate, 2) : 0.0;

        if ($sale >= $price) {
            $sale = 0.0;
        }

        return [
            'price' => $price,
            'sale' => $sale,
        ];
    }

    /**
     * Build the `prices` array for a simple/variant product across applicable contexts.
     * Always returns an indexed PHP array (so JSON encoding is always `[...]`, never `{...}`).
     *
     * @param ProductInterface $product
     * @param array<int, array<string, mixed>> $contexts
     * @param array<int, array<int, array{price: float, final_price: float}>> $indexedPrices
     * @return array<int, array{location: string, currency: string, price: float, sale: float}>
     */
    public function buildPricesArray(
        ProductInterface $product,
        array $contexts,
        array $indexedPrices,
        array $productWebsiteIdsMap = []
    ): array {
        $productId = (int) $product->getId();
        if (isset($productWebsiteIdsMap[$productId])) {
            $websiteIds = $productWebsiteIdsMap[$productId];
        } else {
            $websiteIds = method_exists($product, 'getWebsiteIds')
                ? array_map('intval', (array) $product->getWebsiteIds())
                : [];
        }

        $prices = [];
        foreach ($contexts as $context) {
            if (!empty($websiteIds) && !in_array((int) $context['website_id'], $websiteIds, true)) {
                continue;
            }

            $resolved = $this->resolvePriceForContext($product, $context, $indexedPrices);
            $prices[] = [
                'location' => (string) $context['location'],
                'currency' => (string) $context['currency'],
                'price' => $resolved['price'],
                'sale' => $resolved['sale'],
            ];
        }

        return array_values($prices);
    }

    /**
     * Resolve active special price respecting special_from_date and special_to_date.
     *
     * @param ProductInterface $product
     * @param float $regularPrice
     * @return float
     */
    private function resolveActiveSpecialPrice(ProductInterface $product, float $regularPrice): float
    {
        $specialPrice = (float) $product->getData('special_price');
        if ($specialPrice <= 0.0 || $specialPrice >= $regularPrice) {
            return 0.0;
        }

        $now = time();
        $fromDate = $product->getData('special_from_date');
        $toDate = $product->getData('special_to_date');

        if (!empty($fromDate)) {
            $fromTs = strtotime((string) $fromDate);
            if ($fromTs !== false && $now < $fromTs) {
                return 0.0;
            }
        }

        if (!empty($toDate)) {
            $toStr = (string) $toDate;
            // Magento stores date-only special_to_date at 00:00:00, which is inclusive through end of day
            if (preg_match('/^\d{4}-\d{2}-\d{2}( 00:00:00)?$/', $toStr)) {
                $toTs = strtotime(substr($toStr, 0, 10) . ' 23:59:59');
            } else {
                $toTs = strtotime($toStr);
            }
            if ($toTs !== false && $now > $toTs) {
                return 0.0;
            }
        }

        return $specialPrice;
    }

    /**
     * Get store default country code (ISO 3166-1 alpha-2).
     *
     * @param int $storeId
     * @return string
     */
    private function getStoreCountry(int $storeId): string
    {
        $country = (string) $this->scopeConfig->getValue(
            self::XML_PATH_DEFAULT_COUNTRY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $country !== '' ? strtoupper($country) : 'US';
    }

    /**
     * Get store base currency code.
     *
     * @param int $storeId
     * @return string
     */
    private function getStoreBaseCurrency(int $storeId): string
    {
        $currency = (string) $this->scopeConfig->getValue(
            self::XML_PATH_CURRENCY_BASE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $currency !== '' ? strtoupper($currency) : 'USD';
    }

    /**
     * Get store default display currency code.
     *
     * @param int $storeId
     * @param string $fallback
     * @return string
     */
    private function getStoreDefaultCurrency(int $storeId, string $fallback): string
    {
        $currency = (string) $this->scopeConfig->getValue(
            self::XML_PATH_CURRENCY_DEFAULT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $currency !== '' ? strtoupper($currency) : $fallback;
    }

    /**
     * Get store allowed currency codes.
     *
     * @param int $storeId
     * @param string $defaultCurrency
     * @return string[]
     */
    private function getStoreAllowedCurrencies(int $storeId, string $defaultCurrency): array
    {
        $raw = (string) $this->scopeConfig->getValue(
            self::XML_PATH_CURRENCY_ALLOW,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (trim($raw) === '') {
            return [$defaultCurrency];
        }

        $codes = array_values(array_unique(array_filter(array_map(
            static fn (string $c): string => strtoupper(trim($c)),
            explode(',', $raw)
        ))));

        return !empty($codes) ? $codes : [$defaultCurrency];
    }

    /**
     * Get exchange rate from base currency to target currency.
     *
     * @param string $baseCurrency
     * @param string $targetCurrency
     * @return float
     */
    private function getConversionRate(string $baseCurrency, string $targetCurrency): float
    {
        if ($baseCurrency === $targetCurrency) {
            return 1.0;
        }

        if (!isset($this->currencyCache[$baseCurrency])) {
            $this->currencyCache[$baseCurrency] = $this->currencyFactory->create()->load($baseCurrency);
        }

        $rate = $this->currencyCache[$baseCurrency]->getRate($targetCurrency);

        return is_numeric($rate) ? (float) $rate : 0.0;
    }
}
