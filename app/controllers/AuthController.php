<?php
/**
 * CampusLend Authentication Controller
 * Handles Campus Registration, OTP Email Verification, Login/Logout, Password Resets, Remember Me
 */

namespace App\Controllers;

use App\Models\User;
use App\Models\OtpCode;
use App\Models\PasswordReset;
use App\Helpers\Mailer;
use App\Helpers\RateLimiter;

class AuthController extends Controller
{
    /**
     * Show Registration Form
     */
    public function showRegister(): void
    {
        $this->render('pages/auth/register', [
            'title' => 'Student Registration - CampusLend',
            'campusName' => config('app.campus_name', 'Uka Tarsadia University'),
            'campusDomain' => config('app.email_domain', 'utu.ac.in'),
            'departments' => [
                'Computer Engineering',
                'Information Technology',
                'Computer Applications (BCA / MCA)',
                'Mechanical Engineering',
                'Civil Engineering',
                'Electrical Engineering',
                'Pharmacy (B.Pharm / M.Pharm)',
                'Biotechnology / Life Sciences',
                'Management Studies (BBA / MBA)',
                'Architecture (B.Arch)',
                'Interior Design',
                'Physiotherapy / Nursing',
            ],
            'years' => ['1st Year', '2nd Year', '3rd Year', '4th Year', 'PG / Masters'],
        ]);
    }

    /**
     * Process Registration
     */
    public function handleRegister(): void
    {
        $data = $_POST;
        $campusDomain = config('app.email_domain', 'utu.ac.in');

        // Validation rules
        $errors = $this->validate($data, [
            'full_name' => 'required|min:3|max:100',
            'email' => 'required|email|max:150',
            'password' => 'required|min:6',
            'enrollment_no' => 'required|min:5|max:50',
            'department' => 'required',
            'year_of_study' => 'required',
            'phone' => 'required|min:10|max:20',
        ]);

        $email = strtolower(trim($data['email'] ?? ''));

        // Campus domain strict check
        if (!User::isCampusEmail($email)) {
            $errors['email'] = "Registration requires an official @{$campusDomain} campus email address.";
        }

        // Unique checks
        if (empty($errors['email']) && User::findByEmail($email)) {
            $errors['email'] = "An account with this campus email is already registered.";
        }

        $enrollment = trim($data['enrollment_no'] ?? '');
        if (empty($errors['enrollment_no']) && User::findByEnrollment($enrollment)) {
            $errors['enrollment_no'] = "This enrollment number is already registered.";
        }

        if (!empty($errors)) {
            $_SESSION['old'] = $data;
            $_SESSION['errors'] = $errors;
            redirect('/register', ['error' => 'Please correct the highlighted errors.']);
        }

        // Create unverified user
        try {
            $userId = User::register($data);
            $user = User::find($userId);

            // Generate and send 6-digit OTP
            $otp = OtpCode::generate($userId, 'registration');
            Mailer::sendOtp($user['email'], $user['full_name'], $otp, 'registration');

            // Store pending user in session for OTP verification step
            $_SESSION['pending_user_id'] = $userId;
            $_SESSION['pending_user_email'] = $user['email'];

            redirect('/verify-otp', [
                'success' => "Registration successful! A 6-digit verification code was sent to {$user['email']}."
            ]);
        } catch (\Throwable $e) {
            error_log("Registration error: " . $e->getMessage());
            redirect('/register', ['error' => 'An error occurred during registration. Please try again.']);
        }
    }

    /**
     * Show OTP Verification Page
     */
    public function showVerifyOtp(): void
    {
        $userId = $_SESSION['pending_user_id'] ?? (auth_id() ?: null);
        if (!$userId) {
            redirect('/register', ['warning' => 'Please register or sign in to verify your account.']);
        }

        $user = User::find($userId);
        if (!$user) {
            redirect('/register');
        }

        if ((int)$user['is_verified'] === 1) {
            redirect('/dashboard', ['info' => 'Your campus account is already verified!']);
        }

        // Fetch simulated OTP if mail simulation is active so test user sees code
        $simulatedOtp = $_SESSION['last_sent_otp']['code'] ?? null;

        $this->render('pages/auth/verify_otp', [
            'title' => 'Verify Campus Email OTP - CampusLend',
            'user' => $user,
            'simulatedOtp' => $simulatedOtp,
            'campusDomain' => config('app.email_domain', 'utu.ac.in'),
        ]);
    }

    /**
     * Process OTP Verification
     */
    public function handleVerifyOtp(): void
    {
        $userId = $_SESSION['pending_user_id'] ?? (auth_id() ?: null);
        if (!$userId) {
            redirect('/login', ['error' => 'Session expired. Please sign in to verify.']);
        }

        // Rate limiting check
        $rateKey = "otp_verify_{$userId}";
        if (RateLimiter::tooManyAttempts($rateKey, config('auth.rate_limits.otp_verify.max_attempts', 5))) {
            $wait = RateLimiter::availableIn($rateKey);
            redirect('/verify-otp', ['error' => "Too many verification attempts. Please wait {$wait} seconds."]);
        }

        $code = trim($_POST['otp'] ?? '');
        if (strlen($code) !== 6 || !ctype_digit($code)) {
            redirect('/verify-otp', ['error' => 'Please enter a valid 6-digit verification code.']);
        }

        $result = OtpCode::verify($userId, $code, 'registration');
        if (!$result['success']) {
            RateLimiter::hit($rateKey, config('auth.rate_limits.otp_verify.decay_seconds', 600));
            redirect('/verify-otp', ['error' => $result['error']]);
        }

        // Mark user verified
        User::markVerified($userId);
        RateLimiter::clear($rateKey);

        $user = User::find($userId);

        // Regenerate session ID for security
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
        unset($_SESSION['pending_user_id'], $_SESSION['pending_user_email'], $_SESSION['last_sent_otp']);

        redirect('/dashboard', [
            'success' => "🎉 Welcome to CampusLend, {$user['full_name']}! Your campus email has been successfully verified."
        ]);
    }

    /**
     * Resend Verification OTP
     */
    public function resendOtp(): void
    {
        $userId = $_SESSION['pending_user_id'] ?? (auth_id() ?: null);
        if (!$userId) {
            redirect('/login');
        }

        $user = User::find($userId);
        if (!$user) {
            redirect('/register');
        }

        $rateKey = "otp_resend_{$userId}";
        if (RateLimiter::tooManyAttempts($rateKey, 3)) {
            redirect('/verify-otp', ['error' => 'Please wait a moment before requesting another OTP.']);
        }

        RateLimiter::hit($rateKey, 180);
        $otp = OtpCode::generate($userId, 'registration');
        Mailer::sendOtp($user['email'], $user['full_name'], $otp, 'registration');

        redirect('/verify-otp', ['success' => "A fresh 6-digit code has been sent to {$user['email']}."]);
    }

    /**
     * Show Login Form
     */
    public function showLogin(): void
    {
        $this->render('pages/auth/login', [
            'title' => 'Sign In - CampusLend',
            'campusName' => config('app.campus_name', 'Uka Tarsadia University'),
            'campusDomain' => config('app.email_domain', 'utu.ac.in'),
        ]);
    }

    /**
     * Process Login
     */
    public function handleLogin(): void
    {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $remember = !empty($_POST['remember']);

        $rateKey = "login_{$email}";
        if (RateLimiter::tooManyAttempts($rateKey, config('auth.rate_limits.login.max_attempts', 5))) {
            $seconds = RateLimiter::availableIn($rateKey);
            redirect('/login', ['error' => "Too many failed login attempts. Please try again in {$seconds} seconds."]);
        }

        $user = User::findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            RateLimiter::hit($rateKey, config('auth.rate_limits.login.decay_seconds', 300));
            redirect('/login', ['error' => 'Invalid campus email address or password.']);
        }

        if ((int)$user['is_banned'] === 1) {
            redirect('/login', ['error' => 'Your account has been suspended for policy violations. Please contact campus admin.']);
        }

        // Login successful -> clear rate limit
        RateLimiter::clear($rateKey);

        // Secure session handling
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['last_activity'] = time();

        // Handle Remember Me token
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            User::setRememberToken($user['id'], $token);
            $days = config('auth.remember_token_days', 30);
            setcookie('campuslend_remember', $token, [
                'expires' => time() + ($days * 86400),
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            ]);
        }

        // Redirect based on role or verification state
        if ((int)$user['is_verified'] === 0) {
            $_SESSION['pending_user_id'] = $user['id'];
            redirect('/verify-otp', ['warning' => 'Please verify your campus email to unlock all features.']);
        }

        if ($user['role'] === 'admin') {
            redirect('/admin/dashboard', ['success' => "Welcome back, Administrator {$user['full_name']}!"]);
        }

        redirect('/dashboard', ['success' => "Welcome back, {$user['full_name']}!"]);
    }

    /**
     * Process Logout
     */
    public function logout(): void
    {
        $userId = auth_id();
        if ($userId) {
            User::setRememberToken($userId, null);
        }

        // Delete remember cookie
        if (isset($_COOKIE['campuslend_remember'])) {
            setcookie('campuslend_remember', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        redirect('/login', ['info' => 'You have been safely signed out.']);
    }

    /**
     * Show Forgot Password Form
     */
    public function showForgotPassword(): void
    {
        $this->render('pages/auth/forgot_password', [
            'title' => 'Forgot Password - CampusLend',
            'campusDomain' => config('app.email_domain', 'utu.ac.in'),
        ]);
    }

    /**
     * Handle Forgot Password Request
     */
    public function handleForgotPassword(): void
    {
        $email = strtolower(trim($_POST['email'] ?? ''));

        $rateKey = "pwd_reset_{$email}";
        if (RateLimiter::tooManyAttempts($rateKey, config('auth.rate_limits.password_reset.max_attempts', 3))) {
            redirect('/forgot-password', ['error' => 'Too many reset requests. Please wait a few minutes.']);
        }

        RateLimiter::hit($rateKey, 900);

        $user = User::findByEmail($email);
        if ($user) {
            $token = PasswordReset::createToken($user['id']);
            Mailer::sendPasswordResetLink($user['email'], $user['full_name'], $token);

            // Store in session for quick test access if in simulation mode
            $_SESSION['last_reset_token'] = $token;
        }

        // Always show positive message to prevent user enumeration
        redirect('/forgot-password', [
            'success' => "If that campus email exists in our records, a password reset link has been dispatched."
        ]);
    }

    /**
     * Show Reset Password Form
     */
    public function showResetPassword(): void
    {
        $token = trim($_GET['token'] ?? '');
        $reset = PasswordReset::verifyToken($token);

        if (!$reset) {
            redirect('/forgot-password', ['error' => 'This password reset link is invalid or has expired. Please request a new one.']);
        }

        $this->render('pages/auth/reset_password', [
            'title' => 'Set New Password - CampusLend',
            'token' => $token,
        ]);
    }

    /**
     * Process Reset Password
     */
    public function handleResetPassword(): void
    {
        $token = trim($_POST['token'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $passwordConfirm = (string)($_POST['password_confirm'] ?? '');

        $reset = PasswordReset::verifyToken($token);
        if (!$reset) {
            redirect('/forgot-password', ['error' => 'Password reset token has expired. Please try again.']);
        }

        if (strlen($password) < 6) {
            redirect('/reset-password?token=' . urlencode($token), ['error' => 'New password must be at least 6 characters long.']);
        }

        if ($password !== $passwordConfirm) {
            redirect('/reset-password?token=' . urlencode($token), ['error' => 'Passwords do not match. Please verify.']);
        }

        // Update password and invalidate token
        User::updatePassword((int)$reset['user_id'], $password);
        PasswordReset::markUsed($token);

        redirect('/login', ['success' => 'Your password has been reset successfully! You can now sign in.']);
    }
}
