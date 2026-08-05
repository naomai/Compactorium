<?php
namespace Naomai\Compactorium\Services;

use Naomai\Compactorium\Http\Client;
use Naomai\Compactorium\Logger;

class CoverArtArchive {
    private static string $storagePath;

    public static function init() : void {
        self::$storagePath = $_ENV['BASE_DIR'] . "/storage/covers";

         if(!file_exists(self::$storagePath)) {
            mkdir(directory: self::$storagePath, recursive: true);
        }
    }

    public static function getReleaseFrontCover(object $mbReleaseData) : ?string {
        
        
        $releaseId = $mbReleaseData->releaseId;
        $releaseGroupId = $mbReleaseData->releaseGroupId;

        Logger::debug("CoverArtArchive", "get front cover : {$releaseId}");

        $outputFile = self::getLocalReleaseFrontCover($releaseId);
        if($outputFile !== null) {
            return $outputFile;
        }

        $url = "http://coverartarchive.org/release/" . $releaseId . "/front";

        $frontFile = Client::downloadFile($url);

        if($frontFile === null) {
            $url = "http://coverartarchive.org/release-group/" . $releaseGroupId . "/front";
            $frontFile = Client::downloadFile($url);
        }

        if($frontFile === null) {
            return null;
        }

        $mimeType = Client::getLastRequestInfo()['content_type'];

        $extension = match($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            default      => 'bin'
        };

        $outputFile = self::$storagePath . "/" . $releaseId . "-front." . $extension;

        Logger::debug("CoverArtArchive", "store downloaded cover  {$releaseId}");


        rename($frontFile, $outputFile);

        return $outputFile;
    }

    private static function getLocalReleaseFrontCover(string $releaseId) {

        $globSearch = glob(self::$storagePath . "/" . $releaseId . "-front.*");

        if(count($globSearch)==0) {
            return null;
        }

        Logger::debug("CoverArtArchive", "got local cover: {$releaseId}");


        return $globSearch[0];

    }
}