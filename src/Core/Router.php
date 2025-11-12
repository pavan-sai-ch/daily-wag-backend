<?php
/**
 * A regex-based router class.
 *
 * This class stores API routes and dispatches requests
 * to the correct controller action, supporting dynamic parameters.
 */
class Router {
    protected $routes = [];

    /**
     * Adds a new route to the router.
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

            // 1. Convert the route URI (e.g., /api/pets/:id) into a regex
            // This replaces ":id" with a capture group "([a-zA-Z0-9_]+)"
            $pattern = preg_replace_callback('/:([a-zA-Z_]+)/', function($matches) {
                return '([a-zA-Z0-9_]+)';
            }, $route['uri']);

            // Add regex delimiters and make it a full match
            $pattern = '#^' . $pattern . '$#';

            $matches = [];
            // 2. Check if the method matches AND the regex pattern matches the request URI
            if ($route['method'] === $requestMethod && preg_match($pattern, $requestUri, $matches)) {

                // 3. Remove the full match (index 0) to get only the parameters
                array_shift($matches);
                $params = $matches;

                $action = $route['action'];

                // If the action is a callable function
                if (is_callable($action)) {
                    call_user_func_array($action, $params);
                    return;
                }

                // If the action is an array [Controller, 'method']
                if (is_array($action) && count($action) === 2) {
                    $controller = $action[0];
                    $method = $action[1];

                    if (method_exists($controller, $method)) {
                        // 4. Call the controller method and pass the URL parameters
                        call_user_func_array([$controller, $method], $params);
                        return;
                    }
                }
            }
        }

        // If no route was matched
        $this->sendNotFoundResponse();
    }

    private function sendNotFoundResponse() {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Not Found: The requested endpoint does not exist.'
        ]);
        exit();
    }
}