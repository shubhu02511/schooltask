<?php
// SQLite database connection helper
require_once __DIR__ . '/config.php';

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dbFile = DB_FILE;
            if (file_exists($dbFile)) {
                @chmod($dbFile, 0666);
            } else {
                @touch($dbFile);
                @chmod($dbFile, 0666);
            }
            
            $pdo = new PDO('sqlite:' . $dbFile);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Create users table
            $pdo->exec("CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                otp_code TEXT,
                otp_expires DATETIME,
                is_verified INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            // Create career applications table
            $pdo->exec("CREATE TABLE IF NOT EXISTS career_applications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER DEFAULT 0,
                full_name TEXT NOT NULL,
                email TEXT NOT NULL,
                phone TEXT NOT NULL,
                experience INTEGER NOT NULL,
                job_title TEXT NOT NULL,
                message TEXT,
                resume_path TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

        } catch (PDOException $e) {
            // Fallback to sys_get_temp_dir if root sqlite file isn't writable
            try {
                $tempDb = sys_get_temp_dir() . '/schooltask.sqlite';
                $pdo = new PDO('sqlite:' . $tempDb);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                $pdo->exec("CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    email TEXT UNIQUE NOT NULL,
                    password TEXT NOT NULL,
                    otp_code TEXT,
                    otp_expires DATETIME,
                    is_verified INTEGER DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )");

                $pdo->exec("CREATE TABLE IF NOT EXISTS career_applications (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER DEFAULT 0,
                    full_name TEXT NOT NULL,
                    email TEXT NOT NULL,
                    phone TEXT NOT NULL,
                    experience INTEGER NOT NULL,
                    job_title TEXT NOT NULL,
                    message TEXT,
                    resume_path TEXT NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
            } catch (Exception $fallbackErr) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
                exit;
            }
        }
    }
    return $pdo;
}
