<?php
class PetModels {
    private $pdo;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function addPet($petId, $name, $type, $breed, $age) {
        $sql = "INSERT INTO pets (petId, pet_name, pet_type, pet_breed, pet_age) VALUES (:petId, :name, :type, :breed, :age)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute();
    }
    public function getPetsByUser($user_id) {
        $sql = "SELECT * FROM pets WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function updatePet($petId, $pet_name, $pet_type, $pet_breed, $pet_age) {
        $sql = "UPDATE pets SET pet_name=?, pet_type=?, pet_breed=?, pet_age=? WHERE petId=?";
        $stmt= $this->pdo->prepare($sql);
        return $stmt->execute([$pet_name, $pet_type, $pet_breed, $pet_age, $petId]);
    }
    public function deletePet($petId) {
        $sql = "DELETE FROM pets WHERE petId=?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$petId]);
    }
}
?>