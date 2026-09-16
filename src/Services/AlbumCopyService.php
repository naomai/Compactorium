<?php
namespace Naomai\Compactorium\Services;

use DateTimeImmutable;
use Naomai\Compactorium\Entity\Album;
use Naomai\Compactorium\Entity\Copy;
use Naomai\Compactorium\Entity\Library;

class AlbumCopyService {
    public static function buildForAlbum(Album $album, Library $library, ?object $user) : Copy {
        $copy = new Copy();
        $copy->album = $album;
        $copy->createdAt = new DateTimeImmutable();
        $copy->library = $library;
        $copy->scan = null;
        $copy->ownerId = 0;

        return $copy;
    }
}