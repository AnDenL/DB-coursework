<?php
declare(strict_types=1);

class DB {
    private static ?PDO $instance = null;

    public static function get_connection(): PDO {
        if (self::$instance === null) {
            $params = require __DIR__ . "/config.php";
            #$dsn = "{$params['rdms']}:host={$params['host']};dbname={$params['db']};charset=utf8mb4";
            self::$instance = new PDO("{$params['rdms']}:host={$params['host']};dbname={$params['db']}", $params['user'], $params['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        return self::$instance;
    }
}
