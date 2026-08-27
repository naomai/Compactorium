<?php
namespace Naomai\Compactorium\Services;

use Naomai\Compactorium\Http\CurlClient;
use Naomai\Compactorium\Logger;
use Naomai\Compactorium\Slugger;

class CoverArtArchive {
    private static string $storagePath;
    private static CurlClient $client;

    public static function init() : void {
        self::$storagePath = $_ENV['BASE_DIR'] . "/storage/covers";

        if(!file_exists(self::$storagePath)) {
            mkdir(directory: self::$storagePath, recursive: true);
        }

        self::$client = new CurlClient();
    }
    
    public static function getFrontCover(string $artist, string $title) : ?string {
        $album = MusicBrainz::SearchAlbum("artistname:\"{$artist}\" release:\"{$title}\"");
        return self::getReleaseFrontCover($album->rawJson);
    }

    public static function getReleaseFrontCover(object $mbReleaseData) : ?string {
        $releaseId = $mbReleaseData->releaseId;
        $releaseGroupId = $mbReleaseData->releaseGroupId;

        $artist = $mbReleaseData->releaseInfo->{'artist-credit'}[0]->name;
        $title = $mbReleaseData->releaseInfo->title;

        $slug = Slugger::slugFromArtistAndAlbum($artist, $title);

        Logger::debug("CoverArtArchive", "get front cover : {$slug}");

        $outputFile = self::getLocalReleaseFrontCover($slug);
        if($outputFile !== null) {
            return $outputFile;
        }

        $url = "http://coverartarchive.org/release/" . $releaseId . "/front";

        $frontFile = self::$client->downloadFile($url);

        if($frontFile === null) {
            $url = "http://coverartarchive.org/release-group/" . $releaseGroupId . "/front";
            $frontFile = self::$client->downloadFile($url);
        }

        if($frontFile === null) {
            return null;
        }

        $mimeType = self::$client->getLastRequestInfo()['content_type'];

        $extension = match($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            default      => 'bin'
        };

        $outputFile = self::$storagePath . "/" . $slug . "-front." . $extension;

        Logger::debug("CoverArtArchive", "store downloaded cover  {$slug}");


        rename($frontFile, $outputFile);

        return realpath($outputFile);
    }

    private static function getLocalReleaseFrontCover(string $slug) {

        $globSearch = glob(self::$storagePath . "/" . $slug . "-front.*");

        if(count($globSearch)==0) {
            return null;
        }

        Logger::debug("CoverArtArchive", "got local cover: {$slug}");


        return realpath($globSearch[0]);

    }
}