<?php
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helper.php';

Auth::requireLogin();
$currentUser = Auth::user();
$pdo = Database::getInstance()->getConnection();

// Get rounds
$rounds = $pdo->query("SELECT * FROM booking_rounds ORDER BY id DESC")->fetchAll();

$roundId = isset($_GET['round_id']) ? (int)$_GET['round_id'] : (!empty($rounds) ? (int)$rounds[0]['id'] : 0);
$selectedCategory = trim($_GET['category'] ?? '');

// Fetch active round details
$currentRound = null;
if ($roundId > 0) {
    $roundStmt = $pdo->prepare("SELECT * FROM booking_rounds WHERE id = ?");
    $roundStmt->execute([$roundId]);
    $currentRound = $roundStmt->fetch();
}

// Fetch distinct categories from products
$allCategories = $pdo->query("SELECT DISTINCT category FROM products WHERE is_active = 1 ORDER BY FIELD(category, 'เสื้อ', 'แก้วน้ำ', 'ของที่ระลึก', 'อื่นๆ') ASC, category ASC")->fetchAll(PDO::FETCH_COLUMN);

// Aggregate Size Breakdown for Confirmed Orders
$sql = "
    SELECT 
        p.id as product_id,
        p.code as product_code,
        p.name as product_name,
        p.category,
        ps.id as size_id,
        ps.size_name,
        ps.chest_inch,
        ps.length_inch,
        ps.price,
        COALESCE(SUM(oi.quantity), 0) as confirmed_qty,
        COALESCE(SUM(oi.subtotal), 0) as confirmed_subtotal
    FROM products p
    JOIN product_sizes ps ON p.id = ps.product_id
    LEFT JOIN order_items oi ON ps.id = oi.product_size_id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.status IN ('confirmed', 'completed') AND (o.round_id = ? OR ? = 0)
    WHERE p.is_active = 1
";

$params = [$roundId, $roundId];
if (!empty($selectedCategory)) {
    $sql .= " AND p.category = ? ";
    $params[] = $selectedCategory;
}

$sql .= "
    GROUP BY p.id, ps.id
    ORDER BY FIELD(p.category, 'เสื้อ', 'แก้วน้ำ', 'ของที่ระลึก', 'อื่นๆ') ASC, p.category ASC, p.sort_order ASC, p.id ASC, ps.sort_order ASC, ps.id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Structure data grouped by Category -> Product -> Size
$categoriesSummary = [];
$grandTotalPieces = 0;
$grandTotalRevenue = 0;

foreach ($rows as $r) {
    $cat = $r['category'] ?: 'ทั่วไป';
    $pid = $r['product_id'];
    $sName = $r['size_name'];
    $qty = (int)$r['confirmed_qty'];
    $subtotal = (float)$r['confirmed_subtotal'];

    if (!isset($categoriesSummary[$cat])) {
        $categoriesSummary[$cat] = [
            'name' => $cat,
            'size_names' => [],
            'products' => [],
            'total_qty' => 0,
            'total_amount' => 0
        ];
    }

    if (!in_array($sName, $categoriesSummary[$cat]['size_names'])) {
        $categoriesSummary[$cat]['size_names'][] = $sName;
    }

    if (!isset($categoriesSummary[$cat]['products'][$pid])) {
        $categoriesSummary[$cat]['products'][$pid] = [
            'id' => $pid,
            'code' => $r['product_code'],
            'name' => $r['product_name'],
            'category' => $cat,
            'sizes' => [],
            'total_qty' => 0,
            'total_amount' => 0
        ];
    }

    $categoriesSummary[$cat]['products'][$pid]['sizes'][$sName] = [
        'qty' => $qty,
        'chest' => $r['chest_inch'],
        'length' => $r['length_inch'],
        'price' => $r['price']
    ];

    $categoriesSummary[$cat]['products'][$pid]['total_qty'] += $qty;
    $categoriesSummary[$cat]['products'][$pid]['total_amount'] += $subtotal;

    $categoriesSummary[$cat]['total_qty'] += $qty;
    $categoriesSummary[$cat]['total_amount'] += $subtotal;

    $grandTotalPieces += $qty;
    $grandTotalRevenue += $subtotal;
}

// -------------------------------------------------------------
// EXCEL EXPORT HANDLER
// -------------------------------------------------------------
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $roundLabel = $roundId === 0 ? 'ทุกรอบการสั่งจอง' : ($currentRound['round_name'] ?? ('รอบที่ ' . $roundId));
    $catSlug = !empty($selectedCategory) ? '_' . $selectedCategory : '_ทุกหมวดหมู่';
    $cleanRoundLabel = preg_replace('/[^\p{L}\p{N}_\-]/u', '_', $roundLabel);
    $filename = 'CMTC_Production_Summary_' . date('Ymd_His') . '_Round' . $roundId . $catSlug . '.xls';

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate');
    header('Pragma: public');
    header('Expires: 0');

    // Send UTF-8 BOM so Thai text renders perfectly in Excel
    echo "\xEF\xBB\xBF";
    ?>
    <html xmlns:o="urn:schemas-microsoft-com:office:office"
          xmlns:x="urn:schemas-microsoft-com:office:excel"
          xmlns="http://www.w3.org/TR/REC-html40">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <!--[if gte mso 9]>
        <xml>
        <x:ExcelWorkbook>
        <x:ExcelWorksheets>
        <x:ExcelWorksheet>
        <x:Name><?= htmlspecialchars(mb_substr($roundLabel, 0, 30)) ?></x:Name>
        <x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
        </x:ExcelWorksheet>
        </x:ExcelWorksheets>
        </x:ExcelWorkbook>
        </xml>
        <![endif]-->
        <style>
            body { font-family: 'Sarabun', 'Cordia New', 'TH Sarabun New', Tahoma, sans-serif; font-size: 11pt; color: #000; }
            table { border-collapse: collapse; width: 100%; margin-bottom: 25px; }
            th, td { border: 0.5pt solid #555555; padding: 6px 10px; vertical-align: middle; }
            .title { font-size: 16pt; font-weight: bold; text-align: center; }
            .subtitle { font-size: 11pt; text-align: center; color: #555555; }
            .meta-table td { border: none; padding: 3px 0; font-size: 10pt; }
            .cat-header { background-color: #1e3a8a; color: #ffffff; font-weight: bold; font-size: 12pt; text-align: left; padding: 8px 12px; }
            .th-header { background-color: #0f172a; color: #ffffff; font-weight: bold; text-align: center; }
            .subtotal-row { background-color: #e2e8f0; font-weight: bold; }
            .grand-total-row { background-color: #0f172a; color: #ffffff; font-weight: bold; font-size: 12pt; }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .text-left { text-align: left; }
            .num-int { mso-number-format: "#,##0"; text-align: center; }
            .num-money { mso-number-format: "#,##0.00"; text-align: right; }
            .notes-box { font-size: 10pt; border: 0.5pt solid #cccccc; background-color: #f8fafc; padding: 10px; margin-top: 15px; }
        </style>
    </head>
    <body>
        <table class="meta-table" style="border:none; margin-bottom: 15px;">
            <tr>
                <td colspan="6" class="title">ใบสั่งผลิตและสรุปยอดตัดสต็อกสินค้า (แยกตามหมวดหมู่)</td>
            </tr>
            <tr>
                <td colspan="6" class="subtitle">ระบบจองเสื้อกิจกรรมและของที่ระลึกออนไลน์ วิทยาลัยเทคนิคเชียงใหม่ (CMTC Shop)</td>
            </tr>
            <tr><td colspan="6" style="height: 10px;"></td></tr>
            <tr>
                <td colspan="3"><strong>รอบการผลิต:</strong> <?= htmlspecialchars($roundLabel) ?></td>
                <td colspan="3" class="text-right"><strong>วันที่ออกรายงาน:</strong> <?= Helper::thaiDate(date('Y-m-d H:i:s')) ?></td>
            </tr>
            <tr>
                <td colspan="3"><strong>หมวดหมู่ที่เลือก:</strong> <?= htmlspecialchars($selectedCategory ?: 'ทุกหมวดหมู่สินค้า') ?></td>
                <td colspan="3" class="text-right"><strong>เจ้าหน้าที่ผู้จัดทำ:</strong> <?= htmlspecialchars($currentUser['fullname']) ?></td>
            </tr>
            <tr>
                <td colspan="3"><strong>ยอดสั่งผลิตรวมสุทธิ:</strong> <?= number_format($grandTotalPieces) ?> ชิ้น/ตัว</td>
                <td colspan="3" class="text-right"><strong>มูลค่ารวมสุทธิ:</strong> ฿<?= number_format($grandTotalRevenue, 2) ?> บาท</td>
            </tr>
        </table>

        <?php if (!empty($categoriesSummary)): ?>
            <?php foreach ($categoriesSummary as $catName => $catData): ?>
                <?php 
                    $colCount = count($catData['size_names']) + 5; 
                ?>
                <table>
                    <thead>
                        <tr>
                            <th colspan="<?= $colCount ?>" class="cat-header">
                                หมวดหมู่: <?= htmlspecialchars($catName) ?> 
                                (ยอดรวม <?= number_format($catData['total_qty']) ?> ชิ้น • ฿<?= number_format($catData['total_amount'], 2) ?> บาท)
                            </th>
                        </tr>
                        <tr>
                            <th class="th-header" style="width: 40px;">#</th>
                            <th class="th-header" style="width: 140px;">รหัสสินค้า</th>
                            <th class="th-header" style="width: 280px; text-align: left;">ชื่อสินค้า / แบบ</th>
                            <?php foreach ($catData['size_names'] as $sn): ?>
                                <th class="th-header"><?= htmlspecialchars($sn) ?></th>
                            <?php endforeach; ?>
                            <th class="th-header" style="background-color: #1e3a8a;">รวม (ชิ้น)</th>
                            <th class="th-header" style="background-color: #0f172a; text-align: right;">ยอดรวม (บาท)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rowNo = 1; foreach ($catData['products'] as $p): ?>
                            <tr>
                                <td class="text-center"><?= $rowNo++ ?></td>
                                <td class="text-center font-bold" style="mso-number-format: '\@';"><?= htmlspecialchars($p['code']) ?></td>
                                <td class="text-left"><?= htmlspecialchars($p['name']) ?></td>
                                <?php foreach ($catData['size_names'] as $sn): ?>
                                    <?php $qty = $p['sizes'][$sn]['qty'] ?? 0; ?>
                                    <td class="num-int <?= $qty > 0 ? 'font-bold' : '' ?>" style="<?= $qty > 0 ? 'background-color: #eff6ff;' : 'color: #999999;' ?>">
                                        <?= $qty > 0 ? $qty : 0 ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="num-int font-bold" style="background-color: #dbeafe; color: #1e40af; font-weight: bold;">
                                    <?= $p['total_qty'] ?>
                                </td>
                                <td class="num-money font-bold" style="background-color: #f8fafc;">
                                    <?= $p['total_amount'] ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="subtotal-row">
                            <td colspan="3" class="text-right" style="font-weight: bold;">
                                รวมเฉพาะหมวดหมู่ "<?= htmlspecialchars($catName) ?>":
                            </td>
                            <?php foreach ($catData['size_names'] as $sn): ?>
                                <?php 
                                    $colTotal = 0;
                                    foreach ($catData['products'] as $p) {
                                        $colTotal += ($p['sizes'][$sn]['qty'] ?? 0);
                                    }
                                ?>
                                <td class="num-int font-bold" style="color: #1e40af;">
                                    <?= $colTotal ?>
                                </td>
                            <?php endforeach; ?>
                            <td class="num-int font-bold" style="background-color: #bfdbfe; color: #1e3a8a; font-size: 11pt;">
                                <?= $catData['total_qty'] ?>
                            </td>
                            <td class="num-money font-bold" style="background-color: #e2e8f0; color: #0f172a; font-size: 11pt;">
                                <?= $catData['total_amount'] ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            <?php endforeach; ?>

            <!-- Grand Total Overall Summary Box in Excel -->
            <table>
                <tr class="grand-total-row">
                    <td colspan="3" class="text-right" style="padding: 10px 14px; font-size: 12pt;">
                        รวมยอดสั่งผลิตสุทธิทุกหมวดหมู่ (Grand Total):
                    </td>
                    <td class="num-int text-center" style="font-size: 14pt; color: #facc15; padding: 10px;">
                        <?= $grandTotalPieces ?> ชิ้น/ตัว
                    </td>
                    <td class="num-money text-right" style="font-size: 13pt; color: #4ade80; padding: 10px;">
                        ฿<?= number_format($grandTotalRevenue, 2) ?> บาท
                    </td>
                </tr>
            </table>

        <?php else: ?>
            <table>
                <tr>
                    <td class="text-center" style="padding: 30px; color: #888888;">
                        ไม่พบข้อมูลคำสั่งจองที่ยืนยันการชำระเงินในรอบนี้
                    </td>
                </tr>
            </table>
        <?php endif; ?>

        <div class="notes-box">
            <strong>ข้อกำหนดและคำแนะนำในการผลิต:</strong><br />
            1. ผลิตตามมาตรฐานและตัวอย่างที่ได้รับอนุมัติจากทางวิทยาลัยเทคนิคเชียงใหม่<br />
            2. บรรจุแยกถุงพลาสติก 1 ชิ้น/ถุง พร้อมติดสติกเกอร์ระบุขนาด (Size) ที่มุมถุงอย่างชัดเจน<br />
            3. จัดเรียงลงกล่องแยกลังตามแบบและขนาด เพื่อความสะดวกในการตรวจนับและแจกจ่าย
        </div>

        <table style="border:none; margin-top: 40px; text-align: center;">
            <tr style="border:none;">
                <td style="border:none; width: 33%;">
                    ........................................................<br />
                    ( <?= htmlspecialchars($currentUser['fullname']) ?> )<br />
                    <strong>ผู้จัดทำรายงาน / เจ้าหน้าที่รับจอง</strong><br />
                    วันที่: ..... / ..... / .........
                </td>
                <td style="border:none; width: 34%;">
                    ........................................................<br />
                    ( .................................................... )<br />
                    <strong>ผู้อนุมัติสั่งผลิต (ผู้บริหาร)</strong><br />
                    วันที่: ..... / ..... / .........
                </td>
                <td style="border:none; width: 33%;">
                    ........................................................<br />
                    ( .................................................... )<br />
                    <strong>ตัวแทนโรงงานผู้รับคำสั่งผลิต</strong><br />
                    วันที่: ..... / ..... / .........
                </td>
            </tr>
        </table>
    </body>
    </html>
    <?php
    exit;
}

// -------------------------------------------------------------
// NORMAL PAGE RENDER
// -------------------------------------------------------------
$pageTitle = "สรุปยอดสั่งจองแยกตามหมวดหมู่และขนาด - CMTC Admin";
$activePage = 'summary';
require_once __DIR__ . '/../includes/admin_header.php';

$catBadgeClasses = [
    'เสื้อ' => 'bg-blue-50 text-blue-700 border-blue-200',
    'แก้วน้ำ' => 'bg-amber-50 text-amber-700 border-amber-200',
    'ของที่ระลึก' => 'bg-purple-50 text-purple-700 border-purple-200',
    'อื่นๆ' => 'bg-emerald-50 text-emerald-700 border-emerald-200'
];

$catIcons = [
    'เสื้อ' => 'fas fa-tshirt',
    'แก้วน้ำ' => 'fas fa-mug-hot',
    'ของที่ระลึก' => 'fas fa-award',
    'อื่นๆ' => 'fas fa-box-open'
];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Top Header: Title & Description (Full Width) -->
    <div class="mb-5 no-print">
        <div class="inline-flex items-center space-x-2 px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 text-xs font-bold mb-1.5 border border-blue-200/60">
            <i class="fas fa-layer-group text-blue-600"></i>
            <span>รายงานแยกตามหมวดหมู่สินค้า</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">สรุปยอดสั่งจองแยกตามหมวดหมู่และขนาด (ส่งโรงงาน)</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">รายงานตัดสต็อกเฉพาะคำสั่งจองที่ยืนยันชำระเงินแล้ว เพื่อส่งผลิต แยกตารางตามหมวดหมู่สินค้า</p>
    </div>

    <!-- Filter Toolbar & Action Bar (Next line / Full row) -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-3 bg-white p-3.5 sm:p-4 rounded-2xl border border-slate-200/80 shadow-xs no-print">
        <!-- Filter Form (เมนู รอบที่ & ทุกหมวดหมู่) -->
        <form action="<?= base_url('admin/summary.php') ?>" method="GET" class="flex flex-wrap items-center gap-2.5 sm:gap-3">
            <!-- Round Selector -->
            <div class="flex items-center space-x-2">
                <label for="roundSelect" class="text-xs font-bold text-slate-600 whitespace-nowrap">
                    <i class="fas fa-calendar-alt text-slate-400 mr-1"></i> รอบที่:
                </label>
                <select id="roundSelect" name="round_id" onchange="this.form.submit()" class="px-3.5 py-2 text-xs rounded-xl border border-slate-300 font-semibold text-slate-900 bg-white shadow-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="0" <?= $roundId === 0 ? 'selected' : '' ?>>ทุกรอบการสั่งจอง (รวมทั้งหมด)</option>
                    <?php foreach ($rounds as $rnd): ?>
                        <option value="<?= $rnd['id'] ?>" <?= $roundId == $rnd['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($rnd['round_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Category Selector -->
            <div class="flex items-center space-x-2">
                <label for="catSelect" class="text-xs font-bold text-slate-600 whitespace-nowrap">
                    <i class="fas fa-tags text-slate-400 mr-1"></i> หมวดหมู่:
                </label>
                <select id="catSelect" name="category" onchange="this.form.submit()" class="px-3.5 py-2 text-xs rounded-xl border border-slate-300 font-semibold text-slate-900 bg-white shadow-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="">ทุกหมวดหมู่ (แสดงทั้งหมด)</option>
                    <?php foreach ($allCategories as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= $selectedCategory === $c ? 'selected' : '' ?>>
                            หมวดหมู่: <?= htmlspecialchars($c) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2 pt-2 md:pt-0 border-t md:border-t-0 border-slate-100 flex-wrap sm:flex-nowrap">
            <!-- Export to Excel Button -->
            <a href="<?= base_url('admin/summary.php?export=excel&round_id=' . $roundId . '&category=' . urlencode($selectedCategory)) ?>" 
               class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition flex items-center justify-center active:scale-95 group whitespace-nowrap"
               title="ดาวน์โหลดไฟล์สรุปสำหรับเปิดใน Microsoft Excel หรือ Google Sheets">
                <i class="fas fa-file-excel mr-2 text-emerald-200 group-hover:scale-110 transition-transform"></i> 
                <span>ส่งออกเป็นไฟล์ Excel</span>
            </a>

            <!-- Print / PDF Button -->
            <button onclick="window.print()" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-xs transition flex items-center justify-center active:scale-95 btn-touch whitespace-nowrap">
                <i class="fas fa-print mr-2 text-slate-300"></i> สั่งพิมพ์ (Print / PDF)
            </button>
        </div>
    </div>

    <!-- Category Tabs Navigation (Hidden when printing) -->
    <div class="mb-6 flex flex-wrap items-center gap-2 no-print">
        <a href="<?= base_url('admin/summary.php?round_id=' . $roundId) ?>" 
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 <?= empty($selectedCategory) ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50' ?>">
            <i class="fas fa-th-large"></i>
            <span>ทั้งหมด (ทุกหมวดหมู่)</span>
            <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] <?= empty($selectedCategory) ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' ?>">
                <?= number_format($grandTotalPieces) ?>
            </span>
        </a>

        <?php foreach ($allCategories as $c): ?>
            <?php 
                $cQty = isset($categoriesSummary[$c]) ? $categoriesSummary[$c]['total_qty'] : 0;
                $cIcon = $catIcons[$c] ?? 'fas fa-tag';
                $isActive = ($selectedCategory === $c);
            ?>
            <a href="<?= base_url('admin/summary.php?round_id=' . $roundId . '&category=' . urlencode($c)) ?>" 
               class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 <?= $isActive ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50' ?>">
                <i class="<?= $cIcon ?> text-[11px]"></i>
                <span><?= htmlspecialchars($c) ?></span>
                <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] <?= $isActive ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' ?>">
                    <?= number_format($cQty) ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Printable Report Container -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-4 sm:p-8 lg:p-10 printable-card">
        
        <!-- Report Letterhead Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start pb-6 border-b-2 border-slate-900 gap-4 mb-8">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center font-black text-lg flex-shrink-0">
                        <i class="fas fa-industry"></i>
                    </div>
                    <div>
                        <h2 class="font-black text-xl text-slate-900 tracking-tight">ใบสั่งผลิตและสรุปยอดตัดสต็อกสินค้า</h2>
                        <p class="text-xs text-slate-500 font-medium">CMTC Production & Inventory Allocation Summary (Categorized)</p>
                    </div>
                </div>
                <div class="text-xs text-slate-600 space-y-0.5">
                    <div>รอบการผลิต: <strong><?= htmlspecialchars($roundId === 0 ? 'ทุกรอบการสั่งจอง (รวมทั้งหมด)' : ($currentRound['round_name'] ?? 'รอบที่ ' . $roundId)) ?></strong></div>
                    <div>หมวดหมู่ที่แสดง: <strong><?= htmlspecialchars(!empty($selectedCategory) ? $selectedCategory : 'ทุกหมวดหมู่สินค้า') ?></strong></div>
                    <div>วันที่ออกรายงาน: <?= Helper::thaiDate(date('Y-m-d H:i:s')) ?> • โดยเจ้าหน้าที่: <?= htmlspecialchars($currentUser['fullname']) ?></div>
                </div>
            </div>

            <!-- Total Badge Box -->
            <div class="w-full sm:w-auto text-left sm:text-right bg-slate-50 p-4 rounded-2xl border border-slate-200">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">จำนวนผลิตรวมสุทธิ</div>
                <div class="text-3xl font-black text-blue-600 mt-1"><?= number_format($grandTotalPieces) ?> <span class="text-sm text-slate-600 font-bold">ชิ้น/ตัว</span></div>
                <div class="text-xs text-slate-500 mt-0.5">มูลค่าผลิตรวม: ฿<?= Helper::money($grandTotalRevenue) ?></div>
            </div>
        </div>

        <?php if (!empty($categoriesSummary)): ?>
            
            <!-- Loop through each category table -->
            <?php foreach ($categoriesSummary as $catName => $catData): ?>
                <?php 
                    $cIcon = $catIcons[$catName] ?? 'fas fa-layer-group';
                    $cBadge = $catBadgeClasses[$catName] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                ?>
                <div class="mb-10 last:mb-6">
                    
                    <!-- Category Section Header -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 mb-3 border-b border-slate-200 gap-2">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-sm font-bold flex-shrink-0">
                                <i class="<?= $cIcon ?>"></i>
                            </div>
                            <div>
                                <h3 class="font-black text-base text-slate-900 tracking-tight flex items-center gap-2">
                                    หมวดหมู่: <?= htmlspecialchars($catName) ?>
                                    <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full border <?= $cBadge ?>">
                                        <?= count($catData['products']) ?> รายการแบบ
                                    </span>
                                </h3>
                            </div>
                        </div>

                        <div class="text-xs text-slate-600 flex items-center gap-3">
                            <span>ยอดผลิตหมวดหมู่นี้: <strong class="text-blue-700 font-black text-sm"><?= number_format($catData['total_qty']) ?></strong> ชิ้น</span>
                            <span class="text-slate-300">|</span>
                            <span>รวมมูลค่า: <strong class="text-slate-900 font-black text-sm">฿<?= Helper::money($catData['total_amount']) ?></strong></span>
                        </div>
                    </div>

                    <!-- Size Matrix Table for this Category -->
                    <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-2xs mb-2">
                        <table class="w-full text-xs text-left min-w-[600px]">
                            <thead class="bg-slate-900 text-white font-bold">
                                <tr>
                                    <th class="py-3 px-3 text-center w-10">#</th>
                                    <th class="py-3 px-3 w-32">รหัสสินค้า</th>
                                    <th class="py-3 px-4">ชื่อสินค้า / แบบ</th>
                                    <?php foreach ($catData['size_names'] as $sn): ?>
                                        <th class="py-3 px-3 text-center min-w-[65px]"><?= htmlspecialchars($sn) ?></th>
                                    <?php endforeach; ?>
                                    <th class="py-3 px-3 text-center bg-blue-900 w-28">รวม (ชิ้น)</th>
                                    <th class="py-3 px-4 text-right bg-blue-950 w-32">ยอดรวม (บาท)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                                <?php $no = 1; foreach ($catData['products'] as $p): ?>
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="py-3 px-3 text-center font-bold text-slate-400"><?= $no++ ?></td>
                                        <td class="py-3 px-3 font-mono font-bold text-slate-900"><?= htmlspecialchars($p['code']) ?></td>
                                        <td class="py-3 px-4 font-bold text-slate-900">
                                            <?= htmlspecialchars($p['name']) ?>
                                        </td>

                                        <?php foreach ($catData['size_names'] as $sn): ?>
                                            <?php $qty = $p['sizes'][$sn]['qty'] ?? 0; ?>
                                            <td class="py-3 px-3 text-center font-bold <?= $qty > 0 ? 'text-slate-900 bg-blue-50/50' : 'text-slate-300' ?>">
                                                <?= $qty > 0 ? number_format($qty) : '-' ?>
                                            </td>
                                        <?php endforeach; ?>

                                        <td class="py-3 px-3 text-center font-black text-blue-700 bg-blue-50/70 text-sm">
                                            <?= number_format($p['total_qty']) ?>
                                        </td>
                                        <td class="py-3 px-4 text-right font-black text-slate-900 bg-slate-50">
                                            ฿<?= Helper::money($p['total_amount']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-slate-800 text-white font-bold text-xs">
                                <tr>
                                    <td colspan="3" class="py-3 px-4 text-right uppercase tracking-wider font-bold">
                                        รวมเฉพาะหมวดหมู่ "<?= htmlspecialchars($catName) ?>":
                                    </td>
                                    <?php foreach ($catData['size_names'] as $sn): ?>
                                        <?php 
                                            $colTotal = 0;
                                            foreach ($catData['products'] as $p) {
                                                $colTotal += ($p['sizes'][$sn]['qty'] ?? 0);
                                            }
                                        ?>
                                        <td class="py-3 px-3 text-center text-amber-300 font-black">
                                            <?= $colTotal > 0 ? number_format($colTotal) : '-' ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="py-3 px-3 text-center text-amber-300 font-black text-sm bg-blue-900">
                                        <?= number_format($catData['total_qty']) ?>
                                    </td>
                                    <td class="py-3 px-4 text-right text-emerald-400 font-black text-xs bg-blue-950">
                                        ฿<?= Helper::money($catData['total_amount']) ?>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                </div>
            <?php endforeach; ?>

            <!-- Overall Grand Total Summary Card (Shows when multiple categories displayed) -->
            <?php if (empty($selectedCategory) && count($categoriesSummary) > 1): ?>
                <div class="mt-8 p-5 rounded-2xl bg-gradient-to-r from-slate-900 via-blue-950 to-slate-900 text-white flex flex-col sm:flex-row items-center justify-between gap-4 shadow-md">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 rounded-xl bg-amber-400 text-slate-950 flex items-center justify-center font-black text-xl shadow-sm">
                            <i class="fas fa-check-double"></i>
                        </div>
                        <div>
                            <div class="text-xs uppercase text-slate-400 tracking-wider font-bold">สรุปยอดรวมทุกหมวดหมู่สินค้า</div>
                            <div class="text-base font-extrabold tracking-tight">รวมยอดสั่งผลิตและมูลค่ารวมสุทธิทั้งหมด (Grand Total)</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-6 text-right">
                        <div>
                            <div class="text-[11px] text-slate-400 font-semibold">จำนวนชิ้นรวมทั้งหมด</div>
                            <div class="text-2xl font-black text-amber-300"><?= number_format($grandTotalPieces) ?> <span class="text-xs text-white font-normal">ชิ้น/ตัว</span></div>
                        </div>
                        <div class="border-l border-slate-700 pl-6">
                            <div class="text-[11px] text-slate-400 font-semibold">มูลค่ารวมทั้งหมด</div>
                            <div class="text-2xl font-black text-emerald-400">฿<?= Helper::money($grandTotalRevenue) ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="py-16 text-center text-slate-400">
                <i class="fas fa-box-open text-5xl mb-3 text-slate-300"></i>
                <p class="font-bold text-sm">ไม่พบข้อมูลยอดจองที่ยืนยันการชำระเงินในรอบและหมวดหมู่นี้</p>
                <p class="text-xs text-slate-400 mt-1">กรุณาเลือกรอบการสั่งจองอื่น หรือเลือกแสดงทุกหมวดหมู่</p>
            </div>
        <?php endif; ?>

        <!-- Factory Specifications & Instructions Box -->
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-600 mt-8 mb-8 space-y-1 leading-relaxed">
            <div class="font-bold text-slate-800 mb-1 flex items-center">
                <i class="fas fa-clipboard-check text-blue-600 mr-2"></i> ข้อกำหนดและคำแนะนำในการผลิต:
            </div>
            <div>• ผลิตตามสเปกผ้าและสีตัวอย่างที่ได้รับอนุมัติจากทางวิทยาลัยเทคนิคเชียงใหม่</div>
            <div>• บรรจุแยกถุงพลาสติก 1 ตัวต่อ 1 ถุง พร้อมติดสติกเกอร์ระบุขนาด (Size) ที่มุมขวาบนของทุกถุงอย่างชัดเจน</div>
            <div>• บรรจุกล่องแยกลังตามแบบและขนาด เพื่อความสะดวกในการตรวจนับและแจกจ่ายแก่นักศึกษา</div>
        </div>

        <!-- Factory & Administrative Signature Section -->
        <div class="grid grid-cols-3 gap-6 pt-6 border-t-2 border-slate-200 text-center text-xs">
            <div>
                <div class="border-b border-slate-400 w-44 mx-auto mb-2 mt-8"></div>
                <div class="font-bold text-slate-800">ผู้จัดทำรายงาน / เจ้าหน้าที่รับจอง</div>
                <div class="text-[11px] text-slate-500 mt-0.5">( <?= htmlspecialchars($currentUser['fullname']) ?> )</div>
                <div class="text-[10px] text-slate-400 mt-0.5">วันที่: ..... / ..... / .........</div>
            </div>

            <div>
                <div class="border-b border-slate-400 w-44 mx-auto mb-2 mt-8"></div>
                <div class="font-bold text-slate-800">ผู้อนุมัติสั่งตัดเย็บ (ผู้บริหาร)</div>
                <div class="text-[11px] text-slate-500 mt-0.5">( .................................................... )</div>
                <div class="text-[10px] text-slate-400 mt-0.5">วันที่: ..... / ..... / .........</div>
            </div>

            <div>
                <div class="border-b border-slate-400 w-44 mx-auto mb-2 mt-8"></div>
                <div class="font-bold text-slate-800">ตัวแทนโรงงานผู้รับคำสั่งผลิต</div>
                <div class="text-[11px] text-slate-500 mt-0.5">( .................................................... )</div>
                <div class="text-[10px] text-slate-400 mt-0.5">วันที่: ..... / ..... / .........</div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
