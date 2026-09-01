<?php
namespace Naomai\Compactorium\Views;

use Naomai\Compactorium\Models\Album;

class AlbumBasicView {
    public string $slug;
    public string $title;
    public string $artist;
    public string $year;
    public ?string $image;

    public static function fromAlbum(Album $album) : self {
        $view = new self();

        $view->slug = $album->slug;
        $view->title = $album->title;
        $view->artist = $album->artist;
        $view->year = $album->year;
        $view->image = $album->image;

        return $view;
    }
}