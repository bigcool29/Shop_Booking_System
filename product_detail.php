<?php
require_once __DIR__ . '/config/settings.php';
require_once __DIR__ . '/core/Helper.php';
require_once __DIR__ . '/core/Csrf.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: " . base_url());
    exit;
}

$pdo = Database::getInstance()->getConnection();

// Fetch Product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    Helper::setFlash('error', 'ไม่พบสินค้า', 'ไม่พบสินค้าที่ท่านต้องการ หรือสินค้านี้ปิดรับการจองแล้ว');
    header("Location: " . base_url());
    exit;
}

// Fetch Gallery Images
$galleryStmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC");
$galleryStmt->execute([$id]);
$gallery = $galleryStmt->fetchAll();

// Fetch Sizes
$sizesStmt = $pdo->prepare("SELECT * FROM product_sizes WHERE product_id = ? ORDER BY sort_order ASC, id ASC");
$sizesStmt->execute([$id]);
$sizes = $sizesStmt->fetchAll();

$pageTitle = $product['name'] . " - CMTC Shop";

// Category configurations
$isClothing = ($product['category'] === 'เสื้อ');
$isMug = ($product['category'] === 'แก้วน้ำ');
$isSouvenir = ($product['category'] === 'ของที่ระลึก');

$catConfig = [
    'เสื้อ' => ['icon' => 'fa-tshirt', 'color' => 'bg-blue-600/90 text-white', 'unit' => 'ตัว', 'label' => 'เลือกขนาดไซส์เสื้อ (Size)'],
    'แก้วน้ำ' => ['icon' => 'fa-mug-hot', 'color' => 'bg-amber-600/90 text-white', 'unit' => 'ใบ', 'label' => 'เลือกรุ่น / ความจุ / สีแก้วน้ำ (Options)'],
    'ของที่ระลึก' => ['icon' => 'fa-gift', 'color' => 'bg-purple-600/90 text-white', 'unit' => 'ชิ้น', 'label' => 'เลือกแบบ / ตัวเลือกสินค้า (Options)'],
    'อื่นๆ' => ['icon' => 'fa-box-open', 'color' => 'bg-slate-700/90 text-white', 'unit' => 'ชิ้น', 'label' => 'เลือกแบบ / ตัวเลือกสินค้า (Options)']
];
$currentCatConfig = $catConfig[$product['category']] ?? ['icon' => 'fa-tag', 'color' => 'bg-slate-900/80 text-white', 'unit' => 'ชิ้น', 'label' => 'เลือกตัวเลือกสินค้า (Options)'];

// Collect all product images for the lightbox slider
$allProductImages = [];
if (!empty($product['main_image'])) {
    $allProductImages[] = base_url($product['main_image']);
} else {
    $allProductImages[] = base_url('assets/uploads/products/polo_87_main.jpg');
}
foreach ($gallery as $g) {
    if (!empty($g['image_path'])) {
        $allProductImages[] = base_url($g['image_path']);
    }
}

// Fetch All Active Products for the bottom showcase (if enabled in settings_theme.php)
$showAllProductsSetting = get_setting('show_all_products_in_detail', '1');
$showAllProducts = ($showAllProductsSetting === '1' || $showAllProductsSetting === 'yes');

$allProducts = [];
$allProductSizes = [];

if ($showAllProducts) {
    $allProductsStmt = $pdo->query("
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
    $allProducts = $allProductsStmt->fetchAll();

    // Fetch sizes for each product
    if (!empty($allProducts)) {
        $allSizesStmt = $pdo->query("
            SELECT * FROM product_sizes 
            ORDER BY product_id ASC, sort_order ASC, id ASC
        ");
        while ($row = $allSizesStmt->fetch()) {
            $allProductSizes[$row['product_id']][] = $row;
        }
    }
}

// 4 Canonical Categories
$categoriesDef = [
    'เสื้อ' => ['icon' => 'fa-tshirt', 'color' => 'bg-blue-600/90 text-white'],
    'แก้วน้ำ' => ['icon' => 'fa-mug-hot', 'color' => 'bg-amber-600/90 text-white'],
    'ของที่ระลึก' => ['icon' => 'fa-gift', 'color' => 'bg-purple-600/90 text-white'],
    'อื่นๆ' => ['icon' => 'fa-box-open', 'color' => 'bg-slate-700/90 text-white']
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12" id="top">
    <!-- Breadcrumb -->
    <nav class="flex items-center space-x-2 text-xs text-slate-500 mb-6">
        <a href="<?= base_url() ?>" class="hover:text-blue-600 transition">หน้าแรก</a>
        <i class="fas fa-chevron-right text-[10px] text-slate-300"></i>
        <span class="text-slate-400"><?= htmlspecialchars($product['category']) ?></span>
        <i class="fas fa-chevron-right text-[10px] text-slate-300"></i>
        <span class="text-slate-800 font-medium truncate max-w-xs"><?= htmlspecialchars($product['name']) ?></span>
    </nav>

    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8 lg:p-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">
            
            <!-- Left: Images Column -->
            <div class="lg:col-span-5 space-y-4">
                <!-- Main Product Image Frame -->
                <div class="aspect-square bg-gradient-to-b from-slate-50 via-white to-slate-100/70 rounded-3xl overflow-hidden border border-slate-200/80 flex items-center justify-center relative p-6 sm:p-8 group shadow-xs">
                    <img id="mainDisplayImage" 
                         src="<?= base_url($product['main_image'] ?: 'assets/uploads/products/polo_87_main.jpg') ?>" 
                         alt="<?= htmlspecialchars($product['name']) ?>" 
                         onclick="openImageLightbox(this.src)"
                         class="w-full h-full max-h-full max-w-full object-contain object-center transition-all duration-300 drop-shadow-md select-none group-hover:scale-105 cursor-zoom-in">
                    
                    <span class="absolute top-4 left-4 <?= $currentCatConfig['color'] ?> backdrop-blur-md text-xs font-semibold px-3 py-1 rounded-xl inline-flex items-center shadow-xs pointer-events-none">
                        <i class="fas <?= $currentCatConfig['icon'] ?> mr-1.5"></i> <?= htmlspecialchars($product['category']) ?>
                    </span>

                    <button type="button" 
                            onclick="openImageLightbox(document.getElementById('mainDisplayImage').src)" 
                            class="absolute bottom-4 right-4 w-9 h-9 rounded-xl bg-white/90 backdrop-blur-md border border-slate-200/80 text-slate-500 hover:text-blue-600 hover:bg-white shadow-xs flex items-center justify-center transition opacity-80 group-hover:opacity-100" 
                            title="คลิกเพื่อดูรูปภาพขนาดเต็ม">
                        <i class="fas fa-search-plus text-xs"></i>
                    </button>
                </div>

                <!-- Gallery Thumbnails (if any) -->
                <?php if (!empty($gallery)): ?>
                    <div class="space-y-2">
                        <span class="text-[11px] font-semibold text-slate-400 block">
                            <i class="far fa-images mr-1"></i> ภาพสินค้าเพิ่มเติม (<?= count($gallery) + 1 ?> รูป):
                        </span>
                        <div class="flex items-center space-x-2.5 overflow-x-auto pb-2 scrollbar-thin">
                            <button type="button" onclick="changeDisplayImage(this, '<?= base_url($product['main_image']) ?>', 0)" 
                                    id="pageGalleryThumb_0"
                                    class="gallery-thumb-btn w-16 h-16 sm:w-20 sm:h-20 rounded-2xl border-2 border-blue-600 overflow-hidden flex-shrink-0 focus:outline-none transition active:scale-95 shadow-xs bg-gradient-to-b from-slate-50 to-slate-100/70 p-1.5 flex items-center justify-center">
                                <img src="<?= base_url($product['main_image']) ?>" class="w-full h-full object-contain object-center rounded-lg">
                            </button>
                            <?php foreach ($gallery as $gIdx => $img): ?>
                                <button type="button" onclick="changeDisplayImage(this, '<?= base_url($img['image_path']) ?>', <?= $gIdx + 1 ?>)" 
                                        id="pageGalleryThumb_<?= $gIdx + 1 ?>"
                                        class="gallery-thumb-btn w-16 h-16 sm:w-20 sm:h-20 rounded-2xl border-2 border-transparent hover:border-slate-300 overflow-hidden flex-shrink-0 focus:outline-none transition active:scale-95 shadow-xs bg-gradient-to-b from-slate-50 to-slate-100/70 p-1.5 flex items-center justify-center">
                                    <img src="<?= base_url($img['image_path']) ?>" class="w-full h-full object-contain object-center rounded-lg">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Trust Box -->
                <div class="bg-blue-50/60 rounded-2xl p-4 border border-blue-100 text-xs text-blue-900 space-y-2">
                    <div class="flex items-center font-bold">
                        <i class="fas fa-shield-alt text-blue-600 mr-2 text-sm"></i> การรับประกันสินค้าคุณภาพทางการ
                    </div>
                    <p class="text-blue-800/80 leading-relaxed">
                        สินค้าทุกชิ้นผ่านการคัดสรรเกรดคุณภาพพรีเมียม สกรีนและปักตราสัญลักษณ์มาตรฐานวิทยาลัยเทคนิคเชียงใหม่ ตรวจสอบความถูกต้องก่อนส่งมอบทุกชิ้น
                    </p>
                </div>
            </div>

            <!-- Right: Details and Order Form -->
            <div class="lg:col-span-7 flex flex-col justify-between">
                <div>
                    <div class="flex items-center space-x-2 text-xs font-mono text-slate-400 mb-2">
                        <span>รหัสสินค้า: <?= htmlspecialchars($product['code']) ?></span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mb-3 leading-snug">
                        <?= htmlspecialchars($product['name']) ?>
                    </h1>

                    <p class="text-sm text-slate-600 leading-relaxed mb-6">
                        <?= nl2br(htmlspecialchars($product['description'])) ?>
                    </p>

                    <!-- Form to select size & quantity -->
                    <form id="addToCartForm" class="space-y-6">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="action" value="add_to_cart">

                        <!-- Size / Variant Selector -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <label class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                                    <?= $currentCatConfig['label'] ?> <span class="text-rose-500">*</span>
                                </label>
                                <?php if ($isClothing): ?>
                                    <button type="button" onclick="openSizeModal()" class="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center bg-blue-50 hover:bg-blue-100 px-2.5 py-1 rounded-lg transition">
                                        <i class="fas fa-ruler-combined mr-1.5"></i> ตารางไซส์เสื้อ (Size Chart)
                                    </button>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400 font-medium">สเปก / ตัวเลือกสินค้า</span>
                                <?php endif; ?>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <?php foreach ($sizes as $s): ?>
                                    <?php 
                                        $isUnlim = !empty($s['is_unlimited']);
                                        $avail = (int)($s['stock'] - $s['reserved_stock']);
                                        $disabled = !$isUnlim && ($avail <= 0);
                                        $hasChest = (!empty($s['chest_inch']) && $s['chest_inch'] > 0);
                                    ?>
                                    <label class="relative flex flex-col p-3.5 rounded-2xl border-2 cursor-pointer transition select-none <?= $disabled ? 'opacity-40 bg-slate-50 border-slate-200 cursor-not-allowed' : 'border-slate-200 hover:border-blue-300 bg-white' ?> has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50/40">
                                        <input type="radio" 
                                               name="size_id" 
                                               value="<?= $s['id'] ?>" 
                                               data-price="<?= $s['price'] ?>" 
                                               data-stock="<?= $avail ?>"
                                               data-unlimited="<?= $isUnlim ? '1' : '0' ?>"
                                               <?= $disabled ? 'disabled' : '' ?> 
                                               class="sr-only" 
                                               onchange="onSizeChanged(this)" required>
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-base font-black text-slate-900">
                                                <?= $isClothing ? 'ไซส์ ' : '' ?><?= htmlspecialchars($s['size_name']) ?>
                                            </span>
                                            <span class="text-xs font-bold text-blue-600">฿<?= Helper::money($s['price']) ?></span>
                                        </div>
                                        <div class="text-[11px] text-slate-500 mb-1">
                                            <?php if ($isClothing && $hasChest): ?>
                                                อก <?= $s['chest_inch'] ?>" ยาว <?= $s['length_inch'] ?>"
                                            <?php elseif ($isMug): ?>
                                                <i class="fas fa-mug-hot text-amber-500 mr-1 text-[10px]"></i> แก้วน้ำ CMTC
                                            <?php elseif ($isSouvenir): ?>
                                                <i class="fas fa-gift text-purple-500 mr-1 text-[10px]"></i> ของที่ระลึก
                                            <?php else: ?>
                                                <i class="fas fa-check-circle text-blue-500 mr-1 text-[10px]"></i> ตัวเลือกสินค้า
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-[10px] <?= $isUnlim ? 'text-blue-600 font-bold' : ($avail > 0 ? 'text-emerald-600 font-medium' : 'text-rose-500 font-semibold') ?>">
                                            <?php if ($isUnlim): ?>
                                                <i class="fas fa-infinity mr-1"></i> ผลิตตามยอดจอง (ไม่จำกัด)
                                            <?php else: ?>
                                                <?= $avail > 0 ? "เหลือ {$avail} " . $currentCatConfig['unit'] : "สินค้าหมด" ?>
                                            <?php endif; ?>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Quantity Selector -->
                        <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <label class="text-xs font-bold text-slate-800 uppercase tracking-wider block mb-2">
                                    จำนวน (<?= $currentCatConfig['unit'] ?>)
                                </label>
                                <div class="inline-flex items-center border border-slate-200 rounded-xl bg-slate-50 p-1">
                                    <button type="button" onclick="adjustQty(-1)" class="w-9 h-9 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-100 active:scale-95 transition">
                                        <i class="fas fa-minus text-xs"></i>
                                    </button>
                                    <input type="number" id="quantityInput" name="quantity" value="1" min="1" max="999" oninput="onQtyInput()" class="w-16 text-center bg-transparent font-bold text-sm text-slate-900 focus:outline-none">
                                    <button type="button" onclick="adjustQty(1)" class="w-9 h-9 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-100 active:scale-95 transition">
                                        <i class="fas fa-plus text-xs"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Live Price Summary -->
                            <div class="sm:text-right">
                                <span class="text-xs text-slate-400 block mb-1">ราคารวมรายการนี้</span>
                                <div class="text-2xl font-black text-blue-600">
                                    ฿<span id="calculatedSubtotal">0.00</span>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-6 border-t border-slate-100 flex flex-col sm:flex-row gap-3">
                            <button type="submit" id="btnAddToCart" class="flex-1 py-3.5 px-6 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-md hover:shadow-lg transition flex items-center justify-center active:scale-[0.98]">
                                <i class="fas fa-cart-plus mr-2 text-base"></i>
                                <span>เพิ่มลงในตะกร้าสินค้า</span>
                            </button>
                            <a href="<?= base_url('cart.php') ?>" class="px-6 py-3.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm transition flex items-center justify-center">
                                ดูตะกร้าสินค้า
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if ($showAllProducts): ?>
    <!-- Product Showcase List Section -->
    <div class="mt-12 md:mt-16 pt-10 border-t border-slate-200" id="otherProductsSection">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-200 gap-4">
            <div>
                <div class="inline-flex items-center space-x-2 text-xs font-bold text-blue-600 uppercase tracking-wider mb-1">
                    <i class="fas fa-boxes"></i>
                    <span>สินค้าทั้งหมดในระบบ</span>
                </div>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">รายการสินค้าที่เปิดรับจอง</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">เสื้อกิจกรรม แก้วน้ำ และของที่ระลึกทางการวิทยาลัยเทคนิคเชียงใหม่</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="openSizeModal()" class="inline-flex items-center text-xs font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100 px-3.5 py-2 rounded-xl transition">
                    <i class="fas fa-ruler-combined mr-1.5"></i> ตารางขนาดเสื้อ
                </button>
                <a href="<?= base_url() ?>" class="inline-flex items-center text-xs font-bold text-slate-700 hover:text-blue-600 bg-slate-100 hover:bg-slate-200 px-3.5 py-2 rounded-xl transition">
                    <i class="fas fa-home mr-1.5"></i> หน้าแรก
                </a>
            </div>
        </div>

        <!-- Category Filter Tabs -->
        <div class="flex items-center space-x-2 overflow-x-auto py-4 scrollbar-none">
            <button type="button" onclick="filterCategory('all', this)" class="cat-pill active px-4 py-2 rounded-xl text-xs font-bold transition flex items-center whitespace-nowrap bg-blue-600 text-white shadow-sm">
                <i class="fas fa-th-large mr-1.5"></i> ทั้งหมด (<?= count($allProducts) ?>)
            </button>
            <?php foreach ($categoriesDef as $catName => $cMeta): ?>
                <?php 
                    $catCount = 0;
                    foreach ($allProducts as $p) { if ($p['category'] === $catName) $catCount++; }
                ?>
                <button type="button" onclick="filterCategory('<?= htmlspecialchars($catName, ENT_QUOTES) ?>', this)" class="cat-pill px-4 py-2 rounded-xl text-xs font-bold transition flex items-center whitespace-nowrap bg-white border border-slate-200 text-slate-600 hover:bg-slate-50">
                    <i class="fas <?= $cMeta['icon'] ?> mr-1.5 text-slate-400"></i> <?= htmlspecialchars($catName) ?> (<?= $catCount ?>)
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Products Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8 mt-4" id="productsGridContainer">
            <?php if (!empty($allProducts)): ?>
                <?php foreach ($allProducts as $p): ?>
                    <?php 
                        $isCurrent = ($p['id'] == $product['id']);
                        $pSizes = $allProductSizes[$p['id']] ?? []; 
                        $hasUnlimited = !empty($p['has_unlimited']);
                        $available = (int)$p['total_available_stock'];
                        $isSoldOut = !$hasUnlimited && ($available <= 0);
                        $cMeta = $categoriesDef[$p['category']] ?? ['icon' => 'fa-tag', 'color' => 'bg-slate-900/80 text-white'];
                        $pIsClothing = ($p['category'] === 'เสื้อ');
                    ?>
                    <div class="product-card bg-white rounded-2xl border <?= $isCurrent ? 'border-2 border-blue-500 shadow-md ring-2 ring-blue-500/10' : 'border-slate-200' ?> overflow-hidden flex flex-col hover-lift transition duration-300 relative" data-category="<?= htmlspecialchars($p['category']) ?>">
                        <?php if ($isCurrent): ?>
                            <div class="bg-blue-600 text-white text-[11px] font-bold px-3 py-1.5 text-center tracking-wide flex items-center justify-center">
                                <i class="fas fa-check-circle mr-1.5"></i> กำลังดูสินค้านี้อยู่
                            </div>
                        <?php endif; ?>

                        <!-- Product Image -->
                        <a href="<?= $isCurrent ? '#top' : base_url('product_detail.php?id=' . $p['id']) ?>" 
                           title="<?= $isCurrent ? 'สินค้านี้ (เลื่อนกลับขึ้นด้านบน)' : 'ดูรายละเอียด ' . htmlspecialchars($p['name']) ?>"
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
                                            <i class="fas fa-cubes mr-1"></i> เหลือพร้อมจอง <?= $available ?> <?= $pIsClothing ? 'ตัว' : 'ชิ้น' ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="font-bold text-base text-slate-900 leading-snug line-clamp-2 mb-2">
                                    <a href="<?= $isCurrent ? '#top' : base_url('product_detail.php?id=' . $p['id']) ?>" class="hover:text-blue-600 transition">
                                        <?= htmlspecialchars($p['name']) ?>
                                    </a>
                                </h3>
                                <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed mb-4">
                                    <?= htmlspecialchars($p['description']) ?>
                                </p>

                                <!-- Size / Option Badges -->
                                <div class="mb-4">
                                    <div class="text-[11px] font-semibold text-slate-500 mb-1.5">
                                        <?= $pIsClothing ? 'ขนาดไซส์เสื้อ:' : 'แบบ / ตัวเลือก:' ?>
                                    </div>
                                    <div class="flex flex-wrap gap-1.5">
                                        <?php foreach ($pSizes as $s): ?>
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

                                <?php if ($isCurrent): ?>
                                    <button type="button" 
                                            onclick="window.scrollTo({top: 0, behavior: 'smooth'})"
                                            class="inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-bold text-blue-700 bg-blue-50 hover:bg-blue-100 transition active:scale-95">
                                        <span>กำลังดูสินค้านี้</span>
                                        <i class="fas fa-arrow-up ml-1.5"></i>
                                    </button>
                                <?php else: ?>
                                    <a href="<?= base_url('product_detail.php?id=' . $p['id']) ?>" 
                                       class="inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-sm hover:shadow transition active:scale-95">
                                        <span>สั่งจองสินค้า</span>
                                        <i class="fas fa-arrow-right ml-1.5"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full py-12 text-center text-slate-500">
                    <i class="fas fa-box-open text-4xl text-slate-300 mb-3"></i>
                    <p>ยังไม่มีรายการสินค้าอื่นในขณะนี้</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

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

<!-- Modal: Fullscreen Image Lightbox with Carousel/Slider -->
<div id="imageLightboxModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/92 backdrop-blur-md hidden p-3 sm:p-6 transition-all duration-300 select-none" onclick="closeImageLightbox()">
    
    <!-- Top Bar: Counter & Close Button -->
    <div class="fixed top-4 left-4 right-4 z-30 flex items-center justify-between pointer-events-none">
        <div class="pointer-events-auto flex items-center space-x-2">
            <span id="lightboxCounterBadge" class="px-3.5 py-1.5 rounded-full bg-white/15 backdrop-blur-md text-white text-xs font-bold border border-white/20 shadow-lg">
                1 / <?= count($allProductImages) ?>
            </span>
            <span class="text-white/80 text-xs hidden sm:inline-block font-medium truncate max-w-sm">
                <?= htmlspecialchars($product['name']) ?>
            </span>
        </div>
        <button type="button" onclick="closeImageLightbox()" class="pointer-events-auto text-white/80 hover:text-white w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 border border-white/20 backdrop-blur-md flex items-center justify-center transition text-lg active:scale-95 shadow-lg" title="ปิด (Esc)">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Navigation Arrow Left (Previous) -->
    <button type="button" 
            id="lightboxPrevBtn"
            onclick="event.stopPropagation(); lightboxPrev();" 
            class="fixed left-3 sm:left-6 z-30 w-11 h-11 sm:w-13 sm:h-13 rounded-full bg-white/15 hover:bg-white/30 text-white backdrop-blur-md border border-white/25 flex items-center justify-center transition active:scale-90 shadow-2xl group cursor-pointer focus:outline-none <?= count($allProductImages) <= 1 ? 'hidden' : '' ?>" 
            title="รูปก่อนหน้า (←)">
        <i class="fas fa-chevron-left text-base sm:text-lg group-hover:-translate-x-0.5 transition-transform"></i>
    </button>

    <!-- Navigation Arrow Right (Next) -->
    <button type="button" 
            id="lightboxNextBtn"
            onclick="event.stopPropagation(); lightboxNext();" 
            class="fixed right-3 sm:right-6 z-30 w-11 h-11 sm:w-13 sm:h-13 rounded-full bg-white/15 hover:bg-white/30 text-white backdrop-blur-md border border-white/25 flex items-center justify-center transition active:scale-90 shadow-2xl group cursor-pointer focus:outline-none <?= count($allProductImages) <= 1 ? 'hidden' : '' ?>" 
            title="รูปถัดไป (→)">
        <i class="fas fa-chevron-right text-base sm:text-lg group-hover:translate-x-0.5 transition-transform"></i>
    </button>

    <!-- Center Stage Container -->
    <div class="relative max-w-5xl max-h-[90vh] w-full flex flex-col items-center justify-center z-20 my-auto" onclick="event.stopPropagation()">
        <!-- Main Image Display Frame -->
        <div class="relative bg-white/5 p-3 sm:p-6 rounded-3xl border border-white/15 backdrop-blur-xl flex items-center justify-center max-h-[76vh] w-full shadow-2xl overflow-hidden group">
            <img id="lightboxImage" 
                 src="" 
                 alt="Full view" 
                 onclick="lightboxNext()"
                 class="max-h-[70vh] max-w-full object-contain rounded-2xl drop-shadow-2xl transition-all duration-300 cursor-pointer hover:scale-[1.02]"
                 title="คลิกที่รูปเพื่อดูรูปถัดไป">
        </div>

        <!-- Bottom Thumbnails Strip & Hint -->
        <div class="w-full flex flex-col items-center mt-3 sm:mt-4 space-y-2">
            <!-- Thumbnail strip -->
            <?php if (count($allProductImages) > 1): ?>
                <div id="lightboxThumbStrip" class="flex items-center space-x-2 sm:space-x-3 overflow-x-auto max-w-full py-1 px-2 scrollbar-none">
                    <?php foreach ($allProductImages as $idx => $imgUrl): ?>
                        <button type="button" 
                                onclick="goToLightboxImage(<?= $idx ?>)" 
                                id="lightboxThumb_<?= $idx ?>"
                                class="lightbox-thumb-btn w-12 h-12 sm:w-14 sm:h-14 rounded-xl border-2 <?= $idx === 0 ? 'border-blue-500 scale-105 shadow-md shadow-blue-500/30' : 'border-white/20 opacity-60 hover:opacity-100 hover:border-white/50' ?> overflow-hidden flex-shrink-0 transition-all p-1 bg-white/10 flex items-center justify-center focus:outline-none">
                            <img src="<?= $imgUrl ?>" class="w-full h-full object-contain rounded-lg">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <p class="text-white/60 text-[11px] sm:text-xs text-center flex items-center justify-center">
                <span class="hidden sm:inline"><i class="fas fa-keyboard mr-1 text-blue-400"></i> กดปุ่มลูกศร ← / → หรือคลิกที่รูปเพื่อเลื่อนดูรูปถัดไป &nbsp;•&nbsp; </span>
                <span>กด Esc หรือคลิกพื้นหลังเพื่อปิด</span>
            </p>
        </div>
    </div>
</div>

<script>
const productImagesList = <?= json_encode($allProductImages) ?>;
let currentLightboxIndex = 0;
let currentPrice = 0;
let maxStock = 1;
let touchStartX = 0;
let touchEndX = 0;

function openImageLightbox(srcOrIdx = null) {
    const modal = document.getElementById('imageLightboxModal');
    if (!modal) return;

    if (typeof srcOrIdx === 'number') {
        currentLightboxIndex = srcOrIdx;
    } else if (typeof srcOrIdx === 'string' && srcOrIdx) {
        const found = productImagesList.findIndex(u => srcOrIdx.includes(u) || u.includes(srcOrIdx));
        currentLightboxIndex = (found !== -1) ? found : 0;
    } else {
        const currentSrc = document.getElementById('mainDisplayImage').src;
        const found = productImagesList.findIndex(u => currentSrc.includes(u) || u.includes(currentSrc));
        currentLightboxIndex = (found !== -1) ? found : 0;
    }

    updateLightboxView(0);
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeImageLightbox() {
    const modal = document.getElementById('imageLightboxModal');
    if (modal) modal.classList.add('hidden');
    document.body.style.overflow = '';
}

function lightboxNext(e) {
    if (e && typeof e.stopPropagation === 'function') e.stopPropagation();
    if (productImagesList.length <= 1) return;
    currentLightboxIndex = (currentLightboxIndex + 1) % productImagesList.length;
    updateLightboxView(1);
}

function lightboxPrev(e) {
    if (e && typeof e.stopPropagation === 'function') e.stopPropagation();
    if (productImagesList.length <= 1) return;
    currentLightboxIndex = (currentLightboxIndex - 1 + productImagesList.length) % productImagesList.length;
    updateLightboxView(-1);
}

function goToLightboxImage(idx) {
    if (idx >= 0 && idx < productImagesList.length) {
        const dir = idx > currentLightboxIndex ? 1 : -1;
        currentLightboxIndex = idx;
        updateLightboxView(dir);
    }
}

function updateLightboxView(direction = 0) {
    if (!productImagesList.length) return;
    const imgEl = document.getElementById('lightboxImage');
    const counterBadge = document.getElementById('lightboxCounterBadge');

    imgEl.style.opacity = '0';
    imgEl.style.transform = (direction > 0) ? 'translateX(20px) scale(0.97)' : (direction < 0 ? 'translateX(-20px) scale(0.97)' : 'scale(0.97)');

    setTimeout(() => {
        imgEl.src = productImagesList[currentLightboxIndex];
        imgEl.style.opacity = '1';
        imgEl.style.transform = 'translateX(0) scale(1)';
    }, 120);

    if (counterBadge) {
        counterBadge.textContent = `${currentLightboxIndex + 1} / ${productImagesList.length}`;
    }

    // Update lightbox thumbnail highlights
    document.querySelectorAll('.lightbox-thumb-btn').forEach((btn, idx) => {
        if (idx === currentLightboxIndex) {
            btn.classList.add('border-blue-500', 'scale-105', 'shadow-md', 'shadow-blue-500/30');
            btn.classList.remove('border-white/20', 'opacity-60');
            btn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        } else {
            btn.classList.remove('border-blue-500', 'scale-105', 'shadow-md', 'shadow-blue-500/30');
            btn.classList.add('border-white/20', 'opacity-60');
        }
    });

    // Synchronize page display
    syncPageDisplay(currentLightboxIndex);
}

function syncPageDisplay(idx) {
    if (!productImagesList[idx]) return;
    const mainImg = document.getElementById('mainDisplayImage');
    if (mainImg) mainImg.src = productImagesList[idx];

    document.querySelectorAll('.gallery-thumb-btn').forEach(b => {
        b.classList.remove('border-blue-600');
        b.classList.add('border-transparent');
    });

    const targetThumb = document.getElementById('pageGalleryThumb_' + idx);
    if (targetThumb) {
        targetThumb.classList.remove('border-transparent');
        targetThumb.classList.add('border-blue-600');
    }
}

function changeDisplayImage(btn, url, idx = null) {
    document.getElementById('mainDisplayImage').src = url;
    document.querySelectorAll('.gallery-thumb-btn').forEach(b => {
        b.classList.remove('border-blue-600');
        b.classList.add('border-transparent');
    });
    if (btn) {
        btn.classList.remove('border-transparent');
        btn.classList.add('border-blue-600');
    }
    if (idx !== null) {
        currentLightboxIndex = idx;
    } else {
        const found = productImagesList.findIndex(u => url.includes(u) || u.includes(url));
        if (found !== -1) currentLightboxIndex = found;
    }
}

// Keyboard arrow navigation
document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('imageLightboxModal');
    const isLightboxOpen = modal && !modal.classList.contains('hidden');

    if (e.key === 'Escape') {
        closeImageLightbox();
        if (typeof closeSizeModal === 'function') closeSizeModal();
    } else if (isLightboxOpen) {
        if (e.key === 'ArrowRight' || e.key === ' ') {
            e.preventDefault();
            lightboxNext();
        } else if (e.key === 'ArrowLeft') {
            e.preventDefault();
            lightboxPrev();
        }
    }
});

// Mobile swipe gestures
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('imageLightboxModal');
    if (modal) {
        modal.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        modal.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            const diff = touchEndX - touchStartX;
            if (Math.abs(diff) > 40) {
                if (diff < 0) {
                    lightboxNext();
                } else {
                    lightboxPrev();
                }
            }
        }, { passive: true });
    }
});

function openSizeModal() {
    const modal = document.getElementById('sizeModal');
    if (modal) modal.classList.remove('hidden');
}

function closeSizeModal() {
    const modal = document.getElementById('sizeModal');
    if (modal) modal.classList.add('hidden');
}

function onSizeChanged(radio) {
    currentPrice = parseFloat(radio.dataset.price) || 0;
    const isUnlim = (radio.dataset.unlimited === '1');
    maxStock = isUnlim ? 999 : (parseInt(radio.dataset.stock) || 1);
    
    const qtyInput = document.getElementById('quantityInput');
    qtyInput.max = maxStock;
    if (parseInt(qtyInput.value) > maxStock) {
        qtyInput.value = maxStock;
    }
    updateSubtotal();
}

function adjustQty(amount) {
    const qtyInput = document.getElementById('quantityInput');
    let val = parseInt(qtyInput.value) || 1;
    val += amount;
    if (val < 1) val = 1;
    if (val > maxStock) val = maxStock;
    qtyInput.value = val;
    updateSubtotal();
}

function onQtyInput() {
    const qtyInput = document.getElementById('quantityInput');
    let val = parseInt(qtyInput.value);
    if (isNaN(val) || val < 1) val = 1;
    if (val > maxStock) val = maxStock;
    qtyInput.value = val;
    updateSubtotal();
}

function updateSubtotal() {
    const qty = parseInt(document.getElementById('quantityInput').value) || 1;
    const subtotal = currentPrice * qty;
    document.getElementById('calculatedSubtotal').textContent = subtotal.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Handle Add to Cart submission via AJAX
document.getElementById('addToCartForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const selectedSize = document.querySelector('input[name="size_id"]:checked');
    if (!selectedSize) {
        Swal.fire({
            icon: 'warning',
            title: '<?= $isClothing ? "กรุณาเลือกขนาดไซส์" : "กรุณาเลือกตัวเลือกสินค้า" ?>',
            text: '<?= $isClothing ? "โปรดเลือกไซส์เสื้อก่อนทำการเพิ่มลงในตะกร้า" : "โปรดเลือกแบบหรือตัวเลือกสินค้าก่อนทำการเพิ่มลงในตะกร้า" ?>',
            confirmButtonColor: '#2563eb'
        });
        return;
    }

    const formData = new FormData(this);
    const btn = document.getElementById('btnAddToCart');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> กำลังบันทึก...';

    fetch('<?= base_url("api.php") ?>', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-cart-plus mr-2 text-base"></i> เพิ่มลงในตะกร้าสินค้า';

        if (data.success) {
            // Update nav badge
            const badge = document.getElementById('navCartBadge');
            if (badge) {
                badge.textContent = data.cart_count;
                badge.classList.remove('hidden');
            }

            Swal.fire({
                icon: 'success',
                title: 'เพิ่มลงตะกร้าแล้ว',
                text: 'คุณได้เพิ่มสินค้าลงในตะกร้าเรียบร้อยแล้ว',
                showCancelButton: true,
                confirmButtonText: 'ไปที่ตะกร้าสินค้า',
                cancelButtonText: 'สั่งจองต่อ',
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#64748b'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '<?= base_url("cart.php") ?>';
                }
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'ไม่สามารถเพิ่มสินค้าได้',
                text: data.error || 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง',
                confirmButtonColor: '#2563eb'
            });
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-cart-plus mr-2 text-base"></i> เพิ่มลงในตะกร้าสินค้า';
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาดในการเชื่อมต่อ',
            text: 'กรุณาลองใหม่อีกครั้งในภายหลัง',
            confirmButtonColor: '#2563eb'
        });
    });
});

function filterCategory(cat, btn) {
    document.querySelectorAll('#otherProductsSection .cat-pill').forEach(b => {
        b.className = 'cat-pill px-4 py-2 rounded-xl text-xs font-bold transition flex items-center whitespace-nowrap bg-white border border-slate-200 text-slate-600 hover:bg-slate-50';
    });
    btn.className = 'cat-pill active px-4 py-2 rounded-xl text-xs font-bold transition flex items-center whitespace-nowrap bg-blue-600 text-white shadow-sm';

    const cards = document.querySelectorAll('#otherProductsSection .product-card');
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
