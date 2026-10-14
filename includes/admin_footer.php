        </main>

        <!-- Admin Content Footer -->
        <footer class="bg-white border-t border-slate-200 py-4 px-6 text-center sm:text-left text-xs text-slate-500 no-print flex flex-col sm:flex-row justify-between items-center gap-2">
            <div>
                &copy; <?= date('Y') ?> ระบบบริหารจัดการการสั่งจองเสื้อออนไลน์ • Chiang Mai Technical College (CMTC)
            </div>
            <div class="text-[11px] text-slate-400">
                เวอร์ชันระบบ 1.0 (Mobile Responsive Admin)
            </div>
        </footer>
    </div>

    <!-- Mobile Bottom Navigation Bar (App Dock) -->
    <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur-lg border-t border-slate-200/90 shadow-[0_-4px_20px_rgba(0,0,0,0.06)] px-2 py-1.5 flex items-center justify-around no-print pb-safe">
        <a href="<?= base_url('admin/index.php') ?>" class="flex flex-col items-center justify-center py-1 px-2.5 rounded-xl transition <?= ($activePage ?? '') === 'dashboard' ? 'text-blue-600 font-bold' : 'text-slate-400 hover:text-slate-700' ?>">
            <i class="fas fa-chart-line text-lg mb-0.5"></i>
            <span class="text-[10px]">แดชบอร์ด</span>
        </a>
        <a href="<?= base_url('admin/orders.php') ?>" class="relative flex flex-col items-center justify-center py-1 px-2.5 rounded-xl transition <?= ($activePage ?? '') === 'orders' ? 'text-blue-600 font-bold' : 'text-slate-400 hover:text-slate-700' ?>">
            <i class="fas fa-clipboard-list text-lg mb-0.5"></i>
            <span class="text-[10px]">คำสั่งจอง</span>
            <?php if (($pendingVerifyCount ?? 0) > 0): ?>
                <span class="absolute -top-0.5 right-1 min-w-[18px] h-[18px] bg-rose-500 text-white rounded-full text-[10px] font-black flex items-center justify-center shadow-xs animate-pulse px-1">
                    <?= $pendingVerifyCount ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="<?= base_url('admin/products.php') ?>" class="flex flex-col items-center justify-center py-1 px-2.5 rounded-xl transition <?= ($activePage ?? '') === 'products' ? 'text-blue-600 font-bold' : 'text-slate-400 hover:text-slate-700' ?>">
            <i class="fas fa-box text-lg mb-0.5"></i>
            <span class="text-[10px]">สินค้า</span>
        </a>
        <a href="<?= base_url('admin/rounds.php') ?>" class="flex flex-col items-center justify-center py-1 px-2.5 rounded-xl transition <?= ($activePage ?? '') === 'rounds' ? 'text-blue-600 font-bold' : 'text-slate-400 hover:text-slate-700' ?>">
            <i class="far fa-calendar-check text-lg mb-0.5"></i>
            <span class="text-[10px]">รอบจอง</span>
        </a>
        <button type="button" onclick="toggleSidebar()" class="flex flex-col items-center justify-center py-1 px-2.5 rounded-xl text-slate-400 hover:text-slate-700 transition" aria-label="เปิดเมนู">
            <i class="fas fa-bars text-lg mb-0.5"></i>
            <span class="text-[10px]">เมนู</span>
        </button>
    </nav>

    <!-- Scripts -->
    <script src="<?= base_url('assets/js/qrcode.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/app.js') ?>"></script>
    <script>
    // Responsive Sidebar Toggle Logic
    function toggleSidebar() {
        const sidebar = document.getElementById('adminSidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        if (sidebar && backdrop) {
            const isClosed = sidebar.classList.contains('-translate-x-full');
            if (isClosed) {
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
            }
        }
    }
    </script>
</body>
</html>
