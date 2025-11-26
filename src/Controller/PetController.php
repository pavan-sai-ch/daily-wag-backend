<?php
// We need the Sanitize helper to clean input data
require_once __DIR__ . '/../Utils/Sanitize.php';

/**
 * Pet Controller
 * Handles all API requests for /api/pets and /api/admin/pets
 */
class PetController extends BaseController {

    private $petModel;

    /**
     * Constructor to inject the DB connection and instantiate the model.
     * @param PDO $db The database connection.
     */
    public function __construct($db) {
        $this->petModel = new PetModels($db);
    }

    /**
     * (Customer) GET /api/pets
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
     * (Customer) POST /api/pets
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
            if (empty($data['pet_name']) || empty($data['pet_category']) || empty($data['pet_breed']) || empty($data['pet_age'])) {
                $this->sendError('Name, Category, Breed, and Age are required.', 400);
                return;
            }

            // 4. Add user_id to the data array
            $data['user_id'] = $userId;
            // Default medical condition if not provided
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
     * (Customer) PUT /api/pets/:id
     * Updates an existing pet.
     * @param int $petId The ID of the pet to update.
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
            if (empty($data['pet_name']) || empty($data['pet_category']) || empty($data['pet_breed']) || empty($data['pet_age'])) {
                $this->sendError('Name, Category, Breed, and Age are required.', 400);
                return;
            }

            // 4. Call model to update
            // The model itself checks if the user ID matches (in the WHERE clause), so this is secure.
            // We pass the petId from the URL and the userId from the session.
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
     * (Customer) DELETE /api/pets/:id
     * Deletes an existing pet.
     * @param int $petId The ID of the pet to delete.
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

    /**
     * (Admin) GET /api/admin/pets
     * Gets all pets in the system (for the Admin Dashboard).
     */
    public function getAllPets() {
        try {
            // 1. Authenticate & Check Admin Role
            $session = $this->authenticate();
            if ($session['user_role'] !== 'admin') {
                $this->sendError('Forbidden', 403);
                return;
            }

            // 2. Fetch data via model
            $pets = $this->petModel->findAll();

            // 3. Send response
            $this->sendResponse($pets, 200);

        } catch (Exception $e) {
            $this->sendError("Error: " . $e->getMessage(), 500);
        }
    }

    /**
     * (Admin) POST /api/admin/pets
     * Adds a new pet directly to the adoption pool.
     */
    public function addAdoptionPet() {
        try {
            // 1. Authenticate & Check Admin Role
            $session = $this->authenticate();
            if ($session['user_role'] !== 'admin') {
                $this->sendError('Forbidden', 403);
                return;
            }

            // 2. Get Data
            $data = $this->getRequestData();
            $data = Sanitize::all($data);

            // 3. Validation
            if (empty($data['pet_name']) || empty($data['pet_category']) || empty($data['pet_breed'])) {
                $this->sendError('Name, Category, and Breed are required.', 400);
                return;
            }

            // 4. Prepare Data (Owner is the Admin)
            $data['user_id'] = $session['user_id'];
            $data['medical_condition'] = $data['medical_condition'] ?? 'None';
            $data['pet_age'] = $data['pet_age'] ?? 0; // Default age if missing

            // 5. Create Pet using the specific Admin method
            $newPetId = $this->petModel->createAdoptionPet($data);

            if ($newPetId) {
                $this->sendResponse(['status' => 'success', 'message' => 'Pet listed for adoption.', 'pet_id' => $newPetId], 201);
            } else {
                $this->sendError('Failed to add pet.', 500);
            }

        } catch (Exception $e) {
            $this->sendError("Error: " . $e->getMessage(), 500);
        }
    }
}