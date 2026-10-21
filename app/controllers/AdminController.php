<?php
/**
 * CampusLend Admin Controller
 * Provides Campus Administration, Metrics Dashboard (Chart.js),
 * Moderation, User Ban/Unban, Category CRUD, Dynamic Settings, and Audit Logging.
 */

namespace App\Controllers;

use App\Models\User;
use App\Models\Category;
use App\Models\BorrowRequest;
use App\Models\Item;
use App\Models\BorrowTransaction;
use App\Models\Report;
use App\Models\AdminLog;
use App\Models\Setting;
use Exception;

class AdminController extends Controller
{
    /**
     * Admin Metrics Dashboard with Chart.js Visualization Data
     */
    public function dashboard(): void
    {
        // 1. Overview counts
        $totalUsers = (int)User::count();
        $totalRequests = (int)BorrowRequest::count();
        $totalItems = (int)Item::count();
        $totalTransactions = (int)BorrowTransaction::count();

        // 2. Active critical requests
        $criticalRequests = BorrowRequest::fetchAll("
            SELECT r.*, u.full_name as student_name, c.name as category_name
            FROM requests r
            JOIN users u ON r.user_id = u.id
            JOIN categories c ON r.category_id = c.id
            WHERE r.urgency = 'critical' AND r.status = 'open'
            ORDER BY r.id DESC
            LIMIT 5
        ");

        // 3. Success rate calculation
        $returnedCount = (int)BorrowTransaction::count("status = 'returned'");
        $successRate = $totalTransactions > 0 ? round(($returnedCount / $totalTransactions) * 100, 1) : 100.0;

        // 4. Requests per day (Last 7 days for Chart.js)
        $requestsPerDayRaw = BorrowRequest::fetchAll("
            SELECT substr(created_at, 1, 10) as day, COUNT(*) as count
            FROM requests
            GROUP BY substr(created_at, 1, 10)
            ORDER BY day DESC
            LIMIT 7
        ");
        $requestsPerDay = array_reverse($requestsPerDayRaw);

        // 5. Top Categories distribution
        $topCategories = Category::fetchAll("
            SELECT c.name, COUNT(r.id) as req_count
            FROM categories c
            LEFT JOIN requests r ON c.id = r.category_id
            GROUP BY c.id, c.name
            ORDER BY req_count DESC
            LIMIT 5
        ");

        // 6. Recent Admin Activity Logs
        $recentLogs = AdminLog::getRecent(6);

        // 7. Exam banner settings
        $examMode = Setting::get('exam_mode', '1');
        $examMessage = Setting::get('exam_banner_message', '📢 Mid-Term Exam Alert: Emergency requests for Calculators, Drafters, and Lab Coats are prioritized. Return borrowed items on time!');

        $this->render('admin/dashboard', [
            'title' => 'Admin Command Center - CampusLend',
            'totalUsers' => $totalUsers,
            'totalRequests' => $totalRequests,
            'totalItems' => $totalItems,
            'totalTransactions' => $totalTransactions,
            'successRate' => $successRate,
            'criticalRequests' => $criticalRequests,
            'requestsPerDay' => $requestsPerDay,
            'topCategories' => $topCategories,
            'recentLogs' => $recentLogs,
            'examMode' => $examMode,
            'examMessage' => $examMessage,
        ]);
    }

    /**
     * User Directory & Moderation (Ban/Unban)
     */
    public function users(): void
    {
        $users = User::getAllWithStats();
        $this->render('admin/users', [
            'title' => 'Student Directory & Moderation - CampusLend Admin',
            'users' => $users,
        ]);
    }

    public function banUser(): void
    {
        $userId = (int)($_POST['user_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? 'Violation of campus borrowing rules');

        if ($userId <= 0) {
            redirect('/admin/users', ['error' => 'Invalid student ID specified.']);
        }

        if ($userId === (int)auth_user('id')) {
            redirect('/admin/users', ['error' => 'You cannot ban your own administrator account.']);
        }

        User::banUser($userId);
        AdminLog::log((int)auth_user('id'), 'ban_user', 'user', $userId, "Banned student #{$userId}. Reason: {$reason}");

        redirect('/admin/users', ['success' => "Student #{$userId} has been suspended from CampusLend."]);
    }

    public function unbanUser(): void
    {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            redirect('/admin/users', ['error' => 'Invalid student ID specified.']);
        }

        User::unbanUser($userId);
        AdminLog::log((int)auth_user('id'), 'unban_user', 'user', $userId, "Restored access for student #{$userId}.");

        redirect('/admin/users', ['success' => "Student #{$userId} access restored successfully."]);
    }

    /**
     * Category CRUD Operations
     */
    public function categories(): void
    {
        $categories = Category::getWithItemCounts();
        $this->render('admin/categories', [
            'title' => 'Manage Item Categories - CampusLend Admin',
            'categories' => $categories,
        ]);
    }

    public function createCategory(): void
    {
        $name = trim($_POST['name'] ?? '');
        $icon = trim($_POST['icon'] ?? '📦');
        $desc = trim($_POST['description'] ?? '');

        if (empty($name)) {
            redirect('/admin/categories', ['error' => 'Category name cannot be empty.']);
        }

        $id = Category::createCategory($name, $icon, $desc);
        AdminLog::log((int)auth_user('id'), 'create_category', 'category', $id, "Created category '{$name}' with icon {$icon}");

        redirect('/admin/categories', ['success' => "Category '{$name}' added to campus directory."]);
    }

    public function updateCategory(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $icon = trim($_POST['icon'] ?? '📦');
        $desc = trim($_POST['description'] ?? '');

        if ($id <= 0 || empty($name)) {
            redirect('/admin/categories', ['error' => 'Valid ID and category name are required.']);
        }

        Category::updateCategory($id, $name, $icon, $desc);
        AdminLog::log((int)auth_user('id'), 'update_category', 'category', $id, "Updated category #{$id} ({$name})");

        redirect('/admin/categories', ['success' => "Category #{$id} updated successfully."]);
    }

    public function deleteCategory(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            redirect('/admin/categories', ['error' => 'Invalid category ID.']);
        }

        Category::deleteCategory($id);
        AdminLog::log((int)auth_user('id'), 'delete_category', 'category', $id, "Deleted category #{$id}");

        redirect('/admin/categories', ['success' => "Category #{$id} deleted."]);
    }

    /**
     * Abuse & Safety Reports Moderation
     */
    public function reports(): void
    {
        $reports = Report::getAllWithDetails();
        $this->render('admin/reports', [
            'title' => 'Abuse Reports & Safety - CampusLend Admin',
            'reports' => $reports,
        ]);
    }

    public function updateReport(): void
    {
        $reportId = (int)($_POST['report_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'reviewed');

        if ($reportId <= 0) {
            redirect('/admin/reports', ['error' => 'Invalid report ID.']);
        }

        Report::update($reportId, ['status' => $status]);
        AdminLog::log((int)auth_user('id'), 'update_report', 'report', $reportId, "Updated report status to '{$status}'");

        redirect('/admin/reports', ['success' => "Report #{$reportId} status updated to {$status}."]);
    }

    /**
     * Platform Dynamic Settings (Exam Mode toggle)
     */
    public function toggleExamMode(): void
    {
        $enabled = !empty($_POST['exam_mode']) ? '1' : '0';
        $message = trim($_POST['exam_message'] ?? '');

        Setting::set('exam_mode', $enabled);
        if (!empty($message)) {
            Setting::set('exam_banner_message', $message);
        }

        AdminLog::log((int)auth_user('id'), 'update_settings', 'settings', null, "Exam season banner mode set to {$enabled}.");

        redirect('/admin/dashboard', ['success' => 'Campus exam season banner settings saved.']);
    }

    /**
     * Audit Trail / Action Logs
     */
    public function logs(): void
    {
        $logs = AdminLog::getRecent(100);
        $this->render('admin/logs', [
            'title' => 'Administrator Action Audit Trail - CampusLend',
            'logs' => $logs,
        ]);
    }
}
