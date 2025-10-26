<?php 
require_once '../app/config/pdo.php'; 
require_once '../app/models/UserModels.php';  
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT');
header('Access-Control-Allow-Headers: Content-Type');

$userModel = new UserModels($pdo);
$action = $_GET['action'] ?? '';
switch ($action) {
    case 'register':
            $data = json_decode(file_get_contents('php://input'), true);
            $first_name = $data['first_name'];
            $last_name = $data['last_name'];
            $email = $data['email'];
            $password = $data['password'];
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $role = $data['role'] ?? 'user_id';

            if($userModel->createUser($first_name, $last_name, $email, $password_hash, $role)) {
                echo json_encode(['message' => 'User registered successfully']);
            } else {
                echo json_encode(['message' => 'Registration failed']);             
            }
        break;  

    case 'login':
        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'];
        $password = $data['password'];
        $user = $userModel->getUserByEmail($email); 
        if($user && password_verify($password, $user['password'])) {
            session_start();
            $SESSION['user_id'] = $user['user_id'];
            $SESSION['role'] = $user['role'];

            echo json_encode([
            'message' => 'Login successful', 
            'user' => [
                'user_id' => $user['user_id'],
                'email' => $user['email'],
                'role' => $user['role']]
            ]);
        } else {
            echo json_encode('Invalid email or password');
        }
        break;

    case 'logout':
        session_start();
        session_destroy();
        echo json_encode('Logout successful');
        break;
    default:
        echo json_encode('Invalid action');
}
