<?php
/**
 * CampusLend OTP Code Model
 * 6-digit generation, 10-minute expiry, max 5 attempts enforcement
 */

namespace App\Models;

class OtpCode extends Model
{
    protected static string $table = 'otp_codes';

    public static function generate(int $userId, string $purpose = 'registration'): string
    {
        // Invalidate previous OTPs for this user & purpose
        $pdo = self::pdo();
        $stmt = $pdo->prepare("DELETE FROM otp_codes WHERE user_id = :uid AND purpose = :purpose");
        $stmt->execute([':uid' => $userId, ':purpose' => $purpose]);

        // Generate cryptographically secure 6-digit OTP
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', time() + (config('auth.otp_expiry_minutes', 10) * 60));

        self::insert([
            'user_id' => $userId,
            'code' => $code,
            'purpose' => $purpose,
            'attempts' => 0,
            'expires_at' => $expiresAt,
        ]);

        return $code;
    }

    public static function verify(int $userId, string $code, string $purpose = 'registration'): array
    {
        $code = trim($code);
        $sql = "SELECT * FROM otp_codes WHERE user_id = :uid AND purpose = :purpose ORDER BY id DESC LIMIT 1";
        $otp = self::fetch($sql, [':uid' => $userId, ':purpose' => $purpose]);

        if (!$otp) {
            return ['success' => false, 'error' => 'No active verification code found. Please request a new code.'];
        }

        $maxAttempts = config('auth.otp_max_attempts', 5);
        if ($otp['attempts'] >= $maxAttempts) {
            // Delete compromised OTP
            self::delete($otp['id']);
            return ['success' => false, 'error' => 'Too many failed attempts (maximum 5). Please request a new OTP code.'];
        }

        // Check expiration
        if (strtotime($otp['expires_at']) < time()) {
            self::delete($otp['id']);
            return ['success' => false, 'error' => 'Verification code has expired (10-minute limit). Please request a new code.'];
        }

        // Increment attempt count
        self::update($otp['id'], ['attempts' => $otp['attempts'] + 1]);

        if (!hash_equals($otp['code'], $code)) {
            $remaining = $maxAttempts - ($otp['attempts'] + 1);
            return [
                'success' => false, 
                'error' => "Invalid code. {$remaining} attempt" . ($remaining === 1 ? '' : 's') . " remaining."
            ];
        }

        // Code matches -> Remove used OTP
        self::delete($otp['id']);
        return ['success' => true, 'error' => null];
    }
}
