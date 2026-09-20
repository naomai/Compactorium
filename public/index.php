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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://unpkg.com/@zxing/library@latest"></script>
    <link rel="stylesheet" href="assets/common.css">
    <title>Compactorium</title>

    <script type="text/javascript">
        /* Redirect STUBBORNLY UNDEAD browsers to Ancient version */

        var testEl1 = document.createElement('div');
        testEl1.style.display = 'grid';
        var gridSupport = testEl1.style.display === 'grid';

        var testEl2 = document.createElement("input");
        testEl2.setAttribute("type", "color");
        var html5Support = testEl2.type !== "text";

        if(!html5Support || !gridSupport){
            window.location = "./ancient.php";

        }

    </script>

<script>
    /* LOCAL CONFIG */
    const configKey = 'compactorium-config';

    function getConfig(key, defaultValue = null) {
        const config = JSON.parse(localStorage.getItem(configKey) ?? '{}');
        return config[key] ?? defaultValue;
    }

    function setConfig(key, value) {
        const config = JSON.parse(localStorage.getItem(configKey) ?? '{}');
        config[key] = value;
        localStorage.setItem(configKey, JSON.stringify(config));
    }

    var dpiAdjust = Math.ceil(window.devicePixelRatio) ?? 1.0;


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
                    bcdScanned(bcd);
                    $("#dbg").text(`code:${bcd}`);
                }
            });
        }

        function stopScanner() {
            $('#scanner').html("");
            
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
            const resp=await fetch("api/scan/", {
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
		   store.unresolved=scanInfo.barcodes;
		   reloadView();
        }

        async function resolveAlbumDisambig(scan, albumSlug) {
            const query = new URLSearchParams({
                scan: scan.id
            });

            const resp=await fetch(`api/scan/${scan.id}` , {
                method: "PATCH",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    albumSlug: albumSlug
                })
            });

            //store.library = [];
            //store.unresolved = [];
		    const scanInfo=await resp.json();
		    let status=scanInfo.hasOwnProperty('barcode');
		    scan.copy=scanInfo.copy;

            $("#disambigUi")[0].close();


		   reloadView();
        }

        async function manualAddSubmit(manualAddContext) {
            const editor = editors.manualAdd;

            if(manualAddContext.url != "") {
                const request = {
                    discogsUrl: manualAddContext.url,
                };

                let error = null;

                if(editor.targetScan !== null) {
                    const scanId = editor.targetScan.id
                    const resp=await fetch(`api/scan/${scanId}` , {
                        method: "PATCH",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify(request)
                    });

                    const respObj = await resp.json();
                    error = respObj.error;

                    if(!error) {
                        fullReload(); // TODO partial reload
                    }

                } else {
                    let resp;
                    if(editor.targetCopy !== null) {
                        const copyId = editor.targetCopy.id

                        resp=await fetch("api/copy/" + copyId , {
                            method: "PATCH",
                            headers: {
                                "Content-Type": "application/json"
                            },
                            body: JSON.stringify(request)
                        });
                    } else {
                        request.library = store.libraryId;

                        resp=await fetch("api/copy/" , {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json"
                            },
                            body: JSON.stringify(request)
                        });
                    }

                    const respObj = await resp.json();
                    error = respObj.error;

                    if(!error) {
                        store.library.push(respObj);
                    }
                }

                if(error) {
                    editor.error = error;
                } else {
                    $("#manualAddUi")[0].close();
                }
            }
        }



        function reloadView() {
            const viewId = getConfig("libraryViewId", "artist");
            const showGroupSeparators = getConfig("libraryViewShowGroupSeparators", true);

            let library = applyView(store.library, sortViews[viewId]);

            if(!showGroupSeparators) {
                library = [{key: "#", items: library.flatMap(grp=>grp.items)}];
            }

            store.libraryView = library;
        }

        function fullReload() {
            store.unresolved = [];
            store.library = [];
            fetch(`api/scan/?type=unresolved&library=${store.libraryId}`, 
                {
                    method: "GET",
                })
                .then(resp=>resp.json())
                .then((scans)=>{
                    store.unresolved = scans.barcodes;
                    reloadView();
                }
            );

            fetch("api/library/" + store.libraryId, 
                {
                    method: "GET",
                })
                .then(resp=>resp.json())
                .then((library)=>{
                    store.library = library.copies;
                    reloadView();
                }
            );
        }

        $(document).ready(()=>{
            //initScanner();
			reloadView();

            $(document).on("click", "#scannerOpenBtn", e=>{
                $("#scannerUi")[0].showModal();
                initScanner();
            });

            $("#scannerUi").on("close", (e)=>{
                stopScanner();
            });

            $(document).on("click", "#manualAddOpenBtn", e=>{
                showManualAddUi({});
            });
            $("#manualAddUi").on("close", (e)=>{
                editors.manualAdd.url = "";
                editors.manualAdd.error = "";
                editors.manualAdd.targetCopy = null;
                editors.manualAdd.targetScan = null;
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

            $(document).on("click", "dialog button.dialogClose", function (e) {
                 $(this).closest("dialog")[0].close();
            });

            $("#disambigUi").on("close", (e)=>{
                editors.disambig.scan = null;
                editors.disambig.albumSelection = null;
            });
        });

        function showDisambigAction(scan) {
            if(scan.copy === null) {
                showManualAddUi({scan: scan});
            } else {
                showDisambigSelector(scan);
            }
        }

        function showDisambigSelector(scan) {
            editors.disambig.scan = scan;
            $("#disambigUi")[0].showModal();

        }

        function showManualAddUi(context) {
            editors.manualAdd.targetCopy = null;
            editors.manualAdd.targetScan = null;
            if(context.copy) {
                editors.manualAdd.targetCopy = context.copy;
            } else if(context.scan) {
                editors.manualAdd.targetScan = context.scan;
            }
            $("#manualAddUi")[0].showModal();
        }

        function formatArtistName(name) {
            const groups = name.match(/^(.+?)(?:\s+\(\d+\))?$/);
            if(groups === null) {
                return null;
            }
            return groups[1];
        }

        var store, editors;

        function mapCopyToAlbum(copy) {
            if(copy === null || copy.albumTitle === null){
                return null;
            }

            return {
                artist: copy.artist,
                title: copy.albumTitle,
                year: copy.year ?? 0,
                image: copy.image ?? null,
                slug: copy.slug ?? "",
            };
        }

        /* SORTING */

        const sortViews = {
            artist: {
                group: { by: 'artist', sort: 'asc' },
                sort: { by: 'year', direction: 'asc' }
            },

            added: {
                sort: { by: 'created_at', direction: 'desc' }
            },
        };

        function applyView(copies, view) {
            function sortBy(items, { by, direction = 'asc' }) {
                return Array.from(items).sort((a, b) => {
                    const av = a[by].toLocaleLowerCase();
                    const bv = b[by].toLocaleLowerCase();

                    return direction === 'asc'
                        ? av > bv ? 1 : av < bv ? -1 : 0
                        : av < bv ? 1 : av > bv ? -1 : 0;
                });
            }

            function groupBy(items, { by }) {
                return Object.groupBy(items, item => item[by]);
            }

            function compare(av, bv, direction) {
                    return direction === 'asc'
                ? av > bv ? 1 : av < bv ? -1 : 0
                : av < bv ? 1 : av > bv ? -1 : 0;
            }


            let groups;
            if(view.group !== undefined) {
                groups = Object.groupBy(copies, item => item[view.group.by]);
            } else {
                groups = {"#": copies};
            }

            return Object.entries(groups)
                .sort(([a], [b]) =>
                    compare(a.toLocaleLowerCase(), b.toLocaleLowerCase(), view.group.sort)
                )
                .map(([key, items]) => ({
                    key,
                    items: sortBy(items, view.sort)
                }));
        }

        function formatIsoDate(iso) {
            const isoFix = iso.replace(/^\+0{0,2}(?=\d{4}-)/, '');
            const d = new Date(isoFix);

            

            const formatted =
                d.getFullYear() + '-' +
                String(d.getMonth() + 1).padStart(2, '0') + '-' +
                String(d.getDate()).padStart(2, '0') + ' ' +
                String(d.getHours()).padStart(2, '0') + ':' +
                String(d.getMinutes()).padStart(2, '0');

            return formatted;
        }

        const EMPTY_TEMPLATE = {
            $template: '#tplEmpty'
        };

        function ListViewCopy(copy) {
            if(!copy) {
                return EMPTY_TEMPLATE;
            }
            const album=mapCopyToAlbum(copy);
            if(album) {
                return ListViewAlbum(album);
            }

            return ListViewCopyPlaceholder(copy);

        }

        function ListViewAlbum(album) {
            if(!album) {
                return EMPTY_TEMPLATE;
            }
            return {
                $template: '#tplAlbumCopy',
                album: album,
            }
        }

        function ListViewCopyPlaceholder(copy) {
            return EMPTY_TEMPLATE;
        }

        /* DISAMBIGUATION */

        function DisambigViewAlbum(album, disambig) {
            return {
                $template: '#tplDisambigAlbum',
                album: album,
                disambig: disambig,
            }
        }


        function UnresolvedScanView(scan) {
            if(!scan) {
                return EMPTY_TEMPLATE;
            }
            if(scan.copy === null) {
                return {
                    $template: '#tplAlbumPlaceholderBarcode',
                    scan: scan,
                }
            } 
            else if(scan.copy.albumTitle === null) {
                return {
                    $template: '#tplAlbumPlaceholderDisambig',
                    scan: scan,
                }
            } 

            return EMPTY_TEMPLATE;
        }
        
    </script>
    <script type="module">
        import { reactive, createApp } from 'https://esm.sh/pocket-vue'

        store = reactive({
            library: <?=json_encode($libraryContents)?>,
            unresolved: <?=json_encode($unresolvedBarcodes)?>,
            libraryView: [],
            libraryId: <?=(int)$libraryId ?>
        });

        editors = reactive({
            disambig: {
                scan: null,
                albumSelection: null
            },
            manualAdd: {
                targetCopy: null,
                targetScan: null,
                url: "",
                error: "",
            }
        })

        createApp({store, editors}).mount();
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
        <button id='manualAddOpenBtn'>+ Manual add</button>
        <div class="panel">
            <h2 class='panelTitle'>Collection</h2>
            <div id="groupedList" v-scope>
                <div v-for="group in store.libraryView" class="copyGroup">
                    <h3 v-if="group.key !== '#'">{{group.key}}</h3>
                    <div class="copyGroupAlbumList">
                        <div v-for="copy in group.items"  v-scope="ListViewCopy(copy)" class="album albumCopy"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel" v-scope v-if="store.unresolved.length > 0">
            <h2 class='panelTitle'>Unmarked graves</h2>
            <p class='panelDescription'>Barcodes that match multiple albums. Help them rest in peace.</p>
            <div id="unresolved" v-scope>
                <div v-for="scan in store.unresolved" v-scope="UnresolvedScanView(scan)" class="unresolvedScan clickable"  @click="showDisambigAction(scan)">
                </div>
            </div>
        </div>

    </main>

    <dialog id="scannerUi" class="popup">
        <div class='dialogHeader'>
            <button class="dialogClose dialogCtl">⨉</button>
            <h2>Scan barcode...</h2>
        </div>
        <div class='dialogMain'>
            <div id="dbg"></div>

            <div id="scanner"></div>
        </div>
        <div class="dialogActionBar"></div>
    </dialog>

    <dialog id="manualAddUi" class="popup" v-scope="{editor: editors.manualAdd}">
        <div class='dialogHeader'>
            <button class="dialogClose dialogCtl">⨉</button>
            <h2>Add manually...</h2>
        </div>
        <div class='dialogMain'>
            <div v-if="editor.targetCopy !== null">
                Selected copy with multiple matches...
                <div v-scope="ListViewCopy(editor.targetCopy)" class="album albumCopy"></div>
            </div>
            <div v-else-if="editor.targetScan !== null">
                Selected scan with no matches...
                <div v-scope="UnresolvedScanView(editor.targetScan)" class="unresolvedScan"></div>
            </div>
            <label for="manualAddUrl">Discogs URL</label> <input type="url" name="manualAddUrl" v-model="editor.url"/>
        </div>

        <div id="manualAddError" v-model="editor.error"></div>
        <div class="dialogActionBar">
            <button :disabled="editor.url==''" @click="manualAddSubmit(editor)">OK</button>
        </div>
    </dialog>

    <dialog id="disambigUi" class="popup" v-scope>
        <div class='dialogHeader'>
            <button class="dialogClose dialogCtl">⨉</button>
            <h2>Here lies...</h2>
        </div>
        <div class='dialogMain'>
            <div v-for="album in editors.disambig.scan.copy.disambiguation.albums"  v-scope="DisambigViewAlbum(album, editors.disambig)">
            </div>
        </div>
        <div class="dialogActionBar">
            <button :disabled="editors.disambig.albumSelection===null" @click="resolveAlbumDisambig(editors.disambig.scan, editors.disambig.albumSelection)">OK</button>
            <button @click="showManualAddUi({copy: editors.disambig.scan.copy})">Not on the list</button>

        </div>
    </dialog>

    <template id="tplAlbumPlaceholderBarcode">
        {{scan.barcode}} {{formatIsoDate(scan.scanned_at)}}
    </template>

    <template id="tplAlbumPlaceholderDisambig">
        <div class="scanDate">{{formatIsoDate(scan.scanned_at)}}</div>
        <div class="scanBcd">{{scan.barcode}}</div>
        <div class="disambigCovers">
            <div v-for="album in scan.copy.disambiguation.albums.slice(0, 4)" class="disambigPreview">
                <img :src="`api/thumbnail/front/${album.slug}.webp?size=${50*dpiAdjust}`" :alt="`[${album.artist} - ${album.title}]`" class='cover' />
            </div>
            <div v-if="scan.copy.disambiguation.albums.length > 4" class="disambigPreview moreCounter"> +{{scan.copy.disambiguation.albums.length-4}}</div>
        </div>
    </template>

    <template id="tplAlbumCopy">

        <img :src="`api/thumbnail/front/${album.slug}.webp?size=${200*dpiAdjust}`" alt="front cover" class="cover" />
        <div class='albumDetails'>
            <div class='albumTitle'>{{album.title}}</div>
            <div class='albumArtist'>{{formatArtistName(album.artist)}} [{{album.year}}]</div>
        </div>

    </template>

    <template id="tplDisambigAlbum">
        <label class='disambigItem'>
            <div v-scope="ListViewAlbum(album)" class="album albumCopy"></div>
            <input type="radio" name="disambigAlbumSelection" :value="album.slug" class="disambigCheckbox" v-model="disambig.albumSelection" />
        </label>
    </template>

    <template id="tplEmpty">
    </template>

</body>
</html>