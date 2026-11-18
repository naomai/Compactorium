<?php
namespace Naomai\Compactorium\Http;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'kernel.exception')]
final class ApiExceptionListener {
    public function __invoke(ExceptionEvent $event): void {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $e = $event->getThrowable();

        $status = $e instanceof HttpExceptionInterface
            ? $e->getStatusCode()
            : 500;

        $event->setResponse(new JsonResponse([
            'error' => $status < 5000
                ? $e->getMessage()
                : 'Internal server error',
        ], $status));
    }
}