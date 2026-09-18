<?php

namespace Naomai\Compactorium\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Naomai\Compactorium\Entity\Library;
use Naomai\Compactorium\Views\LibraryCopyView;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('api/library', name: 'library_')]
class LibraryController extends AbstractController {

    public function __construct(private EntityManagerInterface $entityManager) {

    }

    #[Route('/{id}', name: "get", methods: ['GET', 'HEAD'])]
    public function get(
        Library $library
    ): Response {
        // TODO AUTH
        $copiesList = $library->copies->getValues();

        $copiesMapped = array_map(
            fn($copy) => LibraryCopyView::fromCopy($copy),
            $copiesList
        );
        
        $response = [
            'copies' => $copiesMapped
        ];
        return $this->json($response);
    }


}