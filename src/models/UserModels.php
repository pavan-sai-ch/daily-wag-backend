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
     * Useful for checking profile completeness before actions.
     *
     * @param int $userId The user's ID.
     * @return mixed The user record as an array, or false if not found.
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
     *
     * @param string $email The user's email.
     * @return mixed The user record as an array, or false if not found.
     */
    public function findByEmail($email) {
        try {
            $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            // fetch() returns the row or false if no row is found
            return $stmt->fetch();
        } catch (PDOException $e) {
            // Log error
            return false;
        }
    }

    /**
     * (Admin) Finds ALL users.
     * @return array List of all users.
     */
    public function findAll() {
        // Select everything except the password!
        $sql = "SELECT user_id, first_name, last_name, email, phone, role, created_at FROM users ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
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
     *
     * @param array $data Associative array of user data
     * @return bool True on success, false on failure.
     */
    public function create($data) {
        // Get data from the array
        $firstName = $data['firstName'];
        $lastName = $data['lastName'];
        $email = $data['email'];
        $password = $data['password'];

        // --- CRITICAL: Hash the password ---
        // We use BCRYPT, the industry standard.
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // The SQL query with named placeholders
        $sql = "INSERT INTO users (first_name, last_name, email, password, role) 
                VALUES (:firstName, :lastName, :email, :password, :role)";

        try {
            // Prepare the statement
            $stmt = $this->db->prepare($sql);

            // Bind the values to the placeholders
            $stmt->bindParam(':firstName', $firstName);
            $stmt->bindParam(':lastName', $lastName);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashedPassword);

            // New users default to 'user' role
            $defaultRole = 'user';
            $stmt->bindParam(':role', $defaultRole);

            // Execute the statement
            $stmt->execute();

            // Return true if the row was successfully inserted
            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            // This will catch errors, like if the email is already in use (UNIQUE constraint)
            return false;
        }
    }

    /**
     * Updates a user's profile information.
     * @param int $userId The ID of the user.
     * @param array $data Associative array of fields to update (phone, address, etc).
     * @return bool True on success.
     */
    public function updateProfile($userId, $data) {
        // We only allow updating specific fields for security
        $sql = "UPDATE users SET 
                    first_name = :firstName,
                    last_name = :lastName,
                    phone = :phone,
                    address = :address
                WHERE user_id = :userId";

        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':firstName', $data['first_name']);
        $stmt->bindParam(':lastName', $data['last_name']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':userId', $userId);

        $stmt->execute();

        return true; // Returns true if query ran without error
    }
}