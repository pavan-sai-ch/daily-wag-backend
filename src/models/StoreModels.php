<?php
/**
 * Store Model
 * Handles all database operations for the `store` table.
 */
class StoreModels {

    private $db;

    /**
     * Constructor to inject the database connection.
     * @param PDO $db The PDO database connection.
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Finds all products in the store.
     *
     * @return array A list of all products.
     */
    public function findAll() {
        // We also check for stock > 0, so out-of-stock items don't show
        $sql = "SELECT item_id, item_name, description, price, stock, photo_url 
                FROM store 
                WHERE stock > 0 
                ORDER BY item_name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Finds a single product by its ID.
     *
     * @param int $itemId The ID of the item.
     * @return mixed The product record as an array, or false if not found.
     */
    public function findById($itemId) {
        $sql = "SELECT item_id, item_name, description, price, stock, photo_url 
                FROM store 
                WHERE item_id = :itemId";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // You would add Admin-only functions here later:
    // public function createProduct($data) { ... }
    // public function updateProduct($itemId, $data) { ... }
    // public function deleteProduct($itemId) { ... }
    // public function updateStock($itemId, $newStock) { ... }
}