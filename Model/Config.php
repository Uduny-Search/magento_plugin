<?php
/**
 * Configuration Helper
 *
 * This class provides easy access to all Uduny configuration values.
 * Instead of remembering config paths everywhere, we use this helper.
 *
 * Usage in other classes:
 *   $this->config->isEnabled();
 *   $this->config->getMasterToken();
 *   etc.
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    /**
     * Config path constants
     * Format: section/group/field (matches system.xml structure)
     */
    private const XML_PATH_ENABLED = 'uduny/general/enabled';
    private const XML_PATH_MASTER_TOKEN = 'uduny/general/master_token';
    private const XML_PATH_SERVER_WHITELIST = 'uduny/general/server_whitelist';
    private const XML_PATH_PRODUCTION_MODE = 'uduny/general/production_mode';
    private const XML_PATH_DEBUG_MODE = 'uduny/general/debug_mode';
    private const XML_PATH_SCRIPT_URL = 'uduny/frontend/script_url';
    private const XML_PATH_SEARCH_URL = 'uduny/frontend/search_url';
    private const XML_PATH_TRIGGER_CLICKED = 'uduny/frontend/trigger_clicked';
    private const XML_PATH_TRIGGER_TYPING = 'uduny/frontend/trigger_typing';
    private const XML_PATH_IMAGE_WIDTH = 'uduny/frontend/image_width';
    private const XML_PATH_IMAGE_HEIGHT = 'uduny/frontend/image_height';
    private const XML_PATH_PRICELIST_MODE = 'uduny/export/pricelist_mode';
    private const XML_PATH_CURRENCY_LOCATION_MAP = 'uduny/export/currency_location_map';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * Constructor
     *
     * @param ScopeConfigInterface $scopeConfig Magento's config reader
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Check if module is enabled
     *
     * @param int|null $storeId Store ID (null = current store)
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get master token for authentication
     *
     * @return string
     */
    public function getMasterToken(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_MASTER_TOKEN,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get server whitelist as array
     *
     * @return array List of allowed IP addresses
     */
    public function getServerWhitelist(): array
    {
        $whitelist = (string) $this->scopeConfig->getValue(
            self::XML_PATH_SERVER_WHITELIST,
            ScopeInterface::SCOPE_STORE
        );

        if (empty($whitelist)) {
            return [];
        }

        // Split by newlines and clean up
        $ips = preg_split('/\r\n|\r|\n/', $whitelist);
        return array_filter(array_map('trim', $ips));
    }

    /**
     * Check if production mode is enabled
     *
     * @param int|null $storeId Store ID (null = current store)
     * @return bool
     */
    public function isProductionMode(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PRODUCTION_MODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if debug mode is enabled
     *
     * @return bool
     */
    public function isDebugMode(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_DEBUG_MODE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get script URL for frontend
     *
     * @param int|null $storeId Store ID (null = current store)
     * @return string
     */
    public function getScriptUrl(?int $storeId = null): string
    {
        return trim((string) $this->scopeConfig->getValue(
            self::XML_PATH_SCRIPT_URL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }

    /**
     * Get UdSearch base URL (auto-derived from script_url origin when empty)
     *
     * @param int|null $storeId
     * @return string
     */
    public function getSearchUrl(?int $storeId = null): string
    {
        $searchUrl = trim((string) $this->scopeConfig->getValue(
            self::XML_PATH_SEARCH_URL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        if ($searchUrl !== '') {
            return rtrim($searchUrl, '/');
        }

        $scriptUrl = $this->getScriptUrl($storeId);
        if ($scriptUrl === '') {
            return '';
        }

        $parts = parse_url($scriptUrl);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        $origin = $parts['scheme'] . '://' . $parts['host'];
        if (!empty($parts['port'])) {
            $origin .= ':' . $parts['port'];
        }

        return $origin;
    }

    /**
     * Get click trigger CSS selectors
     *
     * @param int|null $storeId
     * @return string[]
     */
    public function getTriggerClickedSelectors(?int $storeId = null): array
    {
        return $this->parseCsvSelectors(
            (string) $this->scopeConfig->getValue(
                self::XML_PATH_TRIGGER_CLICKED,
                ScopeInterface::SCOPE_STORE,
                $storeId
            )
        );
    }

    /**
     * Get typing trigger CSS selectors
     *
     * @param int|null $storeId
     * @return string[]
     */
    public function getTriggerTypingSelectors(?int $storeId = null): array
    {
        $selectors = $this->parseCsvSelectors(
            (string) $this->scopeConfig->getValue(
                self::XML_PATH_TRIGGER_TYPING,
                ScopeInterface::SCOPE_STORE,
                $storeId
            )
        );

        return !empty($selectors) ? $selectors : ['#search'];
    }

    /**
     * Split comma-separated CSS selectors cleanly
     *
     * @param string $raw
     * @return string[]
     */
    private function parseCsvSelectors(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /**
     * Get image width for exports
     *
     * @param int|null $storeId Store ID (null = current store)
     * @return int
     */
    public function getImageWidth(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_IMAGE_WIDTH,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 300; // Default fallback
    }

    /**
     * Get image height for exports
     *
     * @param int|null $storeId Store ID (null = current store)
     * @return int
     */
    public function getImageHeight(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_IMAGE_HEIGHT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 300; // Default fallback
    }

    /**
     * Check if IP is whitelisted
     *
     * @param string $ip IP address to check
     * @return bool True if allowed (whitelist empty OR IP in whitelist)
     */
    public function isIpAllowed(string $ip): bool
    {
        $whitelist = $this->getServerWhitelist();

        // Empty whitelist = allow all
        if (empty($whitelist)) {
            return true;
        }

        return in_array($ip, $whitelist, true);
    }

    /**
     * Get pricelist export mode ('auto', 'always', or 'never')
     *
     * @param int|null $storeId
     * @return string
     */
    public function getPricelistMode(?int $storeId = null): string
    {
        $mode = (string) $this->scopeConfig->getValue(
            self::XML_PATH_PRICELIST_MODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return in_array($mode, ['auto', 'always', 'never'], true) ? $mode : 'auto';
    }

    /**
     * Get custom currency => ISO country code mapping from admin configuration
     *
     * @return array<string, string>
     */
    public function getCurrencyLocationMap(): array
    {
        $raw = (string) $this->scopeConfig->getValue(
            self::XML_PATH_CURRENCY_LOCATION_MAP,
            ScopeInterface::SCOPE_STORE
        );

        if (trim($raw) === '') {
            return [];
        }

        $map = [];
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '=') === false) {
                continue;
            }
            [$currency, $country] = array_map('trim', explode('=', $line, 2));
            if ($currency !== '' && $country !== '') {
                $map[strtoupper($currency)] = strtoupper($country);
            }
        }

        return $map;
    }
}

