<?php declare(strict_types=1);
namespace Tests;

use Naomai\Compactorium\LegacyDotEnv;
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

    public function testApiUrlResolution() : void {
        $this->initDiscogs();

        $apiEndpoint = Discogs::API_ENDPOINT;

        // POSITIVE
        $url = Discogs::resolveApiUrlFromUrl("https://www.discogs.com/master/4894-Black-Sabbath-Black-Sabbath-Vol-4?format=CD");
        $this->assertIsString($url, "master api url is string");
        $this->assertEquals("{$apiEndpoint}/masters/4894", $url, "master api url correct");

        $url = Discogs::resolveApiUrlFromUrl("https://www.discogs.com/release/15266573-Black-Nail-Cabaret-Gods-Verging-On-Sanity");
        $this->assertIsString($url, "release api url is string");
        $this->assertEquals("{$apiEndpoint}/releases/15266573", $url, "release api url correct");

        $url = Discogs::resolveApiUrlFromUrl("https://api.discogs.com/releases/15266573");
        $this->assertIsString($url, "api url from api url is string");
        $this->assertEquals("{$apiEndpoint}/releases/15266573", $url, "api url from api url correct");

        // NEGATIVE
        $url = Discogs::resolveApiUrlFromUrl("https://www.discogs.com/master/xyzabs234");
        $this->assertNull($url, "master api url invalid");

        $url = Discogs::resolveApiUrlFromUrl("https://www.discogs.com/release/xyzabs234");
        $this->assertNull($url, "release api url invalid");

        $url = Discogs::resolveApiUrlFromUrl("https://www.discogs.com/artist/3543255-Black-Nail-Cabaret");
        $this->assertNull($url, "release api url unsupported types");

        
        $url = Discogs::resolveApiUrlFromUrl("https://www.discongs.com/master/4894-Black-Sabbath-Black-Sabbath-Vol-4?format=CD");
        $this->assertNull($url, "api url invalid domain");

        $url = Discogs::resolveApiUrlFromUrl("/master/4894-Black-Sabbath-Black-Sabbath-Vol-4?format=CD");
        $this->assertNull($url, "api url malformed");
    }



    private function initDiscogs() {
        Logger::init();
        LegacyDotEnv::init();
        Discogs::init();
    }
}
