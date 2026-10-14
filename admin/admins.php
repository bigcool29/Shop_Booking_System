<?php
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helper.php';
require_once __DIR__ . '/../core/Csrf.php';

Auth::requireSuperAdmin();
$currentUser = Auth::user();
$pdo = Database::getInstance()->getConnection();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    Csrf::verifyOrDie();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_user') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = ($_POST['role'] ?? 'admin') === 'superadmin' ? 'superadmin' : 'admin';
        $password = trim($_POST['password'] ?? '');

        if ($uid > 0) {
            // Update
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET fullname=?, email=?, phone=?, role=?, password=? WHERE id=?");
                $stmt->execute([$fullname, $email, $phone, $role, $hash, $uid]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET fullname=?, email=?, phone=?, role=? WHERE id=?");
                $stmt->execute([$fullname, $email, $phone, $role, $uid]);
            }
            Helper::setFlash('success', 'อัปเดตข้อมูลผู้ใช้แล้ว');
        } else {
            // Insert
            if (empty($password)) $password = 'admin1234';
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, fullname, email, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hash, $fullname, $email, $phone, $role]);
            Helper::setFlash('success', 'เพิ่มผู้ดูแลระบบใหม่แล้ว');
        }
        header("Location: " . base_url('admin/admins.php'));
        exit;
    }

    if ($action === 'toggle_active') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid === (int)$currentUser['id']) {
            Helper::setFlash('error', 'ไม่สามารถปิดการใช้งานบัญชีตนเองได้');
        } else {
            $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$uid]);
            Helper::setFlash('success', 'เปลี่ยนสถานะผู้ใช้แล้ว');
        }
        header("Location: " . base_url('admin/admins.php'));
        exit;
    }

    if ($action === 'delete_user') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid <= 0) {
            Helper::setFlash('error', 'เกิดข้อผิดพลาด', 'ไม่พบรหัสผู้ดูแลระบบที่ต้องการลบ');
            header("Location: " . base_url('admin/admins.php'));
            exit;
        }

        if ($uid === (int)$currentUser['id']) {
            Helper::setFlash('error', 'ไม่สามารถลบได้', 'คุณไม่สามารถลบบัญชีของตนเองที่กำลังเข้าสู่ระบบอยู่ได้');
            header("Location: " . base_url('admin/admins.php'));
            exit;
        }

        $targetStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $targetStmt->execute([$uid]);
        $targetUser = $targetStmt->fetch();

        if (!$targetUser) {
            Helper::setFlash('error', 'ไม่พบผู้ใช้', 'ไม่พบบัญชีผู้ดูแลระบบนี้ในระบบ');
            header("Location: " . base_url('admin/admins.php'));
            exit;
        }

        // Prevent deleting the last superadmin
        if ($targetUser['role'] === 'superadmin') {
            $superCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'superadmin'")->fetchColumn();
            if ($superCount <= 1) {
                Helper::setFlash('error', 'ไม่สามารถลบได้', 'ไม่สามารถลบ Superadmin คนสุดท้ายได้ ระบบต้องมีผู้ดูแลระบบสูงสุดอย่างน้อย 1 บัญชี');
                header("Location: " . base_url('admin/admins.php'));
                exit;
            }
        }

        $delStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $delStmt->execute([$uid]);

        Helper::setFlash('success', 'ลบผู้ดูแลระบบสำเร็จ', 'ลบบัญชีผู้ใช้งาน ' . htmlspecialchars($targetUser['username']) . ' เรียบร้อยแล้ว');
        header("Location: " . base_url('admin/admins.php'));
        exit;
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY role DESC, id ASC")->fetchAll();

$pageTitle = "จัดการเจ้าหน้าที่ผู้ดูแลระบบ - CMTC Admin";
$activePage = 'admins';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">จัดการผู้ดูแลระบบ (Admin Users)</h1>
            <p class="text-xs text-slate-500 mt-0.5">เพิ่ม ลบ หรือแก้ไขสิทธิ์การเข้าใช้งานระบบเจ้าหน้าที่</p>
        </div>
        <button onclick="openUserModal()" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md transition flex items-center justify-center btn-touch">
            <i class="fas fa-user-plus mr-2"></i> เพิ่มผู้ดูแลระบบใหม่
        </button>
    </div>

    <!-- Mobile Cards View (Smartphones) -->
    <div class="block md:hidden space-y-3.5 mb-6">
        <?php foreach ($users as $u): ?>
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4">
                <div class="flex items-center justify-between gap-2 mb-2">
                    <span class="font-mono text-xs font-bold text-slate-900 bg-slate-100 px-2.5 py-1 rounded-lg">
                        <i class="fas fa-user-shield text-slate-400 mr-1"></i><?= htmlspecialchars($u['username']) ?>
                    </span>
                    <div class="flex items-center space-x-1.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold <?= $u['role'] === 'superadmin' ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-blue-100 text-blue-800 border border-blue-200' ?>">
                            <?= htmlspecialchars($u['role']) ?>
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium <?= $u['is_active'] ? 'text-emerald-700 bg-emerald-50' : 'text-slate-400 bg-slate-100' ?>">
                            <?= $u['is_active'] ? 'เปิดใช้งาน' : 'ระงับ' ?>
                        </span>
                    </div>
                </div>

                <div class="font-bold text-slate-900 text-sm mb-1"><?= htmlspecialchars($u['fullname']) ?></div>
                <div class="text-xs text-slate-500 space-y-0.5 mb-3 bg-slate-50/70 p-2.5 rounded-xl border border-slate-100">
                    <div class="flex items-center"><i class="far fa-envelope w-4 text-slate-400"></i> <?= htmlspecialchars($u['email'] ?: 'ไม่ระบุอีเมล') ?></div>
                    <div class="flex items-center font-mono"><i class="fas fa-phone-alt w-4 text-slate-400"></i> <?= htmlspecialchars($u['phone'] ?: 'ไม่ระบุเบอร์โทร') ?></div>
                </div>

                <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                    <button onclick='editUser(<?= json_encode($u) ?>)' class="flex-1 btn-touch rounded-xl bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs font-bold transition flex items-center justify-center">
                        <i class="fas fa-edit mr-1"></i> แก้ไขข้อมูล
                    </button>
                    <?php if ($u['id'] !== $currentUser['id']): ?>
                        <form action="<?= base_url('admin/admins.php') ?>" method="POST" class="flex-1">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action" value="toggle_active">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="w-full btn-touch rounded-xl border text-xs font-semibold <?= $u['is_active'] ? 'border-amber-300 text-amber-700 bg-amber-50/50 hover:bg-amber-100' : 'border-emerald-300 text-emerald-700 bg-emerald-50/50 hover:bg-emerald-100' ?> transition flex items-center justify-center">
                                <?= $u['is_active'] ? 'ระงับ' : 'เปิดใช้' ?>
                            </button>
                        </form>
                        <button type="button" onclick="confirmDeleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>')" class="btn-touch px-3 rounded-xl border border-rose-200 text-rose-700 bg-rose-50 hover:bg-rose-100 text-xs font-bold transition flex items-center justify-center" title="ลบผู้ใช้งาน">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Desktop Table View (Tablets & Desktops) -->
    <div class="hidden md:block bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-4">ชื่อผู้ใช้ (Username)</th>
                        <th class="py-3.5 px-4">ชื่อ-นามสกุล</th>
                        <th class="py-3.5 px-4">อีเมล / เบอร์โทร</th>
                        <th class="py-3.5 px-4 text-center">สิทธิ์การใช้งาน</th>
                        <th class="py-3.5 px-4 text-center">สถานะ</th>
                        <th class="py-3.5 px-4 text-right">การจัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                                <?= htmlspecialchars($u['username']) ?>
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-slate-900">
                                <?= htmlspecialchars($u['fullname']) ?>
                            </td>
                            <td class="py-3.5 px-4 text-slate-500">
                                <div><?= htmlspecialchars($u['email'] ?: '-') ?></div>
                                <div class="text-[11px] font-mono"><?= htmlspecialchars($u['phone'] ?: '-') ?></div>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?= $u['role'] === 'superadmin' ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-blue-100 text-blue-800 border border-blue-200' ?>">
                                    <?= htmlspecialchars($u['role']) ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium <?= $u['is_active'] ? 'text-emerald-700 bg-emerald-50' : 'text-slate-400 bg-slate-100' ?>">
                                    <?= $u['is_active'] ? 'ใช้งานอยู่' : 'ระงับการใช้งาน' ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex space-x-1.5">
                                    <button onclick='editUser(<?= json_encode($u) ?>)' class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 text-[11px] font-bold">
                                        แก้ไข
                                    </button>
                                    <?php if ($u['id'] !== $currentUser['id']): ?>
                                        <form action="<?= base_url('admin/admins.php') ?>" method="POST" class="inline">
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <button type="submit" class="px-2.5 py-1 rounded-lg border text-[11px] font-semibold <?= $u['is_active'] ? 'border-amber-300 text-amber-700 hover:bg-amber-50' : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50' ?>">
                                                <?= $u['is_active'] ? 'ระงับ' : 'เปิดใช้' ?>
                                            </button>
                                        </form>
                                        <button type="button" onclick="confirmDeleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>')" class="px-2.5 py-1 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 text-[11px] font-bold transition flex items-center">
                                            <i class="fas fa-trash-alt mr-1"></i> ลบ
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add / Edit User -->
<div id="userModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm hidden p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl relative">
        <button onclick="closeUserModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 w-8 h-8 rounded-full flex items-center justify-center hover:bg-slate-100 transition">
            <i class="fas fa-times"></i>
        </button>

        <h3 class="text-base font-black text-slate-900 mb-1" id="userModalTitle">เพิ่มผู้ดูแลระบบ</h3>
        <p class="text-xs text-slate-500 mb-6">กำหนดสิทธิ์และข้อมูลบัญชีเจ้าหน้าที่</p>

        <form action="<?= base_url('admin/admins.php') ?>" method="POST" class="space-y-4">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="save_user">
            <input type="hidden" name="user_id" id="formUserId" value="0">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">ชื่อผู้ใช้ (Username) <span class="text-rose-500">*</span></label>
                <input type="text" name="username" id="formUsername" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">รหัสผ่าน <span id="passReqText" class="text-rose-500">*</span></label>
                <input type="password" name="password" id="formPassword" placeholder="เว้นว่างไว้หากไม่ต้องการเปลี่ยน" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">ชื่อ - นามสกุล <span class="text-rose-500">*</span></label>
                <input type="text" name="fullname" id="formFullname" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">อีเมล</label>
                    <input type="email" name="email" id="formEmail" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">เบอร์โทร</label>
                    <input type="text" name="phone" id="formPhone" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">ระดับสิทธิ์ (Role) <span class="text-rose-500">*</span></label>
                <select name="role" id="formRole" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white">
                    <option value="admin">Admin (เจ้าหน้าที่ทั่วไป)</option>
                    <option value="superadmin">Superadmin (ผู้ดูแลระบบสูงสุด)</option>
                </select>
            </div>

            <div class="pt-4 flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 sm:justify-end border-t border-slate-100">
                <button type="button" onclick="closeUserModal()" class="w-full sm:w-auto px-4 py-2.5 bg-slate-100 text-slate-600 rounded-xl text-xs font-semibold hover:bg-slate-200 btn-touch flex items-center justify-center">
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
function openUserModal() {
    document.getElementById('userModalTitle').textContent = 'เพิ่มผู้ดูแลระบบ';
    document.getElementById('formUserId').value = '0';
    document.getElementById('formUsername').value = '';
    document.getElementById('formUsername').readOnly = false;
    document.getElementById('formPassword').value = '';
    document.getElementById('formPassword').required = true;
    document.getElementById('passReqText').classList.remove('hidden');
    document.getElementById('formFullname').value = '';
    document.getElementById('formEmail').value = '';
    document.getElementById('formPhone').value = '';
    document.getElementById('formRole').value = 'admin';
    document.getElementById('userModal').classList.remove('hidden');
}

function closeUserModal() {
    document.getElementById('userModal').classList.add('hidden');
}

function editUser(u) {
    document.getElementById('userModalTitle').textContent = 'แก้ไขข้อมูลผู้ใช้: ' + u.username;
    document.getElementById('formUserId').value = u.id;
    document.getElementById('formUsername').value = u.username;
    document.getElementById('formUsername').readOnly = true;
    document.getElementById('formPassword').value = '';
    document.getElementById('formPassword').required = false;
    document.getElementById('passReqText').classList.add('hidden');
    document.getElementById('formFullname').value = u.fullname;
    document.getElementById('formEmail').value = u.email || '';
    document.getElementById('formPhone').value = u.phone || '';
    document.getElementById('formRole').value = u.role;
    document.getElementById('userModal').classList.remove('hidden');
}

function confirmDeleteUser(userId, username) {
    Swal.fire({
        title: 'ยืนยันการลบผู้ดูแลระบบ?',
        html: `คุณแน่ใจหรือไม่ว่าต้องการลบบัญชีผู้ใช้ <strong>"${username}"</strong> ออกจากระบบอย่างถาวร?<br><span class="text-xs text-rose-500 font-semibold mt-1 inline-block">การกระทำนี้ไม่สามารถย้อนกลับได้</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> ใช่, ยืนยันลบ',
        cancelButtonText: 'ยกเลิก',
        focusCancel: true
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserForm').submit();
        }
    });
}
</script>

<!-- Hidden Delete User Form -->
<form id="deleteUserForm" action="<?= base_url('admin/admins.php') ?>" method="POST" class="hidden">
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="delete_user">
    <input type="hidden" name="user_id" id="deleteUserId" value="0">
</form>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
