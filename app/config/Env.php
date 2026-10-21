<?php
/**
 * CampusLend - Lightweight .env Configuration Loader
 */

namespace App\Config;

class Env
{
    private static bool $loaded = false;

    public static function load(string $filePath): void
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $val] = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val);

                // Strip outer quotes if present
                if (
                    (str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                    (str_starts_with($val, "'") && str_ends_with($val, "'"))
                ) {
                    $val = substr($val, 1, -1);
                }

                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
                putenv("$key=$val");
            }
        }

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $val = $_ENV[$key] ?? getenv($key) ?? $_SERVER[$key] ?? $default;

        if ($val === null) {
            return $default;
        }

        return match (strtolower((string)$val)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)', 'empty' => null,
            default => $val,
        };
    }
}
