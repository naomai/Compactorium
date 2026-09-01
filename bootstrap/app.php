<?php

namespace Naomai\Compactorium;

require_once __DIR__ . '/../vendor/autoload.php';

Config::init();
Logger::init();
Database::init();
Migration::init();
Migration::run();
Request::init();
Services\Discogs::init();
Services\MusicBrainz::init();
Services\CoverArtArchive::init();

error_reporting(E_ALL ^ E_DEPRECATED);

$auth = new \Delight\Auth\Auth(Database::connection());
