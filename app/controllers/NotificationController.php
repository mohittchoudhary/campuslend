<?php
/**
 * CampusLend Notification Center Controller
 */

namespace App\Controllers;

use App\Models\Notification;

class NotificationController extends Controller
{
    /**
     * Show Notifications Center
     */
    public function index(): void
    {
        $user = auth_user();
        $notifications = Notification::getByUser((int)$user['id'], 50);

        $this->render('pages/notifications/index', [
            'title' => 'Notifications - CampusLend',
            'notifications' => $notifications,
        ]);
    }

    /**
     * Fetch unread notification count (AJAX Polling)
     */
    public function getUnreadCount(): void
    {
        $user = auth_user();
        if (!$user) {
            $this->json(['count' => 0]);
        }

        $count = Notification::getUnreadCount((int)$user['id']);
        $this->json(['count' => $count]);
    }

    /**
     * Mark single notification as read
     */
    public function markAsRead(): void
    {
        $user = auth_user();
        $id = (int)($_POST['id'] ?? 0);

        Notification::markRead($id, (int)$user['id']);

        if (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
            $this->json(['success' => true]);
        }

        redirect($_SERVER['HTTP_REFERER'] ?? '/notifications');
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): void
    {
        $user = auth_user();
        Notification::markAllRead((int)$user['id']);

        redirect('/notifications', ['success' => 'All notifications marked as read.']);
    }
}
