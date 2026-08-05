<?php
namespace Naomai\Compactorium\Http;

use CurlHandle;
use Naomai\Compactorium\Logger;

class Client {
    private static string $userAgent;
    private static string $downloadDir;
    private static array $lastRequestInfo;

    public static function init() : void {
        self::setUserAgent(
            "Compactorium/" . $_ENV['APP_VERSION'] . " (https://github.com/naomai/Compactorium/; lougato@nieznani-sprawcy.pl)"
        );

        self::$downloadDir = $_ENV['BASE_DIR'] . "/storage/downloads";
        if(!file_exists(self::$downloadDir)) {
            mkdir(directory: self::$downloadDir, recursive: true);
        }

    }

    public static function setUserAgent(string $userAgent) : void {
        self::$userAgent = $userAgent;
    }

    public static function getJson(string $url) : ?object {
        $ch = self::createConfiguredCurl($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

        Logger::debug("Client", "curl configured");
        $response = curl_exec($ch);
        Logger::debug("Client", "curl request done");

        self::setLastRequestInfo($ch);

        if ($response === false) {
            throw new \Exception(curl_error($ch), curl_errno($ch));
        }

        $data = json_decode($response);

        return $data;
    }

    public static function downloadFile(string $url) : ?string {
        $urlHash = hash('sha256', $url);
        $downloadPath = self::$downloadDir . "/" . $urlHash . ".bin";


        $fh = fopen($downloadPath, "wb");

        $ch = self::createConfiguredCurl($url);
        curl_setopt($ch, CURLOPT_FILE, $fh);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        Logger::debug("Client", "curl configured");
        $response = curl_exec($ch);
        Logger::debug("Client", "curl request done");

        self::setLastRequestInfo($ch);

        if ($response === false) {
            throw new \Exception(curl_error($ch));
        }
        fclose($fh);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if($httpCode > 299) {
            Logger::debug("Client", "download failed with code {$httpCode}");

            unlink($downloadPath);
            return null;
        }

        Logger::debug("Client", "download stored in {$downloadPath}");
        return $downloadPath;
    }

    private static function createConfiguredCurl(string $url) : CurlHandle {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_USERAGENT => self::$userAgent,
            CURLOPT_TIMEOUT => 10,
        ]);

        return $ch;
    }

    private static function setLastRequestInfo(CurlHandle $ch) : void {
        self::$lastRequestInfo = curl_getinfo($ch);
    }

    public static function getLastRequestInfo() : array {
        return self::$lastRequestInfo;
    }


}