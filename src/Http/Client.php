<?php
namespace Naomai\Compactorium\Http;

class Client {
    private static ?ClientContext $context=null;

    public static function init() : void {
        self::$context = new ClientContext();
    }

    public static function setUserAgent(string $userAgent) : void {
        self::$context->setUserAgent($userAgent);
    }

    public static function getJson(string $url) : ?object {
        return self::$context->getJson($url);
    }

    public static function downloadFile(string $url) : ?string {
        return self::$context->downloadFile($url);
    }

    public static function getLastRequestInfo() : array {
        return self::$context->getLastRequestInfo();
    }
}