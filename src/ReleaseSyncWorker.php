<?php
    namespace Naomai\Compactorium;

	use Naomai\Compactorium\Services\MusicBrainz;
    use Naomai\Compactorium\Models\Album;
    use Naomai\Compactorium\Models\Copy;
    use Naomai\Compactorium\Models\Release;
use Naomai\Compactorium\Models\Scan;
use Naomai\Compactorium\Services\CoverArtArchive;

    class ReleaseSyncWorker { 

        public static function syncPendingBarcodes() : void {
            $db = Database::connection();
            $bcds = self::getPendingBarcodes($db);

            $tasksDone = 0;

            foreach($bcds as $bcd) {
                Logger::debug("ReleaseSyncWorker", "search bcd {$bcd->barcode}");
                $alb = MusicBrainz::GetAlbumByBarcode($bcd->barcode);
                
                if($alb!==null) {
                    $release = $alb->releaseInfo;

                    $albObj = new Album();
                    $albObj->mbid = $release->{'release-group'}->id;
                    $albObj->artist = $release->{'artist-credit'}[0]->name;
                    $albObj->title = $release->title;
                    $albObj->year = Album::getYearFromMbDate($release->date);
                    $albObj->musicbrainzData = $release;
                    $albObj->sync();

                    $relObj = new Release();
                    $relObj->mbid = $release->id;
                    $relObj->groupMbid = $albObj->mbid;
                    $relObj->barcode = $release->barcode;
                    $relObj->musicbrainzData = $release;
                    $relObj->sync();

                    $copyObj = new Copy();
                    $copyObj->libraryId = $bcd->libraryId;
                    $copyObj->ownerId = $bcd->ownerId;
                    $copyObj->releaseMbid = $relObj->mbid;
                    $copyObj->scanId = $bcd->id;
                    $copyObj->sync();


                    Logger::debug("ReleaseSyncWorker", "got {$albObj->artist} - {$albObj->title}");

                    Logger::debug("ReleaseSyncWorker", "grab album cover");
                    $coverPath = CoverArtArchive::GetReleaseFrontCover($alb);
                    Logger::debug("ReleaseSyncWorker", "cover art file: {$coverPath}");

                    $tasksDone++;
                } else{
                    Logger::debug("ReleaseSyncWorker", "got shiet");

                }
                $bcd->processed = true;
                $bcd->sync();
            }
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