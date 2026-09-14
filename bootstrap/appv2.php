<?php
namespace Naomai\Compactorium;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

LegacyDotEnv::init();
Logger::init();
Database::init();
