<?php
/**
 * CampusLend Badge Model & Gamification Engine
 * Handles "Quick Helper", "Exam Saviour", "Reliable Lender", "Campus Hero"
 */

namespace App\Models;

class Badge extends Model
{
    protected static string $table = 'user_badges';

    public static function awardBadge(int $userId, string $badgeName): bool
    {
        $sql = "SELECT id FROM user_badges WHERE user_id = :uid AND badge_name = :bname LIMIT 1";
        if (self::fetch($sql, [':uid' => $userId, ':bname' => $badgeName])) {
            return false; // Already earned
        }

        self::insert([
            'user_id' => $userId,
            'badge_name' => $badgeName,
            'earned_at' => date('Y-m-d H:i:s'),
        ]);

        // Push celebratory in-app notification
        Notification::create(
            $userId,
            "🏆 Badge Earned: {$badgeName}!",
            "Congratulations! You unlocked the '{$badgeName}' badge for helping campus peers.",
            'badge_earned',
            '/profile'
        );

        return true;
    }

    public static function getUserBadges(int $userId): array
    {
        $sql = "SELECT * FROM user_badges WHERE user_id = :uid ORDER BY id ASC";
        return self::fetchAll($sql, [':uid' => $userId]);
    }

    /**
     * Evaluates metrics and awards badges dynamically
     */
    public static function checkAndAwardBadges(int $userId): array
    {
        $awarded = [];
        $pdo = self::pdo();

        // 1. Lending transaction stats
        $sqlLend = "
            SELECT COUNT(*) as lend_count, AVG(r.stars) as avg_rating 
            FROM borrow_transactions t
            LEFT JOIN ratings r ON t.id = r.transaction_id AND r.reviewed_user_id = t.lender_id
            WHERE t.lender_id = :uid AND t.status = 'returned'
        ";
        $stmtLend = $pdo->prepare($sqlLend);
        $stmtLend->execute([':uid' => $userId]);
        $lendStats = $stmtLend->fetch();

        $lendCount = (int)($lendStats['lend_count'] ?? 0);
        $avgRating = (float)($lendStats['avg_rating'] ?? 5.0);

        // Badge: Quick Helper (>= 3 loans)
        if ($lendCount >= 3) {
            if (self::awardBadge($userId, 'Quick Helper')) {
                $awarded[] = 'Quick Helper';
            }
        }

        // Badge: Reliable Lender (>= 5 loans and avg rating >= 4.5)
        if ($lendCount >= 5 && $avgRating >= 4.5) {
            if (self::awardBadge($userId, 'Reliable Lender')) {
                $awarded[] = 'Reliable Lender';
            }
        }

        // Badge: Exam Saviour (lent for Critical request or during exams)
        $sqlCritical = "
            SELECT COUNT(*) FROM borrow_transactions t 
            JOIN requests req ON t.request_id = req.id 
            WHERE t.lender_id = :uid AND t.status = 'returned' AND req.urgency = 'Critical'
        ";
        $critStmt = $pdo->prepare($sqlCritical);
        $critStmt->execute([':uid' => $userId]);
        $criticalCount = (int)$critStmt->fetchColumn();

        if ($criticalCount >= 1) {
            if (self::awardBadge($userId, 'Exam Saviour')) {
                $awarded[] = 'Exam Saviour';
            }
        }

        // Badge: Campus Hero (>= 10 loans and trust score >= 4.8)
        $user = User::find($userId);
        if ($lendCount >= 10 && (float)$user['trust_score'] >= 4.8) {
            if (self::awardBadge($userId, 'Campus Hero')) {
                $awarded[] = 'Campus Hero';
            }
        }

        return $awarded;
    }

    /**
     * Top Helpers Leaderboard
     */
    public static function getLeaderboard(int $limit = 20): array
    {
        $sql = "
            SELECT u.id, u.full_name, u.department, u.year_of_study, u.avatar, u.trust_score, u.total_ratings,
                   (SELECT COUNT(*) FROM borrow_transactions WHERE lender_id = u.id AND status = 'returned') as items_lent_count,
                   (SELECT COUNT(*) FROM user_badges WHERE user_id = u.id) as badge_count
            FROM users u
            WHERE u.is_verified = 1 AND u.is_banned = 0 AND u.role = 'student'
            ORDER BY items_lent_count DESC, u.trust_score DESC, u.total_ratings DESC
            LIMIT {$limit}
        ";

        return self::fetchAll($sql);
    }
}
