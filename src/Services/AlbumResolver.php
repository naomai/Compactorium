<?php
namespace Naomai\Compactorium\Services;

use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Naomai\Compactorium\Logger;
use Naomai\Compactorium\Entity\Album;
use Naomai\Compactorium\Entity\Barcode;
use Naomai\Compactorium\Slugger;

/**
 * Resolves album metadata and creates persistent Album entities.
 */
class AlbumResolver {
    private EntityManagerInterface $em;

    /**
     * Initializes with a Doctrine Entity Manager.
     *
     * @param EntityManagerInterface $em Doctrine Entity Manager
     */
    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    /**
     * Resolves a barcode to matching Album objects.
     *
     * Uses stored barcode data when available, otherwise fetches album metadata
     * from external sources.
     *
     * @param string $bcd Barcode to resolve.
     * @return array<int, Album> Matching Album objects.
     */
    public function resolveBarcode(string $bcd) : array {
        $bcds = $this->em
            ->getRepository(Barcode::class)
            ->findBy(['barcode'=>(int)$bcd]);

        if(count($bcds)==0) {
            Logger::debug("AlbumResolver", "bcd miss: {$bcd}");

            $albums = $this->downloadMetadataForBarcode($bcd);
            return $albums;
        }

        Logger::debug("AlbumResolver", "bcd hit: {$bcd}, albums: ".count($bcds));

        $albums = array_map(
            fn($bcd)=>$bcd->album, 
            $bcds
        );

        return $albums;
    }

    /**
     * Downloads and stores album metadata for a barcode.
     *
     * Searches external services for matching album data, then creates
     * persistent Album entities from the results.
     *
     * @param string $bcd Barcode to search for.
     * @return array<int, Album> Albums resolved from the barcode.
     */
    public function downloadMetadataForBarcode(string $bcd) : array {
        Logger::debug("AlbumResolver", "search bcd {$bcd}");
        $albDiscogs = Discogs::searchBarcode($bcd);

        $mastersCount = $albDiscogs!==null ? count($albDiscogs) : 0;
        Logger::debug("AlbumResolver", "found releases({$mastersCount})");

        $albums = [];


        if($mastersCount == 0) {
            $alb = MusicBrainz::GetAlbumByBarcode($bcd);
            if($alb !== null) {
                $albumData = self::saveAlbum($alb, $bcd);
                $albums[] = $albumData;
            }
        } else {
            foreach($albDiscogs as $master) {
                $alb = self::rawJsonFromDiscogsMasterData($master);
                self::rawJsonHydrateBcd($alb, $bcd);
                $albumData = self::saveAlbum($alb, $bcd);
                $albums[] = $albumData;
            }
        }
        return $albums;
    }

    public function resolveAlbumFromUrl(string $url) : ?Album {
        try{
            $master = Discogs::getReleaseFromUrl($url);
        }
        catch(InvalidArgumentException $e) {
            return null;
        }

        $alb = self::rawJsonFromDiscogsMasterData($master);
        $albumData = self::saveAlbum($alb);
        return $albumData;
    }

    private static function rawJsonFromDiscogsMasterData(object $master) : object {
        $title = $master->title;
        $artist = $master->artists[0]->name;
        $mbData = MusicBrainz::SearchAlbum("artistname:\"{$artist}\" release:\"{$title}\"");
        $alb = (object) [
            'artist'=>$artist,
            'title'=>$title,
            'year'=>$master->year,
            'rawJson'=>(object)[
                'discogs'=>$master,
                'mb'=>$mbData?->rawJson->mb
            ]
        ];

        return $alb;
    }

    private function rawJsonHydrateBcd(object $rawJson, string $bcd) : void {
        $rawJson->barcode = $bcd;
    }

    /**
     * Gets the front cover image for an Album.
     *
     * @param Album $album Album entity to get the cover for.
     * @return string|null Local path to the cover image, or null if unavailable.
     */
    public function getFrontCover(Album $album) : ?string {
        if(isset($album->rawJson->discogs->images[0])) {
            return Discogs::getFrontCover((object)$album->rawJson->discogs);
        }
        if(isset($album->rawJson->mb->releaseInfo)) {
            return CoverArtArchive::getReleaseFrontCover((object)$album->rawJson->mb);
        }
        return null;
    }

    /**
     * Creates and stores an Album and its barcode from raw metadata.
     *
     * @param object $albumData Raw JSON metadata combined from external sources.
     * @param ?string $bcd Barcode associated with the album.
     * @return Album Persisted Album entity.
     */
    private function saveAlbum(object $albumData, ?string $bcd=null) : Album {
        $em = $this->em;

        $slug = Slugger::slugFromArtistAndAlbum($albumData->artist, $albumData->title);
        $albObj = $em->find(Album::class, $slug);
        if($albObj !== null) {
            Logger::debug("AlbumResolver", "found album (already saved) {$albumData->artist} - {$albumData->title}");
        } else {
            $albObj = new Album();

            $albObj->artist = $albumData->artist;
            $albObj->title = $albumData->title;
            $albObj->slug = $slug;

            $albObj->year = $albumData->year;
            $albObj->rawJson = $albumData->rawJson;
            $albObj->createdAt = new DateTimeImmutable();
        }

        $coverPath = $this->getFrontCover($albObj);
        $coverPathRelative = ltrim(
            str_replace(realpath($_ENV['BASE_DIR']."/storage/covers"), '', $coverPath),
            DIRECTORY_SEPARATOR
        );

        $albObj->image = $coverPathRelative;

        $barcodeResolved = $albumData->barcode ?? $bcd;
        $bcdObj = null;

        if($barcodeResolved!==null) {
            $bcdObj = new Barcode();
            $bcdObj->barcode = $albumData->barcode ?? $bcd;
            $bcdObj->album = $albObj;
        }

        try{
            $em->persist($albObj);

            if($bcdObj!==null){
                $em->persist($bcdObj);
            }

            $em->flush();
        } catch (UniqueConstraintViolationException $e) {
            $em->clear();

            $existing = $em->getRepository(Album::class)->find($albObj->slug);
            if ($existing === null) {
                throw $e;
            }
            return $existing;
        }
        
        Logger::debug("AlbumResolver", "saved {$albObj->artist} - {$albObj->title}");
        return $albObj;
    }
}