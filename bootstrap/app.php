<?php

namespace Naomai\Compactorium;

use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
$kernel = new Kernel($_ENV['APP_ENV'], (bool) $_ENV['APP_DEBUG']);
$kernel->boot();

LegacyDotEnv::init();
Logger::init();

$em = $kernel
    ->getContainer()
    ->get('doctrine')
    ->getManager();
Services\CoverArtStore::init();
Services\Discogs::init();
Services\MusicBrainz::init();
Services\CoverArtArchive::init();

error_reporting(E_ALL ^ E_DEPRECATED);

//$auth = new \Delight\Auth\Auth(Database::connection());


