<?php
namespace Naomai\Compactorium;
class Config {
    private static ?\Dotenv\Dotenv $config = null;

    public static function init() : void {
        $_ENV["BASE_DIR"] = realpath(__DIR__ . "/..");
        self::$config = \Dotenv\Dotenv::createImmutable(__DIR__."/..");
        self::$config->safeLoad();
    }

    public static function get(string $variable) : ?string {
        return $_ENV[$variable] ?? null;
    }
}