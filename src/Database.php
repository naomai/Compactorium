<?php
namespace Naomai\Compactorium;

use Exception;

class Database {
    private static ?\PDO $db = null;

    public static function connection(): \PDO {
        
        $dsn = Config::get("DB_DSN");

        if($dsn===null) {
            throw new Exception("Database connection is not configured");
        }

        if (self::$db === null) {
            self::$db = new \PDO( dsn: $dsn);
        }

        return self::$db;
    }
}