<?php
namespace Naomai\Compactorium\Views;

use Naomai\Compactorium\Entity\Scan;

class ScanView {
    public int $id;
    public string $barcode;
    public string $scanned_at;
    public ?LibraryCopyView $copy;

    public static function fromScan(Scan $scan) : self {
        $view = new self();

        $view->id = $scan->id;
        $view->barcode = $scan->barcode;
        $view->scanned_at = $scan->scannedAt->format(\DateTimeInterface::ATOM);
        $view->copy = LibraryCopyView::fromCopy($scan?->copy);


        return $view;
    }
}