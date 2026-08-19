<?php
namespace Naomai\Compactorium\Http;

interface HttpClient{
    public ?RateLimiter $rateLimiter {get; set;}

    public function setUserAgent(string $userAgent) : void;
    public function getJson(string $url, array $headers=[]) : ?object;
    public function downloadFile(string $url, array $headers=[]) : ?string;
    public function getLastRequestInfo() : array;
    public function getLastRequestHeaders() : array;
}