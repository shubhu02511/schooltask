<?php
// ==========================================================================
// BRIO WORLD SCHOOL - Verify OTP API Endpoint
// ==========================================================================

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_helper.php';

handleCORS();

$input = getRequestInput();
$email = strtolower(cleanInput($input['email'] ?? ''));
$otpCode = cleanInput($input['otp_code'] ?? $input['otp'] ?? '');

if (empty($email) || empty($otpCode)) {
    sendJSONResponse(false, 'Email and OTP code are required', [], 400);
}

try {
    $db = getCoreDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        sendJSONResponse(false, 'Account not found for email: ' . $email, [], 404);
    }

    $sessionOTP = $_SESSION['latest_otp_' . $email]['code'] ?? null;

    if ($user['otp_code'] !== $otpCode && $sessionOTP !== $otpCode) {
        sendJSONResponse(false, 'Invalid OTP verification code. Please check your email.', [], 400);
    }

    // Mark user verified
    $updateStmt = $db->prepare("UPDATE users SET is_verified = 1, otp_code = NULL, otp_expires = NULL WHERE id = ?");
    $updateStmt->execute([$user['id']]);

    unset($_SESSION['latest_otp_' . $email]);

    $user['is_verified'] = 1;
    AuthHelper::setUserSession($user);

    sendJSONResponse(true, 'Email verification successful! Account is now active.', [
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'] ?? 'user'
        ]
    ]);

} catch (Exception $e) {
    sendJSONResponse(false, 'OTP verification error: ' . $e->getMessage(), [], 500);
}
