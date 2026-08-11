<?php
// ==========================================================================
// BRIO WORLD SCHOOL - Global Backend Configuration Constants
// ==========================================================================

if (!defined('BRIO_APP')) {
    define('BRIO_APP', true);
}

// Database Connection Credentials (Replace with cPanel MySQL credentials in production)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'syonra_brio');
define('DB_USER', getenv('DB_USER') ?: 'syonra_brio_user');
define('DB_PASS', getenv('DB_PASS') ?: '775299@Ss');
define('DB_CHARSET', 'utf8mb4');

// Security Salt & Secret Keys
define('SALT_KEY', 'brio_salt_2026_production');

// App Info
define('APP_NAME', 'BRIO World School');
define('APP_URL', 'https://syonra.life');
define('ADMIN_EMAIL', 'admissions@brioworldschool.edu.in');
