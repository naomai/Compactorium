<?php
namespace Naomai\Compactorium;

require __DIR__ . '/../bootstrap/app.php';

$sizes = [
    200, 640, 1280, 9999
];

$src = (string)$_GET['src'] ?? '';

$sizeIdx = $_GET['size'] ?? 9999;

$size = selectMatchingSize($sizes, $sizeIdx);

if (!preg_match('/^[\pL\pN]+(?:-+[\pL\pN]+)*-front\.[a-z]{3}$/u', $src)) {
    http_response_code(400);
    exit;
}

$dir = $_ENV['BASE_DIR'] . '/storage/covers';


$file = $dir . '/' . $src;

if (is_file($file)) {
    header('Content-Type: ' . mime_content_type($file));
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}

http_response_code(404);

function selectMatchingSize(array $sizeList, int $size) : int {
    sort($sizeList);
    $result = 0; $idx = 0;
    do {
        $result = $sizeList[$idx++];
    } while($result < $size);

    return $result;
}