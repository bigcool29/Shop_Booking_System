<?php
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helper.php';
require_once __DIR__ . '/../core/Csrf.php';
require_once __DIR__ . '/../core/Theme.php';

Auth::requireSuperAdmin();
$pdo = Database::getInstance()->getConnection();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    Csrf::verifyOrDie();

    $settingsToSave = [
        'promptpay_number' => trim($_POST['promptpay_number'] ?? ''),
        'promptpay_name' => trim($_POST['promptpay_name'] ?? ''),
        'cart_timeout_minutes' => (int)($_POST['cart_timeout_minutes'] ?? 30),
        'shipping_base_fee' => (float)($_POST['shipping_base_fee'] ?? 50.00),
        'shipping_extra_fee' => (float)($_POST['shipping_extra_fee'] ?? 10.00),
        'pickup_location' => trim($_POST['pickup_location'] ?? ''),
        'contact_phone' => trim($_POST['contact_phone'] ?? '')
    ];

    foreach ($settingsToSave as $k => $v) {
        Settings::set($k, $v);
    }

    Helper::setFlash('success', 'บันทึกสำเร็จ', 'การตั้งค่าพร้อมเพย์ การล็อกสต็อก และการจัดส่งได้รับการอัปเดตเรียบร้อยแล้ว');
    header("Location: " . base_url('admin/settings_payment.php'));
    exit;
}

$pageTitle = "พร้อมเพย์ & การจัดส่ง - CMTC Admin";
$activePage = 'settings_payment';
require_once __DIR__ . '/../includes/admin_header.php';

$promptpayNum = get_setting('promptpay_number', '0812345678');
$promptpayName = get_setting('promptpay_name', 'วิทยาลัยเทคนิคเชียงใหม่ (CMTC Shop)');
$cartTimeout = get_setting('cart_timeout_minutes', '30');
$shipBase = get_setting('shipping_base_fee', '50.00');
$shipExtra = get_setting('shipping_extra_fee', '10.00');
$pickupLoc = get_setting('pickup_location', 'ตึกอำนวยการ แผนกวิชาช่างเทคนิคคอมพิวเตอร์ ชั้น 2');
$contactPhone = get_setting('contact_phone', '053-217-708');
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">พร้อมเพย์ & การจัดส่ง (PromptPay, Timeout & Shipping)</h1>
            <p class="text-xs text-slate-500 mt-0.5">กำหนดบัญชีรับเงินพร้อมเพย์ การล็อกสต็อกชั่วคราวและหมดอายุ (Cart Timeout) และอัตราค่าจัดส่งพัสดุ</p>
        </div>
        <div class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-purple-50 text-purple-700 border border-purple-200 text-xs font-bold self-start sm:self-auto">
            <i class="fas fa-shield-alt text-purple-600"></i>
            <span>สิทธิ์เฉพาะ Superadmin</span>
        </div>
    </div>

    <!-- Navigation Tabs for Superadmin Settings -->
    <div class="flex items-center space-x-2 border-b border-slate-200 pb-4 mb-6 overflow-x-auto text-xs font-bold no-scrollbar">
        <a href="<?= base_url('admin/settings_brand.php') ?>" class="px-4 py-2.5 rounded-xl transition flex items-center space-x-2 whitespace-nowrap bg-white text-slate-600 border border-slate-200 hover:bg-slate-50">
            <i class="fas fa-stamp"></i>
            <span>โลโก้และข้อความระบบ</span>
        </a>
        <a href="<?= base_url('admin/settings_theme.php') ?>" class="px-4 py-2.5 rounded-xl transition flex items-center space-x-2 whitespace-nowrap bg-white text-slate-600 border border-slate-200 hover:bg-slate-50">
            <i class="fas fa-palette"></i>
            <span>ธีมและโทนสีของระบบ</span>
        </a>
        <a href="<?= base_url('admin/settings_payment.php') ?>" class="px-4 py-2.5 rounded-xl transition flex items-center space-x-2 whitespace-nowrap bg-blue-600 text-white shadow-sm">
            <i class="fas fa-qrcode"></i>
            <span>พร้อมเพย์ & การจัดส่ง (Cart Timeout)</span>
        </a>
    </div>

    <form action="<?= base_url('admin/settings_payment.php') ?>" method="POST" id="paymentSettingsForm">
        <?= Csrf::field() ?>

        <div class="space-y-6">

            <!-- PromptPay Configuration Card -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
                <div class="flex items-center space-x-3 pb-4 border-b border-slate-100 mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                        <i class="fas fa-qrcode text-lg"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-900 text-sm sm:text-base">การรับเงินผ่าน PromptPay Dynamic QR</h3>
                        <p class="text-xs text-slate-500">บัญชีสำหรับรับเงินและสร้าง QR Code ระบุยอดเงินชำระอัตโนมัติ</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            เบอร์พร้อมเพย์ หรือ เลข 13 หลัก <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="promptpay_number" value="<?= htmlspecialchars($promptpayNum) ?>" required placeholder="0812345678 หรือ เลขประจำตัวผู้เสียภาษี" class="w-full px-4 py-2.5 text-xs rounded-xl border border-slate-200 font-mono font-bold text-slate-900 focus:border-blue-600">
                        <p class="text-[11px] text-slate-400 mt-1">เบอร์มือถือ 10 หลัก (เช่น 0812345678) หรือ เลขภาษี 13 หลัก</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            ชื่อบัญชีที่แสดงให้ผู้สั่งจองตรวจสอบ <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="promptpay_name" value="<?= htmlspecialchars($promptpayName) ?>" required placeholder="เช่น วิทยาลัยเทคนิคเชียงใหม่" class="w-full px-4 py-2.5 text-xs rounded-xl border border-slate-200 text-slate-900 focus:border-blue-600 font-medium">
                        <p class="text-[11px] text-slate-400 mt-1">แสดงผลคู่กับ QR Code เพื่อให้ผู้จองตรวจสอบความถูกต้องก่อนโอน</p>
                    </div>
                </div>
            </div>

            <!-- Cart Timeout & Inventory Hold Card -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
                <div class="flex items-center space-x-3 pb-4 border-b border-slate-100 mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                        <i class="fas fa-clock text-lg"></i>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="font-black text-slate-900 text-sm sm:text-base">การล็อกสต็อกชั่วคราวและหมดอายุ (Cart Timeout)</h3>
                            <span class="text-[10px] font-extrabold text-amber-700 bg-amber-50 border border-amber-200/80 px-2 py-0.5 rounded-full uppercase tracking-wider hidden sm:inline-block">
                                Auto Hold Release
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">คืนสต็อกสินค้ากลับสู่ระบบอัตโนมัติหากผู้สั่งจองไม่ชำระเงินและแนบสลิปภายในเวลาที่กำหนด</p>
                    </div>
                </div>

                <div class="max-w-md">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        ระยะเวลาล็อกสต็อกเพื่อรอชำระเงิน (นาที) <span class="text-rose-500">*</span>
                    </label>
                    <div class="flex items-center space-x-2">
                        <input type="number" name="cart_timeout_minutes" value="<?= htmlspecialchars($cartTimeout) ?>" min="5" max="1440" required class="w-32 px-4 py-2.5 text-xs rounded-xl border border-slate-200 text-center font-black text-slate-900 focus:border-blue-600 text-sm">
                        <span class="text-xs text-slate-600 font-bold">นาที</span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1.5">
                        <i class="fas fa-info-circle text-amber-500 mr-1"></i> ค่าแนะนำ: <strong>30 นาที</strong> (ระบบจะแสดงนาฬิกานับถอยหลังในหน้ารอชำระเงินและบนแถบเมนูด้านบนของผู้สั่งจอง)
                    </p>
                </div>
            </div>

            <!-- Shipping Cost & Pickup Location Card -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
                <div class="flex items-center space-x-3 pb-4 border-b border-slate-100 mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                        <i class="fas fa-truck-moving text-lg"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-900 text-sm sm:text-base">การจัดส่งพัสดุและสถานที่รับเสื้อ</h3>
                        <p class="text-xs text-slate-500">คำนวณค่าจัดส่งพัสดุอัตโนมัติ และระบุสถานที่รับของสำหรับผู้ที่เลือกรับเอง</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            ค่าจัดส่งพัสดุเริ่มต้น (บาท) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" step="0.01" name="shipping_base_fee" value="<?= htmlspecialchars($shipBase) ?>" required class="w-full px-4 py-2.5 text-xs rounded-xl border border-slate-200 text-slate-900 focus:border-blue-600 font-bold">
                        <p class="text-[11px] text-slate-400 mt-1">ค่าจัดส่งสำหรับชิ้นแรก (0 = จัดส่งฟรี)</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            บวกเพิ่มต่อตัวถัดไป (บาท) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" step="0.01" name="shipping_extra_fee" value="<?= htmlspecialchars($shipExtra) ?>" required class="w-full px-4 py-2.5 text-xs rounded-xl border border-slate-200 text-slate-900 focus:border-blue-600 font-bold">
                        <p class="text-[11px] text-slate-400 mt-1">คิดเพิ่มต่อตัวตั้งแต่ตัวที่ 2 เป็นต้นไป</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            สถานที่รับเสื้อด้วยตนเอง (แสดงในใบเสร็จและหน้าเว็บ) <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="pickup_location" rows="2" required class="w-full px-4 py-2.5 text-xs rounded-xl border border-slate-200 text-slate-900 focus:border-blue-600 font-medium leading-relaxed"><?= htmlspecialchars($pickupLoc) ?></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            เบอร์โทรติดต่อสอบถาม <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="contact_phone" value="<?= htmlspecialchars($contactPhone) ?>" required class="w-full px-4 py-2.5 text-xs rounded-xl border border-slate-200 text-slate-900 focus:border-blue-600 font-medium">
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="text-right">
                <button type="submit" class="w-full sm:w-auto px-8 py-3.5 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md transition btn-touch flex items-center justify-center">
                    <i class="fas fa-save mr-2"></i> บันทึกการตั้งค่าพร้อมเพย์ & การจัดส่ง
                </button>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
