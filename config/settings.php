<?php
/**
 * Global Settings and Environment Initialization
 */

if (!ob_get_level()) {
    ob_start();
}

if (session_status() === PHP_SESSION_NONE) {
    // Set secure cookie params if possible
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

require_once __DIR__ . '/database.php';

class Settings {
    private static $settings = null;

    public static function load() {
        if (self::$settings === null) {
            try {
                $pdo = Database::getInstance()->getConnection();
                $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
                $data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                self::$settings = $data ?: [];
            } catch (Exception $e) {
                self::$settings = [];
            }
        }
        return self::$settings;
    }

    public static function get($key, $default = '') {
        $all = self::load();
        return isset($all[$key]) ? $all[$key] : $default;
    }

    public static function set($key, $value) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) 
                               VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([$key, $value]);
        self::$settings[$key] = $value;
    }
}

/**
 * Dynamic base URL generator
 */
function base_url($path = '') {
    // Auto-detect base folder
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $scriptDir = dirname($scriptName);
    
    // Normalize path
    if ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') {
        $base = '';
    } else {
        // Strip out /admin or /cron subdirectories if current script is inside them
        $base = preg_replace('/(\/admin|\/cron)$/', '', $scriptDir);
    }
    
    $cleanPath = ltrim($path, '/');
    if ($cleanPath === '') {
        return $base === '' ? '/' : $base;
    }
    return ($base === '' ? '' : $base) . '/' . $cleanPath;
}

/**
 * Shortcut for settings
 */
function get_setting($key, $default = '') {
    return Settings::get($key, $default);
}
