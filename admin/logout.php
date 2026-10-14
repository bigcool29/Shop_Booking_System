<?php
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../core/Auth.php';

Auth::logout();
header("Location: " . base_url('admin/login.php'));
exit;
