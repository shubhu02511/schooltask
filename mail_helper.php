<?php
// Mail and OTP helper optimized for shared web hosting
require_once __DIR__ . '/config.php';

function generateOTP() {
    return (string)rand(100000, 999999);
}

function sendOTPEmail($toEmail, $otpCode, $subject = "Your BRIO World School Verification OTP") {
    try {
        $cleanEmail = strtolower(trim($toEmail));
        
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['latest_otp_' . $cleanEmail] = [
            'code' => $otpCode,
            'expires' => time() + 600
        ];

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
            <hr style='border: none; border-top: 1px solid #E2E8F0; margin: 20px 0;'>
            <p style='color: #94A3B8; font-size: 12px; text-align: center;'>BRIO World School • Admissions & IT Support Team</p>
        </div>
        ";

        $fromEmail = defined('SMTP_USER') ? SMTP_USER : 'noreply@syonra.life';
        $headers = "From: {$fromEmail}\r\nReply-To: {$fromEmail}\r\nContent-Type: text/html; charset=UTF-8";

        @mail($cleanEmail, $subject, $htmlBody, $headers);
    } catch (Throwable $e) {
        // Fallback safety
    }
    return true;
}
