<?php
/**
 * CampusLend Notification Model
 */

namespace App\Models;

class Notification extends Model
{
    protected static string $table = 'notifications';

    public static function create(int $userId, string $title, string $message, string $type = 'system', ?string $link = null): int
    {
        return self::insert([
            'user_id' => $userId,
            'title' => trim($title),
            'message' => trim($message),
            'type' => $type,
            'link' => $link,
            'is_read' => 0,
        ]);
    }

    public static function getByUser(int $userId, int $limit = 30): array
    {
        $sql = "
            SELECT * FROM notifications 
            WHERE user_id = :uid 
            ORDER BY id DESC 
            LIMIT {$limit}
        ";
        return self::fetchAll($sql, [':uid' => $userId]);
    }

    public static function getUnreadCount(int $userId): int
    {
        $sql = "SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0";
        return (int)self::fetchColumn($sql, [':uid' => $userId]);
    }

    public static function markRead(int $id, int $userId): bool
    {
        $sql = "UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid";
        $stmt = self::pdo()->prepare($sql);
        return $stmt->execute([':id' => $id, ':uid' => $userId]);
    }

    public static function markAllRead(int $userId): bool
    {
        $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND is_read = 0";
        $stmt = self::pdo()->prepare($sql);
        return $stmt->execute([':uid' => $userId]);
    }
}
