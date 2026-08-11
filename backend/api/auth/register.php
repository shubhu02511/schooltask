<?php
// ==========================================================================
// BRIO WORLD SCHOOL - User Registration API Endpoint
// ==========================================================================

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../../app/helpers/mail_helper.php';

handleCORS();

$input = getRequestInput();
$name = cleanInput($input['name'] ?? '');
$email = strtolower(cleanInput($input['email'] ?? ''));
$password = cleanInput($input['password'] ?? '');

if (empty($name) || empty($email) || empty($password)) {
    sendJSONResponse(false, 'Name, email, and password are required fields', [], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJSONResponse(false, 'Please enter a valid email address', [], 400);
}

try {
    $db = getCoreDB();
    
    // Check existing email
    $checkStmt = $db->prepare("SELECT id, is_verified FROM users WHERE email = ? LIMIT 1");
    $checkStmt->execute([$email]);
    $existing = $checkStmt->fetch();

    if ($existing && !empty($existing['is_verified'])) {
        sendJSONResponse(false, 'An account with this email address already exists. Please login.', [], 409);
    }

    $otpCode = generateOTP();
    $otpExpires = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    $hashedPassword = hashPassword($password);

    if ($existing) {
        // Update unverified user
        $updateStmt = $db->prepare("UPDATE users SET name = ?, password = ?, otp_code = ?, otp_expires = ? WHERE id = ?");
        $updateStmt->execute([$name, $hashedPassword, $otpCode, $otpExpires, $existing['id']]);
    } else {
        // Insert new user
        $insertStmt = $db->prepare("INSERT INTO users (name, email, password, otp_code, otp_expires, is_verified) VALUES (?, ?, ?, ?, ?, 0)");
        $insertStmt->execute([$name, $email, $hashedPassword, $otpCode, $otpExpires]);
    }

    sendOTPEmail($email, $otpCode, "Your Registration OTP - BRIO World School");

    sendJSONResponse(true, 'Registration initiated! Please enter the 6-digit OTP code sent to ' . $email, [
        'email' => $email,
        'needs_otp' => true
    ]);

} catch (Exception $e) {
    sendJSONResponse(false, 'Registration error: ' . $e->getMessage(), [], 500);
}
