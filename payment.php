<?php
require_once __DIR__ . '/config/settings.php';
require_once __DIR__ . '/core/Helper.php';
require_once __DIR__ . '/core/Csrf.php';
require_once __DIR__ . '/core/PromptPayQR.php';
require_once __DIR__ . '/core/OrderManager.php';

$code = trim($_GET['code'] ?? '');
if (empty($code)) {
    // If accessed without order code, automatically redirect to active pending order if customer has one
    $activeOrder = OrderManager::getActivePendingOrder();
    if ($activeOrder) {
        header("Location: " . base_url('payment.php?code=' . urlencode($activeOrder['order_code'])));
        exit;
    }
    header("Location: " . base_url('track.php'));
    exit;
}

$pdo = Database::getInstance()->getConnection();

// Fetch order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_code = ? LIMIT 1");
$stmt->execute([$code]);
$order = $stmt->fetch();

if (!$order) {
    Helper::setFlash('error', 'ไม่พบคำสั่งจอง', 'ไม่พบรหัสคำสั่งจอง ' . htmlspecialchars($code));
    header("Location: " . base_url('track.php'));
    exit;
}

// Fetch order items with product images and dimensions
$itemStmt = $pdo->prepare("
    SELECT oi.*, p.main_image, ps.chest_inch, ps.length_inch
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    LEFT JOIN product_sizes ps ON oi.product_size_id = ps.id
    WHERE oi.order_id = ?
");
$itemStmt->execute([$order['id']]);
$items = $itemStmt->fetchAll();

// Fetch payment info if already submitted
$payStmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1");
$payStmt->execute([$order['id']]);
$payment = $payStmt->fetch();

$promptpayNumber = get_setting('promptpay_number', '0812345678');
$promptpayName = get_setting('promptpay_name', 'วิทยาลัยเทคนิคเชียงใหม่ (CMTC Shop)');

// Generate EMVCo Dynamic Payload
$qrPayload = PromptPayQR::generatePayload($promptpayNumber, (float)$order['total_amount']);

$expiresTimestamp = strtotime($order['expires_at']);
$nowTimestamp = time();
$isExpired = ($order['status'] === 'pending_payment' && $expiresTimestamp < $nowTimestamp);

if ($isExpired && $order['status'] === 'pending_payment') {
    OrderManager::cleanupExpiredOrders();
    OrderManager::clearActiveOrder();
    // re-fetch
    $stmt->execute([$code]);
    $order = $stmt->fetch();
}

// Synchronize customer active order lifecycle:
// Customers stay bound to their active pending order until expired, cancelled, or slip submitted
if ($order['status'] === 'pending_payment' && !$isExpired) {
    OrderManager::setActiveOrder($order['order_code']);
} else {
    // If order is completed, waiting verification, or cancelled, clear active pending session/cookie
    $storedActive = $_SESSION['active_order_code'] ?? $_COOKIE['cmtc_active_order'] ?? '';
    if ($storedActive === $order['order_code']) {
        OrderManager::clearActiveOrder();
    }
}

$pageTitle = "ชำระเงินคำสั่งจอง {$order['order_code']} - CMTC Shop";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Flatpickr (Thai Calendar Picker) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>

<style>
/* Custom Flatpickr Thai Styling */
.flatpickr-calendar {
    font-family: inherit;
    border-radius: 1.25rem !important;
    box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1) !important;
    border: 1px solid #e2e8f0 !important;
    padding: 0.5rem !important;
    background: #ffffff !important;
    z-index: 99999 !important;
}
.flatpickr-months .flatpickr-month {
    border-top-left-radius: 1rem;
    border-top-right-radius: 1rem;
    background: #2563eb;
    color: #ffffff;
    fill: #ffffff;
    height: 38px;
}
.flatpickr-current-month {
    font-size: 105%;
    padding: 4px 0 0 0;
}
.flatpickr-current-month .flatpickr-monthDropdown-months {
    font-weight: 700;
}
.flatpickr-months .flatpickr-prev-month, .flatpickr-months .flatpickr-next-month {
    color: #ffffff;
    fill: #ffffff;
    top: 5px;
}
.flatpickr-months .flatpickr-prev-month:hover svg, .flatpickr-months .flatpickr-next-month:hover svg {
    fill: #bfdbfe;
}
.flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange {
    background: #2563eb !important;
    border-color: #2563eb !important;
    font-weight: 700;
    color: #ffffff !important;
}
.flatpickr-day:hover {
    background: #dbeafe !important;
}
.flatpickr-time {
    border-top: 1px solid #f1f5f9 !important;
    padding-top: 0.35rem;
}
.flatpickr-time input:hover, .flatpickr-time input:focus {
    background: #eff6ff !important;
}
</style>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
    <!-- Stepper Header -->
    <div class="max-w-2xl mx-auto mb-8 no-print">
        <div class="flex items-center justify-between">
            <div class="flex flex-col items-center flex-1 text-center">
                <div class="w-10 h-10 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold text-sm shadow-md mb-2">
                    <i class="fas fa-check"></i>
                </div>
                <span class="text-xs font-semibold text-emerald-600">ตรวจสอบตะกร้า</span>
            </div>
            <div class="h-1 bg-emerald-500 flex-1 mx-2"></div>
            <div class="flex flex-col items-center flex-1 text-center">
                <div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm shadow-md mb-2">
                    2
                </div>
                <span class="text-xs font-bold text-blue-600">ชำระเงินและสลิป</span>
            </div>
            <div class="h-1 bg-slate-200 flex-1 mx-2"></div>
            <div class="flex flex-col items-center flex-1 text-center">
                <div class="w-10 h-10 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center font-bold text-sm mb-2">
                    3
                </div>
                <span class="text-xs font-medium text-slate-400">รับใบยืนยัน</span>
            </div>
        </div>
    </div>

    <!-- Order Header Bar -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 mb-6 space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center space-x-3 mb-1">
                    <span class="text-xs font-mono font-bold text-slate-500">หมายเลขคำสั่งจอง:</span>
                    <span class="text-base font-black text-slate-900 tracking-wide"><?= htmlspecialchars($order['order_code']) ?></span>
                    <button type="button" onclick="App.copyText('<?= htmlspecialchars($order['order_code']) ?>', 'รหัสคำสั่งจอง')" class="text-slate-400 hover:text-blue-600 text-xs" title="คัดลอกรหัส">
                        <i class="far fa-copy"></i>
                    </button>
                </div>
                <div class="text-xs text-slate-500 flex flex-wrap items-center gap-3">
                    <span><i class="far fa-user text-slate-400 mr-1"></i> ผู้สั่งจอง: <strong><?= htmlspecialchars($order['customer_name']) ?></strong></span>
                    <?php if (!empty($order['customer_organization'])): ?>
                        <span class="inline-flex items-center text-blue-700 bg-blue-50 px-2 py-0.5 rounded-md font-semibold"><i class="fas fa-university text-blue-500 mr-1"></i> <?= htmlspecialchars($order['customer_organization']) ?></span>
                    <?php endif; ?>
                    <span><i class="fas fa-phone text-slate-400 mr-1"></i> <span id="displayCustomerPhone"><?= htmlspecialchars($order['customer_phone']) ?></span></span>
                    <span class="inline-flex items-center font-semibold <?= $order['delivery_method'] === 'delivery' ? 'text-indigo-600' : 'text-emerald-600' ?>">
                        <i class="fas <?= $order['delivery_method'] === 'delivery' ? 'fa-shipping-fast' : 'fa-hand-holding-box' ?> mr-1"></i>
                        <?= $order['delivery_method'] === 'delivery' ? 'จัดส่งพัสดุ' : 'รับด้วยตนเองที่วิทยาลัย' ?>
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center space-x-2">
                    <span class="text-xs text-slate-500 font-medium">สถานะ:</span>
                    <?= Helper::getStatusBadge($order['status']) ?>
                </div>
                <?php if ($order['status'] === 'pending_payment'): ?>
                    <button type="button" onclick="cancelCurrentOrder('<?= htmlspecialchars($order['order_code']) ?>')" class="inline-flex items-center px-3 py-1.5 rounded-xl border border-rose-200 bg-rose-50/50 hover:bg-rose-100 text-rose-600 hover:text-rose-700 text-xs font-bold transition shadow-sm" title="ยกเลิกการจองนี้และคืนสต็อกสินค้า">
                        <i class="fas fa-times-circle mr-1.5 text-rose-500"></i> ยกเลิกรายการสั่งจองนี้
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Customer Address Display & Edit Section -->
        <?php if ($order['delivery_method'] === 'delivery'): ?>
            <div class="pt-3.5 border-t border-slate-100 flex flex-col sm:flex-row sm:items-start justify-between gap-3 bg-blue-50/50 p-4 rounded-2xl border border-blue-100/80">
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-sm mt-0.5">
                        <i class="fas fa-map-marker-alt text-sm"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">ที่อยู่จัดส่งพัสดุของผู้จอง</span>
                            <span class="text-[10px] bg-blue-200 text-blue-800 font-semibold px-2 py-0.5 rounded-full">บริการจัดส่งพัสดุ</span>
                        </div>
                        <p id="displayShippingAddressText" class="text-xs sm:text-sm text-slate-700 font-medium mt-1 leading-relaxed whitespace-pre-line"><?= htmlspecialchars($order['shipping_address'] ?: 'ยังไม่ได้ระบุที่อยู่จัดส่ง') ?></p>
                    </div>
                </div>

                <?php if (in_array($order['status'], ['pending_payment', 'waiting_verification'])): ?>
                    <button type="button" onclick="openEditAddressModal()" class="inline-flex items-center justify-center px-3.5 py-2 rounded-xl bg-white hover:bg-blue-600 border border-blue-200 hover:border-blue-600 text-blue-700 hover:text-white font-bold text-xs shadow-sm hover:shadow transition active:scale-95 shrink-0">
                        <i class="fas fa-edit mr-1.5"></i> แก้ไขที่อยู่ผู้จอง
                    </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-emerald-50/40 p-3.5 rounded-2xl border border-emerald-100/80 text-xs text-slate-600">
                <div class="flex items-center space-x-2.5">
                    <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                        <i class="fas fa-store-alt text-xs"></i>
                    </div>
                    <div>
                        <span class="font-bold text-slate-800">สถานที่รับสินค้า:</span>
                        <span class="text-slate-700 ml-1"><?= htmlspecialchars(get_setting('pickup_location', 'วิทยาลัยเทคนิคเชียงใหม่ ตึกอำนวยการ แผนกวิชาช่างเทคนิคคอมพิวเตอร์ ชั้น 2')) ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Booked Items & Total Payment Summary Card (ด้านล่าง card หมายเลขคำสั่งจอง แต่อยู่ด้านบน PromptPay QR และ แนบสลิป) -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-7 mb-8">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg shadow-xs">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-black text-slate-900 leading-tight">
                        รายละเอียดสินค้าที่จอง
                    </h3>
                    <p class="text-[11px] text-slate-400">รายการสินค้า ขนาด และยอดเงินที่ต้องชำระทั้งหมด</p>
                </div>
            </div>
            <span class="inline-flex items-center text-xs font-bold text-slate-600 bg-slate-100 px-3 py-1.5 rounded-full">
                รวม <?= count($items) ?> รายการ (<?= array_sum(array_column($items, 'quantity')) ?> ชิ้น/ตัว)
            </span>
        </div>

        <!-- Items List -->
        <div class="divide-y divide-slate-100">
            <?php foreach ($items as $item): ?>
                <div class="py-4 first:pt-0 last:pb-0 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-200/80 p-1 flex items-center justify-center shrink-0 overflow-hidden shadow-xs">
                            <?php if (!empty($item['main_image'])): ?>
                                <img src="<?= base_url($item['main_image']) ?>" class="w-full h-full object-contain object-center">
                            <?php else: ?>
                                <i class="fas fa-tshirt text-slate-300 text-2xl"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h4 class="font-bold text-sm text-slate-900 leading-snug">
                                <?= htmlspecialchars($item['product_name']) ?>
                            </h4>
                            <div class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-2">
                                <span>ขนาด / ไซส์: <strong class="text-blue-600 font-bold"><?= htmlspecialchars($item['size_name']) ?></strong></span>
                                <?php if (!empty($item['chest_inch']) && (float)$item['chest_inch'] > 0): ?>
                                    <span class="text-slate-400 font-normal">(อก <?= $item['chest_inch'] ?>" ยาว <?= $item['length_inch'] ?>")</span>
                                <?php endif; ?>
                                <span class="text-slate-300">&bull;</span>
                                <span class="text-slate-600">฿<?= Helper::money($item['unit_price']) ?> &times; <?= $item['quantity'] ?> ตัว</span>
                            </div>
                        </div>
                    </div>

                    <div class="sm:text-right shrink-0">
                        <span class="text-[11px] text-slate-400 block sm:hidden">ยอดรวมย่อย</span>
                        <span class="font-extrabold text-base text-slate-900">฿<?= Helper::money($item['subtotal']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Payment Breakdown & Grand Total Bar -->
        <div class="mt-6 pt-5 border-t border-slate-100 bg-slate-50/70 -mx-6 -mb-6 sm:-mx-7 sm:-mb-7 p-6 sm:p-7 rounded-b-3xl">
            <div class="max-w-md ml-auto space-y-2.5 text-xs">
                <div class="flex justify-between items-center text-slate-600">
                    <span>ยอดรวมค่าสินค้า (<?= array_sum(array_column($items, 'quantity')) ?> ชิ้น/ตัว):</span>
                    <span class="font-bold text-slate-900">฿<?= Helper::money($order['subtotal_amount']) ?></span>
                </div>

                <div class="flex justify-between items-center text-slate-600">
                    <span>ค่าจัดส่ง:</span>
                    <?php if ((float)$order['shipping_cost'] > 0): ?>
                        <span class="font-bold text-slate-900">฿<?= Helper::money($order['shipping_cost']) ?> <span class="text-[11px] text-indigo-600 font-normal">(จัดส่งพัสดุ)</span></span>
                    <?php else: ?>
                        <span class="font-bold text-emerald-600">ฟรี / ฿0.00 <span class="text-[11px] text-slate-400 font-normal">(<?= $order['delivery_method'] === 'delivery' ? 'จัดส่งฟรี' : 'รับด้วยตนเอง' ?>)</span></span>
                    <?php endif; ?>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-slate-200">
                    <div>
                        <span class="font-black text-slate-900 text-sm sm:text-base">รวมยอดเงินที่ต้องชำระ:</span>
                        <span class="block text-[11px] text-slate-400">ราคาสุทธิรวมค่าจัดส่งเรียบร้อยแล้ว</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-xl sm:text-2xl font-black text-blue-600 tracking-tight">
                            ฿<?= Helper::money($order['total_amount']) ?>
                        </span>
                        <button type="button" onclick="App.copyText('<?= number_format((float)$order['total_amount'], 2, '.', '') ?>', 'ยอดเงิน')" class="text-slate-400 hover:text-blue-600 text-xs p-1.5 rounded-lg hover:bg-blue-50 transition" title="คัดลอกยอดเงิน">
                            <i class="far fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($order['status'] === 'cancelled'): ?>
        <!-- Cancelled / Expired Alert -->
        <div class="bg-rose-50 border border-rose-200 rounded-3xl p-8 text-center">
            <div class="w-16 h-16 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="fas fa-times-circle"></i>
            </div>
            <h2 class="text-xl font-bold text-rose-900 mb-2">คำสั่งจองนี้ถูกยกเลิกแล้ว (หมดเวลาชำระเงิน)</h2>
            <p class="text-xs text-rose-700 max-w-md mx-auto mb-6">
                เนื่องจากไม่มีการแนบหลักฐานการโอนเงินภายในเวลาที่กำหนด ระบบจึงได้ปล่อยสต็อกคืนสู่ระบบ หากท่านยังต้องการสินค้า กรุณากดทำรายการสั่งจองใหม่อีกครั้ง
            </p>
            <a href="<?= base_url() ?>" class="inline-flex items-center px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md transition">
                <i class="fas fa-redo mr-2"></i> สั่งจองใหม่อีกครั้ง
            </a>
        </div>

    <?php elseif ($order['status'] === 'confirmed'): ?>
        <!-- Confirmed Alert with Print Button -->
        <div class="bg-emerald-50 border border-emerald-200 rounded-3xl p-8 text-center">
            <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2 class="text-xl font-bold text-emerald-900 mb-2">ยืนยันคำสั่งจองและชำระเงินเรียบร้อยแล้ว!</h2>
            <p class="text-xs text-emerald-700 max-w-md mx-auto mb-6">
                เจ้าหน้าที่ได้ตรวจสอบยอดเงินและยืนยันการจองของท่านแล้ว ท่านสามารถพิมพ์ใบรับสินค้าเพื่อนำมาแสดงในวันรับเสื้อ
            </p>
            <a href="<?= base_url('voucher.php?code=' . urlencode($order['order_code'])) ?>" class="inline-flex items-center px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md transition">
                <i class="fas fa-print mr-2"></i> พิมพ์ใบรับสินค้า (Pickup Voucher)
            </a>
        </div>

    <?php elseif ($order['status'] === 'waiting_verification'): ?>
        <!-- Waiting Verification Box -->
        <div class="bg-blue-50 border border-blue-200 rounded-3xl p-8 text-center">
            <div class="w-16 h-16 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="fas fa-receipt"></i>
            </div>
            <h2 class="text-xl font-bold text-blue-900 mb-2">ได้รับหลักฐานการโอนเงินแล้ว</h2>
            <p class="text-xs text-blue-700 max-w-md mx-auto mb-6 leading-relaxed">
                ระบบได้รับสลิปการโอนเงินของคุณแล้ว เจ้าหน้าที่จะดำเนินการตรวจสอบยอดและเวลาโอนเงินตามลำดับ ท่านสามารถเข้ามาตรวจสอบสถานะได้ตลอดเวลาผ่านหน้าติดตามสถานะ
            </p>

            <?php if ($payment && !empty($payment['slip_image'])): ?>
                <div class="max-w-xs mx-auto mb-6 bg-white p-3 rounded-2xl shadow-sm border border-blue-100">
                    <span class="block text-[11px] font-bold text-slate-500 mb-2">หลักฐานที่แนบไว้:</span>
                    <img src="<?= base_url($payment['slip_image']) ?>" class="w-full rounded-xl border border-slate-200">
                    <span class="block text-[10px] text-slate-400 mt-2">
                        เวลาที่แจ้ง: <?= Helper::thaiDate($payment['transfer_datetime']) ?>
                    </span>
                </div>
            <?php endif; ?>

            <a href="<?= base_url('track.php?search=' . urlencode($order['customer_phone'])) ?>" class="inline-flex items-center px-6 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 font-bold text-xs transition">
                <i class="fas fa-search mr-2 text-slate-400"></i> ตรวจสอบสถานะการจอง
            </a>
        </div>

    <?php else: ?>
        <!-- Pending Payment: Show PromptPay QR & Slip Form -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Left: Dynamic PromptPay QR Code Box -->
            <div class="lg:col-span-6 bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8 text-center flex flex-col justify-between">
                <div>
                    <!-- Countdown Timer -->
                    <div class="inline-flex items-center space-x-2 bg-amber-50 border border-amber-200 px-4 py-1.5 rounded-full text-xs font-semibold text-amber-800 mb-6">
                        <i class="fas fa-stopwatch text-amber-600 animate-spin"></i>
                        <span>เวลาชำระเงินคงเหลือ:</span>
                        <span id="countdownTimer">--:--</span>
                    </div>

                    <h2 class="text-xl font-black text-slate-900 mb-1">สแกนจ่ายผ่าน PromptPay QR</h2>
                    <p class="text-xs text-slate-500 mb-6">สแกนด้วยแอปพลิเคชันธนาคารทุกแห่ง ยอดเงินระบุอัตโนมัติ</p>

                    <!-- PromptPay QR Code Canvas Container -->
                    <div class="qr-frame mx-auto mb-6">
                        <div class="flex items-center justify-center mb-2">
                            <span class="text-xs font-black tracking-widest text-blue-900 uppercase">
                                THAI QR PAYMENT
                            </span>
                        </div>
                        <div id="promptpayQrCanvas" class="flex justify-center my-1"></div>
                        <div class="mt-2 text-[10px] text-slate-400 font-mono">
                            PROMPTPAY OFFICIAL
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 text-xs space-y-2 mb-6">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">ชื่อบัญชี:</span>
                            <span class="font-bold text-slate-900"><?= htmlspecialchars($promptpayName) ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">หมายเลขพร้อมเพย์:</span>
                            <div class="flex items-center space-x-1.5 font-mono font-bold text-slate-900">
                                <span><?= PromptPayQR::formatDisplayNumber($promptpayNumber) ?></span>
                                <button type="button" onclick="App.copyText('<?= $promptpayNumber ?>', 'หมายเลขพร้อมเพย์')" class="text-blue-600 hover:text-blue-800 text-[11px]" title="คัดลอกเบอร์">
                                    <i class="far fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t border-slate-200 text-sm">
                            <span class="font-bold text-slate-700">ยอดเงินที่ต้องชำระ:</span>
                            <div class="flex items-center space-x-1.5 font-black text-blue-600 text-lg">
                                <span>฿<?= Helper::money($order['total_amount']) ?></span>
                                <button type="button" onclick="App.copyText('<?= number_format((float)$order['total_amount'], 2, '.', '') ?>', 'ยอดเงิน')" class="text-blue-600 hover:text-blue-800 text-xs" title="คัดลอกยอดเงิน">
                                    <i class="far fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-[11px] text-slate-400">
                    <i class="fas fa-info-circle mr-1"></i> เมื่อโอนเงินเสร็จแล้ว กรุณาแนบภาพสลิปที่ฟอร์มด้านขวา
                </div>
            </div>

            <!-- Right: Slip Upload Form & Order Items Breakdown -->
            <div class="lg:col-span-6 space-y-6">
                <!-- Slip Upload Form -->
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
                    <h3 class="text-lg font-black text-slate-900 mb-2 flex items-center">
                        <i class="fas fa-upload text-blue-600 mr-2.5"></i> แนบหลักฐานการโอนเงิน (Slip)
                    </h3>
                    <p class="text-xs text-slate-500 mb-6">
                        อัปโหลดรูปภาพสลิปเพื่อยืนยันรายการจอง
                    </p>

                    <form action="<?= base_url('upload_slip_process.php') ?>" method="POST" enctype="multipart/form-data" id="slipUploadForm">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="order_code" value="<?= htmlspecialchars($order['order_code']) ?>">

                        <div class="space-y-4">
                            <!-- File Upload Area (Fully Optimized for Mobile Phones & Desktop) -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    เลือกไฟล์ภาพสลิป <span class="text-rose-500">*</span>
                                </label>
                                
                                <!-- Native Hidden File Input with accept="image/*" for Camera & Photo Library on Mobile -->
                                <input type="file" 
                                       name="slip_file" 
                                       id="slipFileInput" 
                                       accept="image/*" 
                                       required 
                                       class="hidden" 
                                       onchange="handleSlipFileSelect(this)">

                                <!-- Upload Touch Zone (Supports Mobile Tap & Desktop Click) -->
                                <div id="slipUploadPrompt" 
                                     onclick="triggerSlipFileInput()" 
                                     role="button"
                                     tabindex="0"
                                     class="border-2 border-dashed border-slate-300 hover:border-blue-500 active:border-blue-600 rounded-2xl p-6 flex flex-col items-center justify-center cursor-pointer transition bg-slate-50/70 hover:bg-blue-50/20 active:bg-blue-50/40 select-none">
                                    <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl mb-3 shadow-xs">
                                        <i class="fas fa-camera"></i>
                                    </div>
                                    <span class="text-sm font-bold text-slate-800 text-center">แตะเพื่อเลือกรูปภาพสลิป หรือ ถ่ายภาพ</span>
                                    <span class="text-xs text-slate-500 mt-1 text-center">รองรับรูปภาพ JPG, PNG, WEBP (ไม่เกิน 5MB)</span>
                                    
                                    <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                                        <button type="button" 
                                                onclick="event.stopPropagation(); triggerSlipFileInput();" 
                                                class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:scale-95 text-white font-bold text-xs shadow-sm transition">
                                            <i class="fas fa-images mr-1.5"></i> เลือกรูปภาพ / ถ่ายภาพ
                                        </button>
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-2 flex items-center">
                                        <i class="fas fa-mobile-alt mr-1 text-blue-500"></i> รองรับการเปิดกล้องและคลังรูปภาพบนโทรศัพท์มือถือ
                                    </div>
                                </div>

                                <!-- Live Preview Container (Mobile & Desktop Friendly) -->
                                <div id="slipPreviewContainer" class="hidden mt-3 p-4 bg-slate-50 border border-slate-200 rounded-2xl">
                                    <div class="flex items-center space-x-3.5">
                                        <div class="w-16 h-20 bg-slate-200 rounded-xl overflow-hidden border border-slate-300 flex-shrink-0 cursor-pointer shadow-xs" onclick="previewSlipModal()" title="แตะเพื่อดูภาพขนาดใหญ่">
                                            <img id="slipPreviewImg" class="w-full h-full object-cover" alt="สลิปที่เลือก">
                                        </div>
                                        <div class="flex-1 min-w-0 text-xs">
                                            <div class="flex items-center space-x-1.5 text-emerald-600 font-bold mb-0.5">
                                                <i class="fas fa-check-circle"></i>
                                                <span>เลือกรูปสลิปเรียบร้อยแล้ว</span>
                                            </div>
                                            <p id="slipFileNameText" class="text-slate-700 font-semibold truncate text-xs mb-0.5">slip.jpg</p>
                                            <p id="slipFileSizeText" class="text-[11px] text-slate-400 mb-2">0 KB</p>

                                            <div class="flex items-center gap-2">
                                                <button type="button" 
                                                        onclick="triggerSlipFileInput()" 
                                                        class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-[11px] transition active:scale-95">
                                                    <i class="fas fa-sync-alt mr-1"></i> เปลี่ยนรูป
                                                </button>
                                                <button type="button" 
                                                        onclick="removeSlipFile()" 
                                                        class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-[11px] transition active:scale-95">
                                                    <i class="fas fa-trash-alt mr-1"></i> ลบรูป
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Transfer Datetime (Thai Calendar Picker) -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    วันและเวลาที่โอนเงินในสลิป <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <input type="text" 
                                           name="transfer_datetime" 
                                           id="transferDatetimeInput" 
                                           required 
                                           placeholder="เลือกวันและเวลาที่โอนเงินตามสลิป"
                                           class="w-full px-4 py-3 pl-10 rounded-xl border border-slate-200 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 text-sm text-slate-900 transition bg-white cursor-pointer font-medium">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-blue-600 text-sm">
                                        <i class="far fa-calendar-alt"></i>
                                    </div>
                                </div>
                                <div class="mt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                                    <span id="thaiFormattedDateBadge" class="text-blue-700 bg-blue-50/90 px-2.5 py-1.5 rounded-lg border border-blue-100 font-medium inline-flex items-center">
                                        <i class="fas fa-calendar-check text-blue-600 mr-1.5"></i> กำลังโหลดปฏิทินไทย...
                                    </span>
                                    <button type="button" 
                                            onclick="setDatetimeNow()" 
                                            class="inline-flex items-center px-3 py-1.5 rounded-xl bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-700 hover:text-blue-800 font-bold text-xs transition active:scale-95 shadow-2xs self-start sm:self-auto cursor-pointer">
                                        <i class="fas fa-clock text-blue-600 mr-1.5"></i>
                                        <span>ใช้วันที่และเวลาปัจจุบัน</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Customer Notes -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    ข้อความเพิ่มเติม (ถ้ามี)
                                </label>
                                <input type="text" name="customer_note" placeholder="เช่น โอนจากธนาคารกสิกรไทย / บัญชีนายสมชาย" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 text-sm text-slate-900 transition">
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" id="btnSubmitSlip" class="w-full py-4 px-6 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-md hover:shadow-lg transition flex items-center justify-center active:scale-[0.98]">
                                <i class="fas fa-check-circle mr-2 text-base"></i>
                                <span>ยืนยันการแนบสลิปและส่งข้อมูล</span>
                            </button>
                        </div>
                    </form>
                </div>


            </div>
        </div>
    <?php endif; ?>
</div>

<script>
let flatpickrInstance = null;

document.addEventListener('DOMContentLoaded', function() {
    <?php if ($order['status'] === 'pending_payment'): ?>
        // 1. Render PromptPay QR Code
        const qrPayload = <?= json_encode($qrPayload) ?>;
        App.renderQR('promptpayQrCanvas', qrPayload, 220);

        // 2. Countdown Timer
        const expireTime = <?= $expiresTimestamp ?>;
        App.initCountdown(expireTime, 'countdownTimer', '<?= base_url("payment.php?code=" . urlencode($order["order_code"])) ?>');

        // 3. Initialize Thai Flatpickr Calendar
        const dateInput = document.getElementById('transferDatetimeInput');
        if (dateInput && typeof flatpickr !== 'undefined') {
            flatpickrInstance = flatpickr(dateInput, {
                locale: "th",
                enableTime: true,
                time_24hr: true,
                dateFormat: "Y-m-d H:i:S",
                altInput: true,
                altInputClass: "w-full px-4 py-3 pl-10 rounded-xl border border-slate-200 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 text-sm text-slate-900 transition bg-white cursor-pointer font-medium",
                altFormat: "j F Y เวลา H:i น.",
                defaultDate: new Date(),
                maxDate: new Date(new Date().setHours(23, 59, 59, 999)),
                disableMobile: true, // Force Flatpickr UI on mobile devices so Thai calendar is rendered
                formatDate: function(date, format, locale) {
                    if (format === "Y-m-d H:i:S") {
                        const pad = (n) => String(n).padStart(2, '0');
                        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
                    }
                    const thaiMonths = [
                        "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
                        "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
                    ];
                    const day = date.getDate();
                    const month = thaiMonths[date.getMonth()];
                    const yearBe = date.getFullYear() + 543;
                    const hours = String(date.getHours()).padStart(2, '0');
                    const minutes = String(date.getMinutes()).padStart(2, '0');
                    return `${day} ${month} พ.ศ. ${yearBe} เวลา ${hours}:${minutes} น.`;
                },
                onReady: function(selectedDates, dateStr, instance) {
                    updateCalendarBuddhistYear(instance);
                    updateThaiDateBadge(selectedDates[0] || new Date());
                },
                onOpen: function(selectedDates, dateStr, instance) {
                    updateCalendarBuddhistYear(instance);
                },
                onMonthChange: function(selectedDates, dateStr, instance) {
                    updateCalendarBuddhistYear(instance);
                },
                onYearChange: function(selectedDates, dateStr, instance) {
                    updateCalendarBuddhistYear(instance);
                },
                onValueUpdate: function(selectedDates, dateStr, instance) {
                    updateCalendarBuddhistYear(instance);
                },
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates[0]) {
                        updateThaiDateBadge(selectedDates[0]);
                    }
                    updateCalendarBuddhistYear(instance);
                }
            });
        }

        // 4. Submit Slip Loading State & Verification
        const slipForm = document.getElementById('slipUploadForm');
        if (slipForm) {
            slipForm.addEventListener('submit', function(e) {
                const fileInput = document.getElementById('slipFileInput');
                if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'กรุณาแนบภาพสลิป',
                        text: 'โปรดเลือกรูปภาพสลิปการโอนเงินก่อนกดยืนยัน',
                        confirmButtonColor: '#2563eb'
                    });
                    return;
                }

                const btn = document.getElementById('btnSubmitSlip');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> กำลังส่งข้อมูลสลิป...';
                }
            });
        }
    <?php endif; ?>
});

function updateCalendarBuddhistYear(fp) {
    if (!fp || !fp.calendarContainer) return;
    
    // Update year inputs in calendar header to Buddhist Era (พ.ศ. = ค.ศ. + 543)
    if (fp.yearElements) {
        fp.yearElements.forEach(el => {
            if (el) {
                el.value = (fp.currentYear + 543).toString();
                el.readOnly = true;
            }
        });
    }

    // Add "พ.ศ." prefix label before the year in the header if not already present
    const yearWrappers = fp.calendarContainer.querySelectorAll('.numInputWrapper');
    yearWrappers.forEach(w => {
        if (!w.querySelector('.be-year-label') && !w.previousElementSibling?.classList.contains('be-year-label')) {
            const label = document.createElement('span');
            label.className = 'be-year-label text-xs font-bold text-white mr-1 inline-block select-none';
            label.textContent = 'พ.ศ. ';
            w.parentNode.insertBefore(label, w);
        }
    });
}

function updateThaiDateBadge(date) {
    if (!date) return;
    const thaiMonths = [
        "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
        "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
    ];
    const thaiDays = ["อาทิตย์", "จันทร์", "อังคาร", "พุธ", "พฤหัสบดี", "ศุกร์", "เสาร์"];
    const dayName = thaiDays[date.getDay()];
    const day = date.getDate();
    const month = thaiMonths[date.getMonth()];
    const yearBe = date.getFullYear() + 543;
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    const badge = document.getElementById('thaiFormattedDateBadge');
    if (badge) {
        badge.innerHTML = `<i class="fas fa-calendar-check text-blue-600 mr-1.5"></i> วัน${dayName}ที่ ${day} ${month} พ.ศ. ${yearBe} เวลา ${hours}:${minutes} น.`;
    }
}

function setDatetimeNow() {
    const now = new Date();
    if (flatpickrInstance) {
        flatpickrInstance.set('maxDate', new Date(new Date().setHours(23, 59, 59, 999)));
        flatpickrInstance.setDate(now, true);
        updateCalendarBuddhistYear(flatpickrInstance);
        if (flatpickrInstance.altInput) {
            flatpickrInstance.altInput.classList.add('ring-2', 'ring-emerald-400', 'border-emerald-500');
            setTimeout(() => {
                flatpickrInstance.altInput.classList.remove('ring-2', 'ring-emerald-400', 'border-emerald-500');
            }, 1200);
        }
    } else {
        const input = document.getElementById('transferDatetimeInput');
        if (input) {
            const pad = (n) => String(n).padStart(2, '0');
            input.value = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())} ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
        }
    }
    updateThaiDateBadge(now);

    if (typeof App !== 'undefined' && App.toast) {
        App.toast('success', 'ป้อนวันและเวลาปัจจุบันเรียบร้อยแล้ว');
    }
}

function triggerSlipFileInput() {
    const input = document.getElementById('slipFileInput');
    if (input) {
        input.click();
    }
}

function handleSlipFileSelect(input) {
    const file = input.files && input.files[0];
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
        Swal.fire({
            icon: 'warning',
            title: 'ขนาดไฟล์เกินกำหนด',
            text: 'กรุณาเลือกไฟล์ภาพสลิปขนาดไม่เกิน 5 MB',
            confirmButtonColor: '#2563eb'
        });
        input.value = '';
        return;
    }

    const fileNameEl = document.getElementById('slipFileNameText');
    const fileSizeEl = document.getElementById('slipFileSizeText');
    if (fileNameEl) fileNameEl.textContent = file.name;
    if (fileSizeEl) {
        const sizeKb = (file.size / 1024).toFixed(1);
        const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
        fileSizeEl.textContent = (file.size > 1024 * 1024) ? `${sizeMb} MB` : `${sizeKb} KB`;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        const previewImg = document.getElementById('slipPreviewImg');
        if (previewImg) previewImg.src = e.target.result;
        
        const previewContainer = document.getElementById('slipPreviewContainer');
        if (previewContainer) previewContainer.classList.remove('hidden');

        const uploadPrompt = document.getElementById('slipUploadPrompt');
        if (uploadPrompt) uploadPrompt.classList.add('hidden');
    };
    reader.readAsDataURL(file);
}

function removeSlipFile() {
    const input = document.getElementById('slipFileInput');
    if (input) input.value = '';

    const previewContainer = document.getElementById('slipPreviewContainer');
    if (previewContainer) previewContainer.classList.add('hidden');

    const uploadPrompt = document.getElementById('slipUploadPrompt');
    if (uploadPrompt) uploadPrompt.classList.remove('hidden');
}

function previewSlipModal() {
    const previewImg = document.getElementById('slipPreviewImg');
    if (!previewImg || !previewImg.src) return;
    Swal.fire({
        imageUrl: previewImg.src,
        imageAlt: 'ภาพสลิป',
        showConfirmButton: false,
        showCloseButton: true,
        background: '#0f172a',
        padding: '0.75rem'
    });
}

function cancelCurrentOrder(orderCode) {
    Swal.fire({
        title: 'ยืนยันการยกเลิกคำสั่งจอง?',
        text: 'หากยกเลิก สต็อกสินค้าที่จองไว้จะถูกคืนกลับสู่ระบบทันที',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'ใช่, ขอยกเลิกคำสั่งจอง',
        cancelButtonText: 'กลับ'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'กำลังยกเลิกคำสั่งจอง...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const formData = new FormData();
            formData.append('csrf_token', <?= json_encode(Csrf::token()) ?>);
            formData.append('order_code', orderCode);

            fetch('<?= base_url("api.php?action=cancel_order") ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'ยกเลิกคำสั่งจองแล้ว',
                        text: data.message,
                        confirmButtonColor: '#2563eb'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', data.error || 'ไม่สามารถยกเลิกคำสั่งจองได้', 'error');
                }
            })
            .catch(() => {
                Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            });
        }
    });
}

function openEditAddressModal() {
    const currentAddressEl = document.getElementById('displayShippingAddressText');
    const currentAddress = currentAddressEl ? currentAddressEl.innerText.trim() : '';
    const currentPhoneEl = document.getElementById('displayCustomerPhone');
    const currentPhone = currentPhoneEl ? currentPhoneEl.innerText.trim() : '<?= htmlspecialchars($order['customer_phone']) ?>';
    const defaultAddr = (currentAddress === 'ยังไม่ได้ระบุที่อยู่จัดส่ง') ? '' : currentAddress;

    Swal.fire({
        title: '<div class="text-lg font-black text-slate-900 flex items-center justify-center"><i class="fas fa-map-marked-alt text-blue-600 mr-2"></i> แก้ไขที่อยู่จัดส่งพัสดุ</div>',
        html: `
            <div class="text-left space-y-3 mt-2 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">
                        ที่อยู่จัดส่งพัสดุ (บ้านเลขที่, ถนน, ซอย, ตำบล/แขวง, อำเภอ/เขต, จังหวัด, รหัสไปรษณีย์) <span class="text-rose-500">*</span>
                    </label>
                    <textarea id="swalShippingAddress" rows="4" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 text-sm text-slate-900 transition" placeholder="กรอกที่อยู่จัดส่งพัสดุให้ครบถ้วนและถูกต้อง...">${defaultAddr.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</textarea>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">
                        เบอร์โทรศัพท์สำหรับติดต่อส่งของ <span class="text-rose-500">*</span>
                    </label>
                    <input type="tel" id="swalCustomerPhone" value="${currentPhone.replace(/"/g, '&quot;')}" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 text-sm text-slate-900 transition" placeholder="08xxxxxxxx" maxlength="20">
                </div>
                <div class="p-2.5 bg-blue-50 border border-blue-100 rounded-xl text-[11px] text-blue-700 leading-relaxed">
                    <i class="fas fa-info-circle mr-1"></i> เจ้าหน้าที่จะจัดส่งพัสดุตามที่อยู่ที่ระบุนี้ โปรดตรวจสอบความถูกต้อง
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-check-circle mr-1.5"></i> บันทึกที่อยู่ใหม่',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#64748b',
        focusConfirm: false,
        preConfirm: () => {
            const addr = document.getElementById('swalShippingAddress').value.trim();
            const phone = document.getElementById('swalCustomerPhone').value.trim();
            if (!addr) {
                Swal.showValidationMessage('กรุณากรอกที่อยู่จัดส่งพัสดุ');
                return false;
            }
            if (!phone) {
                Swal.showValidationMessage('กรุณาระบุเบอร์โทรศัพท์สำหรับติดต่อ');
                return false;
            }
            return { address: addr, phone: phone };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'กำลังบันทึกข้อมูล...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const formData = new FormData();
            formData.append('csrf_token', <?= json_encode(Csrf::token()) ?>);
            formData.append('order_code', <?= json_encode($order['order_code']) ?>);
            formData.append('shipping_address', result.value.address);
            formData.append('customer_phone', result.value.phone);

            fetch('<?= base_url("api.php?action=update_order_address") ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const addrEl = document.getElementById('displayShippingAddressText');
                    if (addrEl) addrEl.textContent = data.shipping_address;
                    const phoneEl = document.getElementById('displayCustomerPhone');
                    if (phoneEl) phoneEl.textContent = data.customer_phone;

                    Swal.fire({
                        icon: 'success',
                        title: 'บันทึกเรียบร้อย',
                        text: data.message,
                        confirmButtonColor: '#2563eb',
                        timer: 2000
                    });
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', data.error || 'ไม่สามารถบันทึกที่อยู่ได้', 'error');
                }
            })
            .catch(() => {
                Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            });
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
