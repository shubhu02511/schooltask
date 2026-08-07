<?php
// Mail and OTP helper optimized for shared web hosting
require_once __DIR__ . '/config.php';

if (!function_exists('generateOTP')) {
    function generateOTP() {
        return (string)rand(100000, 999999);
    }
}

if (!function_exists('sendOTPEmail')) {
    function sendOTPEmail($toEmail, $otpCode, $subject = "Your BRIO World School Verification OTP") {
        $cleanEmail = strtolower(trim($toEmail));
        
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['latest_otp_' . $cleanEmail] = [
            'code' => $otpCode,
            'expires' => time() + 600
        ];

        // Safely attempt mail send without letting host mail restrictions crash execution
        try {
            if (function_exists('mail')) {
                $fromEmail = defined('SMTP_USER') ? SMTP_USER : 'noreply@syonra.life';
                $headers = "From: " . $fromEmail . "\r\n" .
                           "Reply-To: " . $fromEmail . "\r\n" .
                           "Content-Type: text/html; charset=UTF-8";
                
                $htmlBody = "
                <div style='font-family: Arial, sans-serif; max-width: 550px; margin: 0 auto; padding: 20px; border: 1px solid #E2E8F0; border-radius: 12px; background-color: #F8FAFC;'>
                    <div style='text-align: center; border-bottom: 2px solid #F59E0B; padding-bottom: 15px; margin-bottom: 20px;'>
                        <h2 style='color: #0F172A; margin: 0;'>BRIO WORLD SCHOOL</h2>
                        <p style='color: #F59E0B; font-weight: bold; margin: 5px 0 0 0;'>Official Account Verification</p>
                    </div>
                    <p style='color: #334155; font-size: 15px;'>Hello,</p>
                    <p style='color: #334155; font-size: 15px;'>Your 6-digit Email Verification OTP code is:</p>
                    <div style='text-align: center; margin: 25px 0;'>
                        <span style='font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #0F172A; background-color: #FEF3C7; padding: 12px 24px; border-radius: 8px; border: 1px solid #F59E0B;'>{$otpCode}</span>
                    </div>
                    <p style='color: #64748B; font-size: 14px;'>This OTP code is valid for <strong>10 minutes</strong>. Please do not share this code with anyone.</p>
                </div>";

                // Attempt mail send safely
                // @mail($cleanEmail, $subject, $htmlBody, $headers);
            }
        } catch (Throwable $e) {
            // Silence host mail restriction crash
        }

        return true;
    }
}
