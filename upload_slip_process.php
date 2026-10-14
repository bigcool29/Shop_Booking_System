<?php
/**
 * Slip Upload Processor
 */

require_once __DIR__ . '/config/settings.php';
require_once __DIR__ . '/core/Helper.php';
require_once __DIR__ . '/core/Csrf.php';
require_once __DIR__ . '/core/OrderManager.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . base_url('track.php'));
    exit;
}

Csrf::verifyOrDie();

$code = trim($_POST['order_code'] ?? '');
$transferDatetime = trim($_POST['transfer_datetime'] ?? '');
$customerNote = trim($_POST['customer_note'] ?? '');

$pdo = Database::getInstance()->getConnection();
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_code = ? LIMIT 1");
$stmt->execute([$code]);
$order = $stmt->fetch();

if (!$order) {
    Helper::setFlash('error', 'ไม่พบคำสั่งจอง', 'รหัสคำสั่งจองไม่ถูกต้อง');
    header("Location: " . base_url('track.php'));
    exit;
}

// Check expiration if status is pending_payment
if ($order['status'] === 'pending_payment' && strtotime($order['expires_at']) < time()) {
    Helper::setFlash('error', 'หมดเวลาชำระเงินแล้ว', 'คำสั่งจองนี้หมดเวลาและถูกยกเลิกแล้ว กรุณาทำรายการใหม่');
    header("Location: " . base_url('payment.php?code=' . urlencode($code)));
    exit;
}

// Upload Slip File
if (!isset($_FILES['slip_file'])) {
    Helper::setFlash('error', 'กรุณาแนบไฟล์สลิป', 'ไม่พบไฟล์ภาพหลักฐานการโอนเงิน');
    header("Location: " . base_url('payment.php?code=' . urlencode($code)));
    exit;
}

$uploadResult = Helper::uploadFile($_FILES['slip_file'], 'slips');

if (!$uploadResult['success']) {
    Helper::setFlash('error', 'อัปโหลดสลิปไม่สำเร็จ', $uploadResult['error']);
    header("Location: " . base_url('payment.php?code=' . urlencode($code)));
    exit;
}

try {
    $pdo->beginTransaction();

    // Insert Payment Record
    $payStmt = $pdo->prepare("
        INSERT INTO payments (
            order_id, amount, slip_image, transfer_datetime, customer_note, status
        ) VALUES (
            ?, ?, ?, ?, ?, 'pending'
        )
    ");
    $payStmt->execute([
        $order['id'],
        $order['total_amount'],
        $uploadResult['filename'],
        $transferDatetime ?: date('Y-m-d H:i:s'),
        $customerNote
    ]);

    // Update order status to waiting_verification
    $updateOrder = $pdo->prepare("
        UPDATE orders 
        SET status = 'waiting_verification', updated_at = NOW() 
        WHERE id = ?
    ");
    $updateOrder->execute([$order['id']]);

    $pdo->commit();

    // Clear active pending order session & cookie as payment slip has been submitted
    OrderManager::clearActiveOrder();

    Helper::setFlash('success', 'แนบหลักฐานเรียบร้อยแล้ว', 'เจ้าหน้าที่จะตรวจสอบยอดเงินและสลิปของท่านโดยเร็วที่สุด');
    header("Location: " . base_url('payment.php?code=' . urlencode($code)));
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    Helper::setFlash('error', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', $e->getMessage());
    header("Location: " . base_url('payment.php?code=' . urlencode($code)));
    exit;
}
