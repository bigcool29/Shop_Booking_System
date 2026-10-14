<?php
$pageTitle = "ตรวจสอบและติดตามสถานะคำสั่งจอง - CMTC Shop";
require_once __DIR__ . '/includes/header.php';

$search = trim($_GET['search'] ?? '');
$orders = [];

if (!empty($search)) {
    $pdo = Database::getInstance()->getConnection();
    
    // Check if searching by phone or order code
    $cleanPhone = preg_replace('/[^0-9]/', '', $search);
    
    if (strlen($cleanPhone) >= 9) {
        $stmt = $pdo->prepare("
            SELECT o.*, r.round_name,
                   (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count,
                   (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) AS total_pieces
            FROM orders o
            LEFT JOIN booking_rounds r ON o.round_id = r.id
            WHERE o.customer_phone = ? OR o.order_code = ?
            ORDER BY o.id DESC
        ");
        $stmt->execute([$cleanPhone, $search]);
    } else {
        $stmt = $pdo->prepare("
            SELECT o.*, r.round_name,
                   (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count,
                   (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) AS total_pieces
            FROM orders o
            LEFT JOIN booking_rounds r ON o.round_id = r.id
            WHERE o.order_code = ?
            ORDER BY o.id DESC
        ");
        $stmt->execute([$search]);
    }
    $orders = $stmt->fetchAll();
}
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
    <!-- Header Title -->
    <div class="text-center max-w-xl mx-auto mb-8">
        <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-3 text-lg font-bold">
            <i class="fas fa-search"></i>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mb-2">
            ตรวจสอบและติดตามสถานะคำสั่งจอง
        </h1>
        <p class="text-xs sm:text-sm text-slate-500">
            ค้นหาด้วย <strong>หมายเลขคำสั่งจอง</strong> (เช่น CMTC-...) หรือ <strong>เบอร์โทรศัพท์ 10 หลัก</strong> ที่ใช้ในการจอง
        </p>
    </div>

    <!-- Search Form -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-6 mb-8">
        <form action="<?= base_url('track.php') ?>" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" 
                       name="search" 
                       value="<?= htmlspecialchars($search) ?>" 
                       placeholder="พิมพ์หมายเลขคำสั่งจอง หรือ เบอร์โทรศัพท์..." 
                       required
                       class="w-full pl-11 pr-4 py-3 rounded-2xl border border-slate-200 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 text-sm text-slate-900 transition">
            </div>
            <button type="submit" class="px-7 py-3 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md transition active:scale-95 flex items-center justify-center">
                <span>ค้นหารายการ</span>
            </button>
        </form>
    </div>

    <?php if (empty($search) && $activePendingOrder): ?>
        <!-- Quick Access to Active Pending Order -->
        <div class="bg-gradient-to-br from-amber-50 to-orange-50 border border-amber-200/80 rounded-3xl p-6 mb-8 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start space-x-3.5">
                    <div class="w-10 h-10 rounded-xl bg-amber-500 text-slate-950 flex items-center justify-center font-bold text-sm shrink-0 shadow-sm">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="text-xs font-bold text-amber-800 uppercase tracking-wider">คำสั่งจองรอชำระเงินล่าสุดของคุณ</span>
                            <?= Helper::getStatusBadge($activePendingOrder['status']) ?>
                        </div>
                        <div class="text-base font-black text-slate-900 font-mono mt-0.5">
                            <?= htmlspecialchars($activePendingOrder['order_code']) ?>
                        </div>
                        <div class="text-xs text-slate-600 mt-1">
                            ยอดชำระ: <strong class="text-blue-600">฿<?= Helper::money($activePendingOrder['total_amount']) ?></strong> | ผู้สั่งจอง: <?= htmlspecialchars($activePendingOrder['customer_name']) ?>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a href="<?= base_url('payment.php?code=' . urlencode($activePendingOrder['order_code'])) ?>" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md transition flex items-center">
                        <i class="fas fa-receipt mr-1.5"></i> ชำระเงิน & แนบสลิป
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Search Results -->
    <?php if (!empty($search)): ?>
        <?php if (!empty($orders)): ?>
            <div class="space-y-4">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                    พบคำสั่งจองทั้งหมด <?= count($orders) ?> รายการ
                </div>

                <?php foreach ($orders as $o): ?>
                    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 hover:border-slate-300 transition space-y-4">
                        <!-- Card Header -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 gap-3">
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-base font-black text-slate-900 font-mono"><?= htmlspecialchars($o['order_code']) ?></span>
                                    <?= Helper::getStatusBadge($o['status']) ?>
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    วันที่สั่งจอง: <?= Helper::thaiDate($o['created_at']) ?>
                                </div>
                            </div>

                            <div class="text-right">
                                <span class="text-[11px] text-slate-400 block">ยอดสุทธิ</span>
                                <span class="text-lg font-black text-blue-600">฿<?= Helper::money($o['total_amount']) ?></span>
                            </div>
                        </div>

                        <!-- Order Details Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 py-1 text-xs">
                            <div>
                                <span class="text-slate-400 block text-[11px] mb-0.5">ผู้สั่งจอง:</span>
                                <?php 
                                    $rawPhone = $o['customer_phone'];
                                    $maskedPhone = (strlen($rawPhone) >= 9) ? substr($rawPhone, 0, 3) . '-***-' . substr($rawPhone, -4) : htmlspecialchars($rawPhone);
                                ?>
                                <span class="font-bold text-slate-800"><?= htmlspecialchars($o['customer_name']) ?></span> (<?= htmlspecialchars($maskedPhone) ?>)
                                <?php if (!empty($o['customer_organization'])): ?>
                                    <span class="block text-[11px] text-blue-700 font-semibold mt-0.5"><i class="fas fa-university mr-1 text-[10px]"></i><?= htmlspecialchars($o['customer_organization']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px] mb-0.5">วิธีรับสินค้า:</span>
                                <span class="font-medium text-slate-800">
                                    <?= $o['delivery_method'] === 'delivery' ? '<i class="fas fa-shipping-fast text-indigo-500 mr-1"></i> จัดส่งพัสดุ' : '<i class="fas fa-hand-holding-box text-emerald-500 mr-1"></i> รับด้วยตนเองที่วิทยาลัย' ?>
                                </span>
                                <?php if ($o['delivery_method'] === 'delivery' && !empty($o['shipping_address'])): ?>
                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                        <i class="fas fa-map-marker-alt text-rose-500 mr-1"></i><?= htmlspecialchars($o['shipping_address']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px] mb-0.5">รอบการจอง:</span>
                                <span class="text-slate-700"><?= htmlspecialchars($o['round_name'] ?: 'รอบทั่วไป') ?></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px] mb-0.5">จำนวนสินค้า:</span>
                                <span class="text-slate-700 font-semibold"><?= (int)$o['total_pieces'] ?> ชิ้น/ตัว</span>
                            </div>
                        </div>

                        <!-- Action Buttons inside the card -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-4 border-t border-slate-100">
                            <div class="text-[11px] text-slate-400">
                                หมายเลขคำสั่งจอง: <span class="font-mono font-semibold text-slate-600"><?= htmlspecialchars($o['order_code']) ?></span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <?php if ($o['status'] === 'pending_payment'): ?>
                                    <a href="<?= base_url('payment.php?code=' . urlencode($o['order_code'])) ?>" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-sm transition flex items-center">
                                        <i class="fas fa-qrcode mr-1.5"></i> ชำระเงิน & แนบสลิป
                                    </a>
                                <?php elseif ($o['status'] === 'waiting_verification'): ?>
                                    <a href="<?= base_url('payment.php?code=' . urlencode($o['order_code'])) ?>" class="px-4 py-2 rounded-xl bg-blue-50 text-blue-700 hover:bg-blue-100 font-bold text-xs transition flex items-center">
                                        <i class="fas fa-file-invoice-dollar mr-1.5"></i> ตรวจสอบสลิปที่แนบ
                                    </a>
                                <?php elseif ($o['status'] === 'confirmed'): ?>
                                    <a href="<?= base_url('voucher.php?code=' . urlencode($o['order_code'])) ?>" target="_blank" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition flex items-center">
                                        <i class="fas fa-print mr-1.5"></i> พิมพ์ใบรับสินค้า (Voucher)
                                    </a>
                                <?php endif; ?>

                                <a href="<?= base_url('payment.php?code=' . urlencode($o['order_code'])) ?>" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition flex items-center">
                                    <i class="fas fa-eye mr-1.5 text-slate-500"></i> ดูรายละเอียดคำสั่งจอง
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-3xl border border-slate-200/80 p-12 text-center shadow-sm">
                <div class="w-16 h-16 rounded-full bg-slate-50 text-slate-300 flex items-center justify-center mx-auto mb-3 text-2xl">
                    <i class="fas fa-search-minus"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800 mb-1">ไม่พบข้อมูลคำสั่งจอง</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">
                    ไม่พบคำสั่งจองที่ตรงกับ "<strong><?= htmlspecialchars($search) ?></strong>" กรุณาตรวจสอบหมายเลขคำสั่งจองหรือเบอร์โทรศัพท์อีกครั้ง
                </p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
