<?php
// Configuration settings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_FILE', __DIR__ . '/schooltask.sqlite');

// SMTP settings configuration for cPanel noreply@syonra.life
define('SMTP_HOST', 'mail.syonra.life');
define('SMTP_PORT', 465);
define('SMTP_USER', 'noreply@syonra.life');
define('SMTP_PASS', '775299@Ss');
define('SMTP_FROM_NAME', 'BRIO World School Admissions');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
