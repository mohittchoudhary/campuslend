<?php
/**
 * CampusLend Admin Log Model
 * Tracks admin actions: ban/unban, category changes, report resolution, banner updates.
 */

namespace App\Models;

class AdminLog extends Model
{
    protected static string $table = 'admin_logs';

    public static function log(int $adminId, string $action, ?string $targetType = null, ?int $targetId = null, ?string $details = null): int
    {
        return self::insert([
            'admin_id' => $adminId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'details' => $details,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function getRecent(int $limit = 50): array
    {
        $sql = "
            SELECT l.*, u.full_name as admin_name, u.email as admin_email
            FROM admin_logs l
            JOIN users u ON l.admin_id = u.id
            ORDER BY l.id DESC
            LIMIT ?
        ";
        return self::fetchAll($sql, [$limit]);
    }
}
