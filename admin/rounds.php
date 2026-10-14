<?php
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helper.php';
require_once __DIR__ . '/../core/Csrf.php';

Auth::requireLogin();
$pdo = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyOrDie();
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_active') {
        $rid = (int)($_POST['round_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE booking_rounds SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$rid]);
        Helper::setFlash('success', 'เปลี่ยนสถานะรอบการจองแล้ว');
        header("Location: " . base_url('admin/rounds.php'));
        exit;
    }

    if ($action === 'save_round') {
        $rid = (int)($_POST['round_id'] ?? 0);
        $code = trim($_POST['round_code'] ?? '');
        $name = trim($_POST['round_name'] ?? '');
        $start = trim($_POST['start_date'] ?? '');
        $isUnlimitedEnd = isset($_POST['is_unlimited_end']);
        $end = (!$isUnlimitedEnd && !empty($_POST['end_date'])) ? trim($_POST['end_date']) : null;
        $desc = trim($_POST['description'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($rid > 0) {
            $stmt = $pdo->prepare("UPDATE booking_rounds SET round_code=?, round_name=?, start_date=?, end_date=?, is_active=?, description=? WHERE id=?");
            $stmt->execute([$code, $name, $start, $end, $isActive, $desc, $rid]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO booking_rounds (round_code, round_name, start_date, end_date, is_active, description) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$code, $name, $start, $end, $isActive, $desc]);
        }
        Helper::setFlash('success', 'บันทึกข้อมูลรอบการจองเรียบร้อยแล้ว');
        header("Location: " . base_url('admin/rounds.php'));
        exit;
    }
}

$rounds = $pdo->query("
    SELECT r.*,
           (SELECT COUNT(*) FROM orders WHERE round_id = r.id) as total_orders,
           (SELECT COALESCE(SUM(quantity), 0) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.round_id = r.id AND o.status IN ('confirmed', 'completed')) as confirmed_pieces
    FROM booking_rounds r
    ORDER BY r.id DESC
")->fetchAll();

$pageTitle = "จัดการรอบการสั่งจอง - CMTC Admin";
$activePage = 'rounds';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">จัดการรอบการสั่งจอง (Booking Batches)</h1>
            <p class="text-xs text-slate-500 mt-0.5">กำหนดระยะเวลาเปิด-ปิดรับจองเสื้อแต่ละรุ่น/แต่ละช่วงเวลา</p>
        </div>
        <button onclick="openRoundModal()" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md transition flex items-center justify-center btn-touch">
            <i class="fas fa-plus mr-2"></i> เพิ่มรอบการจองใหม่
        </button>
    </div>

    <!-- Mobile Cards View (Smartphones) -->
    <div class="block md:hidden space-y-4 mb-6">
        <?php if (empty($rounds)): ?>
            <div class="bg-white rounded-2xl p-8 text-center text-slate-400 border border-slate-200">
                <i class="fas fa-inbox text-3xl mb-2 text-slate-300"></i>
                <p class="text-xs">ยังไม่มีข้อมูลรอบการจอง</p>
            </div>
        <?php else: ?>
            <?php foreach ($rounds as $r): ?>
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="font-mono text-xs font-bold text-blue-700 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-100">
                            <?= htmlspecialchars($r['round_code']) ?>
                        </span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold <?= $r['is_active'] ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-600' ?>">
                            <i class="fas fa-circle text-[6px] mr-1.5 <?= $r['is_active'] ? 'text-emerald-500' : 'text-slate-400' ?>"></i>
                            <?= $r['is_active'] ? 'เปิดรับจอง' : 'ปิดแล้ว' ?>
                        </span>
                    </div>

                    <h3 class="font-bold text-slate-900 text-sm mb-1"><?= htmlspecialchars($r['round_name']) ?></h3>
                    <?php if (!empty($r['description'])): ?>
                        <p class="text-xs text-slate-500 mb-3 line-clamp-2"><?= htmlspecialchars($r['description']) ?></p>
                    <?php endif; ?>
                    
                    <div class="bg-slate-50/80 rounded-xl p-3 border border-slate-100 text-xs text-slate-600 space-y-1.5 mb-3">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400"><i class="far fa-calendar-alt mr-1"></i> เริ่มต้น:</span>
                            <span class="font-semibold text-slate-700"><?= Helper::thaiDate($r['start_date'], false) ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400"><i class="far fa-calendar-check mr-1"></i> สิ้นสุด:</span>
                            <?php if (!empty($r['end_date'])): ?>
                                <span class="font-semibold text-slate-700"><?= Helper::thaiDate($r['end_date'], false) ?></span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <i class="fas fa-infinity mr-1"></i> ไม่จำกัด (เปิดตลอด)
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 mb-3">
                        <div class="bg-blue-50/60 border border-blue-100/80 rounded-xl p-2.5 text-center">
                            <div class="text-[10px] font-bold text-blue-600 uppercase">คำสั่งจอง</div>
                            <div class="text-base font-black text-slate-900 mt-0.5"><?= number_format($r['total_orders']) ?> <span class="text-[10px] font-normal text-slate-500">ใบ</span></div>
                        </div>
                        <div class="bg-indigo-50/60 border border-indigo-100/80 rounded-xl p-2.5 text-center">
                            <div class="text-[10px] font-bold text-indigo-600 uppercase">ยอดผลิตรวม</div>
                            <div class="text-base font-black text-indigo-700 mt-0.5"><?= number_format($r['confirmed_pieces']) ?> <span class="text-[10px] font-normal text-slate-500">ชิ้น/ตัว</span></div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                        <form action="<?= base_url('admin/rounds.php') ?>" method="POST" class="flex-1">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action" value="toggle_active">
                            <input type="hidden" name="round_id" value="<?= $r['id'] ?>">
                            <button type="submit" class="w-full btn-touch rounded-xl border text-xs font-bold <?= $r['is_active'] ? 'border-amber-300 text-amber-700 bg-amber-50/60 hover:bg-amber-100' : 'border-emerald-300 text-emerald-700 bg-emerald-50/60 hover:bg-emerald-100' ?> transition">
                                <i class="fas <?= $r['is_active'] ? 'fa-pause' : 'fa-play' ?> mr-1"></i> <?= $r['is_active'] ? 'ปิดรอบ' : 'เปิดรอบ' ?>
                            </button>
                        </form>
                        <button onclick='editRound(<?= json_encode($r) ?>)' class="flex-1 btn-touch rounded-xl bg-blue-600 text-white hover:bg-blue-700 text-xs font-bold transition shadow-xs flex items-center justify-center">
                            <i class="fas fa-edit mr-1.5"></i> แก้ไขข้อมูล
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Desktop Table View (Tablets & Desktops) -->
    <div class="hidden md:block bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-4">รหัสรอบ</th>
                        <th class="py-3.5 px-4">ชื่อรอบการจอง</th>
                        <th class="py-3.5 px-4">วันที่เปิดจอง</th>
                        <th class="py-3.5 px-4">วันที่ปิดจอง</th>
                        <th class="py-3.5 px-4 text-center">คำสั่งจอง</th>
                        <th class="py-3.5 px-4 text-center">ยอดผลิต (ชิ้น/ตัว)</th>
                        <th class="py-3.5 px-4 text-center">สถานะ</th>
                        <th class="py-3.5 px-4 text-right">การจัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php foreach ($rounds as $r): ?>
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4 font-mono font-bold text-blue-700">
                                <?= htmlspecialchars($r['round_code']) ?>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900"><?= htmlspecialchars($r['round_name']) ?></div>
                                <div class="text-[11px] text-slate-400 truncate max-w-xs"><?= htmlspecialchars($r['description']) ?></div>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600">
                                <?= Helper::thaiDate($r['start_date'], false) ?>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600">
                                <?php if (!empty($r['end_date'])): ?>
                                    <?= Helper::thaiDate($r['end_date'], false) ?>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fas fa-infinity mr-1"></i> ไม่จำกัด (เปิดตลอด)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold">
                                <?= number_format($r['total_orders']) ?>
                            </td>
                            <td class="py-3.5 px-4 text-center font-black text-blue-600">
                                <?= number_format($r['confirmed_pieces']) ?>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?= $r['is_active'] ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-600' ?>">
                                    <?= $r['is_active'] ? 'เปิดรับจอง' : 'ปิดแล้ว' ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex space-x-1.5">
                                    <form action="<?= base_url('admin/rounds.php') ?>" method="POST" class="inline">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="action" value="toggle_active">
                                        <input type="hidden" name="round_id" value="<?= $r['id'] ?>">
                                        <button type="submit" class="px-2.5 py-1 rounded-lg border text-[11px] font-semibold <?= $r['is_active'] ? 'border-amber-300 text-amber-700 hover:bg-amber-50' : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50' ?>">
                                            <?= $r['is_active'] ? 'ปิดรอบ' : 'เปิดรอบ' ?>
                                        </button>
                                    </form>
                                    <button onclick='editRound(<?= json_encode($r) ?>)' class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 text-[11px] font-bold">
                                        แก้ไข
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add / Edit Round -->
<div id="roundModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm hidden p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl relative">
        <button onclick="closeRoundModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 w-8 h-8 rounded-full flex items-center justify-center hover:bg-slate-100 transition">
            <i class="fas fa-times"></i>
        </button>

        <h3 class="text-base font-black text-slate-900 mb-1" id="roundModalTitle">เพิ่มรอบการจองใหม่</h3>
        <p class="text-xs text-slate-500 mb-6">ระบุช่วงเวลาและรายละเอียดของรอบการจอง</p>

        <form action="<?= base_url('admin/rounds.php') ?>" method="POST" class="space-y-4">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="save_round">
            <input type="hidden" name="round_id" id="formRoundId" value="0">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">รหัสรอบ (Round Code) <span class="text-rose-500">*</span></label>
                <input type="text" name="round_code" id="formRoundCode" required placeholder="เช่น ROUND-2026-02" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">ชื่อรอบการสั่งจอง <span class="text-rose-500">*</span></label>
                <input type="text" name="round_name" id="formRoundName" required placeholder="เช่น รอบที่ 2: เสื้อกิจกรรมปีการศึกษา 2569" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">วันเริ่มเปิดจอง <span class="text-rose-500">*</span></label>
                    <input type="datetime-local" name="start_date" id="formStartDate" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold text-slate-700">วันสิ้นสุดการจอง <span id="endDateRequiredStar" class="text-rose-500">*</span></label>
                        <label class="inline-flex items-center space-x-1 cursor-pointer select-none">
                            <input type="checkbox" name="is_unlimited_end" id="formIsUnlimitedEnd" value="1" onchange="toggleUnlimitedEnd(this.checked)" class="w-3.5 h-3.5 rounded text-blue-600 focus:ring-blue-500">
                            <span class="text-[11px] font-bold text-blue-600">ไม่จำกัด</span>
                        </label>
                    </div>
                    <input type="datetime-local" name="end_date" id="formEndDate" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 disabled:bg-slate-100 disabled:text-slate-400">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">คำอธิบายเพิ่มเติม</label>
                <textarea name="description" id="formRoundDesc" rows="3" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200"></textarea>
            </div>

            <div class="flex items-center space-x-2">
                <input type="checkbox" name="is_active" id="formIsActive" value="1" checked class="w-4 h-4 rounded text-blue-600">
                <label for="formIsActive" class="text-xs font-bold text-slate-700">เปิดใช้งานรอบนี้ทันที</label>
            </div>

            <div class="pt-4 flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 sm:justify-end border-t border-slate-100">
                <button type="button" onclick="closeRoundModal()" class="w-full sm:w-auto px-4 py-2.5 bg-slate-100 text-slate-600 rounded-xl text-xs font-semibold hover:bg-slate-200 btn-touch flex items-center justify-center">
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
function toggleUnlimitedEnd(checked) {
    const endInput = document.getElementById('formEndDate');
    const star = document.getElementById('endDateRequiredStar');
    if (checked) {
        endInput.disabled = true;
        endInput.required = false;
        endInput.value = '';
        if (star) star.classList.add('hidden');
    } else {
        endInput.disabled = false;
        endInput.required = true;
        if (!endInput.value) {
            endInput.value = '<?= date("Y-m-d\T23:59", strtotime("+30 days")) ?>';
        }
        if (star) star.classList.remove('hidden');
    }
}

function openRoundModal() {
    document.getElementById('roundModalTitle').textContent = 'เพิ่มรอบการจองใหม่';
    document.getElementById('formRoundId').value = '0';
    document.getElementById('formRoundCode').value = '';
    document.getElementById('formRoundName').value = '';
    document.getElementById('formStartDate').value = '<?= date("Y-m-d\T08:00") ?>';
    document.getElementById('formEndDate').value = '<?= date("Y-m-d\T23:59", strtotime("+30 days")) ?>';
    document.getElementById('formIsUnlimitedEnd').checked = false;
    toggleUnlimitedEnd(false);
    document.getElementById('formRoundDesc').value = '';
    document.getElementById('formIsActive').checked = true;
    document.getElementById('roundModal').classList.remove('hidden');
}

function closeRoundModal() {
    document.getElementById('roundModal').classList.add('hidden');
}

function editRound(rnd) {
    document.getElementById('roundModalTitle').textContent = 'แก้ไขรอบการจอง: ' + rnd.round_name;
    document.getElementById('formRoundId').value = rnd.id;
    document.getElementById('formRoundCode').value = rnd.round_code;
    document.getElementById('formRoundName').value = rnd.round_name;
    document.getElementById('formStartDate').value = rnd.start_date ? rnd.start_date.replace(' ', 'T').substring(0, 16) : '';
    
    const isUnlim = (!rnd.end_date || rnd.end_date === '' || rnd.end_date === '0000-00-00 00:00:00');
    document.getElementById('formIsUnlimitedEnd').checked = isUnlim;
    toggleUnlimitedEnd(isUnlim);
    if (!isUnlim) {
        document.getElementById('formEndDate').value = rnd.end_date.replace(' ', 'T').substring(0, 16);
    }
    
    document.getElementById('formRoundDesc').value = rnd.description || '';
    document.getElementById('formIsActive').checked = (rnd.is_active == 1);
    document.getElementById('roundModal').classList.remove('hidden');
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
