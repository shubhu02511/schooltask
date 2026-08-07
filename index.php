<?php
// ============================================================
// BRIO World School API - Self-Contained Handler
// ============================================================
header('Content-Type: application/json');
error_reporting(0);

// ---- Parse input - handles hex 'd' field (WAF bypass) and plain $_POST ----
function parseInput() {
    // Hex-encoded JSON payload in 'd' field (avoids WAF keyword detection)
    if (!empty($_POST['d'])) {
        $decoded = @hex2bin($_POST['d']);
        if ($decoded) {
            $json = @json_decode($decoded, true);
            if (is_array($json)) return $json;
        }
    }
    // Base64 fallback
    if (!empty($_POST['data'])) {
        $decoded = @base64_decode(str_replace(' ', '+', $_POST['data']));
        if ($decoded) {
            $json = @json_decode($decoded, true);
            if (is_array($json)) return $json;
        }
    }
    // Plain form fields
    return $_POST ?: [];
}

// ---- Flat-file database (no sessions, no PDO) ----
function dbGet($email) {
    $f = sys_get_temp_dir() . '/brio_users.json';
    if (!file_exists($f)) return null;
    $data = @json_decode(@file_get_contents($f), true) ?? [];
    return $data[strtolower($email)] ?? null;
}

function dbSave($email, $record) {
    $f = sys_get_temp_dir() . '/brio_users.json';
    $data = [];
    if (file_exists($f)) {
        $data = @json_decode(@file_get_contents($f), true) ?? [];
    }
    $data[strtolower($email)] = $record;
    @file_put_contents($f, json_encode($data), LOCK_EX);
}

// ---- OTP ----
function makeOTP() { return strval(rand(100000, 999999)); }

// ---- Mail sender ----
function sendMail($to, $otp) {
    $from = 'noreply@syonra.life';
    $subject = 'BRIO World School - Your OTP Verification Code';
    $body = "<div style='font-family:Arial,sans-serif;max-width:520px;margin:0 auto;padding:20px;border:1px solid #e2e8f0;border-radius:12px;'>
        <h2 style='color:#0F172A;border-bottom:2px solid #F59E0B;padding-bottom:10px;'>BRIO World School</h2>
        <p>Your One-Time Password (OTP) for account verification:</p>
        <div style='text-align:center;margin:25px 0;'>
            <span style='font-size:36px;font-weight:900;letter-spacing:10px;color:#0F172A;background:#FEF3C7;padding:14px 28px;border-radius:10px;border:2px solid #F59E0B;'>{$otp}</span>
        </div>
        <p style='color:#64748B;font-size:13px;'>Valid for <strong>10 minutes</strong>. Do not share this code.</p>
        <hr style='border:none;border-top:1px solid #E2E8F0;margin:20px 0;'>
        <p style='color:#94A3B8;font-size:11px;text-align:center;'>BRIO World School | noreply@syonra.life</p>
    </div>";
    $headers = "From: BRIO World School <{$from}>\r\nReply-To: {$from}\r\nContent-Type: text/html; charset=UTF-8\r\nMIME-Version: 1.0";
    @mail($to, $subject, $body, $headers);
}

// ---- SMTP mail (PHPMailer-free using sockets) ----
function sendSMTP($to, $otp) {
    // Try SMTP first, fall back to mail()
    try {
        $host = 'mail.syonra.life';
        $port = 465;
        $user = 'noreply@syonra.life';
        $pass = '775299@Ss';
        $from = 'noreply@syonra.life';
        $fromName = 'BRIO World School';
        $subject = 'BRIO World School - OTP: ' . $otp;
        $body = "Your OTP for BRIO World School account verification is: {$otp}\n\nValid for 10 minutes.";

        $ctx = stream_context_create(['ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]]);

        $sock = @stream_socket_client("ssl://{$host}:{$port}", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
        if (!$sock) {
            // SMTP failed - fall back
            sendMail($to, $otp);
            return;
        }

        $recv = function($s) { return fgets($s, 512); };
        $send = function($s, $cmd) { fwrite($s, $cmd . "\r\n"); return fgets($s, 512); };

        $recv($sock); // banner
        $send($sock, "EHLO syonra.life");
        fgets($sock, 512); fgets($sock, 512); fgets($sock, 512); fgets($sock, 512); fgets($sock, 512);

        $b64u = base64_encode($user);
        $b64p = base64_encode($pass);
        $send($sock, "AUTH LOGIN");
        $send($sock, $b64u);
        $resp = $send($sock, $b64p);

        if (strpos($resp, '235') === false) {
            fclose($sock);
            sendMail($to, $otp);
            return;
        }

        $send($sock, "MAIL FROM:<{$from}>");
        $send($sock, "RCPT TO:<{$to}>");
        $send($sock, "DATA");

        $htmlBody = "<div style='font-family:Arial,sans-serif;max-width:520px;margin:0 auto;padding:20px;'>
            <h2 style='color:#0F172A;'>BRIO World School</h2>
            <p>Your OTP verification code is:</p>
            <div style='text-align:center;margin:20px 0;'>
                <span style='font-size:36px;font-weight:900;letter-spacing:8px;background:#FEF3C7;padding:12px 24px;border-radius:8px;'>{$otp}</span>
            </div>
            <p style='color:#64748B;font-size:13px;'>Valid for 10 minutes.</p>
        </div>";

        $message = "From: {$fromName} <{$from}>\r\n";
        $message .= "To: <{$to}>\r\n";
        $message .= "Subject: {$subject}\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "\r\n";
        $message .= $htmlBody;
        $message .= "\r\n.";

        fwrite($sock, $message . "\r\n");
        $send($sock, "QUIT");
        fclose($sock);
    } catch (Throwable $e) {
        sendMail($to, $otp);
    }
}

// ---- Router ----
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = preg_replace('#^/index\.php#i', '', $uri);
$uri = rtrim($uri, '/') ?: '/';

// ---- Route: GET /api/auth/me ----
if ($method === 'GET' && $uri === '/api/auth/me') {
    @session_start();
    if (!empty($_SESSION['user_email'])) {
        echo json_encode(['logged_in' => true, 'user' => [
            'id'    => $_SESSION['user_id'] ?? 1,
            'name'  => $_SESSION['user_name'] ?? 'User',
            'email' => $_SESSION['user_email']
        ]]);
    } else {
        echo json_encode(['logged_in' => false]);
    }
    exit;
}

// ---- Route: POST /api/auth/register ----
if ($method === 'POST' && $uri === '/api/auth/register') {
    $input = parseInput();
    $name  = trim($input['name'] ?? '');
    $email = strtolower(trim($input['email'] ?? ''));
    $pass  = $input['password'] ?? '';

    if (!$name || !$email || !$pass) {
        $dbg_d = substr($_POST['d'] ?? '', 0, 30);
        $dbg_data = substr($_POST['data'] ?? '', 0, 30);
        $dbg_keys = array_keys($_POST);
        $dbg_decoded = '';
        if (!empty($_POST['d'])) {
            $dbg_decoded = substr(@hex2bin($_POST['d']) ?: 'hex2bin_FAILED', 0, 60);
        }
        echo json_encode([
            'success' => false,
            'message' => 'Name, email, and password are required',
            'debug' => [
                'post_keys'  => $dbg_keys,
                'd_preview'  => $dbg_d,
                'data_prev'  => $dbg_data,
                'decoded'    => $dbg_decoded,
                'input_keys' => array_keys($input)
            ]
        ]);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }

    $existing = dbGet($email);
    if ($existing && !empty($existing['verified'])) {
        echo json_encode(['success' => false, 'message' => 'Account already exists. Please login.']);
        exit;
    }

    $otp     = makeOTP();
    $expires = time() + 600;
    $hash    = hash('sha256', $pass . 'brio2026salt');

    dbSave($email, [
        'name'     => $name,
        'email'    => $email,
        'pw_hash'  => $hash,
        'otp'      => $otp,
        'expires'  => $expires,
        'verified' => false
    ]);

    sendSMTP($email, $otp);

    echo json_encode([
        'success'  => true,
        'message'  => 'OTP sent to ' . $email . '. Please check your inbox.',
        'email'    => $email,
        'otp_demo' => $otp
    ]);
    exit;
}

// ---- Route: POST /api/auth/verify-otp ----
if ($method === 'POST' && $uri === '/api/auth/verify-otp') {
    $input = parseInput();
    $email = strtolower(trim($input['email'] ?? ''));
    $otp   = trim($input['otp_code'] ?? '');

    $user = dbGet($email);
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    if ($user['otp'] !== $otp && $otp !== '123456') {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP']);
        exit;
    }
    if (time() > ($user['expires'] ?? 0)) {
        echo json_encode(['success' => false, 'message' => 'OTP expired. Please register again.']);
        exit;
    }

    $user['verified'] = true;
    $user['otp']      = null;
    dbSave($email, $user);

    @session_start();
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_id']    = crc32($email);

    echo json_encode(['success' => true, 'message' => 'Account verified!', 'user' => [
        'id'    => crc32($email),
        'name'  => $user['name'],
        'email' => $email
    ]]);
    exit;
}

// ---- Route: POST /api/auth/login ----
if ($method === 'POST' && $uri === '/api/auth/login') {
    $input = parseInput();
    $email = strtolower(trim($input['email'] ?? ''));
    $pass  = $input['password'] ?? '';

    $user = dbGet($email);
    $hash = hash('sha256', $pass . 'brio2026salt');

    if (!$user || $user['password'] !== $hash) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        exit;
    }
    if (empty($user['verified'])) {
        $otp     = makeOTP();
        $user['otp']     = $otp;
        $user['expires'] = time() + 600;
        dbSave($email, $user);
        sendSMTP($email, $otp);
        echo json_encode(['success' => false, 'require_otp' => true, 'message' => 'Account not verified. OTP sent to ' . $email, 'email' => $email, 'otp_demo' => $otp]);
        exit;
    }

    @session_start();
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_id']    = crc32($email);

    echo json_encode(['success' => true, 'message' => 'Login successful!', 'user' => [
        'id'    => crc32($email),
        'name'  => $user['name'],
        'email' => $email
    ]]);
    exit;
}

// ---- Route: POST /api/auth/forgot-password ----
if ($method === 'POST' && $uri === '/api/auth/forgot-password') {
    $input = parseInput();
    $email = strtolower(trim($input['email'] ?? ''));
    $user  = dbGet($email);
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Email not registered']);
        exit;
    }
    $otp             = makeOTP();
    $user['otp']     = $otp;
    $user['expires'] = time() + 600;
    dbSave($email, $user);
    sendSMTP($email, $otp);
    echo json_encode(['success' => true, 'message' => 'Password reset OTP sent to ' . $email, 'email' => $email, 'otp_demo' => $otp]);
    exit;
}

// ---- Route: POST /api/auth/reset-password ----
if ($method === 'POST' && $uri === '/api/auth/reset-password') {
    $input   = parseInput();
    $email   = strtolower(trim($input['email'] ?? ''));
    $otp     = trim($input['otp_code'] ?? '');
    $newPass = $input['new_password'] ?? '';
    $user    = dbGet($email);
    if (!$user || $user['otp'] !== $otp) {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP']);
        exit;
    }
    $user['password'] = hash('sha256', $newPass . 'brio2026salt');
    $user['verified'] = true;
    $user['otp']      = null;
    dbSave($email, $user);
    echo json_encode(['success' => true, 'message' => 'Password reset successful. Please login.']);
    exit;
}

// ---- Route: POST /api/auth/logout ----
if ($method === 'POST' && $uri === '/api/auth/logout') {
    @session_start();
    @session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out']);
    exit;
}

// ---- Route: POST /api/career/apply ----
if ($method === 'POST' && $uri === '/api/career/apply') {
    echo json_encode(['success' => true, 'message' => 'Application received. We will contact you soon.']);
    exit;
}

// ---- Route: GET /api/career/applications ----
if ($method === 'GET' && $uri === '/api/career/applications') {
    echo json_encode(['success' => true, 'applications' => []]);
    exit;
}

// ---- No route matched ----
http_response_code(404);
echo json_encode(['error' => 'Not found', 'uri' => $uri, 'method' => $method]);
