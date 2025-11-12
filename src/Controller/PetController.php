<?php
// We need the Sanitize helper
require_once __DIR__ . '/../Utils/Sanitize.php';

/**
 * Pet Controller
 * Handles all API requests for /api/pets
 */
class PetController extends BaseController {

    private $petModel;

    public function __construct($db) {
        $this->petModel = new PetModels($db);
    }

    /**
     * Handles GET /api/pets
     * Gets all pets for the logged-in user.
     */
    public function getUserPets() {
        try {
            // 1. Authenticate: Check if user is logged in
            $session = $this->authenticate();
            $userId = $session['user_id'];

            // 2. Get data from model
            $pets = $this->petModel->findByUserId($userId);

            // 3. Send response
            $this->sendResponse($pets, 200);

        } catch (Exception $e) {
            $this->sendError("An error occurred: " . $e->getMessage(), 500);
        }
    }

    /**
     * Handles POST /api/pets
     * Adds a new pet for the logged-in user.
     */
    public function addPet() {
        try {
            // 1. Authenticate
            $session = $this->authenticate();
            $userId = $session['user_id'];

            // 2. Get and sanitize data
            $data = $this->getRequestData();
            $data = Sanitize::all($data);

            // 3. Validation
            if (empty($data['pet_category']) || empty($data['pet_breed']) || empty($data['pet_age'])) {
                $this->sendError('Category, breed, and age are required.', 400);
                return;
            }

            // 4. Add user_id to the data array
            $data['user_id'] = $userId;
            $data['medical_condition'] = $data['medical_condition'] ?? 'None';

            // 5. Call model to create
            $newPetId = $this->petModel->create($data);

            if ($newPetId) {
                // 6. Send success response
                $this->sendResponse([
                    'status' => 'success',
                    'message' => 'Pet added successfully.',
                    'pet_id' => $newPetId
                ], 201);
            } else {
                $this->sendError('Failed to add pet.', 500);
            }

        } catch (Exception $e) {
            $this->sendError("An error occurred: " . $e->getMessage(), 500);
        }
    }

    /**
     * Handles PUT /api/pets/:id
     * Updates an existing pet.
     */
    public function updatePet($petId) {
        try {
            // 1. Authenticate
            $session = $this->authenticate();
            $userId = $session['user_id'];

            // 2. Get and sanitize data
            $data = $this->getRequestData();
            $data = Sanitize::all($data);

            // 3. Validation
            if (empty($data['pet_category']) || empty($data['pet_breed']) || empty($data['pet_age'])) {
                $this->sendError('Category, breed, and age are required.', 400);
                return;
            }

            // 4. Call model to update
            // The model itself checks if the user ID matches, so this is secure.
            $success = $this->petModel->update($petId, $userId, $data);

            if ($success) {
                $this->sendResponse(['status' => 'success', 'message' => 'Pet updated successfully.'], 200);
            } else {
                $this->sendError('Could not update pet. Make sure the pet exists and you are the owner.', 404);
            }

        } catch (Exception $e) {
            $this->sendError("An error occurred: " . $e->getMessage(), 500);
        }
    }

    /**
     * Handles DELETE /api/pets/:id
     * Deletes an existing pet.
     */
    public function deletePet($petId) {
        try {
            // 1. Authenticate
            $session = $this->authenticate();
            $userId = $session['user_id'];

            // 2. Call model to delete
            $success = $this->petModel->delete($petId, $userId);

            if ($success) {
                $this->sendResponse(['status' => 'success', 'message' => 'Pet deleted successfully.'], 200);
            } else {
                $this->sendError('Could not delete pet. Make sure the pet exists and you are the owner.', 404);
            }

        } catch (Exception $e) {
            $this->sendError("An error occurred: " . $e->getMessage(), 500);
        }
    }
}