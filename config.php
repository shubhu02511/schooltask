<?php
// Configuration settings
session_start();

define('DB_FILE', __DIR__ . '/schooltask.sqlite');

// SMTP settings configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'admissions@brioworldschool.edu.in');
define('SMTP_PASS', 'your_smtp_app_password');
define('SMTP_FROM_NAME', 'BRIO World School Admissions');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
