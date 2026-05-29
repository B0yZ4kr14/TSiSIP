<?php
/**
 * Unit tests for TOTP library (Feature 037)
 * Run: php tests/unit/test_totp.php
 */

require_once __DIR__ . '/../../web/lib/totp.php';

$passed = 0;
$failed = 0;

function test(string $name, bool $condition) {
    global $passed, $failed;
    if ($condition) {
        echo "✅ PASS: $name\n";
        $passed++;
    } else {
        echo "❌ FAIL: $name\n";
        $failed++;
    }
}

// Test 1: Base32 roundtrip
$secret = generateTotpSecret(32);
test('generateTotpSecret returns 32 chars', strlen($secret) === 32);
test('generateTotpSecret uses only Base32 chars', preg_match('/^[A-Z2-7]+$/', $secret) === 1);

$decoded = base32Decode($secret);
$encoded = base32Encode($decoded);
test('Base32 roundtrip', $encoded === $secret);

// Test 2: TOTP code generation
$knownSecret = 'JBSWY3DPEHPK3PXP';
$code = generateTotpCode($knownSecret, 30, 0);
test('Known secret produces 6-digit code', strlen($code) === 6 && ctype_digit($code));

// Test 3: Self-verification
$secret2 = generateTotpSecret();
$code2 = generateTotpCode($secret2);
test('Self-verification succeeds', verifyTotpCode($secret2, $code2));

// Test 4: Wrong code fails
test('Wrong code fails verification', !verifyTotpCode($secret2, '000000'));

// Test 5: Window tolerance
$codeAtT = generateTotpCode($secret2, 30, time());
test('Same window code verifies', verifyTotpCode($secret2, $codeAtT, 1));

// Test 6: Backup codes generation
$codes = generateBackupCodes(10);
test('Generate 10 backup codes', count($codes) === 10);
test('Backup codes are 12 chars', strlen($codes[0]) === 12);
test('Backup codes are unique', count(array_unique($codes)) === 10);

// Test 7: otpauth URI
$uri = buildOtpAuthUri('test@tsiapp.io', $knownSecret);
test('otpauth URI contains secret', strpos($uri, $knownSecret) !== false);
test('otpauth URI contains issuer', strpos($uri, 'TSiSIP') !== false);

echo "\n=== Results: $passed passed, $failed failed ===\n";
exit($failed > 0 ? 1 : 0);
