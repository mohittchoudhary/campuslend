<?php
/**
 * CampusLend Home & Landing Controller
 */

namespace App\Controllers;

use App\Models\Model;

class HomeController extends Controller
{
    public function index(): void
    {
        $pdo = Model::pdo();

        // Platform statistics
        $stats = [
            'students_count' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn(),
            'items_count' => (int)$pdo->query("SELECT COUNT(*) FROM items WHERE is_available = 1")->fetchColumn(),
            'fulfilled_count' => (int)$pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'fulfilled'")->fetchColumn(),
            'critical_count' => (int)$pdo->query("SELECT COUNT(*) FROM requests WHERE urgency = 'Critical' AND status = 'open'")->fetchColumn(),
        ];

        // Categories with active item counts
        $categories = $pdo->query("
            SELECT c.*, COUNT(i.id) as item_count 
            FROM categories c 
            LEFT JOIN items i ON c.id = i.category_id AND i.is_available = 1 
            GROUP BY c.id 
            ORDER BY c.id ASC
        ")->fetchAll();

        // Recent urgent requests (Critical and High)
        $urgentRequests = $pdo->query("
            SELECT r.*, c.name as category_name, c.icon as category_icon, u.full_name as student_name, u.department, u.trust_score 
            FROM requests r 
            JOIN categories c ON r.category_id = c.id 
            JOIN users u ON r.user_id = u.id 
            WHERE r.status IN ('open', 'offered') 
            ORDER BY CASE WHEN r.urgency = 'Critical' THEN 1 WHEN r.urgency = 'High' THEN 2 ELSE 3 END, r.id DESC 
            LIMIT 6
        ")->fetchAll();

        // Check exam mode setting
        $examMode = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'exam_mode_enabled'")->fetchColumn() === '1';
        $examMessage = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'exam_mode_message'")->fetchColumn() ?: '';

        $this->render('pages/landing', [
            'title' => 'CampusLend - Emergency Campus Borrowing Platform | UTU',
            'stats' => $stats,
            'categories' => $categories,
            'urgentRequests' => $urgentRequests,
            'examMode' => $examMode,
            'examMessage' => $examMessage,
            'campusName' => config('app.campus_name', 'Uka Tarsadia University'),
        ]);
    }

    public function howItWorks(): void
    {
        $this->render('pages/how_it_works', [
            'title' => 'How CampusLend Works - Student Guide',
            'campusName' => config('app.campus_name', 'Uka Tarsadia University'),
        ]);
    }
}
