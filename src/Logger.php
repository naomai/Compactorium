<?php
namespace Naomai\Compactorium;


class Logger {
    private static float $startTime;

    public static function init() : void {
        self::$startTime = microtime(true);
    }

    public static function debug(string $tag, string $message) : void {
        if(php_sapi_name() !== 'cli') 
            return;

        $timeSinceStart = (microtime(true)-self::$startTime);
        echo "[".round($timeSinceStart, 3)."] {$tag}: {$message}\n";
    }
}