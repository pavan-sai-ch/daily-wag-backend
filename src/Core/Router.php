<?php
/**
 * A basic router class.
 *
 * This class stores API routes and dispatches requests
 * to the correct controller action or callback function.
 */
class Router {
    /**
     * @var array Stores all the registered routes.
     */
    protected $routes = [];

    /**
     * Adds a new route to the router.
     *
     * @param string $method The HTTP method (e.g., 'GET', 'POST').
     * @param string $uri The URL path (e.g., '/api/auth/register').
     * @param callable|array $action The function to execute or [Controller, 'method'].
     */
    public function add($method, $uri, $action) {
        $this->routes[] = [
            'method' => $method,
            'uri'    => $uri,
            'action' => $action
        ];
    }

    /**
     * Dispatches the request to the appropriate route.
     *
     * @param string $requestMethod The HTTP method of the incoming request.
     * @param string $requestUri The URL path of the incoming request.
     */
    public function dispatch($requestMethod, $requestUri) {
        // Loop through all registered routes
        foreach ($this->routes as $route) {

            // Check if both the method and URI match
            if ($route['method'] === $requestMethod && $route['uri'] === $requestUri) {

                $action = $route['action'];

                // If the action is a callable function (like our /api/test route)
                if (is_callable($action)) {
                    // Execute the function
                    call_user_func($action);
                    return; // Stop processing
                }

                // If the action is an array [Controller, 'method']
                if (is_array($action) && count($action) === 2) {
                    $controller = $action[0]; // The controller object (e.g., new AuthController(...))
                    $method = $action[1];     // The method name (e.g., 'register')

                    // Check if the method exists in the controller
                    if (method_exists($controller, $method)) {
                        // Execute the controller method
                        $controller->$method();
                        return; // Stop processing
                    }
                }
            }
        }

        // If no route was matched after the loop
        $this->sendNotFoundResponse();
    }

    /**
     * Sends a 404 Not Found response.
     */
    private function sendNotFoundResponse() {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Not Found: The requested endpoint does not exist.'
        ]);
        exit();
    }
}