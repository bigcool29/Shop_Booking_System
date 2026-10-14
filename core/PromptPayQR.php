<?php
/**
 * PromptPay Dynamic QR Code Generator (EMVCo Standard)
 * Conforms to Bank of Thailand (BOT) Standard QR Code Specification
 */

class PromptPayQR {
    /**
     * Generate EMVCo QR Payload for PromptPay
     *
     * @param string $target Mobile number (10 digits) or National ID/Tax ID (13 digits)
     * @param float|null $amount Amount in THB (null for static QR)
     * @return string EMVCo formatted payload string
     */
    public static function generatePayload($target, $amount = null) {
        $targetClean = preg_replace('/[^0-9]/', '', $target);
        
        // Determine whether mobile phone or national ID / tax ID
        if (strlen($targetClean) === 10 && substr($targetClean, 0, 1) === '0') {
            // Mobile: 13 digits with country code 0066
            $formattedTarget = '0066' . substr($targetClean, 1);
            $subtag = '01';
        } elseif (strlen($targetClean) === 13) {
            // National ID / Tax ID: 13 digits
            $formattedTarget = $targetClean;
            $subtag = '02';
        } else {
            // Default to subtag 01 or fallback
            $formattedTarget = $targetClean;
            $subtag = '01';
        }

        // Tag 29: Merchant Account Information
        $aid = 'A000000677010111';
        $tag29_00 = '00' . sprintf('%02d', strlen($aid)) . $aid;
        $tag29_target = $subtag . sprintf('%02d', strlen($formattedTarget)) . $formattedTarget;
        $tag29_val = $tag29_00 . $tag29_target;
        $tag29 = '29' . sprintf('%02d', strlen($tag29_val)) . $tag29_val;

        // Initiate payload
        $payload = '000201'; // Tag 00: Version 01
        
        if ($amount !== null && $amount > 0) {
            $payload .= '010212'; // Tag 01: Dynamic QR (Point of Initiation Method)
        } else {
            $payload .= '010211'; // Static QR
        }

        $payload .= $tag29;
        $payload .= '5303764'; // Tag 53: Currency THB (764)

        if ($amount !== null && $amount > 0) {
            $amountStr = number_format((float)$amount, 2, '.', '');
            $payload .= '54' . sprintf('%02d', strlen($amountStr)) . $amountStr; // Tag 54: Amount
        }

        $payload .= '5802TH'; // Tag 58: Country Code (TH)
        $payload .= '6304';   // Tag 63: CRC placeholder

        // Calculate CRC16-CCITT checksum
        $crc = self::crc16($payload);
        return $payload . $crc;
    }

    /**
     * Compute CRC16-CCITT (0xFFFF, 0x1021)
     */
    private static function crc16($data) {
        $crc = 0xFFFF;
        $length = strlen($data);
        for ($i = 0; $i < $length; $i++) {
            $x = (($crc >> 8) ^ ord($data[$i])) & 0xFF;
            $x ^= $x >> 4;
            $crc = (($crc << 8) ^ ($x << 12) ^ ($x << 5) ^ $x) & 0xFFFF;
        }
        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    /**
     * Helper to format PromptPay number for user display
     */
    public static function formatDisplayNumber($target) {
        $clean = preg_replace('/[^0-9]/', '', $target);
        if (strlen($clean) === 10) {
            return substr($clean, 0, 3) . '-' . substr($clean, 3, 3) . '-' . substr($clean, 6);
        } elseif (strlen($clean) === 13) {
            return substr($clean, 0, 1) . '-' . substr($clean, 1, 4) . '-' . substr($clean, 5, 5) . '-' . substr($clean, 10, 2) . '-' . substr($clean, 12, 1);
        }
        return $target;
    }
}
