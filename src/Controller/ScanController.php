<?php

namespace Naomai\Compactorium\Controller;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Naomai\Compactorium\Entity\Album;
use Naomai\Compactorium\Entity\Library;
use Naomai\Compactorium\Entity\Scan;
use Naomai\Compactorium\Services\AlbumCopyService;
use Naomai\Compactorium\Services\AlbumResolver;
use Naomai\Compactorium\Views\LibraryCopyView;
use Naomai\Compactorium\Views\ScanView;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('api/scan', name: 'scan_')]
class ScanController extends AbstractController {

    public function __construct(private EntityManagerInterface $entityManager) {

    }

    #[Route('/', name: "create", methods: ['POST'])]
    public function create(Request $request): Response {
        $em = $this->entityManager;
        $payload = $request->getPayload();

        $bcd = $payload->getString('bcd');
        $libraryId = $payload->getInt('library', 0);
        $library = $em->find(Library::class, $libraryId);

        if($library === null) {
            return $this->json(["error" => "Invalid library ID"], 400);
        }

        // TODO AUTH
        if($library->ownerId !== 0) {
            return $this->json(["error" => "Unauthorized."], 401);
        }

        if(!preg_match('/^(?:\d{8}|\d{13})$/', $bcd)){
            return $this->json(["error" => "Invalid barcode format."], 400);
        }

        $scan = new Scan();
        $scan->ownerId = 0;
        $scan->library = $library;
        $scan->barcode = $bcd;
        $scan->scannedAt = new \DateTimeImmutable();

        try {
            $em->persist($scan);
            $em->flush();
        } catch (UniqueConstraintViolationException $e) {
            return $this->json(["error" => "Duplicate value."], 409);
        }

        $scans = $em->getRepository(Scan::class)->findBy(
            ['library'=>$library],
            ['id'=>'DESC']
        );

        $response = [
            'infoDownloaded' => false,
            'barcodes' => array_map(
                fn($scan)=>ScanView::fromScan($scan), 
                array_values(array_filter($scans, fn($scan)=>
                    $scan->copy === null || $scan->copy->album === null
                ))
            )
        ];
        return $this->json($response, 201);
    }

    #[Route('/', name: "list", methods: ['GET'])]
    public function list(Request $request): Response {
        $em = $this->entityManager;

        $libraryId = $request->query->getInt('library', 0);
        $library = $em->find(Library::class, $libraryId);

        if($library === null) {
            return $this->json(["error" => "Invalid library ID"], 400);
        }

        // TODO AUTH
        if($library->ownerId !== 0) {
            return $this->json(["error" => "Unauthorized."], 401);
        }

        $filter = $request->query->getString('type', 'all');

        $scans = $em->getRepository(Scan::class)->findBy(
            ['library'=>$library],
            ['id'=>'DESC']
        );

        if($filter=="unresolved") {
            $scans = array_values(array_filter(
                $scans,
                fn($scan) => $scan->copy === null || $scan->copy->album === null
            ));
        }

        $response = [
            'infoDownloaded' => false,
            'barcodes' => array_map(
                fn($scan)=>ScanView::fromScan($scan), 
                $scans
            )
        ];
        return $this->json($response);
    }

    #[Route('/{id}', name: "update", methods: ['PATCH'])]
    public function update(string $id, Request $request): Response {
        $em = $this->entityManager;

        $scan = $em->find(Scan::class, $id);
        if(!$scan) {
            return $this->json(["error" => "Scan was not found"], 404);
        }

        // TODO AUTH
        if($scan->ownerId !== 0) {
            return $this->json(["error" => "Unauthorized."], 401);
        }

        $payload = $request->getPayload();

        if($scan->copy !== null) {
            if($payload->has('albumSlug') && $payload->getString('albumSlug') !== '') {
                $copyView = LibraryCopyView::fromCopy($scan->copy);
                if($copyView->disambiguation !== null) {
                    $disambig = $copyView->disambiguation->albums;

                    $matchingAlbum = null;
                    $albumSlug = $payload->getString('albumSlug');
                    foreach($disambig as $a) {
                        if($a->slug === $albumSlug) {
                            $matchingAlbum = $a;
                            break;
                        }
                    }

                    if($matchingAlbum === null) {
                        return $this->json(["error" => "Invalid album selection for barcode"], 400);
                    }
                    $scan->copy->album = $em->find(Album::class, $matchingAlbum->slug);
                    $em->persist($scan->copy);
                }
            }
        } else {
            if($payload->has('discogsUrl') && $payload->getString('discogsUrl') !== '') {
                $library = $scan->library;

                $resolver = new AlbumResolver($em);
                $album = $resolver->resolveAlbumFromUrl(
                    $payload->getString('discogsUrl')
                );

                if($album === null) {
                    return $this->json(["error" => "Could not resolve album from URL"], 400);
                }

                $copy = AlbumCopyService::buildForAlbum($album, $library, null);
                $copy->scan = $scan;
                $em->persist($copy);
            }
        }

        $em->persist($scan);
        try {
            $em->flush();
        } catch (UniqueConstraintViolationException $e) {
            return $this->json(["error" => "Duplicate value."], 409);
        }

        return $this->json(ScanView::fromScan($scan));
    }
}