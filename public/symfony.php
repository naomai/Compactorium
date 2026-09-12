<?php
namespace Naomai\Compactorium;

use Naomai\Compactorium\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

LegacyDotEnv::init();
Logger::init();
Database::init();

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};