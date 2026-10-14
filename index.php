<?php
$pageTitle = "ระบบจองเสื้อออนไลน์ - CMTC Official Store";
require_once __DIR__ . '/includes/header.php';

$pdo = Database::getInstance()->getConnection();

// 1. Get Active Booking Round
$roundStmt = $pdo->query("
    SELECT * FROM booking_rounds 
    WHERE is_active = 1 AND start_date <= NOW() AND (end_date IS NULL OR end_date >= NOW()) 
    ORDER BY id DESC LIMIT 1
");
$activeRound = $roundStmt->fetch();

// 2. Fetch Active Products with their sizes
$productsStmt = $pdo->query("
    SELECT p.*, 
           MIN(ps.price) as min_price, 
           MAX(ps.price) as max_price,
           SUM(ps.stock - ps.reserved_stock) as total_available_stock,
           MAX(ps.is_unlimited) as has_unlimited
    FROM products p
    LEFT JOIN product_sizes ps ON p.id = ps.product_id
    WHERE p.is_active = 1
    GROUP BY p.id
    ORDER BY p.sort_order ASC, p.id DESC
");
$products = $productsStmt->fetchAll();

// Fetch sizes for each product
$productSizes = [];
if (!empty($products)) {
    $sizesStmt = $pdo->query("
        SELECT * FROM product_sizes 
        ORDER BY product_id ASC, sort_order ASC, id ASC
    ");
    while ($row = $sizesStmt->fetch()) {
        $productSizes[$row['product_id']][] = $row;
    }
}

// 4 Canonical Categories
$categoriesDef = [
    'เสื้อ' => ['icon' => 'fa-tshirt', 'color' => 'bg-blue-600/90 text-white'],
    'แก้วน้ำ' => ['icon' => 'fa-mug-hot', 'color' => 'bg-amber-600/90 text-white'],
    'ของที่ระลึก' => ['icon' => 'fa-gift', 'color' => 'bg-purple-600/90 text-white'],
    'อื่นๆ' => ['icon' => 'fa-box-open', 'color' => 'bg-slate-700/90 text-white']
];
?>

<!-- Hero / Active Round Section -->
<div class="relative bg-gradient-to-br from-blue-900 via-indigo-900 to-slate-900 text-white overflow-hidden py-12 md:py-16">
    <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#38bdf8_1px,transparent_1px)] [background-size:16px_16px]"></div>
    
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl">
            <?php if ($activeRound): ?>
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 text-xs font-semibold mb-4 backdrop-blur-sm">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>กำลังเปิดรับคำสั่งจอง</span>
                </div>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight leading-tight mb-4">
                    <?= htmlspecialchars($activeRound['round_name']) ?>
                </h1>
                <p class="text-slate-300 text-sm sm:text-base mb-6 leading-relaxed">
                    <?= nl2br(htmlspecialchars($activeRound['description'] ?: 'เปิดรับจองเสื้อกิจกรรมและเสื้อรุ่นประจำปี สั่งจองล่วงหน้าเพื่อรับราคาพิเศษ ผลิตตามจำนวนคำสั่งซื้อ')) ?>
                </p>
                <div class="flex flex-wrap items-center gap-3 sm:gap-4 text-xs sm:text-sm text-slate-300">
                    <div class="flex items-center bg-white/10 px-3 py-1.5 rounded-lg backdrop-blur-sm">
                        <i class="far fa-calendar-alt text-amber-400 mr-2"></i>
                        <span>เริ่ม: <?= Helper::thaiDate($activeRound['start_date'], false) ?></span>
                    </div>
                    <div class="flex items-center bg-white/10 px-3 py-1.5 rounded-lg backdrop-blur-sm">
                        <i class="far fa-clock text-amber-400 mr-2"></i>
                        <span>สิ้นสุด: <?= !empty($activeRound['end_date']) ? Helper::thaiDate($activeRound['end_date'], false) : '<span class="text-emerald-300 font-bold"><i class="fas fa-infinity mr-1"></i> ไม่จำกัด (เปิดตลอด)</span>' ?></span>
                    </div>
                    <a href="<?= base_url('guide.php') ?>" class="inline-flex items-center bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-400/30 px-3 py-1.5 rounded-lg backdrop-blur-sm font-bold transition">
                        <i class="fas fa-book-open mr-1.5"></i> วิธีการสั่งจอง
                    </a>
                </div>
            <?php else: ?>
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-amber-500/20 border border-amber-400/30 text-amber-300 text-xs font-semibold mb-4">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>ยังไม่เปิดรอบการจองในขณะนี้</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight mb-4">
                    ระบบสั่งจองเสื้อออนไลน์ CMTC
                </h1>
                <p class="text-slate-300 text-sm sm:text-base">
                    ขณะนี้ยังไม่มีรอบการสั่งจองที่เปิดรับ กรุณาติดตามประกาศกำหนดการเปิดรับจองรอบถัดไปจากทางวิทยาลัย
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Workflow Stepper Guide -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6 relative z-10">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5 md:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 mb-4 border-b border-slate-100 gap-2">
            <div class="flex items-center space-x-2">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-600 animate-pulse"></span>
                <span class="text-xs font-black text-slate-900 tracking-tight">4 ขั้นตอนการสั่งจองสินค้าออนไลน์</span>
            </div>
            <a href="<?= base_url('guide.php') ?>" class="text-xs text-blue-600 hover:text-blue-800 font-bold flex items-center group">
                <span>อ่านคู่มือและขั้นตอนการสั่งจอง</span>
                <i class="fas fa-arrow-right ml-1.5 group-hover:translate-x-0.5 transition-transform text-[10px]"></i>
            </a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="<?= base_url('guide.php') ?>" class="flex items-center space-x-3.5 p-2 rounded-xl hover:bg-slate-50 transition group">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition flex items-center justify-center font-bold text-base flex-shrink-0 shadow-2xs">
                    1
                </div>
                <div>
                    <h4 class="text-xs font-bold text-slate-800 group-hover:text-blue-600 transition">เลือกแบบและขนาดเสื้อ</h4>
                    <p class="text-[11px] text-slate-500">เลือกแบบ ไซส์ และจำนวนที่ต้องการ</p>
                </div>
            </a>

            <a href="<?= base_url('guide.php') ?>" class="flex items-center space-x-3.5 p-2 rounded-xl hover:bg-slate-50 transition group">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition flex items-center justify-center font-bold text-base flex-shrink-0 shadow-2xs">
                    2
                </div>
                <div>
                    <h4 class="text-xs font-bold text-slate-800 group-hover:text-indigo-600 transition">กรอกข้อมูลผู้จอง</h4>
                    <p class="text-[11px] text-slate-500">เบอร์โทรศัพท์ และเลือกวิธีรับสินค้า</p>
                </div>
            </a>

            <a href="<?= base_url('guide.php') ?>" class="flex items-center space-x-3.5 p-2 rounded-xl hover:bg-slate-50 transition group">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 group-hover:bg-amber-600 group-hover:text-white transition flex items-center justify-center font-bold text-base flex-shrink-0 shadow-2xs">
                    3
                </div>
                <div>
                    <h4 class="text-xs font-bold text-slate-800 group-hover:text-amber-600 transition">สแกนจ่าย PromptPay</h4>
                    <p class="text-[11px] text-slate-500">QR Code พร้อมยอดเงิน + แนบสลิป</p>
                </div>
            </a>

            <a href="<?= base_url('guide.php') ?>" class="flex items-center space-x-3.5 p-2 rounded-xl hover:bg-slate-50 transition group">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition flex items-center justify-center font-bold text-base flex-shrink-0 shadow-2xs">
                    4
                </div>
                <div>
                    <h4 class="text-xs font-bold text-slate-800 group-hover:text-emerald-600 transition">รับใบยืนยันการจอง</h4>
                    <p class="text-[11px] text-slate-500">พิมพ์หรือแคปหน้าจอเพื่อรับเสื้อ</p>
                </div>
            </a>
        </div>
    </div>
</div>

<!-- Main Products Section -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-10">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-200 gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">รายการสินค้าที่เปิดรับจอง</h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">เสื้อกิจกรรม แก้วน้ำ และของที่ระลึกทางการวิทยาลัยเทคนิคเชียงใหม่</p>
        </div>
        <div>
            <button onclick="openSizeModal()" class="inline-flex items-center text-xs font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100 px-3.5 py-2 rounded-xl transition">
                <i class="fas fa-ruler-combined mr-2"></i> ตารางขนาดเสื้อ (Size Chart)
            </button>
        </div>
    </div>

    <!-- Category Filter Tabs -->
    <div class="flex items-center space-x-2 overflow-x-auto py-4 scrollbar-none">
        <button type="button" onclick="filterCategory('all', this)" class="cat-pill active px-4 py-2 rounded-xl text-xs font-bold transition flex items-center whitespace-nowrap bg-blue-600 text-white shadow-sm">
            <i class="fas fa-th-large mr-1.5"></i> ทั้งหมด (<?= count($products) ?>)
        </button>
        <?php foreach ($categoriesDef as $catName => $cMeta): ?>
            <?php 
                $catCount = 0;
                foreach ($products as $p) { if ($p['category'] === $catName) $catCount++; }
            ?>
            <button type="button" onclick="filterCategory('<?= htmlspecialchars($catName, ENT_QUOTES) ?>', this)" class="cat-pill px-4 py-2 rounded-xl text-xs font-bold transition flex items-center whitespace-nowrap bg-white border border-slate-200 text-slate-600 hover:bg-slate-50">
                <i class="fas <?= $cMeta['icon'] ?> mr-1.5 text-slate-400"></i> <?= htmlspecialchars($catName) ?> (<?= $catCount ?>)
            </button>
        <?php endforeach; ?>
    </div>

    <!-- Products Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8 mt-4" id="productsGridContainer">
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $p): ?>
                <?php 
                    $sizes = $productSizes[$p['id']] ?? []; 
                    $hasUnlimited = !empty($p['has_unlimited']);
                    $available = (int)$p['total_available_stock'];
                    $isSoldOut = !$hasUnlimited && ($available <= 0);
                    $cMeta = $categoriesDef[$p['category']] ?? ['icon' => 'fa-tag', 'color' => 'bg-slate-900/80 text-white'];
                    $isClothing = ($p['category'] === 'เสื้อ');
                ?>
                <div class="product-card bg-white rounded-2xl border border-slate-200 overflow-hidden flex flex-col hover-lift transition duration-300" data-category="<?= htmlspecialchars($p['category']) ?>">
                    <!-- Product Image -->
                    <a href="<?= base_url('product_detail.php?id=' . $p['id']) ?>" 
                       title="ดูรายละเอียด <?= htmlspecialchars($p['name']) ?>"
                       class="block relative bg-gradient-to-b from-slate-50 via-white to-slate-100/70 aspect-square overflow-hidden group flex items-center justify-center p-4">
                        <img src="<?= base_url($p['main_image'] ?: 'assets/uploads/products/polo_87_main.jpg') ?>" 
                             alt="<?= htmlspecialchars($p['name']) ?>" 
                             loading="lazy"
                             class="w-full h-full max-h-full max-w-full object-contain object-center group-hover:scale-105 transition-transform duration-500 ease-out select-none drop-shadow-sm">
                        <div class="absolute top-3 left-3 z-10">
                            <span class="<?= $cMeta['color'] ?> backdrop-blur-md text-[11px] font-semibold px-2.5 py-1 rounded-lg inline-flex items-center shadow-xs">
                                <i class="fas <?= $cMeta['icon'] ?> mr-1"></i> <?= htmlspecialchars($p['category']) ?>
                            </span>
                        </div>
                        <?php if ($isSoldOut): ?>
                            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-10">
                                <span class="bg-rose-600 text-white font-bold text-xs px-3 py-1.5 rounded-full uppercase tracking-wider">
                                    สินค้าหมดชั่วคราว
                                </span>
                            </div>
                        <?php endif; ?>
                    </a>

                    <!-- Product Info -->
                    <div class="p-5 flex-1 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between text-xs text-slate-400 mb-1">
                                <span>รหัส: <?= htmlspecialchars($p['code']) ?></span>
                                <?php if ($hasUnlimited): ?>
                                    <span class="text-blue-600 font-semibold">
                                        <i class="fas fa-infinity mr-1"></i> ผลิตตามยอดจอง (ไม่จำกัด)
                                    </span>
                                <?php else: ?>
                                    <span class="<?= $available > 10 ? 'text-emerald-600' : ($available > 0 ? 'text-amber-600' : 'text-rose-600') ?> font-medium">
                                        <i class="fas fa-cubes mr-1"></i> เหลือพร้อมจอง <?= $available ?> <?= $isClothing ? 'ตัว' : 'ชิ้น' ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h3 class="font-bold text-base text-slate-900 leading-snug line-clamp-2 mb-2">
                                <a href="<?= base_url('product_detail.php?id=' . $p['id']) ?>" class="hover:text-blue-600 transition">
                                    <?= htmlspecialchars($p['name']) ?>
                                </a>
                            </h3>
                            <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed mb-4">
                                <?= htmlspecialchars($p['description']) ?>
                            </p>

                            <!-- Size / Option Badges -->
                            <div class="mb-4">
                                <div class="text-[11px] font-semibold text-slate-500 mb-1.5">
                                    <?= $isClothing ? 'ขนาดไซส์เสื้อ:' : 'แบบ / ตัวเลือก:' ?>
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    <?php foreach ($sizes as $s): ?>
                                        <?php 
                                            $sIsUnlim = !empty($s['is_unlimited']);
                                            $sAvail = (int)($s['stock'] - $s['reserved_stock']); 
                                            $sActive = $sIsUnlim || ($sAvail > 0);
                                            $hasChest = (!empty($s['chest_inch']) && $s['chest_inch'] > 0);
                                            $tooltip = $hasChest ? ('อก ' . $s['chest_inch'] . ' นิ้ว') : ('ตัวเลือก: ' . $s['size_name']);
                                            if ($sIsUnlim) {
                                                $tooltip .= ' / ผลิตตามยอดจอง (ไม่จำกัด)';
                                            } else {
                                                $tooltip .= ' / คงเหลือ ' . $sAvail;
                                            }
                                        ?>
                                        <span class="text-xs px-2 py-0.5 rounded-md font-medium border <?= $sActive ? 'bg-slate-50 border-slate-200 text-slate-700' : 'bg-slate-100 border-slate-200 text-slate-400 line-through' ?>" title="<?= htmlspecialchars($tooltip) ?>">
                                            <?= htmlspecialchars($s['size_name']) ?><?= $sIsUnlim ? ' <span class="text-[10px] text-blue-600 font-bold">∞</span>' : '' ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Price and Action -->
                        <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                            <div>
                                <span class="text-[11px] text-slate-400 block">ราคาเริ่มต้น</span>
                                <span class="text-lg font-black text-blue-600">
                                    ฿<?= Helper::money($p['min_price']) ?>
                                    <?php if ($p['min_price'] != $p['max_price']): ?>
                                        <span class="text-xs font-normal text-slate-400">- ฿<?= Helper::money($p['max_price']) ?></span>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <a href="<?= base_url('product_detail.php?id=' . $p['id']) ?>" 
                               class="inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-sm hover:shadow transition active:scale-95">
                                <span>สั่งจองสินค้า</span>
                                <i class="fas fa-arrow-right ml-1.5"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full py-16 text-center text-slate-500">
                <i class="fas fa-box-open text-5xl text-slate-300 mb-3"></i>
                <p>ยังไม่มีรายการสินค้าในขณะนี้</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Guide Banner Callout -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-12 mb-16">
    <div class="rounded-3xl bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 text-white p-6 sm:p-8 lg:p-10 shadow-lg relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#38bdf8_1px,transparent_1px)] [background-size:16px_16px]"></div>
        <div class="relative z-10 max-w-2xl">
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-blue-500/20 text-blue-200 border border-blue-400/30 text-xs font-bold mb-3 backdrop-blur-sm">
                <i class="fas fa-book-reader text-amber-300"></i>
                <span>คู่มือการใช้งาน • CMTC BOOKING GUIDE</span>
            </div>
            <h3 class="text-xl sm:text-2xl lg:text-3xl font-black tracking-tight mb-2">
                ต้องการทราบขั้นตอนการสั่งจองและรายละเอียดเพิ่มเติม?
            </h3>
            <p class="text-slate-300 text-xs sm:text-sm leading-relaxed">
                ดูขั้นตอนการสั่งจอง 4 ขั้นตอน ตารางเทียบขนาดรอบอกเสื้อ การคำนวณค่าจัดส่ง และคำถามที่พบบ่อยได้ในหน้าคู่มือฉบับเต็ม
            </p>
        </div>
        <div class="relative z-10 flex-shrink-0 flex flex-wrap gap-3">
            <a href="<?= base_url('guide.php') ?>" class="inline-flex items-center px-5 py-3 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs sm:text-sm shadow-md transition active:scale-95">
                <i class="fas fa-book-open mr-2"></i> ไปยังหน้าคู่มือและขั้นตอนการสั่งจอง
            </a>
            <button type="button" onclick="openSizeModal()" class="inline-flex items-center px-4 py-3 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs sm:text-sm backdrop-blur-sm border border-white/20 transition">
                <i class="fas fa-ruler-combined mr-2 text-slate-300"></i> ตรวจสอบตารางไซส์เสื้อ
            </button>
        </div>
    </div>
</section>

<!-- Modal: Size Chart -->
<div id="sizeModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm hidden p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl relative">
        <button onclick="closeSizeModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 w-8 h-8 rounded-full flex items-center justify-center hover:bg-slate-100 transition">
            <i class="fas fa-times"></i>
        </button>
        <div class="flex items-center space-x-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                <i class="fas fa-ruler-combined"></i>
            </div>
            <div>
                <h3 class="font-bold text-slate-900 text-base">ตารางขนาดเสื้อมาตรฐาน (Size Chart)</h3>
                <p class="text-xs text-slate-500">วัดขนาดรอบอกและความยาวเป็นหน่วยนิ้ว (Inches)</p>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200 mb-4">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-2.5">ขนาด (Size)</th>
                        <th class="px-4 py-2.5">รอบอก (Chest)</th>
                        <th class="px-4 py-2.5">ความยาว (Length)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-600 font-medium">
                    <tr>
                        <td class="px-4 py-2.5 font-bold text-slate-900">S</td>
                        <td class="px-4 py-2.5">34 - 36 นิ้ว</td>
                        <td class="px-4 py-2.5">25 - 26 นิ้ว</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 font-bold text-slate-900">M</td>
                        <td class="px-4 py-2.5">38 นิ้ว</td>
                        <td class="px-4 py-2.5">27 นิ้ว</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 font-bold text-slate-900">L</td>
                        <td class="px-4 py-2.5">40 - 42 นิ้ว</td>
                        <td class="px-4 py-2.5">28 - 29 นิ้ว</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 font-bold text-slate-900">XL</td>
                        <td class="px-4 py-2.5">42 - 44 นิ้ว</td>
                        <td class="px-4 py-2.5">29 - 30 นิ้ว</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 font-bold text-slate-900">2XL</td>
                        <td class="px-4 py-2.5">44 - 46 นิ้ว</td>
                        <td class="px-4 py-2.5">30 - 31 นิ้ว</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 font-bold text-slate-900">3XL</td>
                        <td class="px-4 py-2.5">46 - 48 นิ้ว</td>
                        <td class="px-4 py-2.5">31 - 32 นิ้ว</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="text-[11px] text-slate-400 italic">
            * หมายเหตุ: ขนาดจริงอาจมีความคลาดเคลื่อนประมาณ +/- 0.5 นิ้ว เนื่องจากการตัดเย็บ
        </p>

        <div class="mt-5 text-right">
            <button onclick="closeSizeModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                ปิดหน้าต่าง
            </button>
        </div>
    </div>
</div>

<script>
function openSizeModal() {
    document.getElementById('sizeModal').classList.remove('hidden');
}
function closeSizeModal() {
    document.getElementById('sizeModal').classList.add('hidden');
}

function filterCategory(cat, btn) {
    document.querySelectorAll('.cat-pill').forEach(b => {
        b.className = 'cat-pill px-4 py-2 rounded-xl text-xs font-bold transition flex items-center whitespace-nowrap bg-white border border-slate-200 text-slate-600 hover:bg-slate-50';
    });
    btn.className = 'cat-pill active px-4 py-2 rounded-xl text-xs font-bold transition flex items-center whitespace-nowrap bg-blue-600 text-white shadow-sm';

    const cards = document.querySelectorAll('.product-card');
    cards.forEach(card => {
        if (cat === 'all' || card.dataset.category === cat) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
