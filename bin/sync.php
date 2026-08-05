<?php
    namespace Naomai\Compactorium;

    require __DIR__ . '/../bootstrap/app.php';

    $db = Database::connection();

    ReleaseSyncWorker::syncPendingBarcodes();