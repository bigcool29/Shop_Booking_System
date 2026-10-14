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
        'site_title' => trim($_POST['site_title'] ?? ''),
        'footer_about_title' => trim($_POST['footer_about_title'] ?? 'CMTC Shirt Booking System'),
        'footer_about_text' => trim($_POST['footer_about_text'] ?? 'ระบบจองเสื้อกิจกรรมและเสื้อรุ่นออนไลน์ เพื่อความสะดวก รวดเร็ว และเป็นระบบ สำหรับนักเรียน นักศึกษา คณะครู และบุคลากร วิทยาลัยเทคนิคเชียงใหม่'),
        'footer_copyright' => trim($_POST['footer_copyright'] ?? '© 2026 งานผลิตและพัฒนาสื่อ/งานกิจกรรม วิทยาลัยเทคนิคเชียงใหม่. All rights reserved.'),
        'footer_version_text' => trim($_POST['footer_version_text'] ?? 'ระบบสั่งจองสินค้าออนไลน์ v1.0 • PHP 7.4 + MariaDB')
    ];

    foreach ($settingsToSave as $k => $v) {
        Settings::set($k, $v);
    }

    // Handle logo upload
    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = Helper::uploadFile($_FILES['site_logo'], 'system');
        if ($uploadResult['success']) {
            Settings::set('site_logo', $uploadResult['filename']);
        } else {
            Helper::setFlash('error', 'อัปโหลดโลโก้ไม่สำเร็จ', $uploadResult['error']);
            header("Location: " . base_url('admin/settings_brand.php'));
            exit;
        }
    } elseif (!empty($_POST['reset_logo_default'])) {
        Settings::set('site_logo', 'assets/images/cmtc_logo.jpg');
    }

    Helper::setFlash('success', 'บันทึกสำเร็จ', 'โลโก้และข้อความระบบได้รับการอัปเดตเรียบร้อยแล้ว');
    header("Location: " . base_url('admin/settings_brand.php'));
    exit;
}

$pageTitle = "โลโก้และข้อความระบบ - CMTC Admin";
$activePage = 'settings_brand';
require_once __DIR__ . '/../includes/admin_header.php';

$siteTitle = get_setting('site_title', 'ระบบจองเสื้อออนไลน์ - CMTC Official Store');
$siteLogo = get_setting('site_logo', 'assets/images/cmtc_logo.jpg');
$footerAboutTitle = get_setting('footer_about_title', 'CMTC Shirt Booking System');
$footerAboutText = get_setting('footer_about_text', 'ระบบจองเสื้อกิจกรรมและเสื้อรุ่นออนไลน์ เพื่อความสะดวก รวดเร็ว และเป็นระบบ สำหรับนักเรียน นักศึกษา คณะครู และบุคลากร วิทยาลัยเทคนิคเชียงใหม่');
$footerCopyright = get_setting('footer_copyright', '© 2026 งานผลิตและพัฒนาสื่อ/งานกิจกรรม วิทยาลัยเทคนิคเชียงใหม่. All rights reserved.');
$footerVersionText = get_setting('footer_version_text', 'ระบบสั่งจองสินค้าออนไลน์ v1.0 • PHP 7.4 + MariaDB');
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">โลโก้และข้อความระบบ (System Logo & Brand Information)</h1>
            <p class="text-xs text-slate-500 mt-0.5">จัดการตราสัญลักษณ์ของระบบและข้อความอัตลักษณ์ส่วนท้ายเว็บไซต์ (Footer)</p>
        </div>
        <div class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-purple-50 text-purple-700 border border-purple-200 text-xs font-bold self-start sm:self-auto">
            <i class="fas fa-shield-alt text-purple-600"></i>
            <span>สิทธิ์เฉพาะ Superadmin</span>
        </div>
    </div>

    <!-- Navigation Tabs for Superadmin Settings -->
    <div class="flex items-center space-x-2 border-b border-slate-200 pb-4 mb-6 overflow-x-auto text-xs font-bold no-scrollbar">
        <a href="<?= base_url('admin/settings_brand.php') ?>" class="px-4 py-2.5 rounded-xl transition flex items-center space-x-2 whitespace-nowrap bg-blue-600 text-white shadow-sm">
            <i class="fas fa-stamp"></i>
            <span>โลโก้และข้อความระบบ</span>
        </a>
        <a href="<?= base_url('admin/settings_theme.php') ?>" class="px-4 py-2.5 rounded-xl transition flex items-center space-x-2 whitespace-nowrap bg-white text-slate-600 border border-slate-200 hover:bg-slate-50">
            <i class="fas fa-palette"></i>
            <span>ธีมและโทนสีของระบบ</span>
        </a>
        <a href="<?= base_url('admin/settings_payment.php') ?>" class="px-4 py-2.5 rounded-xl transition flex items-center space-x-2 whitespace-nowrap bg-white text-slate-600 border border-slate-200 hover:bg-slate-50">
            <i class="fas fa-qrcode"></i>
            <span>พร้อมเพย์ & การจัดส่ง (Cart Timeout)</span>
        </a>
    </div>

    <form action="<?= base_url('admin/settings_brand.php') ?>" method="POST" enctype="multipart/form-data" id="brandForm">
        <?= Csrf::field() ?>

        <div class="space-y-6">

            <!-- System Logo & Brand Identity Configuration -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-5 border-b border-slate-100 mb-6 gap-3">
                    <div class="flex items-center space-x-3.5">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-amber-500 to-orange-600 text-white flex items-center justify-center font-bold shadow-md shadow-amber-500/20 flex-shrink-0">
                            <i class="fas fa-stamp text-lg"></i>
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <h3 class="font-black text-slate-900 text-sm sm:text-base tracking-tight">ตราสัญลักษณ์และข้อความแนะนำระบบ</h3>
                                <span class="text-[10px] font-extrabold text-amber-700 bg-amber-50 border border-amber-200/80 px-2 py-0.5 rounded-full uppercase tracking-wider hidden sm:inline-flex items-center">
                                    <i class="fas fa-check-circle mr-1 text-[9px]"></i> Storefront & Footer
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">กำหนดรูปโลโก้ของวิทยาลัย/ระบบ ข้อความคำอธิบาย ลิขสิทธิ์ และรายละเอียดแพลตฟอร์ม</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <!-- Row 1: Logo Upload & Live Preview -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2">
                            รูปโลโก้ของระบบ (System Logo)
                        </label>
                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5 p-4 rounded-2xl bg-slate-50/70 border border-slate-200/80">
                            <!-- Logo Preview Box -->
                            <div class="relative w-24 h-24 rounded-2xl bg-white border-2 border-slate-200/90 shadow-sm flex items-center justify-center p-2 flex-shrink-0 overflow-hidden group">
                                <?php $hasLogo = !empty($siteLogo) && file_exists(__DIR__ . '/../' . $siteLogo); ?>
                                <img id="logoPreviewImage" 
                                     src="<?= $hasLogo ? base_url($siteLogo) : '' ?>" 
                                     alt="System Logo Preview" 
                                     class="max-w-full max-h-full object-contain <?= $hasLogo ? '' : 'hidden' ?>">
                                <div id="logoFallbackIcon" class="w-12 h-12 rounded-xl bg-blue-600 text-white flex items-center justify-center text-xl font-bold <?= $hasLogo ? 'hidden' : '' ?>">
                                    <i class="fas fa-tshirt"></i>
                                </div>
                                <span id="logoStatusBadge" class="absolute bottom-1 right-1 text-[9px] font-black bg-slate-900/80 text-white px-1.5 py-0.5 rounded shadow">
                                    <?= $hasLogo ? 'ปัจจุบัน' : 'ไม่มี' ?>
                                </span>
                            </div>

                            <!-- Upload actions & notes -->
                            <div class="flex-1 space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <input type="file" name="site_logo" id="site_logo_input" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="previewSelectedLogo(this)">
                                    <label for="site_logo_input" class="cursor-pointer inline-flex items-center px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-sm transition active:scale-95 btn-touch">
                                        <i class="fas fa-cloud-upload-alt mr-2 text-sm"></i>
                                        <span>เลือกไฟล์รูปภาพโลโก้ใหม่</span>
                                    </label>

                                    <button type="button" id="cancelLogoChangeBtn" onclick="resetSelectedLogoPreview()" class="hidden px-3.5 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs transition">
                                        <i class="fas fa-times mr-1"></i> ยกเลิกที่เลือก
                                    </button>
                                </div>

                                <div class="text-[11px] text-slate-500 leading-relaxed space-y-0.5">
                                    <p><i class="fas fa-info-circle text-blue-500 mr-1"></i> รองรับไฟล์: <strong>JPG, PNG, WEBP</strong> ขนาดไฟล์ไม่เกิน <strong>5MB</strong></p>
                                    <p class="text-slate-400">แนะนำ: ภาพทรงสี่เหลี่ยมจัตุรัส หรือภาพพื้นหลังโปร่งใส (Transparent PNG) เพื่อความสวยงามกลมกลืน</p>
                                </div>

                                <div class="pt-1">
                                    <label class="inline-flex items-center space-x-2 text-xs text-slate-600 hover:text-slate-900 cursor-pointer">
                                        <input type="checkbox" name="reset_logo_default" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                        <span class="font-medium">รีเซ็ตกลับเป็นตราสัญลักษณ์เริ่มต้นของวิทยาลัย (CMTC Emblem)</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: Site Title & Footer About Title -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                ชื่อเว็บไซต์ / หัวข้อระบบหลัก <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="site_title" value="<?= htmlspecialchars($siteTitle) ?>" required placeholder="เช่น ระบบจองเสื้อออนไลน์ - CMTC Official Store" class="w-full px-4 py-2.5 text-xs rounded-xl border border-slate-200 text-slate-900 focus:border-blue-600 font-medium">
                            <p class="text-[11px] text-slate-400 mt-1">แสดงผลบนแท็บ Title Bar ของเบราว์เซอร์</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                ชื่อระบบส่วนท้ายเว็บ (Footer About Title) <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="footer_about_title" id="input_footer_about_title" value="<?= htmlspecialchars($footerAboutTitle) ?>" required placeholder="CMTC Shirt Booking System" class="w-full px-4 py-2.5 text-xs rounded-xl border border-slate-200 text-slate-900 focus:border-blue-600 font-bold" oninput="updateLiveFooterPreview()">
                            <p class="text-[11px] text-slate-400 mt-1">ค่าแนะนำ: <code class="text-slate-600">CMTC Shirt Booking System</code></p>
                        </div>
                    </div>

                    <!-- Row 3: Footer About Description -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            คำอธิบายระบบส่วนท้ายเว็บ (Footer About Description) <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="footer_about_text" id="input_footer_about_text" rows="3" required placeholder="ระบบจองเสื้อกิจกรรมและเสื้อรุ่นออนไลน์ เพื่อความสะดวก รวดเร็ว และเป็นระบบ..." class="w-full px-4 py-2.5 text-xs rounded-xl border border-slate-200 text-slate-900 focus:border-blue-600 leading-relaxed font-medium" oninput="updateLiveFooterPreview()"><?= htmlspecialchars($footerAboutText) ?></textarea>
                        <p class="text-[11px] text-slate-400 mt-1">อธิบายวัตถุประสงค์และกลุ่มเป้าหมาย (นักเรียน นักศึกษา คณะครู บุคลากร)</p>
                    </div>

                    <!-- Row 4: Copyright & Version Text -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                ข้อความลิขสิทธิ์ (Copyright Notice) <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="footer_copyright" id="input_footer_copyright" value="<?= htmlspecialchars($footerCopyright) ?>" required placeholder="© 2026 งานผลิตและพัฒนาสื่อ/งานกิจกรรม วิทยาลัยเทคนิคเชียงใหม่. All rights reserved." class="w-full px-4 py-2.5 text-xs rounded-xl border border-slate-200 text-slate-900 focus:border-blue-600 font-medium" oninput="updateLiveFooterPreview()">
                            <p class="text-[11px] text-slate-400 mt-1">ระบุหน่วยงานและปี พ.ศ./ค.ศ. ลิขสิทธิ์</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                ข้อความเวอร์ชันและแพลตฟอร์ม (Version & System Info) <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="footer_version_text" id="input_footer_version_text" value="<?= htmlspecialchars($footerVersionText) ?>" required placeholder="ระบบสั่งจองสินค้าออนไลน์ v1.0 • PHP 7.4 + MariaDB" class="w-full px-4 py-2.5 text-xs rounded-xl border border-slate-200 text-slate-900 focus:border-blue-600 font-mono text-[11px]" oninput="updateLiveFooterPreview()">
                            <p class="text-[11px] text-slate-400 mt-1">เช่น <code class="text-slate-600">ระบบสั่งจองสินค้าออนไลน์ v1.0 • PHP 7.4 + MariaDB</code></p>
                        </div>
                    </div>

                    <!-- Live Footer Preview Box -->
                    <div class="pt-4 border-t border-slate-100">
                        <div class="flex items-center space-x-2 mb-3">
                            <span class="text-xs font-bold text-slate-700 flex items-center">
                                <i class="fas fa-eye text-blue-600 mr-1.5"></i> ตัวอย่างจำลองส่วนท้ายเว็บสด (Live Footer Preview)
                            </span>
                            <span class="text-[10px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full font-bold">อัปเดตตามการพิมพ์</span>
                        </div>

                        <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200/90 shadow-2xs">
                            <div class="flex items-start space-x-3.5 mb-3">
                                <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 p-1 flex items-center justify-center flex-shrink-0 shadow-xs overflow-hidden">
                                    <img id="livePreviewLogoImg" 
                                         src="<?= $hasLogo ? base_url($siteLogo) : '' ?>" 
                                         alt="Logo" 
                                         class="max-w-full max-h-full object-contain <?= $hasLogo ? '' : 'hidden' ?>">
                                    <div id="livePreviewLogoFallback" class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-xs <?= $hasLogo ? 'hidden' : '' ?>">
                                        <i class="fas fa-tshirt"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 id="livePreviewTitle" class="font-bold text-slate-900 text-sm leading-tight">
                                        <?= htmlspecialchars($footerAboutTitle) ?>
                                    </h4>
                                    <p id="livePreviewDesc" class="text-xs text-slate-500 leading-relaxed mt-1 line-clamp-2">
                                        <?= htmlspecialchars($footerAboutText) ?>
                                    </p>
                                </div>
                            </div>

                            <div class="border-t border-slate-200/70 pt-3 flex flex-col sm:flex-row justify-between items-start sm:items-center text-[11px] text-slate-400 gap-1.5">
                                <div id="livePreviewCopyright">
                                    <?= htmlspecialchars($footerCopyright) ?>
                                </div>
                                <div id="livePreviewVersion" class="font-mono text-[10px] text-slate-500 bg-white px-2 py-0.5 rounded border border-slate-200">
                                    <?= htmlspecialchars($footerVersionText) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="text-right">
                <button type="submit" class="w-full sm:w-auto px-8 py-3.5 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md transition btn-touch flex items-center justify-center">
                    <i class="fas fa-save mr-2"></i> บันทึกโลโก้และข้อความระบบ
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function previewSelectedLogo(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewImg = document.getElementById('logoPreviewImage');
            const fallbackIcon = document.getElementById('logoFallbackIcon');
            const statusBadge = document.getElementById('logoStatusBadge');
            const cancelBtn = document.getElementById('cancelLogoChangeBtn');
            const liveLogoImg = document.getElementById('livePreviewLogoImg');
            const liveFallback = document.getElementById('livePreviewLogoFallback');

            if (previewImg) {
                previewImg.src = e.target.result;
                previewImg.classList.remove('hidden');
            }
            if (fallbackIcon) fallbackIcon.classList.add('hidden');
            if (statusBadge) {
                statusBadge.textContent = 'รูปใหม่';
                statusBadge.className = 'absolute bottom-1 right-1 text-[9px] font-black bg-blue-600 text-white px-1.5 py-0.5 rounded shadow animate-pulse';
            }
            if (cancelBtn) cancelBtn.classList.remove('hidden');

            if (liveLogoImg) {
                liveLogoImg.src = e.target.result;
                liveLogoImg.classList.remove('hidden');
            }
            if (liveFallback) liveFallback.classList.add('hidden');
        };
        reader.readAsDataURL(file);
    }
}

const originalLogoSrc = "<?= $hasLogo ? base_url($siteLogo) : '' ?>";
const originalHasLogo = <?= $hasLogo ? 'true' : 'false' ?>;

function resetSelectedLogoPreview() {
    const input = document.getElementById('site_logo_input');
    if (input) input.value = '';

    const previewImg = document.getElementById('logoPreviewImage');
    const fallbackIcon = document.getElementById('logoFallbackIcon');
    const statusBadge = document.getElementById('logoStatusBadge');
    const cancelBtn = document.getElementById('cancelLogoChangeBtn');
    const liveLogoImg = document.getElementById('livePreviewLogoImg');
    const liveFallback = document.getElementById('livePreviewLogoFallback');

    if (originalHasLogo && originalLogoSrc) {
        if (previewImg) {
            previewImg.src = originalLogoSrc;
            previewImg.classList.remove('hidden');
        }
        if (fallbackIcon) fallbackIcon.classList.add('hidden');
        if (statusBadge) {
            statusBadge.textContent = 'ปัจจุบัน';
            statusBadge.className = 'absolute bottom-1 right-1 text-[9px] font-black bg-slate-900/80 text-white px-1.5 py-0.5 rounded shadow';
        }
        if (liveLogoImg) {
            liveLogoImg.src = originalLogoSrc;
            liveLogoImg.classList.remove('hidden');
        }
        if (liveFallback) liveFallback.classList.add('hidden');
    } else {
        if (previewImg) previewImg.classList.add('hidden');
        if (fallbackIcon) fallbackIcon.classList.remove('hidden');
        if (statusBadge) {
            statusBadge.textContent = 'ไม่มี';
            statusBadge.className = 'absolute bottom-1 right-1 text-[9px] font-black bg-slate-900/80 text-white px-1.5 py-0.5 rounded shadow';
        }
        if (liveLogoImg) liveLogoImg.classList.add('hidden');
        if (liveFallback) liveFallback.classList.remove('hidden');
    }

    if (cancelBtn) cancelBtn.classList.add('hidden');
}

function updateLiveFooterPreview() {
    const titleVal = document.getElementById('input_footer_about_title')?.value || 'CMTC Shirt Booking System';
    const descVal = document.getElementById('input_footer_about_text')?.value || 'ระบบจองเสื้อกิจกรรมและเสื้อรุ่นออนไลน์...';
    const copyVal = document.getElementById('input_footer_copyright')?.value || '© 2026 งานผลิตและพัฒนาสื่อ/งานกิจกรรม วิทยาลัยเทคนิคเชียงใหม่. All rights reserved.';
    const verVal = document.getElementById('input_footer_version_text')?.value || 'ระบบสั่งจองสินค้าออนไลน์ v1.0 • PHP 7.4 + MariaDB';

    const liveTitle = document.getElementById('livePreviewTitle');
    const liveDesc = document.getElementById('livePreviewDesc');
    const liveCopy = document.getElementById('livePreviewCopyright');
    const liveVer = document.getElementById('livePreviewVersion');

    if (liveTitle) liveTitle.textContent = titleVal;
    if (liveDesc) liveDesc.textContent = descVal;
    if (liveCopy) liveCopy.textContent = copyVal;
    if (liveVer) liveVer.textContent = verVal;
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
