<?php
namespace Naomai\Compactorium\Http;

use CurlHandle;

class Client {
    private static string $userAgent;

    public static function init() : void {
        self::setUserAgent(
            "Compactorium/" . $_ENV['APP_VERSION'] . " (https://github.com/naomai/Compactorium/; lougato@nieznani-sprawcy.pl)"
        );
    }

    public static function setUserAgent(string $userAgent) : void {
        self::$userAgent = $userAgent;
    }

    public static function getJson(string $url) : ?object {
        $ch = self::createConfiguredCurl($url);

        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        
        $response = curl_exec($ch);

        if ($response === false) {
            throw new \Exception(curl_error($ch));
        }

        $data = json_decode($response);

        return $data;
    }

    private static function createConfiguredCurl(string $url) : CurlHandle {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => self::$userAgent,
            CURLOPT_TIMEOUT => 10,
        ]);

        return $ch;
    }


}