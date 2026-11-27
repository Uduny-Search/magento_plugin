<?php
/**
 * Add To Cart Observer
 *
 * This observer listens to the checkout_cart_add_product_complete event
 * and sends tracking data to Uduny.
 *
 * Event data available:
 *   - product: The product being added
 *   - request: The request object with qty and options
 *
 * Data sent to Uduny:
 *   - event: 'add_to_cart'
 *   - product_id
 *   - product_name
 *   - quantity
 *   - price
 *   - user_session: uduny_token
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use UnusualDope\Uduny\Api\SessionTrackingRepositoryInterface;
use UnusualDope\Uduny\Model\Logger;
use UnusualDope\Uduny\Model\Config;

class AddToCartObserver implements ObserverInterface
{
    /**
     * @var SessionManagerInterface
     */
    private SessionManagerInterface $sessionManager;

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
        SessionManagerInterface $sessionManager,
        SessionTrackingRepositoryInterface $sessionTrackingRepository,
        Logger $logger,
        Config $config
    ) {
        $this->sessionManager = $sessionManager;
        $this->sessionTrackingRepository = $sessionTrackingRepository;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        // Check if module is enabled
        if (!$this->config->isEnabled()) {
            return;
        }

        try {
            // Get product from event
            $product = $observer->getEvent()->getProduct();
            if (!$product) {
                return;
            }

            // Get quantity from request
            $request = $observer->getEvent()->getRequest();
            $qty = (float) ($request->getParam('qty') ?? 1);

            // Get Uduny token from session tracking
            $udunyToken = $this->getUdunyToken();

            // Build event data
            $eventData = [
                'event' => 'add_to_cart',
                'product_id' => (int) $product->getId(),
                'product_name' => $product->getName(),
                'quantity' => $qty,
                'price' => (float) $product->getFinalPrice(),
                'user_session' => $udunyToken,
            ];

            // Log the event (for now, later send to Uduny API)
            $this->logger->info('Add to cart event', $eventData);

            // TODO: Send to Uduny API
            // $this->sendToUduny($eventData);

        } catch (\Exception $e) {
            $this->logger->error('Add to cart tracking error: ' . $e->getMessage());
        }
    }

    /**
     * Get Uduny token from session tracking
     *
     * @return string|null
     */
    private function getUdunyToken(): ?string
    {
        try {
            $sessionId = $this->sessionManager->getSessionId();
            if (empty($sessionId)) {
                return null;
            }

            $sessionTracking = $this->sessionTrackingRepository->getBySessionId($sessionId);
            return $sessionTracking->getUdunyToken();

        } catch (NoSuchEntityException $e) {
            // Session not tracked yet
            return null;
        }
    }

    /**
     * Send event data to Uduny API (placeholder)
     *
     * @param array $eventData
     * @return void
     */
    private function sendToUduny(array $eventData): void
    {
        // TODO: Implement HTTP client to send to Uduny
        // $this->httpClient->post('https://api.uduny.com/track', $eventData);
    }
}

