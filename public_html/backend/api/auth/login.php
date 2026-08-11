<?php
// ==========================================================================
// BRIO WORLD SCHOOL - User Login API Endpoint
// ==========================================================================

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_helper.php';

handleCORS();

$input = getRequestInput();
$email = strtolower(cleanInput($input['email'] ?? ''));
$password = cleanInput($input['password'] ?? '');

if (empty($email) || empty($password)) {
    sendJSONResponse(false, 'Email and password are required', [], 400);
}

try {
    $db = getCoreDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    $hashedPass = hashPassword($password);

    if (!$user || ($user['password'] !== $hashedPass && $user['password'] !== sha1($password . 'brio_salt_2026'))) {
        sendJSONResponse(false, 'Invalid email address or password', [], 401);
    }

    if (empty($user['is_verified'])) {
        sendJSONResponse(false, 'Account email is not verified. Please verify your OTP.', ['needs_verification' => true, 'email' => $email], 403);
    }

    AuthHelper::setUserSession($user);

    sendJSONResponse(true, 'Login successful!', [
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'] ?? 'user'
        ]
    ]);

} catch (Exception $e) {
    sendJSONResponse(false, 'Database authentication error: ' . $e->getMessage(), [], 500);
}
