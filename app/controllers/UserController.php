<?php
/**
 * CampusLend User Profile, Settings, Reports & Blocking Controller
 */

namespace App\Controllers;

use App\Models\User;
use App\Models\Badge;
use App\Models\Rating;
use App\Models\BorrowTransaction;
use App\Models\UserBlock;
use App\Models\Report;

class UserController extends Controller
{
    /**
     * View Profile & Stats
     */
    public function profile(): void
    {
        $viewer = auth_user();
        $targetId = !empty($_GET['id']) ? (int)$_GET['id'] : (int)$viewer['id'];
        $user = User::find($targetId);

        if (!$user) {
            http_response_code(404);
            $this->render('pages/404', ['title' => 'Student Profile Not Found']);
            return;
        }

        $isOwnProfile = (int)$viewer['id'] === (int)$user['id'];
        $badges = Badge::getUserBadges($user['id']);
        $reviews = Rating::getReviewsForUser($user['id']);
        $isBlocked = !$isOwnProfile && UserBlock::isBlocked((int)$viewer['id'], $user['id']);

        // Transaction stats
        $pdo = User::pdo();
        $stats = [
            'lent_count' => (int)$pdo->query("SELECT COUNT(*) FROM borrow_transactions WHERE lender_id = {$user['id']} AND status = 'returned'")->fetchColumn(),
            'borrowed_count' => (int)$pdo->query("SELECT COUNT(*) FROM borrow_transactions WHERE borrower_id = {$user['id']} AND status = 'returned'")->fetchColumn(),
            'active_count' => (int)$pdo->query("SELECT COUNT(*) FROM borrow_transactions WHERE (lender_id = {$user['id']} OR borrower_id = {$user['id']}) AND status IN ('accepted', 'handed_over', 'overdue')")->fetchColumn(),
        ];

        $this->render('pages/profile/index', [
            'title' => e($user['full_name']) . ' - Campus Profile',
            'user' => $user,
            'isOwnProfile' => $isOwnProfile,
            'badges' => $badges,
            'reviews' => $reviews,
            'stats' => $stats,
            'isBlocked' => $isBlocked,
        ]);
    }

    /**
     * Update Profile Info
     */
    public function updateProfile(): void
    {
        $viewer = auth_user();
        $data = $_POST;

        $errors = $this->validate($data, [
            'full_name' => 'required|min:3|max:100',
            'department' => 'required',
            'phone' => 'required|min:10|max:20',
        ]);

        if (!empty($errors)) {
            redirect('/profile', ['error' => 'Please correct the highlighted fields.']);
        }

        User::update((int)$viewer['id'], [
            'full_name' => trim($data['full_name']),
            'department' => trim($data['department']),
            'year_of_study' => $data['year_of_study'] ?? $viewer['year_of_study'],
            'phone' => trim($data['phone']),
        ]);

        // Refresh session
        $_SESSION['user'] = User::find((int)$viewer['id']);

        redirect('/profile', ['success' => 'Your campus profile details have been updated.']);
    }

    /**
     * Update Avatar Photo
     */
    public function updateAvatar(): void
    {
        $viewer = auth_user();

        if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            redirect('/profile', ['error' => 'No image file uploaded.']);
        }

        $file = $_FILES['avatar'];
        $maxSize = 2 * 1024 * 1024; // 2 MB

        if ($file['size'] > $maxSize) {
            redirect('/profile', ['error' => 'Avatar image cannot exceed 2 MB.']);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            redirect('/profile', ['error' => 'Only JPG, PNG and WebP images are allowed.']);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
            redirect('/profile', ['error' => 'Invalid image format.']);
        }

        $avatarFileName = 'avatar_' . $viewer['id'] . '_' . bin2hex(random_bytes(8)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
        $dest = __DIR__ . '/../../public/uploads/avatars/' . $avatarFileName;

        if (!is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0777, true);
        }

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            User::update((int)$viewer['id'], ['avatar' => $avatarFileName]);
            $_SESSION['user'] = User::find((int)$viewer['id']);
            redirect('/profile', ['success' => 'Profile picture updated successfully!']);
        }

        redirect('/profile', ['error' => 'Failed to save avatar image.']);
    }

    /**
     * Change Password
     */
    public function changePassword(): void
    {
        $viewer = auth_user();
        $currPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        $user = User::find((int)$viewer['id']);
        if (!password_verify($currPassword, $user['password_hash'])) {
            redirect('/profile', ['error' => 'Your current password is incorrect.']);
        }

        if (strlen($newPassword) < 6) {
            redirect('/profile', ['error' => 'New password must be at least 6 characters long.']);
        }

        if ($newPassword !== $confirmPassword) {
            redirect('/profile', ['error' => 'New passwords do not match.']);
        }

        User::updatePassword((int)$viewer['id'], $newPassword);
        redirect('/profile', ['success' => 'Password changed successfully!']);
    }

    /**
     * Block a user
     */
    public function blockUser(): void
    {
        $viewer = auth_user();
        $targetId = (int)($_POST['target_id'] ?? 0);

        try {
            UserBlock::block((int)$viewer['id'], $targetId);
            redirect($_SERVER['HTTP_REFERER'] ?? '/feed', ['success' => 'User blocked successfully.']);
        } catch (\Throwable $e) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/feed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Unblock a user
     */
    public function unblockUser(): void
    {
        $viewer = auth_user();
        $targetId = (int)($_POST['target_id'] ?? 0);

        UserBlock::unblock((int)$viewer['id'], $targetId);
        redirect($_SERVER['HTTP_REFERER'] ?? '/profile', ['success' => 'User unblocked.']);
    }

    /**
     * Report an abusive user
     */
    public function reportUser(): void
    {
        $viewer = auth_user();
        $targetId = (int)($_POST['target_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $details = trim($_POST['details'] ?? '');

        try {
            Report::createReport((int)$viewer['id'], $targetId, $reason, $details);
            redirect($_SERVER['HTTP_REFERER'] ?? '/feed', [
                'success' => 'Report submitted to campus administrators for review. Thank you for keeping our campus safe.'
            ]);
        } catch (\Throwable $e) {
            redirect($_SERVER['HTTP_REFERER'] ?? '/feed', ['error' => $e->getMessage()]);
        }
    }
}
