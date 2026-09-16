<?php

use Naomai\Compactorium\Database;
use Naomai\Compactorium\Entity\Copy;
use Naomai\Compactorium\Entity\Library;
use Naomai\Compactorium\Request;
use Naomai\Compactorium\Views\LibraryCopyView;

require __DIR__ . '/../../bootstrap/app.php';

$response = [];
$httpCode = 404;

try {
    //$db = Database::connection();
    $em = Database::entityManager();
    
    switch(Request::$method) {
        case "GET":
            $request = Request::get();
            $libraryId = $request->int("library", 0);

            $library = $em->find(Library::class, $libraryId);

            $copiesList = $library->copies->getValues();

            $copiesMapped = array_map(
                fn($copy) => LibraryCopyView::fromCopy($copy),
                $copiesList
            );
            
            $response = [
                'copies' => $copiesMapped
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
header("Content-Type: application/json");

http_response_code($httpCode);
echo json_encode($response);
