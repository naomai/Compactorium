<?php
    namespace Naomai\Compactorium;

    require __DIR__ . '/../bootstrap/app.php';

    ReleaseSyncWorker::init();
    ReleaseSyncWorker::syncPendingBarcodes();