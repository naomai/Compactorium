<?php
namespace Naomai\Compactorium\Http;

use CurlHandle;
use Naomai\Compactorium\Logger;

class ClientContext {
    private string $userAgent;
    private string $downloadDir;
    private array $lastRequestInfo;
    private array $lastRequestHeaders;
    public ?RateLimiter $rateLimiter = null;

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

    public function getJson(string $url, array $headers=[]) : ?object {
        $ch = $this->createConfiguredCurl($url);

        curl_setopt($ch, CURLOPT_HTTPHEADER, 
            self::headerListFlatten(['Accept: application/json', ...$headers])
        );
        Logger::debug("Client", "curl configured");


        $response = $this->executeCurl($ch);


        if ($response === false) {
            throw new \Exception(curl_error($ch), curl_errno($ch));
        }

        $data = json_decode($response);

        return $data;
    }

    public function downloadFile(string $url, array $headers=[]) : ?string {
        $urlHash = hash('sha256', $url);
        $downloadPath = $this->downloadDir . "/" . $urlHash . ".bin";


        $fh = fopen($downloadPath, "wb");

        $ch = $this->createConfiguredCurl($url);
        curl_setopt($ch, CURLOPT_FILE, $fh);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, 
            self::headerListFlatten([...$headers])
        );
        Logger::debug("Client", "curl configured");
        
        $response = $this->executeCurl($ch);

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
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADERFUNCTION => [$this, 'passHttpHeader'],
        ]);

        return $ch;
    }

    private function executeCurl(CurlHandle $ch) : string|bool {
        $response = false;
        do {
            try {
                $this->rateLimiterWait();
                $this->requestBegin();
                $response = curl_exec($ch);
                Logger::debug("Client", "curl request done");
                $this->setLastRequestInfo($ch);
                $this->rateLimiterCommit();
            } catch(\Exception $e) {
                if($e->getCode() == CURLE_OPERATION_TIMEDOUT) {
                    $this->rateLimiterCommitFailure();
                }
            }
        } while(!$this->rateLimiterSucceeded());
        return $response;
    }

    private function setLastRequestInfo(CurlHandle $ch) : void {
        $this->lastRequestInfo = curl_getinfo($ch);
    }

    private function requestBegin() : void {
        $this->lastRequestHeaders = [];
    }

    public function getLastRequestInfo() : array {
        return $this->lastRequestInfo;
    }

    public function getLastRequestHeaders() : array {
        return $this->lastRequestHeaders;
    }

    private function passHttpHeader(CurlHandle $ch, string $row) {
        if(substr($row, 0, 5) == "HTTP/") {
            preg_match('/^HTTP\/(\d(?:\.\d)?)\s+(\d{3})/', $row, $m);
            $this->lastRequestHeaders['HTTP_VERSION'] = $m[1];
            $this->lastRequestHeaders['HTTP_RESPONSE_CODE'] = (int)$m[2];

        } else {
            $h = preg_split("/:\s*/", trim($row), 2);
            if($h) {
                [$key, $value] = $h;
                $this->lastRequestHeaders[strtolower($key)] = $value;
            }
        }
        return strlen($row);
    }

    private function rateLimiterWait() : void {
        if($this->rateLimiter === null) 
            return;
        $this->rateLimiter->wait();        
    }

    private function rateLimiterCommit() : void {
        if($this->rateLimiter === null) 
            return;
        $this->rateLimiter->commit($this->getLastRequestHeaders());      
    }

    private function rateLimiterCommitFailure() : void {
        if($this->rateLimiter === null) 
            return;
        $this->rateLimiter->commitFailure();      
    }

    private function rateLimiterSucceeded() : bool {
         if($this->rateLimiter === null) 
            return true;
        return $this->rateLimiter->hasSucceeded();
    }

    private static function headerListFlatten(array $headers) : array {
        return array_map(
            fn($val, $key) => is_numeric($key) ? $val : "$key: $val",
            $headers, 
            array_keys($headers)
        );
        
    }


}