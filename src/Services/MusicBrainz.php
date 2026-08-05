<?php
namespace Naomai\Compactorium\Services;

use Naomai\Compactorium\Http\Client;
use Naomai\Compactorium\Logger;

class MusicBrainz {
    // functions marked as "blocking" may cause long delays, 
    // due to rate limiting.
    // might reimplement this with promise-like pattern

    private static float $lastRequestTime;
    private static bool $backoff;
    private const float REQUEST_DELAY_S = 1.1;
    private const float REQUEST_BACKOFF_S = 5.0;

    public static function init() : void {
        self::$lastRequestTime = 0;
        self::$backoff = false;
    }

    public static function GetAlbumByBarcode(string $bcd) : ?object {
        // blocking
        $urlArgs = [
            'query'=>"barcode:{$bcd}",
            'fmt'=>'json'
        ];

        $url = "https://musicbrainz.org/ws/2/release?" . http_build_query($urlArgs);

        Logger::debug("MusicBrainz", "request url: {$url}");
        
        $releases = null;

        do {

            self::RateLimiterWait();
            try{

                $releases = Client::getJson($url);
                self::RateLimiterCommit();
                self::$backoff = Client::getLastRequestInfo()['http_code']==503;
            } catch(\Exception $e) {
                if($e->getCode() == CURLE_OPERATION_TIMEDOUT) {
                    self::RateLimiterCommit();
                    self::$backoff = true;
                    Logger::debug("MusicBrainz", "curl timeout");

                }
            }

        } while (self::$backoff);

        if(property_exists($releases, 'error')) {
            throw new \Exception("MusicBrainz error: {$releases->error}");
        }

        if($releases->count == 0) {
            Logger::debug("MusicBrainz", "no releases");
            return null;
        }

        $releaseInfo = $releases->releases[0];
        $releaseGroupId = $releaseInfo->{'release-group'}->id;

        return (object)[
            'releaseInfo' => $releaseInfo,
            'releaseId' => $releaseInfo->id,
            'releaseGroupId' => $releaseGroupId
        ];

    }

    private static function RateLimiterWait() : void {
        $currentTime = microtime(true);
        $timePassed = $currentTime - self::$lastRequestTime;

        $delay = self::$backoff ? self::REQUEST_BACKOFF_S : self::REQUEST_DELAY_S;

        if($timePassed < $delay) {
            Logger::debug("MusicBrainz", "backoff for: {$delay}s");
            time_sleep_until(self::$lastRequestTime + $delay);
        }
    }

    private static function RateLimiterCommit() : void {
        self::$lastRequestTime = microtime(true);
    }

    public static function ValidateMbid(string $id) : bool {
        return preg_match("/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i", $id)===1;
    }


}