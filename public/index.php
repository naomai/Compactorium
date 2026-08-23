<?php
    namespace Naomai\Compactorium;

    require __DIR__ . '/../bootstrap/app.php';

    $db = Database::connection();

    $libraryId = 0;
    $stm = $db->prepare("SELECT * FROM `scans` WHERE `library_id`=:library_id");
    $stm->execute(['library_id'=>$libraryId]);
    $barcodes = $stm->fetchAll(\PDO::FETCH_ASSOC);

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
    <!-- <script src="https://unpkg.com/petite-vue" defer init></script> -->
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
            const html = barcodes.reduce((acc,bcd)=>acc+`<p class='bcdScan'>${bcd.copy?.artist} - ${bcd.copy?.albumTitle}</p>\n`, "");
            $("#list").html(html);
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
        });
    </script>
</head>
<body>
    <button id='scannerOpenBtn'>Open scanner</button>
    <div id="list"></div>

    <dialog id="scannerUi" class="popup">
        
        <h2>Scan barcode...</h2>
        <div id="dbg"></div>

        <div id="scanner"></div>


        <button class="close-btn" id="scannerCloseBtn">
            Close
        </button>

    </dialog>

</body>
</html>