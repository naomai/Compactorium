<?php
    namespace Naomai\Compactorium;

    use DateTimeImmutable;
    use Doctrine\ORM\EntityManagerInterface;
    use Naomai\Compactorium\Entity\Copy;
    use Naomai\Compactorium\Entity\Scan;
    use Naomai\Compactorium\Services\AlbumResolver;


    class ReleaseSyncWorker { 
        private static EntityManagerInterface $em;
        private static AlbumResolver $resolver;


        public static function init() : void {
            self::$em = Database::entityManager();
            self::$resolver = new AlbumResolver(self::$em);
        }

        public static function syncPendingBarcodes() : void {
            $em = self::$em;

            $bcds = self::getPendingBarcodes();

            Logger::debug("ReleaseSyncWorker", "pending barcodes: ".count($bcds));

            $tasksDone = 0;

            foreach($bcds as $bcd) {

                $library = $bcd->library;

                $albums = self::$resolver->resolveBarcode($bcd->barcode);
                
                $copyObj = new Copy();
                $copyObj->library = $library;
                $copyObj->ownerId = $bcd->ownerId;
                $copyObj->scan = $bcd;
                $copyObj->createdAt = new DateTimeImmutable();

                
                if(count($albums)==1) {
                    $copyObj->album = $albums[0];
                } 

                if(count($albums) > 0) {
                    $em->persist($copyObj);
                } else {
                    Logger::debug("ReleaseSyncWorker", "got shiet");
                }

                $bcd->processed = true;
                $em->flush();

                $tasksDone++;
            }
        }


        private static function getPendingBarcodes() : array {
            $scans = self::$em
                ->getRepository(Scan::class)
                ->createQueryBuilder('s')
                ->leftJoin(
                    Copy::class,
                    'c',
                    'WITH',
                    'c.scan = s'
                )
                ->where('c.id IS NULL')
                ->andWhere('s.processed = :processed')
                ->setParameter('processed', false)
                ->getQuery()
                ->getResult();

            return $scans;
        }

    }