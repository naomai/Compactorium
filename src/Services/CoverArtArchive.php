<?php
namespace Naomai\Compactorium\Services;

use Naomai\Compactorium\Logger;
use Naomai\Compactorium\Slugger;

class CoverArtArchive {
    public static function init() : void {

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

        $outputFile = CoverArtStore::getStoredCover($artist, $title);
        if($outputFile !== null) {
            return $outputFile;
        }

        $url = "http://coverartarchive.org/release/" . $releaseId . "/front";

        $frontFile = CoverArtStore::downloadCover($artist, $title, $url);

        if($frontFile === null) {
            $url = "http://coverartarchive.org/release-group/" . $releaseGroupId . "/front";
            $frontFile = CoverArtStore::downloadCover($artist, $title, $url);
        }

        if($frontFile === null) {
            return null;
        }
        return $frontFile;
    }


}