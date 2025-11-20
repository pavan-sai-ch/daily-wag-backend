<?php
// We must start the session *before* any other output or headers.
require_once __DIR__ . '/../src/helpers/sessions/SessionManager.php';
SessionManager::start(); // Start the secure session
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
require_once __DIR__ . '/../src/Controller/PetController.php';
require_once __DIR__ . '/../src/models/PetModels.php';
require_once __DIR__ . '/../src/Controller/BookingController.php';
require_once __DIR__ . '/../src/models/BookingModels.php';
require_once __DIR__ . '/../src/Controller/StoreController.php';
require_once __DIR__ . '/../src/models/StoreModels.php';
require_once __DIR__ . '/../src/Controller/CartController.php';
require_once __DIR__ . '/../src/Controller/CheckoutController.php';
require_once __DIR__ . '/../src/models/OrderModels.php';
require_once __DIR__ . '/../src/Controller/AdoptionController.php';
require_once __DIR__ . '/../src/models/AdoptionModels.php';
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
$router->add('POST', '/api/auth/login', [new AuthController($dbConnection), 'login']);

//pet routes
// Note: We create ONE controller instance and reuse it
$petController = new PetController($dbConnection);

// GET /api/pets - Get all pets for the logged-in user
$router->add('GET', '/api/pets', [$petController, 'getUserPets']);

// POST /api/pets - Add a new pet
$router->add('POST', '/api/pets', [$petController, 'addPet']);

// PUT /api/pets/:id - Update a specific pet
// :id will be converted to a regex and passed as a parameter
$router->add('PUT', '/api/pets/:id', [$petController, 'updatePet']);

// DELETE /api/pets/:id - Delete a specific pet
$router->add('DELETE', '/api/pets/:id', [$petController, 'deletePet']);

$bookingController = new BookingController($dbConnection);

// Customer Routes
$router->add('POST', '/api/bookings/grooming', [$bookingController, 'addGroomingBooking']);
$router->add('POST', '/api/bookings/medical', [$bookingController, 'addMedicalBooking']);
$router->add('GET', '/api/bookings/user', [$bookingController, 'getUserBookings']);

// Doctor Route
$router->add('GET', '/api/bookings/doctor', [$bookingController, 'getDoctorBookings']);

// Admin Routes
$router->add('GET', '/api/bookings/all', [$bookingController, 'getAllBookings']);
$router->add('PUT', '/api/bookings/:id/status', [$bookingController, 'updateBookingStatus']);

$storeController = new StoreController($dbConnection);
// GET /api/products - Get all products
$router->add('GET', '/api/products', [$storeController, 'getAllProducts']);
// GET /api/products/:id - Get a single product
$router->add('GET', '/api/products/:id', [$storeController, 'getProductById']);

//cart routes
$cartController = new CartController($dbConnection);

// GET /api/cart - Get the user's current cart
$router->add('GET', '/api/cart', [$cartController, 'getCart']);

// POST /api/cart - Add an item to the cart
$router->add('POST', '/api/cart', [$cartController, 'addItemToCart']);

// PUT /api/cart/:id - Update an item's quantity in the cart
$router->add('PUT', '/api/cart/:id', [$cartController, 'updateCartItem']);

// DELETE /api/cart/:id - Remove an item from the cart
$router->add('DELETE', '/api/cart/:id', [$cartController, 'removeCartItem']);

$checkoutController = new CheckoutController($dbConnection);
$router->add('POST', '/api/checkout', [$checkoutController, 'processCheckout']);

// --- ADD NEW ADOPTION ROUTES ---
$adoptionController = new AdoptionController($dbConnection);

// Public: View available pets
$router->add('GET', '/api/adoption/available', [$adoptionController, 'getAvailablePets']);

// User: Request adoption
$router->add('POST', '/api/adoption/request', [$adoptionController, 'requestAdoption']);

// Admin: View pending requests
$router->add('GET', '/api/adoption/pending', [$adoptionController, 'getPendingRequests']);

// Admin: Approve/Deny request
$router->add('PUT', '/api/adoption/:id/status', [$adoptionController, 'updateRequestStatus']);

// --- 7. Dispatch the Router ---
$router->dispatch($requestMethod, $requestUri);