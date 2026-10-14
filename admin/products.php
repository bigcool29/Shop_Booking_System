<?php
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helper.php';
require_once __DIR__ . '/../core/Csrf.php';

Auth::requireLogin();
$pdo = Database::getInstance()->getConnection();

// Handle Actions (Add, Edit, Delete, Toggle Active)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    Csrf::verifyOrDie();
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_active') {
        $pid = (int)($_POST['product_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE products SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$pid]);
        Helper::setFlash('success', 'ปรับสถานะสินค้าแล้ว');
        header("Location: " . base_url('admin/products.php'));
        exit;
    }

    if ($action === 'delete_gallery_image') {
        $imgId = (int)($_POST['image_id'] ?? 0);
        $imgStmt = $pdo->prepare("SELECT * FROM product_images WHERE id = ?");
        $imgStmt->execute([$imgId]);
        $img = $imgStmt->fetch();

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_POST['ajax']);

        if ($img) {
            $productId = (int)$img['product_id'];
            $filePath = __DIR__ . '/../' . $img['image_path'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            $pdo->prepare("DELETE FROM product_images WHERE id = ?")->execute([$imgId]);

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'message' => 'ลบรูปภาพเพิ่มเติมเรียบร้อยแล้ว',
                    'image_id' => $imgId,
                    'product_id' => $productId
                ]);
                exit;
            }

            Helper::setFlash('success', 'ลบรูปภาพเพิ่มเติมเรียบร้อยแล้ว');
            header("Location: " . base_url('admin/products.php?edit_id=' . $productId));
            exit;
        }

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'ไม่พบข้อมูลรูปภาพที่ต้องการลบ'
            ]);
            exit;
        }

        Helper::setFlash('error', 'ไม่พบรูปภาพ');
        header("Location: " . base_url('admin/products.php'));
        exit;
    }

    if ($action === 'save_product') {
        $pid = (int)($_POST['product_id'] ?? 0);
        $code = trim($_POST['code'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $validCats = ['เสื้อ', 'แก้วน้ำ', 'ของที่ระลึก', 'อื่นๆ'];
        $category = in_array(trim($_POST['category'] ?? ''), $validCats) ? trim($_POST['category']) : 'เสื้อ';
        $desc = trim($_POST['description'] ?? '');
        $roundId = (int)($_POST['round_id'] ?? 1);

        $mainImage = null;
        if (!empty($_FILES['main_image']['tmp_name'])) {
            $up = Helper::uploadFile($_FILES['main_image'], 'products');
            if ($up['success']) {
                $mainImage = $up['filename'];
            }
        }

        try {
            $pdo->beginTransaction();

            if ($pid > 0) {
                // Update
                if ($mainImage) {
                    $uStmt = $pdo->prepare("UPDATE products SET round_id=?, code=?, name=?, category=?, description=?, main_image=? WHERE id=?");
                    $uStmt->execute([$roundId, $code, $name, $category, $desc, $mainImage, $pid]);
                } else {
                    $uStmt = $pdo->prepare("UPDATE products SET round_id=?, code=?, name=?, category=?, description=? WHERE id=?");
                    $uStmt->execute([$roundId, $code, $name, $category, $desc, $pid]);
                }
            } else {
                // Insert
                $iStmt = $pdo->prepare("INSERT INTO products (round_id, code, name, category, description, main_image, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
                $iStmt->execute([$roundId, $code, $name, $category, $desc, $mainImage ?: 'assets/uploads/products/polo_87_main.jpg']);
                $pid = (int)$pdo->lastInsertId();
            }

            // Process Sizes / Variants
            $sizes = $_POST['sizes'] ?? [];
            if (!empty($sizes)) {
                $existingIds = [];

                foreach ($sizes as $s) {
                    $sId = (int)($s['id'] ?? 0);
                    $sName = trim($s['size_name'] ?? '');
                    $chest = (isset($s['chest_inch']) && $s['chest_inch'] !== '') ? (float)$s['chest_inch'] : null;
                    $len = (isset($s['length_inch']) && $s['length_inch'] !== '') ? (float)$s['length_inch'] : null;
                    $price = (float)($s['price'] ?? 0);
                    $isUnlimited = !empty($s['is_unlimited']) ? 1 : 0;
                    $stock = $isUnlimited ? 0 : (int)($s['stock'] ?? 0);

                    if (empty($sName)) continue;

                    if ($sId > 0) {
                        $upSize = $pdo->prepare("UPDATE product_sizes SET size_name=?, chest_inch=?, length_inch=?, price=?, stock=?, is_unlimited=? WHERE id=? AND product_id=?");
                        $upSize->execute([$sName, $chest, $len, $price, $stock, $isUnlimited, $sId, $pid]);
                        $existingIds[] = $sId;
                    } else {
                        $inSize = $pdo->prepare("INSERT INTO product_sizes (product_id, size_name, chest_inch, length_inch, price, stock, is_unlimited) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $inSize->execute([$pid, $sName, $chest, $len, $price, $stock, $isUnlimited]);
                        $existingIds[] = (int)$pdo->lastInsertId();
                    }
                }

                if (!empty($existingIds)) {
                    $inClause = implode(',', array_map('intval', $existingIds));
                    $pdo->exec("DELETE FROM product_sizes WHERE product_id = {$pid} AND id NOT IN ({$inClause})");
                }
            }

            // Process Multiple Gallery Images Upload
            if (!empty($_FILES['gallery_images']['name']) && is_array($_FILES['gallery_images']['name'])) {
                $galleryUploaded = Helper::uploadMultipleFiles($_FILES['gallery_images'], 'products');
                if (!empty($galleryUploaded)) {
                    $maxSortStmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) FROM product_images WHERE product_id = ?");
                    $maxSortStmt->execute([$pid]);
                    $currentSort = (int)$maxSortStmt->fetchColumn();

                    $imgInsert = $pdo->prepare("INSERT INTO product_images (product_id, image_path, sort_order) VALUES (?, ?, ?)");
                    foreach ($galleryUploaded as $gImg) {
                        $currentSort++;
                        $imgInsert->execute([$pid, $gImg, $currentSort]);
                    }
                }
            }

            $pdo->commit();
            Helper::setFlash('success', 'บันทึกข้อมูลสินค้าและรูปภาพเรียบร้อยแล้ว');
            header("Location: " . base_url('admin/products.php'));
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            Helper::setFlash('error', 'เกิดข้อผิดพลาดในการบันทึก', $e->getMessage());
            header("Location: " . base_url('admin/products.php'));
            exit;
        }
    }
}

// Fetch all products with their sizes & gallery images
$products = $pdo->query("SELECT p.*, r.round_name FROM products p LEFT JOIN booking_rounds r ON p.round_id = r.id ORDER BY p.sort_order ASC, p.id DESC")->fetchAll();

$productSizes = [];
$sizesStmt = $pdo->query("SELECT * FROM product_sizes ORDER BY sort_order ASC, id ASC");
while ($s = $sizesStmt->fetch()) {
    $productSizes[$s['product_id']][] = $s;
}

$productImages = [];
$gStmt = $pdo->query("SELECT * FROM product_images ORDER BY product_id ASC, sort_order ASC, id ASC");
while ($g = $gStmt->fetch()) {
    $productImages[$g['product_id']][] = $g;
}

$rounds = $pdo->query("SELECT id, round_name FROM booking_rounds ORDER BY id DESC")->fetchAll();

$pageTitle = "จัดการสินค้าและขนาดไซต์ - CMTC Admin";
$activePage = 'products';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">จัดการสินค้าและหมวดหมู่</h1>
            <p class="text-xs text-slate-500 mt-0.5">รองรับทั้งเสื้อผ้า แก้วน้ำ ของที่ระลึก พร้อมจัดการรูปภาพหลายรูปและสต็อก</p>
        </div>
        <button onclick="openProductModal()" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md transition flex items-center justify-center btn-touch">
            <i class="fas fa-plus mr-2"></i> เพิ่มสินค้าใหม่
        </button>
    </div>

    <!-- Category Tabs Filter -->
    <div class="flex items-center space-x-2 overflow-x-auto pb-3 scrollbar-none mb-6 -mx-1 px-1">
        <button type="button" onclick="adminFilterCategory('all', this)" class="admin-cat-pill active px-4 py-2 rounded-xl text-xs font-bold transition flex items-center whitespace-nowrap bg-blue-600 text-white shadow-xs">
            <i class="fas fa-th-large mr-1.5"></i> ทั้งหมด (<?= count($products) ?>)
        </button>
        <?php 
            $adminCats = [
                'เสื้อ' => ['icon' => 'fa-tshirt', 'color' => 'blue'],
                'แก้วน้ำ' => ['icon' => 'fa-mug-hot', 'color' => 'amber'],
                'ของที่ระลึก' => ['icon' => 'fa-gift', 'color' => 'purple'],
                'อื่นๆ' => ['icon' => 'fa-box-open', 'color' => 'slate']
            ];
            foreach ($adminCats as $cName => $cInfo):
                $cCount = 0;
                foreach ($products as $p) { if ($p['category'] === $cName) $cCount++; }
        ?>
            <button type="button" onclick="adminFilterCategory('<?= $cName ?>', this)" class="admin-cat-pill px-4 py-2 rounded-xl text-xs font-bold transition flex items-center whitespace-nowrap bg-white border border-slate-200 text-slate-600 hover:bg-slate-50">
                <i class="fas <?= $cInfo['icon'] ?> mr-1.5 text-slate-400"></i> <?= $cName ?> (<?= $cCount ?>)
            </button>
        <?php endforeach; ?>
    </div>

    <!-- Products Grid -->
    <div class="space-y-6">
        <?php foreach ($products as $p): ?>
            <?php 
                $sizes = $productSizes[$p['id']] ?? []; 
                $gImgs = $productImages[$p['id']] ?? [];
                $isShirt = ($p['category'] === 'เสื้อ');

                $badgeStyles = [
                    'เสื้อ' => ['bg' => 'bg-blue-50 text-blue-700 border-blue-200', 'icon' => 'fa-tshirt'],
                    'แก้วน้ำ' => ['bg' => 'bg-amber-50 text-amber-700 border-amber-200', 'icon' => 'fa-mug-hot'],
                    'ของที่ระลึก' => ['bg' => 'bg-purple-50 text-purple-700 border-purple-200', 'icon' => 'fa-gift'],
                    'อื่นๆ' => ['bg' => 'bg-slate-100 text-slate-700 border-slate-200', 'icon' => 'fa-box-open']
                ];
                $bInfo = $badgeStyles[$p['category']] ?? ['bg' => 'bg-slate-100 text-slate-700 border-slate-200', 'icon' => 'fa-tag'];
            ?>
            <div class="product-admin-card bg-white rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-6 flex flex-col lg:flex-row gap-5 lg:gap-6 items-start" data-admin-category="<?= htmlspecialchars($p['category']) ?>">
                <!-- Thumbnail & Gallery Preview -->
                <div class="flex flex-col gap-2 flex-shrink-0 w-full sm:w-auto">
                    <div class="w-full sm:w-32 h-44 sm:h-32 rounded-2xl bg-slate-100 border border-slate-200 overflow-hidden relative group">
                        <img src="<?= base_url($p['main_image'] ?: 'assets/uploads/products/polo_87_main.jpg') ?>" class="w-full h-full object-cover">
                        <?php if (!empty($gImgs)): ?>
                            <span id="productGalleryBadge_<?= $p['id'] ?>" class="absolute bottom-2 right-2 sm:bottom-1 sm:right-1 bg-slate-900/80 backdrop-blur-sm text-white text-[10px] font-bold px-2 py-0.5 rounded-md">
                                +<?= count($gImgs) ?> รูป
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($gImgs)): ?>
                        <div id="productGalleryThumbContainer_<?= $p['id'] ?>" class="flex items-center space-x-1.5 max-w-full sm:max-w-[128px] overflow-x-auto pb-1">
                            <?php foreach (array_slice($gImgs, 0, 5) as $gi): ?>
                                <img id="productGalleryThumb_<?= $gi['id'] ?>" src="<?= base_url($gi['image_path']) ?>" class="w-8 h-8 rounded-lg object-cover border border-slate-200 flex-shrink-0" title="รูปภาพเพิ่มเติม">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Info & Sizes Table -->
                <div class="flex-1 w-full">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-slate-100 gap-3 mb-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                                <span class="text-xs font-mono font-bold text-slate-400"><?= htmlspecialchars($p['code']) ?></span>
                                <span class="text-xs <?= $bInfo['bg'] ?> border px-2.5 py-0.5 rounded-md font-bold inline-flex items-center">
                                    <i class="fas <?= $bInfo['icon'] ?> mr-1"></i> <?= htmlspecialchars($p['category']) ?>
                                </span>
                                <span class="text-xs <?= $p['is_active'] ? 'text-emerald-600' : 'text-slate-400' ?> font-semibold">
                                    <i class="fas fa-circle text-[8px] mr-1"></i> <?= $p['is_active'] ? 'เปิดรับจอง' : 'ปิดการจอง' ?>
                                </span>
                            </div>
                            <h3 class="text-base font-black text-slate-900 mt-1"><?= htmlspecialchars($p['name']) ?></h3>
                        </div>

                        <div class="flex items-center gap-2">
                            <form action="<?= base_url('admin/products.php') ?>" method="POST" class="inline flex-1 sm:flex-initial">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="action" value="toggle_active">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <button type="submit" class="w-full btn-touch px-3 py-2 rounded-xl border text-xs font-semibold <?= $p['is_active'] ? 'border-amber-300 text-amber-700 bg-amber-50/50 hover:bg-amber-100' : 'border-emerald-300 text-emerald-700 bg-emerald-50/50 hover:bg-emerald-100' ?> transition flex items-center justify-center">
                                    <i class="fas <?= $p['is_active'] ? 'fa-pause' : 'fa-play' ?> mr-1 text-[10px]"></i> <?= $p['is_active'] ? 'ปิดรับจอง' : 'เปิดรับจอง' ?>
                                </button>
                            </form>
                            <button onclick='editProduct(<?= json_encode($p) ?>, <?= json_encode($sizes) ?>)' class="flex-1 sm:flex-initial btn-touch px-3 py-2 rounded-xl bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs font-bold transition flex items-center justify-center">
                                <i class="fas fa-edit mr-1"></i> แก้ไข
                            </button>
                        </div>
                    </div>

                    <!-- Sizes / Variants Table -->
                    <div class="block sm:hidden text-[11px] text-slate-400 mb-1.5 flex items-center">
                        <i class="fas fa-arrows-alt-h text-blue-500 mr-1.5 animate-pulse"></i> เลื่อนแนวนอนเพื่อดูตารางสต็อกและราคา
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="w-full text-xs text-left min-w-[520px]">
                            <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                                <tr>
                                    <th class="py-2 px-3"><?= $isShirt ? 'ขนาดไซส์ (Size)' : 'แบบ / ตัวเลือกสินค้า' ?></th>
                                    <?php if ($isShirt): ?>
                                        <th class="py-2 px-3 text-center">รอบอก (นิ้ว)</th>
                                        <th class="py-2 px-3 text-center">ความยาว (นิ้ว)</th>
                                    <?php endif; ?>
                                    <th class="py-2 px-3 text-right">ราคาจำหน่าย</th>
                                    <th class="py-2 px-3 text-center">สต็อกทั้งหมด</th>
                                    <th class="py-2 px-3 text-center">ล็อกจองชั่วคราว</th>
                                    <th class="py-2 px-3 text-center">พร้อมจอง</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                <?php foreach ($sizes as $s): ?>
                                    <?php 
                                        $isUnlim = !empty($s['is_unlimited']);
                                        $avail = (int)($s['stock'] - $s['reserved_stock']); 
                                        $unit = $isShirt ? 'ตัว' : 'ชิ้น';
                                    ?>
                                    <tr>
                                        <td class="py-2 px-3 font-bold text-slate-900"><?= htmlspecialchars($s['size_name']) ?></td>
                                        <?php if ($isShirt): ?>
                                            <td class="py-2 px-3 text-center"><?= (!empty($s['chest_inch']) && $s['chest_inch'] > 0) ? $s['chest_inch'] . '"' : '<span class="text-slate-300">-</span>' ?></td>
                                            <td class="py-2 px-3 text-center"><?= (!empty($s['length_inch']) && $s['length_inch'] > 0) ? $s['length_inch'] . '"' : '<span class="text-slate-300">-</span>' ?></td>
                                        <?php endif; ?>
                                        <td class="py-2 px-3 text-right font-bold text-blue-600">฿<?= Helper::money($s['price']) ?></td>
                                        <td class="py-2 px-3 text-center">
                                            <?php if ($isUnlim): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                    <i class="fas fa-infinity mr-1"></i> ไม่จำกัด
                                                </span>
                                            <?php else: ?>
                                                <span class="font-semibold"><?= (int)$s['stock'] ?> <?= $unit ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-2 px-3 text-center text-amber-600 font-semibold"><?= (int)$s['reserved_stock'] ?></td>
                                        <td class="py-2 px-3 text-center font-bold">
                                            <?php if ($isUnlim): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    <i class="fas fa-infinity mr-1"></i> รับไม่จำกัด
                                                </span>
                                            <?php else: ?>
                                                <span class="<?= $avail > 0 ? 'text-emerald-600' : 'text-rose-500' ?>"><?= $avail ?> <?= $unit ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal: Add / Edit Product -->
<div id="productModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm hidden p-3 sm:p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl max-w-3xl w-full p-4 sm:p-8 shadow-2xl relative my-auto sm:my-8 max-h-[92vh] overflow-y-auto">
        <button onclick="closeProductModal()" class="absolute top-3.5 right-3.5 sm:top-4 sm:right-4 text-slate-400 hover:text-slate-600 w-9 h-9 rounded-full flex items-center justify-center hover:bg-slate-100 transition">
            <i class="fas fa-times text-base"></i>
        </button>

        <h3 class="text-lg font-black text-slate-900 mb-1" id="modalTitle">เพิ่มสินค้าใหม่</h3>
        <p class="text-xs text-slate-500 mb-6">กำหนดข้อมูลสินค้า หมวดหมู่ รูปภาพหลัก/เพิ่มเติม และตัวเลือก/ราคา/สต็อก</p>

        <form action="<?= base_url('admin/products.php') ?>" method="POST" enctype="multipart/form-data" class="space-y-4">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="save_product">
            <input type="hidden" name="product_id" id="formProductId" value="0">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">รหัสสินค้า (Product Code) <span class="text-rose-500">*</span></label>
                    <input type="text" name="code" id="formCode" required placeholder="เช่น POLO-CMTC-87 หรือ MUG-CMTC-01" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 uppercase font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">หมวดหมู่สินค้า <span class="text-rose-500">*</span></label>
                    <select name="category" id="formCategory" onchange="onCategoryChanged(this.value)" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white font-semibold">
                        <option value="เสื้อ">👕 1. เสื้อ</option>
                        <option value="แก้วน้ำ">☕ 2. แก้วน้ำ</option>
                        <option value="ของที่ระลึก">🎁 3. ของที่ระลึก</option>
                        <option value="อื่นๆ">📦 4. อื่นๆ</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1">ชื่อสินค้า <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="formName" required placeholder="เช่น เสื้อโปโล 87 ปี หรือ แก้วน้ำเซรามิก CMTC" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1">คำอธิบาย</label>
                    <textarea name="description" id="formDesc" rows="2" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200" placeholder="ระบุรายละเอียด วัสดุ หรือคุณสมบัติสินค้า"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">รอบการจอง</label>
                    <select name="round_id" id="formRoundId" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white">
                        <?php foreach ($rounds as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['round_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Main Image Section -->
                <div class="sm:col-span-2 bg-slate-50/80 p-3.5 rounded-2xl border border-slate-200/80">
                    <label class="block text-xs font-bold text-slate-800 mb-2">
                        <i class="far fa-image text-blue-600 mr-1"></i> รูปภาพหลัก (Main Image)
                    </label>
                    <div class="flex items-center space-x-3.5">
                        <div class="w-20 h-20 rounded-xl bg-white border border-slate-200 overflow-hidden flex-shrink-0 relative flex items-center justify-center shadow-xs">
                            <img id="mainImagePreview" src="" alt="Main Image Preview" class="w-full h-full object-cover hidden">
                            <div id="mainImagePlaceholder" class="text-slate-300 text-center p-2">
                                <i class="fas fa-image text-2xl"></i>
                                <span class="block text-[10px] mt-0.5 text-slate-400 font-medium">ยังไม่มีรูป</span>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <input type="file" id="mainImageInput" name="main_image" accept="image/*" onchange="previewMainImage(this)" class="w-full text-xs text-slate-500 file:mr-2.5 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 transition cursor-pointer">
                            <span id="mainImageHelp" class="text-[11px] text-slate-400 block mt-1.5">รองรับไฟล์ JPG, PNG, WEBP (คลิกเลือกไฟล์หากต้องการเปลี่ยนรูปหลัก)</span>
                        </div>
                    </div>
                </div>

                <!-- Multiple Gallery Images Upload -->
                <div class="sm:col-span-2 bg-slate-50/70 p-3.5 rounded-2xl border border-slate-200/80">
                    <label class="block text-xs font-bold text-slate-800 mb-1">
                        <i class="far fa-images text-indigo-600 mr-1"></i> เพิ่มรูปภาพสินค้าอื่นๆ (Gallery Images - เพิ่มได้หลายรูป)
                    </label>
                    <input type="file" id="galleryImagesInput" name="gallery_images[]" multiple accept="image/*" onchange="previewNewGalleryImages(this)" class="w-full text-xs text-slate-500 file:mr-2.5 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700 transition cursor-pointer">
                    <span class="text-[10px] text-slate-400 block mt-1">สามารถกดเลือกไฟล์ภาพพร้อมกันได้หลายๆ รูป (รองรับ JPG, PNG, WEBP)</span>
                    
                    <!-- Preview for freshly selected new gallery files -->
                    <div id="newGalleryPreviews" class="hidden mt-3 pt-3 border-t border-slate-200/60">
                        <span class="text-[11px] font-bold text-slate-600 block mb-1.5">
                            <i class="fas fa-eye text-indigo-500 mr-1"></i> รูปใหม่ที่เลือกเตรียมบันทึก:
                        </span>
                        <div id="newGalleryPreviewsContainer" class="flex flex-wrap gap-2.5"></div>
                    </div>
                </div>

                <!-- Existing Gallery Images Preview (Edit mode only) -->
                <div id="existingGallerySection" class="hidden sm:col-span-2 bg-blue-50/40 p-4 rounded-2xl border border-blue-100">
                    <div class="flex items-center justify-between mb-2.5">
                        <label class="block text-xs font-bold text-slate-800">
                            <i class="far fa-images text-blue-600 mr-1"></i> รูปภาพเพิ่มเติมปัจจุบัน (<span id="existingGalleryCount">0</span> รูป)
                        </label>
                        <span class="text-[10px] text-slate-400">ชี้ที่รูปภาพเพื่อกดปุ่มลบ</span>
                    </div>
                    <div id="existingGalleryContainer" class="flex flex-wrap gap-3">
                        <!-- Populated dynamically via JS -->
                    </div>
                </div>
            </div>

            <!-- Dynamic Sizes / Options Section -->
            <div class="pt-4 border-t border-slate-100">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-3 gap-2">
                    <div>
                        <label id="optionsSectionTitle" class="text-xs font-bold text-slate-800 uppercase tracking-wider block">
                            ขนาดไซส์เสื้อ ราคา และสต็อก
                        </label>
                        <span id="optionsSectionHint" class="text-[10px] text-slate-400">
                            สินค้าประเภทเสื้อ: กำหนดขนาดไซส์ (S, M, L, XL...) พร้อมรอบอกและความยาว
                        </span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button type="button" id="btnAddClothingPresets" onclick="populateClothingPresets()" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 flex items-center bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1.5 rounded-lg transition">
                            <i class="fas fa-magic mr-1"></i> เติมไซส์มาตรฐาน (S-3XL)
                        </button>
                        <button type="button" onclick="addOptionRow()" class="text-xs font-bold text-blue-600 hover:text-blue-700 flex items-center bg-blue-50 hover:bg-blue-100 px-2.5 py-1.5 rounded-lg transition">
                            <i class="fas fa-plus-circle mr-1"></i> <span id="btnAddOptionText">เพิ่มไซส์</span>
                        </button>
                    </div>
                </div>

                <div class="block sm:hidden text-[10px] text-slate-400 mb-1 flex items-center">
                    <i class="fas fa-arrows-alt-h text-indigo-500 mr-1 animate-pulse"></i> เลื่อนแนวนอนเพื่อกรอกข้อมูลตัวเลือก/ไซส์
                </div>
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-xs text-left min-w-[580px]">
                        <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                            <tr>
                                <th class="p-2" id="colHeaderName">ขนาดไซส์ (Size)</th>
                                <th class="p-2 text-center col-measurement" id="colHeaderChest">รอบอก (นิ้ว)</th>
                                <th class="p-2 text-center col-measurement" id="colHeaderLength">ความยาว (นิ้ว)</th>
                                <th class="p-2 text-right">ราคา (บาท)</th>
                                <th class="p-2 text-center">ไม่จำกัด (Pre-order)</th>
                                <th class="p-2 text-center" id="colHeaderStock">สต็อก (ตัว)</th>
                                <th class="p-2 text-center">ลบ</th>
                            </tr>
                        </thead>
                        <tbody id="sizeRowsContainer" class="divide-y divide-slate-100">
                            <!-- Injected dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="pt-4 flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 sm:justify-end border-t border-slate-100">
                <button type="button" onclick="closeProductModal()" class="w-full sm:w-auto px-4 py-2.5 bg-slate-100 text-slate-600 rounded-xl text-xs font-semibold hover:bg-slate-200 btn-touch flex items-center justify-center">
                    ยกเลิก
                </button>
                <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-700 shadow-md btn-touch flex items-center justify-center">
                    <i class="fas fa-save mr-1.5"></i> บันทึกข้อมูล
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const appBaseUrl = '<?= rtrim(base_url(), "/") ?>/';
const csrfToken = '<?= Csrf::getToken() ?>';
const allProductsData = <?= json_encode($products) ?>;
const productSizesData = <?= json_encode($productSizes) ?>;
const productGalleryData = <?= json_encode($productImages) ?>;

function getAssetUrl(path) {
    if (!path) return '';
    if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('data:')) return path;
    return appBaseUrl + path.replace(/^\//, '');
}

function adminFilterCategory(cat, btn) {
    document.querySelectorAll('.admin-cat-pill').forEach(b => {
        b.classList.remove('bg-blue-600', 'text-white', 'shadow-xs');
        b.classList.add('bg-white', 'border', 'border-slate-200', 'text-slate-600');
    });
    btn.classList.add('bg-blue-600', 'text-white', 'shadow-xs');
    btn.classList.remove('bg-white', 'border', 'border-slate-200', 'text-slate-600');

    document.querySelectorAll('.product-admin-card').forEach(card => {
        if (cat === 'all' || card.dataset.adminCategory === cat) {
            card.classList.remove('hidden');
        } else {
            card.classList.add('hidden');
        }
    });
}

function onCategoryChanged(category) {
    const isClothing = (category === 'เสื้อ');
    const title = document.getElementById('optionsSectionTitle');
    const hint = document.getElementById('optionsSectionHint');
    const colName = document.getElementById('colHeaderName');
    const colStock = document.getElementById('colHeaderStock');
    const btnPresets = document.getElementById('btnAddClothingPresets');
    const btnAddText = document.getElementById('btnAddOptionText');
    const measurementCols = document.querySelectorAll('.col-measurement');

    if (isClothing) {
        if (title) title.textContent = 'ขนาดไซส์เสื้อ ราคา และสต็อก';
        if (hint) hint.textContent = 'สินค้าประเภทเสื้อ: กำหนดขนาดไซส์ (S, M, L, XL...) พร้อมรอบอกและความยาว';
        if (colName) colName.textContent = 'ขนาดไซส์ (Size)';
        if (colStock) colStock.textContent = 'สต็อก (ตัว)';
        if (btnPresets) btnPresets.classList.remove('hidden');
        if (btnAddText) btnAddText.textContent = 'เพิ่มไซส์';
        measurementCols.forEach(el => el.classList.remove('hidden'));
    } else {
        if (title) title.textContent = `ตัวเลือกสินค้า (${category}) ราคา และสต็อก`;
        if (category === 'แก้วน้ำ') {
            if (hint) hint.textContent = 'สินค้าประเภทแก้วน้ำ: กำหนดแบบ / ความจุ / สี (เช่น 500ml, สีน้ำเงิน, ทรงสูง) โดยไม่ต้องระบุรอบอกหรือความยาว';
        } else if (category === 'ของที่ระลึก') {
            if (hint) hint.textContent = 'สินค้าประเภทของที่ระลึก: กำหนดแบบ / รุ่น / สี (เช่น กล่องเซต, พวงกุญแจ, ชิ้นเดี่ยว) โดยไม่ต้องระบุรอบอกหรือความยาว';
        } else {
            if (hint) hint.textContent = 'สินค้าทั่วไป: กำหนดตัวเลือกสินค้า ราคา และจำนวนสต็อก';
        }
        if (colName) colName.textContent = 'แบบ / ตัวเลือกสินค้า';
        if (colStock) colStock.textContent = (category === 'แก้วน้ำ' ? 'สต็อก (ใบ)' : 'สต็อก (ชิ้น)');
        if (btnPresets) btnPresets.classList.add('hidden');
        if (btnAddText) btnAddText.textContent = 'เพิ่มตัวเลือก';
        measurementCols.forEach(el => el.classList.add('hidden'));
    }
}

let sizeIndex = 0;

function previewMainImage(input) {
    const mainImg = document.getElementById('mainImagePreview');
    const mainPlh = document.getElementById('mainImagePlaceholder');
    const mainHelp = document.getElementById('mainImageHelp');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            mainImg.src = e.target.result;
            mainImg.classList.remove('hidden');
            mainPlh.classList.add('hidden');
            if (mainHelp) mainHelp.textContent = 'เลือกภาพใหม่แล้ว: ' + input.files[0].name;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function previewNewGalleryImages(input) {
    const wrap = document.getElementById('newGalleryPreviews');
    const container = document.getElementById('newGalleryPreviewsContainer');
    container.innerHTML = '';
    if (input.files && input.files.length > 0) {
        wrap.classList.remove('hidden');
        Array.from(input.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const thumb = document.createElement('div');
                thumb.className = 'w-16 h-16 rounded-xl overflow-hidden border border-indigo-200 bg-white shadow-xs';
                thumb.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover" title="${file.name}">`;
                container.appendChild(thumb);
            };
            reader.readAsDataURL(file);
        });
    } else {
        wrap.classList.add('hidden');
    }
}

function openProductModal() {
    document.getElementById('modalTitle').textContent = 'เพิ่มสินค้าใหม่';
    document.getElementById('formProductId').value = '0';
    document.getElementById('formCode').value = '';
    document.getElementById('formCategory').value = 'เสื้อ';
    document.getElementById('formName').value = '';
    document.getElementById('formDesc').value = '';

    // Reset Main Image Preview
    const mainImg = document.getElementById('mainImagePreview');
    const mainPlh = document.getElementById('mainImagePlaceholder');
    const mainHelp = document.getElementById('mainImageHelp');
    const mainInput = document.getElementById('mainImageInput');
    if (mainInput) mainInput.value = '';
    mainImg.src = '';
    mainImg.classList.add('hidden');
    mainPlh.classList.remove('hidden');
    if (mainHelp) mainHelp.textContent = 'รองรับไฟล์ JPG, PNG, WEBP (เลือกไฟล์รูปภาพหลัก)';

    // Reset Gallery Inputs & Previews
    const galInput = document.getElementById('galleryImagesInput');
    if (galInput) galInput.value = '';
    document.getElementById('newGalleryPreviews').classList.add('hidden');
    document.getElementById('newGalleryPreviewsContainer').innerHTML = '';
    document.getElementById('existingGallerySection').classList.add('hidden');
    document.getElementById('existingGalleryContainer').innerHTML = '';

    onCategoryChanged('เสื้อ');
    populateClothingPresets();

    document.getElementById('productModal').classList.remove('hidden');
}

function closeProductModal() {
    document.getElementById('productModal').classList.add('hidden');
}

function populateClothingPresets() {
    const container = document.getElementById('sizeRowsContainer');
    container.innerHTML = '';
    const presets = [
        { name: 'S', chest: 36, len: 26, price: 280, stock: 50 },
        { name: 'M', chest: 38, len: 27, price: 280, stock: 50 },
        { name: 'L', chest: 40, len: 28, price: 280, stock: 50 },
        { name: 'XL', chest: 42, len: 29, price: 280, stock: 50 },
        { name: '2XL', chest: 44, len: 30, price: 310, stock: 30 },
        { name: '3XL', chest: 46, len: 31, price: 330, stock: 20 },
    ];
    presets.forEach(p => {
        addOptionRow(p.name, p.chest, p.len, p.price, p.stock, 0, 0);
    });
}

function addOptionRow(name = '', chest = '', len = '', price = 280, stock = 50, id = 0, isUnlimited = 0) {
    sizeIndex++;
    const isUnlimBool = (isUnlimited == 1 || isUnlimited === true);
    const chestVal = (chest !== null && chest !== undefined && chest !== '') ? chest : '';
    const lenVal = (len !== null && len !== undefined && len !== '') ? len : '';
    const currentCat = document.getElementById('formCategory').value;
    const isClothing = (currentCat === 'เสื้อ');
    const placeholderText = isClothing ? 'เช่น S, M, XL' : (currentCat === 'แก้วน้ำ' ? 'เช่น 500ml หรือ สีน้ำเงิน' : 'เช่น มาตรฐาน หรือ สีดำ');

    const tr = document.createElement('tr');
    tr.id = 'sizeRow_' + sizeIndex;
    tr.innerHTML = `
        <td class="p-2">
            <input type="hidden" name="sizes[${sizeIndex}][id]" value="${id}">
            <input type="text" name="sizes[${sizeIndex}][size_name]" value="${name}" required placeholder="${placeholderText}" class="w-full sm:w-36 px-2.5 py-1 border border-slate-200 rounded-lg text-xs font-bold">
        </td>
        <td class="p-2 text-center col-measurement ${isClothing ? '' : 'hidden'}">
            <input type="number" step="0.1" name="sizes[${sizeIndex}][chest_inch]" value="${chestVal}" placeholder="-" class="w-14 px-2 py-1 border border-slate-200 rounded-lg text-xs text-center">
        </td>
        <td class="p-2 text-center col-measurement ${isClothing ? '' : 'hidden'}">
            <input type="number" step="0.1" name="sizes[${sizeIndex}][length_inch]" value="${lenVal}" placeholder="-" class="w-14 px-2 py-1 border border-slate-200 rounded-lg text-xs text-center">
        </td>
        <td class="p-2 text-right">
            <input type="number" step="0.01" name="sizes[${sizeIndex}][price]" value="${price}" required class="w-20 px-2 py-1 border border-slate-200 rounded-lg text-xs text-right font-bold text-blue-600">
        </td>
        <td class="p-2 text-center">
            <label class="inline-flex items-center space-x-1 cursor-pointer">
                <input type="checkbox" name="sizes[${sizeIndex}][is_unlimited]" value="1" ${isUnlimBool ? 'checked' : ''} onchange="toggleUnlimited(${sizeIndex}, this.checked)" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500">
                <span class="text-[11px] font-semibold text-slate-600">ไม่จำกัด</span>
            </label>
        </td>
        <td class="p-2 text-center">
            <input type="number" id="stockInput_${sizeIndex}" name="sizes[${sizeIndex}][stock]" value="${isUnlimBool ? '' : stock}" ${isUnlimBool ? 'disabled placeholder=\"∞ ไม่จำกัด\"' : ''} ${isUnlimBool ? '' : 'required'} class="w-20 px-2 py-1 border border-slate-200 rounded-lg text-xs text-center font-bold disabled:bg-slate-100 disabled:text-slate-400">
        </td>
        <td class="p-2 text-center">
            <button type="button" onclick="document.getElementById('sizeRow_${sizeIndex}').remove()" class="text-rose-500 hover:text-rose-700 text-xs p-1">
                <i class="fas fa-trash-alt"></i>
            </button>
        </td>
    `;
    document.getElementById('sizeRowsContainer').appendChild(tr);
}

function toggleUnlimited(idx, checked) {
    const stockInput = document.getElementById('stockInput_' + idx);
    if (!stockInput) return;
    if (checked) {
        stockInput.value = '';
        stockInput.placeholder = '∞ ไม่จำกัด';
        stockInput.disabled = true;
        stockInput.required = false;
    } else {
        stockInput.disabled = false;
        stockInput.required = true;
        stockInput.placeholder = '50';
        if (!stockInput.value) stockInput.value = '50';
    }
}

function editProduct(prod, sizes, galleryImages = null) {
    document.getElementById('modalTitle').textContent = 'แก้ไขสินค้า: ' + prod.name;
    document.getElementById('formProductId').value = prod.id;
    document.getElementById('formCode').value = prod.code;
    document.getElementById('formCategory').value = prod.category || 'เสื้อ';
    document.getElementById('formName').value = prod.name;
    document.getElementById('formDesc').value = prod.description || '';
    if (prod.round_id) {
        document.getElementById('formRoundId').value = prod.round_id;
    }

    onCategoryChanged(prod.category || 'เสื้อ');

    // Set Main Image Preview
    const mainImg = document.getElementById('mainImagePreview');
    const mainPlh = document.getElementById('mainImagePlaceholder');
    const mainHelp = document.getElementById('mainImageHelp');
    const mainInput = document.getElementById('mainImageInput');
    if (mainInput) mainInput.value = '';
    
    if (prod.main_image) {
        mainImg.src = getAssetUrl(prod.main_image);
        mainImg.classList.remove('hidden');
        mainPlh.classList.add('hidden');
        if (mainHelp) mainHelp.textContent = 'มีรูปภาพหลักเดิมอยู่แล้ว (เลือกไฟล์ใหม่หากต้องการเปลี่ยนรูป)';
    } else {
        mainImg.src = '';
        mainImg.classList.add('hidden');
        mainPlh.classList.remove('hidden');
        if (mainHelp) mainHelp.textContent = 'ยังไม่มีรูปหลัก เลือกไฟล์เพื่ออัปโหลด (JPG, PNG, WEBP)';
    }

    // Reset new gallery selection previews
    const galInput = document.getElementById('galleryImagesInput');
    if (galInput) galInput.value = '';
    document.getElementById('newGalleryPreviews').classList.add('hidden');
    document.getElementById('newGalleryPreviewsContainer').innerHTML = '';

    // Render Existing Gallery Images
    const gallerySection = document.getElementById('existingGallerySection');
    const galleryContainer = document.getElementById('existingGalleryContainer');
    const galleryCount = document.getElementById('existingGalleryCount');
    galleryContainer.innerHTML = '';

    const effectiveGallery = (galleryImages !== null && galleryImages !== undefined) ? galleryImages : (productGalleryData[prod.id] || []);

    if (effectiveGallery && effectiveGallery.length > 0) {
        galleryCount.textContent = effectiveGallery.length;
        gallerySection.classList.remove('hidden');

        effectiveGallery.forEach(g => {
            const card = document.createElement('div');
            card.id = `galleryCard_${g.id}`;
            card.className = 'relative w-20 h-20 rounded-xl overflow-hidden border border-slate-200 bg-white group shadow-xs transition-all duration-200';
            card.innerHTML = `
                <img src="${getAssetUrl(g.image_path)}" class="w-full h-full object-cover">
                <button type="button" onclick="deleteGalleryImage(${g.id}, ${prod.id})" class="absolute inset-0 bg-rose-600/85 text-white flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition text-xs font-bold">
                    <i class="fas fa-trash-alt mb-1"></i> ลบรูป
                </button>
            `;
            galleryContainer.appendChild(card);
        });
    } else {
        gallerySection.classList.add('hidden');
    }

    // Render Sizes / Options
    const container = document.getElementById('sizeRowsContainer');
    container.innerHTML = '';
    
    if (sizes && sizes.length > 0) {
        sizes.forEach(s => {
            addOptionRow(s.size_name, s.chest_inch, s.length_inch, s.price, s.stock, s.id, s.is_unlimited);
        });
    } else {
        if (prod.category === 'เสื้อ') {
            populateClothingPresets();
        } else {
            addOptionRow('มาตรฐาน', '', '', 150, 50, 0, 1);
        }
    }

    document.getElementById('productModal').classList.remove('hidden');
}

async function deleteGalleryImage(imgId, prodId = null) {
    if (!prodId) {
        prodId = parseInt(document.getElementById('formProductId').value) || 0;
    }

    const res = await Swal.fire({
        title: 'ยืนยันการลบรูปภาพ?',
        text: 'ต้องการลบรูปภาพเพิ่มเติมนี้ออกจากระบบใช่หรือไม่',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'ลบรูปภาพ',
        cancelButtonText: 'ยกเลิก'
    });

    if (!res.isConfirmed) return;

    const card = document.getElementById(`galleryCard_${imgId}`);
    if (card) {
        card.style.opacity = '0.35';
        card.style.pointerEvents = 'none';
    }

    try {
        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('action', 'delete_gallery_image');
        formData.append('image_id', imgId);
        formData.append('ajax', '1');

        const response = await fetch('<?= base_url("admin/products.php") ?>', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            // Animate card removal in modal smoothly without closing the modal
            if (card) {
                card.classList.add('scale-75', 'opacity-0');
                setTimeout(() => {
                    card.remove();
                    const container = document.getElementById('existingGalleryContainer');
                    const remainingCards = container ? container.querySelectorAll('[id^="galleryCard_"]').length : 0;
                    const galleryCount = document.getElementById('existingGalleryCount');
                    if (galleryCount) galleryCount.textContent = remainingCards;
                    if (remainingCards === 0) {
                        const gallerySection = document.getElementById('existingGallerySection');
                        if (gallerySection) gallerySection.classList.add('hidden');
                    }
                }, 200);
            }

            // Keep in-memory gallery data synchronized
            const targetPid = data.product_id || prodId;
            if (productGalleryData && productGalleryData[targetPid]) {
                productGalleryData[targetPid] = productGalleryData[targetPid].filter(item => item.id != imgId);
            }

            // Synchronize gallery thumbnail and counter badge on the main product list
            const thumbInList = document.getElementById(`productGalleryThumb_${imgId}`);
            if (thumbInList) thumbInList.remove();

            const badgeInList = document.getElementById(`productGalleryBadge_${targetPid}`);
            if (badgeInList) {
                const updatedCount = (productGalleryData[targetPid] || []).length;
                if (updatedCount > 0) {
                    badgeInList.textContent = `+${updatedCount} รูป`;
                } else {
                    badgeInList.remove();
                }
            }

            // Show discreet Toast alert so user can continue editing peacefully
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            });
            Toast.fire({
                icon: 'success',
                title: 'ลบรูปภาพเพิ่มเติมเรียบร้อยแล้ว'
            });
        } else {
            if (card) {
                card.style.opacity = '1';
                card.style.pointerEvents = 'auto';
            }
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: data.message || 'ไม่สามารถลบรูปภาพได้'
            });
        }
    } catch (err) {
        console.error('Delete gallery image error:', err);
        if (card) {
            card.style.opacity = '1';
            card.style.pointerEvents = 'auto';
        }
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถเชื่อมต่อเพื่อลบรูปภาพได้ กรุณาลองใหม่อีกครั้ง'
        });
    }
}

// Auto-open modal if redirected with edit_id parameter
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const editId = urlParams.get('edit_id');
    if (editId) {
        const pId = parseInt(editId);
        const prod = (allProductsData || []).find(p => p.id == pId);
        if (prod) {
            const sizes = (productSizesData || {})[pId] || [];
            editProduct(prod, sizes);
            const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
            window.history.replaceState({ path: cleanUrl }, '', cleanUrl);
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
