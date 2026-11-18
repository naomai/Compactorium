<?php

namespace Naomai\Compactorium\Controller;

use Naomai\Compactorium\Entity\User;
use Naomai\Compactorium\Views\UserView;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'admin_')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController {

    #[Route('/', name: 'panel', methods: ['GET', 'HEAD'])]
    public function panel(): Response {
        /** @var User|null $user */
        $user = $this->getUser();
        $userInfo = null;
        if ($user instanceof User) {
            $userInfo = UserView::fromUser($user);
        }

        return $this->render('admin/index.html.twig',
            [
                'userInfo'=> $userInfo
            ]
        );
    }
}