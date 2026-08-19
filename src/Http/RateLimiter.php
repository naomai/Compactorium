<?php
namespace Naomai\Compactorium\Http;

use CurlHandle;
use Naomai\Compactorium\Logger;

class RateLimiter {
    private float $lastRequestTime = 0;
    private bool $succeeded = false;   
    private float $nextRequestTime = 0; 

    public function __construct(
        private float $delay = 0,
        private float $backoffDelay = 5.0,
        private ?string $httpRemainingHeader = null, 
        private ?int $httpTooManyRequestsCode = 429,
    ) {

    }

    public function wait() : void {
        $currentTime = microtime(true);
        if($currentTime > $this->nextRequestTime) 
            return;

        $delay = $this->nextRequestTime - $currentTime;
        Logger::debug("RateLimiter", "wait for: {$delay}s");
        time_sleep_until($this->nextRequestTime);
    }

    public function commit(array $httpHeaders=[]) : void {
        $this->lastRequestTime = microtime(true);
        $this->succeeded = true;
        $backoff = false;

        $remainingQuota = $this->getRemainingQuotaFromHeaders($httpHeaders);

        if($httpHeaders['HTTP_STATUS_CODE'] == $this->httpTooManyRequestsCode) {
            $this->succeeded = false;
            $backoff = true;
        }

        if($remainingQuota !== null && $remainingQuota <= 1) {
            $backoff = true;
        }

        $delay = $backoff ? $this->backoffDelay :  $this->delay;
        $this->nextRequestTime = $this->lastRequestTime + $delay;

        Logger::debug("RateLimiter", "commit, success={$this->succeeded} backoff={$backoff} nextdelay={$delay}s quota={$remainingQuota}");
    }

    public function commitFailure() : void {
        $this->lastRequestTime = microtime(true);
        $this->succeeded = false;
        $this->nextRequestTime = $this->lastRequestTime + $this->backoffDelay;        
    }

    public function hasSucceeded() : bool {
        return $this->succeeded;
    }

    private function getRemainingQuotaFromHeaders(array $headers) : ?int {
        if($this->httpRemainingHeader === null) 
            return null;

        if(!isset($headers[$this->httpRemainingHeader]) || !is_numeric($headers[$this->httpRemainingHeader]))
            return null;

        return (int) $headers[$this->httpRemainingHeader];

    }

}