<?php
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helper.php';
require_once __DIR__ . '/../core/Csrf.php';

Auth::requireLogin();
$pdo = Database::getInstance()->getConnection();

// Handle Mark Completed Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_order') {
    Csrf::verifyOrDie();
    $oid = (int)($_POST['order_id'] ?? 0);
    if ($oid > 0) {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'completed', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$oid]);
        Helper::setFlash('success', 'ปรับสถานะสำเร็จ', 'ทำเครื่องหมายว่าส่งมอบสินค้าเรียบร้อยแล้ว');
        header("Location: " . base_url('admin/orders.php'));
        exit;
    }
}

$pageTitle = "จัดการรายการสั่งจอง - CMTC Admin";
$activePage = 'orders';
require_once __DIR__ . '/../includes/admin_header.php';

// Filters
$statusFilter = trim($_GET['status'] ?? 'all');
$roundFilter = (int)($_GET['round_id'] ?? 0);
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT o.*, r.round_name, p.slip_image, p.transfer_datetime, p.customer_note, p.status as payment_status,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count,
           (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) as total_qty
    FROM orders o
    LEFT JOIN booking_rounds r ON o.round_id = r.id
    LEFT JOIN payments p ON o.id = p.order_id
    WHERE 1=1
";
$params = [];

if ($statusFilter !== 'all' && !empty($statusFilter)) {
    $sql .= " AND o.status = ?";
    $params[] = $statusFilter;
}

if ($roundFilter > 0) {
    $sql .= " AND o.round_id = ?";
    $params[] = $roundFilter;
}

if (!empty($search)) {
    $sql .= " AND (o.order_code LIKE ? OR o.customer_name LIKE ? OR o.customer_phone LIKE ? OR o.customer_organization LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY o.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Fetch rounds for filter dropdown
$rounds = $pdo->query("SELECT id, round_name FROM booking_rounds ORDER BY id DESC")->fetchAll();

// Quick status counts for mobile pills
$statusCounts = [
    'all' => (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'waiting_verification' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'waiting_verification'")->fetchColumn(),
    'confirmed' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'confirmed'")->fetchColumn(),
    'completed' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'")->fetchColumn(),
    'pending_payment' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending_payment'")->fetchColumn(),
];
?>

<div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-8">
    
    <!-- Header -->
    <div class="mb-4 sm:mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">รายการสั่งจองสินค้าทั้งหมด</h1>
            <p class="text-xs text-slate-500 mt-0.5">ค้นหา ตรวจสอบหลักฐานการโอนเงิน และพิมพ์ใบรับสินค้า</p>
        </div>
        <div class="flex items-center space-x-2">
            <span class="text-xs font-bold text-slate-600 bg-slate-100 px-3 py-1.5 rounded-xl">
                พบทั้งหมด <?= count($orders) ?> รายการ
            </span>
        </div>
    </div>

    <!-- Quick Status Filter Pills (Mobile First Horizontal Scroll) -->
    <div class="flex items-center space-x-2 overflow-x-auto pb-2.5 mb-4 scrollbar-none touch-scroll">
        <?php
        $pillConfig = [
            'all' => ['label' => 'ทั้งหมด', 'count' => $statusCounts['all'], 'icon' => 'fa-list'],
            'waiting_verification' => ['label' => 'รอตรวจสลิป', 'count' => $statusCounts['waiting_verification'], 'icon' => 'fa-clock', 'alert' => true],
            'confirmed' => ['label' => 'ยืนยันแล้ว', 'count' => $statusCounts['confirmed'], 'icon' => 'fa-check-circle'],
            'completed' => ['label' => 'รับสินค้าแล้ว', 'count' => $statusCounts['completed'], 'icon' => 'fa-box-check'],
            'pending_payment' => ['label' => 'รอชำระเงิน', 'count' => $statusCounts['pending_payment'], 'icon' => 'fa-wallet'],
        ];
        foreach ($pillConfig as $stKey => $stData):
            $isActive = ($statusFilter === $stKey);
            $hasAlert = !empty($stData['alert']) && $stData['count'] > 0;
        ?>
            <a href="<?= base_url('admin/orders.php?status=' . $stKey . ($roundFilter ? '&round_id=' . $roundFilter : '')) ?>" 
               class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center whitespace-nowrap flex-shrink-0 <?= $isActive ? 'bg-blue-600 text-white shadow-xs' : ($hasAlert ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50') ?>">
                <i class="fas <?= $stData['icon'] ?> mr-1.5 <?= $isActive ? 'text-white' : ($hasAlert ? 'text-amber-600' : 'text-slate-400') ?>"></i>
                <span><?= $stData['label'] ?></span>
                <span class="ml-1.5 text-[10px] font-black px-1.5 py-0.5 rounded-full <?= $isActive ? 'bg-white/25 text-white' : ($hasAlert ? 'bg-amber-200 text-amber-950' : 'bg-slate-100 text-slate-600') ?>">
                    <?= $stData['count'] ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/80 shadow-xs p-3.5 sm:p-5 mb-5">
        <form action="<?= base_url('admin/orders.php') ?>" method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 sm:gap-3 items-center">
            
            <!-- Search Keyword -->
            <div class="sm:col-span-5 relative">
                <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="ค้นหาด้วยรหัสคำสั่งจอง, ชื่อ, หรือเบอร์โทร..." class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:border-blue-600 focus:ring-1 focus:ring-blue-100 text-slate-900">
            </div>

            <!-- Status Filter -->
            <div class="sm:col-span-3">
                <select name="status" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:border-blue-600 text-slate-900 bg-white">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>ทุกสถานะคำสั่งจอง</option>
                    <option value="waiting_verification" <?= $statusFilter === 'waiting_verification' ? 'selected' : '' ?>>รอตรวจสอบสลิป</option>
                    <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>ยืนยันแล้ว</option>
                    <option value="pending_payment" <?= $statusFilter === 'pending_payment' ? 'selected' : '' ?>>รอชำระเงิน</option>
                    <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>รับสินค้าแล้ว</option>
                    <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>ปฏิเสธ/สลิปไม่ถูกต้อง</option>
                    <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>ยกเลิก/หมดเวลา</option>
                </select>
            </div>

            <!-- Round Filter -->
            <div class="sm:col-span-3">
                <select name="round_id" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:border-blue-600 text-slate-900 bg-white">
                    <option value="0">ทุกรอบการจอง</option>
                    <?php foreach ($rounds as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= $roundFilter == $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['round_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Submit -->
            <div class="sm:col-span-1 flex gap-2">
                <button type="submit" class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-xs shadow-xs transition">
                    ค้นหา
                </button>
            </div>
        </form>
    </div>

    <!-- MOBILE VIEW: Dedicated Order Cards (block md:hidden) -->
    <div class="block md:hidden space-y-3.5 mb-6">
        <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $o): ?>
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-4 flex flex-col justify-between space-y-3 transition">
                    <!-- Top: Order Code & Status -->
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <div class="min-w-0">
                            <span class="font-mono font-black text-blue-700 text-xs block truncate">
                                <?= htmlspecialchars($o['order_code']) ?>
                            </span>
                            <span class="text-[10px] text-slate-400">
                                <?= Helper::thaiDate($o['created_at']) ?>
                            </span>
                        </div>
                        <div class="flex-shrink-0">
                            <?= Helper::getStatusBadge($o['status']) ?>
                        </div>
                    </div>

                    <!-- Customer Info -->
                    <div class="space-y-1.5 text-xs text-slate-600">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <span class="font-bold text-slate-900 block truncate"><?= htmlspecialchars($o['customer_name']) ?></span>
                                <?php if (!empty($o['customer_organization'])): ?>
                                    <span class="text-[10px] text-blue-700 font-semibold block truncate mt-0.5"><i class="fas fa-university text-[9px] mr-1 text-slate-400"></i><?= htmlspecialchars($o['customer_organization']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($o['customer_phone'])): ?>
                                <a href="tel:<?= htmlspecialchars($o['customer_phone']) ?>" class="inline-flex items-center px-2 py-0.5 rounded-lg bg-blue-50 text-blue-700 font-bold text-[11px] active:scale-95 transition flex-shrink-0">
                                    <i class="fas fa-phone-alt mr-1 text-[10px]"></i> <?= htmlspecialchars($o['customer_phone']) ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center justify-between text-[11px] pt-1">
                            <span class="text-slate-500">
                                <?php if ($o['delivery_method'] === 'delivery'): ?>
                                    <span class="text-indigo-700 font-semibold"><i class="fas fa-shipping-fast mr-1"></i> จัดส่งพัสดุ</span>
                                    <?php if (!empty($o['shipping_address'])): ?>
                                        <div class="text-[11px] text-slate-600 bg-slate-50 p-2 rounded-lg border border-slate-100 mt-1">
                                            <i class="fas fa-map-marker-alt text-rose-500 mr-1"></i><span class="font-bold text-slate-700">ที่อยู่:</span> <?= htmlspecialchars($o['shipping_address']) ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-slate-600 font-semibold"><i class="fas fa-hand-holding-box mr-1"></i> รับเองที่วิทยาลัย</span>
                                <?php endif; ?>
                                &bull; <?= (int)$o['total_qty'] ?> ชิ้น/ตัว
                            </span>
                            <span class="font-black text-base text-blue-600">
                                ฿<?= Helper::money($o['total_amount']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Slip Preview Thumbnail if uploaded -->
                    <?php if (!empty($o['slip_image'])): ?>
                        <div class="bg-slate-50 rounded-xl p-2 border border-slate-100 flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <img src="<?= base_url($o['slip_image']) ?>" class="w-10 h-10 object-cover rounded-lg border border-slate-200">
                                <div>
                                    <span class="text-[11px] font-bold text-slate-800 block">มีสลิปการโอน</span>
                                    <span class="text-[10px] text-slate-400 block"><?= Helper::thaiDate($o['transfer_datetime']) ?></span>
                                </div>
                            </div>
                            <button onclick="previewSlipModal('<?= base_url($o['slip_image']) ?>', '<?= htmlspecialchars($o['order_code']) ?>', '<?= Helper::money($o['total_amount']) ?>', '<?= Helper::thaiDate($o['transfer_datetime']) ?>', <?= $o['id'] ?>)" 
                                    class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 text-blue-600 text-[11px] font-bold shadow-xs active:scale-95 transition">
                                <i class="far fa-image mr-1"></i> แตะตรวจสลิป
                            </button>
                        </div>
                    <?php endif; ?>

                    <!-- Mobile Action Buttons -->
                    <div class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
                        <?php if ($o['status'] === 'waiting_verification'): ?>
                            <button onclick="actionVerify(<?= $o['id'] ?>, 'approve')" class="flex-1 py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition flex items-center justify-center btn-touch">
                                <i class="fas fa-check mr-1.5"></i> อนุมัติการจอง
                            </button>
                            <button onclick="actionRejectModal(<?= $o['id'] ?>, '<?= htmlspecialchars($o['order_code']) ?>')" class="py-2.5 px-3 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 font-bold text-xs transition flex items-center justify-center btn-touch">
                                <i class="fas fa-times mr-1"></i> ปฏิเสธ
                            </button>
                        <?php endif; ?>

                        <?php if ($o['status'] === 'confirmed'): ?>
                            <a href="<?= base_url('voucher.php?code=' . urlencode($o['order_code'])) ?>" target="_blank" class="flex-1 py-2.5 px-3 rounded-xl bg-blue-50 text-blue-700 font-bold text-xs text-center transition flex items-center justify-center btn-touch">
                                <i class="fas fa-print mr-1.5"></i> พิมพ์ใบรับของ
                            </a>
                            <form action="<?= base_url('admin/orders.php') ?>" method="POST" class="inline flex-1" onsubmit="return confirm('ยืนยันว่าลูกค้าได้รับสินค้าเรียบร้อยแล้ว?');">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="action" value="complete_order">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <button type="submit" class="w-full py-2.5 px-3 rounded-xl bg-purple-50 text-purple-700 font-bold text-xs transition flex items-center justify-center btn-touch">
                                    <i class="fas fa-box-check mr-1.5"></i> รับของแล้ว
                                </button>
                            </form>
                        <?php endif; ?>

                        <a href="<?= base_url('payment.php?code=' . urlencode($o['order_code'])) ?>" target="_blank" class="p-2.5 rounded-xl bg-slate-100 text-slate-600 text-xs transition flex items-center justify-center btn-touch" title="เปิดหน้าการจอง">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="bg-white rounded-2xl p-8 text-center text-slate-400 border border-slate-200">
                <i class="fas fa-inbox text-3xl mb-2 text-slate-300"></i>
                <p class="text-xs">ไม่พบรายการคำสั่งจองที่ตรงกับเงื่อนไข</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- DESKTOP VIEW: Full Orders Table (hidden md:block) -->
    <div class="hidden md:block bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-4">รหัสจอง</th>
                        <th class="py-3.5 px-4">วันที่ทำรายการ</th>
                        <th class="py-3.5 px-4">ผู้สั่งจอง / เบอร์โทร</th>
                        <th class="py-3.5 px-4">วิธีรับสินค้า</th>
                        <th class="py-3.5 px-4 text-center">จำนวน (ชิ้น/ตัว)</th>
                        <th class="py-3.5 px-4 text-right">ยอดสุทธิ</th>
                        <th class="py-3.5 px-4 text-center">สลิปโอน</th>
                        <th class="py-3.5 px-4 text-center">สถานะ</th>
                        <th class="py-3.5 px-4 text-right">การจัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $o): ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-blue-700">
                                    <?= htmlspecialchars($o['order_code']) ?>
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 text-[11px]">
                                    <?= Helper::thaiDate($o['created_at']) ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900"><?= htmlspecialchars($o['customer_name']) ?></div>
                                    <?php if (!empty($o['customer_organization'])): ?>
                                        <div class="text-[10px] text-blue-700 font-semibold truncate max-w-xs"><i class="fas fa-university text-[9px] mr-1 text-slate-400"></i><?= htmlspecialchars($o['customer_organization']) ?></div>
                                    <?php endif; ?>
                                    <div class="text-[11px] text-slate-500 font-mono">
                                        <a href="tel:<?= htmlspecialchars($o['customer_phone']) ?>" class="hover:text-blue-600">
                                            <?= htmlspecialchars($o['customer_phone']) ?>
                                        </a>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if ($o['delivery_method'] === 'delivery'): ?>
                                        <span class="inline-flex items-center text-indigo-700 font-bold">
                                            <i class="fas fa-shipping-fast mr-1"></i> จัดส่งพัสดุ
                                        </span>
                                        <?php if (!empty($o['shipping_address'])): ?>
                                            <div class="text-[10px] text-slate-500 mt-0.5 line-clamp-2 max-w-xs" title="<?= htmlspecialchars($o['shipping_address']) ?>">
                                                <i class="fas fa-map-marker-alt text-rose-500 mr-1"></i><?= htmlspecialchars($o['shipping_address']) ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="inline-flex items-center text-slate-600">
                                            <i class="fas fa-hand-holding-box mr-1"></i> รับเอง
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-center font-bold text-slate-800">
                                    <?= (int)$o['total_qty'] ?>
                                </td>
                                <td class="py-3.5 px-4 text-right font-black text-blue-600">
                                    ฿<?= Helper::money($o['total_amount']) ?>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <?php if (!empty($o['slip_image'])): ?>
                                        <button onclick="previewSlipModal('<?= base_url($o['slip_image']) ?>', '<?= htmlspecialchars($o['order_code']) ?>', '<?= Helper::money($o['total_amount']) ?>', '<?= Helper::thaiDate($o['transfer_datetime']) ?>', <?= $o['id'] ?>)" class="inline-flex items-center text-[11px] text-blue-600 hover:text-blue-800 font-bold bg-blue-50 px-2.5 py-1 rounded-lg">
                                            <i class="far fa-image mr-1"></i> ดูสลิป
                                        </button>
                                    <?php else: ?>
                                        <span class="text-slate-300">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <?= Helper::getStatusBadge($o['status']) ?>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center space-x-1.5">
                                        <?php if ($o['status'] === 'waiting_verification'): ?>
                                            <button onclick="actionVerify(<?= $o['id'] ?>, 'approve')" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] shadow-sm transition" title="อนุมัติการจอง">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button onclick="actionRejectModal(<?= $o['id'] ?>, '<?= htmlspecialchars($o['order_code']) ?>')" class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-[11px] transition" title="ปฏิเสธสลิป">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($o['status'] === 'confirmed'): ?>
                                            <a href="<?= base_url('voucher.php?code=' . urlencode($o['order_code'])) ?>" target="_blank" class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-[11px] transition" title="พิมพ์ใบรับสินค้า">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <form action="<?= base_url('admin/orders.php') ?>" method="POST" class="inline" onsubmit="return confirm('ยืนยันว่าลูกค้าได้รับสินค้าเรียบร้อยแล้ว?');">
                                                <?= Csrf::field() ?>
                                                <input type="hidden" name="action" value="complete_order">
                                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                                <button type="submit" class="px-2.5 py-1 rounded-lg bg-purple-50 hover:bg-purple-100 text-purple-700 font-bold text-[11px] transition" title="ทำเครื่องหมายว่ารับของแล้ว">
                                                    <i class="fas fa-box-check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <a href="<?= base_url('payment.php?code=' . urlencode($o['order_code'])) ?>" target="_blank" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition" title="ดูหน้าคำสั่งจอง">
                                            <i class="fas fa-external-link-alt text-[10px]"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-400">
                                <i class="fas fa-inbox text-4xl mb-2 text-slate-300"></i>
                                <p>ไม่พบรายการคำสั่งจองที่ตรงกับเงื่อนไข</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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
