<?php

namespace Naomai\Compactorium;

require_once __DIR__ . '/../vendor/autoload.php';

Config::init();
Logger::init();
Database::init();
Migration::init();
Migration::run();
Http\Client::init();
Services\MusicBrainz::init();
Services\CoverArtArchive::init();

