<?php

namespace Naomai\Compactorium\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Naomai\Compactorium\Models\Album;
use Naomai\Compactorium\Models\Copy;
use Naomai\Compactorium\Views\LibraryCopyView;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('api/copy', name: 'copy_')]
class CopyController extends AbstractController {

    public function __construct(private EntityManagerInterface $entityManager)
    {
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

        $em->persist($copy);
        
        return $this->json(
            LibraryCopyView::fromCopy($copy)
        );
       
    }
}