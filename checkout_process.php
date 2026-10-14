<?php
/**
 * Checkout Process Handler
 */

require_once __DIR__ . '/config/settings.php';
require_once __DIR__ . '/core/Helper.php';
require_once __DIR__ . '/core/Csrf.php';
require_once __DIR__ . '/core/OrderManager.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . base_url('cart.php'));
    exit;
}

Csrf::verifyOrDie();

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    Helper::setFlash('warning', 'ตะกร้าสินค้าว่างเปล่า', 'กรุณาเลือกสินค้าก่อนทำการสั่งจอง');
    header("Location: " . base_url());
    exit;
}

$name = trim($_POST['customer_name'] ?? '');
$phone = preg_replace('/[^0-9]/', '', $_POST['customer_phone'] ?? '');
$email = trim($_POST['customer_email'] ?? '');
$organization = trim($_POST['customer_organization'] ?? '');
$deliveryMethod = ($_POST['delivery_method'] ?? 'pickup') === 'delivery' ? 'delivery' : 'pickup';
$shippingAddress = trim($_POST['shipping_address'] ?? '');
$notes = trim($_POST['notes'] ?? '');

// Validation
if (empty($name)) {
    Helper::setFlash('error', 'ข้อมูลไม่ครบถ้วน', 'กรุณากรอกชื่อ-นามสกุล');
    header("Location: " . base_url('cart.php'));
    exit;
}

if (strlen($phone) < 9 || strlen($phone) > 10) {
    Helper::setFlash('error', 'เบอร์โทรศัพท์ไม่ถูกต้อง', 'กรุณากรอกเบอร์โทรศัพท์ 9-10 หลัก');
    header("Location: " . base_url('cart.php'));
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Helper::setFlash('error', 'อีเมลไม่ถูกต้อง', 'กรุณากรอกอีเมลที่ถูกต้อง');
    header("Location: " . base_url('cart.php'));
    exit;
}

if ($deliveryMethod === 'delivery' && empty($shippingAddress)) {
    Helper::setFlash('error', 'กรุณาระบุที่อยู่จัดส่ง', 'เนื่องจากท่านเลือกจัดส่งทางไปรษณีย์ กรุณาระบุที่อยู่ให้ครบถ้วน');
    header("Location: " . base_url('cart.php'));
    exit;
}

$customerData = [
    'customer_name' => $name,
    'customer_phone' => $phone,
    'customer_email' => $email,
    'customer_organization' => $organization,
    'delivery_method' => $deliveryMethod,
    'shipping_address' => $shippingAddress,
    'notes' => $notes
];

// Execute order creation in database transaction
$result = OrderManager::createOrder($customerData, $cart);

if ($result['success']) {
    OrderManager::setActiveOrder($result['order_code']);
    Helper::setFlash('success', 'บันทึกคำสั่งจองสำเร็จ', 'กรุณาชำระเงินและแนบหลักฐานการโอนเงินภายในเวลาที่กำหนด');
    header("Location: " . base_url('payment.php?code=' . urlencode($result['order_code'])));
    exit;
} else {
    Helper::setFlash('error', 'ไม่สามารถสร้างคำสั่งจองได้', $result['error']);
    header("Location: " . base_url('cart.php'));
    exit;
}
