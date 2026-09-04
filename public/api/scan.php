<?php

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Naomai\Compactorium\Database;
use Naomai\Compactorium\Models\Library;
use Naomai\Compactorium\Models\Scan;
use Naomai\Compactorium\Request;
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




