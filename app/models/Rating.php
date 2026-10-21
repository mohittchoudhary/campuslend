<?php
/**
 * CampusLend Rating & Trust Score Calculation Model
 */

namespace App\Models;

use App\Helpers\Mailer;
use Exception;
use InvalidArgumentException;

class Rating extends Model
{
    protected static string $table = 'ratings';

    /**
     * Submit rating and review after item return
     */
    public static function submitRating(int $transactionId, int $reviewerId, int $stars, ?string $comment = null): int
    {
        if ($stars < 1 || $stars > 5) {
            throw new InvalidArgumentException("Rating must be between 1 and 5 stars.");
        }

        $tx = BorrowTransaction::find($transactionId);
        if (!$tx) {
            throw new InvalidArgumentException("Transaction not found.");
        }

        if ($tx['status'] !== 'returned') {
            throw new Exception("Ratings can only be submitted after the item has been returned.");
        }

        $isBorrower = (int)$tx['borrower_id'] === $reviewerId;
        $isLender = (int)$tx['lender_id'] === $reviewerId;

        if (!$isBorrower && !$isLender) {
            throw new Exception("Unauthorized: You are not a party to this transaction.");
        }

        $reviewedUserId = $isBorrower ? (int)$tx['lender_id'] : (int)$tx['borrower_id'];
        $roleInTx = $isBorrower ? 'borrower' : 'lender';

        // Check duplicate rating: Only 1 rating per person per transaction
        $sql = "SELECT id FROM ratings WHERE transaction_id = :tx_id AND reviewer_id = :rev_id LIMIT 1";
        if (self::fetch($sql, [':tx_id' => $transactionId, ':rev_id' => $reviewerId])) {
            throw new Exception("You have already submitted a review for this transaction.");
        }

        $ratingId = self::insert([
            'transaction_id' => $transactionId,
            'reviewer_id' => $reviewerId,
            'reviewed_user_id' => $reviewedUserId,
            'role_in_tx' => $roleInTx,
            'stars' => $stars,
            'comment' => trim($comment ?? ''),
        ]);

        // Recalculate comprehensive Trust Score for the reviewed student
        self::recalculateComprehensiveTrustScore($reviewedUserId);

        // Check if new badges were earned
        Badge::checkAndAwardBadges($reviewedUserId);

        // Notify reviewed student
        $reviewer = User::find($reviewerId);
        $starIcons = str_repeat('⭐', $stars);
        Notification::create(
            $reviewedUserId,
            "{$starIcons} New {$stars}-Star Review Received!",
            "{$reviewer['full_name']} rated you {$stars}/5 stars for transaction #{$transactionId}.",
            'rating_received',
            '/profile'
        );

        $reviewedUser = User::find($reviewedUserId);
        if ($reviewedUser) {
            Mailer::send(
                $reviewedUser['email'],
                $reviewedUser['full_name'],
                "New Rating Received: {$stars} Stars - CampusLend",
                "<p><strong>{$reviewer['full_name']}</strong> submitted a rating for your recent item exchange:</p>
                 <p style='font-size: 20px;'>{$starIcons} ({$stars}/5)</p>
                 <blockquote style='border-left: 3px solid #6366f1; padding-left: 12px; color: #4b5563;'>" . e($comment) . "</blockquote>
                 <p><a href='" . url('/profile') . "'>View Your Updated Trust Score →</a></p>"
            );
        }

        return $ratingId;
    }

    /**
     * Comprehensive Trust Score Formula:
     * - 70% Average Star Rating
     * - 20% On-Time Return Ratio
     * - 10% Transaction Volume Bonus (+0.1 per 5 transactions up to +0.5)
     */
    public static function recalculateComprehensiveTrustScore(int $userId): float
    {
        $pdo = self::pdo();

        // 1. Average Rating
        $stmtRatings = $pdo->prepare("
            SELECT AVG(stars) as avg_stars, COUNT(*) as count 
            FROM ratings 
            WHERE reviewed_user_id = :uid
        ");
        $stmtRatings->execute([':uid' => $userId]);
        $rStats = $stmtRatings->fetch();

        $count = (int)($rStats['count'] ?? 0);
        $avgRating = $count > 0 ? (float)$rStats['avg_stars'] : 5.00;

        // 2. On-Time Return Rate (when user was borrower)
        $stmtOnTime = $pdo->prepare("
            SELECT 
                COUNT(*) as total_borrowed,
                SUM(CASE WHEN returned_time <= due_time THEN 1 ELSE 0 END) as on_time_count
            FROM borrow_transactions 
            WHERE borrower_id = :uid AND status = 'returned'
        ");
        $stmtOnTime->execute([':uid' => $userId]);
        $onTimeStats = $stmtOnTime->fetch();

        $totalBorrowed = (int)($onTimeStats['total_borrowed'] ?? 0);
        $onTimeCount = (int)($onTimeStats['on_time_count'] ?? 0);
        $onTimeRate = $totalBorrowed > 0 ? ($onTimeCount / $totalBorrowed) : 1.0;

        // 3. Completed Volume Factor
        $stmtCompleted = $pdo->prepare("
            SELECT COUNT(*) FROM borrow_transactions 
            WHERE (borrower_id = :uid1 OR lender_id = :uid2) AND status = 'returned'
        ");
        $stmtCompleted->execute([':uid1' => $userId, ':uid2' => $userId]);
        $totalCompleted = (int)$stmtCompleted->fetchColumn();
        $volumeBonus = min(0.5, floor($totalCompleted / 5) * 0.1);

        // Weighted calculation:
        // Score = (avgRating * 0.70) + ((onTimeRate * 5.0) * 0.20) + (volumeBonus * 0.10)
        $score = ($avgRating * 0.70) + (($onTimeRate * 5.0) * 0.20) + ($volumeBonus * 0.10);
        $score = max(1.00, min(5.00, round($score, 2)));

        User::update($userId, [
            'trust_score' => $score,
            'total_ratings' => $count,
        ]);

        return $score;
    }

    public static function getReviewsForUser(int $userId): array
    {
        $sql = "
            SELECT r.*, u.full_name as reviewer_name, u.avatar as reviewer_avatar,
                   t.request_id, req.item_name
            FROM ratings r
            JOIN users u ON r.reviewer_id = u.id
            JOIN borrow_transactions t ON r.transaction_id = t.id
            JOIN requests req ON t.request_id = req.id
            WHERE r.reviewed_user_id = :uid
            ORDER BY r.id DESC
        ";
        return self::fetchAll($sql, [':uid' => $userId]);
    }
}
