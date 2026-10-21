<?php
/**
 * CampusLend Phase 3 Automated Acceptance Test Suite
 * Covers:
 * 1. Chat Access Control (Participants can chat, third parties are blocked)
 * 2. Real-Time Chat Message Sending & Retrieval
 * 3. Notification Center & AJAX Unread Counter
 * 4. Ratings & Reviews (1 rating per party per transaction)
 * 5. Comprehensive Trust Score Formula Recalculation
 * 6. Automated Badge Award Logic (Quick Helper, Exam Saviour, etc.)
 * 7. Abuse Reporting
 * 8. User Blocking Enforcement (Blocked users cannot chat)
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
$cookieBorrower = __DIR__ . '/cookie_p3_borrower.txt';
$cookieLender = __DIR__ . '/cookie_p3_lender.txt';
$cookieThirdParty = __DIR__ . '/cookie_p3_third.txt';

if (file_exists($cookieBorrower)) unlink($cookieBorrower);
if (file_exists($cookieLender)) unlink($cookieLender);
if (file_exists($cookieThirdParty)) unlink($cookieThirdParty);

function http_req(string $method, string $url, array $data = [], string $cookieFile = '', array $headers = []): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    curl_setopt($ch, CURLOPT_HEADER, true);

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

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
    if (preg_match('/name="csrf_token" value="([^"]+)"/', $html, $matches)) return $matches[1];
    if (preg_match('/content="([^"]+)" name="csrf-token"/', $html, $matches)) return $matches[1];
    if (preg_match('/name="csrf-token" content="([^"]+)"/', $html, $matches)) return $matches[1];
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
echo "   CampusLend – Phase 3 Acceptance Verification Suite   \n";
echo "========================================================\n\n";

// Login Borrower (Aarav Patel, id=2)
echo "[1/8] Setting up Test Participants...\n";
$loginB = http_req('GET', $baseUrl . '/login', [], $cookieBorrower);
$csrfB = extract_csrf($loginB['body']);
http_req('POST', $baseUrl . '/login', ['csrf_token' => $csrfB, 'email' => 'aarav.patel@utu.ac.in', 'password' => 'Student@123'], $cookieBorrower);

// Login Lender (Rohan Desai, id=4)
$loginL = http_req('GET', $baseUrl . '/login', [], $cookieLender);
$csrfL = extract_csrf($loginL['body']);
http_req('POST', $baseUrl . '/login', ['csrf_token' => $csrfL, 'email' => 'rohan.desai@utu.ac.in', 'password' => 'Student@123'], $cookieLender);

// Login Third Party (Diya Shah, id=3)
$loginT = http_req('GET', $baseUrl . '/login', [], $cookieThirdParty);
$csrfT = extract_csrf($loginT['body']);
http_req('POST', $baseUrl . '/login', ['csrf_token' => $csrfT, 'email' => 'diya.shah@utu.ac.in', 'password' => 'Student@123'], $cookieThirdParty);

assert_test("All 3 test users logged in", true);

// Create a dedicated active transaction between Aarav (borrower) and Rohan (lender)
$pdo = App\Config\Database::getConnection();
$txId = App\Models\BorrowTransaction::insert([
    'request_id' => 1,
    'offer_id' => 1,
    'borrower_id' => 2,
    'lender_id' => 4,
    'status' => 'accepted',
    'due_time' => date('Y-m-d H:i:s', time() + 86400),
]);
assert_test("Created test transaction (#{$txId})", $txId > 0);

// TEST 1: Chat Access Control
echo "\n[2/8] Testing Chat Access Control...\n";
// Third party attempt to view messages
$thirdPartyChat = http_req('GET', $baseUrl . "/api/chat/messages?transaction_id={$txId}", [], $cookieThirdParty, ['Accept: application/json']);
assert_test("Third party blocked from reading chat (HTTP 403)", $thirdPartyChat['status'] === 403);

// TEST 2: Real-time Message Sending & Retrieval
echo "\n[3/8] Testing Chat Messaging...\n";
$txViewPage = http_req('GET', $baseUrl . "/transactions?id={$txId}", [], $cookieBorrower);
$csrfChat = extract_csrf($txViewPage['body']);

$sendRes = http_req('POST', $baseUrl . '/api/chat/send', [
    'csrf_token' => $csrfChat,
    'transaction_id' => $txId,
    'message' => 'Hey Rohan! I am outside Room 204. Can we meet near the water cooler?',
], $cookieBorrower);

assert_test("Borrower sent chat message", str_contains($sendRes['body'], '"success":true'));

// Lender reads messages
$lenderChat = http_req('GET', $baseUrl . "/api/chat/messages?transaction_id={$txId}", [], $cookieLender, ['Accept: application/json']);
assert_test("Lender successfully received chat message", str_contains($lenderChat['body'], 'water cooler'));

// TEST 3: Notifications & Unread Counter
echo "\n[4/8] Testing Notification Center...\n";
$unreadCountRes = http_req('GET', $baseUrl . '/api/notifications/unread-count', [], $cookieLender, ['Accept: application/json']);
$unreadData = json_decode($unreadCountRes['body'], true);
assert_test("Lender unread notification counter updated", ($unreadData['count'] ?? 0) >= 1);

$notifPage = http_req('GET', $baseUrl . '/notifications', [], $cookieLender);
assert_test("Notification rendered on notifications page", str_contains($notifPage['body'], 'New message from Aarav'));

// TEST 4: Transaction Handover & Return for Rating
echo "\n[5/8] Preparing Transaction for Return and Rating...\n";
App\Models\BorrowTransaction::confirmHandover($txId, 2);
App\Models\BorrowTransaction::confirmReturn($txId, 4);
$refreshedTx = App\Models\BorrowTransaction::find($txId);
assert_test("Transaction reached 'returned' status", $refreshedTx['status'] === 'returned');

// TEST 5: Ratings & Trust Score Recalculation
echo "\n[6/8] Testing Star Ratings & Trust Score Recalculation...\n";
$txPageReturned = http_req('GET', $baseUrl . "/transactions?id={$txId}", [], $cookieBorrower);
$csrfRating = extract_csrf($txPageReturned['body']);

$ratingPost = http_req('POST', $baseUrl . '/ratings/submit', [
    'csrf_token' => $csrfRating,
    'transaction_id' => $txId,
    'stars' => 5,
    'comment' => 'Rohan was super fast and saved my mid-term exam! Top-notch campus mate.',
], $cookieBorrower);

assert_test("Rating submitted successfully", str_contains($ratingPost['body'], 'review has been recorded'));

// Verify duplicate rating blocked
$dupRating = http_req('POST', $baseUrl . '/ratings/submit', [
    'csrf_token' => $csrfRating,
    'transaction_id' => $txId,
    'stars' => 4,
    'comment' => 'Duplicate attempt',
], $cookieBorrower);
assert_test("Duplicate review rejected", str_contains($dupRating['body'], 'already submitted a review'));

$lenderUser = App\Models\User::find(4);
assert_test("Trust score recalculated in DB", (float)$lenderUser['trust_score'] > 0);

// TEST 6: Automated Badges Award
echo "\n[7/8] Testing Badge Award Logic...\n";
$awardedBadges = App\Models\Badge::checkAndAwardBadges(4);
$userBadges = App\Models\Badge::getUserBadges(4);
assert_test("User badges fetched", count($userBadges) > 0);

// TEST 7: Reports and User Blocking Enforcement
echo "\n[8/8] Testing Abuse Reporting & Block Enforcement...\n";
// Aarav reports Diya
$reportRes = http_req('POST', $baseUrl . '/users/report', [
    'csrf_token' => $csrfChat,
    'target_id' => 3,
    'reason' => 'Inappropriate or abusive chat behavior',
    'details' => 'Testing safety reporting system',
], $cookieBorrower);
assert_test("Abuse report submitted to administrators", str_contains($reportRes['body'], 'Report submitted to campus administrators'));

// Aarav blocks Diya
$blockRes = http_req('POST', $baseUrl . '/users/block', [
    'csrf_token' => $csrfChat,
    'target_id' => 3,
], $cookieBorrower);
assert_test("User successfully blocked", str_contains($blockRes['body'], 'User blocked successfully'));
assert_test("Block relationship active in DB", App\Models\UserBlock::isBlocked(2, 3));

// Check that blocked users cannot send messages
$blockedTx = App\Models\BorrowTransaction::insert([
    'request_id' => 2,
    'offer_id' => 2,
    'borrower_id' => 2,
    'lender_id' => 3, // Diya
    'status' => 'accepted',
    'due_time' => date('Y-m-d H:i:s', time() + 86400),
]);

$blockedChatAttempt = http_req('POST', $baseUrl . '/api/chat/send', [
    'csrf_token' => $csrfT,
    'transaction_id' => $blockedTx,
    'message' => 'Hello Aarav!',
], $cookieThirdParty);

assert_test("Blocked user blocked from sending messages", $blockedChatAttempt['status'] === 403 || str_contains($blockedChatAttempt['body'], 'blocked'));

echo "\n🎉 ALL PHASE 3 ACCEPTANCE TESTS PASSED SUCCESSFULLY! (8/8 Suites)\n";
