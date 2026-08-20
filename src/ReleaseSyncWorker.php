<?php
    namespace Naomai\Compactorium;

	use Naomai\Compactorium\Services\MusicBrainz;
    use Naomai\Compactorium\Models\Album;
    use Naomai\Compactorium\Models\Copy;
    use Naomai\Compactorium\Models\Release;
use Naomai\Compactorium\Models\Scan;
use Naomai\Compactorium\Services\CoverArtArchive;
use Naomai\Compactorium\Services\Discogs;

    class ReleaseSyncWorker { 

        public static function syncPendingBarcodes() : void {
            $db = Database::connection();
            $bcds = self::getPendingBarcodes($db);

            $tasksDone = 0;

            foreach($bcds as $bcd) {
                Logger::debug("ReleaseSyncWorker", "search bcd {$bcd->barcode}");
                //$alb = MusicBrainz::GetAlbumByBarcode($bcd->barcode);
                $albDiscogs = Discogs::SearchBarcode($bcd->barcode);

                $mastersCount = count($albDiscogs);
                Logger::debug("ReleaseSyncWorker", "found releases({$mastersCount})");


                $lastRelease = null;
                

                if($mastersCount == 0) {
                    $alb = MusicBrainz::GetAlbumByBarcode($bcd->barcode);

                    $lastRelease = self::saveRelease($alb, null, $bcd->barcode);
                } else {
                    foreach($albDiscogs as $master) {
                        $title = $master->title;
                        $artist = $master->artists[0]->name;
                        $alb = MusicBrainz::SearchAlbum("artistname:\"{$artist}\" release:\"{$title}\" barcode:{$bcd->barcode}");
                        $lastRelease = self::saveRelease($alb, $master, $bcd->barcode);
                    }
                }
                
                if($lastRelease !== null) {
                    $copyObj = new Copy();
                    $copyObj->libraryId = $bcd->libraryId;
                    $copyObj->ownerId = $bcd->ownerId;
                    $copyObj->releaseMbid = ($mastersCount == 1) ? $lastRelease->mbid : null;
                    $copyObj->scanId = $bcd->id;
                    $copyObj->sync();

                    $tasksDone++;
                } else{
                    Logger::debug("ReleaseSyncWorker", "got shiet");

                }
                $bcd->processed = true;
                $bcd->sync();
            }
        }

        private static function saveRelease(object $releaseMb, ?object $masterDiscogs, string $bcd) : Release {
            $release = $releaseMb->releaseInfo;

            $albObj = new Album();
            $albObj->mbid = $release->{'release-group'}->id;
            $albObj->artist = $release->{'artist-credit'}[0]->name;
            $albObj->title = $release->title;
            //$albObj->year = Album::getYearFromMbDate($release->date);
            $albObj->year = $masterDiscogs->year ?? Album::getYearFromMbDate($release->date) ?? 0;
            $albObj->musicbrainzData = $release;
            $albObj->sync();

            $relObj = new Release();
            $relObj->mbid = $release->id;
            $relObj->groupMbid = $albObj->mbid;
            $relObj->barcode = $release->barcode ?? $bcd;
            $relObj->musicbrainzData = $release;
            $relObj->sync();
            
            Logger::debug("ReleaseSyncWorker", "saved {$albObj->artist} - {$albObj->title}");

            Logger::debug("ReleaseSyncWorker", "grab album cover");
            $coverPath = CoverArtArchive::GetReleaseFrontCover($releaseMb);
            Logger::debug("ReleaseSyncWorker", "cover art file: {$coverPath}");

            return $relObj;
        }

        private static function getPendingBarcodes(\PDO $db) : array {
            $stm = $db->query("
                SELECT b.*
                FROM scans b
                LEFT JOIN copies c
                    ON c.scan_id = b.id
                WHERE c.scan_id IS NULL AND b.processed = FALSE;
                ",  \PDO::FETCH_ASSOC);
            return array_map(
                fn($scanRow) => new Scan($scanRow), 
                $stm->fetchAll()
            );
        }

    }