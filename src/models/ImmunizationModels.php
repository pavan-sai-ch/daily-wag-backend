<?php
/**
 * Immunization Model
 * Handles database operations for the `immunizations` table.
 */
class ImmunizationModels {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Gets all immunization records for a specific pet.
     * @param int $petId
     * @return array List of records.
     */
    public function findByPetId($petId) {
        $sql = "SELECT * FROM immunizations WHERE pet_id = :petId ORDER BY vaccine_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':petId', $petId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Adds a new immunization record.
     * @param array $data {pet_id, vaccine_name, vaccine_date, due_date, comments}
     * @return int|false The new ID or false.
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO immunizations (pet_id, vaccine_name, vaccine_date, due_date, comments) 
                    VALUES (:petId, :name, :date, :dueDate, :comments)";

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':petId', $data['pet_id']);
            $stmt->bindParam(':name', $data['vaccine_name']);
            $stmt->bindParam(':date', $data['vaccine_date']);

            // Handle optional fields
            $dueDate = !empty($data['due_date']) ? $data['due_date'] : null;
            $stmt->bindParam(':dueDate', $dueDate);

            $comments = !empty($data['comments']) ? $data['comments'] : null;
            $stmt->bindParam(':comments', $comments);

            $stmt->execute();
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("ImmunizationModels::create Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes an immunization record.
     */
    public function delete($immunId) {
        $sql = "DELETE FROM immunizations WHERE immun_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $immunId);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}