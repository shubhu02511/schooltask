<?php
@ob_start();
if (function_exists('opcache_reset')) { @opcache_reset(); }
if (function_exists('clearstatcache')) { @clearstatcache(true); }

// Production Security & Environment Settings
ini_set('display_errors', 0);
error_reporting(0);

// Load Private Configuration & Dependencies from app/
$appDir = file_exists(__DIR__ . '/app/config/config.php') ? __DIR__ . '/app' : __DIR__ . '/../app';
require_once $appDir . '/config/config.php';
require_once $appDir . '/config/db.php';
require_once $appDir . '/helpers/mail_helper.php';
require_once $appDir . '/controllers/AuthController.php';

header('Content-Type: application/json');

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if (!empty($_POST) || !empty($GLOBALS['RAW_INPUT']) || !empty($_SERVER['CONTENT_LENGTH'])) {
    $method = 'POST';
}
$rawUri = $_SERVER['REQUEST_URI'] ?? $_SERVER['REDIRECT_URL'] ?? '/';
$uri = parse_url($rawUri, PHP_URL_PATH);
$uri = preg_replace('#^/index\.php#i', '', $uri);
if (strpos($uri, '/api') !== 0) {
    $uri = '/api' . (strpos($uri, '/') === 0 ? '' : '/') . $uri;
}
$uri = rtrim($uri, '/') ?: '/';

$authController = new AuthController();

// Route Dispatcher
if ($method === 'GET' && ($uri === '/api/auth/me' || $uri === '/api/auth/user')) {
    $authController->me();
} elseif ($method === 'POST' && str_contains($uri, 'register')) {
    $authController->register();
} elseif ($method === 'POST' && str_contains($uri, 'verify-otp')) {
    $authController->verifyOTP();
} elseif ($method === 'POST' && str_contains($uri, 'login')) {
    $authController->login();
} elseif ($method === 'POST' && str_contains($uri, 'forgot-password')) {
    header('Content-Type: application/json');
    $db = getDB();
    $input = AuthController::extractInput();
    $email = strtolower(trim($input['email'] ?? ''));

    if (empty($email)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email address is required']);
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Email address not found']);
        exit;
    }

    $cooldown = checkOTPCooldown($email);
    if ($cooldown > 0) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => "Please wait {$cooldown} seconds before requesting a new OTP."]);
        exit;
    }

    $otp = generateOTP();
    $expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));

    $update = $db->prepare("UPDATE users SET otp_code = ?, otp_expires = ? WHERE id = ?");
    $update->execute([$otp, $expires, $user['id']]);

    $sent = sendOTPEmail($email, $otp, "Password Reset OTP - BRIO World School");
    if (!$sent) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to send OTP email. Please try again later.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Password reset OTP sent to your email address (' . $email . '). Please check your inbox.',
        'email' => $email
    ]);
    exit;
} elseif ($method === 'POST' && str_contains($uri, 'reset-password')) {
    header('Content-Type: application/json');
    $db = getDB();
    $input = AuthController::extractInput();
    $email = strtolower(trim($input['email'] ?? ''));
    $otp = trim($input['otp_code'] ?? '');
    $newPass = $input['new_password'] ?? '';

    if (empty($email) || empty($otp) || empty($newPass)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email, OTP, and new password are required']);
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $sessionOTP = $_SESSION['latest_otp_' . $email]['code'] ?? null;
    if ($user['otp_code'] !== $otp && $sessionOTP !== $otp) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid OTP code']);
        exit;
    }

    $sessionExpires = $_SESSION['latest_otp_' . $email]['expires'] ?? 0;
    $dbExpires = !empty($user['otp_expires']) ? strtotime($user['otp_expires']) : 0;
    $maxExpires = max($sessionExpires, $dbExpires);
    if ($maxExpires > 0 && time() > $maxExpires) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'OTP code has expired. Please request a new one.']);
        exit;
    }

    unset($_SESSION['latest_otp_' . $email]);
    $hashedPassword = sha1($newPass . 'brio_salt_2026');
    $update = $db->prepare("UPDATE users SET password = ?, is_verified = 1, otp_code = NULL, otp_expires = NULL WHERE id = ?");
    $update->execute([$hashedPassword, $user['id']]);

    echo json_encode(['success' => true, 'message' => 'Password reset successfully! You can now login.']);
    exit;
} elseif ($method === 'POST' && str_contains($uri, 'logout')) {
    $authController->logout();
} elseif ($method === 'POST' && str_contains($uri, 'career')) {
    echo json_encode(['success' => true, 'message' => 'Career application submitted successfully!']);
    exit;
}

http_response_code(404);
echo json_encode(['success' => false, 'message' => 'API Endpoint Not Found: ' . $uri]);
exit;
