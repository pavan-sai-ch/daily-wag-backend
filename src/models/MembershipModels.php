<?php
/**
 * Membership Model
 * Handles database operations for the `membership` table.
 */
class MembershipModels {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Gets the active membership for a user.
     * @param int $userId
     * @return mixed The membership record or false.
     */
    public function getActiveMembership($userId) {
        // Check for a membership that is 'active' and not expired
        $sql = "SELECT * FROM membership 
                WHERE user_id = :userId 
                AND status = 'active' 
                AND end_date >= CURDATE() 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Creates a new membership subscription.
     * * @param int $userId
     * @param string $plan ('Silver', 'Gold', 'Platinum')
     * @param int $durationMonths (e.g., 1, 12)
     * @return bool True on success
     */
    public function create($userId, $plan, $durationMonths = 1) {
        try {
            // Calculate start and end dates
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime("+$durationMonths month"));

            // 1. Deactivate any existing memberships first (to avoid duplicates)
            $cancelSql = "UPDATE membership SET status = 'expired' WHERE user_id = :userId AND status = 'active'";
            $cancelStmt = $this->db->prepare($cancelSql);
            $cancelStmt->bindParam(':userId', $userId);
            $cancelStmt->execute();

            // 2. Create new membership
            $sql = "INSERT INTO membership (user_id, plan_details, start_date, end_date, status) 
                    VALUES (:userId, :plan, :start, :end, 'active')";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':userId', $userId);
            $stmt->bindParam(':plan', $plan);
            $stmt->bindParam(':start', $startDate);
            $stmt->bindParam(':end', $endDate);

            $stmt->execute();
            return true;

        } catch (PDOException $e) {
            error_log("Membership Create Error: " . $e->getMessage());
            return false;
        }
    }
}