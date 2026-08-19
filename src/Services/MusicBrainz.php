<?php
namespace Naomai\Compactorium\Services;

use Naomai\Compactorium\Http\CurlClient;
use Naomai\Compactorium\Http\RateLimiter;
use Naomai\Compactorium\Logger;

class MusicBrainz {
    // functions marked as "blocking" may cause long delays, 
    // due to rate limiting.
    // might reimplement this with promise-like pattern

    private static CurlClient $client;


    public static function init() : void {
        self::$client = new CurlClient();
        self::$client->rateLimiter = new RateLimiter(
            delay: 1.1,
            httpRemainingHeader: "x-ratelimit-remaining",
            httpTooManyRequestsCode: 503
        );
    }

    public static function GetAlbumByBarcode(string $bcd) : ?object {
        // blocking
        $urlArgs = [
            'query'=>"barcode:{$bcd}",
            'fmt'=>'json'
        ];

        $url = "https://musicbrainz.org/ws/2/release?" . http_build_query($urlArgs);

        Logger::debug("MusicBrainz", "request url: {$url}");
        
        $releases = self::$client->getJson($url);


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

    public static function ValidateMbid(string $id) : bool {
        return preg_match("/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i", $id)===1;
    }


}