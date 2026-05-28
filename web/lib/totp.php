<?php
/**
 * totp.php — RFC 6238 TOTP implementation and QR code generation
 * Feature 037
 */

require_once __DIR__ . '/crypto.php';

/**
 * Generate a random Base32-encoded TOTP secret.
 */
function generateTotpSecret(int $length = 32): string {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    for ($i = 0; $i < $length; $i++) {
        $secret .= $chars[random_int(0, 31)];
    }
    return $secret;
}

/**
 * Base32 decode (RFC 4648).
 */
function base32Decode(string $input): string {
    $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $input = strtoupper(str_replace('=', '', $input));
    $output = '';
    $buffer = 0;
    $bitsLeft = 0;
    for ($i = 0; $i < strlen($input); $i++) {
        $val = strpos($map, $input[$i]);
        if ($val === false) continue;
        $buffer = ($buffer << 5) | $val;
        $bitsLeft += 5;
        if ($bitsLeft >= 8) {
            $bitsLeft -= 8;
            $output .= chr(($buffer >> $bitsLeft) & 0xFF);
        }
    }
    return $output;
}

/**
 * Base32 encode (RFC 4648).
 */
function base32Encode(string $input): string {
    $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $output = '';
    $buffer = 0;
    $bitsLeft = 0;
    for ($i = 0; $i < strlen($input); $i++) {
        $buffer = ($buffer << 8) | ord($input[$i]);
        $bitsLeft += 8;
        while ($bitsLeft >= 5) {
            $output .= $map[($buffer >> ($bitsLeft - 5)) & 31];
            $bitsLeft -= 5;
        }
    }
    if ($bitsLeft > 0) {
        $output .= $map[($buffer << (5 - $bitsLeft)) & 31];
    }
    return $output;
}

/**
 * Compute HMAC-SHA1 for TOTP.
 */
function hmacSha1(string $key, string $data): string {
    return hash_hmac('sha1', $data, $key, true);
}

/**
 * Generate a 6-digit TOTP code for a given secret and time slice.
 */
function generateTotpCode(string $secret, int $timeStep = 30, int $time = null): string {
    $time = $time ?? time();
    $counter = intdiv($time, $timeStep);
    $secretBin = base32Decode($secret);
    $counterBin = pack('N*', 0, $counter);
    $hash = hmacSha1($secretBin, $counterBin);
    $offset = ord($hash[19]) & 0x0F;
    $code = ((ord($hash[$offset]) & 0x7F) << 24 |
             (ord($hash[$offset + 1]) & 0xFF) << 16 |
             (ord($hash[$offset + 2]) & 0xFF) << 8 |
             (ord($hash[$offset + 3]) & 0xFF)) % 1000000;
    return str_pad((string)$code, 6, '0', STR_PAD_LEFT);
}

/**
 * Verify a TOTP code with ±1 window tolerance.
 */
function verifyTotpCode(string $secret, string $code, int $window = 1): bool {
    $time = time();
    for ($i = -$window; $i <= $window; $i++) {
        if (hash_equals(generateTotpCode($secret, 30, $time + ($i * 30)), $code)) {
            return true;
        }
    }
    return false;
}

/**
 * Get the current time window index.
 */
function getTotpWindow(int $timeStep = 30, int $time = null): int {
    return intdiv($time ?? time(), $timeStep);
}

/**
 * Generate a QR code SVG for TOTP enrollment.
 */
function generateQrCodeSvg(string $otpAuthUri, int $size = 200): string {
    // Use Google Charts API for simplicity (can be replaced with local QR lib)
    $encoded = urlencode($otpAuthUri);
    $url = "https://chart.googleapis.com/chart?chs={$size}x{$size}&chld=M|0&cht=qr&chl={$encoded}";
    return '<img src="' . htmlspecialchars($url) . '" alt="MFA QR Code" width="' . $size . '" height="' . $size . '" style="image-rendering:pixelated;">';
}

/**
 * Build otpauth:// URI.
 */
function buildOtpAuthUri(string $username, string $secret, string $issuer = 'TSiSIP'): string {
    $label = urlencode($issuer . ':' . $username);
    $issuerEnc = urlencode($issuer);
    return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuerEnc}&algorithm=SHA1&digits=6&period=30";
}

/**
 * Generate backup codes.
 */
function generateBackupCodes(int $count = 10): array {
    $codes = [];
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    for ($i = 0; $i < $count; $i++) {
        $code = '';
        for ($j = 0; $j < 12; $j++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $codes[] = $code;
    }
    return $codes;
}
