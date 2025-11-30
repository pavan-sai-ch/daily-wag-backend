<?php
class VisitModels {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function log($userId, $ip, $page) {
        try {
            $sql = "INSERT INTO user_visits (user_id, ip_address, page_visited) VALUES (:userId, :ip, :page)";
            $stmt = $this->db->prepare($sql);

            // Bind nullable user_id
            if ($userId) {
                $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':userId', null, PDO::PARAM_NULL);
            }

            $stmt->bindParam(':ip', $ip);
            $stmt->bindParam(':page', $page);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            // Silent fail for logging to not disrupt app
            error_log("Logging Error: " . $e->getMessage());
            return false;
        }
    }
}