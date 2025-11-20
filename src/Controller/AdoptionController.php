<?php
require_once __DIR__ . '/../Utils/Sanitize.php';
require_once __DIR__ . '/../models/AdoptionModels.php';

class AdoptionController extends BaseController {

    private $adoptionModel;

    public function __construct($db) {
        $this->adoptionModel = new AdoptionModels($db);
    }

    /**
     * GET /api/adoption/available
     * Public route to see adoptable pets.
     */
    public function getAvailablePets() {
        try {
            $pets = $this->adoptionModel->findAvailablePets();
            $this->sendResponse($pets, 200);
        } catch (Exception $e) {
            $this->sendError("Error fetching pets: " . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/adoption/request
     * User requests to adopt a pet.
     */
    public function requestAdoption() {
        try {
            $session = $this->authenticate();
            $userId = $session['user_id'];

            $data = $this->getRequestData();
            $petId = (int)$data['pet_id'];

            if (empty($petId)) {
                $this->sendError('Pet ID is required.', 400);
                return;
            }

            $success = $this->adoptionModel->createRequest($petId, $userId);

            if ($success) {
                $this->sendResponse(['status' => 'success', 'message' => 'Adoption request submitted!'], 201);
            } else {
                $this->sendError('Failed to submit request. Pet may no longer be available.', 400);
            }
        } catch (Exception $e) {
            $this->sendError("Error: " . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/adoption/pending (Admin Only)
     */
    public function getPendingRequests() {
        try {
            $session = $this->authenticate();
            if ($session['user_role'] !== 'admin') {
                $this->sendError('Forbidden', 403);
                return;
            }

            $requests = $this->adoptionModel->findAllPendingRequests();
            $this->sendResponse($requests, 200);
        } catch (Exception $e) {
            $this->sendError("Error: " . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/adoption/:id/status (Admin Only)
     */
    public function updateRequestStatus($adoptId) {
        try {
            $session = $this->authenticate();
            if ($session['user_role'] !== 'admin') {
                $this->sendError('Forbidden', 403);
                return;
            }

            $data = $this->getRequestData();
            $status = $data['status']; // 'approved' or 'denied'

            if (!in_array($status, ['approved', 'denied'])) {
                $this->sendError('Invalid status.', 400);
                return;
            }

            $success = $this->adoptionModel->updateStatus($adoptId, $status);

            if ($success) {
                $this->sendResponse(['status' => 'success', 'message' => 'Request updated.'], 200);
            } else {
                $this->sendError('Failed to update request.', 500);
            }
        } catch (Exception $e) {
            $this->sendError("Error: " . $e->getMessage(), 500);
        }
    }
}
