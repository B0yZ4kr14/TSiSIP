<?php
/**
 * crypto.php — AES-256-GCM encryption/decryption for MFA secrets
 * Feature 037
 */

function getEncryptionKey(): string {
    $secretPath = '/run/secrets/auth_secret';
    if (!file_exists($secretPath)) {
        $secretPath = '/tmp/auth_secret';
    }
    $key = '';
    if (file_exists($secretPath) && is_readable($secretPath)) {
        $key = rtrim(file_get_contents($secretPath), "\r\n");
    }
    if (empty($key)) {
        $key = getenv('AUTH_SECRET') ?: '';
    }
    if (empty($key)) {
        throw new Exception('Encryption key not available');
    }
    // Derive a 32-byte key using SHA-256
    return hash('sha256', $key, true);
}

function encryptSecret(string $plaintext): string {
    $key = getEncryptionKey();
    $iv = random_bytes(12); // GCM recommends 96-bit IV
    $tag = '';
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
    if ($ciphertext === false) {
        throw new Exception('Encryption failed');
    }
    return base64_encode($iv . $tag . $ciphertext);
}

function decryptSecret(string $ciphertext): string {
    $key = getEncryptionKey();
    $data = base64_decode($ciphertext);
    if (strlen($data) < 28) {
        throw new Exception('Invalid ciphertext');
    }
    $iv = substr($data, 0, 12);
    $tag = substr($data, 12, 16);
    $encrypted = substr($data, 28);
    $plaintext = openssl_decrypt($encrypted, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($plaintext === false) {
        throw new Exception('Decryption failed');
    }
    return $plaintext;
}
