<?php
/**
 * CampusLend Chat Message Model
 * Per-transaction direct messaging between borrower and lender
 */

namespace App\Models;

use Exception;
use InvalidArgumentException;

class Message extends Model
{
    protected static string $table = 'messages';

    /**
     * Check if communication is blocked between two users
     */
    public static function isBlockedBetween(int $userA, int $userB): bool
    {
        $sql = "SELECT id FROM user_blocks WHERE (blocker_id = :a1 AND blocked_id = :b1) OR (blocker_id = :b2 AND blocked_id = :a2) LIMIT 1";
        $res = self::fetch($sql, [
            ':a1' => $userA, ':b1' => $userB,
            ':b2' => $userB, ':a2' => $userA,
        ]);
        return $res !== null;
    }

    /**
     * Send a message within a transaction
     */
    public static function sendMessage(int $transactionId, int $senderId, string $message): int
    {
        $message = trim($message);
        if ($message === '') {
            throw new InvalidArgumentException("Message cannot be empty.");
        }

        $tx = BorrowTransaction::find($transactionId);
        if (!$tx) {
            throw new InvalidArgumentException("Transaction not found.");
        }

        // Access Control: Only the borrower and lender can chat
        $isBorrower = (int)$tx['borrower_id'] === $senderId;
        $isLender = (int)$tx['lender_id'] === $senderId;

        if (!$isBorrower && !$isLender) {
            throw new Exception("Unauthorized: You are not a participant in this transaction.");
        }

        $receiverId = $isBorrower ? (int)$tx['lender_id'] : (int)$tx['borrower_id'];

        // Block check
        if (self::isBlockedBetween($senderId, $receiverId)) {
            throw new Exception("Cannot send message: You or the recipient have blocked communication.");
        }

        $msgId = self::insert([
            'transaction_id' => $transactionId,
            'request_id' => $tx['request_id'],
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'message' => $message,
            'is_read' => 0,
        ]);

        // Push in-app notification if receiver hasn't read recent messages
        $sender = User::find($senderId);
        self::pdo()->prepare("
            INSERT INTO notifications (user_id, title, message, type, link)
            VALUES (:uid, :title, :msg, 'chat_message', :link)
        ")->execute([
            ':uid' => $receiverId,
            ':title' => "💬 New message from {$sender['full_name']}",
            ':msg' => mb_substr($message, 0, 75) . (mb_strlen($message) > 75 ? '...' : ''),
            ':link' => '/transactions?id=' . $transactionId . '#chat',
        ]);

        return $msgId;
    }

    /**
     * Retrieve conversation history for a transaction and mark received messages as read
     */
    public static function getConversation(int $transactionId, int $userId): array
    {
        $tx = BorrowTransaction::find($transactionId);
        if (!$tx) {
            throw new InvalidArgumentException("Transaction not found.");
        }

        // Access Control
        if ((int)$tx['borrower_id'] !== $userId && (int)$tx['lender_id'] !== $userId) {
            throw new Exception("Unauthorized: Cannot access this conversation.");
        }

        // Mark incoming messages as read
        self::pdo()->prepare("
            UPDATE messages SET is_read = 1 
            WHERE transaction_id = :tx_id AND receiver_id = :uid AND is_read = 0
        ")->execute([':tx_id' => $transactionId, ':uid' => $userId]);

        $sql = "
            SELECT m.*, u.full_name as sender_name, u.avatar as sender_avatar
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.transaction_id = :tx_id
            ORDER BY m.id ASC
        ";

        return self::fetchAll($sql, [':tx_id' => $transactionId]);
    }

    /**
     * Get unread message count for user
     */
    public static function getUnreadCount(int $userId): int
    {
        $sql = "SELECT COUNT(*) FROM messages WHERE receiver_id = :uid AND is_read = 0";
        return (int)self::fetchColumn($sql, [':uid' => $userId]);
    }
}
