<?php
/**
 * Csrf - Cross-Site Request Forgery Protection
 * Generates and validates cryptographic tokens for forms and AJAX requests.
 */

class Csrf {
    private const SESSION_KEY = 'shopease_csrf_token';

    /**
     * Generate or retrieve the current session token
     */
    public static function getToken(): string {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Validate an incoming token against the stored session token
     */
    public static function validate(?string $token): bool {
        if (empty($token) || empty($_SESSION[self::SESSION_KEY])) {
            return false;
        }
        return hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    /**
     * Output a hidden HTML input containing the CSRF token
     */
    public static function field(): string {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::getToken(), ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Verify an incoming request (checks POST parameter or X-CSRF-TOKEN header)
     * Throws an exception or returns false if invalid
     */
    public static function verify(bool $throwException = true): bool {
        $token = $_POST['csrf_token'] ?? null;

        if (!$token && isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        $isValid = self::validate($token);

        if (!$isValid && $throwException) {
            http_response_code(403);
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'))) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Invalid or expired CSRF security token.']);
            } else {
                die('Security Error: Invalid or expired CSRF token. Please refresh the page and try again.');
            }
            exit;
        }

        return $isValid;
    }

    /**
     * Regenerate token after sensitive actions (e.g., login, password change)
     */
    public static function regenerate(): string {
        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        return $_SESSION[self::SESSION_KEY];
    }
}
