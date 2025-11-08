<?php
class AuthController {
    protected $userModel;

    public function __construct($pdo) {
        require_once __DIR__ . '/../models/UserModels.php';
        $this->userModel = new UserModels($pdo);
    }

    public function register($data) {
        $first_name = ($data['first_name'] ?? '');
        $last_name  = ($data['last_name'] ?? '');
        $email      = ($data['email'] ?? '');
        $password   = $data['password'] ?? '';
        $role       = ($data['role'] ?? 'user');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'Invalid email!'];
        }

        if (empty($password) || strlen($password) < 8) {
            return ['error' => 'Password must be at least 8 characters'];
        }

        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        if ($this->userModel->createUser($first_name, $last_name, $email, $password_hash, $role)) {
            return ['message' => 'User registered!'];
        } else {
            return ['error' => 'Registration failed!'];
        }
    }

    public function login($data) {
        $email    = ($data['email'] ?? '');
        $password = $data['password'] ?? '';

        $user = $this->userModel->getUserByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            if (session_status() !== PHP_SESSION_ACTIVE) session_start();
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role']    = $user['role'];

            $access_count = isset($_COOKIE['access_count']) ? (int)$_COOKIE['access_count'] + 1 : 1;
            setcookie('access_count', $access_count, time() + 3600, '/');

            return [
                'message'      => 'Login Successful',
                'email'        => $user['email'],
                'role'         => $user['role'],
                'access_count' => $access_count
            ];
        }

        return ['error' => 'Invalid credentials'];
    }

    public function logout() {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        session_destroy();
        setcookie('access_count', '', time() - 3600, '/');
        return ['message' => 'Logged out'];
    }
    
    public function listUsers() {
        return $this->userModel->getAllUsers();
    }

    public function updateUser($data) {
    $user_id    = $data['user_id'] ?? '';
    $first_name = $data['first_name'] ?? '';
    $last_name  = $data['last_name'] ?? '';
    $email      = $data['email'] ?? '';
    $role       = $data['role'] ?? 'user';

    if ($this->userModel->updateUser($user_id, $first_name, $last_name, $email, $role)) {
        return ['message' => 'User updated!'];
    } else {
        return ['error' => 'Update failed!'];
    }}
    
    public function deleteUser($data) {
    $user_id = $data['user_id'] ?? '';
    if ($this->userModel->deleteUser($user_id)) {
        return ['message' => 'User deleted!'];
    } else {
        return ['error' => 'Delete failed!'];
    }
}
}