<?php
namespace Naomai\Compactorium\Views;

use Doctrine\Common\Collections\Collection;
use Naomai\Compactorium\Models\Barcode;

class AlbumDisambigView {
    public array $albums;

    public static function fromBarcodesCollection(Collection $barcodes) : self {
        $view = new self();

        $view->albums = $barcodes->map(
            fn(Barcode $bcd) => AlbumBasicView::fromAlbum($bcd->album)
        )->toArray();
        return $view;
    }
}