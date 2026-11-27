<?php
/**
 * Tracking & UdSearch Widget Block
 *
 * Provides storefront configuration for:
 *   - window.UdunyStoreContext ({ country, currency }) synced with the JSON export pricelist
 *   - window.UdunySearch ({ url, lang, trigger_elements }) + deferred search.js embed
 *   - UnusualDope_Uduny/js/session-tracking via text/x-magento-init
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Block;

use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use UnusualDope\Uduny\Model\Config;
use UnusualDope\Uduny\Model\PriceContextResolver;

class Tracking extends Template
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var PriceContextResolver
     */
    private PriceContextResolver $priceContextResolver;

    /**
     * @var ResolverInterface
     */
    private ResolverInterface $localeResolver;

    /**
     * Constructor
     */
    public function __construct(
        Context $context,
        Config $config,
        Json $json,
        PriceContextResolver $priceContextResolver,
        ResolverInterface $localeResolver,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->json = $json;
        $this->priceContextResolver = $priceContextResolver;
        $this->localeResolver = $localeResolver;
    }

    /**
     * Check if module is enabled
     */
    public function isEnabled(): bool
    {
        return $this->config->isEnabled($this->getCurrentStoreId());
    }

    /**
     * Check if production mode is enabled
     */
    public function isProductionMode(): bool
    {
        return $this->config->isProductionMode($this->getCurrentStoreId());
    }

    /**
     * Check if we should load tracking and search widget on this page.
     * In non-production mode, only load if ?uduny_test=1 is present.
     */
    public function shouldTrack(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        if ($this->isProductionMode()) {
            return true;
        }

        return (bool) $this->getRequest()->getParam('uduny_test');
    }

    /**
     * Get configured UdSearch script URL (e.g. https://search.yourstore.com/js/search.js)
     */
    public function getScriptUrl(): string
    {
        return $this->config->getScriptUrl($this->getCurrentStoreId());
    }

    /**
     * Whether to emit window.UdunyStoreContext on the page.
     * Mirrors the JSON feed pricelist mode so UdSearch receives cid/currency
     * only when prices are exported (multi-currency/multi-store or pricelist_mode=always).
     */
    public function shouldOutputStoreContext(): bool
    {
        $storeId = $this->getCurrentStoreId();
        $contexts = $this->priceContextResolver->getContexts($storeId);

        return $this->priceContextResolver->shouldIncludePrices($storeId, $contexts);
    }

    /**
     * Resolve current visitor's { country, currency } matching the exported JSON feed.
     *
     * @return array{country: string, currency: string}
     */
    public function getStoreContext(): array
    {
        $store = $this->_storeManager->getStore();
        $storeId = (int) $store->getId();
        $currentCurrency = (string) $store->getCurrentCurrencyCode();

        return $this->priceContextResolver->resolveVisitorContext($storeId, $currentCurrency);
    }

    /**
     * Get window.UdunyStoreContext JSON string
     */
    public function getStoreContextJson(): string
    {
        return (string) $this->json->serialize($this->getStoreContext());
    }

    /**
     * Get window.UdunySearch JSON string
     */
    public function getSearchConfigJson(): string
    {
        $storeId = $this->getCurrentStoreId();

        $config = [
            'url' => $this->config->getSearchUrl($storeId),
            'lang' => $this->getStoreLanguageCode(),
            'trigger_elements' => [
                'clicked' => $this->config->getTriggerClickedSelectors($storeId),
                'typing' => $this->config->getTriggerTypingSelectors($storeId),
            ],
        ];

        return (string) $this->json->serialize($config);
    }

    /**
     * Build Method 2 direct embed / custom iframe URL with lang (and cid/currency when active).
     */
    public function getDirectEmbedUrl(): string
    {
        $baseUrl = $this->config->getSearchUrl($this->getCurrentStoreId());
        if ($baseUrl === '') {
            return '';
        }

        $params = ['lang' => $this->getStoreLanguageCode()];
        if ($this->shouldOutputStoreContext()) {
            $ctx = $this->getStoreContext();
            $params['cid'] = $ctx['country'];
            $params['currency'] = $ctx['currency'];
        }

        return $baseUrl . '/?' . http_build_query($params);
    }

    /**
     * Get session tracking configuration as JSON
     */
    public function getConfigJson(): string
    {
        $config = [
            'enabled' => $this->isEnabled(),
            'productionMode' => $this->isProductionMode(),
            'forceRefresh' => false,
            'trackUrl' => $this->getTrackSessionUrl(),
        ];

        return (string) $this->json->serialize($config);
    }

    /**
     * Get track session URL
     */
    public function getTrackSessionUrl(): string
    {
        return $this->getUrl('uduny/track/session');
    }

    /**
     * Extract 2-letter ISO language code from Magento store locale (e.g. it_IT -> it)
     */
    private function getStoreLanguageCode(): string
    {
        $locale = (string) $this->localeResolver->getLocale();
        $parts = explode('_', $locale);
        $lang = strtolower(trim($parts[0] ?? 'en'));

        return $lang !== '' ? $lang : 'en';
    }

    /**
     * Current store ID helper
     */
    private function getCurrentStoreId(): int
    {
        return (int) $this->_storeManager->getStore()->getId();
    }
}
