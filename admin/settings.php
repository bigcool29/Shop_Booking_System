<?php
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../core/Auth.php';

Auth::requireSuperAdmin();
header("Location: " . base_url('admin/settings_brand.php'));
exit;
