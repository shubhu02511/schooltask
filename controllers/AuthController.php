<?php
// Authentication Controller handling user registration, login, and OTP verification
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../mail_helper.php';

class AuthController {

    // Register user & send OTP
    public function register() {
        header('Content-Type: application/json');
        try {
            $db = getDB();

            $rawInput = !empty($GLOBALS['RAW_INPUT']) ? $GLOBALS['RAW_INPUT'] : @file_get_contents('php://input');
            $json = [];

            // 1. Try decoding raw JSON body
            if (!empty($rawInput)) {
                $decodedJson = @json_decode($rawInput, true);
                if (is_array($decodedJson)) {
                    $json = array_merge($json, $decodedJson);
                }
            }

            // 2. Try decoding base64 data field
            $dataRaw = $_POST['data'] ?? $_REQUEST['data'] ?? null;
            if (empty($dataRaw) && !empty($rawInput)) {
                parse_str($rawInput, $parsedParams);
                $dataRaw = $parsedParams['data'] ?? null;
            }
            if (!empty($dataRaw)) {
                $cleanB64 = str_replace(' ', '+', urldecode($dataRaw));
                $decodedB64 = @json_decode(base64_decode($cleanB64), true);
                if (is_array($decodedB64)) {
                    $json = array_merge($json, $decodedB64);
                }
            }

            // 3. Fallback to $_POST and $_REQUEST
            $name = trim($json['name'] ?? $_POST['name'] ?? $_REQUEST['name'] ?? '');
            $email = strtolower(trim($json['email'] ?? $_POST['email'] ?? $_REQUEST['email'] ?? ''));
            $password = $json['password'] ?? $_POST['password'] ?? $_REQUEST['password'] ?? '';

            if (empty($name) || empty($email) || empty($password)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Name, email, and password are required',
                    'debug_raw' => $rawInput,
                    'debug_post' => $_POST,
                    'debug_req' => $_REQUEST
                ]);
                exit;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email address format']);
                exit;
            }

            // Check if verified user exists
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $existing = $stmt->fetch();

            if (is_array($existing) && !empty($existing['is_verified']) && $existing['is_verified'] == 1) {
                echo json_encode(['success' => false, 'message' => 'Account already exists. Please login.']);
                exit;
            }

            $otp = generateOTP();
            $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $hashedPassword = sha1($password . 'brio_salt_2026');

            if (is_array($existing)) {
                // Update unverified user
                $update = $db->prepare("UPDATE users SET name = ?, password = ?, otp_code = ?, otp_expires = ? WHERE email = ?");
                $update->execute([$name, $hashedPassword, $otp, $expires, $email]);
            } else {
                // Create unverified user
                $insert = $db->prepare("INSERT INTO users (name, email, password, otp_code, otp_expires, is_verified) VALUES (?, ?, ?, ?, ?, 0)");
                $insert->execute([$name, $email, $hashedPassword, $otp, $expires]);
            }

            sendOTPEmail($email, $otp);

            echo json_encode([
                'success' => true,
                'message' => 'Registration OTP sent to your email (' . $email . '). Please verify.',
                'email' => $email,
                'otp_demo' => $otp
            ]);
            exit;
        } catch (Throwable $t) {
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $t->getMessage()]);
            exit;
        }
    }

    // Verify Email OTP
    public function verifyOTP() {
        header('Content-Type: application/json');
        $db = getDB();

        $rawInput = $GLOBALS['RAW_INPUT'] ?? @file_get_contents('php://input');
        $json = [];
        if (!empty($rawInput)) {
            $decodedJson = @json_decode($rawInput, true);
            if (is_array($decodedJson)) {
                $json = array_merge($json, $decodedJson);
            }
        }
        $dataRaw = $_POST['data'] ?? $_REQUEST['data'] ?? null;
        if (empty($dataRaw) && !empty($rawInput)) {
            parse_str($rawInput, $parsedParams);
            $dataRaw = $parsedParams['data'] ?? null;
        }
        if (!empty($dataRaw)) {
            $cleanB64 = str_replace(' ', '+', urldecode($dataRaw));
            $decodedB64 = @json_decode(base64_decode($cleanB64), true);
            if (is_array($decodedB64)) {
                $json = array_merge($json, $decodedB64);
            }
        }

        $email = strtolower(trim($json['email'] ?? $_POST['email'] ?? $_REQUEST['email'] ?? ''));
        $otp = trim($json['otp_code'] ?? $_POST['otp_code'] ?? $_REQUEST['otp_code'] ?? '');

        if (empty($email) || empty($otp)) {
            echo json_encode(['success' => false, 'message' => 'Email and OTP code are required']);
            exit;
        }

        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }

        // Check OTP match (allow generated OTP, session OTP, or master demo OTP 123456)
        $sessionOTP = $_SESSION['latest_otp_' . $email]['code'] ?? null;
        if ($user['otp_code'] !== $otp && $sessionOTP !== $otp && $otp !== '123456') {
            echo json_encode(['success' => false, 'message' => 'Invalid OTP code entered']);
            exit;
        }

        // Mark as verified
        $update = $db->prepare("UPDATE users SET is_verified = 1, otp_code = NULL, otp_expires = NULL WHERE id = ?");
        $update->execute([$user['id']]);

        // Auto login session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];

        echo json_encode([
            'success' => true,
            'message' => 'Account verified and activated successfully!',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email']
            ]
        ]);
        exit;
    }

    // Login user
    public function login() {
        header('Content-Type: application/json');
        $db = getDB();

        $rawInput = $GLOBALS['RAW_INPUT'] ?? @file_get_contents('php://input');
        $json = [];
        if (!empty($rawInput)) {
            $decodedJson = @json_decode($rawInput, true);
            if (is_array($decodedJson)) {
                $json = array_merge($json, $decodedJson);
            }
        }
        $dataRaw = $_POST['data'] ?? $_REQUEST['data'] ?? null;
        if (empty($dataRaw) && !empty($rawInput)) {
            parse_str($rawInput, $parsedParams);
            $dataRaw = $parsedParams['data'] ?? null;
        }
        if (!empty($dataRaw)) {
            $cleanB64 = str_replace(' ', '+', urldecode($dataRaw));
            $decodedB64 = @json_decode(base64_decode($cleanB64), true);
            if (is_array($decodedB64)) {
                $json = array_merge($json, $decodedB64);
            }
        }

        $email = strtolower(trim($json['email'] ?? $_POST['email'] ?? $_REQUEST['email'] ?? ''));
        $password = $json['password'] ?? $_POST['password'] ?? $_REQUEST['password'] ?? '';

        if (empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Email and password are required']);
            exit;
        }

        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
            exit;
        }

        $isMatch = ($user['password'] === sha1($password . 'brio_salt_2026')) || password_verify($password, $user['password']);

        if (!$isMatch) {
            echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
            exit;
        }

        if (empty($user['is_verified']) || $user['is_verified'] == 0) {
            // Re-send OTP if unverified
            $otp = generateOTP();
            $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $update = $db->prepare("UPDATE users SET otp_code = ?, otp_expires = ? WHERE id = ?");
            $update->execute([$otp, $expires, $user['id']]);

            sendOTPEmail($email, $otp);

            echo json_encode([
                'success' => false,
                'require_otp' => true,
                'message' => 'Account unverified. A new OTP has been sent to your email (' . $email . ').',
                'email' => $email,
                'otp_demo' => $otp
            ]);
            exit;
        }

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];

        echo json_encode([
            'success' => true,
            'message' => 'Login successful!',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email']
            ]
        ]);
        exit;
    }

    // Get current logged-in user
    public function me() {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (isset($_SESSION['user_id'])) {
            echo json_encode([
                'logged_in' => true,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'name' => $_SESSION['user_name'] ?? 'User',
                    'email' => $_SESSION['user_email'] ?? ''
                ]
            ]);
            exit;
        }
        echo json_encode(['logged_in' => false]);
        exit;
    }

    // Logout
    public function logout() {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
        exit;
    }
}
