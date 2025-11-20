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

    /**
     * Finds all pets that are currently available for adoption.
     * @return array List of available pets.
     */
    public function findAvailablePets() {
        // Join with the pets table to get details
        // We only want pets where adoption_status is 'available'
        $sql = "SELECT * FROM pets WHERE adoption_status = 'available' ORDER BY pet_id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Creates a new adoption request.
     *
     * @param int $petId The ID of the pet being adopted.
     * @param int $adopterId The ID of the user requesting adoption.
     * @return bool True on success.
     */
    public function createRequest($petId, $adopterId) {
        try {
            $this->db->beginTransaction();

            // 1. Create the adoption record
            $sql = "INSERT INTO adoption (pet_id, adopter_id, status) VALUES (:petId, :adopterId, 'pending')";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':petId', $petId);
            $stmt->bindParam(':adopterId', $adopterId);
            $stmt->execute();

            // 2. Update the pet's status to 'pending' so no one else can request it
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
     */
    public function findAllPendingRequests() {
        $sql = "SELECT a.*, p.pet_name, p.pet_breed, u.first_name, u.last_name 
                FROM adoption a
                JOIN pets p ON a.pet_id = p.pet_id
                JOIN users u ON a.adopter_id = u.user_id
                WHERE a.status = 'pending'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * (Admin) Updates the status of an adoption request.
     * If approved, it also marks the pet as 'adopted'.
     * If denied, it marks the pet as 'available' again.
     */
    public function updateStatus($adoptId, $status) {
        try {
            $this->db->beginTransaction();

            // 1. Get the pet_id associated with this request
            $getPetSql = "SELECT pet_id FROM adoption WHERE adopt_id = :adoptId";
            $getStmt = $this->db->prepare($getPetSql);
            $getStmt->bindParam(':adoptId', $adoptId);
            $getStmt->execute();
            $pet = $getStmt->fetch();

            if (!$pet) throw new Exception("Adoption request not found");
            $petId = $pet['pet_id'];

            // 2. Update the adoption request status
            $sql = "UPDATE adoption SET status = :status WHERE adopt_id = :adoptId";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':adoptId', $adoptId);
            $stmt->execute();

            // 3. Update the pet's status based on the decision
            $newPetStatus = ($status === 'approved') ? 'adopted' : 'available';

            $updateSql = "UPDATE pets SET adoption_status = :newStatus WHERE pet_id = :petId";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->bindParam(':newStatus', $newPetStatus);
            $updateStmt->bindParam(':petId', $petId);
            $updateStmt->execute();

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}