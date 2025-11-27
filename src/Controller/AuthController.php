<?php
require_once __DIR__ . '/../Utils/Sanitize.php';

class AuthController extends BaseController {

    private $userModel;

    public function __construct($db) {
        $this->userModel = new UserModels($db);
    }

    /**
     * Handles POST /api/auth/register
     */
    public function register() {
        $data = $this->getRequestData();
        $data = Sanitize::all($data);

        if (empty($data['firstName']) || empty($data['lastName']) || empty($data['email']) || empty($data['password'])) {
            $this->sendError('All fields are required.', 400);
            return;
        }

        if (strlen($data['password']) < 8) {
            $this->sendError('Password must be at least 8 characters long.', 400);
            return;
        }

        try {
            $existingUser = $this->userModel->findByEmail($data['email']);
            if ($existingUser) {
                $this->sendError('An account with this email already exists.', 409);
                return;
            }

            $success = $this->userModel->create($data);

            if ($success) {
                $this->sendResponse(['status' => 'success', 'message' => 'User registered successfully.'], 201);
            } else {
                $this->sendError('Failed to register user. Please try again.', 500);
            }
        } catch (Exception $e) {
            $this->sendError('An internal server error occurred: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Handles POST /api/auth/login
     */
    public function login() {
        $data = $this->getRequestData();
        $data = Sanitize::all($data);

        if (empty($data['email']) || empty($data['password'])) {
            $this->sendError('Email and password are required.', 400);
            return;
        }

        try {
            $user = $this->userModel->findByEmail($data['email']);

            if (!$user || !password_verify($data['password'], $user['password'])) {
                $this->sendError('Invalid email or password.', 401);
                return;
            }

            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            // Store user data in session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_first_name'] = $user['first_name'];

            $this->sendResponse([
                'status' => 'success',
                'user' => [
                    'id' => $user['user_id'],
                    'firstName' => $user['first_name'],
                    'lastName' => $user['last_name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'address' => $user['address'], // Send address so frontend can validate checkout
                    'phone' => $user['phone']
                ]
            ], 200);

        } catch (Exception $e) {
            $this->sendError('An internal server error occurred: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Handles GET /api/auth/me
     * Used by frontend to check if session is valid on page reload.
     */
    public function checkSession() {
        if (!isset($_SESSION['user_id'])) {
            $this->sendResponse(['authenticated' => false], 200);
            return;
        }

        try {
            $user = $this->userModel->findById($_SESSION['user_id']);

            if (!$user) {
                session_destroy();
                $this->sendResponse(['authenticated' => false], 200);
                return;
            }

            $this->sendResponse([
                'authenticated' => true,
                'user' => [
                    'id' => $user['user_id'],
                    'firstName' => $user['first_name'],
                    'lastName' => $user['last_name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'address' => $user['address'],
                    'phone' => $user['phone']
                ]
            ], 200);

        } catch (Exception $e) {
            $this->sendError('Session check failed', 500);
        }
    }

    /**
     * Handles PUT /api/auth/profile
     * Updates the logged-in user's profile (address, phone, name).
     */
    public function updateProfile() {
        try {
            // 1. Authenticate
            $session = $this->authenticate();
            $userId = $session['user_id'];

            // 2. Get Data
            $data = $this->getRequestData();
            $data = Sanitize::all($data);

            // 3. Validation
            if (empty($data['first_name']) || empty($data['last_name'])) {
                $this->sendError('First name and Last name are required.', 400);
                return;
            }

            // 4. Update Database
            $success = $this->userModel->updateProfile($userId, [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null
            ]);

            if ($success) {
                // Fetch fresh user data to return
                $updatedUser = $this->userModel->findById($userId);

                // Update session data if name changed
                $_SESSION['user_first_name'] = $updatedUser['first_name'];

                $this->sendResponse([
                    'status' => 'success',
                    'message' => 'Profile updated successfully.',
                    'user' => [
                        'id' => $updatedUser['user_id'],
                        'firstName' => $updatedUser['first_name'],
                        'lastName' => $updatedUser['last_name'],
                        'email' => $updatedUser['email'],
                        'role' => $updatedUser['role'],
                        'phone' => $updatedUser['phone'],
                        'address' => $updatedUser['address']
                    ]
                ], 200);
            } else {
                $this->sendError('Failed to update profile.', 500);
            }

        } catch (Exception $e) {
            $this->sendError("Error: " . $e->getMessage(), 500);
        }
    }

    /**
     * (Admin) GET /api/users
     * Fetches all users in the system.
     */
    public function getAllUsers() {
        try {
            $session = $this->authenticate();
            if ($session['user_role'] !== 'admin') {
                $this->sendError('Forbidden', 403);
                return;
            }

            $users = $this->userModel->findAll();
            $this->sendResponse($users, 200);

        } catch (Exception $e) {
            $this->sendError("Error: " . $e->getMessage(), 500);
        }
    }

    /**
     * Handles GET /api/doctors
     * Public route to get a list of doctors for booking.
     */
    public function getDoctors() {
        try {
            $doctors = $this->userModel->findDoctors();

            // Format the data for the frontend dropdown
            $formattedDoctors = array_map(function($doc) {
                return [
                    'id' => $doc['user_id'],
                    'name' => "Dr. " . $doc['first_name'] . " " . $doc['last_name'],
                    'specialization' => $doc['specialty'] ?? 'General Vet',
                    'email' => $doc['email']
                ];
            }, $doctors);

            $this->sendResponse($formattedDoctors, 200);
        } catch (Exception $e) {
            $this->sendError("Error: " . $e->getMessage(), 500);
        }
    }
}