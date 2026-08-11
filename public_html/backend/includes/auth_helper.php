<?php
// ==========================================================================
// BRIO WORLD SCHOOL - Secure Session & Authentication Helper
// ==========================================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    @session_start();
}

class AuthHelper {
    
    // Check if user is logged in
    public static function isLoggedIn(): bool {
        return !empty($_SESSION['brio_user_id']) && !empty($_SESSION['brio_user_email']);
    }

    // Check if user is Admin
    public static function isAdmin(): bool {
        return self::isLoggedIn() && isset($_SESSION['brio_user_role']) && $_SESSION['brio_user_role'] === 'admin';
    }

    // Require Admin authentication for admin panel pages
    public static function requireAdmin() {
        if (!self::isAdmin()) {
            if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
                sendJSONResponse(false, 'Unauthorized: Admin access required', [], 403);
            } else {
                header('Location: login.php');
                exit;
            }
        }
    }

    // Set User Session Data
    public static function setUserSession(array $user) {
        $_SESSION['brio_user_id'] = $user['id'];
        $_SESSION['brio_user_name'] = $user['name'];
        $_SESSION['brio_user_email'] = $user['email'];
        $_SESSION['brio_user_role'] = $user['role'] ?? 'user';
        $_SESSION['brio_logged_in_at'] = time();
    }

    // Destroy User Session
    public static function logout() {
        unset($_SESSION['brio_user_id']);
        unset($_SESSION['brio_user_name']);
        unset($_SESSION['brio_user_email']);
        unset($_SESSION['brio_user_role']);
        unset($_SESSION['brio_logged_in_at']);
        @session_destroy();
    }
}
