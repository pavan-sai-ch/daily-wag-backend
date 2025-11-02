<?php 
require_once __DIR__ . '/../app/config/pdo.php';  
require_once __DIR__ . '/../src/models/UserModels.php';
header('Content-Type: application/json');
session_start();

$userModel = new UserModels($pdo); 
$action = $_GET['action'] ?? ''; 

if($action === 'register') {
    $data = json_decode(file_get_contents('php://input'), true);
    $first_name = ($data['first_name']);
    $last_name = ($data['last_name']);
    $email = $data['email'];
    $password = $data['password'];
    $role = isset($data['role']) ? $data['role'] : 'user';

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error'=>'Invalid email!']);
        exit;
    }
    if(empty($password) OR strlen($password) < 8) {
    echo json_encode(['error'=>'Password must be at least 8 characters!']);
        exit;
    }

    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    if($userModel->createUser($first_name, $last_name, $email, $password_hash, $role)) {
        echo json_encode(['message'=>'User registered!']);
    } else {
        echo json_encode(['error'=> 'Registration failed!']);
    }
    exit;
}
if($action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = ($data['email']);
    $password = ($data['password']);

    $user = $userModel->getUserByEmail($email);
    if($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];

        $access_count = isset($_COOKIE['access_count']) ? $_COOKIE['access_count'] + 1 : 1;
        setcookie('access_count', $access_count, time()+3600);

        echo json_encode([
            'message'=>'Login Successful', 
            'email'=>$user['email'], 
            'role'=>$user['role'], 
            'access_count'=>$access_count
        ]);
    } else {
        echo json_encode(['error'=>'Invalid credentials']);
    }
    exit;
}
if ($action === 'logout') {
    session_destroy();
    echo json_encode(['message'=>'Logged out']);
    exit;
}
echo json_encode(['error'=>'Invalid action']);