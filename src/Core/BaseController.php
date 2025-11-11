<?php
/**
 * Base Controller
 * This class provides common methods that all other controllers can inherit.
 */
class BaseController {

    /**
     * Sends a JSON response back to the client.
     *
     * @param mixed $data The data to encode as JSON.
     * @param int $statusCode The HTTP status code (e.g., 200, 201, 404).
     */
    protected function sendResponse($data, $statusCode = 200) {
        // Clear any previous headers
        if (headers_sent()) {
            return;
        }

        // Set the HTTP response code
        http_response_code($statusCode);

        // Set the content type header to JSON
        header('Content-Type: application/json');

        // Encode the data and send it
        echo json_encode($data);
    }

    /**
     * Sends a standardized error response.
     *
     * @param string $message The error message.
     * @param int $statusCode The HTTP status code (e.g., 400, 404, 500).
     */
    protected function sendError($message, $statusCode = 400) {
        $this->sendResponse(['status' => 'error', 'message' => $message], $statusCode);
    }

    /**
     * Gets the JSON data from the request body (e.g., from a POST or PUT).
     *
     * @return mixed An associative array of the JSON data, or null on failure.
     */
    protected function getRequestData() {
        // Read the raw input from the request
        $input = file_get_contents('php://input');

        // Decode the JSON string into an associative array
        $data = json_decode($input, true);

        // Handle JSON decoding errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendError('Invalid JSON data provided.', 400);
            exit();
        }

        return $data;
    }
}