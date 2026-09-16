<?php
namespace Naomai\Compactorium\Services;

use InvalidArgumentException;
use Naomai\Compactorium\Http\CurlClient;
use Naomai\Compactorium\Http\HttpClient;
use Naomai\Compactorium\Http\RateLimiter;
use Naomai\Compactorium\Logger;

class Discogs {
    private static HttpClient $client;

    const API_ENDPOINT = "https://api.discogs.com";


    public static function init() : void {
        $client = new CurlClient();
        $client->rateLimiter = new RateLimiter(
            delay: 1,
            backoffDelay: 10,
            httpRemainingHeader: "x-discogs-ratelimit-remaining",
            httpTooManyRequestsCode: 429
        );

        self::setHttpClient($client);
    }

    /**
     * Searches Discogs for releases matching a barcode.
     *
     * @param string $bcd Barcode to search for.
     * @return array<int, object>|null Matching Discogs Release/Master objects, or null if no matches are found.
     */
    public static function searchBarcode(string $bcd) : ?array {
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
            fn($releaseUrl) => self::getReleaseFromUrl($releaseUrl), 
            $masterUrls
        );

        return $masters;

    }

    /**
     * Fetches and validates a Discogs release from a frontend or API URL.
     *
     * @param string $url Discogs master/release URL.
     * @return object Discogs API Master/Release object.
     *
     * @throws InvalidArgumentException If the URL is not a valid Discogs URL.
     */
    public static function getReleaseFromUrl(string $url) : object {
        Logger::debug("Discogs", "GetReleaseFromUrl: {$url}");
        $apiUrl = Discogs::resolveApiUrlFromUrl($url);

        if($apiUrl === null) {
            throw new InvalidArgumentException("Provided Discogs URL is invalid");
        }

        $master = self::$client->getJson($apiUrl);

        return self::validateRelease($master);
    }

    private static function validateRelease(object $release) : object {
        if(!property_exists($release, 'title')) {
            throw new \Exception("Discogs error: {$release->message}");
        }

        return $release;        
    }

    /**
     * Sets the HTTP client used by the Discogs class.
     *
     * @param HttpClient $client HTTP client to use for requests.
     */
    public static function setHttpClient(HttpClient $client) : void {
        self::$client = $client;
    }

    /**
     * Gets the front cover image for a Discogs Master/Release.
     *
     * @param object $discogsAlbum Discogs API Master/Release object.
     * @return string|null Local path to the cover image, or null if no cover is available.
     */
    public static function getFrontCover(object $discogsAlbum) : ?string {
        $title = $discogsAlbum->title;
        $artist = $discogsAlbum->artists[0]->name;

        $localPath = CoverArtStore::getStoredCover($artist, $title);
        if($localPath) {
            return $localPath;
        }

        if(!isset($discogsAlbum->images[0])) {
            return null;
        }

        $imageUrl = $discogsAlbum->images[0]->{'resource_url'};

        return CoverArtStore::downloadCover($artist, $title, $imageUrl, client: self::$client);

        
    }

    /**
     * Resolves a Discogs API URL from frontent/API URL.
     *
     * @param string $url Discogs frontend/API URL.
     * @return string|null Resolved API URL, or null if the URL is invalid.
     */
    public static function resolveApiUrlFromUrl(string $url) : ?string {
        $parsed = parse_url($url);

        if(!isset($parsed['host'])) {
            return null;
        }

        if(strtolower($parsed['host']) == "api.discogs.com") {
            return $url;
        }


        if (!in_array(strtolower($parsed['host']), ['discogs.com', 'www.discogs.com'], true)) {
            return null;
        }

        $path = array_values(array_filter(
            explode('/', trim($parsed['path'] ?? '', '/')),
            static fn(string $part) => $part !== ''
        ));

        if(count($path)!=2) {
            return null;
        }

        $apiUrl = null;

        switch($path[0]) {
            case "master":
                if (!preg_match('/^(\d+)-/', $path[1], $matches)) {
                    return null;
                }
                $id = (int)$matches[1];

                $apiUrl = Discogs::API_ENDPOINT . "/masters/" . $id;


                break;
            case "release":
                if (!preg_match('/^(\d+)-/', $path[1], $matches)) {
                    return null;
                }
                $id = (int)$matches[1];

                $apiUrl = Discogs::API_ENDPOINT . "/releases/" . $id;


                break;

        }

        return $apiUrl;


    }
}