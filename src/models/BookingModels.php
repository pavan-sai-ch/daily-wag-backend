<?php
/**
 * Booking Model
 * Handles all database operations for the `bookings` table.
 */
class BookingModels {

    private $db;

    /**
     * Constructor to inject the database connection.
     * @param PDO $db The PDO database connection.
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Creates a new booking in the database.
     *
     * @param array $data Associative array of booking data.
     * @return int The ID of the newly created booking.
     */
    public function create($data) {
        $sql = "INSERT INTO bookings (user_id, pet_id, doctor_id, booking_type, booking_date, service_type, status) 
                VALUES (:userId, :petId, :doctorId, :bookingType, :bookingDate, :serviceType, 'Pending')";

        $stmt = $this->db->prepare($sql);

        // Bind all parameters
        $stmt->bindParam(':userId', $data['user_id']);
        $stmt->bindParam(':petId', $data['pet_id']);

        // doctor_id can be null for grooming
        $stmt->bindParam(':doctorId', $data['doctor_id'], $data['doctor_id'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);

        $stmt->bindParam(':bookingType', $data['booking_type']);
        $stmt->bindParam(':bookingDate', $data['booking_date']);
        $stmt->bindParam(':serviceType', $data['service_type']);

        $stmt->execute();

        return $this->db->lastInsertId();
    }

    /**
     * Finds all bookings for a specific user.
     *
     * @param int $userId The user's ID.
     * @return array A list of the user's bookings.
     */
    public function findByUserId($userId) {
        $sql = "SELECT * FROM bookings WHERE user_id = :userId ORDER BY booking_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Finds all bookings for a specific doctor.
     *
     * @param int $doctorId The doctor's user_id.
     * @return array A list of the doctor's bookings.
     */
    public function findByDoctorId($doctorId) {
        // We also join with pets and users to get their names for the doctor's schedule
        $sql = "SELECT 
                    b.*, 
                    p.pet_category, 
                    p.pet_breed,
                    u.first_name AS owner_first_name,
                    u.last_name AS owner_last_name
                FROM bookings b
                JOIN pets p ON b.pet_id = p.pet_id
                JOIN users u ON b.user_id = u.user_id
                WHERE b.doctor_id = :doctorId
                ORDER BY b.booking_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':doctorId', $doctorId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Finds all bookings (for an admin).
     *
     * @return array A list of all bookings.
     */
    public function findAll() {
        $sql = "SELECT * FROM bookings ORDER BY booking_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Updates the status of a booking (for an admin).
     *
     * @param int $bookingId The ID of the booking to update.
     * @param string $status The new status.
     * @return bool True on success, false on failure.
     */
    public function updateStatus($bookingId, $status) {
        $sql = "UPDATE bookings SET status = :status WHERE booking_id = :bookingId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':bookingId', $bookingId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}