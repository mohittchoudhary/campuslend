<?php
/**
 * CampusLend Offer Model ("I Can Lend This")
 */

namespace App\Models;

use App\Helpers\Mailer;

class Offer extends Model
{
    protected static string $table = 'offers';

    /**
     * Create an offer from a student to fulfill a request
     */
    public static function createOffer(int $requestId, int $lenderId, ?int $itemId = null, ?string $message = null): int
    {
        $request = BorrowRequest::find($requestId);
        if (!$request) {
            throw new \InvalidArgumentException("Emergency request not found.");
        }

        if (in_array($request['status'], ['fulfilled', 'cancelled', 'expired'])) {
            throw new \InvalidArgumentException("This request is already {$request['status']} and cannot receive offers.");
        }

        // Rule: A student cannot offer on their own request
        if ((int)$request['user_id'] === $lenderId) {
            throw new \InvalidArgumentException("You cannot offer help on your own emergency request.");
        }

        // Rule: If an item is attached, verify lender owns it
        if ($itemId !== null) {
            $item = Item::find($itemId);
            if (!$item || (int)$item['user_id'] !== $lenderId) {
                throw new \InvalidArgumentException("Selected item does not belong to you.");
            }
        }

        // Check if student already has a pending offer on this request
        $sql = "SELECT id FROM offers WHERE request_id = :rid AND lender_id = :lid AND status = 'pending' LIMIT 1";
        $existing = self::fetch($sql, [':rid' => $requestId, ':lid' => $lenderId]);
        if ($existing) {
            throw new \InvalidArgumentException("You have already submitted an active offer for this request.");
        }

        $offerId = self::insert([
            'request_id' => $requestId,
            'lender_id' => $lenderId,
            'item_id' => $itemId,
            'message' => trim($message ?? ''),
            'status' => 'pending',
        ]);

        // Update request status to 'offered' if it was 'open'
        if ($request['status'] === 'open') {
            BorrowRequest::update($requestId, ['status' => 'offered']);
        }

        // In-app Notification for the requester
        $lender = User::find($lenderId);
        $requester = User::find((int)$request['user_id']);

        if ($requester) {
            $title = "🤝 Help Offered: {$request['item_name']}";
            $msg = "{$lender['full_name']} just offered to lend you their item for your emergency request in {$request['campus_block']}!";
            
            self::pdo()->prepare("
                INSERT INTO notifications (user_id, title, message, type, link) 
                VALUES (:uid, :title, :msg, 'offer_received', :link)
            ")->execute([
                ':uid' => $requester['id'],
                ':title' => $title,
                ':msg' => $msg,
                ':link' => '/requests/view?id=' . $requestId,
            ]);

            // Email Notification
            Mailer::send(
                $requester['email'],
                $requester['full_name'],
                "Peer Offer Received for '{$request['item_name']}' - CampusLend",
                "<p>Great news! <strong>{$lender['full_name']}</strong> ({$lender['department']}) offered to lend you an item for your emergency request.</p>
                 <p><a href='" . url('/requests/view?id=' . $requestId) . "'>View Offer & Connect →</a></p>"
            );
        }

        return $offerId;
    }

    /**
     * Get offers for a specific request with lender profile
     */
    public static function getByRequest(int $requestId): array
    {
        $sql = "
            SELECT o.*, 
                   u.full_name as lender_name, u.department, u.year_of_study, u.phone as lender_phone,
                   u.trust_score, u.total_ratings, u.avatar,
                   i.title as item_title, i.item_condition, i.pickup_location, i.photo as item_photo
            FROM offers o
            JOIN users u ON o.lender_id = u.id
            LEFT JOIN items i ON o.item_id = i.id
            WHERE o.request_id = :rid
            ORDER BY o.id DESC
        ";
        return self::fetchAll($sql, [':rid' => $requestId]);
    }
}
