<?php

namespace Naomai\Compactorium;

require_once __DIR__ . '/../vendor/autoload.php';

Config::init();
Database::init();
Http\Client::init();
Services\CoverArtArchive::init();

