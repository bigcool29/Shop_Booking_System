<?php
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helper.php';
require_once __DIR__ . '/../core/Csrf.php';
require_once __DIR__ . '/../core/Theme.php';

Auth::requireLogin();
$currentUser = Auth::user();

// Count orders waiting verification for badge
$pdo = Database::getInstance()->getConnection();
$pendingVerifyCount = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'waiting_verification'")->fetchColumn();

$flash = Helper::getFlash();
$activePage = $activePage ?? '';
?>
<!DOCTYPE html>
<html lang="th" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'ระบบจัดการร้านค้า - CMTC Admin') ?></title>
    <!-- Tailwind CSS CDN & Dynamic Theme Engine -->
    <script src="https://cdn.tailwindcss.com"></script>
    <?= Theme::renderHead() ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="<?= base_url('assets/css/custom.css') ?>">
</head>
<body class="min-h-screen text-slate-800 antialiased bg-slate-100 flex flex-col md:flex-row">

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div id="sidebarBackdrop" onclick="toggleSidebar()" class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm hidden md:hidden no-print"></div>

    <!-- Left Sidebar -->
    <aside id="adminSidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-slate-300 flex flex-col justify-between transition-transform duration-300 -translate-x-full md:translate-x-0 md:static md:min-h-screen shadow-xl md:shadow-none flex-shrink-0 no-print">
        
        <div>
            <!-- Sidebar Header / Brand -->
            <div class="h-16 flex items-center justify-between px-6 border-b border-slate-800 bg-slate-950/40">
                <a href="<?= base_url('admin/index.php') ?>" class="flex items-center space-x-3 group">
                    <?php 
                    $adminLogo = get_setting('site_logo', 'assets/images/cmtc_logo.jpg');
                    if (!empty($adminLogo) && file_exists(__DIR__ . '/../' . $adminLogo)): 
                    ?>
                        <img src="<?= base_url($adminLogo) ?>" alt="CMTC Logo" class="w-9 h-9 object-contain rounded-xl bg-white p-0.5 shadow-md group-hover:scale-105 transition border border-slate-700/50 flex-shrink-0">
                    <?php else: ?>
                        <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center font-black text-white shadow-md group-hover:scale-105 transition flex-shrink-0">
                            <i class="fas fa-tshirt"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <span class="font-extrabold text-sm tracking-tight text-white block leading-tight">CMTC Admin</span>
                        <span class="text-[10px] text-slate-400 block tracking-wider">ระบบจัดการจองเสื้อ</span>
                    </div>
                </a>

                <!-- Mobile Close Button -->
                <button onclick="toggleSidebar()" class="md:hidden text-slate-400 hover:text-white p-1 rounded-lg">
                    <i class="fas fa-times text-base"></i>
                </button>
            </div>

            <!-- Navigation Menu Items -->
            <nav class="p-4 space-y-6 text-xs">
                
                <!-- Group 1: การจัดการหลัก (Core) -->
                <div>
                    <div class="px-3 mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        เมนูหลัก (Main)
                    </div>
                    <div class="space-y-1">
                        <a href="<?= base_url('admin/index.php') ?>" 
                           class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium transition <?= $activePage === 'dashboard' ? 'bg-blue-600 text-white font-bold shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' ?>">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-chart-line w-4 text-center <?= $activePage === 'dashboard' ? 'text-white' : 'text-slate-400' ?>"></i>
                                <span>ภาพรวมแดชบอร์ด</span>
                            </div>
                        </a>

                        <a href="<?= base_url('admin/orders.php') ?>" 
                           class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium transition <?= $activePage === 'orders' ? 'bg-blue-600 text-white font-bold shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' ?>">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-clipboard-list w-4 text-center <?= $activePage === 'orders' ? 'text-white' : 'text-slate-400' ?>"></i>
                                <span>รายการสั่งจอง</span>
                            </div>
                            <?php if ($pendingVerifyCount > 0): ?>
                                <span class="bg-rose-500 text-white text-[10px] font-black px-2 py-0.5 rounded-full shadow-sm">
                                    <?= $pendingVerifyCount ?>
                                </span>
                            <?php endif; ?>
                        </a>

                        <a href="<?= base_url('admin/summary.php') ?>" 
                           class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium transition <?= $activePage === 'summary' ? 'bg-blue-600 text-white font-bold shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' ?>">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-industry w-4 text-center <?= $activePage === 'summary' ? 'text-white' : 'text-slate-400' ?>"></i>
                                <span>สรุปตัดยอดส่งโรงงาน</span>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Group 2: ข้อมูลสินค้าและรอบการจอง (Catalog) -->
                <div>
                    <div class="px-3 mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        สินค้า & รอบการจอง (Catalog)
                    </div>
                    <div class="space-y-1">
                        <a href="<?= base_url('admin/products.php') ?>" 
                           class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium transition <?= $activePage === 'products' ? 'bg-blue-600 text-white font-bold shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' ?>">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-box w-4 text-center <?= $activePage === 'products' ? 'text-white' : 'text-slate-400' ?>"></i>
                                <span>จัดการแบบเสื้อ & ไซส์</span>
                            </div>
                        </a>

                        <a href="<?= base_url('admin/rounds.php') ?>" 
                           class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium transition <?= $activePage === 'rounds' ? 'bg-blue-600 text-white font-bold shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' ?>">
                            <div class="flex items-center space-x-3">
                                <i class="far fa-calendar-check w-4 text-center <?= $activePage === 'rounds' ? 'text-white' : 'text-slate-400' ?>"></i>
                                <span>รอบการสั่งจอง</span>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Group 3: สิทธิ์และตั้งค่าระบบ (Superadmin Only) -->
                <?php if (Auth::isSuperAdmin()): ?>
                <div>
                    <div class="px-3 mb-2 text-[10px] font-bold uppercase tracking-wider text-purple-400 flex items-center justify-between">
                        <span>ผู้ดูแลระบบสูงสุด</span>
                        <i class="fas fa-shield-alt text-[9px]"></i>
                    </div>
                    <div class="space-y-1">
                        <a href="<?= base_url('admin/settings_brand.php') ?>" 
                           class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium transition <?= $activePage === 'settings_brand' ? 'bg-blue-600 text-white font-bold shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' ?>">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-stamp w-4 text-center <?= $activePage === 'settings_brand' ? 'text-white' : 'text-slate-400' ?>"></i>
                                <span>โลโก้และข้อความระบบ</span>
                            </div>
                        </a>

                        <a href="<?= base_url('admin/settings_theme.php') ?>" 
                           class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium transition <?= $activePage === 'settings_theme' ? 'bg-blue-600 text-white font-bold shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' ?>">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-palette w-4 text-center <?= $activePage === 'settings_theme' ? 'text-white' : 'text-slate-400' ?>"></i>
                                <span>ธีมและโทนสีของระบบ</span>
                            </div>
                        </a>

                        <a href="<?= base_url('admin/settings_payment.php') ?>" 
                           class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium transition <?= $activePage === 'settings_payment' ? 'bg-blue-600 text-white font-bold shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' ?>"
                           title="พร้อมเพย์ & การจัดส่ง (การล็อกสต็อกชั่วคราวและหมดอายุ (Cart Timeout))">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-qrcode w-4 text-center <?= $activePage === 'settings_payment' ? 'text-white' : 'text-slate-400' ?>"></i>
                                <div class="truncate">
                                    <span class="block">พร้อมเพย์ & การจัดส่ง</span>
                                    <span class="text-[9px] text-slate-400 block -mt-0.5 leading-tight truncate">ล็อกสต็อก (Cart Timeout)</span>
                                </div>
                            </div>
                        </a>

                        <a href="<?= base_url('admin/admins.php') ?>" 
                           class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium transition <?= $activePage === 'admins' ? 'bg-blue-600 text-white font-bold shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' ?>">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-users-cog w-4 text-center <?= $activePage === 'admins' ? 'text-white' : 'text-slate-400' ?>"></i>
                                <span>จัดการผู้ดูแลระบบ</span>
                            </div>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

            </nav>
        </div>

        <!-- Sidebar Bottom Footer: User Profile & Quick Link -->
        <div class="p-4 border-t border-slate-800 bg-slate-950/30 space-y-3">
            <a href="<?= base_url() ?>" target="_blank" class="flex items-center justify-between px-3 py-2 rounded-xl bg-slate-800/80 hover:bg-slate-800 text-slate-300 hover:text-white text-xs transition">
                <span class="flex items-center">
                    <i class="fas fa-external-link-alt mr-2 text-[10px] text-blue-400"></i> เปิดดูหน้าร้าน (Store)
                </span>
                <i class="fas fa-chevron-right text-[10px] text-slate-500"></i>
            </a>

            <!-- User Info Card -->
            <div class="flex items-center justify-between pt-2">
                <div class="flex items-center space-x-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-full bg-blue-600/30 border border-blue-500/50 text-blue-400 flex items-center justify-center font-bold text-xs flex-shrink-0">
                        <?= strtoupper(substr($currentUser['username'], 0, 1)) ?>
                    </div>
                    <div class="min-w-0">
                        <span class="font-bold text-white block text-xs truncate leading-tight"><?= htmlspecialchars($currentUser['fullname']) ?></span>
                        <span class="text-[10px] <?= $currentUser['role'] === 'superadmin' ? 'text-purple-400' : 'text-blue-400' ?> font-semibold uppercase tracking-wider block">
                            <?= htmlspecialchars($currentUser['role']) ?>
                        </span>
                    </div>
                </div>

                <a href="<?= base_url('admin/logout.php') ?>" class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-rose-900/60 text-slate-400 hover:text-rose-300 flex items-center justify-center transition flex-shrink-0" title="ออกจากระบบ">
                    <i class="fas fa-sign-out-alt text-xs"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content Container Wrapper -->
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        
        <!-- Top App Bar for Mobile & Quick Actions -->
        <header class="bg-white/95 backdrop-blur-md border-b border-slate-200 h-16 flex items-center justify-between px-3 sm:px-6 lg:px-8 sticky top-0 z-30 shadow-xs no-print">
            <div class="flex items-center space-x-2.5 min-w-0">
                <!-- Hamburger Button on Mobile -->
                <button type="button" onclick="toggleSidebar()" class="relative md:hidden text-slate-600 hover:text-slate-900 w-10 h-10 rounded-xl flex items-center justify-center hover:bg-slate-100 active:scale-95 transition flex-shrink-0" aria-label="Toggle Navigation">
                    <i class="fas fa-bars text-base"></i>
                    <?php if ($pendingVerifyCount > 0): ?>
                        <span class="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-rose-500 rounded-full ring-2 ring-white animate-pulse"></span>
                    <?php endif; ?>
                </button>
                <div class="min-w-0">
                    <h1 class="text-sm sm:text-base font-extrabold text-slate-900 truncate tracking-tight">
                        <?= htmlspecialchars($pageTitle ?? 'ระบบจัดการ') ?>
                    </h1>
                    <div class="text-[10px] text-slate-400 font-medium md:hidden truncate flex items-center">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 inline-block"></span>
                        CMTC Admin Online
                    </div>
                </div>
            </div>

            <!-- Right Controls -->
            <div class="flex items-center space-x-2 sm:space-x-3 flex-shrink-0">
                <?php if ($pendingVerifyCount > 0): ?>
                    <a href="<?= base_url('admin/orders.php?status=waiting_verification') ?>" class="md:hidden relative px-2.5 py-1.5 rounded-xl bg-amber-50 text-amber-700 text-xs font-bold border border-amber-200 flex items-center" title="สลิปรอตรวจสอบ">
                        <i class="fas fa-bell text-xs mr-1 animate-bounce"></i>
                        <span><?= $pendingVerifyCount ?></span>
                    </a>
                <?php endif; ?>
                <a href="<?= base_url() ?>" target="_blank" class="inline-flex items-center px-2.5 sm:px-3 py-1.5 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-bold transition shadow-xs">
                    <i class="fas fa-store sm:mr-1.5 text-xs"></i> <span class="hidden sm:inline">หน้าร้านค้า</span>
                </a>
                <div class="text-xs text-slate-500 hidden lg:block">
                    เข้าสู่ระบบโดย: <strong class="text-slate-800"><?= htmlspecialchars($currentUser['fullname']) ?></strong>
                </div>
            </div>
        </header>

        <!-- Flash message display -->
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

        <!-- Main Body Content with Safe Mobile Bottom Padding -->
        <main class="flex-1 pb-24 md:pb-8">
