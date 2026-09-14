<?php
namespace Naomai\Compactorium;

require __DIR__ . '/../bootstrap/appv2.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};