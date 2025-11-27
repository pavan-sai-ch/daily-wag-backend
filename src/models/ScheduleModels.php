<?php
class ScheduleModels {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Sets or Updates a schedule for a specific day.
     * @param int|null $userId (NULL for grooming)
     */
    public function setSchedule($userId, $day, $start, $end, $isActive) {
        // FIX: Use unique placeholders for the UPDATE clause to avoid PDO parameter count errors
        $sql = "INSERT INTO schedule (user_id, day_of_week, start_time, end_time, is_active)
                VALUES (:userId, :day, :start, :end, :active)
                ON DUPLICATE KEY UPDATE 
                start_time = :start_update, 
                end_time = :end_update, 
                is_active = :active_update";

        $stmt = $this->db->prepare($sql);

        // Bind INSERT values
        $stmt->bindValue(':userId', $userId, $userId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindParam(':day', $day);
        $stmt->bindParam(':start', $start);
        $stmt->bindParam(':end', $end);
        $stmt->bindParam(':active', $isActive, PDO::PARAM_BOOL);

        // Bind UPDATE values (Same data, new placeholder names)
        $stmt->bindParam(':start_update', $start);
        $stmt->bindParam(':end_update', $end);
        $stmt->bindParam(':active_update', $isActive, PDO::PARAM_BOOL);

        return $stmt->execute();
    }

    /**
     * Gets the schedule for a provider.
     * @param int|null $userId (NULL for grooming)
     */
    public function getSchedule($userId) {
        // Logic: If userId is provided, match it. If NULL, look for rows where user_id IS NULL.
        if ($userId) {
            $sql = "SELECT * FROM schedule WHERE user_id = :userId";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':userId', $userId);
        } else {
            $sql = "SELECT * FROM schedule WHERE user_id IS NULL";
            $stmt = $this->db->prepare($sql);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Helper: Get specific day schedule
     */
    public function getDaySchedule($userId, $day) {
        if ($userId) {
            $sql = "SELECT * FROM schedule WHERE day_of_week = :day AND user_id = :userId";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':userId', $userId);
        } else {
            $sql = "SELECT * FROM schedule WHERE day_of_week = :day AND user_id IS NULL";
            $stmt = $this->db->prepare($sql);
        }

        $stmt->bindParam(':day', $day);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}