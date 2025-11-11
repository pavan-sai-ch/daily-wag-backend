<?php
// --- 1. Global Headers & CORS ---

// Read the allowed origin from the environment variable
$allowed_origin = getenv('FRONTEND_URL') ?: 'http://localhost:5173';

// Use the variable in the header
header("Access-Control-Allow-Origin: " . $allowed_origin);
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle pre-flight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --- 2. Load Core Files ---
require_once __DIR__ . '/../src/Config/pdo.php';
require_once __DIR__ . '/../src/Core/BaseController.php';
require_once __DIR__ . '/../src/Core/Router.php';
require_once __DIR__ . '/../src/Utils/Sanitize.php';

// --- 3. Load All Your Controllers & Models ---
require_once __DIR__ . '/../src/Controller/AuthController.php';
require_once __DIR__ . '/../src/models/UserModels.php';
// require_once __DIR__ . '/../src/Controller/PetController.php';
// require_once __DIR__ . '/../src/models/PetModels.php';

// --- 4. Get Request Info ---
$requestMethod = $_SERVER["REQUEST_METHOD"];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// --- 5. Initialize the Router & DB Connection ---
$router = new Router();
$dbConnection = (new Database())->getConnection();

// Check if database connection was successful
if ($dbConnection === null) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed. Check configuration."]);
    exit();
}

// --- 6. Define Your API Routes ---
$router->add('GET', '/api/test', function() {
    echo json_encode(['status' => 'success', 'message' => 'API is online and working!']);
});

// Auth Routes
$router->add('POST', '/api/auth/register', [new AuthController($dbConnection), 'register']);
// $router->add('POST', '/api/auth/login', [new AuthController($dbConnection), 'login']);

// --- 7. Dispatch the Router ---
$router->dispatch($requestMethod, $requestUri);