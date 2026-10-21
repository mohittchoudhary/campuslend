<?php
/**
 * CampusLend Rate Limiter
 * Protects against brute-force attacks on login, OTP, and password reset
 */

namespace App\Helpers;

class RateLimiter
{
    private static function getStorageKey(string $key): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        return 'rate_limit_' . md5($ip . '_' . $key);
    }

    public static function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $storageKey = self::getStorageKey($key);
        if (!isset($_SESSION[$storageKey])) {
            return false;
        }

        $data = $_SESSION[$storageKey];
        if (time() > $data['expires_at']) {
            unset($_SESSION[$storageKey]);
            return false;
        }

        return $data['attempts'] >= $maxAttempts;
    }

    public static function hit(string $key, int $decaySeconds = 300): int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $storageKey = self::getStorageKey($key);
        $now = time();

        if (!isset($_SESSION[$storageKey]) || $now > $_SESSION[$storageKey]['expires_at']) {
            $_SESSION[$storageKey] = [
                'attempts' => 1,
                'expires_at' => $now + $decaySeconds,
            ];
            return 1;
        }

        $_SESSION[$storageKey]['attempts']++;
        return $_SESSION[$storageKey]['attempts'];
    }

    public static function availableIn(string $key): int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $storageKey = self::getStorageKey($key);
        if (!isset($_SESSION[$storageKey])) {
            return 0;
        }

        $diff = $_SESSION[$storageKey]['expires_at'] - time();
        return max(0, $diff);
    }

    public static function clear(string $key): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $storageKey = self::getStorageKey($key);
        unset($_SESSION[$storageKey]);
    }
}
