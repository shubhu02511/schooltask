<?php
// ==========================================================================
// BRIO WORLD SCHOOL - Core PDO MySQL Database Connector
// Uses PDO Prepared Statements for SQL Injection Protection
// ==========================================================================

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // If MySQL connection fails (e.g. before DB is created), fallback to JsonDBWrapper
                require_once __DIR__ . '/../../app/config/db.php';
                return getDB();
            }
        }
        return self::$instance;
    }
}

// Global Helper function to retrieve DB instance
if (!function_exists('getCoreDB')) {
    function getCoreDB() {
        return Database::getConnection();
    }
}
