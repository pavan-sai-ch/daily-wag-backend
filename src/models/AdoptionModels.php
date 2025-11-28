<?php
/**
 * Adoption Model
 * Handles database operations for the `adoption` table and
 * related updates to the `pets` table.
 */
class AdoptionModels {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // ... (findAvailablePets and createRequest remain the same) ...

    public function findAvailablePets() {
        $sql = "SELECT * FROM pets WHERE adoption_status = 'available' ORDER BY pet_id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function createRequest($petId, $adopterId) {
        try {
            $this->db->beginTransaction();
            $sql = "INSERT INTO adoption (pet_id, adopter_id, status) VALUES (:petId, :adopterId, 'pending')";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':petId', $petId);
            $stmt->bindParam(':adopterId', $adopterId);
            $stmt->execute();

            $updateSql = "UPDATE pets SET adoption_status = 'pending' WHERE pet_id = :petId";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->bindParam(':petId', $petId);
            $updateStmt->execute();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * (Admin) Finds all pending adoption requests.
     * UPDATED: Now explicitly selects u.email
     */
    public function findAllPendingRequests() {
        // Added 'u.email' to the SELECT list
        $sql = "SELECT a.*, p.pet_name, p.pet_breed, u.first_name, u.last_name, u.email 
                FROM adoption a
                JOIN pets p ON a.pet_id = p.pet_id
                JOIN users u ON a.adopter_id = u.user_id
                WHERE a.status = 'pending'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ... (updateStatus remains the same) ...

    public function updateStatus($adoptId, $status) {
        try {
            $this->db->beginTransaction();

            $getDetailsSql = "SELECT pet_id, adopter_id FROM adoption WHERE adopt_id = :adoptId";
            $getStmt = $this->db->prepare($getDetailsSql);
            $getStmt->bindParam(':adoptId', $adoptId);
            $getStmt->execute();
            $request = $getStmt->fetch();

            if (!$request) throw new Exception("Adoption request not found");
            $petId = $request['pet_id'];
            $adopterId = $request['adopter_id'];

            $sql = "UPDATE adoption SET status = :status WHERE adopt_id = :adoptId";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':adoptId', $adoptId);
            $stmt->execute();

            if ($status === 'approved') {
                $updateSql = "UPDATE pets SET adoption_status = 'adopted', user_id = :newOwnerId WHERE pet_id = :petId";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->bindParam(':newOwnerId', $adopterId);
                $updateStmt->bindParam(':petId', $petId);
                $updateStmt->execute();
            } else {
                $updateSql = "UPDATE pets SET adoption_status = 'available' WHERE pet_id = :petId";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->bindParam(':petId', $petId);
                $updateStmt->execute();
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}