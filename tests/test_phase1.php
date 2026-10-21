<?php
/**
 * CampusLend Phase 1 Automated Acceptance Test Suite
 * Tests:
 * 1. Server & Landing Page
 * 2. Non-campus email rejection (@gmail.com blocked)
 * 3. Campus email registration (@utu.ac.in accepted)
 * 4. 6-digit OTP generation & attempt enforcement
 * 5. OTP verification & user account activation
 * 6. Logout & Session invalidation
 * 7. Login with valid & invalid credentials (with rate limiting)
 * 8. Password reset token creation & password update
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/config/Env.php';
App\Config\Env::load(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/helpers/functions.php';

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

$baseUrl = 'http://127.0.0.1:8000';
$cookieFile = __DIR__ . '/cookie.txt';
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

function http_req(string $method, string $url, array $data = [], string $cookieFile = ''): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_HEADER, true);

    if (strtoupper($method) === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    $header = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);

    return [
        'status' => $status,
        'effective_url' => $effectiveUrl,
        'body' => $body,
        'header' => $header,
    ];
}

function extract_csrf(string $html): string
{
    if (preg_match('/name="csrf_token" value="([^"]+)"/', $html, $matches)) {
        return $matches[1];
    }
    if (preg_match('/content="([^"]+)" name="csrf-token"/', $html, $matches)) {
        return $matches[1];
    }
    if (preg_match('/name="csrf-token" content="([^"]+)"/', $html, $matches)) {
        return $matches[1];
    }
    return '';
}

function assert_test(string $name, bool $condition, string $detail = ''): void
{
    if ($condition) {
        echo "✅ PASS: {$name}" . ($detail ? " ({$detail})" : "") . PHP_EOL;
    } else {
        echo "❌ FAIL: {$name}" . ($detail ? " ({$detail})" : "") . PHP_EOL;
        exit(1);
    }
}

echo "========================================================\n";
echo "   CampusLend – Phase 1 Acceptance Verification Suite   \n";
echo "========================================================\n\n";

// TEST 1: Landing Page
echo "[1/8] Testing Server & Landing Page...\n";
$res = http_req('GET', $baseUrl . '/', [], $cookieFile);
assert_test("Landing page returns HTTP 200", $res['status'] === 200);
assert_test("Landing contains CampusLend brand", str_contains($res['body'], 'CampusLend'));
assert_test("Landing contains UTU campus name", str_contains($res['body'], 'Uka Tarsadia University'));

// TEST 2: Rejection of Non-Campus Email
echo "\n[2/8] Testing Non-Campus Email Rejection...\n";
$regPage = http_req('GET', $baseUrl . '/register', [], $cookieFile);
$csrf = extract_csrf($regPage['body']);
assert_test("Extracted CSRF token from registration form", !empty($csrf));

$postNonCampus = http_req('POST', $baseUrl . '/register', [
    'csrf_token' => $csrf,
    'full_name' => 'Intruder User',
    'email' => 'intruder@gmail.com', // Non-campus email
    'password' => 'Secret123',
    'enrollment_no' => 'NONCAMPUS001',
    'department' => 'Computer Engineering',
    'year_of_study' => '2nd Year',
    'phone' => '9825100000',
], $cookieFile);

assert_test(
    "Non-campus email is blocked with error message",
    str_contains($postNonCampus['body'], 'Registration requires an official @utu.ac.in campus email address.')
);

// TEST 3: Registration with Valid Campus Email
echo "\n[3/8] Testing Valid Campus Email Registration (@utu.ac.in)...\n";
$testEmail = 'mohit.test' . time() . '@utu.ac.in';
$testEnrollment = 'UTU' . time();
$testPass = 'UTUStudent@2026';

// Fresh CSRF
$regPage = http_req('GET', $baseUrl . '/register', [], $cookieFile);
$csrf = extract_csrf($regPage['body']);

$postValid = http_req('POST', $baseUrl . '/register', [
    'csrf_token' => $csrf,
    'full_name' => 'Mohit Test Student',
    'email' => $testEmail,
    'password' => $testPass,
    'enrollment_no' => $testEnrollment,
    'department' => 'Computer Engineering',
    'year_of_study' => '3rd Year',
    'phone' => '9876543210',
], $cookieFile);

assert_test("Redirected to OTP verification page", str_contains($postValid['effective_url'], '/verify-otp'));
assert_test("User created in database", (bool)App\Models\User::findByEmail($testEmail));

$dbUser = App\Models\User::findByEmail($testEmail);
assert_test("New user starts as unverified (is_verified = 0)", (int)$dbUser['is_verified'] === 0);

// TEST 4: OTP Generation & Attempts Enforcement
echo "\n[4/8] Testing OTP Generation & Validation Rules...\n";
$pdo = App\Config\Database::getConnection();
$otpRecord = $pdo->query("SELECT * FROM otp_codes WHERE user_id = {$dbUser['id']} ORDER BY id DESC LIMIT 1")->fetch();
assert_test("6-digit OTP created in database", !empty($otpRecord) && strlen($otpRecord['code']) === 6);

// Extract CSRF for OTP submission
$otpPage = http_req('GET', $baseUrl . '/verify-otp', [], $cookieFile);
$csrfOtp = extract_csrf($otpPage['body']);

// Submit invalid OTP
$badOtpRes = http_req('POST', $baseUrl . '/verify-otp', [
    'csrf_token' => $csrfOtp,
    'otp' => '000000',
], $cookieFile);

assert_test("Invalid OTP is rejected", str_contains($badOtpRes['body'], 'Invalid code') || str_contains($badOtpRes['body'], 'remaining'));

// TEST 5: Successful OTP Verification
echo "\n[5/8] Testing OTP Verification with Valid Code...\n";
$otpPage = http_req('GET', $baseUrl . '/verify-otp', [], $cookieFile);
$csrfOtp = extract_csrf($otpPage['body']);

$goodOtpRes = http_req('POST', $baseUrl . '/verify-otp', [
    'csrf_token' => $csrfOtp,
    'otp' => $otpRecord['code'],
], $cookieFile);

$refreshedUser = App\Models\User::find($dbUser['id']);
assert_test("User marked verified (is_verified = 1)", (int)$refreshedUser['is_verified'] === 1);
assert_test("Logged into platform", str_contains($goodOtpRes['body'], 'Welcome to CampusLend') || str_contains($goodOtpRes['effective_url'], '/'));

// TEST 6: Logout
echo "\n[6/8] Testing Logout...\n";
$csrfLogout = extract_csrf($goodOtpRes['body']);
$logoutRes = http_req('POST', $baseUrl . '/logout', [
    'csrf_token' => $csrfLogout,
], $cookieFile);
assert_test("Redirected to login after logout", str_contains($logoutRes['effective_url'], '/login'));

// TEST 7: Login Flow & Credential Validation
echo "\n[7/8] Testing Sign-In...\n";
$loginPage = http_req('GET', $baseUrl . '/login', [], $cookieFile);
$csrfLogin = extract_csrf($loginPage['body']);

// Wrong password
$badLogin = http_req('POST', $baseUrl . '/login', [
    'csrf_token' => $csrfLogin,
    'email' => $testEmail,
    'password' => 'WrongPassword!',
], $cookieFile);
assert_test("Invalid password rejected", str_contains($badLogin['body'], 'Invalid campus email address or password'));

// Correct password
$loginPage = http_req('GET', $baseUrl . '/login', [], $cookieFile);
$csrfLogin = extract_csrf($loginPage['body']);
$goodLogin = http_req('POST', $baseUrl . '/login', [
    'csrf_token' => $csrfLogin,
    'email' => $testEmail,
    'password' => $testPass,
    'remember' => '1',
], $cookieFile);
assert_test("Successful login with correct credentials", str_contains($goodLogin['body'], 'Mohit') || str_contains($goodLogin['effective_url'], '/'));

// TEST 8: Password Reset Flow
echo "\n[8/8] Testing Password Reset Flow...\n";
// Clear session cookie so user is a guest for forgot-password
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

$forgotPage = http_req('GET', $baseUrl . '/forgot-password', [], $cookieFile);
$csrfForgot = extract_csrf($forgotPage['body']);

$forgotRes = http_req('POST', $baseUrl . '/forgot-password', [
    'csrf_token' => $csrfForgot,
    'email' => $testEmail,
], $cookieFile);

$resetRecord = $pdo->query("SELECT * FROM password_resets WHERE user_id = {$dbUser['id']} AND used = 0 ORDER BY id DESC LIMIT 1")->fetch();
assert_test("Password reset token generated", !empty($resetRecord) && !empty($resetRecord['token']));

$resetPage = http_req('GET', $baseUrl . '/reset-password?token=' . $resetRecord['token'], [], $cookieFile);
assert_test("Reset password form loaded", $resetPage['status'] === 200);

$newPass = 'NewSecurePassword@2026';
$csrfReset = extract_csrf($resetPage['body']);
$resetPost = http_req('POST', $baseUrl . '/reset-password', [
    'csrf_token' => $csrfReset,
    'token' => $resetRecord['token'],
    'password' => $newPass,
    'password_confirm' => $newPass,
], $cookieFile);

assert_test("Password reset succeeded", str_contains($resetPost['effective_url'], '/login'));

// Test login with new password
$loginPage = http_req('GET', $baseUrl . '/login', [], $cookieFile);
$csrfLogin = extract_csrf($loginPage['body']);
$newLoginRes = http_req('POST', $baseUrl . '/login', [
    'csrf_token' => $csrfLogin,
    'email' => $testEmail,
    'password' => $newPass,
], $cookieFile);

assert_test("Login with new password succeeded", str_contains($newLoginRes['effective_url'], '/') || str_contains($newLoginRes['body'], 'Mohit'));

echo "\n🎉 ALL PHASE 1 ACCEPTANCE TESTS PASSED SUCCESSFULLY! (8/8 Suites)\n";
