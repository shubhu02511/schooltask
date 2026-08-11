<?php
// ==========================================================================
// BRIO WORLD SCHOOL - Core PHP Admin Logout
// ==========================================================================

require_once __DIR__ . '/../includes/auth_helper.php';

AuthHelper::logout();
header('Location: login.php');
exit;
