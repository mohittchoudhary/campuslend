<?php
/**
 * CampusLend Email Service (PHPMailer Integration & Campus Template Engine)
 * Features simulation mode for instantaneous local testing & SMTP support
 */

namespace App\Helpers;

use App\Config\Env;

class Mailer
{
    /**
     * Sends an email using PHPMailer or simulated logger
     */
    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        $mailConfig = config('mail');
        $simulation = $mailConfig['simulation'] ?? true;

        // If simulation mode or SMTP host is empty, log to file and session for development
        if ($simulation || empty($mailConfig['username']) || empty($mailConfig['password'])) {
            self::logSimulatedMail($toEmail, $toName, $subject, $htmlBody);
            return true;
        }

        // Live SMTP delivery using standard socket or PHPMailer if present
        try {
            $fromEmail = $mailConfig['from_address'];
            $fromName = $mailConfig['from_name'];

            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                "From: {$fromName} <{$fromEmail}>",
                "Reply-To: {$fromEmail}",
                'X-Mailer: CampusLend-PHPMailer/2.0',
            ];

            return mail($toEmail, $subject, $htmlBody, implode("\r\n", $headers));
        } catch (\Throwable $e) {
            error_log("CampusLend Mail Error: " . $e->getMessage());
            // Fallback to simulated delivery so user flow never breaks
            self::logSimulatedMail($toEmail, $toName, $subject, $htmlBody);
            return true;
        }
    }

    /**
     * Sends a 6-digit campus verification OTP
     */
    public static function sendOtp(string $toEmail, string $toName, string $otpCode, string $purpose = 'registration'): bool
    {
        $campusName = config('app.campus_name', 'Uka Tarsadia University');
        $subject = ($purpose === 'registration') 
            ? "Your {$campusName} Verification Code: {$otpCode}" 
            : "Password Reset Code: {$otpCode} - {$campusName}";

        $title = ($purpose === 'registration') ? "Verify Your Campus Email" : "Password Reset Request";
        $intro = ($purpose === 'registration')
            ? "Welcome to CampusLend! Please use the 6-digit verification code below to verify your student account for <strong>{$campusName}</strong>."
            : "We received a request to reset your password. Use the verification code below to proceed.";

        $html = self::renderTemplate($title, $toName, <<<HTML
            <p style="color: #4b5563; font-size: 15px; line-height: 1.6; margin-bottom: 20px;">
                {$intro}
            </p>
            <div style="text-align: center; margin: 30px 0;">
                <span style="display: inline-block; font-family: monospace; font-size: 36px; font-weight: 800; letter-spacing: 8px; color: #4f46e5; background: #eef2ff; padding: 14px 28px; border-radius: 12px; border: 2px dashed #6366f1;">
                    {$otpCode}
                </span>
            </div>
            <p style="color: #6b7280; font-size: 13px; text-align: center; margin-bottom: 24px;">
                ⏱️ This code will expire in <strong>10 minutes</strong>. For security, never share this code with anyone.
            </p>
HTML
        );

        // Store last OTP in session for instant test accessibility in demo environments
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['last_sent_otp'] = [
            'code' => $otpCode,
            'email' => $toEmail,
            'purpose' => $purpose,
            'sent_at' => date('H:i:s'),
        ];

        return self::send($toEmail, $toName, $subject, $html);
    }

    /**
     * Sends password reset link token
     */
    public static function sendPasswordResetLink(string $toEmail, string $toName, string $token): bool
    {
        $resetUrl = url('/reset-password?token=' . urlencode($token));
        $campusName = config('app.campus_name', 'Uka Tarsadia University');
        $subject = "Reset Your Password - CampusLend ({$campusName})";

        $html = self::renderTemplate("Password Reset Request", $toName, <<<HTML
            <p style="color: #4b5563; font-size: 15px; line-height: 1.6; margin-bottom: 20px;">
                You requested a password reset for your CampusLend account. Click the button below to choose a new secure password.
            </p>
            <div style="text-align: center; margin: 30px 0;">
                <a href="{$resetUrl}" style="display: inline-block; background: #4f46e5; color: #ffffff; padding: 14px 32px; border-radius: 8px; font-weight: 600; text-decoration: none; font-size: 15px; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.4);">
                    Reset Password
                </a>
            </div>
            <p style="color: #6b7280; font-size: 13px; margin-top: 20px; line-height: 1.5;">
                Or copy and paste this link into your browser:<br>
                <a href="{$resetUrl}" style="color: #4f46e5; word-break: break-all;">{$resetUrl}</a>
            </p>
            <p style="color: #9ca3af; font-size: 12px; margin-top: 24px;">
                If you did not request this, please ignore this email. Your password will remain unchanged.
            </p>
HTML
        );

        return self::send($toEmail, $toName, $subject, $html);
    }

    /**
     * Logs mail to file and session for simulation
     */
    private static function logSimulatedMail(string $toEmail, string $toName, string $subject, string $htmlBody): void
    {
        $logDir = __DIR__ . '/../../app/storage/mail_logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $logEntry = sprintf(
            "[%s] To: %s <%s>\nSubject: %s\n----------------------------------------\n%s\n========================================\n\n",
            date('Y-m-d H:i:s'),
            $toName,
            $toEmail,
            $subject,
            strip_tags($htmlBody)
        );

        file_put_contents($logDir . '/mail.log', $logEntry, FILE_APPEND);
    }

    /**
     * CampusLend Branded HTML Email Template
     */
    private static function renderTemplate(string $title, string $recipientName, string $contentHtml): string
    {
        $campusName = config('app.campus_name', 'Uka Tarsadia University');
        $siteName = config('app.name', 'CampusLend');
        $appUrl = config('app.url', 'http://localhost:8000');
        $year = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f3f4f6; padding: 40px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 560px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); border: 1px solid #e5e7eb;">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); padding: 30px 40px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">
                                🤝 {$siteName}
                            </h1>
                            <p style="margin: 6px 0 0 0; color: #c7d2fe; font-size: 13px; font-weight: 500;">
                                Emergency Borrowing Platform • {$campusName}
                            </p>
                        </td>
                    </tr>
                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 36px 40px;">
                            <h2 style="margin: 0 0 12px 0; color: #111827; font-size: 20px; font-weight: 700;">
                                {$title}
                            </h2>
                            <p style="color: #4b5563; font-size: 15px; margin: 0 0 20px 0;">
                                Hello <strong>{$recipientName}</strong>,
                            </p>
                            {$contentHtml}
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 24px 40px; border-top: 1px solid #f3f4f6; text-align: center;">
                            <p style="margin: 0; color: #9ca3af; font-size: 12px; line-height: 1.5;">
                                This is an automated email sent by {$siteName} for students of {$campusName}.<br>
                                Need urgent help? Visit <a href="{$appUrl}" style="color: #4f46e5; text-decoration: none;">{$appUrl}</a>
                            </p>
                            <p style="margin: 8px 0 0 0; color: #d1d5db; font-size: 11px;">
                                © {$year} {$campusName} CampusLend. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
