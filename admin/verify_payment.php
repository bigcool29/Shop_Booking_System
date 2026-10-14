<?php
/**
 * Admin Action: Approve / Reject Payment Slip
 */

require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helper.php';
require_once __DIR__ . '/../core/Csrf.php';
require_once __DIR__ . '/../core/OrderManager.php';

Auth::requireLogin();
Csrf::verifyOrDie();

$orderId = (int)($_POST['order_id'] ?? 0);
$action = trim($_POST['action'] ?? '');
$reason = trim($_POST['reason'] ?? '');
$admin = Auth::user();

if ($orderId <= 0 || !in_array($action, ['approve', 'reject'])) {
    Helper::json(['success' => false, 'error' => 'พารามิเตอร์ไม่ถูกต้อง'], 400);
}

$res = OrderManager::verifyPayment($orderId, $action, (int)$admin['id'], $reason);

if ($res['success']) {
    Helper::json(['success' => true]);
} else {
    Helper::json(['success' => false, 'error' => $res['error']], 400);
}
