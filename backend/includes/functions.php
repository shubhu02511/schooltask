<?php
// ==========================================================================
// BRIO WORLD SCHOOL - Core Utility Functions & Helpers
// ==========================================================================

require_once __DIR__ . '/../config/config.php';

// Send standardized JSON Response
if (!function_exists('sendJSONResponse')) {
    function sendJSONResponse(bool $success, string $message, array $extraData = [], int $httpCode = 200) {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(array_merge([
            'success' => $success,
            'message' => $message
        ], $extraData));
        exit;
    }
}

// Clean and sanitize string input
if (!function_exists('cleanInput')) {
    function cleanInput($data): string {
        if (is_array($data)) return '';
        $data = trim((string)$data);
        $data = stripslashes($data);
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
}

// Parse input from POST, GET, or JSON raw body
if (!function_exists('getRequestInput')) {
    function getRequestInput(): array {
        $input = [];
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $input = array_merge($input, $json);
            }
        }
        return array_merge($_REQUEST, $_POST, $input);
    }
}

// Enable CORS headers safely
if (!function_exists('handleCORS')) {
    function handleCORS() {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}

// Password Hasher using SHA1 + Salt
if (!function_exists('hashPassword')) {
    function hashPassword(string $password): string {
        return sha1($password . 'brio_salt_2026');
    }
}
