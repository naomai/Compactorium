<?php
namespace Naomai\Compactorium\Views;

use Naomai\Compactorium\Entity\User;

class UserView {
    public int $id;
    public string $email;
    public string $username;
    /** @var string[] */
    public array $roles;
    public bool $verified;

    public static function fromUser(User $user) : self {
        $view = new self();

        $view->id       = $user->id;
        $view->email    = $user->email;
        $view->username = $user->username;
        $view->roles    = $user->getRoles();
        $view->verified = $user->verified;

        return $view;
    }
}