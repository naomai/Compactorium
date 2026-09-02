<?php
    namespace Naomai\Compactorium;

    use Naomai\Compactorium\Models\Library;
    use Naomai\Compactorium\Models\Scan;
    use Naomai\Compactorium\Views\ScanView;

    require __DIR__ . '/../bootstrap/app.php';

    $em = Database::entityManager();

    $libraryId = 0;
    $library = $em->find(Library::class, $libraryId);

    $scans = $em->getRepository(Scan::class)->findBy(
        ['library'=>$library],
        ['id'=>'DESC']
    );

    $barcodes = array_map(
        fn($scan)=>ScanView::fromScan($scan), 
        $scans
    );

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <!-- <script src="https://cdn.jsdelivr.net/npm/@ericblade/quagga2@1.12.1/dist/quagga.js"></script> -->
    <!-- <script type="text/javascript" src="https://unpkg.com/@zxing/browser@latest"></script> -->
    <script src="https://unpkg.com/@zxing/library@latest"></script>
    <link rel="stylesheet" href="assets/common.css">
    <title>Compactorium</title>

<script>
    let barcodes = <?=json_encode($barcodes)?>;
    let lastBcd = null;
    const hints = new Map();
    hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, [
        ZXing.BarcodeFormat.EAN_13,
        ZXing.BarcodeFormat.EAN_8
    ]);

    const codeReader = new ZXing.BrowserMultiFormatReader(hints);

    var scannerControls;
            
        async function initScanner() {
            $("#dbg").text(`ready`);

            const previewCont = document.querySelector('#scanner');

            const previewElem = document.createElement("video");
            previewElem.autoplay = true;
            previewElem.playsInline = true;
            previewElem.style.width = "100%";
            previewCont.appendChild(previewElem);

            // you can use the controls to stop() the scan or switchTorch() if available
            scannerControls = await codeReader.decodeFromConstraints({
                video: {
                    facingMode: "environment",
                    width: { ideal: 1920 },
                    height: { ideal: 1080 }
                }}, previewElem, (result, error, controls) => {
                if (result) {
                    let bcd = result.getText();
                    if(bcd == lastBcd) {
                        return;
                    }
                    lastBcd = bcd;
                    //barcodes.push({barcode:bcd});
                    bcdScanned(bcd);
                    $("#dbg").text(`code:${bcd} len:${barcodes.length}`);
                }
            });
        }

        function stopScanner() {
            $('#scanner').html("");
            
            /*if(scannerControls) {
                scannerControls.stop();
            }*/

            codeReader.reset();
            codeReader.stopContinuousDecode();

        }

        function bcdGetError(resullt) {
            // https://github.com/serratus/quaggaJS/issues/237#issue-270285902
            var countDecodedCodes=0, err=0;
            $.each(result.codeResult.decodedCodes, function(id,error){
                if (error.error!=undefined) {
                    countDecodedCodes++;
                    err+=parseFloat(error.error);
                }
            });
            return err/countDecodedCodes;
        }

        function bcdScanned(bcd) {
            //navigator.vibrate(200);
            reloadView();
            sendBcd(bcd);
        }

        async function sendBcd(bcd) {
            const resp=await fetch("api/scan.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    bcd: bcd
                })
            });
		   const scanInfo=await resp.json();
		   let status=scanInfo.hasOwnProperty('barcodes');
		   $("#dbg").text(`status:${status?'OK ':scanInfo.error}`);
		   barcodes=scanInfo.barcodes;
		   reloadView();
        }



        function reloadView() {
            //const html = barcodes.reduce((acc,bcd)=>acc+`<p class='bcdScan'>${bcd.id} - ${bcd.barcode} ${bcd.scanned_at}</p>\n`, "");
            /*const html = barcodes.reduce((acc,bcd)=>acc+`<p class='bcdScan'>${bcd.copy?.artist} - ${bcd.copy?.albumTitle}</p>\n`, "");
            $("#list").html(html);*/
            store.scans = barcodes;

        }

        $(document).ready(()=>{
            //initScanner();
			reloadView();

            $(document).on("click", "#scannerOpenBtn", e=>{
                $("#scannerUi")[0].showModal();
                initScanner();
            });

            $(document).on("click", "#scannerCloseBtn", e=>{
                stopScanner();
                $("#scannerUi")[0].close();
            });

            
            $(document).on("dblclick", ".popup", function (e) {


                const rect = this.getBoundingClientRect();

                const inside =
                    rect.top <= e.clientY &&
                    e.clientY <= rect.top + rect.height &&
                    rect.left <= e.clientX &&
                    e.clientX <= rect.left + rect.width;

                if (!inside) {
                    this.close();
                }

            });
        });

        function showDisambigSelector(scan) {
            store.disambigScan = scan;
            $("#disambigUi")[0].showModal();

        }

        function formatArtistName(name) {
            const groups = name.match(/^(.+?)(?:\s+\(\d+\))?$/);
            if(groups === null) {
                return null;
            }
            return groups[1];
        }

        var store;

        
    </script>
    <script type="module">
        import { reactive, createApp } from 'https://esm.sh/pocket-vue'
        //import { reactive, createApp } from 'https://unpkg.com/petite-vue?module'

        store = reactive({
            scans: [],
            disambigScan: null,
        });

        createApp({store}).mount();
    </script>
</head>
<body>
    <header>
        <div id="logotype"><img 
            src="assets/img/logotype.png" 
            srcset="assets/img/logotype.png 640w, assets/img/logotype_s.png 450w"
            sizes="(width <= 800px) 450px, 640px"
            alt="Compactorium"/></div>
        <div id="headerdeco"><img 
            src="assets/img/barcodemonk.png" 
            srcset="assets/img/barcodemonk.png 273w, assets/img/barcodemonk_s.png 190w"
            sizes="(width <= 800px) 190px, 273px"
            alt="Barcode monk"/></div>


    </header>
    <main>
        <button id='scannerOpenBtn'>Open scanner</button>
        <div class="panel">
            <h2>Collection</h2>
            <div id="list" v-scope>
                <div v-for="scan in store.scans" class="album albumCopy">
                    <template v-if="scan.copy !== null">
                        <template v-if="scan.copy.disambiguation === undefined">
                            <img v-if="scan.copy.image !== null" :src="`cover.php?src=${scan.copy.image}`" alt="front cover" class="cover" />
                            <div class='albumDetails'>
                                <div class='albumTitle'>{{scan.copy.albumTitle}}</div>
                                <div class='albumArtist'>{{formatArtistName(scan.copy.artist)}} [{{scan.copy.year}}]</div>
                            </div>
                        </template>
                        <template v-else>
                            <button @click="showDisambigSelector(scan)">Multiple albums</button>
                             <!-- <div v-for="album in scan.copy.disambiguation.albums">
                                {{album.artist}} - {{album.title}}
                            </div> -->
                        </template>
                    </template>
                    <template v-else>
                        {{ scan.barcode }}
                    </template>
                </div>
            </div>
        </div>

    </main>

    <dialog id="scannerUi" class="popup">
        
        <h2>Scan barcode...</h2>
        <div id="dbg"></div>

        <div id="scanner"></div>


        <button class="close-btn" id="scannerCloseBtn">
            Close
        </button>

    </dialog>

    <dialog id="disambigUi" class="popup" v-scope>
        <h2>Select the correct album...</h2>

        <div v-for="album in store.disambigScan.copy.disambiguation.albums" class="album">
            <img v-if="album.image !== null" :src="`cover.php?src=${album.image}`" alt="front cover" class="cover" />
            <div class='albumDetails'>
                <div class='albumTitle'>{{album.title}}</div>
                <div class='albumArtist'>{{formatArtistName(album.artist)}} [{{album.year}}]</div>
            </div>
        </div>
    </dialog>

</body>
</html>