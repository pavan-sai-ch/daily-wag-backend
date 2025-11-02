<?php
require_once '../app/config/pdo.php'; 
require_once '../app/models/PetModels.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$action = $_GET['action'] ?? '';
$petModel = new PetModels($pdo);

switch ($action) {
    case 'addPet':
        $data = json_decode(file_get_contents('php://input'), true);
        $petId    = $data['petId'] ?? null;
        $pet_name = $data['pet_name'] ?? null;
        $pet_type = $data['pet_type'] ?? null;
        $pet_breed= $data['pet_breed'] ?? null;
        $pet_age  = $data['pet_age'] ?? null;
        if ($petModel->addPet($petId, $pet_name, $pet_type, $pet_breed, $pet_age)) {
            echo json_encode(['message' => 'Pet added']);
        } else {
            echo json_encode(['error' => 'Failed! unable to add pet']);
        }
        break;

    case 'getByUser':
        $data = json_decode(file_get_contents('php://input'), true);
        $user_id = $data['user_id'] ?? null;
    }
