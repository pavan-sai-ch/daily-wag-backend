<?php
require_once __DIR__ . '/../Utils/Sanitize.php';
// Load S3 Uploader
require_once __DIR__ . '/../Utils/S3Uploader.php';

/**
 * Pet Controller
 * Handles all API requests for /api/pets and /api/admin/pets
 */
class PetController extends BaseController {

    private $petModel;
    private $s3;

    public function __construct($db) {
        $this->petModel = new PetModels($db);
        // Initialize S3 Uploader
        $this->s3 = new S3Uploader();
    }

    // --- Helper: Validate Image ---
    private function validateImage($file) {
        // 1. Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'File upload error code: ' . $file['error'];
        }

        // 2. Check file size (Max 3MB)
        $maxSize = 3 * 1024 * 1024; // 3MB in bytes
        if ($file['size'] > $maxSize) {
            return 'File size exceeds the maximum limit of 3MB.';
        }

        // 3. Check file type (Allow JPG, PNG, GIF, WEBP)
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            return 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.';
        }

        return true; // Valid
    }

    /**
     * (Customer) GET /api/pets
     * Gets all pets for the logged-in user.
     */
    public function getUserPets() {
        try {
            $session = $this->authenticate();
            $userId = $session['user_id'];

            $pets = $this->petModel->findByUserId($userId);

            $this->sendResponse($pets, 200);

        } catch (Exception $e) {
            $this->sendError("An error occurred: " . $e->getMessage(), 500);
        }
    }

    /**
     * (Customer) POST /api/pets
     * Adds a new pet for the logged-in user (with optional photo).
     */
    public function addPet() {
        try {
            $session = $this->authenticate();
            $userId = $session['user_id'];

            // Determine data source (JSON vs Multipart)
            // If sending a file, the content type will be multipart/form-data, so we use $_POST
            $contentType = $_SERVER["CONTENT_TYPE"] ?? '';
            if (strpos($contentType, 'application/json') !== false) {
                $data = $this->getRequestData();
            } else {
                $data = $_POST;
            }

            $data = Sanitize::all($data);

            // Validation
            if (empty($data['pet_name']) || empty($data['pet_category']) || empty($data['pet_breed']) || empty($data['pet_age'])) {
                $this->sendError('Name, Category, Breed, and Age are required.', 400);
                return;
            }

            // --- IMAGE UPLOAD LOGIC ---
            $photoUrl = null;
            if (isset($_FILES['image'])) {
                // Validate the image
                $validation = $this->validateImage($_FILES['image']);
                if ($validation !== true) {
                    $this->sendError($validation, 400);
                    return;
                }

                // Upload to S3
                $photoUrl = $this->s3->upload($_FILES['image'], 'pets');
                if (!$photoUrl) {
                    $this->sendError('Failed to upload image to S3.', 500);
                    return;
                }
            }
            // --------------------------

            $data['user_id'] = $userId;
            $data['medical_condition'] = $data['medical_condition'] ?? 'None';
            $data['photo_url'] = $photoUrl; // Add S3 URL to data for Model

            $newPetId = $this->petModel->create($data);

            if ($newPetId) {
                $this->sendResponse([
                    'status' => 'success',
                    'message' => 'Pet added successfully.',
                    'pet_id' => $newPetId,
                    'photo_url' => $photoUrl
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
     */
    public function updatePet($petId) {
        try {
            $session = $this->authenticate();
            $userId = $session['user_id'];

            // For PUT requests, PHP doesn't parse multipart/form-data automatically.
            // Typically handled via JSON for text updates.
            $data = $this->getRequestData();
            $data = Sanitize::all($data);

            if (empty($data['pet_name']) || empty($data['pet_category']) || empty($data['pet_breed']) || empty($data['pet_age'])) {
                $this->sendError('Name, Category, Breed, and Age are required.', 400);
                return;
            }

            $success = $this->petModel->update($petId, $userId, $data);

            if ($success) {
                $this->sendResponse(['status' => 'success', 'message' => 'Pet updated successfully.'], 200);
            } else {
                $this->sendError('Could not update pet. Check ownership.', 404);
            }

        } catch (Exception $e) {
            $this->sendError("An error occurred: " . $e->getMessage(), 500);
        }
    }

    /**
     * (Customer) DELETE /api/pets/:id
     * Deletes an existing pet AND removes its photo from S3.
     */
    public function deletePet($petId) {
        try {
            $session = $this->authenticate();
            $userId = $session['user_id'];

            // 1. Fetch the pet first to get the photo URL and verify ownership
            $pet = $this->petModel->findOne($petId, $userId);

            if (!$pet) {
                $this->sendError('Pet not found or access denied.', 404);
                return;
            }

            // 2. Delete image from S3 if it exists
            if (!empty($pet['photo_url'])) {
                $this->s3->delete($pet['photo_url']);
            }

            // 3. Delete from Database
            $success = $this->petModel->delete($petId, $userId);

            if ($success) {
                $this->sendResponse(['status' => 'success', 'message' => 'Pet deleted.'], 200);
            } else {
                $this->sendError('Could not delete pet from database.', 500);
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
            $session = $this->authenticate();
            if ($session['user_role'] !== 'admin') {
                $this->sendError('Forbidden', 403);
                return;
            }

            $pets = $this->petModel->findAll();

            $this->sendResponse($pets, 200);

        } catch (Exception $e) {
            $this->sendError("Error: " . $e->getMessage(), 500);
        }
    }

    /**
     * (Admin) POST /api/admin/pets
     * Adds a pet for adoption (with optional image).
     */
    public function addAdoptionPet() {
        try {
            $session = $this->authenticate();
            if ($session['user_role'] !== 'admin') {
                $this->sendError('Forbidden', 403);
                return;
            }

            // Handle Multipart Form Data
            $data = $_POST;
            $data = Sanitize::all($data);

            if (empty($data['pet_name']) || empty($data['pet_category']) || empty($data['pet_breed'])) {
                $this->sendError('Name, Category, and Breed are required.', 400);
                return;
            }

            // --- IMAGE UPLOAD ---
            $photoUrl = null;
            if (isset($_FILES['image'])) {
                $validation = $this->validateImage($_FILES['image']);
                if ($validation !== true) {
                    $this->sendError($validation, 400);
                    return;
                }

                $photoUrl = $this->s3->upload($_FILES['image'], 'adoption');
                if (!$photoUrl) {
                    $this->sendError('Failed to upload image to S3.', 500);
                    return;
                }
            }
            // --------------------

            $data['user_id'] = $session['user_id'];
            $data['medical_condition'] = $data['medical_condition'] ?? 'None';
            $data['pet_age'] = $data['pet_age'] ?? 0;
            $data['photo_url'] = $photoUrl; // Pass URL to model

            $newPetId = $this->petModel->createAdoptionPet($data);

            if ($newPetId) {
                $this->sendResponse([
                    'status' => 'success',
                    'message' => 'Pet listed for adoption.',
                    'pet_id' => $newPetId,
                    'photo_url' => $photoUrl
                ], 201);
            } else {
                $this->sendError('Failed to add pet.', 500);
            }

        } catch (Exception $e) {
            $this->sendError("Error: " . $e->getMessage(), 500);
        }
    }
}