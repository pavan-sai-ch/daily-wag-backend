<?php
/**
 * Auth Controller
 * This class handles user registration and login.
 * It extends BaseController to use its response methods.
 */
class AuthController extends BaseController {

    /**
     * @var UserModels The user model instance.
     */
    private $userModel;

    /**
     * Constructor to inject the DB connection and instantiate the model.
     *
     * @param PDO $db The database connection.
     */
    public function __construct($db) {
        // Create a new instance of the UserModels, passing it the DB connection
        $this->userModel = new UserModels($db);
    }

    /**
     * Handles the POST /api/auth/register request.
     */
    public function register() {
        // 1. Get the JSON data from the request
        $data = $this->getRequestData();

        // 2. Validate the data
        if (empty($data['firstName']) || empty($data['lastName']) || empty($data['email']) || empty($data['password'])) {
            $this->sendError('All fields (firstName, lastName, email, password) are required.', 400);
            return;
        }

        // (Your frontend validation is more complex, but this is a good server-side check)
        if (strlen($data['password']) < 8) {
            $this->sendError('Password must be at least 8 characters long.', 400);
            return;
        }

        try {
            // 3. Check if user already exists
            $existingUser = $this->userModel->findByEmail($data['email']);
            if ($existingUser) {
                // 409 Conflict is the correct status code for a duplicate
                $this->sendError('An account with this email already exists.', 409);
                return;
            }

            // 4. Try to create the user
            $success = $this->userModel->create($data);

            if ($success) {
                // 5. Send success response
                // 201 Created is the correct status code for a successful POST
                $this->sendResponse(['status' => 'success', 'message' => 'User registered successfully.'], 201);
            } else {
                $this->sendError('Failed to register user. Please try again.', 500);
            }
        } catch (Exception $e) {
            // Catch any other unexpected errors
            $this->sendError('An internal server error occurred: ' . $e->getMessage(), 500);
        }
    }

    // You will add your login() function here later
    public function login() {
        // 1. Get request data (email, password)
        // 2. Find user by email in the model
        // 3. If user exists, use password_verify() to check the hash
        // 4. If password is correct, create a session/cookie
        // 5. Send success response
        $this->sendResponse(['status' => 'success', 'message' => 'Login endpoint is not yet implemented.']);
    }
}