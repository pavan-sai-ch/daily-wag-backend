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
     * Finds products with pagination AND search support.
     * @param int $limit
     * @param int $offset
     * @param string $search (Optional) Search term
     * @return array List of products.
     */
    public function findAll($limit = null, $offset = 0, $search = '') {
        $sql = "SELECT * FROM store WHERE stock > 0";

        // Add search condition if provided
        if (!empty($search)) {
            $sql .= " AND item_name LIKE :search";
        }

        $sql .= " ORDER BY item_id DESC";

        if ($limit !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }

        $stmt = $this->db->prepare($sql);

        if (!empty($search)) {
            $searchTerm = "%" . $search . "%";
            $stmt->bindParam(':search', $searchTerm);
        }

        if ($limit !== null) {
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findById($itemId) {
        $sql = "SELECT * FROM store WHERE item_id = :itemId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function create($data) {
        try {
            $sql = "INSERT INTO store (item_name, description, price, stock, photo_url) 
                    VALUES (:name, :desc, :price, :stock, :photoUrl)";

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':name', $data['item_name']);
            $stmt->bindParam(':desc', $data['description']);
            $stmt->bindParam(':price', $data['price']);
            $stmt->bindParam(':stock', $data['stock']);

            $photoUrl = isset($data['photo_url']) ? $data['photo_url'] : null;
            $stmt->bindParam(':photoUrl', $photoUrl);

            $stmt->execute();
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("StoreModel::create Error: " . $e->getMessage());
            return false;
        }
    }

    public function update($itemId, $data) {
        try {
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