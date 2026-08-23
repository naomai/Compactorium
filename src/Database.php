<?php
namespace Naomai\Compactorium;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;

class Database {
    private static ?\PDO $db = null;
    private static ?Connection $dbConn = null;
    private static ?EntityManagerInterface $entityManager = null;

    public static function init() : void {
        
        $dsn = Config::get("DB_DSN");

        if($dsn===null) {
            throw new \Exception("Database connection is not configured");
        }

        $dsnParser = new DsnParser([
            'sqlite' => 'pdo_sqlite',
            'mysql'  => 'pdo_mysql',
            'pgsql'  => 'pdo_pgsql',
            'oci'    => 'pdo_oci',
            'sqlsrv' => 'pdo_sqlsrv',
        ]);

        $connectionParams = $dsnParser
            ->parse($dsn);
        self::$dbConn = DriverManager::getConnection($connectionParams);

        $config = ORMSetup::createAttributeMetadataConfig(
            paths: [__DIR__],
            isDevMode: true,
        );

        $config->setProxyDir(__DIR__ . "/../storage/doctrine/proxies");
        $config->setProxyNamespace('Naomai\\Compactorium\\Doctrine\\Proxies');

        self::$entityManager = new EntityManager(self::$dbConn, $config);

        self::$db = new \PDO( dsn: $dsn);
    }

    public static function entityManager(): EntityManagerInterface {
        if (self::$entityManager === null) {
            throw new \Exception("Database is not initialized");
        }

        return self::$entityManager;
    }

    public static function connection(): \PDO {
        if (self::$db === null) {
            throw new \Exception("Database is not initialized");
        }

        return self::$db;
    }

    public static function timeNow(): string {
        return gmdate('Y-m-d\TH:i:s\Z');
    }


    public static function insert(string $table, array $fields) : int {
        if(!self::validateFieldName($table)){
            throw new \InvalidArgumentException("Invalid characters in table name.");
        }       
        if(!self::validateFieldList($fields)){
            throw new \InvalidArgumentException("Invalid characters in field name.");
        }


        $columns = implode(', ', array_keys($fields));

        $placeholders = implode(', ', array_map(
            fn($k) => ':' . $k,
            array_keys($fields)
        ));

        $sql = "INSERT INTO $table 
            ($columns) 
            VALUES 
            ($placeholders)
        ";

        $db = self::$db;
        $stm = $db->prepare($sql);

        foreach ($fields as $key => $value) {
            $stm->bindValue(":$key", $value);
        }

        $stm->execute();

        return (int)$db->lastInsertId();
    }

    public static function upsert(string $table, array $fields) : int {
        if(!self::validateFieldName($table)){
            throw new \InvalidArgumentException("Invalid characters in table name.");
        }       
        if(!self::validateFieldList($fields)){
            throw new \InvalidArgumentException("Invalid characters in field name.");
        }


        $columns = implode(', ', array_keys($fields));

        $placeholders = implode(', ', array_map(
            fn($k) => ":{$k}",
            array_keys($fields)
        ));


        $updates = implode(', ', array_map(
            fn($k) => "{$k} = :{$k}_dbupsrt",
            array_keys($fields)
        ));

        $sql = "INSERT INTO $table 
            ($columns) 
            VALUES 
            ($placeholders)
            ON CONFLICT DO UPDATE 
            SET $updates
        ";

        $db = self::$db;
        $stm = $db->prepare($sql);

        foreach ($fields as $key => $value) {
            $stm->bindValue(":{$key}", $value);
            $stm->bindValue(":{$key}_dbupsrt", $value);
        }

        $stm->execute();

        return (int)$db->lastInsertId();
    }

    private static function validateFieldList(array $fields) : bool {
        foreach($fields as $field=>$value) {
            if(!self::validateFieldName($field)){
                return false;
            }
        }
        return true;
    }

    private static function validateFieldName(string $name) : bool {
        return preg_match("/^[a-z][a-z0-9_]*$/i", $name)===1;
    }


    public static function createDbTimeFromDateTime(\DateTimeInterface $date) : string {
        return $date->format(\DateTimeInterface::ISO8601_EXPANDED);
    }

    public static function createDateTimeFromDbTime(string $isoDate) : \DateTimeImmutable {
        return \DateTimeImmutable::createFromFormat(
            \DateTimeInterface::ISO8601_EXPANDED,
            $isoDate
        );
    }
}