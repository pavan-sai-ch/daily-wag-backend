<?php
/**
 * Sanitize Utility Class
 *
 * Provides static methods for cleaning and sanitizing input data
 * before it's used in database queries or rendered to the page.
 */
class Sanitize {

    /**
     * Sanitizes a single string value.
     * Removes tags and converts special characters to HTML entities.
     *
     * @param string $input The raw string to sanitize.
     * @return string The sanitized string.
     */
    public static function string($input) {
        // 1. Remove leading/trailing whitespace
        $input = trim($input);

        // 2. Convert special characters to HTML entities
        // This is the most important step to prevent XSS.
        // ENT_QUOTES: Encodes both single and double quotes.
        // 'UTF-8': Ensures correct character encoding.
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');

        return $input;
    }

    /**
     * Sanitizes an email.
     * Removes illegal characters from an email address.
     *
     * @param string $email The raw email to sanitize.
     * @return string The sanitized email.
     */
    public static function email($email) {
        // 1. Remove whitespace
        $email = trim($email);

        // 2. Use PHP's built-in filter to remove characters
        // that are not valid in an email.
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);

        return $email;
    }

    /**
     * Sanitizes an entire array of data (like $_POST).
     *
     * @param array $data The associative array to clean.
     * @return array The new array with all values sanitized.
     */
    public static function all($data) {
        $sanitizedData = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Recursively clean nested arrays (though not needed for our current form)
                $sanitizedData[$key] = self::all($value);
            } else if (is_string($value)) {
                // Use a specific sanitizer if the key is 'email'
                if ($key === 'email') {
                    $sanitizedData[$key] = self::email($value);
                } else {
                    // Use the generic string sanitizer for everything else
                    $sanitizedData[$key] = self::string($value);
                }
            } else {
                // Keep non-string values (like numbers or booleans) as-is
                $sanitizedData[$key] = $value;
            }
        }

        return $sanitizedData;
    }
}