<?php
namespace Naomai\Compactorium\Services;

use Naomai\Compactorium\Http\ClientContext;
use Naomai\Compactorium\Http\RateLimiter;
use Naomai\Compactorium\Logger;

class Discogs {
    private static ClientContext $client;


    public static function init() : void {
        self::$client = new ClientContext();
        self::$client->rateLimiter = new RateLimiter(
            delay: 1,
            backoffDelay: 10,
            httpRemainingHeader: "x-discogs-ratelimit-remaining",
            httpTooManyRequestsCode: 429
        );
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

        $masterIds = [];


        foreach($search->results as $release) {
            array_push($masterIds, $release->master_id);
        }

        $masterIds = array_unique($masterIds);

        $masters = array_map(
            fn($masterId) => self::GetMasterInfo($masterId), 
            $masterIds
        );

        return $masters;

    }

    public static function GetMasterInfo(int $masterId) : object {
        $url = "https://api.discogs.com/masters/{$masterId}";
        Logger::debug("Discogs", "SearchBarcode url: {$url}");
        $master = self::$client->getJson($url);

        if(!property_exists($master, 'title')) {
            throw new \Exception("Discogs error: {$master->message}");
        }

        return $master;

    }
}