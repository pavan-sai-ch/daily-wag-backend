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
        // 1. Get and sanitize the input data
        $data = $this->getRequestData();
        $data = Sanitize::all($data);

        // 2. Validate input
        if (empty($data['email']) || empty($data['password'])) {
            $this->sendError('Email and password are required.', 400);
            return;
        }

        try {
            // 3. Find the user by their email
            $user = $this->userModel->findByEmail($data['email']);

            // 4. Verify the user and password
            // A. Check if user exists
            // B. Use password_verify() to check the hash
            if (!$user || !password_verify($data['password'], $user['password'])) {
                // Use a generic error message to prevent "user enumeration" attacks
                $this->sendError('Invalid email or password.', 401);
                return;
            }

            // 5. --- SESSION HANDLING ---
            // Password is correct! Regenerate session ID to prevent fixation
            session_regenerate_id(true);

            // Store user data in the session
            // We do NOT store the password hash.
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_first_name'] = $user['first_name'];

            // 6. Send the user data back to the React app
            // The React app will use this to update its Redux store.
            $this->sendResponse([
                'status' => 'success',
                'user' => [
                    'id' => $user['user_id'],
                    'firstName' => $user['first_name'],
                    'lastName' => $user['last_name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ], 200);

        } catch (Exception $e) {
            $this->sendError('An internal server error occurred: ' . $e.getMessage(), 500);
        }
    }
}