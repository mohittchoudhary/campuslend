<?php
/**
 * CampusLend - Front Controller & Router Dispatcher
 */

declare(strict_types=1);

// Configure secure session parameters before starting session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', '7200');
    session_start();
}

// 1. PSR-4 Style Autoloader for App namespace
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// 2. Load Environment Variables
use App\Config\Env;
use App\Config\Database;
use App\Helpers\Router;
use App\Models\User;
use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\RequestController;
use App\Controllers\ItemController;
use App\Controllers\TransactionController;
use App\Controllers\ChatController;
use App\Controllers\NotificationController;
use App\Controllers\RatingController;
use App\Controllers\LeaderboardController;
use App\Controllers\UserController;

Env::load(__DIR__ . '/../.env');

// 3. Load Global Helper Functions
require_once __DIR__ . '/../app/helpers/functions.php';

// 4. Remember Me Auto-Login Handler
if (!is_logged_in() && !empty($_COOKIE['campuslend_remember'])) {
    $token = trim($_COOKIE['campuslend_remember']);
    $user = User::findByRememberToken($token);
    if ($user && (int)$user['is_banned'] === 0) {
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
        $_SESSION['user_id'] = $user['id'];
    } else {
        // Clear invalid token
        setcookie('campuslend_remember', '', time() - 3600, '/');
    }
}

// 5. Define Application Routes
// Home & Informational Routes
Router::get('/', [HomeController::class, 'index']);
Router::get('/how-it-works', [HomeController::class, 'howItWorks']);
Router::get('/leaderboard', [LeaderboardController::class, 'index']);

// Authentication Routes
Router::get('/register', [AuthController::class, 'showRegister'], ['guest']);
Router::post('/register', [AuthController::class, 'handleRegister'], ['guest', 'csrf']);

Router::get('/verify-otp', [AuthController::class, 'showVerifyOtp']);
Router::post('/verify-otp', [AuthController::class, 'handleVerifyOtp'], ['csrf']);
Router::post('/resend-otp', [AuthController::class, 'resendOtp'], ['csrf']);

Router::get('/login', [AuthController::class, 'showLogin'], ['guest']);
Router::post('/login', [AuthController::class, 'handleLogin'], ['guest', 'csrf']);
Router::post('/logout', [AuthController::class, 'logout'], ['auth', 'csrf']);

Router::get('/forgot-password', [AuthController::class, 'showForgotPassword'], ['guest']);
Router::post('/forgot-password', [AuthController::class, 'handleForgotPassword'], ['guest', 'csrf']);

Router::get('/reset-password', [AuthController::class, 'showResetPassword'], ['guest']);
Router::post('/reset-password', [AuthController::class, 'handleResetPassword'], ['guest', 'csrf']);

// Phase 2: Live Feed & Emergency Requests
Router::get('/feed', [RequestController::class, 'feed']);
Router::get('/requests/create', [RequestController::class, 'showCreate'], ['auth', 'verified']);
Router::post('/requests/create', [RequestController::class, 'handleCreate'], ['auth', 'verified', 'csrf']);
Router::get('/requests/view', [RequestController::class, 'view']);
Router::post('/requests/offer', [RequestController::class, 'handleOffer'], ['auth', 'verified', 'csrf']);
Router::post('/requests/cancel', [RequestController::class, 'cancel'], ['auth', 'verified', 'csrf']);

// Phase 2: Lend Item Listings & Catalog
Router::get('/items', [ItemController::class, 'index']);
Router::get('/items/create', [ItemController::class, 'showCreate'], ['auth', 'verified']);
Router::post('/items/create', [ItemController::class, 'handleCreate'], ['auth', 'verified', 'csrf']);
Router::get('/items/view', [ItemController::class, 'view']);
Router::post('/items/toggle-pause', [ItemController::class, 'togglePause'], ['auth', 'verified', 'csrf']);

// Phase 2: Transactions & State Machine
Router::post('/transactions/accept', [TransactionController::class, 'acceptOffer'], ['auth', 'verified', 'csrf']);
Router::get('/transactions', [TransactionController::class, 'view'], ['auth']);
Router::post('/transactions/handover', [TransactionController::class, 'confirmHandover'], ['auth', 'verified', 'csrf']);
Router::post('/transactions/return', [TransactionController::class, 'confirmReturn'], ['auth', 'verified', 'csrf']);
Router::get('/my-activity', [TransactionController::class, 'myActivity'], ['auth']);

// Phase 3: Real-Time Chat Endpoints
Router::get('/api/chat/messages', [ChatController::class, 'getMessages'], ['auth']);
Router::post('/api/chat/send', [ChatController::class, 'sendMessage'], ['auth']);

// Phase 3: Notification Center & AJAX Count
Router::get('/notifications', [NotificationController::class, 'index'], ['auth']);
Router::get('/api/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
Router::post('/notifications/read', [NotificationController::class, 'markAsRead'], ['auth', 'csrf']);
Router::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'], ['auth', 'csrf']);

// Phase 3: Ratings & Trust Score
Router::post('/ratings/submit', [RatingController::class, 'submit'], ['auth', 'csrf']);

// Phase 3: Profile & Safety Settings
Router::get('/profile', [UserController::class, 'profile'], ['auth']);
Router::post('/profile/update', [UserController::class, 'updateProfile'], ['auth', 'csrf']);
Router::post('/profile/avatar', [UserController::class, 'updateAvatar'], ['auth', 'csrf']);
Router::post('/profile/password', [UserController::class, 'changePassword'], ['auth', 'csrf']);
Router::post('/users/block', [UserController::class, 'blockUser'], ['auth', 'csrf']);
Router::post('/users/unblock', [UserController::class, 'unblockUser'], ['auth', 'csrf']);
Router::post('/users/report', [UserController::class, 'reportUser'], ['auth', 'csrf']);

// Phase 5: Administrator Panel & Moderation
Router::get('/admin', function() { redirect('/admin/dashboard'); }, ['admin']);
Router::get('/admin/dashboard', [\App\Controllers\AdminController::class, 'dashboard'], ['admin']);
Router::get('/admin/users', [\App\Controllers\AdminController::class, 'users'], ['admin']);
Router::post('/admin/users/ban', [\App\Controllers\AdminController::class, 'banUser'], ['admin', 'csrf']);
Router::post('/admin/users/unban', [\App\Controllers\AdminController::class, 'unbanUser'], ['admin', 'csrf']);

Router::get('/admin/categories', [\App\Controllers\AdminController::class, 'categories'], ['admin']);
Router::post('/admin/categories/create', [\App\Controllers\AdminController::class, 'createCategory'], ['admin', 'csrf']);
Router::post('/admin/categories/update', [\App\Controllers\AdminController::class, 'updateCategory'], ['admin', 'csrf']);
Router::post('/admin/categories/delete', [\App\Controllers\AdminController::class, 'deleteCategory'], ['admin', 'csrf']);

Router::get('/admin/reports', [\App\Controllers\AdminController::class, 'reports'], ['admin']);
Router::post('/admin/reports/update', [\App\Controllers\AdminController::class, 'updateReport'], ['admin', 'csrf']);

Router::post('/admin/settings/exam-mode', [\App\Controllers\AdminController::class, 'toggleExamMode'], ['admin', 'csrf']);
Router::get('/admin/logs', [\App\Controllers\AdminController::class, 'logs'], ['admin']);

// Dashboard redirect
Router::get('/dashboard', function() {
    redirect(is_admin() ? '/admin/dashboard' : '/my-activity');
}, ['auth']);

// 6. Dispatch Request
try {
    Router::dispatch();
} catch (\Throwable $e) {
    if (config('app.debug', true)) {
        http_response_code(500);
        echo "<div style='font-family: monospace; padding: 30px; background: #fff1f2; color: #9f1239; border: 2px solid #f43f5e; margin: 30px; border-radius: 8px;'>";
        echo "<h2>⚠️ Application Exception</h2>";
        echo "<p><strong>Message:</strong> " . e($e->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . e($e->getFile()) . " on line " . $e->getLine() . "</p>";
        echo "<pre style='background: #ffe4e6; padding: 15px; border-radius: 4px; overflow-x: auto;'>" . e($e->getTraceAsString()) . "</pre>";
        echo "</div>";
    } else {
        http_response_code(500);
        echo "<h1>500 Internal Server Error</h1><p>A campus server error occurred. Please try again shortly.</p>";
    }
}
