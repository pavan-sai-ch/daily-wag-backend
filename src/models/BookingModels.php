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

        // Use bindValue instead of bindParam for safer handling
        $stmt->bindValue(':userId', $data['user_id']);
        $stmt->bindValue(':petId', $data['pet_id']);

        // Handle nullable doctor_id safely
        if (empty($data['doctor_id'])) {
            $stmt->bindValue(':doctorId', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':doctorId', $data['doctor_id'], PDO::PARAM_INT);
        }

        $stmt->bindValue(':bookingType', $data['booking_type']);
        $stmt->bindValue(':bookingDate', $data['booking_date']);
        $stmt->bindValue(':serviceType', $data['service_type']);

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
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Finds a specific booking by ID.
     * @param int $bookingId
     * @return mixed Booking row or false
     */
    public function findById($bookingId) {
        $sql = "SELECT * FROM bookings WHERE booking_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $bookingId);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Finds all bookings for a specific doctor.
     * Includes joining with pets and users tables for detail display.
     *
     * @param int $doctorId The doctor's user_id.
     * @return array A list of the doctor's bookings.
     */
    public function findByDoctorId($doctorId) {
        $sql = "SELECT 
                    b.*, 
                    p.pet_category, 
                    p.pet_breed,
                    p.pet_name,
                    u.first_name AS owner_first_name,
                    u.last_name AS owner_last_name
                FROM bookings b
                JOIN pets p ON b.pet_id = p.pet_id
                JOIN users u ON b.user_id = u.user_id
                WHERE b.doctor_id = :doctorId
                ORDER BY b.booking_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':doctorId', $doctorId, PDO::PARAM_INT);
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
     * Updates the status of a booking.
     *
     * @param int $bookingId The ID of the booking to update.
     * @param string $status The new status.
     * @return bool True on success, false on failure.
     */
    public function updateStatus($bookingId, $status) {
        $sql = "UPDATE bookings SET status = :status WHERE booking_id = :bookingId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':bookingId', $bookingId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Gets a list of times that are already booked for a specific provider on a specific date.
     * Used by the ScheduleController to determine available slots.
     *
     * @param int|null $doctorId The doctor ID (or null for grooming).
     * @param string $date The date to check (YYYY-MM-DD).
     * @return array Array of time strings (e.g., ['09:30:00', '10:00:00']).
     */
    public function getBookedTimes($doctorId, $date) {
        // We only care about the TIME part of the booking_date for comparison
        // We filter out Cancelled appointments so those slots become free again.
        $sql = "SELECT TIME(booking_date) as booked_time 
                FROM bookings 
                WHERE DATE(booking_date) = :date 
                AND status != 'Cancelled'";

        // If doctorId is provided, filter by that doctor
        if ($doctorId) {
            $sql .= " AND doctor_id = :doctorId";
        } else {
            // If no doctorId (grooming), filter where doctor_id is NULL
            $sql .= " AND doctor_id IS NULL";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':date', $date);

        if ($doctorId) {
            $stmt->bindValue(':doctorId', $doctorId, PDO::PARAM_INT);
        }

        $stmt->execute();

        // Fetch specific column to return a simple flat array [time1, time2]
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Marks a booking as Checked In.
     */
    public function checkIn($bookingId) {
        date_default_timezone_set('America/Chicago'); // Ensure consistency
        $now = date('Y-m-d H:i:s');
        $sql = "UPDATE bookings SET status = 'Checked In', checkin_time = :now WHERE booking_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':now', $now);
        $stmt->bindParam(':id', $bookingId);
        return $stmt->execute();
    }

    /**
     * Automatically updates statuses based on time rules.
     * Runs whenever bookings are fetched to keep data fresh.
     */
    public function autoUpdateStatuses() {
        // Set timezone to Central (or your local timezone) to match user expectation
        // Ideally, this should be set globally in index.php or php.ini
        date_default_timezone_set('America/Chicago');

        $now = date('Y-m-d H:i:s');

        // Rule 1: Auto 'No Show'
        // IF Status is 'Confirmed' AND Current Time > (Booking Time + 15 Minutes)
        // Meaning: If they are more than 15 minutes late, mark No Show.
        $sqlNoShow = "UPDATE bookings 
                      SET status = 'No Show' 
                      WHERE status = 'Confirmed' 
                      AND :now > DATE_ADD(booking_date, INTERVAL 15 MINUTE)";

        $stmt1 = $this->db->prepare($sqlNoShow);
        $stmt1->bindParam(':now', $now);
        $stmt1->execute();

        // Rule 2: Auto 'Completed'
        // IF Status is 'Checked In' AND Current Time > (Booking Time + 1 Hour)
        // THEN set Status = 'Completed'
        $sqlComplete = "UPDATE bookings 
                        SET status = 'Completed' 
                        WHERE status = 'Checked In' 
                        AND :now > DATE_ADD(booking_date, INTERVAL 1 HOUR)";

        $stmt2 = $this->db->prepare($sqlComplete);
        $stmt2->bindParam(':now', $now);
        $stmt2->execute();
    }
}