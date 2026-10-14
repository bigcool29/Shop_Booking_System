<?php
/**
 * CSRF Protection Token Manager
 */

class Csrf {
    public static function getToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function token() {
        return self::getToken();
    }

    public static function field() {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function validate($token = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if ($token === null) {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        }
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function verifyOrDie() {
        if (!self::validate()) {
            http_response_code(403);
            die("Error: Invalid or expired CSRF token. Please refresh the page and try again.");
        }
    }
}
