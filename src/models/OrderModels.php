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

    /**
     * (Admin) Finds ALL orders with user details.
     * @return array List of all orders.
     */
    public function findAll() {
        $sql = "SELECT o.*, u.first_name, u.last_name, u.email 
                FROM orders o
                JOIN users u ON o.user_id = u.user_id
                ORDER BY o.order_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * (Admin) Finds details (items) for a specific order.
     * @param int $orderId
     * @return array List of items in the order.
     */
    public function findOrderItems($orderId) {
        $sql = "SELECT oi.*, s.item_name, s.photo_url 
                FROM order_items oi
                JOIN store s ON oi.item_id = s.item_id
                WHERE oi.order_id = :orderId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':orderId', $orderId);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * (Admin) Updates order status.
     */
    public function updateStatus($orderId, $status) {
        $sql = "UPDATE orders SET status = :status WHERE order_id = :orderId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':orderId', $orderId);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}