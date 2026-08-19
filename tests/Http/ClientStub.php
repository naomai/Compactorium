<?php
namespace Tests\Http;

use Naomai\Compactorium\Http\HttpClient;
use Naomai\Compactorium\Http\RateLimiter;

class ClientStub implements HttpClient {
    public ?RateLimiter $rateLimiter = null;

    public array $urlMapping = [];

    public ?object $jsonResponse = null;
    public ?int $curlErrorCode = null;
    public int $responseCode = 200;
    public array $responseHeaders = [];


    public function getJson(string $url, array $headers=[]) : ?object {
        $mapping = $this->getUrlMapping($url);

        $this->jsonResponse = json_decode($mapping['response']);
        $this->responseCode = $mapping['status'];

        if($this->curlErrorCode !== null) {
            throw new \Exception(curl_strerror($this->curlErrorCode), $this->curlErrorCode);
        }

        return $this->jsonResponse;
    }

    public function downloadFile(string $url, array $headers=[]) : ?string {
        return "/";
    }

    public function getLastRequestInfo() : array {
        return [];
    }

    public function getLastRequestHeaders() : array {
        $headers = $this->responseHeaders;
        $headers['HTTP_VERSION'] = "2";
        $headers['HTTP_STATUS_CODE'] = $this->responseCode;
        return $headers;
    }

    public function setUserAgent(string $userAgent) : void {
    }

    public function getUrlMapping(string $url) : ?array {
        $match = array_find(
            $this->urlMapping, 
            fn($file, $urlPattern) => preg_match($urlPattern, $url)==1
        );

        if($match===null) {
            $match = ['status'=>404, 'response'=>''];
            return $match;
        }

        if(isset($match['contentFile'])) {
            $fixture = __DIR__ . "/../Fixtures/Http/" . $match['contentFile'];
            if(!file_exists($fixture)) {
                throw new \Exception("Fixture file for url does not exist: {$url}");
            }

            $match['response'] = file_get_contents($fixture);
        }
        return $match;


    }



}