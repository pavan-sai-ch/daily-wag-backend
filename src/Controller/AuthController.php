<?php
require_once __DIR__ . '/../Utils/Sanitize.php';

class AuthController extends BaseController {

    private $userModel;

    public function __construct($db) {
        $this->userModel = new UserModels($db);
    }

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

            session_regenerate_id(true);
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
                    'role' => $user['role']
                ]
            ], 200);

        } catch (Exception $e) {
            $this->sendError('An internal server error occurred: ' . $e->getMessage(), 500);
        }
    }

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
                    'role' => $user['role']
                ]
            ], 200);

        } catch (Exception $e) {
            $this->sendError('Session check failed', 500);
        }
    }

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
     * --- NEW METHOD ---
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