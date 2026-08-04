<?php
namespace Naomai\Compactorium\Services;

use Naomai\Compactorium\Http\Client;

class MusicBrainz {
    // functions marked as "blocking" may cause long delays, 
    // due to rate limiting.
    // might reimplement this with promise-like pattern

    private static float $lastRequestTime;
    private const float REQUEST_DELAY_S = 1.0;

    public static function init() : void {
        self::$lastRequestTime = 0;
    }

    public static function GetAlbumByBarcode(string $bcd) : ?object {
        // blocking
        $urlArgs = [
            'query'=>"barcode:{$bcd}",
            'fmt'=>'json'
        ];

        $url = "https://musicbrainz.org/ws/2/release?" . http_build_query($urlArgs);

        self::RateLimiterWait();
        $releases = Client::getJson($url);
        self::RateLimiterCommit();

        if(property_exists($releases, 'error')) {
            throw new \Exception("MusicBrainz error: {$releases->error}");
        }

        if($releases->count == 0) {
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

        if($timePassed < self::REQUEST_DELAY_S) {
            time_sleep_until(self::$lastRequestTime + self::REQUEST_DELAY_S);
        }
    }

    private static function RateLimiterCommit() : void {
        self::$lastRequestTime = microtime(true);
    }

    public static function ValidateMbid(string $id) : bool {
        return preg_match("/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i", $id)===1;
    }


}