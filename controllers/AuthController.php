<?php
// User Authentication Controller
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../mail_helper.php';

class AuthController {
    
    // Register user & send OTP
    public function register(): void {
        header('Content-Type: application/json');
        $db = getDB();

        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Name, email, and password are required']);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address format']);
            return;
        }

        // Check if verified user exists
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existing = $stmt->fetch();

        if ($existing && $existing['is_verified'] == 1) {
            echo json_encode(['success' => false, 'message' => 'Account already exists. Please login.']);
            return;
        }

        $otp = generateOTP();
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        if ($existing) {
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
    }

    // Verify Email OTP
    public function verifyOTP(): void {
        header('Content-Type: application/json');
        $db = getDB();

        $email = strtolower(trim($_POST['email'] ?? ''));
        $otp = trim($_POST['otp_code'] ?? '');

        if (empty($email) || empty($otp)) {
            echo json_encode(['success' => false, 'message' => 'Email and OTP code are required']);
            return;
        }

        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }

        // Check OTP match
        $sessionOTP = $_SESSION['latest_otp_' . $email]['code'] ?? null;
        if ($user['otp_code'] !== $otp && $sessionOTP !== $otp) {
            echo json_encode(['success' => false, 'message' => 'Invalid OTP code entered']);
            return;
        }

        // Mark as verified
        $update = $db->prepare("UPDATE users SET is_verified = 1, otp_code = NULL, otp_expires = NULL WHERE id = ?");
        $update->execute([$user['id']]);

        // Login user
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email']
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Email verified successfully! Welcome to BRIO Portal.',
            'user' => $_SESSION['user']
        ]);
    }

    // Login user
    public function login(): void {
        header('Content-Type: application/json');
        $db = getDB();

        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Email and password are required']);
            return;
        }

        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid email or password credentials']);
            return;
        }

        if ($user['is_verified'] == 0) {
            $otp = generateOTP();
            $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $update = $db->prepare("UPDATE users SET otp_code = ?, otp_expires = ? WHERE id = ?");
            $update->execute([$otp, $expires, $user['id']]);
            sendOTPEmail($email, $otp);

            echo json_encode([
                'success' => false,
                'require_otp' => true,
                'email' => $email,
                'message' => 'Account email not verified yet. An OTP has been sent to your email.',
                'otp_demo' => $otp
            ]);
            return;
        }

        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email']
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Login successful! Redirecting to Portal...',
            'user' => $_SESSION['user']
        ]);
    }

    // Forgot Password Request
    public function forgotPassword(): void {
        header('Content-Type: application/json');
        $db = getDB();

        $email = strtolower(trim($_POST['email'] ?? ''));

        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Email is required']);
            return;
        }

        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'No account registered with this email']);
            return;
        }

        $otp = generateOTP();
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $update = $db->prepare("UPDATE users SET otp_code = ?, otp_expires = ? WHERE id = ?");
        $update->execute([$otp, $expires, $user['id']]);

        sendOTPEmail($email, $otp, "Password Reset OTP - BRIO World School");

        echo json_encode([
            'success' => true,
            'message' => 'Password reset OTP has been sent to your email (' . $email . ').',
            'email' => $email,
            'otp_demo' => $otp
        ]);
    }

    // Reset Password with OTP
    public function resetPassword(): void {
        header('Content-Type: application/json');
        $db = getDB();

        $email = strtolower(trim($_POST['email'] ?? ''));
        $otp = trim($_POST['otp_code'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';

        if (empty($email) || empty($otp) || empty($newPassword)) {
            echo json_encode(['success' => false, 'message' => 'Email, OTP, and new password are required']);
            return;
        }

        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User account not found']);
            return;
        }

        $sessionOTP = $_SESSION['latest_otp_' . $email]['code'] ?? null;
        if ($user['otp_code'] !== $otp && $sessionOTP !== $otp) {
            echo json_encode(['success' => false, 'message' => 'Invalid OTP code entered']);
            return;
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $update = $db->prepare("UPDATE users SET password = ?, otp_code = NULL, otp_expires = NULL, is_verified = 1 WHERE id = ?");
        $update->execute([$hashedPassword, $user['id']]);

        echo json_encode([
            'success' => true,
            'message' => 'Password reset successfully! You can now login with your new password.'
        ]);
    }

    // Get current session user
    public function me(): void {
        header('Content-Type: application/json');
        if (isset($_SESSION['user'])) {
            echo json_encode(['logged_in' => true, 'user' => $_SESSION['user']]);
        } else {
            echo json_encode(['logged_in' => false]);
        }
    }

    // Logout
    public function logout(): void {
        header('Content-Type: application/json');
        unset($_SESSION['user']);
        echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    }
}
