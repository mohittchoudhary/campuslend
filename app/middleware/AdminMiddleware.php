<?php
/**
 * CampusLend - Admin Authorization Middleware
 * Enforces strict role-based access control (RBAC).
 * Ensures non-admin students receive HTTP 403 Forbidden.
 */

namespace App\Middleware;

class AdminMiddleware
{
    public function handle(): void
    {
        if (!is_logged_in()) {
            redirect('/login', ['error' => 'Please sign in to access admin functions.']);
        }

        if (!is_admin()) {
            http_response_code(403);
            echo "<!DOCTYPE html><html><head><title>403 Forbidden - CampusLend Admin</title>";
            echo "<style>body{font-family:system-ui,-apple-system,sans-serif;background:#0f172a;color:#f8fafc;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;text-align:center;}</style></head><body>";
            echo "<div style='max-width:480px;padding:32px;background:#1e293b;border-radius:16px;border:1px solid #334155;box-shadow:0 20px 25px -5px rgba(0,0,0,0.5);'>";
            echo "<h1 style='color:#ef4444;font-size:3rem;margin:0 0 12px 0;'>403</h1>";
            echo "<h2 style='margin:0 0 8px 0;font-size:1.4rem;'>Forbidden: Administrator Access Only</h2>";
            echo "<p style='color:#94a3b8;font-size:0.95rem;line-height:1.6;margin-bottom:24px;'>You are logged in as a student. Only campus administrators have permission to access the control panel.</p>";
            echo "<a href='/feed' style='display:inline-block;padding:10px 22px;background:#4f46e5;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;'>Return to Campus Feed</a>";
            echo "</div></body></html>";
            exit;
        }
    }
}
