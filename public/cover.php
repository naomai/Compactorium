<?php
namespace Naomai\Compactorium;

require __DIR__ . '/../bootstrap/app.php';

$src = $_GET['src'] ?? '';

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