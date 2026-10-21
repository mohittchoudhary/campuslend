<?php
/**
 * CampusLend - Master Automated Test Runner
 * Executes all Phase verification suites:
 * - Phase 1: Foundation, DB, Auth & OTP Verification
 * - Phase 2: Live Feed, Requests, Listings, Offers & State Machine
 * - Phase 3: Per-Transaction Chat, Notifications, Ratings, Trust Score & Blocks
 * - Phase 5: Admin RBAC, Moderation, Python Smart Match & Resilient Fallback
 */

declare(strict_types=1);

$phpBinary = PHP_BINARY;
if (empty($phpBinary) || !file_exists($phpBinary)) {
    $phpBinary = "C:\\Users\\Mohit\\AppData\\Local\\Microsoft\\WinGet\\Packages\\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\\php.exe";
}

$suites = [
    [
        'name' => 'Phase 1: Foundation, DB & Auth Suite',
        'file' => __DIR__ . '/test_phase1.php',
        'description' => 'Campus email validation (@utu.ac.in), 6-digit OTP, Password hashing, Session security',
    ],
    [
        'name' => 'Phase 2: Requests, Offers & State Machine',
        'file' => __DIR__ . '/test_phase2.php',
        'description' => 'Emergency SOS requests, photo upload MIME validation, state machine transition barriers',
    ],
    [
        'name' => 'Phase 3: Chat, Notifications & Trust Score',
        'file' => __DIR__ . '/test_phase3.php',
        'description' => 'Per-tx AJAX chat access control, star ratings, automated badges, abuse reporting & block enforcement',
    ],
    [
        'name' => 'Phase 5: Admin Panel & Python Smart Match',
        'file' => __DIR__ . '/test_phase5.php',
        'description' => 'RBAC 403 Forbidden enforcement, category CRUD, student moderation, Python microservice & fallback',
    ],
];

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════════════════╗\n";
echo "║          CAMPUSLEND – MASTER END-TO-END VERIFICATION SUITE                  ║\n";
echo "║          Uka Tarsadia University (@utu.ac.in) Peer Platform                  ║\n";
echo "╚══════════════════════════════════════════════════════════════════════════════╝\n\n";

$allPassed = true;
$results = [];
$totalStart = microtime(true);

foreach ($suites as $index => $suite) {
    $suiteNum = $index + 1;
    echo "──────────────────────────────────────────────────────────────────────────────\n";
    echo "▶ RUNNING SUITE [{$suiteNum}/" . count($suites) . "]: {$suite['name']}\n";
    echo "  Summary: {$suite['description']}\n";
    echo "──────────────────────────────────────────────────────────────────────────────\n";

    $startTime = microtime(true);
    
    // Execute suite command
    $cmd = escapeshellcmd($phpBinary) . ' ' . escapeshellarg($suite['file']) . ' 2>&1';
    $output = [];
    $returnCode = 0;
    exec($cmd, $output, $returnCode);
    $duration = round(microtime(true) - $startTime, 2);

    // Echo output cleanly
    echo implode("\n", $output) . "\n";

    $passed = ($returnCode === 0);
    if (!$passed) {
        $allPassed = false;
    }

    $results[] = [
        'name' => $suite['name'],
        'passed' => $passed,
        'duration' => $duration,
        'code' => $returnCode,
    ];
}

$totalDuration = round(microtime(true) - $totalStart, 2);

echo "\n";
echo "==============================================================================\n";
echo "                       FINAL ACCEPTANCE TEST REPORT                          \n";
echo "==============================================================================\n";
printf("%-45s | %-10s | %-10s\n", "Test Suite", "Result", "Duration");
echo "------------------------------------------------------------------------------\n";

foreach ($results as $res) {
    $statusStr = $res['passed'] ? "✅ PASS" : "❌ FAIL";
    printf("%-45s | %-10s | %-10s\n", $res['name'], $statusStr, "{$res['duration']}s");
}

echo "==============================================================================\n";
echo "Total Execution Time: {$totalDuration} seconds\n";

if ($allPassed) {
    echo "\n🏆 ALL PHASE SUITES PASSED WITH 100% SUCCESS RATE!\n";
    echo "   The CampusLend emergency borrowing system is fully verified and production-ready.\n\n";
    exit(0);
} else {
    echo "\n⚠️ ONE OR MORE SUITES FAILED. Please review the output above.\n\n";
    exit(1);
}
