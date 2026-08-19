<?php
namespace Naomai\Compactorium\Http;

use CurlHandle;
use Naomai\Compactorium\Logger;

class ClientContext {
    private string $userAgent;
    private string $downloadDir;
    private array $lastRequestInfo;

    public function __construct() {
        $this->setUserAgent(
            "Compactorium/" . $_ENV['APP_VERSION'] . " (https://github.com/naomai/Compactorium/; lougato@nieznani-sprawcy.pl)"
        );

        $this->downloadDir = $_ENV['BASE_DIR'] . "/storage/downloads";
        if(!file_exists($this->downloadDir)) {
            mkdir(directory: $this->downloadDir, recursive: true);
        }

    }

    public function setUserAgent(string $userAgent) : void {
        $this->userAgent = $userAgent;
    }

    public function getJson(string $url) : ?object {
        $ch = $this->createConfiguredCurl($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

        Logger::debug("Client", "curl configured");
        $response = curl_exec($ch);
        Logger::debug("Client", "curl request done");

        $this->setLastRequestInfo($ch);

        if ($response === false) {
            throw new \Exception(curl_error($ch), curl_errno($ch));
        }

        $data = json_decode($response);

        return $data;
    }

    public function downloadFile(string $url) : ?string {
        $urlHash = hash('sha256', $url);
        $downloadPath = $this->downloadDir . "/" . $urlHash . ".bin";


        $fh = fopen($downloadPath, "wb");

        $ch = $this->createConfiguredCurl($url);
        curl_setopt($ch, CURLOPT_FILE, $fh);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        Logger::debug("Client", "curl configured");
        $response = curl_exec($ch);
        Logger::debug("Client", "curl request done");

        $this->setLastRequestInfo($ch);

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

    private function createConfiguredCurl(string $url) : CurlHandle {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_TIMEOUT => 10,
        ]);

        return $ch;
    }

    private function setLastRequestInfo(CurlHandle $ch) : void {
        $this->lastRequestInfo = curl_getinfo($ch);
    }

    public function getLastRequestInfo() : array {
        return $this->lastRequestInfo;
    }


}