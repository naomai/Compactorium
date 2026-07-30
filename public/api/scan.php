<?php

use Naomai\Compactorium\Database;
use Naomai\Compactorium\Request;
use Naomai\Compactorium\Services\CoverArtArchive;
use Naomai\Compactorium\Services\MusicBrainz;

require __DIR__ . '/../../bootstrap/app.php';

$response = [];

try{
    header("Content-Type: application/json");
    $db = Database::connection();
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
    $response = [
        'infoDownloaded' => false
    ];

} 
catch (Exception $e) {
    http_response_code(400);
    die(json_encode(['error'=>$e->getMessage()]));
}

http_response_code(201);
echo json_encode($response);




