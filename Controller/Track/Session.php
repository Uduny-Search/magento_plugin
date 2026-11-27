<?php
/**
 * Session Tracking Controller
 *
 * This controller receives AJAX requests from frontend JavaScript
 * to register/update session tracking data.
 *
 * Endpoint: POST /uduny/track/session
 *
 * Flow:
 *   1. JavaScript calls this endpoint on page load
 *   2. We get or create a session tracking record
 *   3. Return the uduny_token to JavaScript
 *   4. JavaScript stores token for future event tracking
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Controller\Track;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Store\Model\StoreManagerInterface;
use UnusualDope\Uduny\Api\SessionTrackingRepositoryInterface;
use UnusualDope\Uduny\Model\Logger;
use UnusualDope\Uduny\Model\Config;

/**
 * Implements CsrfAwareActionInterface to bypass CSRF validation for AJAX requests
 */
class Session implements HttpPostActionInterface, CsrfAwareActionInterface
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
     * @var SessionManagerInterface
     */
    private SessionManagerInterface $sessionManager;

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var SessionTrackingRepositoryInterface
     */
    private SessionTrackingRepositoryInterface $sessionTrackingRepository;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * Constructor
     */
    public function __construct(
        RequestInterface $request,
        JsonFactory $jsonFactory,
        SessionManagerInterface $sessionManager,
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager,
        SessionTrackingRepositoryInterface $sessionTrackingRepository,
        Logger $logger,
        Config $config
    ) {
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->sessionManager = $sessionManager;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->sessionTrackingRepository = $sessionTrackingRepository;
        $this->logger = $logger;
        $this->config = $config;
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
                return $result->setData([
                    'success' => false,
                    'error' => 'Module is disabled'
                ]);
            }

            // Get Magento session ID
            $sessionId = $this->sessionManager->getSessionId();
            if (empty($sessionId)) {
                return $result->setData([
                    'success' => false,
                    'error' => 'No session ID'
                ]);
            }

            // Get customer ID (null if guest)
            $customerId = null;
            if ($this->customerSession->isLoggedIn()) {
                $customerId = (int) $this->customerSession->getCustomerId();
            }

            // Get store ID
            $storeId = (int) $this->storeManager->getStore()->getId();

            // Get or create session tracking record
            $sessionTracking = $this->sessionTrackingRepository->getOrCreate(
                $sessionId,
                $storeId,
                $customerId
            );

            $this->logger->debug('Session tracked', [
                'session_id' => $sessionId,
                'customer_id' => $customerId,
                'uduny_token' => $sessionTracking->getUdunyToken(),
            ]);

            // Return token to JavaScript
            return $result->setData([
                'success' => true,
                'token' => $sessionTracking->getUdunyToken(),
                'is_logged_in' => $this->customerSession->isLoggedIn(),
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Session tracking error: ' . $e->getMessage());
            return $result->setData([
                'success' => false,
                'error' => 'Internal error'
            ]);
        }
    }

    /**
     * Create CSRF validation exception
     *
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null; // No exception - allow the request
    }

    /**
     * Validate for CSRF
     *
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true; // Skip CSRF validation for this AJAX endpoint
    }
}

