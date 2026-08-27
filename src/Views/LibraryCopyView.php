<?php
namespace Naomai\Compactorium\Views;

use Naomai\Compactorium\Models\Copy;

class LibraryCopyView {
    public int $id;
    public string $barcode;
    public ?string $albumTitle;
    public ?string $artist;
    public ?string $year;
    public ?string $image;

    public static function fromCopy(?Copy $copy) : ?self {
        if($copy===null) {
            return null;
        }

        $view = new self();

        $album = $copy->album;
        
        $view->id = $copy->id;
        $view->barcode = $copy->scan->barcode;
        $view->albumTitle = $album?->title;
        $view->artist = $album?->artist;
        $view->year = $album?->year;
        $view->image = $album?->image;

        return $view;
    }
}