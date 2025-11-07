<?php echo "Backend is working!"; ?>
<?php
$host = 'db';
$db   = 'dailywag';
$user = 'testuser';
$pass = 'testpassword';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "MySQL connection success!";
} catch (\PDOException $e) {
    echo "MySQL connection failed: " . $e->getMessage();
}
?>

<?php
//
///**
// * The Daily Wag - Main Entry Point (Front Controller)
// *
// * All requests are funneled through this file.
// */
//
//// 1. Start the session
//// This is needed for authentication, flash messages, etc.
//session_start();
//
//// 2. Error Reporting
//// Show all errors during development. Turn this off in production.
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
//
//// 3. Define Base Path
//// Create a constant for the project's root directory.
//// __DIR__ is the 'src' directory, so dirname(__DIR__) is the project root.
//define('BASE_PATH', dirname(__DIR__));
//
//// 4. Autoloader
//// This function automatically loads classes when they are first used.
//// It maps namespaces to your directory structure.
//// e.g., new Core\Router() will load 'src/Core/Router.php'
//spl_autoload_register(function ($className) {
//    // Replace namespace backslashes with directory forward slashes
//    $className = str_replace('\\', '/', $className);
//
//    // Construct the full file path
//    // e.g., BASE_PATH . '/src/' . 'Controller/AuthController' . '.php'
//    $file = BASE_PATH . '/src/' . $className . '.php';
//
//    if (file_exists($file)) {
//        require_once $file;
//    }
//});
//
//// 5. Load Helper Files
//// If you have files with helper functions (not classes) in your
//// 'helpers' directory, you must load them manually here.
//// Example:
//// require_once __DIR__ . '/helpers/responses/json.php';
//// require_once __DIR__ . '/helpers/sessions/auth.php';
//
//
//// 6. Load Database Connection
//// We get the PDO instance from the config file.
//// This $pdo variable can be passed to the router and controllers.
//try {
//    $pdo = require_once BASE_PATH . '/app/config/pdo.php';
//} catch (PDOException $e) {
//    // If the database connection fails, stop everything and show an error.
//    http_response_code(500);
//    echo json_encode([
//        'status' => 'error',
//        'message' => 'Database connection failed: ' . $e->getMessage()
//    ]);
//    exit; // Stop script execution
//}
//
//// 7. Initialize the Router
//// We assume your Router class is in 'src/Core/Router.php'
//// and has the namespace 'Core'.
//$router = new Core\Router();
//
//// 8. Load API Route Definitions
//// These files will (presumably) call methods on the $router variable
//// to define your 'GET', 'POST', 'PUT', 'DELETE' endpoints.
//require_once BASE_PATH . '/api/api.php';
//require_once BASE_PATH . '/api/auth.php';
//require_once BASE_PATH . '/api/pets.php';
//require_once BASE_PATH . '/api/products.php';
//require_once BASE_PATH . '/api/users.php';
//require_once BASE_PATH . '/api/visits.php';
//
//// 9. Get Current Request URI and Method
//$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
//$method = $_SERVER['REQUEST_METHOD'];
//
//// 10. Dispatch the Request
//// We pass the router the URI, the method, and the database connection.
//// The router will find the matching route and execute its controller.
//try {
//    // We assume your router has a 'dispatch' method.
//    $router->dispatch($uri, $method, $pdo);
//} catch (Exception $e) {
//    // A fallback for any unhandled exceptions (e.D., "Route not found")
//    http_response_code($e->getCode() ?: 500);
//    echo json_encode([
//        'status' => 'error',
//        'message' => $e->getMessage()
//    ]);
//}
