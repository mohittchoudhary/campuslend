<?php
/**
 * CampusLend Password Reset Model
 */

namespace App\Models;

class PasswordReset extends Model
{
    protected static string $table = 'password_resets';

    public static function createToken(int $userId): string
    {
        // Remove previous unused tokens for this user
        $pdo = self::pdo();
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = :uid");
        $stmt->execute([':uid' => $userId]);

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry

        self::insert([
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => $expiresAt,
            'used' => 0,
        ]);

        return $token;
    }

    public static function verifyToken(string $token): ?array
    {
        $sql = "SELECT * FROM password_resets WHERE token = :t AND used = 0 LIMIT 1";
        $reset = self::fetch($sql, [':t' => $token]);

        if (!$reset) {
            return null;
        }

        if (strtotime($reset['expires_at']) < time()) {
            return null;
        }

        return $reset;
    }

    public static function markUsed(string $token): bool
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = :t");
        return $stmt->execute([':t' => $token]);
    }
}
