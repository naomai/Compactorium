<?php
namespace Naomai\Compactorium\Services;

use Naomai\Compactorium\Http\Client;

class MusicBrainz {
    public static function GetAlbumByBarcode(string $bcd) : ?object {
        $urlArgs = [
            'query'=>"barcode:{$bcd}",
            'fmt'=>'json'
        ];

        $url = "https://musicbrainz.org/ws/2/release?" . http_build_query($urlArgs);

        return Client::getJson($url);

    }
}