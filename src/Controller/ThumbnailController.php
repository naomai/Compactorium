<?php

namespace Naomai\Compactorium\Controller;

use DateTimeImmutable;
use Naomai\Compactorium\Services\CoverArtStore;
use Naomai\Compactorium\Services\ThumbnailGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/thumbnail', name: 'thumbnail_')]
class ThumbnailController extends AbstractController {

    #[Route('/front/{slug}.webp', name: 'frontcover', methods: ['GET'])]
    public function frontCover(string $slug, Request $request): Response {
        $size = $request->query->getInt('size', 9999);

        $gen = new ThumbnailGenerator();
        $gen->setSizeConstraints([50, 200, 400, 800, 1280, 9999]);

        $statusCode = 200;

        $coverArtLoc = CoverArtStore::getStoredCoverFromSlug($slug);


        if($coverArtLoc===null) {
            $coverArtLoc = $_ENV['BASE_DIR'] . "/public/assets/img/404_front.png";
            $statusCode = 404;
        }

        $lastModified = new DateTimeImmutable("@" . filemtime($coverArtLoc));

        $response = new Response();
        $response->setLastModified($lastModified);
        $response->setPublic();
        $response->setMaxAge(86400);

        if ($response->isNotModified($request)) {
            return $response;
        }

        $image = imagecreatefromstring(file_get_contents($coverArtLoc));

        
        $imageResized = $gen->createConstrainedFrontCover($image, $size);
        imagepalettetotruecolor($imageResized);
        
        ob_start();
        imagewebp($imageResized, null, 85);
        $data = ob_get_clean();

        $response->setStatusCode($statusCode);
        $response->setContent($data);
        $response->headers->set('Content-Type', 'image/webp');
        
        return $response;
    }
}