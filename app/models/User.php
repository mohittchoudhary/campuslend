<?php
/**
 * CampusLend User Model
 */

namespace App\Models;

class User extends Model
{
    protected static string $table = 'users';

    public static function findByEmail(string $email): ?array
    {
        return self::findBy('email', strtolower(trim($email)));
    }

    public static function findByEnrollment(string $enrollment): ?array
    {
        return self::findBy('enrollment_no', trim($enrollment));
    }

    public static function isCampusEmail(string $email): bool
    {
        $domain = strtolower(config('app.email_domain', 'utu.ac.in'));
        $email = strtolower(trim($email));
        return str_ends_with($email, '@' . $domain) || str_ends_with($email, '.' . $domain);
    }

    public static function register(array $data): int
    {
        $email = strtolower(trim($data['email']));
        if (!self::isCampusEmail($email)) {
            $domain = config('app.email_domain', 'utu.ac.in');
            throw new \InvalidArgumentException("Registration is restricted to {$domain} campus email addresses.");
        }

        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);

        return self::insert([
            'full_name' => trim($data['full_name']),
            'email' => $email,
            'password_hash' => $passwordHash,
            'enrollment_no' => trim($data['enrollment_no']),
            'department' => trim($data['department']),
            'year_of_study' => $data['year_of_study'] ?? '1st Year',
            'phone' => trim($data['phone']),
            'avatar' => $data['avatar'] ?? 'default_avatar.svg',
            'role' => 'student',
            'is_verified' => 0,
            'is_banned' => 0,
            'trust_score' => 5.00,
            'total_ratings' => 0,
        ]);
    }

    public static function markVerified(int $userId): bool
    {
        return self::update($userId, ['is_verified' => 1]);
    }

    public static function updatePassword(int $userId, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        return self::update($userId, ['password_hash' => $hash]);
    }

    public static function setRememberToken(int $userId, ?string $token): bool
    {
        return self::update($userId, ['remember_token' => $token]);
    }

    public static function findByRememberToken(string $token): ?array
    {
        return self::findBy('remember_token', $token);
    }

    public static function recalculateTrustScore(int $userId): float
    {
        $sql = "SELECT AVG(stars) as avg_stars, COUNT(*) as count FROM ratings WHERE reviewed_user_id = :uid";
        $stats = self::fetch($sql, [':uid' => $userId]);

        $avg = !empty($stats['avg_stars']) ? (float)$stats['avg_stars'] : 5.00;
        $count = (int)($stats['count'] ?? 0);

        self::update($userId, [
            'trust_score' => round($avg, 2),
            'total_ratings' => $count,
        ]);

        return round($avg, 2);
    }

    public static function banUser(int $userId): bool
    {
        return self::update($userId, ['is_banned' => 1]);
    }

    public static function unbanUser(int $userId): bool
    {
        return self::update($userId, ['is_banned' => 0]);
    }

    public static function getAllWithStats(): array
    {
        $sql = "
            SELECT u.*,
                   (SELECT COUNT(*) FROM requests r WHERE r.user_id = u.id) as total_requests,
                   (SELECT COUNT(*) FROM items i WHERE i.user_id = u.id) as total_items,
                   (SELECT COUNT(*) FROM borrow_transactions t WHERE t.lender_id = u.id AND t.status = 'returned') as successful_lends
            FROM users u
            ORDER BY u.id DESC
        ";
        return self::fetchAll($sql);
    }
}

