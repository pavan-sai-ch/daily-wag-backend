<?php
/**
 * Store Model
 * Handles all database operations for the `store` table.
 */
class StoreModels {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Finds all products in the store (Admin sees everything, even 0 stock).
     */
    public function findAll() {
        $sql = "SELECT * FROM store ORDER BY item_id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Finds a single product by its ID.
     */
    public function findById($itemId) {
        $sql = "SELECT * FROM store WHERE item_id = :itemId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * (Admin) Creates a new product.
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO store (item_name, description, price, stock, photo_url) 
                    VALUES (:name, :desc, :price, :stock, :photoUrl)";

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':name', $data['item_name']);
            $stmt->bindParam(':desc', $data['description']);
            $stmt->bindParam(':price', $data['price']);
            $stmt->bindParam(':stock', $data['stock']);

            // Handle null photo
            $photoUrl = isset($data['photo_url']) ? $data['photo_url'] : null;
            $stmt->bindParam(':photoUrl', $photoUrl);

            $stmt->execute();
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("StoreModel::create Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * (Admin) Updates a product.
     */
    public function update($itemId, $data) {
        try {
            // We build the query dynamically because photo_url might not change
            $sql = "UPDATE store SET 
                        item_name = :name,
                        description = :desc,
                        price = :price,
                        stock = :stock";

            if (isset($data['photo_url'])) {
                $sql .= ", photo_url = :photoUrl";
            }

            $sql .= " WHERE item_id = :itemId";

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':name', $data['item_name']);
            $stmt->bindParam(':desc', $data['description']);
            $stmt->bindParam(':price', $data['price']);
            $stmt->bindParam(':stock', $data['stock']);
            $stmt->bindParam(':itemId', $itemId);

            if (isset($data['photo_url'])) {
                $stmt->bindParam(':photoUrl', $data['photo_url']);
            }

            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            error_log("StoreModel::update Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * (Admin) Deletes a product.
     * Note: This might fail if the item is in an existing order (Foreign Key constraint).
     */
    public function delete($itemId) {
        try {
            $sql = "DELETE FROM store WHERE item_id = :itemId";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':itemId', $itemId);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("StoreModel::delete Error: " . $e->getMessage());
            return false;
        }
    }
}