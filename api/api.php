<?php
session_start();

require_once '../app/config/pdo.php';
require_once '../src/Controller/AuthController.php';
header('Content-Type: application/json');

$controller = new AuthController($pdo);
$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true);

if ($action === 'register') {
    echo json_encode($controller->register($data));
} else if ($action === 'login') {
    echo json_encode($controller->login($data));
} else if ($action === 'logout') {
    echo json_encode($controller->logout());
} else if ($action === 'list_users') {
    echo json_encode($controller->listUsers());
} else if ($action === 'update_user') {
    echo json_encode($controller->updateUser($data));
} else if ($action === 'delete_user') {
    echo json_encode($controller->deleteUser($data));
}
else {
    echo json_encode(['error' => 'Invalid action']);
}

