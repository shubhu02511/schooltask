<?php
// Mail and OTP helper
require_once __DIR__ . '/config.php';

function generateOTP() {
    return (string)rand(100000, 999999);
}

function sendOTPEmail($toEmail, $otpCode, $subject = "Your BRIO World School Verification OTP") {
    $message = "Hello,\n\nYour 6-digit OTP verification code is: {$otpCode}\n\nThis code is valid for 10 minutes. Please do not share it with anyone.\n\nRegards,\nBRIO World School Admissions Team";
    
    $headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_USER . ">\r\n";
    $headers .= "Reply-To: " . SMTP_USER . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    // Store in session fallback
    $_SESSION['latest_otp_' . strtolower(trim($toEmail))] = [
        'code' => $otpCode,
        'expires' => time() + 600
    ];

    // Attempt mail dispatch
    @mail($toEmail, $subject, $message, $headers);
    return true;
}
