<?php

namespace Core;

use PDO;
use Exception;

/**
 * A simple regex-based router.
 *
 * This class registers routes and dispatches them to the appropriate
 * controller and method.
 */
class Router
{
    /**
     * @var array Stores all registered routes, grouped by method.
     */
    protected $routes = [];

    /**
     * Adds a route to the routing table.
     *
     * @param string $method The HTTP method (GET, POST, etc.)
     * @param string $uri The URI pattern (e.g., /users/{id})
     * @param array $action The [Controller::class, 'methodName'] array.
     */
    public function addRoute(string $method, string $uri, array $action): void
    {
        // Ensure the URI starts with a forward slash
        $uri = '/' . ltrim($uri, '/');

        $this->routes[strtoupper($method)][$uri] = $action;
    }

    /**
     * Helper method to register a GET route.
     *
     * @param string $uri The URI pattern.
     * @param array $action The [Controller::class, 'methodName'] array.
     */
    public function get(string $uri, array $action): void
    {
        $this->addRoute('GET', $uri, $action);
    }

    /**
     * Helper method to register a POST route.
     *
     * @param string $uri The URI pattern.
     * @param array $action The [Controller::class, 'methodName'] array.
     */
    public function post(string $uri, array $action): void
    {
        $this->addRoute('POST', $uri, $action);
    }

    /**
     * Helper method to register a PUT route.
     *
     * @param string $uri The URI pattern.
     * @param array $action The [Controller::class, 'methodName'] array.
     */
    public function put(string $uri, array $action): void
    {
        $this->addRoute('PUT', $uri, $action);
    }

    /**
     * Helper method to register a DELETE route.
     *
     * @param string $uri The URI pattern.
     * @param array $action The [Controller::class, 'methodName'] array.
     */
    public function delete(string $uri, array $action): void
    {
        $this->addRoute('DELETE', $uri, $action);
    }

    /**
     * Dispatches the request to the correct route.
     *
     * @param string $uri The requested URI.
     * @param string $method The requested HTTP method.
     * @param PDO $pdo The database connection.
     * @throws Exception If no route is found (404) or class/method is invalid (500).
     */
    public function dispatch(string $uri, string $method, PDO $pdo): void
    {
        $method = strtoupper($method);

        // 1. Check if we have any routes for this HTTP method
        if (!isset($this->routes[$method])) {
            $this->abort(404, "No routes found for method {$method}");
        }

        // 2. Find the matching route
        foreach ($this->routes[$method] as $routeUri => $action) {
            // Convert the route URI into a regex pattern
            // e.g., /users/{id} becomes #^/users/([a-zA-Z0-9_-]+)$#
            $pattern = preg_replace(
                '/{([a-zA-Z0-9_-]+)}/', // Matches {id}, {slug}, etc.
                '([a-zA-Z0-9_-]+)',     // Replaces with a capture group
                $routeUri
            );
            $pattern = '#^' . str_replace('/', '\/', $pattern) . '$#';

            // Check if the current URI matches the pattern
            if (preg_match($pattern, $uri, $matches)) {
                // We found a match!

                // Remove the full string match (index 0)
                array_shift($matches);
                $params = $matches;

                // 3. Resolve the controller and method
                [$controllerClass, $methodName] = $action;

                if (!class_exists($controllerClass)) {
                    $this->abort(500, "Controller class {$controllerClass} not found.");
                }

                // 4. Instantiate the controller and pass the DB connection
                // We assume all controllers have a constructor that accepts $pdo.
                // This is best handled by having a BaseController.
                $controller = new $controllerClass($pdo);

                if (!method_exists($controller, $methodName)) {
                    $this->abort(500, "Method {$methodName} not found on controller {$controllerClass}.");
                }

                // 5. Call the controller method with the URL parameters
                call_user_func_array([$controller, $methodName], $params);
                return; // Stop processing
            }
        }

        // 6. No route matched
        $this->abort(404, "Route not found for {$method} {$uri}");
    }

    /**
     * Throws a formatted exception.
     *
     * @param int $code The HTTP status code (e.g., 404, 500).
     * @param string $message The error message.
     * @throws Exception
     */
    protected function abort(int $code, string $message): void
    {
        // This will be caught by the try/catch block in index.php
        throw new Exception($message, $code);
    }
}