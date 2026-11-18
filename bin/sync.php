<?php
    namespace Naomai\Compactorium;

    require __DIR__ . '/../bootstrap/app.php';

    $em = $kernel
        ->getContainer()
        ->get('doctrine')
        ->getManager();

    ReleaseSyncWorker::init($em);

    const RUN_LIMIT_S = 300;

    $startTime = microtime(true);
    do {
        $iterStart = microtime(true);
        ReleaseSyncWorker::syncPendingBarcodes();
        time_sleep_until($iterStart + 1.0);
    } while(microtime(true) < $startTime + RUN_LIMIT_S);

    
