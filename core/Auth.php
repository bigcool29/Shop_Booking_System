<?php
/**
 * Authentication and Authorization Manager
 */

require_once __DIR__ . '/../config/database.php';

class Auth {
    public static function login($username, $password) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (!headers_sent()) {
                session_regenerate_id(true);
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_username'] = $user['username'];
            $_SESSION['user_fullname'] = $user['fullname'];
            $_SESSION['user_role'] = $user['role'];
            return true;
        }
        return false;
    }

    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['user_id']);
        unset($_SESSION['user_username']);
        unset($_SESSION['user_fullname']);
        unset($_SESSION['user_role']);
        session_destroy();
    }

    public static function check() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return !empty($_SESSION['user_id']);
    }

    public static function user() {
        if (!self::check()) {
            return null;
        }
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['user_username'],
            'fullname' => $_SESSION['user_fullname'],
            'role' => $_SESSION['user_role'],
        ];
    }

    public static function isSuperAdmin() {
        return self::check() && ($_SESSION['user_role'] === 'superadmin');
    }

    public static function requireLogin() {
        if (!self::check()) {
            header("Location: " . base_url('admin/login.php'));
            exit;
        }
    }

    public static function requireSuperAdmin() {
        self::requireLogin();
        if (!self::isSuperAdmin()) {
            http_response_code(403);
            die("Access Denied: Superadmin privileges required.");
        }
    }

    /**
     * Check rate limit for failed login attempts (Max 5 attempts per 15 minutes)
     */
    public static function checkRateLimit($ip) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $key = 'login_attempts_' . md5($ip);
        if (isset($_SESSION[$key])) {
            $data = $_SESSION[$key];
            if ($data['count'] >= 5) {
                $lockoutRemaining = ($data['first_attempt'] + 900) - time(); // 15 mins = 900s
                if ($lockoutRemaining > 0) {
                    return [
                        'allowed' => false,
                        'remaining_seconds' => $lockoutRemaining,
                        'remaining_minutes' => ceil($lockoutRemaining / 60)
                    ];
                } else {
                    unset($_SESSION[$key]);
                }
            }
        }
        return ['allowed' => true];
    }

    /**
     * Record a failed login attempt
     */
    public static function recordFailedLogin($ip) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $key = 'login_attempts_' . md5($ip);
        if (!isset($_SESSION[$key]) || (time() - $_SESSION[$key]['first_attempt'] > 900)) {
            $_SESSION[$key] = [
                'count' => 1,
                'first_attempt' => time()
            ];
        } else {
            $_SESSION[$key]['count']++;
        }
    }

    /**
     * Reset failed login attempts upon successful login
     */
    public static function clearRateLimit($ip) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $key = 'login_attempts_' . md5($ip);
        unset($_SESSION[$key]);
    }
}
