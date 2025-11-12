<?php
/**
 * Pet Model
 * Handles all database operations for the `pets` table.
 */
class PetModels {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Gets all pets for a specific user.
     * @param int $userId The ID of the logged-in user.
     * @return array An array of the user's pets.
     */
    public function findByUserId($userId) {
        $sql = "SELECT * FROM pets WHERE user_id = :userId ORDER BY pet_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Finds a single pet by its ID and owner ID.
     * This ensures a user can only access their own pet.
     */
    public function findOne($petId, $userId) {
        $sql = "SELECT * FROM pets WHERE pet_id = :petId AND user_id = :userId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':petId', $petId, PDO::PARAM_INT);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Creates a new pet for a specific user.
     * @param array $data Associative array of pet data.
     * @return int The ID of the newly created pet.
     */
    public function create($data) {
        $sql = "INSERT INTO pets (user_id, pet_category, pet_breed, pet_age, medical_condition) 
                VALUES (:userId, :category, :breed, :age, :medical)";

        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':userId', $data['user_id']);
        $stmt->bindParam(':category', $data['pet_category']);
        $stmt->bindParam(':breed', $data['pet_breed']);
        $stmt->bindParam(':age', $data['pet_age']);
        $stmt->bindParam(':medical', $data['medical_condition']);

        $stmt->execute();

        // Return the ID of the new pet
        return $this->db->lastInsertId();
    }

    /**
     * Updates an existing pet.
     * @param int $petId The ID of the pet to update.
     * @param int $userId The ID of the owner (for security).
     * @param array $data The new data.
     * @return bool True on success, false on failure.
     */
    public function update($petId, $userId, $data) {
        $sql = "UPDATE pets SET 
                    pet_category = :category, 
                    pet_breed = :breed, 
                    pet_age = :age, 
                    medical_condition = :medical
                WHERE pet_id = :petId AND user_id = :userId";

        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':category', $data['pet_category']);
        $stmt->bindParam(':breed', $data['pet_breed']);
        $stmt->bindParam(':age', $data['pet_age']);
        $stmt->bindParam(':medical', $data['medical_condition']);
        $stmt->bindParam(':petId', $petId);
        $stmt->bindParam(':userId', $userId);

        $stmt->execute();

        // rowCount() returns the number of rows affected.
        // If it's > 0, the update was successful.
        return $stmt->rowCount() > 0;
    }

    /**
     * Deletes a pet.
     * @param int $petId The ID of the pet to delete.
     * @param int $userId The ID of the owner (for security).
     * @return bool True on success, false on failure.
     */
    public function delete($petId, $userId) {
        $sql = "DELETE FROM pets WHERE pet_id = :petId AND user_id = :userId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':petId', $petId);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}