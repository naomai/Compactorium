<?php

namespace Naomai\Compactorium\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Naomai\Compactorium\Entity\Album;
use Naomai\Compactorium\Entity\Copy;
use Naomai\Compactorium\Entity\Library;
use Naomai\Compactorium\Services\AlbumCopyService;
use Naomai\Compactorium\Services\AlbumResolver;
use Naomai\Compactorium\Views\LibraryCopyView;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('api/copy', name: 'copy_')]
class CopyController extends AbstractController {

    public function __construct(private EntityManagerInterface $entityManager) {

    }

    #[Route('/{id}', name: "get", methods: ['GET', 'HEAD'])]
    public function get(string $id): Response {
        $copy = $this->entityManager->find(Copy::class, $id);
        if (!$copy) {
            return $this->json(["error" => "Copy was not found" . $id], 404);
        }
        $dto = LibraryCopyView::fromCopy($copy);
        return $this->json($dto);
    }

    #[Route('/{id}', name: "update", methods: ['PATCH'])]
    public function update(string $id, Request $request): Response {
        $em = $this->entityManager;

        $copy = $em->find(Copy::class, $id);
        if (!$copy) {
            return $this->json(["error" => "Copy was not found" . $id], 404);
        }
        $modified = $request->getPayload();

        if($modified->has('albumSlug')) {
            $slug = $modified->getAlnum('albumSlug');
            $album = $em->find(Album::class, $slug);

            if(!$album) {
                return $this->json(["error" => "Invalid album ID"], 400);
            }
            $copy->album = $album;
        }

        if($modified->has('discogsUrl')) {
            $resolver = new AlbumResolver($em);
            $album = $resolver->resolveAlbumFromUrl(
                $modified->getString('discogsUrl')
            );
            $copy->album = $album;
        }

        $em->persist($copy);
        $em->flush();
        
        return $this->json(
            LibraryCopyView::fromCopy($copy)
        );
       
    }

    #[Route('/', name: "create", methods: ['POST'])]
    public function create(Request $request): Response {
        $em = $this->entityManager;
        $resolver = new AlbumResolver($em);

        $criteria = $request->getPayload();

        if(!$criteria->has('library')) {
            return $this->json(["error" => "Invalid library ID"], 400);
        }
                
        $libraryId = $criteria->getInt('library');
        $library = $em->find(Library::class, $libraryId);

        if($library===null) {
            return $this->json(["error" => "Invalid library ID"], 400);
        }

        $copy = null;

        if($criteria->has('discogsUrl')) {
            $newAlbum = $resolver->resolveAlbumFromUrl(
                $criteria->getString('discogsUrl')
            );

            $matchingCopies = $em
                ->getRepository(Copy::class)
                ->count([
                    'album'=>$newAlbum,
                    'library'=>$library,
                ]);

            if($matchingCopies > 0) {
                return $this->json(["error" => "Already added."], 400);
            }

            $copy = AlbumCopyService::buildForAlbum($newAlbum, $library, null);

            $em->persist($copy);
        }
        
        $em->flush();
        return $this->json(LibraryCopyView::fromCopy($copy));


    }

}