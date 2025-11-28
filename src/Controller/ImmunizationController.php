<?php
require_once __DIR__ . '/../Utils/Sanitize.php';
require_once __DIR__ . '/../models/ImmunizationModels.php';
require_once __DIR__ . '/../models/PetModels.php'; // Needed for ownership check

class ImmunizationController extends BaseController {

    private $immunizationModel;
    private $petModel;

    public function __construct($db) {
        $this->immunizationModel = new ImmunizationModels($db);
        $this->petModel = new PetModels($db);
    }

    /**
     * GET /api/pets/:id/immunizations
     * Gets records for a specific pet.
     */
    public function getRecords($petId) {
        try {
            $session = $this->authenticate();

            // Security Check: Does this user own this pet? (Or is it a doctor/admin)
            // For now, let's allow the owner, any doctor, or admin.
            $isOwner = $this->petModel->findOne($petId, $session['user_id']);
            $isStaff = in_array($session['user_role'], ['doctor', 'admin']);

            if (!$isOwner && !$isStaff) {
                $this->sendError('Forbidden: You do not have access to this pet\'s records.', 403);
                return;
            }

            $records = $this->immunizationModel->findByPetId($petId);
            $this->sendResponse($records, 200);

        } catch (Exception $e) {
            $this->sendError("Error: " . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/immunizations
     * Adds a new record.
     */
    public function addRecord() {
        try {
            $session = $this->authenticate();
            // Only Doctors or Admins should probably add records,
            // but for now, let's allow Owners too for tracking purposes.

            $data = $this->getRequestData();
            $data = Sanitize::all($data);

            if (empty($data['pet_id']) || empty($data['vaccine_name']) || empty($data['vaccine_date'])) {
                $this->sendError('Pet ID, Vaccine Name, and Date are required.', 400);
                return;
            }

            // Verify pet exists
            // (In a real app, verify ownership here too)

            $newId = $this->immunizationModel->create($data);

            if ($newId) {
                $this->sendResponse(['status' => 'success', 'message' => 'Record added.', 'immun_id' => $newId], 201);
            } else {
                $this->sendError('Failed to add record.', 500);
            }

        } catch (Exception $e) {
            $this->sendError("Error: " . $e->getMessage(), 500);
        }
    }
}