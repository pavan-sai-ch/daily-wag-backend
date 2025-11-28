<?php
require_once __DIR__ . '/../Utils/Sanitize.php';
require_once __DIR__ . '/../models/MembershipModels.php';

class MembershipController extends BaseController {

    private $membershipModel;

    public function __construct($db) {
        $this->membershipModel = new MembershipModels($db);
    }

    /**
     * GET /api/membership
     * Gets the logged-in user's active membership status.
     */
    public function getStatus() {
        try {
            $session = $this->authenticate();
            $userId = $session['user_id'];

            $membership = $this->membershipModel->getActiveMembership($userId);

            if ($membership) {
                $this->sendResponse($membership, 200);
            } else {
                // Return 200 OK but null/empty to signify "No Plan"
                $this->sendResponse(null, 200);
            }
        } catch (Exception $e) {
            $this->sendError("Error: " . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/membership
     * Subscribes the user to a plan.
     */
    public function subscribe() {
        try {
            $session = $this->authenticate();
            $userId = $session['user_id'];

            $data = $this->getRequestData();
            $plan = $data['plan'] ?? ''; // 'Silver', 'Gold', 'Platinum'

            $validPlans = ['Silver', 'Gold', 'Platinum'];
            if (!in_array($plan, $validPlans)) {
                $this->sendError('Invalid plan selected.', 400);
                return;
            }

            // Default to 1 month for now
            $success = $this->membershipModel->create($userId, $plan, 1);

            if ($success) {
                $this->sendResponse(['status' => 'success', 'message' => "Welcome to $plan Membership!"], 201);
            } else {
                $this->sendError('Failed to activate membership.', 500);
            }

        } catch (Exception $e) {
            $this->sendError("Error: " . $e->getMessage(), 500);
        }
    }
}