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
     * (Customer) Creates a new pet for a specific user.
     * Default adoption status is 'not_available'.
     * UPDATED: Now saves photo_url.
     * * @param array $data Associative array of pet data.
     * @return int|false The ID of the newly created pet or false on error.
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO pets (user_id, pet_name, pet_category, pet_breed, pet_age, medical_condition, photo_url) 
                    VALUES (:userId, :name, :category, :breed, :age, :medical, :photoUrl)";

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':userId', $data['user_id']);
            $stmt->bindParam(':name', $data['pet_name']);
            $stmt->bindParam(':category', $data['pet_category']);
            $stmt->bindParam(':breed', $data['pet_breed']);
            $stmt->bindParam(':age', $data['pet_age']);
            $stmt->bindParam(':medical', $data['medical_condition']);

            // Handle potential null photo
            $photoUrl = isset($data['photo_url']) ? $data['photo_url'] : null;
            $stmt->bindParam(':photoUrl', $photoUrl);

            $stmt->execute();

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("PetModels::create Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * (Admin) Creates a new pet specifically for adoption.
     * Sets adoption_status to 'available'.
     * UPDATED: Now saves photo_url.
     * * @param array $data Associative array of pet data.
     * @return int|false The ID of the newly created pet or false on error.
     */
    public function createAdoptionPet($data) {
        try {
            $sql = "INSERT INTO pets (user_id, pet_name, pet_category, pet_breed, pet_age, medical_condition, photo_url, adoption_status) 
                    VALUES (:userId, :name, :category, :breed, :age, :medical, :photoUrl, 'available')";

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':userId', $data['user_id']);
            $stmt->bindParam(':name', $data['pet_name']);
            $stmt->bindParam(':category', $data['pet_category']);
            $stmt->bindParam(':breed', $data['pet_breed']);
            $stmt->bindParam(':age', $data['pet_age']);
            $stmt->bindParam(':medical', $data['medical_condition']);

            $photoUrl = isset($data['photo_url']) ? $data['photo_url'] : null;
            $stmt->bindParam(':photoUrl', $photoUrl);

            $stmt->execute();

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("PetModels::createAdoptionPet Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates an existing pet.
     * * @param int $petId The ID of the pet to update.
     * @param int $userId The ID of the owner (for security).
     * @param array $data The new data.
     * @return bool True on success, false on failure.
     */
    public function update($petId, $userId, $data) {
        try {
            // Note: We typically don't update photo_url here unless specifically requested,
            // as standard edit forms often don't re-upload the image.
            $sql = "UPDATE pets SET 
                        pet_name = :name,
                        pet_category = :category, 
                        pet_breed = :breed, 
                        pet_age = :age, 
                        medical_condition = :medical
                    WHERE pet_id = :petId AND user_id = :userId";

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':name', $data['pet_name']);
            $stmt->bindParam(':category', $data['pet_category']);
            $stmt->bindParam(':breed', $data['pet_breed']);
            $stmt->bindParam(':age', $data['pet_age']);
            $stmt->bindParam(':medical', $data['medical_condition']);
            $stmt->bindParam(':petId', $petId);
            $stmt->bindParam(':userId', $userId);

            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            error_log("PetModels::update Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes a pet.
     * @param int $petId The ID of the pet to delete.
     * @param int $userId The ID of the owner (for security).
     * @return bool True on success, false on failure.
     */
    public function delete($petId, $userId) {
        try {
            $sql = "DELETE FROM pets WHERE pet_id = :petId AND user_id = :userId";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':petId', $petId);
            $stmt->bindParam(':userId', $userId);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("PetModels::delete Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * (Admin) Finds ALL pets in the database.
     * @return array List of all pets.
     */
    public function findAll() {
        $sql = "SELECT * FROM pets ORDER BY pet_id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}