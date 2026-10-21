<?php
/**
 * CampusLend User Block Model
 * Prevents abusive users from chatting, offering help, or interacting
 */

namespace App\Models;

use InvalidArgumentException;

class UserBlock extends Model
{
    protected static string $table = 'user_blocks';

    public static function block(int $blockerId, int $blockedId): bool
    {
        if ($blockerId === $blockedId) {
            throw new InvalidArgumentException("You cannot block yourself.");
        }

        $sql = "SELECT id FROM user_blocks WHERE blocker_id = :b1 AND blocked_id = :b2 LIMIT 1";
        if (self::fetch($sql, [':b1' => $blockerId, ':b2' => $blockedId])) {
            return true; // Already blocked
        }

        self::insert([
            'blocker_id' => $blockerId,
            'blocked_id' => $blockedId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    public static function unblock(int $blockerId, int $blockedId): bool
    {
        $sql = "DELETE FROM user_blocks WHERE blocker_id = :b1 AND blocked_id = :b2";
        $stmt = self::pdo()->prepare($sql);
        return $stmt->execute([':b1' => $blockerId, ':b2' => $blockedId]);
    }

    public static function isBlocked(int $userA, int $userB): bool
    {
        $sql = "SELECT id FROM user_blocks WHERE (blocker_id = :a1 AND blocked_id = :b1) OR (blocker_id = :b2 AND blocked_id = :a2) LIMIT 1";
        return self::fetch($sql, [
            ':a1' => $userA, ':b1' => $userB,
            ':b2' => $userB, ':a2' => $userA,
        ]) !== null;
    }

    public static function getBlockedUsers(int $userId): array
    {
        $sql = "
            SELECT b.*, u.full_name as blocked_name, u.department, u.email as blocked_email
            FROM user_blocks b
            JOIN users u ON b.blocked_id = u.id
            WHERE b.blocker_id = :uid
            ORDER BY b.id DESC
        ";
        return self::fetchAll($sql, [':uid' => $userId]);
    }
}
