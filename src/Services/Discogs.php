<?php
namespace Naomai\Compactorium\Services;

use Naomai\Compactorium\Http\CurlClient;
use Naomai\Compactorium\Http\HttpClient;
use Naomai\Compactorium\Http\RateLimiter;
use Naomai\Compactorium\Logger;

class Discogs {
    private static HttpClient $client;


    public static function init() : void {
        $client = new CurlClient();
        $client->rateLimiter = new RateLimiter(
            delay: 1,
            backoffDelay: 10,
            httpRemainingHeader: "x-discogs-ratelimit-remaining",
            httpTooManyRequestsCode: 429
        );

        self::SetHttpClient($client);
    }

    public static function SearchBarcode(string $bcd) : ?array {
        $urlArgs = [
            'barcode'=>"{$bcd}",
            'type'=>"release",
        ];

        $url = "https://api.discogs.com/database/search?" . http_build_query($urlArgs);

        Logger::debug("Discogs", "SearchBarcode url: {$url}");

        $headers = [];
        if(isset($_ENV['DISCOGS_KEY']) && isset($_ENV['DISCOGS_SECRET'])) {
            $headers['Authorization'] = "Discogs key={$_ENV['DISCOGS_KEY']}, secret={$_ENV['DISCOGS_SECRET']}";
        }

        $search = self::$client->getJson($url, $headers);

        if(!property_exists($search, 'results')) {
            throw new \Exception("Discogs error: {$search->message}");
        }

        Logger::debug("Discogs", "SearchBarcode results: " . count($search->results));


        if(count($search->results) == 0) {
            Logger::debug("Discogs", "no releases");
            return null;
        }

        $masterUrls = [];


        foreach($search->results as $release) {
            $releaseUrl = $release->master_url;
            if($release->master_id === 0) {
                $releaseUrl = $release->resource_url;
            }
            $masterUrls[] = $releaseUrl;
        }

        $masterUrls = array_unique($masterUrls);

        $masters = array_map(
            fn($releaseUrl) => self::GetReleaseFromUrl($releaseUrl), 
            $masterUrls
        );

        return $masters;

    }

    public static function GetReleaseFromUrl(string $url) : object {
        Logger::debug("Discogs", "GetReleaseFromUrl: {$url}");
        $master = self::$client->getJson($url);

        return self::ValidateRelease($master);
    }

    private static function ValidateRelease(object $release) : object {
        if(!property_exists($release, 'title')) {
            throw new \Exception("Discogs error: {$release->message}");
        }

        return $release;        
    }

    public static function SetHttpClient(HttpClient $client) : void {
        self::$client = $client;
    }
}