<?php
declare(strict_types=1);

class DB {
    private static ?PDO $instance  = null;
    private static ?bool $writable = null;

    public static function init_session(int $ttl = 900): void {
        if (PHP_SAPI === 'cli' || headers_sent()) {
            return;
        }
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Lax');
            session_start();
        }
        if (!empty($_SESSION['db_user'])) {
            $last = (int)($_SESSION['last_activity'] ?? 0);
            if ($last > 0 && (time() - $last) > $ttl) {
                $_SESSION = [];
                session_regenerate_id(true);
                $_SESSION['flash_warning'] = 'Сесію завершено через 15 хвилин неактивності (виконано автоматичний вихід).';
                return;
            }
            $_SESSION['last_activity'] = time();
        }
    }

    private static function ensure_session(): void {
        self::init_session();
    }

    public static function connect(string $user, string $pass): PDO {
        $params = require __DIR__ . '/config.php';
        $dsn    = "{$params['rdms']}:host={$params['host']};dbname={$params['db']};charset=utf8mb4";
        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    public static function get_connection(): PDO {
        if (self::$instance === null) {
            self::ensure_session();
            $params = require __DIR__ . '/config.php';

            if (!empty($_SESSION['db_user'])) {
                try {
                    self::$instance = self::connect((string)$_SESSION['db_user'], (string)($_SESSION['db_pass'] ?? ''));
                    return self::$instance;
                } catch (PDOException) {
                    unset($_SESSION['db_user'], $_SESSION['db_pass']);
                }
            }

            try {
                self::$instance = self::connect($params['guest_user'], $params['guest_pass']);
            } catch (PDOException) {
                throw new RuntimeException('Не вдалося підключитися до бази даних. Перевірте роботу сервера MariaDB та права гостьового облікового запису.');
            }
        }
        return self::$instance;
    }

    public static function is_logged_in(): bool {
        self::ensure_session();
        $params = require __DIR__ . '/config.php';
        return !empty($_SESSION['db_user']) && $_SESSION['db_user'] !== $params['guest_user'];
    }

    public static function current_user(): string {
        self::ensure_session();
        $params = require __DIR__ . '/config.php';
        return (string)($_SESSION['db_user'] ?? $params['guest_user']);
    }

    public static function can_write(): bool {
        if (self::$writable !== null) {
            return self::$writable;
        }
        try {
            $grants = self::get_connection()
                ->query('SHOW GRANTS FOR CURRENT_USER()')
                ->fetchAll(PDO::FETCH_COLUMN);

            self::$writable = false;
            foreach ($grants as $grant) {
                if (preg_match('/^GRANT\s+.*?\b(ALL PRIVILEGES|INSERT|UPDATE|DELETE)\b.*?\s+ON\s+/i', (string)$grant)) {
                    self::$writable = true;
                    break;
                }
            }
        } catch (Throwable) {
            self::$writable = false;
        }
        return self::$writable;
    }

    public static function online_stats(int $window = 180, bool $isEditing = false): array {
        self::ensure_session();
        $sid  = session_id();
        $now  = time();
        $path = sys_get_temp_dir() . '/expeditions_db_presence.json';

        $fp = @fopen($path, 'c+');
        if (!$fp) {
            $rw = self::can_write();
            return ['online' => 1, 'rw' => $rw ? 1 : 0, 'editing' => ($rw && $isEditing) ? 1 : 0];
        }

        flock($fp, LOCK_EX);
        $raw  = stream_get_contents($fp);
        $data = $raw ? (json_decode($raw, true) ?: []) : [];

        foreach ($data as $k => $entry) {
            if (($now - (int)($entry['ts'] ?? 0)) > $window) {
                unset($data[$k]);
            }
        }

        if ($sid !== '') {
            $canWrite   = self::can_write();
            $data[$sid] = [
                'ts'      => $now,
                'rw'      => $canWrite,
                'editing' => $canWrite && $isEditing,
            ];
        }

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        $online  = count($data);
        $rw      = 0;
        $editing = 0;
        foreach ($data as $entry) {
            if (!empty($entry['rw'])) $rw++;
            if (!empty($entry['editing'])) $editing++;
        }

        return ['online' => $online, 'rw' => $rw, 'editing' => $editing];
    }

    public static function remove_presence(): void {
        $sid  = session_id();
        $path = sys_get_temp_dir() . '/expeditions_db_presence.json';
        if ($sid === '' || !file_exists($path)) {
            return;
        }

        $fp = @fopen($path, 'c+');
        if (!$fp) {
            return;
        }

        flock($fp, LOCK_EX);
        $raw  = stream_get_contents($fp);
        $data = $raw ? (json_decode($raw, true) ?: []) : [];
        unset($data[$sid]);
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}
