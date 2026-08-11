<?php
// Persistent File-Based JSON Database Wrapper for Shared Hosting & CLI Compatibility
require_once __DIR__ . '/config.php';

if (!class_exists('JsonDBWrapper')) {
    class JsonDBWrapper {
        private static function getFilePath() {
            $tmp = sys_get_temp_dir();
            return rtrim($tmp, '/\\') . '/brio_app_users_db.json';
        }

        public static function loadData() {
            $f = self::getFilePath();
            if (file_exists($f)) {
                $content = @file_get_contents($f);
                $decoded = @json_decode($content, true);
                if (is_array($decoded) && isset($decoded['users'])) {
                    return $decoded;
                }
            }
            return ['users' => [], 'career' => []];
        }

        public static function saveData($data) {
            $f = self::getFilePath();
            @file_put_contents($f, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
        }

        public function prepare($sql) {
            return new JsonDBStatement($sql);
        }

        public function exec($sql) {
            $store = self::loadData();
            if (stripos($sql, 'DELETE FROM users') !== false) {
                if (preg_match("/email\s*=\s*'([^']+)'/i", $sql, $m)) {
                    $targetEmail = strtolower($m[1]);
                    $store['users'] = array_values(array_filter($store['users'], function($u) use ($targetEmail) {
                        return strtolower($u['email'] ?? '') !== $targetEmail;
                    }));
                } else {
                    $store['users'] = [];
                }
                self::saveData($store);
            } elseif (stripos($sql, 'UPDATE users SET otp_expires') !== false) {
                if (preg_match("/otp_expires\s*=\s*'([^']+)'/i", $sql, $mExp) && preg_match("/email\s*=\s*'([^']+)'/i", $sql, $mEmail)) {
                    $expVal = $mExp[1];
                    $targetEmail = strtolower($mEmail[1]);
                    foreach ($store['users'] as &$u) {
                        if (strtolower($u['email'] ?? '') === $targetEmail) {
                            $u['otp_expires'] = $expVal;
                            break;
                        }
                    }
                    self::saveData($store);
                }
            }
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
            $store = JsonDBWrapper::loadData();

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
                JsonDBWrapper::saveData($store);
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
                JsonDBWrapper::saveData($store);
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
                JsonDBWrapper::saveData($store);
            } elseif (stripos($this->sql, 'UPDATE users SET otp_code =') !== false) {
                $id = $params[2] ?? 0;
                foreach ($store['users'] as &$u) {
                    if (($u['id'] ?? null) == $id) {
                        $u['otp_code'] = $params[0];
                        $u['otp_expires'] = $params[1];
                        break;
                    }
                }
                JsonDBWrapper::saveData($store);
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
                JsonDBWrapper::saveData($store);
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
