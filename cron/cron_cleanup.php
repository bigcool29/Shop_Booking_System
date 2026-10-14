<?php
/**
 * Cron Job: Auto-cleanup Expired Pending Orders
 * Releases reserved stock back to inventory
 *
 * Usage:
 * CLI: php cron/cron_cleanup.php
 * Web: http://localhost/.../cron/cron_cleanup.php?key=YOUR_SECRET_KEY
 */

require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../core/OrderManager.php';

// Check execution context
$isCli = (php_sapi_name() === 'cli');
$isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);

if (!$isCli && !$isLocal) {
    $secretKey = get_setting('cron_secret_key', 'cmtc_cron_safe_key_2026');
    if (($_GET['key'] ?? '') !== $secretKey) {
        http_response_code(403);
        die("Forbidden");
    }
}

$cleanedCount = OrderManager::cleanupExpiredOrders();

$timestamp = date('Y-m-d H:i:s');
$message = "[{$timestamp}] Auto-cleanup completed. Expired orders cancelled: {$cleanedCount}\n";

if ($isCli) {
    echo $message;
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
}
