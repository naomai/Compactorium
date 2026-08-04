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
            
        async function initScanner() {
            $("#dbg").text(`ready`);

            const previewCont = document.querySelector('#scanner');

            const previewElem = document.createElement("video");
            previewElem.autoplay = true;
            previewElem.playsInline = true;
            previewElem.style.width = "100%";
            previewCont.appendChild(previewElem);

            // you can use the controls to stop() the scan or switchTorch() if available
            const controls = await codeReader.decodeFromConstraints({
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

        $(document).ready(()=>{
            initScanner();
			reloadView();
        });


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
            const html = barcodes.reduce((acc,bcd)=>acc+`<p class='bcdScan'>${bcd.id} - ${bcd.barcode} ${bcd.scanned_at}</p>\n`, "");
            $("#list").html(html);
        }
    </script>
</head>
<body>
    <div id="dbg"></div>
    <div id="scanner"></div>
    <div id="list"></div>

</body>
</html>