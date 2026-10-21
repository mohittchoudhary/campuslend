<?php
/**
 * CampusLend Abuse & Policy Report Model
 */

namespace App\Models;

use InvalidArgumentException;

class Report extends Model
{
    protected static string $table = 'reports';

    public static function createReport(int $reporterId, int $reportedUserId, string $reason, ?string $details = null): int
    {
        if ($reporterId === $reportedUserId) {
            throw new InvalidArgumentException("You cannot report yourself.");
        }

        $reason = trim($reason);
        if (empty($reason)) {
            throw new InvalidArgumentException("Please select a reason for reporting.");
        }

        return self::insert([
            'reporter_id' => $reporterId,
            'reported_user_id' => $reportedUserId,
            'reason' => $reason,
            'details' => trim($details ?? ''),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function getAllWithDetails(): array
    {
        $sql = "
            SELECT r.*,
                   rep.full_name as reporter_name, rep.email as reporter_email,
                   target.full_name as target_name, target.email as target_email, target.is_banned as target_banned
            FROM reports r
            JOIN users rep ON r.reporter_id = rep.id
            JOIN users target ON r.reported_user_id = target.id
            ORDER BY r.id DESC
        ";
        return self::fetchAll($sql);
    }
}
