<?php
$pageTitle = "แดชบอร์ดภาพรวม - CMTC Admin";
$activePage = 'dashboard';
require_once __DIR__ . '/../includes/admin_header.php';

$pdo = Database::getInstance()->getConnection();

// 1. Calculate Summary Statistics
$confirmedRev = (float)$pdo->query("
    SELECT COALESCE(SUM(total_amount), 0) 
    FROM orders 
    WHERE status IN ('confirmed', 'completed')
")->fetchColumn();

$confirmedPieces = (int)$pdo->query("
    SELECT COALESCE(SUM(oi.quantity), 0)
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status IN ('confirmed', 'completed')
")->fetchColumn();

$waitingCount = (int)$pdo->query("
    SELECT COUNT(*) FROM orders WHERE status = 'waiting_verification'
")->fetchColumn();

$totalOrdersCount = (int)$pdo->query("
    SELECT COUNT(*) FROM orders
")->fetchColumn();

// 2. Fetch Latest Orders Waiting for Verification
$waitingOrdersStmt = $pdo->query("
    SELECT o.*, p.slip_image, p.transfer_datetime, p.customer_note
    FROM orders o
    LEFT JOIN payments p ON o.id = p.order_id AND p.status = 'pending'
    WHERE o.status = 'waiting_verification'
    ORDER BY o.id ASC
    LIMIT 10
");
$waitingOrders = $waitingOrdersStmt->fetchAll();

// 3. Product Sales Breakdown (Confirmed vs Pending)
$productStatsStmt = $pdo->query("
    SELECT p.id, p.name, p.code, p.category, p.main_image,
           COALESCE(SUM(CASE WHEN o.status IN ('confirmed', 'completed') THEN oi.quantity ELSE 0 END), 0) as confirmed_qty,
           COALESCE(SUM(CASE WHEN o.status = 'waiting_verification' THEN oi.quantity ELSE 0 END), 0) as pending_verify_qty,
           COALESCE(SUM(CASE WHEN o.status IN ('confirmed', 'completed') THEN oi.subtotal ELSE 0 END), 0) as confirmed_sales
    FROM products p
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id
    GROUP BY p.id
    ORDER BY confirmed_qty DESC, p.id ASC
");
$productStats = $productStatsStmt->fetchAll();
?>

<div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-8">
    
    <!-- Top Welcome Banner -->
    <div class="mb-5 sm:mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">ภาพรวมระบบสั่งจองสินค้า</h1>
            <p class="text-xs text-slate-500 mt-0.5">ยินดีต้อนรับ <?= htmlspecialchars($currentUser['fullname']) ?> • ข้อมูลอัปเดตแบบเรียลไทม์</p>
        </div>
        <div class="grid grid-cols-2 sm:flex sm:items-center gap-2">
            <a href="<?= base_url('admin/summary.php') ?>" class="px-3.5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-xs transition flex items-center justify-center btn-touch">
                <i class="fas fa-industry mr-1.5"></i> <span class="truncate">ใบสั่งผลิต</span>
            </a>
            <a href="<?= base_url('admin/orders.php') ?>" class="px-3.5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-xs transition flex items-center justify-center btn-touch">
                <i class="fas fa-clipboard-list mr-1.5"></i> <span class="truncate">รายการสั่งจอง</span>
            </a>
        </div>
    </div>

    <!-- 4 Stats Cards Grid (2x2 on Mobile, 4 Cols on Desktop) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-5 mb-5 sm:mb-8">
        
        <!-- Total Revenue Card -->
        <div class="bg-white rounded-2xl sm:rounded-3xl p-3.5 sm:p-6 border border-slate-200/80 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] sm:text-xs font-semibold text-slate-400 block truncate">ยอดขายยืนยัน</span>
                <div class="w-8 h-8 sm:w-11 sm:h-11 rounded-xl sm:rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm sm:text-lg flex-shrink-0">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>
            <div>
                <span class="text-lg sm:text-2xl font-black text-slate-900 block truncate">฿<?= Helper::money($confirmedRev) ?></span>
                <span class="text-[10px] sm:text-[11px] text-emerald-600 mt-0.5 font-medium truncate flex items-center">
                    <i class="fas fa-check-circle mr-1 text-[9px]"></i> โอนเข้าบัญชีแล้ว
                </span>
            </div>
        </div>

        <!-- Confirmed Pieces Card -->
        <div class="bg-white rounded-2xl sm:rounded-3xl p-3.5 sm:p-6 border border-slate-200/80 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] sm:text-xs font-semibold text-slate-400 block truncate">ยอดจองทั้งหมด</span>
                <div class="w-8 h-8 sm:w-11 sm:h-11 rounded-xl sm:rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm sm:text-lg flex-shrink-0">
                    <i class="fas fa-box-open"></i>
                </div>
            </div>
            <div>
                <span class="text-lg sm:text-2xl font-black text-blue-600 block truncate">
                    <?= number_format($confirmedPieces) ?> <span class="text-xs font-semibold text-slate-500">ชิ้น/ตัว</span>
                </span>
                <span class="text-[10px] sm:text-[11px] text-slate-400 mt-0.5 block truncate">
                    ตัดสต็อกส่งผลิตแล้ว
                </span>
            </div>
        </div>

        <!-- Waiting Verification Card (Actionable) -->
        <div class="bg-white rounded-2xl sm:rounded-3xl p-3.5 sm:p-6 border <?= $waitingCount > 0 ? 'border-amber-300 bg-amber-50/20' : 'border-slate-200/80' ?> shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] sm:text-xs font-semibold text-slate-400 block truncate">รอตรวจสลิป</span>
                <div class="w-8 h-8 sm:w-11 sm:h-11 rounded-xl sm:rounded-2xl <?= $waitingCount > 0 ? 'bg-amber-100 text-amber-700 animate-pulse' : 'bg-slate-50 text-slate-400' ?> flex items-center justify-center text-sm sm:text-lg flex-shrink-0">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
            </div>
            <div>
                <span class="text-lg sm:text-2xl font-black <?= $waitingCount > 0 ? 'text-amber-600' : 'text-slate-900' ?> block truncate">
                    <?= number_format($waitingCount) ?> <span class="text-xs font-semibold text-slate-500">รายการ</span>
                </span>
                <a href="<?= base_url('admin/orders.php?status=waiting_verification') ?>" class="text-[10px] sm:text-[11px] text-blue-600 hover:underline font-bold mt-0.5 block truncate">
                    ตรวจด่วนทันที &rarr;
                </a>
            </div>
        </div>

        <!-- Total Orders Card -->
        <div class="bg-white rounded-2xl sm:rounded-3xl p-3.5 sm:p-6 border border-slate-200/80 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] sm:text-xs font-semibold text-slate-400 block truncate">บิลคำสั่งจอง</span>
                <div class="w-8 h-8 sm:w-11 sm:h-11 rounded-xl sm:rounded-2xl bg-slate-50 text-slate-600 flex items-center justify-center text-sm sm:text-lg flex-shrink-0">
                    <i class="fas fa-boxes"></i>
                </div>
            </div>
            <div>
                <span class="text-lg sm:text-2xl font-black text-slate-900 block truncate">
                    <?= number_format($totalOrdersCount) ?> <span class="text-xs font-semibold text-slate-500">บิล</span>
                </span>
                <span class="text-[10px] sm:text-[11px] text-slate-400 mt-0.5 block truncate">รวมทุกสถานะ</span>
            </div>
        </div>
    </div>

    <!-- Section 1: Urgent Waiting Verification -->
    <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/80 shadow-xs p-4 sm:p-8 mb-6 sm:mb-8">
        <div class="flex items-center justify-between pb-3 sm:pb-4 border-b border-slate-100 mb-4 sm:mb-6">
            <div class="flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-sm flex-shrink-0">
                    <i class="fas fa-bell"></i>
                </div>
                <div>
                    <h2 class="text-sm sm:text-base font-black text-slate-900">รอตรวจสอบสลิปโอนเงิน (ด่วน)</h2>
                    <p class="text-[11px] sm:text-xs text-slate-400">ตรวจสอบยอดเงินและเวลาก่อนกดยืนยัน</p>
                </div>
            </div>

            <a href="<?= base_url('admin/orders.php?status=waiting_verification') ?>" class="text-xs font-bold text-blue-600 hover:text-blue-700 flex-shrink-0">
                ดูทั้งหมด (<?= $waitingCount ?>) &rarr;
            </a>
        </div>

        <?php if (!empty($waitingOrders)): ?>
            <!-- MOBILE CARDS VIEW (block md:hidden) -->
            <div class="block md:hidden space-y-3">
                <?php foreach ($waitingOrders as $wo): ?>
                    <div class="bg-slate-50/90 rounded-2xl p-3.5 border border-slate-200/80 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="font-mono font-black text-blue-700 text-xs">
                                <?= htmlspecialchars($wo['order_code']) ?>
                            </span>
                            <span class="font-black text-base text-blue-600">
                                ฿<?= Helper::money($wo['total_amount']) ?>
                            </span>
                        </div>

                        <div class="flex items-start justify-between text-xs text-slate-600 gap-2">
                            <div class="min-w-0 flex-1">
                                <span class="font-bold text-slate-900 block truncate"><?= htmlspecialchars($wo['customer_name']) ?></span>
                                <?php if (!empty($wo['customer_organization'])): ?>
                                    <span class="text-[10px] text-blue-700 font-semibold block truncate"><i class="fas fa-university text-[9px] mr-1 text-slate-400"></i><?= htmlspecialchars($wo['customer_organization']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($wo['customer_phone'])): ?>
                                <a href="tel:<?= htmlspecialchars($wo['customer_phone']) ?>" class="text-[11px] text-blue-600 bg-blue-50 px-2 py-0.5 rounded-lg font-bold flex-shrink-0">
                                    <i class="fas fa-phone-alt mr-1"></i> <?= htmlspecialchars($wo['customer_phone']) ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Slip Thumbnail Preview -->
                        <?php if (!empty($wo['slip_image'])): ?>
                            <div class="bg-white rounded-xl p-2 border border-slate-200 flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <img src="<?= base_url($wo['slip_image']) ?>" class="w-10 h-10 object-cover rounded-lg border border-slate-200">
                                    <div>
                                        <span class="text-[11px] font-bold text-slate-800 block">สลิปโอนเงิน</span>
                                        <span class="text-[10px] text-slate-400 block"><?= Helper::thaiDate($wo['transfer_datetime']) ?></span>
                                    </div>
                                </div>
                                <button onclick="previewSlipModal('<?= base_url($wo['slip_image']) ?>', '<?= htmlspecialchars($wo['order_code']) ?>', '<?= Helper::money($wo['total_amount']) ?>', '<?= Helper::thaiDate($wo['transfer_datetime']) ?>', <?= $wo['id'] ?>)" 
                                        class="px-2.5 py-1.5 rounded-lg bg-blue-50 text-blue-700 text-[11px] font-bold shadow-xs active:scale-95 transition">
                                    <i class="far fa-image mr-1"></i> ดูสลิป
                                </button>
                            </div>
                        <?php endif; ?>

                        <!-- Quick Thumb Action Buttons -->
                        <div class="pt-1.5 flex items-center gap-2">
                            <button onclick="actionVerify(<?= $wo['id'] ?>, 'approve')" class="flex-1 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition flex items-center justify-center btn-touch">
                                <i class="fas fa-check mr-1.5"></i> อนุมัติการจอง
                            </button>
                            <button onclick="actionRejectModal(<?= $wo['id'] ?>, '<?= htmlspecialchars($wo['order_code']) ?>')" class="py-2.5 px-3 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 font-bold text-xs transition flex items-center justify-center btn-touch">
                                <i class="fas fa-times mr-1"></i> ปฏิเสธ
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- DESKTOP TABLE VIEW (hidden md:block) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4">รหัสคำสั่งจอง</th>
                            <th class="py-3 px-4">ผู้สั่งจอง</th>
                            <th class="py-3 px-4">เบอร์โทรศัพท์</th>
                            <th class="py-3 px-4 text-right">ยอดที่ต้องชำระ</th>
                            <th class="py-3 px-4 text-center">ภาพสลิป</th>
                            <th class="py-3 px-4">เวลาที่แจ้งโอน</th>
                            <th class="py-3 px-4 text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php foreach ($waitingOrders as $wo): ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-4 font-mono font-bold text-blue-700">
                                    <?= htmlspecialchars($wo['order_code']) ?>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-semibold text-slate-900"><?= htmlspecialchars($wo['customer_name']) ?></div>
                                    <?php if (!empty($wo['customer_organization'])): ?>
                                        <div class="text-[10px] text-blue-700 font-semibold truncate max-w-xs"><i class="fas fa-university text-[9px] mr-1 text-slate-400"></i><?= htmlspecialchars($wo['customer_organization']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4">
                                    <?= htmlspecialchars($wo['customer_phone']) ?>
                                </td>
                                <td class="py-3 px-4 text-right font-black text-blue-600">
                                    ฿<?= Helper::money($wo['total_amount']) ?>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <?php if (!empty($wo['slip_image'])): ?>
                                        <button onclick="previewSlipModal('<?= base_url($wo['slip_image']) ?>', '<?= htmlspecialchars($wo['order_code']) ?>', '<?= Helper::money($wo['total_amount']) ?>', '<?= Helper::thaiDate($wo['transfer_datetime']) ?>', <?= $wo['id'] ?>)" class="inline-flex items-center text-xs text-blue-600 hover:text-blue-800 font-semibold bg-blue-50 px-2 py-1 rounded-lg">
                                            <i class="far fa-image mr-1"></i> ดูสลิป
                                        </button>
                                    <?php else: ?>
                                        <span class="text-slate-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-slate-500">
                                    <?= Helper::thaiDate($wo['transfer_datetime']) ?>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="inline-flex space-x-1">
                                        <button onclick="actionVerify(<?= $wo['id'] ?>, 'approve')" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] shadow-sm transition">
                                            <i class="fas fa-check mr-1"></i> อนุมัติ
                                        </button>
                                        <button onclick="actionRejectModal(<?= $wo['id'] ?>, '<?= htmlspecialchars($wo['order_code']) ?>')" class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-[11px] transition">
                                            <i class="fas fa-times mr-1"></i> ปฏิเสธ
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="py-8 text-center text-slate-400">
                <i class="fas fa-check-circle text-3xl text-emerald-400 mb-2"></i>
                <p class="text-xs">ไม่มีรายการรอตรวจสอบสลิปในขณะนี้ ทุกรายการได้รับการตรวจสอบเรียบร้อยแล้ว</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Section 2: Product Breakdown Summary -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-6">
            <div>
                <h2 class="text-base font-black text-slate-900">สรุปยอดจองรายสินค้า</h2>
                <p class="text-xs text-slate-500">สถิติยอดจองสินค้าและรายได้แยกตามแบบ</p>
            </div>
            <a href="<?= base_url('admin/summary.php') ?>" class="text-xs font-semibold text-blue-600 hover:text-blue-700">
                ดูรายงานตารางแยกตามไซส์ / ตัวเลือกอย่างละเอียด &rarr;
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($productStats as $ps): ?>
                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 flex items-center space-x-4">
                    <img src="<?= base_url($ps['main_image'] ?: 'assets/uploads/products/polo_87_main.jpg') ?>" class="w-16 h-16 rounded-xl object-cover bg-white border border-slate-200 flex-shrink-0">
                    <div class="flex-1 min-w-0">
                        <span class="text-[10px] bg-slate-200 text-slate-700 px-2 py-0.5 rounded font-mono font-bold"><?= htmlspecialchars($ps['code']) ?></span>
                        <h4 class="font-bold text-xs text-slate-900 truncate mt-1"><?= htmlspecialchars($ps['name']) ?></h4>
                        <div class="mt-2 flex items-center justify-between text-xs">
                            <span class="text-slate-500">จองยืนยัน: <strong class="text-blue-600"><?= $ps['confirmed_qty'] ?></strong> ชิ้น/ตัว</span>
                            <span class="font-black text-slate-900">฿<?= Helper::money($ps['confirmed_sales']) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Modal: Preview Slip -->
<div id="slipModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm hidden p-3 sm:p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl sm:rounded-3xl max-w-lg w-full p-4 sm:p-6 shadow-2xl relative my-auto max-h-[92vh] flex flex-col">
        <button onclick="closeSlipModal()" class="absolute top-3 right-3 sm:top-4 sm:right-4 text-slate-400 hover:text-slate-600 w-9 h-9 rounded-full flex items-center justify-center hover:bg-slate-100 transition z-10" aria-label="Close">
            <i class="fas fa-times text-base"></i>
        </button>

        <h3 class="text-base font-black text-slate-900 mb-0.5 pr-8 truncate" id="slipModalTitle">ตรวจสอบหลักฐานการโอนเงิน</h3>
        <p class="text-xs text-slate-500 mb-3" id="slipModalSubtitle"></p>

        <div class="bg-slate-100 rounded-xl sm:rounded-2xl overflow-hidden mb-3 max-h-[55vh] flex items-center justify-center border border-slate-200 relative group">
            <img id="slipModalImg" class="max-h-[55vh] w-auto object-contain cursor-zoom-in" onclick="window.open(this.src, '_blank')" title="แตะเพื่อเปิดรูปภาพขนาดเต็ม">
            <div class="absolute bottom-2 right-2 bg-slate-900/70 text-white text-[10px] px-2 py-1 rounded-md backdrop-blur-xs pointer-events-none">
                <i class="fas fa-search-plus mr-1"></i> แตะภาพเพื่อดูขนาดเต็ม
            </div>
        </div>

        <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100 flex-shrink-0" id="slipModalActions">
            <!-- Dynamic Buttons added via JS -->
        </div>
    </div>
</div>

<script>
let currentActiveOrderId = null;

function previewSlipModal(imageUrl, orderCode, totalAmount, transferDate, orderId) {
    currentActiveOrderId = orderId;
    document.getElementById('slipModalTitle').textContent = 'หลักฐานการโอน: ' + orderCode;
    document.getElementById('slipModalSubtitle').textContent = 'ยอดสั่งจอง: ฿' + totalAmount + ' • เวลาที่แจ้ง: ' + transferDate;
    document.getElementById('slipModalImg').src = imageUrl;

    const actionDiv = document.getElementById('slipModalActions');
    actionDiv.innerHTML = `
        <button onclick="actionRejectModal(${orderId}, '${orderCode}')" class="flex-1 sm:flex-initial px-4 py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-xs transition flex items-center justify-center btn-touch">
            <i class="fas fa-times mr-1.5"></i> ปฏิเสธสลิป
        </button>
        <button onclick="actionVerify(${orderId}, 'approve')" class="flex-1 sm:flex-initial px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md transition flex items-center justify-center btn-touch">
            <i class="fas fa-check mr-1.5"></i> อนุมัติการจอง
        </button>
    `;

    document.getElementById('slipModal').classList.remove('hidden');
}

function closeSlipModal() {
    document.getElementById('slipModal').classList.add('hidden');
}

function actionVerify(orderId, action) {
    Swal.fire({
        title: 'ยืนยันอนุมัติการจอง?',
        text: 'ระบบจะตัดสต็อกสินค้าจริงและเปลี่ยนสถานะเป็นยืนยันแล้ว',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'ยืนยันอนุมัติ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b'
    }).then(res => {
        if (res.isConfirmed) {
            submitVerifyAction(orderId, 'approve');
        }
    });
}

function actionRejectModal(orderId, orderCode) {
    closeSlipModal();
    Swal.fire({
        title: 'ปฏิเสธรายการจอง ' + orderCode,
        text: 'ระบุเหตุผลการปฏิเสธ (เช่น ยอดเงินไม่ตรง, สลิปไม่ชัดเจน):',
        input: 'text',
        inputValue: 'ยอดเงินหรือเวลาในสลิปไม่ถูกต้อง',
        showCancelButton: true,
        confirmButtonText: 'ยืนยันปฏิเสธ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        inputValidator: (value) => {
            if (!value) {
                return 'กรุณาระบุเหตุผลการปฏิเสธ';
            }
        }
    }).then(res => {
        if (res.isConfirmed) {
            submitVerifyAction(orderId, 'reject', res.value);
        }
    });
}

function submitVerifyAction(orderId, action, reason = '') {
    const formData = new FormData();
    formData.append('csrf_token', '<?= Csrf::getToken() ?>');
    formData.append('order_id', orderId);
    formData.append('action', action);
    formData.append('reason', reason);

    fetch('<?= base_url("admin/verify_payment.php") ?>', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ',
                text: 'ดำเนินการเรียบร้อยแล้ว',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: data.error || 'ไม่สามารถดำเนินการได้'
            });
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
