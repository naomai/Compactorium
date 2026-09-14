<?php
namespace Naomai\Compactorium;

use Symfony\Component\Dotenv\Dotenv;

class LegacyDotEnv {
    private static ?Dotenv $config = null;

    public static function init() : void {
        $_ENV["BASE_DIR"] = realpath(__DIR__ . "/..");
        self::$config = new Dotenv(); 
        self::$config->load(__DIR__."/../.env");
    }

    public static function get(string $variable) : ?string {
        return $_ENV[$variable] ?? null;
    }
}