<?php
// Configuration settings
if (session_status() === PHP_SESSION_NONE) {
    $tmpDir = sys_get_temp_dir();
    if (is_writable($tmpDir)) {
        @ini_set('session.save_path', $tmpDir);
    }
    @session_start();
}

define('DB_FILE', __DIR__ . '/schooltask.sqlite');

// SMTP settings configuration for cPanel noreply@syonra.life
define('SMTP_HOST', 'mail.syonra.life');
define('SMTP_PORT', 587);
define('SMTP_USER', 'noreply@syonra.life');
define('SMTP_PASS', '775299@Ss');
define('SMTP_FROM_NAME', 'BRIO World School Admissions');

// Disable display errors for production LiteSpeed compatibility
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 0);
