<?php
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../core/Helper.php';
require_once __DIR__ . '/../core/Theme.php';
require_once __DIR__ . '/../core/Csrf.php';

// Passive auto-cleanup of expired orders
require_once __DIR__ . '/../core/OrderManager.php';
OrderManager::cleanupExpiredOrders();

$activePendingOrder = OrderManager::getActivePendingOrder();
$isCurrentPaymentPage = ($activePendingOrder && basename($_SERVER['PHP_SELF']) === 'payment.php' && ($_GET['code'] ?? '') === $activePendingOrder['order_code']);

$cartCount = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int)($item['quantity'] ?? 0);
    }
}

$pageTitle = $pageTitle ?? get_setting('site_title', 'ระบบจองเสื้อออนไลน์ - CMTC');
$flash = Helper::getFlash();
?>
<!DOCTYPE html>
<html lang="th" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <!-- Tailwind CSS CDN & Dynamic Theme Engine -->
    <script src="https://cdn.tailwindcss.com"></script>
    <?= Theme::renderHead() ?>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/custom.css') ?>">
</head>
<body class="flex flex-col min-h-screen text-slate-800 antialiased">

    <!-- Top Notice / Announcement Bar -->
    <div class="bg-gradient-to-r from-blue-900 via-indigo-900 to-blue-950 text-white text-xs py-2 px-4 shadow-sm no-print">
        <div class="max-w-7xl mx-auto flex flex-wrap justify-between items-center gap-2">
            <div class="flex items-center space-x-2">
                <span class="bg-amber-500 text-slate-900 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">Official</span>
                <span class="text-slate-200">ระบบสั่งจองเสื้อกิจกรรมและเสื้อที่ระลึก วิทยาลัยเทคนิคเชียงใหม่</span>
            </div>
            <div class="flex items-center space-x-4 text-slate-300">
                <span><i class="fas fa-phone-alt mr-1 text-amber-400"></i> <?= htmlspecialchars(get_setting('contact_phone', '053-217-708')) ?></span>
                <a href="<?= base_url('admin/login.php') ?>" class="hover:text-white transition flex items-center">
                    <i class="fas fa-user-shield mr-1"></i> เจ้าหน้าที่
                </a>
            </div>
        </div>
    </div>

    <!-- Main Navigation Bar -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-sm no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo & Brand Name -->
                <div class="flex items-center">
                    <a href="<?= base_url() ?>" class="flex items-center space-x-3 group">
                        <?php 
                        $headerLogo = get_setting('site_logo', 'assets/images/cmtc_logo.jpg');
                        if (!empty($headerLogo) && file_exists(__DIR__ . '/../' . $headerLogo)): 
                        ?>
                            <img src="<?= base_url($headerLogo) ?>" alt="CMTC Logo" class="w-10 h-10 object-contain rounded-xl p-0.5 bg-white shadow-md border border-slate-200 group-hover:scale-105 transition flex-shrink-0">
                        <?php else: ?>
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-800 text-white flex items-center justify-center font-black text-xl shadow-md group-hover:scale-105 transition flex-shrink-0">
                                <i class="fas fa-tshirt"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <div class="font-bold text-lg text-slate-900 leading-tight flex items-center gap-2">
                                CMTC Shop <span class="text-xs bg-blue-100 text-blue-700 font-semibold px-2 py-0.5 rounded-full">Pre-Order</span>
                            </div>
                            <div class="text-[11px] text-slate-500 tracking-wide">Chiang Mai Technical College</div>
                        </div>
                    </a>
                </div>

                <!-- Desktop Nav Links -->
                <nav class="hidden md:flex items-center space-x-1 lg:space-x-3">
                    <a href="<?= base_url() ?>" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:text-blue-600 hover:bg-slate-50 transition">
                        <i class="fas fa-store mr-1 text-slate-400"></i> หน้าสั่งจอง
                    </a>
                    <a href="<?= base_url('guide.php') ?>" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:text-blue-600 hover:bg-slate-50 transition">
                        <i class="fas fa-book-open mr-1 text-slate-400"></i> วิธีการสั่งจอง
                    </a>
                    <a href="<?= base_url('track.php') ?>" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:text-blue-600 hover:bg-slate-50 transition">
                        <i class="fas fa-search mr-1 text-slate-400"></i> ตรวจสอบสถานะคำสั่งจอง
                    </a>
                </nav>

                <!-- Actions: Pending Order Button & Cart Button -->
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <?php if ($activePendingOrder): ?>
                        <a href="<?= base_url('payment.php?code=' . urlencode($activePendingOrder['order_code'])) ?>" 
                           class="relative inline-flex items-center px-3 sm:px-3.5 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-bold text-xs sm:text-sm rounded-xl shadow-sm hover:shadow-md transition active:scale-95 group border border-amber-400/60"
                           title="ไปยังหน้าชำระเงินและแนบสลิป">
                            <span class="relative flex h-2 w-2 mr-1.5 sm:mr-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-500 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-600"></span>
                            </span>
                            <i class="fas fa-file-invoice-dollar text-slate-900 mr-1 sm:mr-1.5 text-xs sm:text-sm"></i>
                            <span class="hidden sm:inline">รอชำระเงิน</span>
                            <span class="sm:hidden">ชำระ</span>
                            <span id="navPendingCountdown" class="ml-1 sm:ml-1.5 bg-slate-950 text-amber-300 font-mono text-[10px] sm:text-[11px] font-bold px-1.5 py-0.5 rounded shadow-inner">--:--</span>
                        </a>
                    <?php endif; ?>

                    <a href="<?= base_url('cart.php') ?>" class="relative inline-flex items-center px-3.5 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-semibold rounded-xl shadow-sm hover:shadow-md transition active:scale-95">
                        <i class="fas fa-shopping-bag mr-1.5 sm:mr-2"></i>
                        <span class="hidden sm:inline">ตะกร้าสินค้า</span>
                        <span class="sm:hidden">ตะกร้า</span>
                        <span id="navCartBadge" class="<?= $cartCount > 0 ? '' : 'hidden' ?> ml-1.5 sm:ml-2 bg-amber-400 text-slate-900 text-xs font-black px-2 py-0.5 rounded-full">
                            <?= $cartCount ?>
                        </span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Mobile Quick Navigation Bar -->
        <div class="md:hidden border-t border-slate-100 bg-slate-50/80 backdrop-blur px-4 py-2 flex items-center justify-around text-xs no-print">
            <a href="<?= base_url() ?>" class="flex items-center gap-1.5 py-1 px-2 rounded-lg font-medium text-slate-700 hover:text-blue-600 hover:bg-white transition">
                <i class="fas fa-store text-blue-600"></i>
                <span>หน้าสั่งจอง</span>
            </a>
            <a href="<?= base_url('guide.php') ?>" class="flex items-center gap-1.5 py-1 px-2 rounded-lg font-medium text-slate-700 hover:text-blue-600 hover:bg-white transition">
                <i class="fas fa-book-open text-blue-600"></i>
                <span>วิธีการสั่งจอง</span>
            </a>
            <a href="<?= base_url('track.php') ?>" class="flex items-center gap-1.5 py-1 px-2 rounded-lg font-medium text-slate-700 hover:text-blue-600 hover:bg-white transition">
                <i class="fas fa-search text-blue-600"></i>
                <span>ตรวจสอบสถานะ</span>
            </a>
        </div>
    </header>

    <?php if ($activePendingOrder && !$isCurrentPaymentPage): ?>
    <!-- Active Pending Order Sticky Notice Banner -->
    <div id="activePendingOrderBanner" class="bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 text-slate-950 px-4 py-2.5 shadow-md border-b border-amber-600/30 no-print transition-all">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3 text-xs sm:text-sm">
            <div class="flex items-center space-x-2.5 text-center sm:text-left">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-xl bg-slate-950 text-amber-300 text-xs font-black shrink-0 shadow-sm animate-bounce">
                    <i class="fas fa-clock"></i>
                </span>
                <div class="leading-relaxed">
                    <span class="font-bold">ท่านมีรายการสั่งจองค้างชำระ:</span>
                    <span class="font-mono font-black text-slate-900 bg-amber-300/80 px-1.5 py-0.5 rounded"><?= htmlspecialchars($activePendingOrder['order_code']) ?></span>
                    <span class="font-bold text-slate-900">(฿<?= Helper::money($activePendingOrder['total_amount']) ?>)</span>
                    <span class="text-slate-800 ml-1">เหลือเวลาอีก:</span>
                    <span id="headerPendingCountdown" class="font-mono font-bold bg-slate-950 text-amber-300 px-2 py-0.5 rounded ml-0.5 shadow-inner">--:--</span>
                </div>
            </div>
            <div class="flex items-center space-x-2 shrink-0">
                <a href="<?= base_url('payment.php?code=' . urlencode($activePendingOrder['order_code'])) ?>" class="inline-flex items-center px-4 py-1.5 bg-slate-950 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-md transition active:scale-95">
                    <i class="fas fa-receipt mr-1.5 text-amber-400"></i> ไปที่หน้าชำระเงินและแนบสลิป
                </a>
                <button type="button" onclick="cancelPendingOrderGlobal('<?= htmlspecialchars($activePendingOrder['order_code']) ?>')" class="inline-flex items-center px-2.5 py-1.5 bg-rose-600/90 hover:bg-rose-700 text-white font-semibold text-xs rounded-xl transition shadow" title="ยกเลิกคำสั่งจองนี้">
                    <i class="fas fa-times mr-1"></i> ยกเลิก
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($activePendingOrder): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const expTimestamp = <?= strtotime($activePendingOrder['expires_at']) ?>;
        function updatePendingTimers() {
            const now = Math.floor(Date.now() / 1000);
            const diff = expTimestamp - now;
            const navEl = document.getElementById('navPendingCountdown');
            const bannerEl = document.getElementById('headerPendingCountdown');

            if (diff <= 0) {
                if (navEl) navEl.textContent = 'หมดเวลา';
                if (bannerEl) bannerEl.textContent = 'หมดเวลา';
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
                return;
            }

            const mins = Math.floor(diff / 60);
            const secs = diff % 60;
            const formatted = (mins < 10 ? '0' : '') + mins + ':' + (secs < 10 ? '0' : '') + secs;
            if (navEl) navEl.textContent = formatted;
            if (bannerEl) bannerEl.textContent = formatted;
        }

        updatePendingTimers();
        setInterval(updatePendingTimers, 1000);
    });

    function cancelPendingOrderGlobal(orderCode) {
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
    </script>
    <?php endif; ?>

    <!-- Flash Notification Display (if any) -->
    <?php if ($flash): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: <?= json_encode($flash['icon']) ?>,
                title: <?= json_encode($flash['title']) ?>,
                text: <?= json_encode($flash['text']) ?>,
                confirmButtonColor: '#2563eb',
                timer: 3500
            });
        });
    </script>
    <?php endif; ?>

    <!-- Main Content Area -->
    <main class="flex-1 pb-16">
