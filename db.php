<?php
// Database connection helper with SQLite and JSON file fallback
require_once __DIR__ . '/config.php';

class JsonDBWrapper {
    private string $storageFile;

    public function __construct() {
        $this->storageFile = __DIR__ . '/json_db_store.json';
        if (!file_exists($this->storageFile)) {
            @file_put_contents($this->storageFile, json_encode(['users' => [], 'career' => []]));
            @chmod($this->storageFile, 0666);
        }
    }

    public function prepare($sql) {
        return new JsonDBStatement($this->storageFile, $sql);
    }

    public function exec($sql) {
        return true;
    }
}

class JsonDBStatement {
    private string $storageFile;
    private string $sql;
    private array $data = [];

    public function __construct($storageFile, $sql) {
        $this->storageFile = $storageFile;
        $this->sql = $sql;
    }

    public function execute($params = []) {
        $store = json_decode(@file_get_contents($this->storageFile), true) ?: ['users' => [], 'career' => []];

        if (stripos($this->sql, 'SELECT * FROM users WHERE email') !== false) {
            $email = strtolower($params[0] ?? '');
            foreach ($store['users'] as $u) {
                if (strtolower($u['email'] ?? '') === $email) {
                    $this->data = [$u];
                    return true;
                }
            }
            $this->data = [];
        } elseif (stripos($this->sql, 'INSERT INTO users') !== false) {
            $newUser = [
                'id' => count($store['users']) + 1,
                'name' => $params[0] ?? '',
                'email' => strtolower($params[1] ?? ''),
                'password' => $params[2] ?? '',
                'otp_code' => $params[3] ?? '',
                'otp_expires' => $params[4] ?? '',
                'is_verified' => $params[5] ?? 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
            $store['users'][] = $newUser;
            @file_put_contents($this->storageFile, json_encode($store));
            $this->data = [$newUser];
        } elseif (stripos($this->sql, 'UPDATE users SET name') !== false) {
            $email = strtolower($params[4] ?? '');
            foreach ($store['users'] as &$u) {
                if (strtolower($u['email'] ?? '') === $email) {
                    $u['name'] = $params[0];
                    $u['password'] = $params[1];
                    $u['otp_code'] = $params[2];
                    $u['otp_expires'] = $params[3];
                    break;
                }
            }
            @file_put_contents($this->storageFile, json_encode($store));
        } elseif (stripos($this->sql, 'UPDATE users SET is_verified = 1') !== false) {
            $id = $params[0] ?? 0;
            foreach ($store['users'] as &$u) {
                if (($u['id'] ?? null) == $id) {
                    $u['is_verified'] = 1;
                    $u['otp_code'] = null;
                    $u['otp_expires'] = null;
                    break;
                }
            }
            @file_put_contents($this->storageFile, json_encode($store));
        } elseif (stripos($this->sql, 'UPDATE users SET otp_code =') !== false) {
            $id = $params[2] ?? 0;
            foreach ($store['users'] as &$u) {
                if (($u['id'] ?? null) == $id) {
                    $u['otp_code'] = $params[0];
                    $u['otp_expires'] = $params[1];
                    break;
                }
            }
            @file_put_contents($this->storageFile, json_encode($store));
        } elseif (stripos($this->sql, 'UPDATE users SET password =') !== false) {
            $id = $params[1] ?? 0;
            foreach ($store['users'] as &$u) {
                if (($u['id'] ?? null) == $id) {
                    $u['password'] = $params[0];
                    $u['is_verified'] = 1;
                    $u['otp_code'] = null;
                    $u['otp_expires'] = null;
                    break;
                }
            }
            @file_put_contents($this->storageFile, json_encode($store));
        }
        return true;
    }

    public function fetch() {
        return $this->data[0] ?? false;
    }

    public function fetchAll() {
        return $this->data;
    }
}

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        if (!extension_loaded('pdo') || !in_array('sqlite', PDO::getAvailableDrivers())) {
            return new JsonDBWrapper();
        }

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

        } catch (Exception $e) {
            return new JsonDBWrapper();
        }
    }
    return $pdo;
}
