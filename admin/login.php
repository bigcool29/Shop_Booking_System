<?php
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helper.php';
require_once __DIR__ . '/../core/Csrf.php';
require_once __DIR__ . '/../core/Theme.php';

// Redirect if already logged in
if (Auth::check()) {
    header("Location: " . base_url('admin/index.php'));
    exit;
}

$error = '';
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$rateLimit = Auth::checkRateLimit($clientIp);

if (!$rateLimit['allowed']) {
    $error = "คุณกรอกรหัสผ่านผิดเกินกำหนด (5 ครั้ง) เพื่อความปลอดภัย กรุณารออีก " . $rateLimit['remaining_minutes'] . " นาที ก่อนลองใหม่อีกครั้ง";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyOrDie();

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (Auth::login($username, $password)) {
        Auth::clearRateLimit($clientIp);
        Helper::setFlash('success', 'เข้าสู่ระบบสำเร็จ', 'ยินดีต้อนรับเข้าสู่ระบบจัดการ');
        header("Location: " . base_url('admin/index.php'));
        exit;
    } else {
        Auth::recordFailedLogin($clientIp);
        $attempts = $_SESSION['login_attempts_' . md5($clientIp)]['count'] ?? 1;
        $remainingAttempts = max(0, 5 - $attempts);
        if ($remainingAttempts > 0) {
            $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง (เหลือโอกาสลองอีก {$remainingAttempts} ครั้ง)";
        } else {
            $error = "คุณกรอกรหัสผ่านผิดเกินกำหนด (5 ครั้ง) บัญชีถูกระงับชั่วคราว 15 นาที";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบเจ้าหน้าที่ - CMTC Admin</title>
    <!-- Tailwind CSS CDN & Dynamic Theme Engine -->
    <script src="https://cdn.tailwindcss.com"></script>
    <?= Theme::renderHead() ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/custom.css') ?>">
</head>
<body class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <?php 
        $loginLogo = get_setting('site_logo', 'assets/images/cmtc_logo.jpg');
        if (!empty($loginLogo) && file_exists(__DIR__ . '/../' . $loginLogo)): 
        ?>
            <img src="<?= base_url($loginLogo) ?>" alt="CMTC Logo" class="w-16 h-16 object-contain rounded-2xl mx-auto mb-4 bg-white p-1 shadow-lg border border-slate-200">
        <?php else: ?>
            <div class="w-16 h-16 rounded-3xl bg-blue-600 text-white flex items-center justify-center font-black text-2xl mx-auto mb-4 shadow-lg">
                <i class="fas fa-user-shield"></i>
            </div>
        <?php endif; ?>
        <h2 class="text-2xl font-black text-slate-900 tracking-tight">เข้าสู่ระบบเจ้าหน้าที่</h2>
        <p class="mt-1 text-xs text-slate-500">ระบบบริหารจัดการการสั่งจองเสื้อ CMTC Official Store</p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4">
        <div class="bg-white py-8 px-6 sm:px-10 rounded-3xl border border-slate-200/80 shadow-xl">
            
            <?php if (!empty($error)): ?>
                <div class="mb-5 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center">
                    <i class="fas fa-exclamation-circle mr-2 text-rose-500 text-base"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form action="<?= base_url('admin/login.php') ?>" method="POST" class="space-y-4">
                <?= Csrf::field() ?>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        ชื่อผู้ใช้งาน (Username)
                    </label>
                    <input type="text" name="username" required autofocus class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 text-sm text-slate-900 transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        รหัสผ่าน (Password)
                    </label>
                    <input type="password" name="password" required  class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 text-sm text-slate-900 transition">
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-md hover:shadow-lg transition flex items-center justify-center active:scale-[0.98]">
                        <i class="fas fa-sign-in-alt mr-2"></i> เข้าสู่ระบบ
                    </button>
                </div>
            </form>

            <!-- Demo helper -->
          

            <div class="mt-4 text-center">
                <a href="<?= base_url() ?>" class="text-xs text-blue-600 hover:underline">
                    <i class="fas fa-arrow-left mr-1"></i> กลับไปหน้าร้าน
                </a>
            </div>
        </div>
    </div>
</body>
</html>
