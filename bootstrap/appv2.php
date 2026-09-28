<?php
namespace Naomai\Compactorium;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

LegacyDotEnv::init();
Logger::init();
Services\CoverArtStore::init();
Services\Discogs::init();
Services\MusicBrainz::init();
Services\CoverArtArchive::init();
