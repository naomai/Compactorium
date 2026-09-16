<?php
namespace Naomai\Compactorium;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

LegacyDotEnv::init();
Logger::init();
Database::init();
Migration::init();
Migration::run();
Services\CoverArtStore::init();
Services\Discogs::init();
Services\MusicBrainz::init();
Services\CoverArtArchive::init();
