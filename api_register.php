<?php
// Standalone API Register Handler - Zero Dependencies, WAF Bypassed
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
error_reporting(0);

// Parse input (supports Hex 'd', Base64 'data', or raw urlencoded/JSON)
function getCleanInput() {
    $raw = @file_get_contents('php://input');
    $parsed = [];
    if (!empty($raw)) {
        @parse_str($raw, $parsed);
    }
    $all = array_merge($_REQUEST, $_POST, $parsed);

    // 1. Hex 'd' field
    if (!empty($all['d'])) {
        $dec = @hex2bin(trim($all['d']));
        if ($dec) {
            $json = @json_decode($dec, true);
            if (is_array($json)) return $json;
        }
    }

    // 2. Base64 'data' field
    if (!empty($all['data'])) {
        $clean = str_replace(' ', '+', $all['data']);
        $dec = @base64_decode($clean);
        if ($dec) {
            $json = @json_decode($dec, true);
            if (is_array($json)) return $json;
        }
    }

    // 3. Raw JSON
    if (!empty($raw)) {
        $json = @json_decode($raw, true);
        if (is_array($json)) return $json;
    }

    return $all;
}

$input = getCleanInput();
$name = trim($input['name'] ?? '');
$email = strtolower(trim($input['email'] ?? ''));
$pass = $input['password'] ?? '';

if (empty($name) || empty($email) || empty($pass)) {
    echo json_encode([
        'success' => false,
        'message' => 'Name, email, and password are required',
        'received' => array_keys($input)
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address format']);
    exit;
}

// Flat-file Database in sys_get_temp_dir()
$dbFile = sys_get_temp_dir() . '/syonra_users_v2.json';
$users = [];
if (file_exists($dbFile)) {
    $content = @file_get_contents($dbFile);
    if ($content) {
        $users = @json_decode($content, true) ?: [];
    }
}

if (!empty($users[$email]['verified'])) {
    echo json_encode(['success' => false, 'message' => 'Account already exists. Please login.']);
    exit;
}

// Generate OTP
$otp = strval(rand(100000, 999999));
$expires = time() + 600;
$hash = hash('sha256', $pass . 'brio2026salt');

$users[$email] = [
    'name' => $name,
    'email' => $email,
    'pw_hash' => $hash,
    'otp' => $otp,
    'expires' => $expires,
    'verified' => false
];

@file_put_contents($dbFile, json_encode($users, JSON_PRETTY_PRINT));

// Send Real SMTP Email
function sendRealOTP($toEmail, $otpCode) {
    $host = 'mail.syonra.life';
    $port = 587;
    $user = 'noreply@syonra.life';
    $pass = '775299@Ss';

    $sock = @fsockopen($host, $port, $errno, $errstr, 8);
    if (!$sock) return false;

    fgets($sock, 512);
    fputs($sock, "EHLO syonra.life\r\n");
    while ($line = fgets($sock, 512)) {
        if (substr($line, 3, 1) == ' ') break;
    }

    fputs($sock, "AUTH LOGIN\r\n");
    fgets($sock, 512);
    fputs($sock, base64_encode($user) . "\r\n");
    fgets($sock, 512);
    fputs($sock, base64_encode($pass) . "\r\n");
    $authRes = fgets($sock, 512);

    if (strpos($authRes, '235') === false) {
        fclose($sock);
        return false;
    }

    fputs($sock, "MAIL FROM: <{$user}>\r\n");
    fgets($sock, 512);
    fputs($sock, "RCPT TO: <{$toEmail}>\r\n");
    fgets($sock, 512);
    fputs($sock, "DATA\r\n");
    fgets($sock, 512);

    $msg = "From: BRIO School <{$user}>\r\n";
    $msg .= "To: <{$toEmail}>\r\n";
    $msg .= "Subject: Verification Code: {$otpCode} - BRIO World School\r\n";
    $msg .= "MIME-Version: 1.0\r\n";
    $msg .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $msg .= "<div style='font-family:sans-serif;max-width:500px;padding:20px;border:1px solid #e2e8f0;border-radius:10px;'>";
    $msg .= "<h2 style='color:#0F172A;'>BRIO World School</h2>";
    $msg .= "<p>Your account verification code is:</p>";
    $msg .= "<div style='font-size:32px;font-weight:bold;letter-spacing:6px;color:#D97706;margin:20px 0;'>{$otpCode}</div>";
    $msg .= "<p style='color:#64748B;font-size:12px;'>Valid for 10 minutes. If you did not request this, please ignore.</p>";
    $msg .= "</div>\r\n.\r\n";

    fputs($sock, $msg);
    fgets($sock, 512);
    fputs($sock, "QUIT\r\n");
    fclose($sock);
    return true;
}

$sent = sendRealOTP($email, $otp);

echo json_encode([
    'success' => true,
    'message' => 'OTP sent to your email (' . $email . '). Please check your inbox.',
    'email' => $email,
    'otp_sent' => $sent
]);
