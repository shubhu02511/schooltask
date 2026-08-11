<?php
// ==========================================================================
// BRIO WORLD SCHOOL - User Logout API Endpoint
// ==========================================================================

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_helper.php';

handleCORS();

AuthHelper::logout();

sendJSONResponse(true, 'Logged out successfully');
