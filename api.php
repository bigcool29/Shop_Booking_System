<?php
/**
 * AJAX API Endpoints for Cart and Real-Time Actions
 */

require_once __DIR__ . '/config/settings.php';
require_once __DIR__ . '/core/Helper.php';
require_once __DIR__ . '/core/Csrf.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/OrderManager.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'add_to_cart') {
    Csrf::verifyOrDie();

    $sizeId = (int)($_POST['size_id'] ?? 0);
    $qty = (int)($_POST['quantity'] ?? 1);

    if ($sizeId <= 0 || $qty <= 0) {
        Helper::json(['success' => false, 'error' => 'ข้อมูลไม่ถูกต้อง'], 400);
    }

    $pdo = Database::getInstance()->getConnection();
    $stmt = $pdo->prepare("
        SELECT ps.*, p.name AS product_name, p.main_image 
        FROM product_sizes ps
        JOIN products p ON ps.product_id = p.id
        WHERE ps.id = ? AND p.is_active = 1
    ");
    $stmt->execute([$sizeId]);
    $size = $stmt->fetch();

    if (!$size) {
        Helper::json(['success' => false, 'error' => 'ไม่พบข้อมูลสินค้าหรือสินค้านี้ปิดรับจองแล้ว'], 404);
    }

    $available = $size['stock'] - $size['reserved_stock'];
    $currentCartQty = isset($_SESSION['cart'][$sizeId]) ? (int)$_SESSION['cart'][$sizeId]['quantity'] : 0;
    $newTotalQty = $currentCartQty + $qty;

    if (!$size['is_unlimited'] && $newTotalQty > $available) {
        Helper::json([
            'success' => false,
            'error' => "สต็อกคงเหลือไม่พอ (เหลือพร้อมจอง {$available} ตัว ในตะกร้ามีแล้ว {$currentCartQty} ตัว)"
        ], 400);
    }

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $_SESSION['cart'][$sizeId] = [
        'size_id' => $size['id'],
        'product_id' => $size['product_id'],
        'product_name' => $size['product_name'],
        'size_name' => $size['size_name'],
        'chest_inch' => $size['chest_inch'],
        'length_inch' => $size['length_inch'],
        'price' => (float)$size['price'],
        'main_image' => $size['main_image'],
        'is_unlimited' => (int)$size['is_unlimited'],
        'quantity' => $newTotalQty
    ];

    $cartCount = 0;
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int)$item['quantity'];
    }

    Helper::json([
        'success' => true,
        'message' => 'เพิ่มสินค้าลงตะกร้าเรียบร้อยแล้ว',
        'cart_count' => $cartCount
    ]);
}

if ($action === 'update_cart') {
    Csrf::verifyOrDie();

    $sizeId = (int)($_POST['size_id'] ?? 0);
    $qty = (int)($_POST['quantity'] ?? 0);

    if ($sizeId <= 0) {
        Helper::json(['success' => false, 'error' => 'ข้อมูลไม่ถูกต้อง'], 400);
    }

    if ($qty <= 0) {
        unset($_SESSION['cart'][$sizeId]);
    } else {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT stock, is_unlimited, reserved_stock FROM product_sizes WHERE id = ?");
        $stmt->execute([$sizeId]);
        $size = $stmt->fetch();

        if ($size) {
            $available = $size['stock'] - $size['reserved_stock'];
            if (!$size['is_unlimited'] && $qty > $available) {
                Helper::json(['success' => false, 'error' => "สต็อกคงเหลือไม่เพียงพอ (เหลือ {$available} ตัว)"], 400);
            }
            if (isset($_SESSION['cart'][$sizeId])) {
                $_SESSION['cart'][$sizeId]['quantity'] = $qty;
            }
        }
    }

    $cartCount = 0;
    $subtotal = 0;
    foreach ($_SESSION['cart'] ?? [] as $item) {
        $cartCount += (int)$item['quantity'];
        $subtotal += ((float)$item['price'] * (int)$item['quantity']);
    }

    Helper::json([
        'success' => true,
        'cart_count' => $cartCount,
        'subtotal' => Helper::money($subtotal)
    ]);
}

if ($action === 'remove_from_cart') {
    Csrf::verifyOrDie();
    $sizeId = (int)($_POST['size_id'] ?? 0);
    if (isset($_SESSION['cart'][$sizeId])) {
        unset($_SESSION['cart'][$sizeId]);
    }

    $cartCount = 0;
    $subtotal = 0;
    foreach ($_SESSION['cart'] ?? [] as $item) {
        $cartCount += (int)$item['quantity'];
        $subtotal += ((float)$item['price'] * (int)$item['quantity']);
    }

    Helper::json([
        'success' => true,
        'cart_count' => $cartCount,
        'subtotal' => Helper::money($subtotal)
    ]);
}

if ($action === 'cancel_order') {
    Csrf::verifyOrDie();
    $orderCode = trim($_POST['order_code'] ?? '');
    if (empty($orderCode)) {
        Helper::json(['success' => false, 'error' => 'ไม่พบรหัสคำสั่งจอง'], 400);
    }

    // Ownership Verification: Must be the active session/cookie owner or an admin
    $storedOrderCode = $_SESSION['active_order_code'] ?? $_COOKIE['cmtc_active_order'] ?? '';
    $isAdmin = Auth::check();
    if (!$isAdmin && $storedOrderCode !== $orderCode) {
        Helper::json(['success' => false, 'error' => 'คุณไม่มีสิทธิ์ในการยกเลิกคำสั่งจองนี้'], 403);
    }

    $res = OrderManager::cancelOrder($orderCode);
    if ($res['success']) {
        Helper::json(['success' => true, 'message' => 'ยกเลิกคำสั่งจองและคืนสต็อกสินค้าเรียบร้อยแล้ว']);
    } else {
        Helper::json(['success' => false, 'error' => $res['error'] ?? 'ไม่สามารถยกเลิกคำสั่งจองได้'], 400);
    }
}

if ($action === 'update_order_address') {
    Csrf::verifyOrDie();
    $orderCode = trim($_POST['order_code'] ?? '');
    $shippingAddress = trim($_POST['shipping_address'] ?? '');
    $customerPhone = trim($_POST['customer_phone'] ?? '');

    if (empty($orderCode)) {
        Helper::json(['success' => false, 'error' => 'ไม่พบรหัสคำสั่งจอง'], 400);
    }
    if (empty($shippingAddress)) {
        Helper::json(['success' => false, 'error' => 'กรุณากรอกที่อยู่จัดส่งพัสดุ'], 400);
    }

    $pdo = Database::getInstance()->getConnection();
    $stmt = $pdo->prepare("SELECT id, status, customer_phone FROM orders WHERE order_code = ? LIMIT 1");
    $stmt->execute([$orderCode]);
    $order = $stmt->fetch();

    if (!$order) {
        Helper::json(['success' => false, 'error' => 'ไม่พบข้อมูลคำสั่งจองนี้'], 404);
    }

    // Ownership Verification: Must be the active session/cookie owner, matching phone, or an admin
    $storedOrderCode = $_SESSION['active_order_code'] ?? $_COOKIE['cmtc_active_order'] ?? '';
    $isAdmin = Auth::check();
    $cleanInputPhone = preg_replace('/[^0-9]/', '', $customerPhone);
    $cleanOrderPhone = preg_replace('/[^0-9]/', '', $order['customer_phone']);

    $isOwner = ($storedOrderCode === $orderCode) || (!empty($cleanInputPhone) && $cleanInputPhone === $cleanOrderPhone);
    if (!$isAdmin && !$isOwner) {
        Helper::json(['success' => false, 'error' => 'คุณไม่มีสิทธิ์แก้ไขข้อมูลที่อยู่ของคำสั่งจองนี้'], 403);
    }

    if (!in_array($order['status'], ['pending_payment', 'waiting_verification'])) {
        Helper::json(['success' => false, 'error' => 'ไม่สามารถแก้ไขที่อยู่ได้ในสถานะปัจจุบันของคำสั่งจอง'], 400);
    }

    $phoneToSave = !empty($customerPhone) ? $customerPhone : $order['customer_phone'];

    $updateStmt = $pdo->prepare("UPDATE orders SET shipping_address = ?, customer_phone = ?, updated_at = NOW() WHERE id = ?");
    $updateStmt->execute([$shippingAddress, $phoneToSave, $order['id']]);

    Helper::json([
        'success' => true,
        'message' => 'บันทึกที่อยู่จัดส่งเรียบร้อยแล้ว',
        'shipping_address' => $shippingAddress,
        'customer_phone' => $phoneToSave
    ]);
}

Helper::json(['error' => 'Invalid action'], 400);
