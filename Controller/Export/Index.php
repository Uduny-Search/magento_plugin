<?php
/**
 * Export Controller
 *
 * Frontend controller that handles product export requests.
 *
 * URL: /uduny/export/index/slug/{slug}
 *      or: /uduny/export/{slug} (with URL rewrite)
 *
 * Required parameters:
 *   - slug: The export list slug
 *   - token: Master token for authentication (query param or header)
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Controller\Export;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Exception\NoSuchEntityException;
use UnusualDope\Uduny\Api\ExportListRepositoryInterface;
use UnusualDope\Uduny\Model\Config;
use UnusualDope\Uduny\Model\Logger;
use UnusualDope\Uduny\Model\ProductExporter;

class Index implements HttpGetActionInterface
{
    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var JsonFactory
     */
    private JsonFactory $jsonFactory;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var ExportListRepositoryInterface
     */
    private ExportListRepositoryInterface $exportListRepository;

    /**
     * @var ProductExporter
     */
    private ProductExporter $productExporter;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * Constructor
     *
     * @param RequestInterface $request
     * @param JsonFactory $jsonFactory
     * @param Config $config
     * @param ExportListRepositoryInterface $exportListRepository
     * @param ProductExporter $productExporter
     * @param Logger $logger
     */
    public function __construct(
        RequestInterface $request,
        JsonFactory $jsonFactory,
        Config $config,
        ExportListRepositoryInterface $exportListRepository,
        ProductExporter $productExporter,
        Logger $logger
    ) {
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->config = $config;
        $this->exportListRepository = $exportListRepository;
        $this->productExporter = $productExporter;
        $this->logger = $logger;
    }

    /**
     * Execute action
     *
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();

        try {
            // Check if module is enabled
            if (!$this->config->isEnabled()) {
                return $this->errorResponse($result, 'Module is disabled', 503);
            }

            // Validate authentication
            $authError = $this->validateAuthentication();
            if ($authError) {
                $this->logger->info('Authentication failed: ' . $authError);
                return $this->errorResponse($result, $authError, 401);
            }

            // Get slug from request
            $slug = $this->request->getParam('slug');
            if (!$slug) {
                return $this->errorResponse($result, 'Missing slug parameter', 400);
            }

            $this->logger->debug('Export request for slug: ' . $slug);

            // Load export list by slug
            try {
                $exportList = $this->exportListRepository->getBySlug($slug);
            } catch (NoSuchEntityException $e) {
                return $this->errorResponse($result, 'Export list not found', 404);
            }

            // Check if export list is active
            if (!$exportList->getIsActive()) {
                return $this->errorResponse($result, 'Export list is disabled', 403);
            }

            // Export products
            $products = $this->productExporter->export($exportList);

            $this->logger->info('Export completed', [
                'slug' => $slug,
                'count' => count($products),
            ]);

            // Return Uduny JSON feed format
            return $result->setData([
                'items' => $products,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Export error: ' . $e->getMessage());
            return $this->errorResponse($result, 'Internal server error', 500);
        }
    }

    /**
     * Validate authentication (token and IP whitelist)
     *
     * @return string|null Error message or null if valid
     */
    private function validateAuthentication(): ?string
    {
        // Get master token from config
        $masterToken = $this->config->getMasterToken();

        // If no token configured, skip token validation
        if (!empty($masterToken)) {
            // Get token from request (query param or header)
            // Note: getHeader() returns false when header is not present, not null
            // So we use ?: instead of ?? to handle falsy values
            $requestToken = $this->request->getParam('token')
                ?: $this->request->getHeader('X-Uduny-Token')
                ?: $this->getAuthorizationHeader();

            // Remove "Bearer " prefix if present
            if ($requestToken && strpos($requestToken, 'Bearer ') === 0) {
                $requestToken = substr($requestToken, 7);
            }

            if ($requestToken !== $masterToken) {
                return 'Invalid token';
            }
        }

        // Check IP whitelist
        $clientIp = $this->getClientIp();
        if (!$this->config->isIpAllowed($clientIp)) {
            return 'IP not allowed: ' . $clientIp;
        }

        return null;
    }

    /**
     * Get Authorization header value
     *
     * Handles various server configurations where the Authorization header
     * might be available under different names or in server variables.
     *
     * @return string|null
     */
    private function getAuthorizationHeader(): ?string
    {
        // Try standard header first
        $authHeader = $this->request->getHeader('Authorization');
        if ($authHeader) {
            return $authHeader;
        }

        // Try HTTP_AUTHORIZATION from server variables (Apache/CGI workaround)
        $httpAuth = $this->request->getServer('HTTP_AUTHORIZATION');
        if ($httpAuth) {
            return $httpAuth;
        }

        // Try REDIRECT_HTTP_AUTHORIZATION (some Apache configs)
        $redirectAuth = $this->request->getServer('REDIRECT_HTTP_AUTHORIZATION');
        if ($redirectAuth) {
            return $redirectAuth;
        }

        return null;
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    private function getClientIp(): string
    {
        // Check common proxy headers
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Standard proxy
            'HTTP_X_REAL_IP',            // Nginx proxy
            'REMOTE_ADDR',               // Direct connection
        ];

        foreach ($headers as $header) {
            $ip = $this->request->getServer($header);
            if ($ip) {
                // X-Forwarded-For may contain multiple IPs
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                return $ip;
            }
        }

        return 'unknown';
    }

    /**
     * Create error response
     *
     * @param Json $result
     * @param string $message
     * @param int $code
     * @return Json
     */
    private function errorResponse(Json $result, string $message, int $code): Json
    {
        $result->setHttpResponseCode($code);
        return $result->setData([
            'success' => false,
            'error' => $message,
            'code' => $code,
        ]);
    }
}

