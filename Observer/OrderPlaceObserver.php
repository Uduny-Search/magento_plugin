<?php
/**
 * Order Place Observer
 *
 * This observer listens to the checkout_onepage_controller_success_action event
 * and sends purchase tracking data to Uduny.
 *
 * Event fires: After successful order placement on success page
 *
 * Data sent to Uduny:
 *   - event: 'purchase'
 *   - order_id
 *   - total_amount
 *   - total_quantity
 *   - data: array of products
 *   - user_session: uduny_token
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Api\OrderRepositoryInterface;
use UnusualDope\Uduny\Api\SessionTrackingRepositoryInterface;
use UnusualDope\Uduny\Model\Logger;
use UnusualDope\Uduny\Model\Config;

class OrderPlaceObserver implements ObserverInterface
{
    /**
     * @var SessionManagerInterface
     */
    private SessionManagerInterface $sessionManager;

    /**
     * @var CheckoutSession
     */
    private CheckoutSession $checkoutSession;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

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
        CheckoutSession $checkoutSession,
        OrderRepositoryInterface $orderRepository,
        SessionTrackingRepositoryInterface $sessionTrackingRepository,
        Logger $logger,
        Config $config
    ) {
        $this->sessionManager = $sessionManager;
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
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
            // Get order ID from checkout session
            $orderId = $this->checkoutSession->getLastOrderId();
            if (!$orderId) {
                return;
            }

            // Load the order
            $order = $this->orderRepository->get($orderId);

            // Get Uduny token from session tracking
            $udunyToken = $this->getUdunyToken();

            // Build products array
            $products = [];
            $totalQty = 0;

            foreach ($order->getAllVisibleItems() as $item) {
                $products[] = [
                    'product_id' => (int) $item->getProductId(),
                    'product_name' => $item->getName(),
                    'quantity' => (float) $item->getQtyOrdered(),
                    'price' => (float) $item->getPrice(),
                ];
                $totalQty += (float) $item->getQtyOrdered();
            }

            // Build event data
            $eventData = [
                'event' => 'purchase',
                'order_id' => $order->getIncrementId(),
                'total_amount' => (float) $order->getGrandTotal(),
                'total_quantity' => $totalQty,
                'data' => $products,
                'user_session' => $udunyToken,
            ];

            // Log the event (for now, later send to Uduny API)
            $this->logger->info('Purchase event', $eventData);

            // TODO: Send to Uduny API
            // $this->sendToUduny($eventData);

        } catch (\Exception $e) {
            $this->logger->error('Order tracking error: ' . $e->getMessage());
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

