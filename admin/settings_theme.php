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

    $newTheme = trim($_POST['system_theme'] ?? Theme::DEFAULT_THEME);
    if (!array_key_exists($newTheme, Theme::getAll())) {
        $newTheme = Theme::DEFAULT_THEME;
    }

    Settings::set('system_theme', $newTheme);

    $showAllProducts = (isset($_POST['show_all_products_in_detail']) && $_POST['show_all_products_in_detail'] === '0') ? '0' : '1';
    Settings::set('show_all_products_in_detail', $showAllProducts);

    Helper::setFlash('success', 'บันทึกการตั้งค่าสำเร็จ', 'ธีมและการตั้งค่าการแสดงผลได้รับการปรับใช้เรียบร้อยแล้ว');
    header("Location: " . base_url('admin/settings_theme.php'));
    exit;
}

$pageTitle = "ธีมและโทนสีของระบบ - CMTC Admin";
$activePage = 'settings_theme';
require_once __DIR__ . '/../includes/admin_header.php';

$currentThemeKey = Theme::getCurrentKey();
$themes = Theme::getAll();
$showAllProducts = get_setting('show_all_products_in_detail', '1');
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">ธีมและโทนสีของระบบ (System Appearance & Theme)</h1>
            <p class="text-xs text-slate-500 mt-0.5">เลือกชุดสีและเอกลักษณ์ของระบบ แสดงผลทันทีทั้งหน้าร้าน (Storefront) และระบบหลังบ้าน (Admin Panel)</p>
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
        <a href="<?= base_url('admin/settings_theme.php') ?>" class="px-4 py-2.5 rounded-xl transition flex items-center space-x-2 whitespace-nowrap bg-blue-600 text-white shadow-sm">
            <i class="fas fa-palette"></i>
            <span>ธีมและโทนสีของระบบ</span>
        </a>
        <a href="<?= base_url('admin/settings_payment.php') ?>" class="px-4 py-2.5 rounded-xl transition flex items-center space-x-2 whitespace-nowrap bg-white text-slate-600 border border-slate-200 hover:bg-slate-50">
            <i class="fas fa-qrcode"></i>
            <span>พร้อมเพย์ & การจัดส่ง (Cart Timeout)</span>
        </a>
    </div>

    <form action="<?= base_url('admin/settings_theme.php') ?>" method="POST" id="themeForm">
        <?= Csrf::field() ?>

        <div class="space-y-6">

            <!-- System Theme & Appearance Configuration -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-5 border-b border-slate-100 mb-6 gap-3">
                    <div class="flex items-center space-x-3.5">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold shadow-md shadow-blue-500/20 flex-shrink-0">
                            <i class="fas fa-palette text-lg"></i>
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <h3 class="font-black text-slate-900 text-sm sm:text-base tracking-tight">เลือกชุดธีมของระบบ (Select System Theme)</h3>
                                <span class="text-[10px] font-extrabold text-purple-700 bg-purple-50 border border-purple-200/80 px-2 py-0.5 rounded-full uppercase tracking-wider hidden sm:inline-flex items-center">
                                    <i class="fas fa-magic mr-1 text-[9px]"></i> Dynamic Theme
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">คลิกเลือกธีมที่ต้องการ แล้วกดบันทึกเพื่อเปลี่ยนสีธีมทั่วทั้งระบบทันที</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2 bg-slate-50 px-3.5 py-1.5 rounded-xl border border-slate-200 self-start sm:self-auto flex-shrink-0">
                        <span class="text-[11px] text-slate-500 font-medium">ธีมที่เลือก:</span>
                        <span id="activeThemeBadgeLabel" class="text-xs font-bold text-blue-700">
                            <?= htmlspecialchars($themes[$currentThemeKey]['name'] ?? 'CMTC Classic Navy') ?>
                        </span>
                    </div>
                </div>

                <!-- Themes Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="themeGridContainer">
                    <?php foreach ($themes as $tKey => $t): ?>
                        <?php 
                            $isSelected = ($tKey === $currentThemeKey);
                            $pColor = $t['colors']['600'];
                            $pLight = $t['colors']['100'];
                            $pText = $t['colors']['700'];
                        ?>
                        <label for="theme_<?= $tKey ?>" 
                               id="theme_card_<?= $tKey ?>"
                               class="theme-card-item cursor-pointer block rounded-2xl border-2 transition-all p-4 relative <?= $isSelected ? 'border-blue-600 bg-blue-50/20 shadow-md ring-2 ring-blue-500/20' : 'border-slate-200 hover:border-slate-300 hover:shadow-xs bg-white' ?>">
                            <input type="radio" 
                                   name="system_theme" 
                                   id="theme_<?= $tKey ?>" 
                                   value="<?= $tKey ?>" 
                                   <?= $isSelected ? 'checked' : '' ?> 
                                   class="sr-only" 
                                   onchange="handleThemeSelect('<?= $tKey ?>', '<?= htmlspecialchars($t['name']) ?>')">

                            <!-- Card Top Header -->
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <div>
                                    <div class="flex items-center space-x-1.5">
                                        <span class="w-3.5 h-3.5 rounded-full shadow-2xs border border-white" style="background-color: <?= $pColor ?>;"></span>
                                        <span class="font-black text-slate-900 text-xs sm:text-sm tracking-tight"><?= htmlspecialchars($t['name']) ?></span>
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-medium mt-0.5"><?= htmlspecialchars($t['name_th']) ?></div>
                                </div>
                                <!-- Radio Circle Indicator -->
                                <div id="radio_indicator_<?= $tKey ?>" class="w-5 h-5 rounded-full border flex items-center justify-center transition-all flex-shrink-0 <?= $isSelected ? 'border-blue-600 bg-blue-600 text-white shadow-xs' : 'border-slate-300 bg-white text-transparent' ?>">
                                    <i class="fas fa-check text-[10px]"></i>
                                </div>
                            </div>

                            <!-- Badge & Description -->
                            <div class="mb-3">
                                <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-md border mb-1.5 <?= $t['badge_color'] ?>">
                                    <?= htmlspecialchars($t['badge']) ?>
                                </span>
                                <p class="text-[11px] text-slate-500 line-clamp-2 leading-relaxed">
                                    <?= htmlspecialchars($t['description']) ?>
                                </p>
                            </div>

                            <!-- Palette Swatches -->
                            <div class="mb-3">
                                <span class="text-[10px] font-bold text-slate-400 block mb-1 uppercase tracking-wider">ชุดสีหลัก (Palette)</span>
                                <div class="flex items-center space-x-1.5">
                                    <?php foreach ($t['swatches'] as $sColor): ?>
                                        <span class="w-5 h-5 rounded-lg shadow-2xs border border-white/60" style="background-color: <?= $sColor ?>;" title="<?= $sColor ?>"></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Mini UI Mockup Preview -->
                            <div class="bg-slate-50/90 rounded-xl p-2.5 border border-slate-200/80 mb-3 space-y-1.5 shadow-2xs">
                                <div class="h-3 rounded-md flex items-center justify-between px-2 shadow-2xs" style="background: <?= $t['gradient'] ?>;">
                                    <div class="w-10 h-1 rounded-full bg-white/70"></div>
                                    <div class="w-2.5 h-1 rounded-full bg-white/70"></div>
                                </div>
                                <div class="flex items-center justify-between gap-2 pt-0.5">
                                    <div class="flex items-center space-x-1">
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded" style="background-color: <?= $pLight ?>; color: <?= $pText ?>;">ตัวอย่าง</span>
                                    </div>
                                    <div class="px-2 py-0.5 rounded text-[9px] font-bold text-white shadow-2xs" style="background-color: <?= $pColor ?>;">
                                        ปุ่มกด
                                    </div>
                                </div>
                            </div>

                            <!-- Bottom Selection State -->
                            <div id="selection_footer_<?= $tKey ?>" class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] font-bold <?= $isSelected ? 'text-blue-600' : 'text-slate-400' ?>">
                                <span class="flex items-center">
                                    <i class="fas <?= $isSelected ? 'fa-check-circle text-blue-600' : 'fa-circle text-slate-300' ?> mr-1.5 text-xs"></i>
                                    <span id="selection_text_<?= $tKey ?>"><?= $isSelected ? 'เลือกใช้งานธีมนี้' : 'คลิกเพื่อเลือก' ?></span>
                                </span>
                                <?php if ($isSelected): ?>
                                    <span id="active_pill_<?= $tKey ?>" class="text-[9px] font-extrabold px-1.5 py-0.5 rounded bg-blue-100 text-blue-800">
                                        กำลังใช้งาน
                                    </span>
                                <?php endif; ?>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Product Detail Page Display Configuration -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-5 border-b border-slate-100 mb-6 gap-3">
                    <div class="flex items-center space-x-3.5">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-600 text-white flex items-center justify-center font-bold shadow-md shadow-emerald-500/20 flex-shrink-0">
                            <i class="fas fa-boxes text-lg"></i>
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <h3 class="font-black text-slate-900 text-sm sm:text-base tracking-tight">การแสดงผลในหน้ารายละเอียดสินค้า (Product Detail Display)</h3>
                                <span class="text-[10px] font-extrabold text-emerald-700 bg-emerald-50 border border-emerald-200/80 px-2 py-0.5 rounded-full uppercase tracking-wider hidden sm:inline-flex items-center">
                                    <i class="fas fa-sliders-h mr-1 text-[9px]"></i> Storefront Setting
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">กำหนดว่าจะให้แสดงหรือซ่อนส่วน "สินค้าทั้งหมดในระบบ" ที่อยู่ด้านล่างของหน้ารายละเอียดสินค้า (product_detail.php)</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Option 1: แสดงสินค้าทั้งหมดในระบบ -->
                    <label for="show_all_products_1" 
                           id="show_products_card_1" 
                           class="show-products-card cursor-pointer block rounded-2xl border-2 transition-all p-5 relative <?= ($showAllProducts === '1') ? 'border-emerald-600 bg-emerald-50/30 shadow-md ring-2 ring-emerald-500/20' : 'border-slate-200 hover:border-slate-300 hover:shadow-xs bg-white' ?>">
                        <input type="radio" 
                               name="show_all_products_in_detail" 
                               id="show_all_products_1" 
                               value="1" 
                               <?= ($showAllProducts === '1') ? 'checked' : '' ?> 
                               class="sr-only" 
                               onchange="handleProductDisplayToggle('1')">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start space-x-3.5">
                                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold flex-shrink-0 mt-0.5">
                                    <i class="fas fa-eye text-base"></i>
                                </div>
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <span class="font-black text-slate-900 text-sm sm:text-base tracking-tight">แสดงสินค้าทั้งหมดในระบบ</span>
                                        <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800">แนะนำ</span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                                        แสดงส่วน "สินค้าทั้งหมดในระบบ" พร้อมตัวกรองหมวดหมู่และรายการสินค้าทั้งหมดที่ด้านล่าง ให้ผู้สั่งซื้อเลือกดูและจองสินค้าอื่นต่อได้ทันที
                                    </p>
                                </div>
                            </div>
                            <div id="show_products_indicator_1" class="w-5 h-5 rounded-full border flex items-center justify-center transition-all flex-shrink-0 <?= ($showAllProducts === '1') ? 'border-emerald-600 bg-emerald-600 text-white shadow-xs' : 'border-slate-300 bg-white text-transparent' ?>">
                                <i class="fas fa-check text-[10px]"></i>
                            </div>
                        </div>
                    </label>

                    <!-- Option 2: ไม่ให้แสดงสินค้าทั้งหมดในระบบ -->
                    <label for="show_all_products_0" 
                           id="show_products_card_0" 
                           class="show-products-card cursor-pointer block rounded-2xl border-2 transition-all p-5 relative <?= ($showAllProducts === '0') ? 'border-emerald-600 bg-emerald-50/30 shadow-md ring-2 ring-emerald-500/20' : 'border-slate-200 hover:border-slate-300 hover:shadow-xs bg-white' ?>">
                        <input type="radio" 
                               name="show_all_products_in_detail" 
                               id="show_all_products_0" 
                               value="0" 
                               <?= ($showAllProducts === '0') ? 'checked' : '' ?> 
                               class="sr-only" 
                               onchange="handleProductDisplayToggle('0')">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start space-x-3.5">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center font-bold flex-shrink-0 mt-0.5">
                                    <i class="fas fa-eye-slash text-base"></i>
                                </div>
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <span class="font-black text-slate-900 text-sm sm:text-base tracking-tight">ไม่ให้แสดงสินค้าทั้งหมดในระบบ</span>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-slate-100 text-slate-600">ซ่อน</span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                                        ซ่อนส่วนรายการสินค้าทั้งหมดในระบบ แสดงเฉพาะข้อมูลสินค้าที่กำลังดูอยู่เท่านั้น ช่วยให้หน้าเว็บกระชับและโหลดหน้าได้รวดเร็วยิ่งขึ้น
                                    </p>
                                </div>
                            </div>
                            <div id="show_products_indicator_0" class="w-5 h-5 rounded-full border flex items-center justify-center transition-all flex-shrink-0 <?= ($showAllProducts === '0') ? 'border-emerald-600 bg-emerald-600 text-white shadow-xs' : 'border-slate-300 bg-white text-transparent' ?>">
                                <i class="fas fa-check text-[10px]"></i>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Save Button -->
            <div class="text-right">
                <button type="submit" class="w-full sm:w-auto px-8 py-3.5 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md transition btn-touch flex items-center justify-center">
                    <i class="fas fa-save mr-2"></i> บันทึกการตั้งค่าธีมและการแสดงผล
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function handleThemeSelect(themeKey, themeName) {
    // Remove active styles from all theme cards
    document.querySelectorAll('.theme-card-item').forEach(card => {
        card.classList.remove('border-blue-600', 'bg-blue-50/20', 'shadow-md', 'ring-2', 'ring-blue-500/20');
        card.classList.add('border-slate-200', 'bg-white');
    });

    // Add active styles to selected theme card
    const targetCard = document.getElementById('theme_card_' + themeKey);
    if (targetCard) {
        targetCard.classList.remove('border-slate-200', 'bg-white');
        targetCard.classList.add('border-blue-600', 'bg-blue-50/20', 'shadow-md', 'ring-2', 'ring-blue-500/20');
    }

    // Update active theme badge label in header
    const badgeLabel = document.getElementById('activeThemeBadgeLabel');
    if (badgeLabel) {
        badgeLabel.textContent = themeName;
    }

    // Update indicators & footers for all theme cards
    const themeKeys = <?= json_encode(array_keys($themes)) ?>;
    themeKeys.forEach(k => {
        const radio = document.getElementById('radio_indicator_' + k);
        const footer = document.getElementById('selection_footer_' + k);
        const text = document.getElementById('selection_text_' + k);
        const activePill = document.getElementById('active_pill_' + k);

        if (k === themeKey) {
            if (radio) {
                radio.className = 'w-5 h-5 rounded-full border border-blue-600 bg-blue-600 text-white flex items-center justify-center transition-all flex-shrink-0 shadow-xs';
            }
            if (footer) {
                footer.className = 'pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] font-bold text-blue-600';
            }
            if (text) {
                text.textContent = 'เลือกใช้งานธีมนี้';
            }
            if (!activePill && footer) {
                const newPill = document.createElement('span');
                newPill.id = 'active_pill_' + k;
                newPill.className = 'text-[9px] font-extrabold px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 animate-pulse';
                newPill.textContent = 'เลือกแล้ว (รอกดบันทึก)';
                footer.appendChild(newPill);
            }
        } else {
            if (radio) {
                radio.className = 'w-5 h-5 rounded-full border border-slate-300 bg-white text-transparent flex items-center justify-center transition-all flex-shrink-0';
            }
            if (footer) {
                footer.className = 'pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] font-medium text-slate-400';
            }
            if (text) {
                text.textContent = 'คลิกเพื่อเลือก';
            }
            if (activePill) {
                activePill.remove();
            }
        }
    });
}
function handleProductDisplayToggle(val) {
    const card1 = document.getElementById('show_products_card_1');
    const card0 = document.getElementById('show_products_card_0');
    const ind1 = document.getElementById('show_products_indicator_1');
    const ind0 = document.getElementById('show_products_indicator_0');

    if (val === '1') {
        if (card1) {
            card1.className = 'show-products-card cursor-pointer block rounded-2xl border-2 transition-all p-5 relative border-emerald-600 bg-emerald-50/30 shadow-md ring-2 ring-emerald-500/20';
        }
        if (card0) {
            card0.className = 'show-products-card cursor-pointer block rounded-2xl border-2 transition-all p-5 relative border-slate-200 hover:border-slate-300 hover:shadow-xs bg-white';
        }
        if (ind1) {
            ind1.className = 'w-5 h-5 rounded-full border flex items-center justify-center transition-all flex-shrink-0 border-emerald-600 bg-emerald-600 text-white shadow-xs';
        }
        if (ind0) {
            ind0.className = 'w-5 h-5 rounded-full border flex items-center justify-center transition-all flex-shrink-0 border-slate-300 bg-white text-transparent';
        }
    } else {
        if (card0) {
            card0.className = 'show-products-card cursor-pointer block rounded-2xl border-2 transition-all p-5 relative border-emerald-600 bg-emerald-50/30 shadow-md ring-2 ring-emerald-500/20';
        }
        if (card1) {
            card1.className = 'show-products-card cursor-pointer block rounded-2xl border-2 transition-all p-5 relative border-slate-200 hover:border-slate-300 hover:shadow-xs bg-white';
        }
        if (ind0) {
            ind0.className = 'w-5 h-5 rounded-full border flex items-center justify-center transition-all flex-shrink-0 border-emerald-600 bg-emerald-600 text-white shadow-xs';
        }
        if (ind1) {
            ind1.className = 'w-5 h-5 rounded-full border flex items-center justify-center transition-all flex-shrink-0 border-slate-300 bg-white text-transparent';
        }
    }
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
