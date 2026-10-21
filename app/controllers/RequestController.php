<?php
/**
 * CampusLend Emergency Requests & Live Feed Controller
 */

namespace App\Controllers;

use App\Models\BorrowRequest;
use App\Models\Category;
use App\Models\Item;
use App\Models\Offer;
use App\Models\User;

class RequestController extends Controller
{
    /**
     * Live Emergency Feed with Filters and Search
     */
    public function feed(): void
    {
        $filters = [
            'category' => $_GET['category'] ?? 'all',
            'urgency' => $_GET['urgency'] ?? 'all',
            'block' => $_GET['block'] ?? 'all',
            'status' => $_GET['status'] ?? 'open',
            'search' => $_GET['search'] ?? '',
        ];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $feedData = BorrowRequest::getFeed($filters, $limit, $offset);
        $categories = Category::allOrdered();

        $campusBlocks = [
            'Block A', 'Block B', 'Block C', 'Block D', 
            'Pharmacy Block', 'Central Library', 'Mechanical Workshop', 
            'Student Canteen', 'Hostels', 'Main Gate'
        ];

        // Support AJAX response for live filter updates
        if (
            (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
            !empty($_GET['ajax'])
        ) {
            $this->json([
                'success' => true,
                'items' => $feedData['items'],
                'total' => $feedData['total'],
                'page' => $page,
                'pages' => ceil($feedData['total'] / $limit),
            ]);
        }

        $this->render('pages/requests/feed', [
            'title' => 'Live Campus Emergency Feed - CampusLend',
            'requests' => $feedData['items'],
            'total' => $feedData['total'],
            'page' => $page,
            'pages' => max(1, ceil($feedData['total'] / $limit)),
            'categories' => $categories,
            'campusBlocks' => $campusBlocks,
            'filters' => $filters,
            'campusName' => config('app.campus_name', 'Uka Tarsadia University'),
        ]);
    }

    /**
     * Show Post Emergency Request Form (or SOS Mode)
     */
    public function showCreate(): void
    {
        $categories = Category::allOrdered();
        $isSos = !empty($_GET['urgent']) || !empty($_GET['sos']);

        $campusBlocks = [
            'Block A', 'Block B', 'Block C', 'Block D', 
            'Pharmacy Block', 'Central Library', 'Mechanical Workshop', 
            'Student Canteen', 'Hostels', 'Main Gate'
        ];

        $this->render('pages/requests/create', [
            'title' => ($isSos ? '🚨 Urgent SOS Request' : 'Post Emergency Request') . ' - CampusLend',
            'categories' => $categories,
            'campusBlocks' => $campusBlocks,
            'isSos' => $isSos,
        ]);
    }

    /**
     * Process Request Creation
     */
    public function handleCreate(): void
    {
        $user = auth_user();
        if (!$user || (int)$user['is_verified'] !== 1 || (int)$user['is_banned'] === 1) {
            redirect('/login', ['error' => 'Only verified, active campus students can post requests.']);
        }

        $data = $_POST;
        $errors = $this->validate($data, [
            'item_name' => 'required|min:3|max:150',
            'category_id' => 'required',
            'campus_block' => 'required',
            'room_location' => 'required|max:100',
        ]);

        // Calculate needed_by datetime
        $neededBy = null;
        if (!empty($data['needed_in_minutes'])) {
            // Quick preset: 15m, 30m, 60m, 120m
            $mins = (int)$data['needed_in_minutes'];
            $neededBy = date('Y-m-d H:i:s', time() + ($mins * 60));
        } elseif (!empty($data['needed_by'])) {
            $ts = strtotime($data['needed_by']);
            if ($ts <= time()) {
                $errors['needed_by'] = "Needed-by time must be in the future.";
            } else {
                $neededBy = date('Y-m-d H:i:s', $ts);
            }
        } else {
            // Default 1 hour
            $neededBy = date('Y-m-d H:i:s', time() + 3600);
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $data;
            redirect('/requests/create', ['error' => 'Please correct the highlighted fields.']);
        }

        $urgency = in_array($data['urgency'] ?? '', ['Critical', 'High', 'Normal']) ? $data['urgency'] : 'Normal';
        if (!empty($data['is_sos'])) {
            $urgency = 'Critical';
        }

        $requestId = BorrowRequest::createRequest([
            'user_id' => $user['id'],
            'category_id' => (int)$data['category_id'],
            'item_name' => $data['item_name'],
            'urgency' => $urgency,
            'campus_block' => $data['campus_block'],
            'room_location' => $data['room_location'],
            'needed_by' => $neededBy,
            'note' => $data['note'] ?? '',
        ]);

        redirect('/requests/view?id=' . $requestId, [
            'success' => "🚨 Emergency request broadcast to nearby students in {$data['campus_block']}!"
        ]);
    }

    /**
     * View Request Details & Active Offers
     */
    public function view(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $request = BorrowRequest::getWithDetails($id);

        if (!$request) {
            http_response_code(404);
            $this->render('pages/404', ['title' => 'Request Not Found']);
            return;
        }

        // Fetch offers for this request
        $offers = Offer::getByRequest($id);

        // Fetch user's own listings if logged in (to allow picking an item to lend)
        $userItems = [];
        $viewerId = auth_id();
        $isOwner = $viewerId && (int)$request['user_id'] === $viewerId;

        if ($viewerId && !$isOwner) {
            $userItems = Item::getUserListings($viewerId);
        }

        // Phase 5: Python Smart Match Service with resilient PHP/SQL fallback
        $matchResult = \App\Services\SmartMatcher::suggestLenders([
            'id' => $request['id'],
            'title' => $request['item_name'],
            'category_id' => $request['category_id'],
            'building_block' => $request['campus_block'],
            'urgency' => $request['urgency'],
            'user_id' => $request['user_id'],
        ], 4);

        $suggestedItems = $matchResult['matches'];
        $matchSource = $matchResult['source'];

        $this->render('pages/requests/view', [
            'title' => e($request['item_name']) . ' - Emergency Request | CampusLend',
            'request' => $request,
            'offers' => $offers,
            'userItems' => $userItems,
            'suggestedItems' => $suggestedItems,
            'matchSource' => $matchSource,
            'isOwner' => $isOwner,
            'viewerId' => $viewerId,
        ]);
    }

    /**
     * Submit "I Can Lend This" Offer
     */
    public function handleOffer(): void
    {
        $user = auth_user();
        if (!$user || (int)$user['is_verified'] !== 1 || (int)$user['is_banned'] === 1) {
            redirect('/login', ['error' => 'Only verified campus students can offer to lend.']);
        }

        $requestId = (int)($_POST['request_id'] ?? 0);
        $itemId = !empty($_POST['item_id']) ? (int)$_POST['item_id'] : null;
        $message = trim($_POST['message'] ?? '');

        try {
            Offer::createOffer($requestId, (int)$user['id'], $itemId, $message);
            redirect('/requests/view?id=' . $requestId, [
                'success' => 'Thank you! Your lending offer has been submitted and the student was notified.'
            ]);
        } catch (\Throwable $e) {
            redirect('/requests/view?id=' . $requestId, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Cancel an open request
     */
    public function cancel(): void
    {
        $user = auth_user();
        $requestId = (int)($_POST['request_id'] ?? 0);
        $request = BorrowRequest::find($requestId);

        if (!$request || (int)$request['user_id'] !== (int)$user['id']) {
            redirect('/feed', ['error' => 'Unauthorized action.']);
        }

        if (in_array($request['status'], ['fulfilled', 'cancelled'])) {
            redirect('/requests/view?id=' . $requestId, ['error' => 'This request cannot be cancelled.']);
        }

        BorrowRequest::update($requestId, ['status' => 'cancelled']);
        redirect('/requests/view?id=' . $requestId, ['info' => 'Your request has been cancelled.']);
    }
}
