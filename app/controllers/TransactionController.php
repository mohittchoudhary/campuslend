<?php
/**
 * CampusLend Transaction & Borrow Flow Controller
 * Handles Offer Acceptance, Handover Confirmation, Return Confirmation, and Activity
 */

namespace App\Controllers;

use App\Models\BorrowTransaction;
use App\Models\BorrowRequest;
use App\Models\Item;
use App\Models\Offer;

class TransactionController extends Controller
{
    /**
     * Accept an offer to start borrow transaction
     */
    public function acceptOffer(): void
    {
        $user = auth_user();
        if (!$user || (int)$user['is_verified'] !== 1) {
            redirect('/login', ['error' => 'Please verify your campus account to continue.']);
        }

        $offerId = (int)($_POST['offer_id'] ?? 0);

        try {
            $txId = BorrowTransaction::createFromOffer($offerId, (int)$user['id']);
            redirect('/transactions?id=' . $txId, [
                'success' => '🎉 Offer accepted! Please connect with your peer lender to coordinate handover.'
            ]);
        } catch (\Throwable $e) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/feed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * View active borrow transaction status & state machine progress
     */
    public function view(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $user = auth_user();

        $tx = BorrowTransaction::getWithDetails($id);
        if (!$tx) {
            http_response_code(404);
            $this->render('pages/404', ['title' => 'Transaction Not Found']);
            return;
        }

        // Verify viewer is either borrower, lender or admin
        $isBorrower = (int)$tx['borrower_id'] === (int)$user['id'];
        $isLender = (int)$tx['lender_id'] === (int)$user['id'];
        $isAdmin = ($user['role'] ?? '') === 'admin';

        if (!$isBorrower && !$isLender && !$isAdmin) {
            redirect('/dashboard', ['error' => 'Unauthorized: You are not a party to this transaction.']);
        }

        $this->render('pages/transactions/view', [
            'title' => 'Transaction #' . $id . ' - ' . e($tx['item_name']),
            'tx' => $tx,
            'isBorrower' => $isBorrower,
            'isLender' => $isLender,
            'userId' => (int)$user['id'],
        ]);
    }

    /**
     * Confirm Handover
     */
    public function confirmHandover(): void
    {
        $user = auth_user();
        $txId = (int)($_POST['transaction_id'] ?? 0);

        try {
            $res = BorrowTransaction::confirmHandover($txId, (int)$user['id']);
            redirect('/transactions?id=' . $txId, ['success' => $res['message']]);
        } catch (\Throwable $e) {
            redirect('/transactions?id=' . $txId, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Confirm Return
     */
    public function confirmReturn(): void
    {
        $user = auth_user();
        $txId = (int)($_POST['transaction_id'] ?? 0);

        try {
            $res = BorrowTransaction::confirmReturn($txId, (int)$user['id']);
            redirect('/transactions?id=' . $txId, ['success' => $res['message']]);
        } catch (\Throwable $e) {
            redirect('/transactions?id=' . $txId, ['error' => $e->getMessage()]);
        }
    }

    /**
     * User Activity Overview (Active Loans, Open Requests, Listings)
     */
    public function myActivity(): void
    {
        $user = auth_user();
        $userId = (int)$user['id'];

        $transactions = BorrowTransaction::getUserTransactions($userId);
        $myRequests = BorrowRequest::getUserRequests($userId);
        $myItems = Item::getUserListings($userId);

        $this->render('pages/transactions/my_activity', [
            'title' => 'My Campus Activity - CampusLend',
            'transactions' => $transactions,
            'requests' => $myRequests,
            'items' => $myItems,
            'user' => $user,
        ]);
    }
}
