<?php
// ==========================================================================
// BRIO WORLD SCHOOL - Core Database Connector
// Compatible with PHP 7.0 - 8.3 & PDO MySQL
// ==========================================================================

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (Exception $e) {
                // Fallback to JsonDBWrapper if MySQL DB is not created on cPanel yet
                $jsonDbFile = __DIR__ . '/../../app/config/db.php';
                if (!file_exists($jsonDbFile)) {
                    $jsonDbFile = __DIR__ . '/../../../app/config/db.php';
                }
                if (file_exists($jsonDbFile)) {
                    require_once $jsonDbFile;
                    if (function_exists('getDB')) {
                        self::$instance = getDB();
                    }
                }
            }
        }
        return self::$instance;
    }
}

if (!function_exists('getCoreDB')) {
    function getCoreDB() {
        return Database::getConnection();
    }
}
