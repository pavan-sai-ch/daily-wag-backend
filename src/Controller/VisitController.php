<?php
require_once __DIR__ . '/../Utils/Sanitize.php';
require_once __DIR__ . '/../models/VisitModels.php';

class VisitController extends BaseController {
    private $visitModel;

    public function __construct($db) {
        $this->visitModel = new VisitModels($db);
    }

    /**
     * POST /api/log-visit
     * Logs a page view or action.
     */
    public function logVisit() {
        // 1. Get User ID (if logged in)
        $userId = null;
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }

        // 2. Get Request Data
        $data = $this->getRequestData();
        $page = isset($data['page']) ? Sanitize::string($data['page']) : 'Unknown';

        // 3. Get IP Address
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        // 4. Log to DB
        $this->visitModel->log($userId, $ip, $page);

        // 5. Silent Success (200 OK)
        $this->sendResponse(['status' => 'logged'], 200);
    }
}