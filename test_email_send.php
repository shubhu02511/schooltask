<?php
// Diagnostic Native cPanel Mailer Test Script
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';

$to = isset($_GET['email']) ? trim($_GET['email']) : 'shubhamchaurasiya2025@gmail.com';
$otp = strval(rand(100000, 999999));
$subject = "Live Test Verification OTP Code: " . $otp;
$from = defined('SMTP_USER') ? SMTP_USER : 'noreply@syonra.life';

$headers = "From: BRIO World School <{$from}>\r\n" .
           "Reply-To: {$from}\r\n" .
           "MIME-Version: 1.0\r\n" .
           "Content-Type: text/html; charset=UTF-8\r\n" .
           "X-Mailer: PHP/" . phpversion();

$body = "<div style='font-family: Arial, sans-serif; max-width: 550px; margin: 0 auto; padding: 20px; border: 1px solid #E2E8F0; border-radius: 12px; background-color: #F8FAFC;'>
    <div style='text-align: center; border-bottom: 2px solid #F59E0B; padding-bottom: 15px; margin-bottom: 20px;'>
        <h2 style='color: #0F172A; margin: 0;'>BRIO WORLD SCHOOL</h2>
        <p style='color: #F59E0B; font-weight: bold; margin: 5px 0 0 0;'>Official Account Verification</p>
    </div>
    <p style='color: #334155; font-size: 15px;'>Hello,</p>
    <p style='color: #334155; font-size: 15px;'>Your 6-digit Email Verification OTP code is:</p>
    <div style='text-align: center; margin: 25px 0;'>
        <span style='font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #0F172A; background-color: #FEF3C7; padding: 12px 24px; border-radius: 8px; border: 1px solid #F59E0B;'>{$otp}</span>
    </div>
    <p style='color: #64748B; font-size: 14px;'>This OTP code is valid for <strong>10 minutes</strong>. Please do not share this code with anyone.</p>
</div>";

$mailSent = @mail($to, $subject, $body, $headers, "-f" . $from);

echo json_encode([
    'success' => $mailSent,
    'message' => $mailSent ? "Email dispatched successfully to {$to}" : "Mail dispatch failed",
    'recipient' => $to,
    'sender' => $from,
    'timestamp' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT);
