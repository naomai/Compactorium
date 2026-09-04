<?php

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Naomai\Compactorium\Database;
use Naomai\Compactorium\Models\Album;
use Naomai\Compactorium\Models\Library;
use Naomai\Compactorium\Models\Scan;
use Naomai\Compactorium\Request;
use Naomai\Compactorium\Views\LibraryCopyView;
use Naomai\Compactorium\Views\ScanView;

require __DIR__ . '/../../bootstrap/app.php';

$response = [];
$httpCode = 404;

try {
    $em = Database::entityManager();

    $userId = 0;
    
    switch(Request::$method) {
        case "POST":
            $request = Request::post();

            $bcd = $request->text("bcd");
            $libraryId = $request->int("library", 0);
            $library = $em->find(Library::class, $libraryId);

            if($library->ownerId !== $userId) {
                throw new Exception("Unauthorized.");
            }

            if(!preg_match('/^(?:\d{8}|\d{13})$/', $bcd)){
                throw new Exception("Invalid barcode format.");
            }


            $scan = new Scan;
            $scan->ownerId = 0;
            $scan->library = $library;
            $scan->barcode = $bcd;
            $scan->scannedAt = new \DateTimeImmutable();

            $em->persist($scan);
            $em->flush();

            $scans = $em->getRepository(Scan::class)->findBy(
                ['library'=>$library],
                ['id'=>'DESC']
            );

            $response = [
                'infoDownloaded' => false,
				'barcodes' => array_map(
                    fn($scan)=>ScanView::fromScan($scan), 
                    $scans
                )
            ];
            $httpCode = 201;

            break;
        case "GET":
            $request = Request::get();
            $libraryId = $request->int("library", 0);
            $library = $em->find(Library::class, $libraryId);

            if($library->ownerId !== $userId) {
                throw new Exception("Unauthorized.");
            }

            $scans = $em->getRepository(Scan::class)->findBy(
                ['library'=>$library],
                ['id'=>'DESC']
            );

            $response = [
                'infoDownloaded' => false,
				'barcodes' => array_map(
                    fn($scan)=>ScanView::fromScan($scan), 
                    $scans
                )
            ];
            $httpCode = 200;
            break;

        case "PATCH":
            $query = Request::get();
            $scanId = $query->int("scan");

            $scan = $em->find(Scan::class, $scanId);

            if($scan->ownerId !== $userId) {
                throw new Exception("Unauthorized.");
            }
            

            $request = Request::content();


            if($scan->copy !== null) {
                if($request->text('albumSlug', "") !== "") {
                    $copyView = LibraryCopyView::fromCopy($scan->copy);
                    if(property_exists($copyView, 'disambiguation')) {
                        $disambig = $copyView->disambiguation->albums;

                        $matchingAlbum = array_find(
                            $disambig, 
                            fn($a) => $a->slug==$request->text('albumSlug')
                        );
                        if($matchingAlbum===null) {
                            throw new Exception("Invalid album selection for barcode");
                        }
                        $scan->copy->album = $em->find(Album::class, $matchingAlbum->slug);
                        $em->persist($scan->copy);
                    }

                }
            }
            
            $em->persist($scan);
            $em->flush();

            $httpCode = 200;
            $response = ScanView::fromScan($scan);

            break;

        default:
            throw new Exception("Unsupported request method.");
    }

} 
catch(UniqueConstraintViolationException $e) {
    $httpCode = 409;
    $response = ['error'=>"Duplicate value."];
}
catch (Exception $e) {
    $httpCode = 400;
    $response = ['error'=>$e->getMessage()];
}

header("Content-Type: application/json");

http_response_code($httpCode);
echo json_encode($response);




