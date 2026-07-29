<?php
namespace Naomai\Compactorium;
class Config {
    private static ?\Dotenv\Dotenv $config = null;

    private static function ensureLoaded() : void {
        if (self::$config === null) {
            $_ENV["BASE_DIR"] = realpath(__DIR__ . "/..");
            self::$config = \Dotenv\Dotenv::createImmutable(__DIR__."/..");
            self::$config->safeLoad();
        }
    }

    public static function get(string $variable) : ?string {
        self::ensureLoaded();
        return $_ENV[$variable] ?? null;
    }
}