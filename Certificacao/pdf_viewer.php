<?php
// Visualizador simples usando PDF.js para evitar barras do navegador
$base = __DIR__;
$file = isset($_GET['file']) ? basename((string)$_GET['file']) : '';
$pdfPath = $base . '/certificados/' . $file;
if (!$file || !file_exists($pdfPath)) {
    http_response_code(404);
    echo 'PDF não encontrado.';
    exit;
}
$pdfUrl = 'certificados/' . rawurlencode($file);
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Visualizador de PDF</title>
    <style>
        html,
        body {
            height: 100%;
        }

        body {
            margin: 0;
            background: #eef5ff;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        }

        header {
            padding: 10px 14px;
            background: #334b68;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        header .spacer {
            flex: 1;
        }

        .btn {
            background: #6598d2;
            color: #fff;
            padding: 8px 12px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        #viewer {
            position: fixed;
            top: 48px;
            left: 0;
            right: 0;
            bottom: 0;
            overflow: auto;
            background: #fff;
        }

        #pdf-canvas {
            display: block;
            margin: 0 auto;
            background: #fff;
        }

        #pageBar {
            position: fixed;
            bottom: 12px;
            left: 50%;
            transform: translateX(-50%);
            background: #0009;
            color: #fff;
            border-radius: 20px;
            padding: 6px 10px;
            display: flex;
            gap: 8px;
            align-items: center;
        }

        #pageBar button {
            background: #ffffff22;
            color: #fff;
            border: 0;
            border-radius: 12px;
            padding: 6px 10px;
            cursor: pointer;
        }

        #pageBar span {
            min-width: 80px;
            text-align: center;
        }
    </style>
    <!-- PDF.js via CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js" integrity="sha512-T8vlaQ9jAS8wrmQ0g1l6zYkB0GV9MFXSHEknW1cM+u2xgL5aE1pMzyew1r7bHRA+5z3O0w8nI2Eq7t9m2yXvCg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        // Configuração correta do worker no PDF.js v3+
        if (window.pdfjsLib && pdfjsLib.GlobalWorkerOptions) {
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        }
    </script>
</head>

<body>
    <header>
        <strong>Visualização</strong>
        <div class="spacer"></div>
        <a class="btn" href="<?= htmlspecialchars($pdfUrl) ?>" download>Baixar PDF</a>
    </header>
    <div id="viewer">
        <canvas id="pdf-canvas"></canvas>
        <div id="pageBar">
            <button id="prevBtn">◀</button>
            <span id="pageInfo">1 / 1</span>
            <button id="nextBtn">▶</button>
            <button id="fitBtn">Ajustar</button>
            <button id="zoomIn">+</button>
            <button id="zoomOut">−</button>
        </div>''
    </div>
    <script>
        (function() {
            const url = '<?= htmlspecialchars($pdfUrl) ?>';
            const canvas = document.getElementById('pdf-canvas');
            const ctx = canvas.getContext('2d');
            let pdfDoc = null,
                pageNum = 1,
                scale = 1.2,
                fitToWidth = true;

            function renderPage(num) {
                pdfDoc.getPage(num).then(function(page) {
                    const viewport = page.getViewport({
                        scale: scale
                    });
                    canvas.width = viewport.width;
                    canvas.height = viewport.height;
                    const renderContext = {
                        canvasContext: ctx,
                        viewport: viewport
                    };
                    page.render(renderContext);
                    document.getElementById('pageInfo').textContent = num + ' / ' + pdfDoc.numPages;
                });
            }

            function fit() {
                fitToWidth = true;
                const container = document.getElementById('viewer');
                const w = container.clientWidth - 20; // margin
                pdfDoc.getPage(pageNum).then(function(page) {
                    const viewport = page.getViewport({
                        scale: 1
                    });
                    scale = w / viewport.width;
                    renderPage(pageNum);
                });
            }

            pdfjsLib.getDocument(url).promise.then(function(pdf) {
                pdfDoc = pdf;
                fit();
            });

            document.getElementById('prevBtn').onclick = function() {
                if (pageNum > 1) {
                    pageNum--;
                    renderPage(pageNum);
                }
            };
            document.getElementById('nextBtn').onclick = function() {
                if (pageNum < pdfDoc.numPages) {
                    pageNum++;
                    renderPage(pageNum);
                }
            };
            document.getElementById('fitBtn').onclick = fit;
            document.getElementById('zoomIn').onclick = function() {
                fitToWidth = false;
                scale *= 1.1;
                renderPage(pageNum);
            };
            document.getElementById('zoomOut').onclick = function() {
                fitToWidth = false;
                scale /= 1.1;
                renderPage(pageNum);
            };
            window.addEventListener('resize', function() {
                if (fitToWidth && pdfDoc) {
                    fit();
                }
            });
        })();
    </script>
</body>

</html>