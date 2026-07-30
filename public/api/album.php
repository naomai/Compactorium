<?php

use Naomai\Compactorium\Database;
use Naomai\Compactorium\Services\MusicBrainz;

require __DIR__ . '/../../bootstrap/app.php';

$db = Database::connection();

print_r(MusicBrainz::GetAlbumByBarcode("727361393229"));

