<?php
    namespace Naomai\Compactorium;

    require __DIR__ . '/../bootstrap/app.php';

    $em = $kernel
        ->getContainer()
        ->get('doctrine')
        ->getManager();

    ReleaseSyncWorker::init($em);
    ReleaseSyncWorker::syncPendingBarcodes();