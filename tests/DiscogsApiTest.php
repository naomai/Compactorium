<?php declare(strict_types=1);
namespace Tests;

use Naomai\Compactorium\Logger;
use Naomai\Compactorium\Services\Discogs;
use PHPUnit\Framework\TestCase;

final class DiscogsApiTest extends TestCase{
    public function testSearchSuccessful() : void {
        $this->initDiscogs();
        
        $client = new Http\ClientStub();
        Discogs::SetHttpClient($client);

        $client->urlMapping = [
            "#//api\.discogs\.com/database/search\?.*barcode=0194398819426#" => [
                'status' => 200,
                'contentFile'=>"discogsapi_search_daria.json"
            ],
            "#//api\.discogs\.com/masters/2168104#" => [
                'status' => 200,
                'contentFile'=>"discogsapi_master_daria.json"
            ]
        ];

        $info = Discogs::SearchBarcode("0194398819426");

        $this->assertContainsOnlyObject($info);
        $this->assertObjectHasProperty('title', $info[0]);
        $this->assertObjectHasProperty('artists', $info[0]);
        $this->assertIsArray($info[0]->artists);
        //print_r($info);
    }



    private function initDiscogs() {
        Logger::init();
        Discogs::init();
    }
}
