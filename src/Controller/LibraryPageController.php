<?php

namespace Naomai\Compactorium\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Naomai\Compactorium\Entity\Copy;
use Naomai\Compactorium\Entity\Library;
use Naomai\Compactorium\Entity\Scan;
use Naomai\Compactorium\Entity\User;
use Naomai\Compactorium\Views\LibraryCopyView;
use Naomai\Compactorium\Views\ScanView;
use Naomai\Compactorium\Views\UserView;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/library', name: 'library_page_')]
class LibraryPageController extends AbstractController {

    public function __construct(private EntityManagerInterface $entityManager) {
    }

    #[Route('/', name: 'show', methods: ['GET', 'HEAD'])]
    public function showLibrary(): Response {
        $libraryId = 0;
        $library = $this->entityManager->find(Library::class, $libraryId);

        $copies = array_values(array_filter(
            $this->entityManager->getRepository(Copy::class)->findBy(
                ['library' => $library],
                ['id' => 'DESC']
            ),
            fn($copy) => $copy->album !== null
        ));

        $libraryContents = array_map(
            fn($copy) => LibraryCopyView::fromCopy($copy),
            $copies
        );

        $scans = $this->entityManager->getRepository(Scan::class)->findBy(
            ['library' => $library],
            ['id' => 'DESC']
        );

        $unresolvedBarcodes = array_map(
            fn($scan) => ScanView::fromScan($scan),
            array_values(array_filter($scans, fn($scan) =>
                $scan->copy === null || $scan->copy->album === null
            ))
        );

        /** @var User|null $user */
        $user = $this->getUser();
        $userInfo = null;
        if ($user instanceof User) {
            $userInfo = UserView::fromUser($user);
        }

        return $this->render('index.html.twig', [
            'libraryContents'    => $libraryContents,
            'unresolvedBarcodes' => $unresolvedBarcodes,
            'libraryId'          => $libraryId,
            'userInfo'           => $userInfo,
        ]);
    }
}