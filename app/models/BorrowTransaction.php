<?php
/**
 * CampusLend Borrow Transaction & State Machine Model
 * Manages 4-step transaction flow:
 * Requested -> Offered -> Accepted -> Handed Over -> Returned (plus overdue / cancelled)
 */

namespace App\Models;

use App\Helpers\Mailer;
use Exception;
use InvalidArgumentException;

class BorrowTransaction extends Model
{
    protected static string $table = 'borrow_transactions';

    /**
     * Accept an offer, creating a formal borrow transaction
     */
    public static function createFromOffer(int $offerId, int $requesterId): int
    {
        $offer = Offer::find($offerId);
        if (!$offer) {
            throw new InvalidArgumentException("Offer not found.");
        }

        $request = BorrowRequest::find((int)$offer['request_id']);
        if (!$request) {
            throw new InvalidArgumentException("Associated request not found.");
        }

        // Only the requester can accept an offer
        if ((int)$request['user_id'] !== $requesterId) {
            throw new InvalidArgumentException("Unauthorized: Only the creator of this request can accept offers.");
        }

        // Rule: A student cannot borrow their own item or borrow from themselves
        if ((int)$offer['lender_id'] === $requesterId) {
            throw new InvalidArgumentException("You cannot borrow an item from yourself.");
        }

        // Check request status
        if ($request['status'] === 'fulfilled' || $request['status'] === 'cancelled') {
            throw new InvalidArgumentException("This request has already been {$request['status']}.");
        }

        // Determine due time
        $durationHours = 24;
        if (!empty($offer['item_id'])) {
            $item = Item::find((int)$offer['item_id']);
            if ($item && !empty($item['max_duration_hours'])) {
                $durationHours = (int)$item['max_duration_hours'];
            }
        }
        $dueTime = date('Y-m-d H:i:s', time() + ($durationHours * 3600));

        // Create transaction with initial state 'accepted'
        $txId = self::insert([
            'request_id' => $request['id'],
            'offer_id' => $offer['id'],
            'borrower_id' => $requesterId,
            'lender_id' => (int)$offer['lender_id'],
            'item_id' => $offer['item_id'],
            'status' => 'accepted',
            'handover_confirmed_borrower' => 0,
            'handover_confirmed_lender' => 0,
            'return_confirmed_borrower' => 0,
            'return_confirmed_lender' => 0,
            'due_time' => $dueTime,
        ]);

        // Mark selected offer as accepted and others as declined
        Offer::update($offerId, ['status' => 'accepted']);
        self::pdo()->prepare("
            UPDATE offers SET status = 'declined' 
            WHERE request_id = :rid AND id != :oid AND status = 'pending'
        ")->execute([':rid' => $request['id'], ':oid' => $offerId]);

        // Update request status to 'matched'
        BorrowRequest::update($request['id'], ['status' => 'matched']);

        // Notifications & Email for lender
        $borrower = User::find($requesterId);
        $lender = User::find((int)$offer['lender_id']);

        if ($lender) {
            self::pdo()->prepare("
                INSERT INTO notifications (user_id, title, message, type, link) 
                VALUES (:uid, :title, :msg, 'offer_accepted', :link)
            ")->execute([
                ':uid' => $lender['id'],
                ':title' => '🎉 Offer Accepted!',
                ':msg' => "{$borrower['full_name']} accepted your offer for '{$request['item_name']}'. Meet up to complete handover.",
                ':link' => '/transactions?id=' . $txId,
            ]);

            Mailer::send(
                $lender['email'],
                $lender['full_name'],
                "Your Offer was Accepted! Coordinate Handover - CampusLend",
                "<p><strong>{$borrower['full_name']}</strong> accepted your offer for <strong>{$request['item_name']}</strong>!</p>
                 <p>Contact: {$borrower['phone']} ({$borrower['department']})</p>
                 <p><a href='" . url('/transactions?id=' . $txId) . "'>View Transaction & Confirm Handover →</a></p>"
            );
        }

        return $txId;
    }

    /**
     * Confirm physical handover of item (moves state from 'accepted' to 'handed_over')
     */
    public static function confirmHandover(int $txId, int $userId): array
    {
        $tx = self::find($txId);
        if (!$tx) {
            throw new InvalidArgumentException("Transaction not found.");
        }

        // User must be party to this transaction
        $isBorrower = (int)$tx['borrower_id'] === $userId;
        $isLender = (int)$tx['lender_id'] === $userId;
        if (!$isBorrower && !$isLender) {
            throw new InvalidArgumentException("Unauthorized: You are not part of this transaction.");
        }

        // STRICT STATE MACHINE VALIDATION:
        // Handover can ONLY be confirmed when status is 'accepted'
        if ($tx['status'] === 'handed_over') {
            return ['status' => 'handed_over', 'message' => 'Handover is already active.'];
        }

        if ($tx['status'] === 'returned') {
            throw new Exception("Invalid state jump: Item is already marked as returned.");
        }

        if ($tx['status'] === 'cancelled') {
            throw new Exception("Invalid state jump: This transaction has been cancelled.");
        }

        if ($tx['status'] !== 'accepted') {
            throw new Exception("Invalid state transition: Handover cannot be confirmed from status '{$tx['status']}'.");
        }

        // Mark confirmation from the acting party
        $updateData = [];
        if ($isBorrower) {
            $updateData['handover_confirmed_borrower'] = 1;
        }
        if ($isLender) {
            $updateData['handover_confirmed_lender'] = 1;
        }

        // Once confirmed by either party (or both), activate 'handed_over'
        $updateData['status'] = 'handed_over';
        $updateData['handover_time'] = date('Y-m-d H:i:s');

        self::update($txId, $updateData);

        // If an item was linked, mark it unavailable during the loan period
        if (!empty($tx['item_id'])) {
            Item::update((int)$tx['item_id'], ['is_available' => 0]);
        }

        // Notify the other party
        $otherUserId = $isBorrower ? (int)$tx['lender_id'] : (int)$tx['borrower_id'];
        $actor = User::find($userId);
        
        self::pdo()->prepare("
            INSERT INTO notifications (user_id, title, message, type, link) 
            VALUES (:uid, :title, :msg, 'handover', :link)
        ")->execute([
            ':uid' => $otherUserId,
            ':title' => '📦 Handover Confirmed',
            ':msg' => "{$actor['full_name']} confirmed that the item has been physically handed over.",
            ':link' => '/transactions?id=' . $txId,
        ]);

        return ['status' => 'handed_over', 'message' => 'Handover successfully confirmed!'];
    }

    /**
     * Confirm safe return of item (moves state from 'handed_over' or 'overdue' to 'returned')
     */
    public static function confirmReturn(int $txId, int $userId): array
    {
        $tx = self::find($txId);
        if (!$tx) {
            throw new InvalidArgumentException("Transaction not found.");
        }

        $isBorrower = (int)$tx['borrower_id'] === $userId;
        $isLender = (int)$tx['lender_id'] === $userId;
        if (!$isBorrower && !$isLender) {
            throw new InvalidArgumentException("Unauthorized: You are not part of this transaction.");
        }

        // STRICT STATE MACHINE VALIDATION:
        // Item CANNOT be returned without having been handed over first!
        if ($tx['status'] === 'accepted') {
            throw new Exception("Invalid state jump: Cannot mark item as returned before handover has taken place.");
        }

        if ($tx['status'] === 'returned') {
            throw new Exception("Invalid state jump: Item has already been returned.");
        }

        if ($tx['status'] === 'cancelled') {
            throw new Exception("Invalid state jump: This transaction was cancelled.");
        }

        if (!in_array($tx['status'], ['handed_over', 'overdue'])) {
            throw new Exception("Invalid state transition: Cannot return item from status '{$tx['status']}'.");
        }

        // Mark return confirmed
        $updateData = [
            'status' => 'returned',
            'returned_time' => date('Y-m-d H:i:s'),
        ];

        if ($isBorrower) {
            $updateData['return_confirmed_borrower'] = 1;
        }
        if ($isLender) {
            $updateData['return_confirmed_lender'] = 1;
        }

        self::update($txId, $updateData);

        // Mark request as completely fulfilled
        BorrowRequest::update((int)$tx['request_id'], ['status' => 'fulfilled']);

        // Make the item available in the catalog again
        if (!empty($tx['item_id'])) {
            Item::update((int)$tx['item_id'], ['is_available' => 1]);
        }

        // Notify other party to leave a rating
        $otherUserId = $isBorrower ? (int)$tx['lender_id'] : (int)$tx['borrower_id'];
        $actor = User::find($userId);

        self::pdo()->prepare("
            INSERT INTO notifications (user_id, title, message, type, link) 
            VALUES (:uid, :title, :msg, 'return_due', :link)
        ")->execute([
            ':uid' => $otherUserId,
            ':title' => '⭐ Item Returned – Rate Your Experience',
            ':msg' => "{$actor['full_name']} confirmed safe return of the item. Please share your rating and review!",
            ':link' => '/transactions?id=' . $txId,
        ]);

        return ['status' => 'returned', 'message' => 'Item safely returned and transaction completed!'];
    }

    /**
     * Get transaction with all related entity details
     */
    public static function getWithDetails(int $id): ?array
    {
        $sql = "
            SELECT t.*,
                   r.item_name, r.urgency, r.campus_block, r.room_location, r.needed_by,
                   c.name as category_name, c.icon as category_icon,
                   b.full_name as borrower_name, b.email as borrower_email, b.phone as borrower_phone, b.department as borrower_dept, b.trust_score as borrower_trust,
                   l.full_name as lender_name, l.email as lender_email, l.phone as lender_phone, l.department as lender_dept, l.trust_score as lender_trust,
                   i.title as item_title, i.item_condition, i.pickup_location, i.photo as item_photo
            FROM borrow_transactions t
            JOIN requests r ON t.request_id = r.id
            JOIN categories c ON r.category_id = c.id
            JOIN users b ON t.borrower_id = b.id
            JOIN users l ON t.lender_id = l.id
            LEFT JOIN items i ON t.item_id = i.id
            WHERE t.id = :id
            LIMIT 1
        ";

        $tx = self::fetch($sql, [':id' => $id]);
        if (!$tx) {
            return null;
        }

        // Check if overdue
        if ($tx['status'] === 'handed_over' && strtotime($tx['due_time']) < time()) {
            self::update($id, ['status' => 'overdue']);
            $tx['status'] = 'overdue';
        }

        return $tx;
    }

    /**
     * Get user transactions (both borrowed and lent)
     */
    public static function getUserTransactions(int $userId): array
    {
        $sql = "
            SELECT t.*,
                   r.item_name, r.campus_block,
                   c.name as category_name, c.icon as category_icon,
                   b.full_name as borrower_name,
                   l.full_name as lender_name
            FROM borrow_transactions t
            JOIN requests r ON t.request_id = r.id
            JOIN categories c ON r.category_id = c.id
            JOIN users b ON t.borrower_id = b.id
            JOIN users l ON t.lender_id = l.id
            WHERE t.borrower_id = :uid1 OR t.lender_id = :uid2
            ORDER BY t.id DESC
        ";

        return self::fetchAll($sql, [':uid1' => $userId, ':uid2' => $userId]);
    }
}
