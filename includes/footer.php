<?php
$siteLogo = get_setting('site_logo', 'assets/images/cmtc_logo.jpg');
$footerAboutTitle = get_setting('footer_about_title', 'CMTC Shirt Booking System');
$footerAboutText = get_setting('footer_about_text', 'ระบบจองเสื้อกิจกรรมและเสื้อรุ่นออนไลน์ เพื่อความสะดวก รวดเร็ว และเป็นระบบ สำหรับนักเรียน นักศึกษา คณะครู และบุคลากร วิทยาลัยเทคนิคเชียงใหม่');
$footerCopyright = get_setting('footer_copyright', '© 2026 งานผลิตและพัฒนาสื่อ/งานกิจกรรม วิทยาลัยเทคนิคเชียงใหม่. All rights reserved.');
$footerVersionText = get_setting('footer_version_text', 'ระบบสั่งจองสินค้าออนไลน์ v1.0 • PHP 7.4 + MariaDB');
?>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 mt-auto text-slate-600 text-sm no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Col 1: About -->
                <div>
                    <div class="flex items-center space-x-3 mb-3">
                        <?php if (!empty($siteLogo) && file_exists(__DIR__ . '/../' . $siteLogo)): ?>
                            <img src="<?= base_url($siteLogo) ?>" alt="<?= htmlspecialchars($footerAboutTitle) ?>" class="w-9 h-9 object-contain rounded-xl p-0.5 bg-white shadow-sm border border-slate-200 flex-shrink-0">
                        <?php else: ?>
                            <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold text-sm shadow-sm flex-shrink-0">
                                <i class="fas fa-tshirt"></i>
                            </div>
                        <?php endif; ?>
                        <span class="font-bold text-slate-900 text-base leading-tight"><?= htmlspecialchars($footerAboutTitle) ?></span>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed mb-4">
                        <?= nl2br(htmlspecialchars($footerAboutText)) ?>
                    </p>
                    <div class="flex items-center space-x-3 text-slate-400">
                        <span class="inline-flex items-center text-xs text-slate-500">
                            <i class="fas fa-shield-alt text-emerald-500 mr-1.5"></i> ความปลอดภัยด้วย SSL & PromptPay Dynamic QR
                        </span>
                    </div>
                </div>

                <!-- Col 2: Pickup & Location -->
                <div>
                    <h4 class="font-semibold text-slate-900 mb-3 text-sm flex items-center">
                        <i class="fas fa-map-marker-alt text-rose-500 mr-2"></i> จุดรับสินค้าและติดต่อ
                    </h4>
                    <p class="text-xs text-slate-600 mb-2 leading-relaxed">
                        <?= nl2br(htmlspecialchars(get_setting('pickup_location', 'ตึกอำนวยการ แผนกวิชาช่างเทคนิคคอมพิวเตอร์ ชั้น 2'))) ?>
                    </p>
                    <p class="text-xs text-slate-600">
                        <span class="font-medium">โทรศัพท์:</span> <?= htmlspecialchars(get_setting('contact_phone', '053-217-708')) ?>
                    </p>
                </div>

                <!-- Col 3: Quick Links & Help -->
                <div>
                    <h4 class="font-semibold text-slate-900 mb-3 text-sm flex items-center">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i> เมนูช่วยเหลือ
                    </h4>
                    <ul class="space-y-2 text-xs">
                        <li>
                            <a href="<?= base_url() ?>" class="hover:text-blue-600 transition flex items-center">
                                <i class="fas fa-chevron-right text-[10px] text-slate-400 mr-2"></i> สั่งจองเสื้อรอบปัจจุบัน
                            </a>
                        </li>
                        <li>
                            <a href="<?= base_url('guide.php') ?>" class="hover:text-blue-600 transition flex items-center">
                                <i class="fas fa-chevron-right text-[10px] text-slate-400 mr-2"></i> คู่มือและขั้นตอนการสั่งจอง
                            </a>
                        </li>
                        <li>
                            <a href="<?= base_url('track.php') ?>" class="hover:text-blue-600 transition flex items-center">
                                <i class="fas fa-chevron-right text-[10px] text-slate-400 mr-2"></i> ค้นหาและตรวจสอบสถานะคำสั่งจอง
                            </a>
                        </li>
                        <li>
                            <a href="<?= base_url('admin/login.php') ?>" class="hover:text-blue-600 transition flex items-center">
                                <i class="fas fa-chevron-right text-[10px] text-slate-400 mr-2"></i> ระบบจัดการสำหรับเจ้าหน้าที่ (Admin Portal)
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-slate-100 mt-8 pt-6 flex flex-col sm:flex-row justify-between items-center text-xs text-slate-400 gap-2">
                <div>
                    <?= htmlspecialchars($footerCopyright) ?>
                </div>
                <div>
                    <?= htmlspecialchars($footerVersionText) ?>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="<?= base_url('assets/js/qrcode.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/app.js') ?>"></script>
</body>
</html>
