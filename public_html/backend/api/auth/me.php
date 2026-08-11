<?php
// ==========================================================================
// BRIO WORLD SCHOOL - Current User Session API Endpoint
// ==========================================================================

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_helper.php';

handleCORS();

if (AuthHelper::isLoggedIn()) {
    sendJSONResponse(true, 'User session active', [
        'user' => [
            'id' => $_SESSION['brio_user_id'],
            'name' => $_SESSION['brio_user_name'],
            'email' => $_SESSION['brio_user_email'],
            'role' => $_SESSION['brio_user_role'] ?? 'user'
        ]
    ]);
} else {
    sendJSONResponse(false, 'No active user session', [], 401);
}
