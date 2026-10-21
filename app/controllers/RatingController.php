<?php
/**
 * CampusLend Rating & Review Controller
 */

namespace App\Controllers;

use App\Models\Rating;
use App\Models\BorrowTransaction;

class RatingController extends Controller
{
    /**
     * Submit Rating for a completed transaction
     */
    public function submit(): void
    {
        $user = auth_user();
        if (!$user) {
            redirect('/login');
        }

        $txId = (int)($_POST['transaction_id'] ?? 0);
        $stars = (int)($_POST['stars'] ?? 5);
        $comment = trim($_POST['comment'] ?? '');

        try {
            Rating::submitRating($txId, (int)$user['id'], $stars, $comment);
            redirect('/transactions?id=' . $txId, [
                'success' => "⭐ Thank you! Your review has been recorded and the student's campus trust score updated."
            ]);
        } catch (\Throwable $e) {
            redirect('/transactions?id=' . $txId, ['error' => $e->getMessage()]);
        }
    }
}
