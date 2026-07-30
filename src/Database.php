<?php
namespace Naomai\Compactorium;

use Exception;

class Database {
    private static ?\PDO $db = null;

    public static function init() : void {
        
        $dsn = Config::get("DB_DSN");

        if($dsn===null) {
            throw new Exception("Database connection is not configured");
        }

        self::$db = new \PDO( dsn: $dsn);
    }

    public static function connection(): \PDO {
        if (self::$db === null) {
            throw new Exception("Database is not initialized");
        }

        return self::$db;
    }

    public static function timeNow(): string {
        return gmdate('Y-m-d\TH:i:s\Z');
    }
}