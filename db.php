<?php
// Database connection helper with Session RAM database fallback for shared hosting
require_once __DIR__ . '/config.php';

if (!class_exists('JsonDBWrapper')) {
    class JsonDBWrapper {
        public function __construct() {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            if (!isset($_SESSION['schooltask_users_db'])) {
                $_SESSION['schooltask_users_db'] = ['users' => [], 'career' => []];
            }
        }

        public function prepare($sql) {
            return new JsonDBStatement($sql);
        }

        public function exec($sql) {
            return true;
        }
    }
}

if (!class_exists('JsonDBStatement')) {
    class JsonDBStatement {
        private $sql;
        private $data = [];

        public function __construct($sql) {
            $this->sql = $sql;
        }

        public function execute($params = []) {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            if (!isset($_SESSION['schooltask_users_db']) || !is_array($_SESSION['schooltask_users_db'])) {
                $_SESSION['schooltask_users_db'] = ['users' => [], 'career' => []];
            }
            $store = &$_SESSION['schooltask_users_db'];
            if (!isset($store['users']) || !is_array($store['users'])) {
                $store['users'] = [];
            }

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
            } elseif (stripos($this->sql, 'UPDATE users SET otp_code =') !== false) {
                $id = $params[2] ?? 0;
                foreach ($store['users'] as &$u) {
                    if (($u['id'] ?? null) == $id) {
                        $u['otp_code'] = $params[0];
                        $u['otp_expires'] = $params[1];
                        break;
                    }
                }
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
}

if (!function_exists('getDB')) {
    function getDB() {
        static $pdo = null;
        if ($pdo === null) {
            $pdo = new JsonDBWrapper();
        }
        return $pdo;
    }
}
