<?php
/**
 * CampusLend Authentication & Authorization Middlewares
 */

namespace App\Middleware;

class AuthMiddleware
{
    public function handle(): void
    {
        if (!is_logged_in()) {
            redirect('/login', ['error' => 'Please log in to continue.']);
        }
    }
}

class GuestMiddleware
{
    public function handle(): void
    {
        if (is_logged_in()) {
            redirect(is_admin() ? '/admin/dashboard' : '/dashboard');
        }
    }
}

class VerifiedMiddleware
{
    public function handle(): void
    {
        if (!is_logged_in()) {
            redirect('/login', ['error' => 'Please log in to continue.']);
        }

        if (!is_verified()) {
            redirect('/verify-otp', ['warning' => 'Please verify your campus email OTP to access this feature.']);
        }
    }
}

class AdminMiddleware
{
    public function handle(): void
    {
        if (!is_logged_in()) {
            redirect('/login', ['error' => 'Admin authentication required.']);
        }

        if (!is_admin()) {
            http_response_code(403);
            echo "<!DOCTYPE html><html><head><title>403 Forbidden - CampusLend Admin</title></head><body style='font-family:sans-serif;text-align:center;padding:50px;'>";
            echo "<h1 style='color:#ef4444;font-size:3rem;'>403 Forbidden</h1>";
            echo "<p>Access denied. Administrator privileges required.</p>";
            echo "<a href='/feed'>Return to Campus Feed</a>";
            echo "</body></html>";
            exit;
        }
    }
}

class CsrfMiddleware
{
    public function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf()) {
                http_response_code(419);
                redirect($_SERVER['HTTP_REFERER'] ?? '/login', [
                    'error' => 'Security token expired or invalid. Please submit the form again.'
                ]);
            }
        }
    }
}
