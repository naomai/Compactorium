<?php

namespace Naomai\Compactorium\Security;

use Naomai\Compactorium\Security\AdminSetupChecker;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsEventListener(event: 'kernel.request')]
final class AdminSetupListener
{
    public function __construct(
        private AdminSetupChecker $adminSetupChecker,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (strpos($request->attributes->get('_route'), 'setup_')===0) {
            return;
        }

        if (!$this->adminSetupChecker->needsSetup()) {
            return;
        }

        $event->setResponse(new RedirectResponse(
            $this->urlGenerator->generate('setup_form')
        ));
    }
}