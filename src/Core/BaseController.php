<?php
/**
 * Base Controller
 * Provides common methods for all controllers.
 */
class BaseController {

    /**
     * Sends a JSON response.
     */
    protected function sendResponse($data, $statusCode = 200) {
        if (headers_sent()) {
            return;
        }
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    /**
     * Sends a standardized error response.
     */
    protected function sendError($message, $statusCode = 400) {
        $this->sendResponse(['status' => 'error', 'message' => $message], $statusCode);
    }

    /**
     * Gets the JSON data from the request body.
     */
    protected function getRequestData() {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendError('Invalid JSON data provided.', 400);
            exit();
        }
        return $data;
    }

    /**
     * --- NEW FUNCTION ---
     * Checks if a user is authenticated via the session.
     * This is the gatekeeper for all protected endpoints.
     *
     * @return array The authenticated user's session data.
     */
    protected function authenticate() {
        // Check if the session contains our user_id
        if (!isset($_SESSION['user_id'])) {
            // If not, send a 401 Unauthorized error and stop the script
            $this->sendError('Unauthorized: You must be logged in to access this resource.', 401);
            exit();
        }

        // If they are logged in, return their session data
        // so the controller knows *who* is making the request.
        return [
            'user_id' => $_SESSION['user_id'],
            'user_role' => $_SESSION['user_role']
        ];
    }
}