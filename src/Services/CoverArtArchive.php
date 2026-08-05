<?php
namespace Naomai\Compactorium\Services;

use Exception;
use Naomai\Compactorium\Http\Client;

class CoverArtArchive {
    private static string $storagePath;

    public static function init() : void {
        self::$storagePath = $_ENV['BASE_DIR'] . "/storage/covers";

         if(!file_exists(self::$storagePath)) {
            mkdir(directory: self::$storagePath, recursive: true);
        }
    }

    public static function getReleaseFrontCover(object $mbReleaseData) : ?string {
        if(property_exists($mbReleaseData, 'error')) {
            throw new Exception("CoverArtArchive error: {$mbReleaseData->error}");
        }

        if($mbReleaseData->count==0){
            return null;
        }

        $releaseId = $mbReleaseData->releases[0]->id;
        $releaseGroupId = $mbReleaseData->releases[0]->{'release-group'}->id;

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