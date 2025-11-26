<?php
/**
 * User Model
 * This class handles all database operations for the `users` table.
 */
class UserModels {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Finds a user by their ID.
     */
    public function findById($userId) {
        try {
            $sql = "SELECT * FROM users WHERE user_id = :userId LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':userId', $userId);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Finds a user by their email address.
     */
    public function findByEmail($email) {
        try {
            $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * (Admin) Finds ALL users.
     */
    public function findAll() {
        $sql = "SELECT user_id, first_name, last_name, email, phone, role, created_at FROM users ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * --- NEW METHOD ---
     * Finds all users with the role 'doctor'.
     * @return array List of doctors.
     */
    public function findDoctors() {
        // Select specific fields needed for the booking dropdown
        $sql = "SELECT user_id, first_name, last_name, specialty, email FROM users WHERE role = 'doctor'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Creates a new user in the database.
     */
    public function create($data) {
        $firstName = $data['firstName'];
        $lastName = $data['lastName'];
        $email = $data['email'];
        $password = $data['password'];
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $sql = "INSERT INTO users (first_name, last_name, email, password, role) 
                VALUES (:firstName, :lastName, :email, :password, :role)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':firstName', $firstName);
            $stmt->bindParam(':lastName', $lastName);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashedPassword);

            $defaultRole = 'user';
            $stmt->bindParam(':role', $defaultRole);

            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}