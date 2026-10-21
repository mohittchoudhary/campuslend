<?php
/**
 * CampusLend Real-Time Chat Controller
 * Endpoints for per-transaction AJAX polling & message dispatch
 */

namespace App\Controllers;

use App\Models\Message;
use App\Models\BorrowTransaction;
use Exception;

class ChatController extends Controller
{
    /**
     * Fetch conversation messages for a transaction (AJAX Polling)
     */
    public function getMessages(): void
    {
        $user = auth_user();
        if (!$user) {
            $this->json(['error' => 'Unauthenticated'], 401);
        }

        $txId = (int)($_GET['transaction_id'] ?? 0);
        if ($txId <= 0) {
            $this->json(['error' => 'Missing transaction ID'], 400);
        }

        try {
            $messages = Message::getConversation($txId, (int)$user['id']);
            $this->json([
                'success' => true,
                'messages' => $messages,
                'user_id' => (int)$user['id'],
            ]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 403);
        }
    }

    /**
     * Send a new message inside transaction chat
     */
    public function sendMessage(): void
    {
        $user = auth_user();
        if (!$user) {
            $this->json(['error' => 'Unauthenticated'], 401);
        }

        if (!verify_csrf()) {
            $this->json(['error' => 'Invalid CSRF token'], 419);
        }

        $txId = (int)($_POST['transaction_id'] ?? 0);
        $text = trim($_POST['message'] ?? '');

        if ($txId <= 0 || empty($text)) {
            $this->json(['error' => 'Message and transaction ID required'], 400);
        }

        try {
            $msgId = Message::sendMessage($txId, (int)$user['id'], $text);
            $this->json([
                'success' => true,
                'message_id' => $msgId,
                'created_at' => date('h:i A'),
            ]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 403);
        }
    }
}
