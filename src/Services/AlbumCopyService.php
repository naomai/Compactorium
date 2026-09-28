<?php
namespace Naomai\Compactorium\Services;

use DateTimeImmutable;
use Naomai\Compactorium\Entity\Album;
use Naomai\Compactorium\Entity\Copy;
use Naomai\Compactorium\Entity\Library;
use Naomai\Compactorium\Entity\User;

class AlbumCopyService {
    public static function buildForAlbum(Album $album, Library $library, User $user) : Copy {
        $copy = new Copy();
        $copy->album = $album;
        $copy->createdAt = new DateTimeImmutable();
        $copy->library = $library;
        $copy->scan = null;
        $copy->owner = $user;

        return $copy;
    }
}