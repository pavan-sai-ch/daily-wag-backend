<?php
/**
 * SessionManager Class
 *
 * A helper class to securely configure and manage PHP sessions.
 */
class SessionManager {

    /**
     * Starts and configures a secure session.
     */
    public static function start() {
        // If a session is already active, do nothing.
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // --- Security Settings ---

        // 1. Use cookies only
        ini_set('session.use_only_cookies', 1);

        // 2. Set the HttpOnly flag
        // This is CRITICAL: It prevents client-side JavaScript (XSS)
        // from being able to read the session cookie.
        ini_set('session.cookie_httponly', 1);

        // 3. Set the Secure flag (if on HTTPS)
        // This ensures the cookie is only sent over HTTPS.
        // You would enable this in production.
        // ini_set('session.cookie_secure', 1);

        // 4. Set SameSite attribute to 'Lax' or 'Strict'
        // This helps prevent Cross-Site Request Forgery (CSRF).
        ini_set('session.cookie_samesite', 'Lax');

        // Start the session
        session_start();

        // 5. Regenerate the session ID after the initial start
        // This prevents "session fixation" attacks.
        if (empty($_SESSION['initialized'])) {
            session_regenerate_id(true);
            $_SESSION['initialized'] = true;
        }
    }
}