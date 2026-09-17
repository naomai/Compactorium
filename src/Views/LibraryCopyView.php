<?php
namespace Naomai\Compactorium\Views;

use Naomai\Compactorium\Entity\Copy;

class LibraryCopyView {
    public int $id;
    public ?string $barcode;
    public ?string $albumTitle;
    public ?string $artist;
    public ?string $year;
    public ?string $image;
    public ?string $slug;
    public string $created_at;
    public ?AlbumDisambigView $disambiguation;

    public static function fromCopy(?Copy $copy) : ?self {
        if($copy===null) {
            return null;
        }

        $view = new self();

        $album = $copy->album;
        
        $view->id = $copy->id;
        $view->barcode = $copy?->scan?->barcode;
        $view->albumTitle = $album?->title;
        $view->artist = $album?->artist;
        $view->year = $album?->year;
        $view->image = $album?->image;
        $view->slug = $album?->slug;
        $view->created_at = $copy->createdAt->format(\DateTimeInterface::ATOM);

        if($copy->scan !== null) {
            $disambig = AlbumDisambigView::fromBarcodesCollection($copy->scan->barcodes);
            if(count($disambig->albums) > 1) {
                $view->disambiguation = $disambig;
            }
        }

        return $view;
    }
}