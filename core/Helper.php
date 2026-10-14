<?php
/**
 * Utility Helpers
 */

class Helper {
    /**
     * Format currency in Thai Baht
     */
    public static function money($amount) {
        return number_format((float)$amount, 2);
    }

    /**
     * Format Thai Date
     */
    public static function thaiDate($datetime, $showTime = true) {
        if (!$datetime) return '-';
        $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
        if (!$timestamp) return '-';

        $thaiMonths = [
            1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
            5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
            9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
        ];

        $day = date('j', $timestamp);
        $month = $thaiMonths[(int)date('n', $timestamp)];
        $year = (int)date('Y', $timestamp) + 543;

        $dateStr = "{$day} {$month} พ.ศ. {$year}";
        if ($showTime) {
            $dateStr .= " " . date('H:i', $timestamp) . " น.";
        }
        return $dateStr;
    }

    /**
     * Sanitize string input
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Return JSON response and exit
     */
    public static function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Set a flash message for SweetAlert2
     */
    public static function setFlash($icon, $title, $text = '') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash_message'] = [
            'icon' => $icon, // success, error, warning, info
            'title' => $title,
            'text' => $text
        ];
    }

    /**
     * Get and clear flash message
     */
    public static function getFlash() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['flash_message'])) {
            $msg = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            return $msg;
        }
        return null;
    }

    /**
     * Handle Secure File Upload
     *
     * @param array $file $_FILES['input_name']
     * @param string $destinationSubdir Subdir under assets/uploads/
     * @param array $allowedMimes
     * @param int $maxBytes
     * @return array [success => bool, filename => string, error => string]
     */
    public static function uploadFile($file, $destinationSubdir = 'slips', $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'], $maxBytes = 5242880) {
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['success' => false, 'error' => 'พารามิเตอร์ของไฟล์ไม่ถูกต้อง'];
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return ['success' => false, 'error' => 'กรุณาเลือกไฟล์เพื่ออัปโหลด'];
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['success' => false, 'error' => 'ขนาดไฟล์เกินขีดจำกัดที่ระบบกำหนด (สูงสุด 5MB)'];
            default:
                return ['success' => false, 'error' => 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์'];
        }

        if ($file['size'] > $maxBytes) {
            return ['success' => false, 'error' => 'ขนาดไฟล์ต้องไม่เกิน ' . ($maxBytes / 1048576) . ' MB'];
        }

        // Verify Real MIME Type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, $allowedMimes)) {
            return ['success' => false, 'error' => 'รูปแบบไฟล์ไม่รองรับ (อนุญาตเฉพาะ JPG, PNG, WEBP)'];
        }

        // Determine extension
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp'
        ];
        $ext = $extensions[$mime] ?? 'jpg';

        $uploadRoot = realpath(__DIR__ . '/../assets/uploads');
        if (!$uploadRoot) {
            $uploadRoot = __DIR__ . '/../assets/uploads';
            if (!is_dir($uploadRoot)) {
                mkdir($uploadRoot, 0755, true);
            }
        }

        $targetDir = $uploadRoot . '/' . trim($destinationSubdir, '/');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Generate cryptographically secure filename
        $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $destinationPath = $targetDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
            return ['success' => false, 'error' => 'ไม่สามารถบันทึกไฟล์ลงเซิร์ฟเวอร์ได้'];
        }

        return [
            'success' => true,
            'filename' => 'assets/uploads/' . trim($destinationSubdir, '/') . '/' . $filename
        ];
    }

    /**
     * Handle Multiple Files Upload
     *
     * @param array $filesArray $_FILES['input_name'] with multiple files
     * @param string $destinationSubdir
     * @return array Array of uploaded filenames
     */
    public static function uploadMultipleFiles($filesArray, $destinationSubdir = 'products') {
        $uploaded = [];
        if (!isset($filesArray['name']) || !is_array($filesArray['name'])) {
            return $uploaded;
        }

        $count = count($filesArray['name']);
        for ($i = 0; $i < $count; $i++) {
            if (empty($filesArray['tmp_name'][$i]) || $filesArray['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $singleFile = [
                'name'     => $filesArray['name'][$i],
                'type'     => $filesArray['type'][$i] ?? '',
                'tmp_name' => $filesArray['tmp_name'][$i],
                'error'    => $filesArray['error'][$i],
                'size'     => $filesArray['size'][$i] ?? 0
            ];

            $res = self::uploadFile($singleFile, $destinationSubdir);
            if ($res['success']) {
                $uploaded[] = $res['filename'];
            }
        }

        return $uploaded;
    }

    /**
     * Return Status Badge HTML
     */
    public static function getStatusBadge($status) {
        $badges = [
            'pending_payment' => '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200"><i class="fas fa-clock mr-1.5"></i> รอชำระเงิน</span>',
            'waiting_verification' => '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200"><i class="fas fa-file-invoice-dollar mr-1.5"></i> รอตรวจสอบสลิป</span>',
            'confirmed' => '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200"><i class="fas fa-check-circle mr-1.5"></i> ยืนยันการจองแล้ว</span>',
            'rejected' => '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-200"><i class="fas fa-times-circle mr-1.5"></i> สลิปไม่ถูกต้อง / ปฏิเสธ</span>',
            'completed' => '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 border border-purple-200"><i class="fas fa-box-open mr-1.5"></i> รับสินค้าแล้ว</span>',
            'cancelled' => '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200"><i class="fas fa-ban mr-1.5"></i> หมดเวลา / ยกเลิก</span>',
        ];

        return $badges[$status] ?? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">' . htmlspecialchars($status) . '</span>';
    }
}
