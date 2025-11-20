<?php
require_once __DIR__ . '/../Utils/Sanitize.php';
require_once __DIR__ . '/../models/OrderModels.php';
// We need the UserModel now to check the address
require_once __DIR__ . '/../models/UserModels.php';

/**
 * Checkout Controller
 * Handles the conversion of a session cart into a permanent order.
 */
class CheckoutController extends BaseController {

    private $orderModel;
    private $userModel; // Add user model property

    public function __construct($db) {
        $this->orderModel = new OrderModels($db);
        $this->userModel = new UserModels($db); // Initialize user model
    }

    /**
     * Handles POST /api/checkout
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

        // --- ADDRESS VALIDATION (NEW) ---
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

        // 4. Calculate Grand Total
        // Never trust the total sent from the frontend!
        $grandTotal = 0.0;
        foreach ($cartItems as $item) {
            $grandTotal += $item['price'] * $item['quantity'];
        }

        // 5. Create Order Transaction
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
}