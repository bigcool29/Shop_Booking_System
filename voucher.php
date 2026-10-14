<?php
require_once __DIR__ . '/config/settings.php';
require_once __DIR__ . '/core/Helper.php';

$code = trim($_GET['code'] ?? '');
if (empty($code)) {
    header("Location: " . base_url('track.php'));
    exit;
}

$pdo = Database::getInstance()->getConnection();
$stmt = $pdo->prepare("
    SELECT o.*, r.round_name 
    FROM orders o 
    LEFT JOIN booking_rounds r ON o.round_id = r.id 
    WHERE o.order_code = ? LIMIT 1
");
$stmt->execute([$code]);
$order = $stmt->fetch();

if (!$order) {
    die("Error: Order not found");
}

// Fetch order items
$itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$itemStmt->execute([$order['id']]);
$items = $itemStmt->fetchAll();

// Fetch payment info
$payStmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ? AND status = 'approved' ORDER BY id DESC LIMIT 1");
$payStmt->execute([$order['id']]);
$payment = $payStmt->fetch();

$totalPieces = 0;
foreach ($items as $it) {
    $totalPieces += (int)$it['quantity'];
}

$verificationUrl = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . base_url('track.php?search=' . urlencode($order['order_code']));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบรับสินค้า - <?= htmlspecialchars($order['order_code']) ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/custom.css') ?>">
    <script src="<?= base_url('assets/js/qrcode.min.js') ?>"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; font-size: 13px !important; }
            .print-card { box-shadow: none !important; border: 1px solid #94a3b8 !important; padding: 1.5rem !important; }
        }
    </style>
</head>
<body class="bg-slate-100 py-8 px-4 font-sans text-slate-800 antialiased min-h-screen">

    <!-- Top Action Bar (hidden when printing) -->
    <div class="max-w-3xl mx-auto mb-6 flex justify-between items-center no-print">
        <a href="<?= base_url('payment.php?code=' . urlencode($order['order_code'])) ?>" class="inline-flex items-center text-xs font-semibold text-slate-600 hover:text-slate-900 bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-200">
            <i class="fas fa-arrow-left mr-2"></i> ย้อนกลับ
        </a>
        <div class="flex items-center space-x-2">
            <button onclick="window.print()" class="inline-flex items-center text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 px-5 py-2.5 rounded-xl shadow-md transition">
                <i class="fas fa-print mr-2"></i> สั่งพิมพ์เอกสาร (Print A4/A5)
            </button>
        </div>
    </div>

    <!-- Printable Voucher Container -->
    <div class="max-w-3xl mx-auto bg-white rounded-3xl border border-slate-200 shadow-lg p-8 sm:p-10 print-card">
        
        <!-- Header -->
        <div class="flex justify-between items-start pb-6 border-b-2 border-slate-900 gap-4">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center font-black text-lg">
                        <i class="fas fa-tshirt"></i>
                    </div>
                    <div>
                        <h1 class="font-extrabold text-xl text-slate-900 tracking-tight">ใบยืนยันการสั่งจองและรับสินค้า</h1>
                        <p class="text-xs text-slate-500 font-medium">วิทยาลัยเทคนิคเชียงใหม่ (CMTC Pre-Order Voucher)</p>
                    </div>
                </div>
                <div class="text-xs text-slate-600 space-y-0.5">
                    <div>รอบการจอง: <strong><?= htmlspecialchars($order['round_name'] ?: 'รอบทั่วไป') ?></strong></div>
                    <div>วันที่ทำรายการ: <?= Helper::thaiDate($order['created_at']) ?></div>
                </div>
            </div>

            <!-- QR Code for fast verification -->
            <div class="text-center flex flex-col items-center flex-shrink-0">
                <div id="voucherQrCode" class="p-1 border border-slate-300 rounded-lg bg-white"></div>
                <span class="text-[10px] font-mono text-slate-400 mt-1">สแกนตรวจสอบ</span>
            </div>
        </div>

        <!-- Order & Customer Info Grid -->
        <div class="grid grid-cols-2 gap-6 my-6 text-xs">
            <!-- Left Info -->
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-1.5">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">ข้อมูลผู้สั่งจอง</div>
                <div><span class="text-slate-500">ชื่อ-นามสกุล:</span> <strong class="text-slate-900"><?= htmlspecialchars($order['customer_name']) ?></strong></div>
                <?php if (!empty($order['customer_organization'])): ?>
                    <div><span class="text-slate-500">สถานศึกษา/หน่วยงาน:</span> <strong class="text-slate-900"><?= htmlspecialchars($order['customer_organization']) ?></strong></div>
                <?php endif; ?>
                <div><span class="text-slate-500">เบอร์โทรศัพท์:</span> <strong class="text-slate-900"><?= htmlspecialchars($order['customer_phone']) ?></strong></div>
                <div><span class="text-slate-500">อีเมล:</span> <span class="text-slate-700"><?= htmlspecialchars($order['customer_email']) ?></span></div>
            </div>

            <!-- Right Info -->
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-1.5">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">การชำระเงินและรับของ</div>
                <div><span class="text-slate-500">หมายเลขอ้างอิง:</span> <strong class="font-mono text-blue-700"><?= htmlspecialchars($order['order_code']) ?></strong></div>
                <div>
                    <span class="text-slate-500">วิธีรับสินค้า:</span> 
                    <strong class="text-slate-900">
                        <?= $order['delivery_method'] === 'delivery' ? 'จัดส่งพัสดุไปรษณีย์' : 'รับด้วยตนเอง ณ วิทยาลัย' ?>
                    </strong>
                </div>
                <div>
                    <span class="text-slate-500">สถานะ:</span> 
                    <span class="font-bold text-emerald-700"><i class="fas fa-check-circle"></i> ยืนยันชำระเงินแล้ว</span>
                </div>
            </div>
        </div>

        <?php if ($order['delivery_method'] === 'delivery' && !empty($order['shipping_address'])): ?>
            <div class="mb-6 p-3.5 rounded-xl bg-blue-50/50 border border-blue-100 text-xs text-blue-900">
                <span class="font-bold">ที่อยู่จัดส่งพัสดุ:</span> <?= nl2br(htmlspecialchars($order['shipping_address'])) ?>
            </div>
        <?php else: ?>
            <div class="mb-6 p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700">
                <span class="font-bold text-slate-900"><i class="fas fa-map-marker-alt text-rose-500 mr-1"></i> จุดรับเสื้อ:</span> 
                <?= htmlspecialchars(get_setting('pickup_location', 'ตึกอำนวยการ แผนกวิชาช่างเทคนิคคอมพิวเตอร์ ชั้น 2')) ?>
            </div>
        <?php endif; ?>

        <!-- Itemized Table -->
        <div class="overflow-hidden rounded-2xl border border-slate-200 mb-6">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-900 text-white font-bold">
                    <tr>
                        <th class="py-3 px-4 w-12 text-center">#</th>
                        <th class="py-3 px-4">รายการสินค้า</th>
                        <th class="py-3 px-4 text-center">ขนาด / ตัวเลือก</th>
                        <th class="py-3 px-4 text-right">ราคาต่อหน่วย</th>
                        <th class="py-3 px-4 text-center">จำนวน</th>
                        <th class="py-3 px-4 text-right">รวมเงิน (บาท)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php $no = 1; foreach ($items as $it): ?>
                        <tr>
                            <td class="py-3 px-4 text-center font-medium text-slate-400"><?= $no++ ?></td>
                            <td class="py-3 px-4 font-bold text-slate-900"><?= htmlspecialchars($it['product_name']) ?></td>
                            <td class="py-3 px-4 text-center font-semibold text-blue-700"><?= htmlspecialchars($it['size_name']) ?></td>
                            <td class="py-3 px-4 text-right"><?= Helper::money($it['unit_price']) ?></td>
                            <td class="py-3 px-4 text-center font-bold"><?= $it['quantity'] ?></td>
                            <td class="py-3 px-4 text-right font-bold text-slate-900"><?= Helper::money($it['subtotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-slate-50 font-medium text-xs divide-y divide-slate-100">
                    <tr>
                        <td colspan="4" class="py-2.5 px-4 text-right text-slate-500">รวมจำนวนสินค้าทั้งหมด:</td>
                        <td class="py-2.5 px-4 text-center font-bold text-slate-900"><?= $totalPieces ?> ชิ้น/ตัว</td>
                        <td class="py-2.5 px-4 text-right font-semibold text-slate-700"><?= Helper::money($order['subtotal_amount']) ?></td>
                    </tr>
                    <?php if ((float)$order['shipping_cost'] > 0): ?>
                        <tr>
                            <td colspan="5" class="py-2.5 px-4 text-right text-slate-500">ค่าจัดส่งพัสดุ:</td>
                            <td class="py-2.5 px-4 text-right font-semibold text-slate-700"><?= Helper::money($order['shipping_cost']) ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr class="bg-slate-100/80 font-bold text-sm">
                        <td colspan="5" class="py-3 px-4 text-right text-slate-900">ยอดชำระสุทธิ (ชำระแล้ว):</td>
                        <td class="py-3 px-4 text-right text-blue-700 font-black">฿<?= Helper::money($order['total_amount']) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Signature Box for Physical Pickup Claiming -->
        <div class="grid grid-cols-2 gap-8 pt-8 border-t border-dashed border-slate-300 mt-6 text-xs">
            <div class="text-center">
                <div class="border-b border-slate-400 w-44 mx-auto mb-2 mt-8"></div>
                <div class="font-bold text-slate-800">ลงชื่อผู้รับสินค้า</div>
                <div class="text-slate-400 text-[11px] mt-0.5">วันที่: ..... / ..... / .........</div>
            </div>

            <div class="text-center">
                <div class="border-b border-slate-400 w-44 mx-auto mb-2 mt-8"></div>
                <div class="font-bold text-slate-800">ลงชื่อเจ้าหน้าที่ผู้ส่งมอบ</div>
                <div class="text-slate-400 text-[11px] mt-0.5">วันที่: ..... / ..... / .........</div>
            </div>
        </div>

        <!-- Footer Notice -->
        <div class="text-[10px] text-slate-400 text-center mt-8 pt-4 border-t border-slate-100">
            เอกสารนี้สร้างขึ้นโดยอัตโนมัติจากระบบรับจองเสื้อออนไลน์ วิทยาลัยเทคนิคเชียงใหม่ • รหัสตรวจสอบ: <?= htmlspecialchars($order['order_code']) ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        new QRCode(document.getElementById('voucherQrCode'), {
            text: <?= json_encode($verificationUrl) ?>,
            width: 80,
            height: 80,
            colorDark: "#0f172a",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.M
        });
    });
    </script>
</body>
</html>
