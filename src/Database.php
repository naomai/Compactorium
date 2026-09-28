<?php
namespace Naomai\Compactorium;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Symfony\Component\HttpKernel\Kernel;

class Database {
    private static ?EntityManagerInterface $entityManager = null;

    public static function init(EntityManagerInterface $em) : void {
        self::$entityManager = $em;

    }

    public static function entityManager(): EntityManagerInterface {
        if (self::$entityManager === null) {
            throw new \Exception("Database is not initialized");
        }

        return self::$entityManager;
    }

    public static function timeNow(): string {
        return gmdate('Y-m-d\TH:i:s\Z');
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