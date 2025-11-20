<?php
/**
 * Cart Controller
 * Handles all API requests for the shopping cart,
 * which is stored in the user's session.
 */
class CartController extends BaseController {

    // Note: We don't need a Model for basic cart management,
    // as we are just manipulating the $_SESSION array.
    // We *will* need Models (Store, Order) for the checkout process.
    private $db;
    public function __construct($db) {
        // We still need the $db connection for when we add checkout logic
        $this->db = $db;
    }

    /**
     * Handles GET /api/cart
     * Gets the current user's cart from the session.
     */
    public function getCart() {
        $this->authenticate(); // Ensure user is logged in

        // Check if the cart exists in the session, default to empty array
        $cart = $_SESSION['cart'] ?? [];

        // We'll also add a helper to calculate totals
        $totalItems = 0;
        $subtotal = 0.0;

        // In a real app, you'd fetch product details here to ensure
        // prices are up-to-date, but for a simple session cart,
        // we can store price-on-add (which we'll do in the 'add' function).
        foreach ($cart as $item) {
            $totalItems += $item['quantity'];
            $subtotal += $item['price'] * $item['quantity'];
        }

        $this->sendResponse([
            'items' => array_values($cart), // Re-index array for clean JSON
            'totalItems' => $totalItems,
            'subtotal' => (float)number_format($subtotal, 2, '.', '')
        ], 200);
    }

    /**
     * Handles POST /api/cart
     * Adds an item to the cart.
     */
    public function addItemToCart() {
        $session = $this->authenticate();
        $data = $this->getRequestData();
        $data = Sanitize::all($data);

        // Validation
        if (empty($data['item_id']) || empty($data['quantity'])) {
            $this->sendError('item_id and quantity are required.', 400);
            return;
        }

        $itemId = (int)$data['item_id'];
        $quantity = (int)$data['quantity'];

        if ($quantity <= 0) {
            $this->sendError('Quantity must be at least 1.', 400);
            return;
        }

        // --- Check Stock & Get Price (CRITICAL) ---
        // We must get the price from the database, not from the user.
        // This prevents a user from POSTing `{"price": 0.01}`.
        require_once __DIR__ . '/../models/StoreModels.php';
        $storeModel = new StoreModels($this->db);
        $product = $storeModel->findById($itemId);

        if (!$product) {
            $this->sendError('Product not found.', 404);
            return;
        }

        if ($product['stock'] < $quantity) {
            $this->sendError('Not enough stock available.', 400);
            return;
        }

        // --- Add to Session Cart ---
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // If item already in cart, update quantity
        if (isset($_SESSION['cart'][$itemId])) {
            // Check if new total quantity exceeds stock
            $newQuantity = $_SESSION['cart'][$itemId]['quantity'] + $quantity;
            if ($product['stock'] < $newQuantity) {
                $this->sendError('Not enough stock to add more. You already have ' . $_SESSION['cart'][$itemId]['quantity'] . ' in your cart.', 400);
                return;
            }
            $_SESSION['cart'][$itemId]['quantity'] = $newQuantity;
        } else {
            // Add new item to cart
            $_SESSION['cart'][$itemId] = [
                'item_id' => $itemId,
                'name' => $product['item_name'],
                'price' => (float)$product['price'], // Get price from DB!
                'quantity' => $quantity,
                'photo_url' => $product['photo_url']
            ];
        }

        $this->sendResponse(['status' => 'success', 'message' => 'Item added to cart.'], 201);
    }

    /**
     * Handles PUT /api/cart/:id
     * Updates an item's quantity in the cart.
     */
    public function updateCartItem($itemId) {
        $session = $this->authenticate();
        $data = $this->getRequestData();
        $quantity = (int)Sanitize::string($data['quantity']);
        $itemId = (int)$itemId;

        if (!isset($_SESSION['cart']) || !isset($_SESSION['cart'][$itemId])) {
            $this->sendError('Item not found in cart.', 404);
            return;
        }

        // If quantity is 0, remove the item
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$itemId]);
            $this->sendResponse(['status' => 'success', 'message' => 'Item removed from cart.'], 200);
            return;
        }

        // Check stock
        require_once __DIR__ . '/../models/StoreModels.php';
        $storeModel = new StoreModels($this->db);
        $product = $storeModel->findById($itemId);

        if (!$product) {
            $this->sendError('Product not found.', 404);
            return;
        }
        if ($product['stock'] < $quantity) {
            $this->sendError('Not enough stock available. Only ' . $product['stock'] . ' left.', 400);
            return;
        }

        // Update quantity
        $_SESSION['cart'][$itemId]['quantity'] = $quantity;
        $this->sendResponse(['status' => 'success', 'message' => 'Cart quantity updated.'], 200);
    }

    /**
     * Handles DELETE /api/cart/:id
     * Removes an item from the cart.
     */
    public function removeCartItem($itemId) {
        $session = $this->authenticate();
        $itemId = (int)$itemId;

        if (!isset($_SESSION['cart']) || !isset($_SESSION['cart'][$itemId])) {
            $this->sendError('Item not found in cart.', 404);
            return;
        }

        // Remove the item
        unset($_SESSION['cart'][$itemId]);
        $this->sendResponse(['status' => 'success', 'message' => 'Item removed from cart.'], 200);
    }
}