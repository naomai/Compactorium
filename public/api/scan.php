<?php

use Naomai\Compactorium\Database;
use Naomai\Compactorium\Request;
use Naomai\Compactorium\Services\CoverArtArchive;
use Naomai\Compactorium\Services\MusicBrainz;

require __DIR__ . '/../../bootstrap/app.php';

$response = [];
$httpCode = 404;

try {
    header("Content-Type: application/json");
    $db = Database::connection();
    
    switch(Request::$method) {
        case "POST":
            $request = Request::post();

            $bcd = $request->text("bcd");
            $libraryId = $request->int("library", 0);

            if(!preg_match('/^(?:\d{8}|\d{13})$/', $bcd)){
                throw new Exception("Invalid barcode format.");
            }

            $stm = $db->prepare("INSERT INTO `scans` 
                (owner_id, library_id, barcode, scanned_at)
                VALUES
                (:owner_id, :library_id, :barcode, :scanned_at)
            ");

            $success = $stm->execute([
                'owner_id'=> 0,
                'library_id'=> $libraryId,
                'barcode'=> $bcd,
                'scanned_at'=> Database::timeNow()
            ]);

            if(!$success) {
                throw new Exception("Unable to add barcode.");
            }

		    $stm = $db->prepare("SELECT * FROM `scans` WHERE `library_id`=:library_id ORDER BY id DESC");
            $stm->execute(['library_id' => $libraryId]);
            
            $response = [
                'infoDownloaded' => false,
				'barcodes' => $stm->fetchAll(\PDO::FETCH_ASSOC)
            ];
            $httpCode = 201;

            break;
        case "GET":
            $request = Request::get();
            $libraryId = $request->int("library", 0);
            $stm = $db->prepare("SELECT * FROM `scans` WHERE `library_id`=:library_id");
            $stm->execute(['library_id' => $libraryId]);
            $response = [
                'barcodes' => $stm->fetchAll(\PDO::FETCH_ASSOC)
            ];
            $httpCode = 200;
            break;
        default:
            throw new Exception("Unsupported request method.");
    }

} 
catch(\PDOException $e) {
    if($e->getCode() == "23000") {
        $httpCode = 409;
        $response = ['error'=>"Duplicate value."];
    } else {
        $httpCode = 500;
        $response = ['error'=>"Database error."];
    }
}
catch (Exception $e) {
    $httpCode = 400;
    $response = ['error'=>$e->getMessage()];
}

http_response_code($httpCode);
echo json_encode($response);




