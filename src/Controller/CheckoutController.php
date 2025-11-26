<?php
require_once __DIR__ . '/../Utils/Sanitize.php';
require_once __DIR__ . '/../models/OrderModels.php';
require_once __DIR__ . '/../models/UserModels.php';
require_once __DIR__ . '/../models/StoreModels.php'; // Needed for stock checks

/**
 * Checkout Controller
 * Handles the conversion of a session cart into a permanent order
 * AND handles Admin order management.
 */
class CheckoutController extends BaseController {

    private $orderModel;
    private $userModel;
    private $storeModel;

    public function __construct($db) {
        $this->orderModel = new OrderModels($db);
        $this->userModel = new UserModels($db);
        $this->storeModel = new StoreModels($db);
    }

    /**
     * Handles POST /api/checkout
     * Converts the user's session cart into a database order.
     */
    public function processCheckout() {
        // 1. Authenticate User
        $session = $this->authenticate();
        $userId = $session['user_id'];

        // 2. Validate Cart
        if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
            $this->sendError('Cart is empty.', 400);
            return;
        }

        $cartItems = $_SESSION['cart'];

        // 3. Get Request Data
        $data = $this->getRequestData();
        $data = Sanitize::all($data);

        $paymentMethod = $data['payment_method'] ?? 'card';
        $deliveryType = $data['delivery_type'] ?? 'pickup';

        if (!in_array($paymentMethod, ['cash', 'card'])) {
            $this->sendError('Invalid payment method.', 400);
            return;
        }
        if (!in_array($deliveryType, ['pickup', 'delivery'])) {
            $this->sendError('Invalid delivery type.', 400);
            return;
        }

        // --- ADDRESS VALIDATION ---
        // If the user chose 'delivery', they MUST have an address on file.
        if ($deliveryType === 'delivery') {
            $user = $this->userModel->findById($userId);

            if (!$user) {
                $this->sendError('User not found.', 404);
                return;
            }

            // Check if address column is empty or null
            if (empty($user['address'])) {
                // Send a specific error so the frontend knows to redirect to profile
                $this->sendError('Delivery address is required. Please update your profile.', 400);
                return;
            }
        }

        // --- STOCK VALIDATION ---
        // Before we charge the card, let's double-check that everything is still in stock
        $grandTotal = 0.0;
        foreach ($cartItems as $item) {
            $product = $this->storeModel->findById($item['item_id']);
            if (!$product || $product['stock'] < $item['quantity']) {
                $this->sendError("Sorry, '" . $item['name'] . "' is out of stock or unavailable.", 400);
                return;
            }
            $grandTotal += $product['price'] * $item['quantity'];
        }

        // 5. Create Order Transaction
        // This function handles the DB transaction (insert order, insert items, decrease stock)
        $orderId = $this->orderModel->createOrder($userId, $cartItems, $grandTotal, $paymentMethod, $deliveryType);

        if ($orderId) {
            // 6. Clear the Cart
            unset($_SESSION['cart']);

            $this->sendResponse([
                'status' => 'success',
                'message' => 'Order placed successfully!',
                'order_id' => $orderId
            ], 201);
        } else {
            $this->sendError('Failed to place order. Please try again.', 500);
        }
    }

    /**
     * (User) GET /api/orders/user
     * Gets all orders for the logged-in user.
     */
    public function getUserOrders() {
        try {
            // 1. Authenticate
            $session = $this->authenticate();
            $userId = $session['user_id'];

            // 2. Fetch orders
            $orders = $this->orderModel->getOrdersByUser($userId);

            // 3. Send response
            $this->sendResponse($orders, 200);

        } catch (Exception $e) {
            $this->sendError("Error: " . $e->getMessage(), 500);
        }
    }

    /**
     * (Admin) GET /api/orders/all
     * Gets all orders in the system.
     */
    public function getAllOrders() {
        try {
            $session = $this->authenticate();
            if ($session['user_role'] !== 'admin') {
                $this->sendError('Forbidden', 403);
                return;
            }

            $orders = $this->orderModel->findAll();
            $this->sendResponse($orders, 200);
        } catch (Exception $e) {
            $this->sendError("Error: " . $e->getMessage(), 500);
        }
    }

    /**
     * (Admin) GET /api/orders/:id/items
     * Gets the specific items within an order.
     */
    public function getOrderItems($orderId) {
        try {
            $session = $this->authenticate();
            if ($session['user_role'] !== 'admin') {
                $this->sendError('Forbidden', 403);
                return;
            }

            $items = $this->orderModel->findOrderItems($orderId);
            $this->sendResponse($items, 200);
        } catch (Exception $e) {
            $this->sendError("Error: " . $e->getMessage(), 500);
        }
    }

    /**
     * (Admin) PUT /api/orders/:id/status
     * Updates the status of an order.
     */
    public function updateOrderStatus($orderId) {
        try {
            $session = $this->authenticate();
            if ($session['user_role'] !== 'admin') {
                $this->sendError('Forbidden', 403);
                return;
            }

            $data = $this->getRequestData();
            $status = $data['status'];
            // Validate status against ENUM list from DB schema
            $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

            if (!in_array($status, $validStatuses)) {
                $this->sendError('Invalid status.', 400);
                return;
            }

            $success = $this->orderModel->updateStatus($orderId, $status);
            if ($success) {
                $this->sendResponse(['status' => 'success', 'message' => 'Order status updated.'], 200);
            } else {
                $this->sendError('Failed to update order.', 500);
            }
        } catch (Exception $e) {
            $this->sendError("Error: " . $e->getMessage(), 500);
        }
    }
}