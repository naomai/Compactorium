<?php
namespace Naomai\Compactorium\Services;

use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\EntityIdentityCollisionException;
use Naomai\Compactorium\Logger;
use Naomai\Compactorium\Models\Album;
use Naomai\Compactorium\Models\Barcode;
use Naomai\Compactorium\Slugger;

class AlbumResolver {
    private EntityManagerInterface $em;
    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

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

    public function downloadMetadataForBarcode(string $bcd) : array {
        Logger::debug("AlbumResolver", "search bcd {$bcd}");
        //$alb = MusicBrainz::GetAlbumByBarcode($bcd->barcode);
        $albDiscogs = Discogs::SearchBarcode($bcd);

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
                $title = $master->title;
                $artist = $master->artists[0]->name;
                $mbData = MusicBrainz::SearchAlbum("artistname:\"{$artist}\" release:\"{$title}\" barcode:{$bcd}");
                $alb = (object) [
                    'artist'=>$artist,
                    'title'=>$title,
                    'year'=>$master->year,
                    'barcode'=>$bcd,
                    'rawJson'=>(object)[
                        'discogs'=>$master,
                        'mb'=>$mbData?->rawJson->mb
                    ]
                ];
                $albumData = self::saveAlbum($alb, $bcd);
                $albums[] = $albumData;
            }
        }
        return $albums;
    }

    public function getFrontCover(Album $album) : ?string {
        if(isset($album->rawJson->discogs->images[0])) {
            return Discogs::getFrontCover((object)$album->rawJson->discogs);
        }
        if(isset($album->rawJson->mb->releaseInfo)) {
            return CoverArtArchive::getReleaseFrontCover((object)$album->rawJson->mb);
        }
        return null;
    }

    private function saveAlbum(object $albumData, string $bcd) : Album {
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

            //$albObj->year = Album::getYearFromMbDate($release->date);
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
        $bcdObj = new Barcode();
        $bcdObj->barcode = $albumData->barcode ?? $bcd;
        $bcdObj->album = $albObj;

        try{
            $em->persist($albObj);
            $em->persist($bcdObj);
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