<?php
namespace Naomai\Compactorium\Services;

use Naomai\Compactorium\Http\CurlClient;
use Naomai\Compactorium\Http\HttpClient;
use Naomai\Compactorium\Logger;
use Naomai\Compactorium\Slugger;

class CoverArtStore {
    private static string $storagePath;
    private static CurlClient $client;

    public static function init() : void {
        self::$storagePath = $_ENV['BASE_DIR'] . "/storage/covers";

        if(!file_exists(self::$storagePath)) {
            mkdir(directory: self::$storagePath, recursive: true);
        }

        self::$client = new CurlClient();
    }

    public static function getStoredCover(string $artist, string $title) : ?string {
        $slug = Slugger::slugFromArtistAndAlbum($artist, $title);
        return self::getStoredCoverFromSlug($slug);
    }

    public static function getStoredCoverFromSlug(string $slug) : ?string {

        $globSearch = glob(self::$storagePath . "/" . $slug . "-front.*");

        if(count($globSearch)==0) {
            Logger::debug("CoverArtStore", "local cover MISS: {$slug}");
            return null;
        }

        Logger::debug("CoverArtStore", "got local cover: {$slug}");

        return realpath($globSearch[0]);
    }

    public static function storeCover(
        string $artist, string $title, 
        string $sourceFile, ?string $extension=null
    ) : string {
        $isUrl = filter_var($sourceFile, FILTER_VALIDATE_URL) !== false;

        if($isUrl) {
            return self::downloadCover($artist, $title, $sourceFile);
        }

        if($extension===null) {
            $extension = pathinfo($sourceFile, PATHINFO_EXTENSION);
        }

        $slug = Slugger::slugFromArtistAndAlbum($artist, $title);

        return self::commitCover($slug, $sourceFile, $extension);
        
    }

    public static function downloadCover(string $artist, string $title, string $url, ?HttpClient $client = null) {
        $slug = Slugger::slugFromArtistAndAlbum($artist, $title);
        if($client===null) {
            $client = self::$client;
        }

        Logger::debug("CoverArtStore", "downloading front cover : {$slug}");

        $frontFile = $client->downloadFile($url); 

        if($frontFile === null) {
            return null;
        }

        $mimeType = $client->getLastRequestInfo()['content_type'];

        $extension = match($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            default      => 'bin'
        };

        $outputFile = self::commitCover($slug, $frontFile, $extension);
        
        return $outputFile;
    }

    private static function commitCover(string $slug, string $srcPath, string $extension) {
        $outputFile = self::$storagePath . "/" . $slug . "-front." . $extension;

        Logger::debug("CoverArtStore", "saved front cover  {$slug}");

        rename($srcPath, $outputFile);
        return realpath($outputFile);
    }
}