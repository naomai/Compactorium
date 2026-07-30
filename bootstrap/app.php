<?php

namespace Naomai\Compactorium;

require_once __DIR__ . '/../vendor/autoload.php';

Config::init();
Http\Client::init();
Services\CoverArtArchive::init();

