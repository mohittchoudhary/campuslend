<?php
/**
 * CampusLend Global Helper Functions
 * Escaping, CSRF protection, routing, session flash, authentication helpers
 */

use App\Config\Env;

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        static $cfg = null;
        if ($cfg === null) {
            $cfg = require __DIR__ . '/../config/config.php';
        }

        $parts = explode('.', $key);
        $curr = $cfg;
        foreach ($parts as $part) {
            if (!is_array($curr) || !array_key_exists($part, $curr)) {
                return $default;
            }
            $curr = $curr[$part];
        }

        return $curr;
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML output for XSS protection
     */
    function e(?string $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $path = '/' . ltrim($path, '/');
        if (!empty($_SERVER['HTTP_HOST'])) {
            $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            return $proto . '://' . $_SERVER['HTTP_HOST'] . ($path === '/' ? '' : $path);
        }
        $base = rtrim(config('app.url', 'http://localhost:8000'), '/');
        return $base . ($path === '/' ? '' : $path);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $base = rtrim(config('app.url', 'http://localhost:8000'), '/');
        $clean = ltrim($path, '/');
        // Add cache buster timestamp if file exists
        $filePath = __DIR__ . '/../../public/' . $clean;
        $ver = file_exists($filePath) ? '?v=' . filemtime($filePath) : '';
        return $base . '/' . $clean . $ver;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path, array $flash = []): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        foreach ($flash as $key => $message) {
            $_SESSION['flash'][$key] = $message;
        }

        $target = str_starts_with($path, 'http') ? $path : url($path);
        header("Location: $target");
        exit;
    }
}

if (!function_exists('flash')) {
    function flash(string $key, ?string $message = null): ?string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($message !== null) {
            $_SESSION['flash'][$key] = $message;
            return null;
        }

        if (isset($_SESSION['flash'][$key])) {
            $msg = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $msg;
        }

        return null;
    }
}

if (!function_exists('has_flash')) {
    function has_flash(string $key): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['flash'][$key]);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('auth_user')) {
    function auth_user(): ?array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user'] ?? null;
    }
}

if (!function_exists('auth_id')) {
    function auth_id(): ?int
    {
        $user = auth_user();
        return $user ? (int)$user['id'] : null;
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool
    {
        return auth_user() !== null;
    }
}

if (!function_exists('is_verified')) {
    function is_verified(): bool
    {
        $user = auth_user();
        return $user !== null && (int)($user['is_verified'] ?? 0) === 1;
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        $user = auth_user();
        return $user !== null && ($user['role'] ?? '') === 'admin';
    }
}

if (!function_exists('format_time_ago')) {
    function format_time_ago(string $datetime): string
    {
        $time = strtotime($datetime);
        $diff = time() - $time;

        if ($diff < 60) {
            return 'Just now';
        }
        if ($diff < 3600) {
            $m = (int)($diff / 60);
            return $m . ' min' . ($m > 1 ? 's' : '') . ' ago';
        }
        if ($diff < 86400) {
            $h = (int)($diff / 3600);
            return $h . ' hour' . ($h > 1 ? 's' : '') . ' ago';
        }
        $d = (int)($diff / 86400);
        return $d . ' day' . ($d > 1 ? 's' : '') . ' ago';
    }
}

if (!function_exists('format_time_remaining')) {
    function format_time_remaining(string $datetime): array
    {
        $target = strtotime($datetime);
        $now = time();
        $diff = $target - $now;

        if ($diff <= 0) {
            return ['expired' => true, 'text' => 'Expired', 'seconds' => 0];
        }

        $hours = floor($diff / 3600);
        $minutes = floor(($diff % 3600) / 60);
        $seconds = $diff % 60;

        if ($hours > 0) {
            $text = sprintf("%dh %dm left", $hours, $minutes);
        } else {
            $text = sprintf("%dm %ds left", $minutes, $seconds);
        }

        return ['expired' => false, 'text' => $text, 'seconds' => $diff];
    }
}
