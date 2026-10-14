<?php
/**
 * Order & Inventory Management with Database Transactions and Row Locking
 * Prevents race conditions and guarantees stock consistency
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';

class OrderManager {

    /**
     * Calculate Shipping Cost based on system settings
     */
    public static function calculateShippingCost($deliveryMethod, $totalItems) {
        if ($deliveryMethod !== 'delivery' || $totalItems <= 0) {
            return 0.00;
        }

        $baseFee = (float)get_setting('shipping_base_fee', 50.00);
        $extraFee = (float)get_setting('shipping_extra_fee', 10.00);

        if ($totalItems <= 1) {
            return $baseFee;
        }

        return $baseFee + (($totalItems - 1) * $extraFee);
    }

    /**
     * Create Order with Stock Reservation inside an ACID Transaction
     *
     * @param array $customerData
     * @param array $cartItems [['size_id' => int, 'quantity' => int]]
     * @return array [success => bool, order_code => string, order_id => int, error => string]
     */
    public static function createOrder($customerData, $cartItems) {
        if (empty($cartItems)) {
            return ['success' => false, 'error' => 'ไม่มีสินค้าในตะกร้า'];
        }

        $pdo = Database::getInstance()->getConnection();

        try {
            $pdo->beginTransaction();

            $totalQuantity = 0;
            $subtotalAmount = 0.00;
            $itemsToInsert = [];

            // 1. Lock and Verify Stock for all items (FOR UPDATE)
            $sizeStmt = $pdo->prepare("
                SELECT ps.id, ps.product_id, ps.size_name, ps.price, ps.stock, ps.is_unlimited, ps.reserved_stock,
                       p.name AS product_name, p.is_active AS product_active, p.round_id
                FROM product_sizes ps
                JOIN products p ON ps.product_id = p.id
                WHERE ps.id = ? 
                FOR UPDATE
            ");

            foreach ($cartItems as $item) {
                $sizeId = (int)$item['size_id'];
                $qty = (int)$item['quantity'];

                if ($qty <= 0) continue;

                $sizeStmt->execute([$sizeId]);
                $sizeRow = $sizeStmt->fetch();

                if (!$sizeRow) {
                    throw new Exception("ไม่พบข้อมูลสินค้าขนาดที่ระบุ (ID: {$sizeId})");
                }

                if (!$sizeRow['product_active']) {
                    throw new Exception("สินค้า {$sizeRow['product_name']} ปิดรับการจองแล้ว");
                }

                // If not unlimited pre-order, check available inventory
                if (!$sizeRow['is_unlimited']) {
                    $availableStock = $sizeRow['stock'] - $sizeRow['reserved_stock'];
                    if ($availableStock < $qty) {
                        throw new Exception("สินค้า '{$sizeRow['product_name']} (ไซส์ {$sizeRow['size_name']})' มีสต็อกคงเหลือไม่พอ (เหลือ {$availableStock} ตัว แต่คุณเลือก {$qty} ตัว)");
                    }
                }

                $itemSubtotal = (float)$sizeRow['price'] * $qty;
                $subtotalAmount += $itemSubtotal;
                $totalQuantity += $qty;

                $itemsToInsert[] = [
                    'size_id' => $sizeRow['id'],
                    'product_id' => $sizeRow['product_id'],
                    'product_name' => $sizeRow['product_name'],
                    'size_name' => $sizeRow['size_name'],
                    'unit_price' => (float)$sizeRow['price'],
                    'quantity' => $qty,
                    'subtotal' => $itemSubtotal,
                    'round_id' => $sizeRow['round_id']
                ];
            }

            if (empty($itemsToInsert)) {
                throw new Exception("ไม่มีรายการสินค้าที่สามารถสั่งจองได้");
            }

            // 2. Reserve Stock
            $reserveStmt = $pdo->prepare("
                UPDATE product_sizes 
                SET reserved_stock = reserved_stock + ? 
                WHERE id = ?
            ");

            foreach ($itemsToInsert as $it) {
                $reserveStmt->execute([$it['quantity'], $it['size_id']]);
            }

            // 3. Calculate Shipping & Total
            $deliveryMethod = $customerData['delivery_method'] === 'delivery' ? 'delivery' : 'pickup';
            $shippingCost = self::calculateShippingCost($deliveryMethod, $totalQuantity);
            $totalAmount = $subtotalAmount + $shippingCost;

            // 4. Generate Unique Order Code with High Entropy & Collision Check
            $roundId = $itemsToInsert[0]['round_id'] ?: 1;
            do {
                $orderCode = 'CMTC-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
                $checkStmt = $pdo->prepare("SELECT 1 FROM orders WHERE order_code = ? LIMIT 1");
                $checkStmt->execute([$orderCode]);
            } while ($checkStmt->fetch());

            // 5. Expiration Date calculation
            $timeoutMinutes = (int)get_setting('cart_timeout_minutes', 30);
            if ($timeoutMinutes <= 0) $timeoutMinutes = 30;

            // 6. Insert Order
            $orderInsert = $pdo->prepare("
                INSERT INTO orders (
                    order_code, round_id, customer_name, customer_phone, customer_email, customer_organization,
                    delivery_method, shipping_address, shipping_cost, subtotal_amount, total_amount,
                    status, expires_at, notes
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    'pending_payment', DATE_ADD(NOW(), INTERVAL ? MINUTE), ?
                )
            ");

            $orderInsert->execute([
                $orderCode,
                $roundId,
                $customerData['customer_name'] ?? '',
                $customerData['customer_phone'] ?? '',
                $customerData['customer_email'] ?? '',
                $customerData['customer_organization'] ?? '',
                $deliveryMethod,
                $customerData['shipping_address'] ?? null,
                $shippingCost,
                $subtotalAmount,
                $totalAmount,
                $timeoutMinutes,
                $customerData['notes'] ?? null
            ]);

            $orderId = (int)$pdo->lastInsertId();

            // 7. Insert Order Items
            $itemInsert = $pdo->prepare("
                INSERT INTO order_items (
                    order_id, product_id, product_size_id, product_name, size_name, unit_price, quantity, subtotal
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($itemsToInsert as $it) {
                $itemInsert->execute([
                    $orderId,
                    $it['product_id'],
                    $it['size_id'],
                    $it['product_name'],
                    $it['size_name'],
                    $it['unit_price'],
                    $it['quantity'],
                    $it['subtotal']
                ]);
            }

            $pdo->commit();

            // Clear session cart
            if (isset($_SESSION['cart'])) {
                unset($_SESSION['cart']);
            }

            return [
                'success' => true,
                'order_id' => $orderId,
                'order_code' => $orderCode
            ];

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Auto Cleanup Expired Pending Orders and Release Reserved Stock
     * Can be invoked passively on page loads or explicitly via Cronjob
     */
    public static function cleanupExpiredOrders() {
        $pdo = Database::getInstance()->getConnection();

        try {
            // Find all pending orders that have exceeded their timeout
            $stmt = $pdo->query("
                SELECT id, order_code 
                FROM orders 
                WHERE status = 'pending_payment' AND expires_at < NOW()
            ");
            $expiredOrders = $stmt->fetchAll();

            if (empty($expiredOrders)) {
                return 0;
            }

            $count = 0;
            foreach ($expiredOrders as $order) {
                $pdo->beginTransaction();

                // Get items in order to restore reserved stock
                $itemsStmt = $pdo->prepare("SELECT product_size_id, quantity FROM order_items WHERE order_id = ?");
                $itemsStmt->execute([$order['id']]);
                $items = $itemsStmt->fetchAll();

                $restoreStmt = $pdo->prepare("
                    UPDATE product_sizes 
                    SET reserved_stock = GREATEST(0, reserved_stock - ?) 
                    WHERE id = ?
                ");

                foreach ($items as $item) {
                    $restoreStmt->execute([(int)$item['quantity'], (int)$item['product_size_id']]);
                }

                // Update order status
                $updateOrder = $pdo->prepare("
                    UPDATE orders 
                    SET status = 'cancelled', updated_at = NOW() 
                    WHERE id = ? AND status = 'pending_payment'
                ");
                $updateOrder->execute([$order['id']]);

                $pdo->commit();
                $count++;
            }

            return $count;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Order Cleanup Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Set Active Pending Order for Customer Session & Cookie
     *
     * @param string $orderCode
     */
    public static function setActiveOrder($orderCode) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['active_order_code'] = $orderCode;
        if (!headers_sent()) {
            setcookie('cmtc_active_order', $orderCode, [
                'expires' => time() + 86400,
                'path' => '/',
                'httponly' => false,
                'samesite' => 'Lax'
            ]);
        }
    }

    /**
     * Clear Active Pending Order from Session & Cookie
     */
    public static function clearActiveOrder() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        unset($_SESSION['active_order_code']);
        if (!headers_sent()) {
            setcookie('cmtc_active_order', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'httponly' => false,
                'samesite' => 'Lax'
            ]);
        }
    }

    /**
     * Get Current Active Pending Order for Customer (if any and not expired/cancelled/completed)
     *
     * @return array|null Order array or null
     */
    public static function getActivePendingOrder() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $code = $_SESSION['active_order_code'] ?? $_COOKIE['cmtc_active_order'] ?? null;
        if (empty($code)) return null;

        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_code = ? LIMIT 1");
        $stmt->execute([$code]);
        $order = $stmt->fetch();

        if (!$order) {
            self::clearActiveOrder();
            return null;
        }

        // If expired
        if ($order['status'] === 'pending_payment' && strtotime($order['expires_at']) < time()) {
            self::cleanupExpiredOrders();
            self::clearActiveOrder();
            return null;
        }

        // Only pending_payment orders require active return banner
        if ($order['status'] !== 'pending_payment') {
            self::clearActiveOrder();
            return null;
        }

        return $order;
    }

    /**
     * Customer or Admin Order Cancellation (Releases Reserved Stock immediately)
     *
     * @param string $orderCode
     * @return array ['success' => bool, 'error' => string]
     */
    public static function cancelOrder($orderCode) {
        $pdo = Database::getInstance()->getConnection();

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_code = ? FOR UPDATE");
            $stmt->execute([$orderCode]);
            $order = $stmt->fetch();

            if (!$order) {
                throw new Exception("ไม่พบคำสั่งจอง");
            }

            if ($order['status'] !== 'pending_payment') {
                throw new Exception("ไม่สามารถยกเลิกคำสั่งจองในสถานะปัจจุบันได้");
            }

            // Restore reserved stock
            $itemsStmt = $pdo->prepare("SELECT product_size_id, quantity FROM order_items WHERE order_id = ?");
            $itemsStmt->execute([$order['id']]);
            $items = $itemsStmt->fetchAll();

            $restoreStmt = $pdo->prepare("
                UPDATE product_sizes 
                SET reserved_stock = GREATEST(0, reserved_stock - ?) 
                WHERE id = ?
            ");

            foreach ($items as $item) {
                $restoreStmt->execute([(int)$item['quantity'], (int)$item['product_size_id']]);
            }

            // Update order status to cancelled
            $updateOrder = $pdo->prepare("
                UPDATE orders 
                SET status = 'cancelled', updated_at = NOW() 
                WHERE id = ?
            ");
            $updateOrder->execute([$order['id']]);

            $pdo->commit();
            self::clearActiveOrder();

            return ['success' => true];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }


    /**
     * Admin Verification for Payment Slip
     *
     * @param int $orderId
     * @param string $action 'approve' | 'reject'
     * @param int $adminId
     * @param string $reason
     */
    public static function verifyPayment($orderId, $action, $adminId, $reason = '') {
        $pdo = Database::getInstance()->getConnection();

        try {
            $pdo->beginTransaction();

            // Lock order
            $orderStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? FOR UPDATE");
            $orderStmt->execute([$orderId]);
            $order = $orderStmt->fetch();

            if (!$order) {
                throw new Exception("ไม่พบคำสั่งซื้อที่ระบุ");
            }

            // Get Items
            $itemsStmt = $pdo->prepare("SELECT product_size_id, quantity FROM order_items WHERE order_id = ?");
            $itemsStmt->execute([$orderId]);
            $items = $itemsStmt->fetchAll();

            if ($action === 'approve') {
                // Deduct actual stock (if not unlimited) and release reserved stock
                $stockUpdate = $pdo->prepare("
                    UPDATE product_sizes 
                    SET stock = CASE WHEN is_unlimited = 1 THEN stock ELSE GREATEST(0, stock - ?) END,
                        reserved_stock = GREATEST(0, reserved_stock - ?)
                    WHERE id = ?
                ");

                foreach ($items as $item) {
                    $stockUpdate->execute([(int)$item['quantity'], (int)$item['quantity'], (int)$item['product_size_id']]);
                }

                // Update order status
                $orderUp = $pdo->prepare("UPDATE orders SET status = 'confirmed', updated_at = NOW() WHERE id = ?");
                $orderUp->execute([$orderId]);

                // Update payment status
                $payUp = $pdo->prepare("
                    UPDATE payments 
                    SET status = 'approved', verified_by = ?, verified_at = NOW() 
                    WHERE order_id = ?
                ");
                $payUp->execute([$adminId, $orderId]);

            } elseif ($action === 'reject') {
                // Release reserved stock back to available pool
                $stockRestore = $pdo->prepare("
                    UPDATE product_sizes 
                    SET reserved_stock = GREATEST(0, reserved_stock - ?)
                    WHERE id = ?
                ");

                foreach ($items as $item) {
                    $stockRestore->execute([(int)$item['quantity'], (int)$item['product_size_id']]);
                }

                // Update order status
                $orderUp = $pdo->prepare("UPDATE orders SET status = 'rejected', updated_at = NOW() WHERE id = ?");
                $orderUp->execute([$orderId]);

                // Update payment status
                $payUp = $pdo->prepare("
                    UPDATE payments 
                    SET status = 'rejected', reject_reason = ?, verified_by = ?, verified_at = NOW() 
                    WHERE order_id = ?
                ");
                $payUp->execute([$reason, $adminId, $orderId]);
            } else {
                throw new Exception("การดำเนินการไม่ถูกต้อง");
            }

            $pdo->commit();
            return ['success' => true];

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
