<?php
    namespace Naomai\Compactorium;

    use Naomai\Compactorium\Entity\Copy;
    use Naomai\Compactorium\Entity\Library;
    use Naomai\Compactorium\Entity\Scan;
    use Naomai\Compactorium\Views\LibraryCopyView;
    use Naomai\Compactorium\Views\ScanView;

    require __DIR__ . '/../bootstrap/app.php';

    $em = Database::entityManager();

    $libraryId = 0;
    $library = $em->find(Library::class, $libraryId);

    $copies = array_values(array_filter(
        $em->getRepository(Copy::class)->findBy(
            ['library'=>$library],
            ['id'=>'DESC']
        ),
        fn($copy) => $copy->album !== null
    ));

    $libraryContents = array_map(
        fn($copy)=>LibraryCopyView::fromCopy($copy), 
        $copies
    );


    $scans = $em->getRepository(Scan::class)->findBy(
        ['library'=>$library],
        ['id'=>'DESC']
    );



    $unresolvedBarcodes = array_map(
        fn($scan)=>ScanView::fromScan($scan), 
        array_values(array_filter($scans, fn($scan)=>
            $scan->copy === null || $scan->copy->album === null
        ))
    );

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
    <link rel="stylesheet" href="assets/common.css">
    <title>Compactorium</title>
</head>
<body class="ancient">
    <header>
        <table><tr>
            <td id="logotype"><img 
                src="assets/img/logotype_s.png" 
                alt="Compactorium"/></td>
            <td id="headerdeco"><img 
                src="assets/img/barcodemonk_s.png" 
                alt="Barcode monk"/></td>
        </tr></table>
    </header>
    <main>
        <div class="panel">
            <h2 class='panelTitle'>. : Collection : .</h2>
            <table id="collectionTable">
<?php
    usort(
        $libraryContents, 
        function($a,$b){
            $artistCmp = strcasecmp($a->artist, $b->artist);
            $yearCmp = strcasecmp($a->year, $b->year);
            return $artistCmp!==0 ? $artistCmp : $yearCmp;
        }
    );

    foreach($libraryContents as $copy){
        echo <<<TEMPLATE
                <tr class="album albumCopy">
                    <td>
                        <img src="api/thumbnail/front/{$copy->slug}.jpg?size=200" alt="front cover" class="cover" />
                    </td>
                    <td class='albumDetails'>
                        <div class='albumTitle'>{$copy->albumTitle}</div>
                        <div class='albumArtist'>{$copy->artist} [{$copy->year}]</div>
                    </td>
                </tr>
TEMPLATE;
    }

?>
            </table>
        </div>
    </main>
    <footer>
        <p>Your browser predates several technologies required by Compactorium. </p>
        <p>What you see is merely a reconstruction, maintained out of respect for the stubbornly undead.</p>
        <img src="assets/img/valid-any.gif" alt="Compatible with Any Browser 0.0"/>
        <img src="assets/img/valid-xhtml10.gif" alt="Valid XHTML 1.0"/>
    </footer>
</body>
</html>