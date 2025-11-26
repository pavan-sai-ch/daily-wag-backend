<?php
// --- 1. Start the Session ---
// This must be the very first thing before any output
require_once __DIR__ . '/../src/helpers/sessions/SessionManager.php';
SessionManager::start();

// --- 2. Global Headers & CORS ---
// Read the frontend URL from environment or default to localhost
$allowed_origin = getenv('FRONTEND_URL') ?: 'http://localhost:5173';

header("Access-Control-Allow-Origin: " . $allowed_origin);
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle pre-flight OPTIONS requests (sent by browsers to check CORS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --- 3. Load Core Files ---
require_once __DIR__ . '/../src/Config/pdo.php';
require_once __DIR__ . '/../src/Core/BaseController.php';
require_once __DIR__ . '/../src/Core/Router.php';
require_once __DIR__ . '/../src/Utils/Sanitize.php';

// --- 4. Load All Controllers & Models ---
// Auth
require_once __DIR__ . '/../src/Controller/AuthController.php';
require_once __DIR__ . '/../src/models/UserModels.php';

// Pets
require_once __DIR__ . '/../src/Controller/PetController.php';
require_once __DIR__ . '/../src/models/PetModels.php';

// Bookings
require_once __DIR__ . '/../src/Controller/BookingController.php';
require_once __DIR__ . '/../src/models/BookingModels.php';

// Store & Checkout
require_once __DIR__ . '/../src/Controller/StoreController.php';
require_once __DIR__ . '/../src/models/StoreModels.php';
require_once __DIR__ . '/../src/Controller/CartController.php';
require_once __DIR__ . '/../src/Controller/CheckoutController.php';
require_once __DIR__ . '/../src/models/OrderModels.php';

// Adoption
require_once __DIR__ . '/../src/Controller/AdoptionController.php';
require_once __DIR__ . '/../src/models/AdoptionModels.php';


// --- 5. Get Request Info ---
$requestMethod = $_SERVER["REQUEST_METHOD"];
// Parse the URL path to handle query strings correctly
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);


// --- 6. Initialize Router & Database ---
$router = new Router();
$dbConnection = (new Database())->getConnection();

if ($dbConnection === null) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed."]);
    exit();
}


// --- 7. Define API Routes ---

// >> System Check
$router->add('GET', '/api/test', function() {
    echo json_encode(['status' => 'success', 'message' => 'API is online and working!']);
});

// >> Authentication
$authController = new AuthController($dbConnection);
$router->add('POST', '/api/auth/register', [$authController, 'register']);
$router->add('POST', '/api/auth/login', [$authController, 'login']);
$router->add('GET', '/api/auth/me', [$authController, 'checkSession']);
// Admin: Get All Users
$router->add('GET', '/api/users', [$authController, 'getAllUsers']);
// Public: Get List of Doctors
$router->add('GET', '/api/doctors', [$authController, 'getDoctors']);


// >> Pet Management
$petController = new PetController($dbConnection);
$router->add('GET', '/api/pets', [$petController, 'getUserPets']);
$router->add('POST', '/api/pets', [$petController, 'addPet']);
$router->add('PUT', '/api/pets/:id', [$petController, 'updatePet']);
$router->add('DELETE', '/api/pets/:id', [$petController, 'deletePet']);
// Admin: Manage Pets
$router->add('GET', '/api/admin/pets', [$petController, 'getAllPets']);
$router->add('POST', '/api/admin/pets', [$petController, 'addAdoptionPet']);


// >> Bookings (Appointments)
$bookingController = new BookingController($dbConnection);
// Customer
$router->add('POST', '/api/bookings/grooming', [$bookingController, 'addGroomingBooking']);
$router->add('POST', '/api/bookings/medical', [$bookingController, 'addMedicalBooking']);
$router->add('GET', '/api/bookings/user', [$bookingController, 'getUserBookings']);
// Doctor
$router->add('GET', '/api/bookings/doctor', [$bookingController, 'getDoctorBookings']);
// Admin
$router->add('GET', '/api/bookings/all', [$bookingController, 'getAllBookings']);
$router->add('PUT', '/api/bookings/:id/status', [$bookingController, 'updateBookingStatus']);


// >> Store (Catalog)
$storeController = new StoreController($dbConnection);
$router->add('GET', '/api/products', [$storeController, 'getAllProducts']);
$router->add('GET', '/api/products/:id', [$storeController, 'getProductById']);


// >> Store (Cart - Session Based)
$cartController = new CartController($dbConnection);
$router->add('GET', '/api/cart', [$cartController, 'getCart']);
$router->add('POST', '/api/cart', [$cartController, 'addItemToCart']);
$router->add('PUT', '/api/cart/:id', [$cartController, 'updateCartItem']);
$router->add('DELETE', '/api/cart/:id', [$cartController, 'removeCartItem']);


// >> Store (Checkout & Orders)
$checkoutController = new CheckoutController($dbConnection);
// Customer
$router->add('POST', '/api/checkout', [$checkoutController, 'processCheckout']);
$router->add('GET', '/api/orders/user', [$checkoutController, 'getUserOrders']);
// Admin
$router->add('GET', '/api/orders/all', [$checkoutController, 'getAllOrders']);
$router->add('GET', '/api/orders/:id/items', [$checkoutController, 'getOrderItems']);
$router->add('PUT', '/api/orders/:id/status', [$checkoutController, 'updateOrderStatus']);


// >> Adoption
$adoptionController = new AdoptionController($dbConnection);
// Public
$router->add('GET', '/api/adoption/available', [$adoptionController, 'getAvailablePets']);
// User
$router->add('POST', '/api/adoption/request', [$adoptionController, 'requestAdoption']);
// Admin
$router->add('GET', '/api/adoption/pending', [$adoptionController, 'getPendingRequests']);
$router->add('PUT', '/api/adoption/:id/status', [$adoptionController, 'updateRequestStatus']);


// --- 8. Dispatch Request ---
$router->dispatch($requestMethod, $requestUri);