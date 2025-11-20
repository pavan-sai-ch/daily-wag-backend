<?php
/**
 * Order Model
 * Handles database operations for orders and order items.
 */
class OrderModels {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Creates a complete order transaction.
     * * @param int $userId The ID of the user placing the order.
     * @param array $cartItems The array of items from the session cart.
     * @param float $grandTotal The total cost of the order.
     * @param string $paymentMethod 'cash' or 'card'
     * @param string $deliveryType 'pickup' or 'delivery'
     * @return int|false The new Order ID on success, false on failure.
     */
    public function createOrder($userId, $cartItems, $grandTotal, $paymentMethod, $deliveryType) {
        try {
            // 1. Start Transaction
            $this->db->beginTransaction();

            // 2. Insert into 'orders' table
            $sql = "INSERT INTO orders (user_id, grand_total, payment_method, delivery_type, status) 
                    VALUES (:userId, :grandTotal, :paymentMethod, :deliveryType, 'pending')";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':userId', $userId);
            $stmt->bindParam(':grandTotal', $grandTotal);
            $stmt->bindParam(':paymentMethod', $paymentMethod);
            $stmt->bindParam(':deliveryType', $deliveryType);
            $stmt->execute();

            $orderId = $this->db->lastInsertId();

            // 3. Loop through items and insert into 'order_items' table
            $itemSql = "INSERT INTO order_items (order_id, item_id, quantity, price_at_purchase) 
                        VALUES (:orderId, :itemId, :quantity, :price)";
            $itemStmt = $this->db->prepare($itemSql);

            // 4. Prepare statement to update stock
            $stockSql = "UPDATE store SET stock = stock - :quantity WHERE item_id = :itemId";
            $stockStmt = $this->db->prepare($stockSql);

            foreach ($cartItems as $item) {
                // Insert Order Item
                $itemStmt->bindParam(':orderId', $orderId);
                $itemStmt->bindParam(':itemId', $item['item_id']);
                $itemStmt->bindParam(':quantity', $item['quantity']);
                $itemStmt->bindParam(':price', $item['price']);
                $itemStmt->execute();

                // Update Stock
                $stockStmt->bindParam(':quantity', $item['quantity']);
                $stockStmt->bindParam(':itemId', $item['item_id']);
                $stockStmt->execute();
            }

            // 5. Commit Transaction
            $this->db->commit();

            return $orderId;

        } catch (Exception $e) {
            // If anything goes wrong, rollback changes
            $this->db->rollBack();
            // In production, log the error: $e->getMessage()
            return false;
        }
    }

    /**
     * Get all orders for a specific user.
     */
    public function getOrdersByUser($userId) {
        $sql = "SELECT * FROM orders WHERE user_id = :userId ORDER BY order_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}