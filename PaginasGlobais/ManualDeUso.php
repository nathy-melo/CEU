<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual de Uso - CEU</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="/CEU/Imagens/CEU-Logo-1x1.png" />
    <!-- PDF.js Local - Configurar ANTES de usar -->
    <script src="/CEU/bibliotecas/pdfjs/pdf.min.js"></script>
    <script>
        // Configurar worker IMEDIATAMENTE após PDF.js carregar
        if (window.pdfjsLib) {
            pdfjsLib.GlobalWorkerOptions.workerSrc = '/CEU/bibliotecas/pdfjs/pdf.worker.min.js';
        }
    </script>
    <style>
        :root {
            --branco: #FFFFFF;
            --preto: #000000;
            --botao: #6598D2;
            --caixas: #4F6C8C;
            --fundo: #D1EAFF;
            --cinza-escuro: #333333;
            --azul-escuro: #0a1449;
            --azul-claro: #8ad7da;
            --verde: #2c9533;
            --vermelho: #ff0000;
            --sombra-padrao: rgba(0, 0, 0, 0.6);
            --sombra-forte: rgba(0, 0, 0, 0.8);
            --sombra-leve: rgba(0, 0, 0, 0.5);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--fundo);
            color: var(--cinza-escuro);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow-x: hidden;
        }

        /* Padrão geométrico de fundo */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 0;
            pointer-events: none;
        }

        .container {
            position: relative;
            z-index: 1;
            width: 100%;
            margin: 0 auto;
            padding: 2rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            animation: surgirDeBaixo 0.6s ease-out;
            max-width: 95%;
            transition: margin-left 0.3s, margin-right 0.3s, max-width 0.3s;
            margin-left: 0;
            margin-right: 0;
        }

        /* Ajuste para quando menu está expandido */
        .container.shifted {
            margin-left: clamp(180px, 15vw, 220px);
            max-width: calc(95% - clamp(180px, 15vw, 220px));
        }

        /* Quando menu está fechado */
        .container:not(.shifted) {
            margin-left: 0;
            max-width: 95%;
        }

        @keyframes surgirDeBaixo {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header-manual {
            text-align: center;
            margin-bottom: 2rem;
            background: var(--branco);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            padding: 2rem;
        }

        .header-manual img {
            width: 100px;
            height: auto;
            margin-bottom: 1rem;
        }

        .header-manual h1 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--azul-escuro);
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }

        .header-manual p {
            font-size: 1rem;
            color: var(--cinza-escuro);
            font-weight: 500;
            opacity: 0.8;
        }

        .pdf-viewer-container {
            background: var(--branco);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            margin-bottom: 2rem;
            display: flex;
            flex-direction: column;
            flex: 1;
            position: relative;
        }

        .pdf-canvas-wrapper {
            overflow: visible;
            position: relative;
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 1.5rem;
        }

        .pdf-header {
            background: var(--botao);
            color: var(--branco);
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .pdf-header h2 {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            background: rgba(255, 255, 255, 0.15);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            flex: 1;
            min-width: 200px;
        }

        .search-box input {
            background: transparent;
            border: none;
            color: var(--branco);
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            flex: 1;
            outline: none;
        }

        .search-box input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .search-counter {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.85rem;
            font-weight: 600;
            min-width: 60px;
            text-align: right;
        }

        .search-nav-btn {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: var(--branco);
            cursor: pointer;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }

        .search-nav-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.6);
        }

        .search-nav-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Estilos para SVGs inline */
        .icon-pdf, .icon-search, .icon-info, .icon-check, .icon-warning {
            width: 1.2em;
            height: 1.2em;
            display: inline-block;
            vertical-align: -0.25em;
            margin-right: 0.3rem;
        }

        .search-nav-btn svg, .pdf-btn svg {
            width: 1em;
            height: 1em;
            display: inline-block;
            vertical-align: -0.1em;
        }

        .pdf-btn svg {
            margin-right: 0.4rem;
        }

        .info-box .icon-check {
            width: 1em;
            height: 1em;
            margin-right: 0.4rem;
            display: inline;
            vertical-align: -0.15em;
        }

        .pdf-error .icon-warning {
            width: 1.5em;
            height: 1.5em;
            margin-right: 0.5rem;
            display: inline-block;
            vertical-align: -0.3em;
        }

        .pdf-controls {
            display: flex;
            gap: 0.8rem;
            flex-wrap: wrap;
        }

        .pdf-btn {
            background: var(--branco);
            color: var(--botao);
            border: none;
            padding: 0.7rem 1.2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            text-decoration: none;
            font-family: 'Inter', sans-serif;
        }

        .pdf-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .pdf-btn:active {
            transform: translateY(0);
        }

        .pdf-btn.secondary {
            background: rgba(255, 255, 255, 0.2);
            color: var(--branco);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .pdf-btn.secondary:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .pdf-frame {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            background: var(--branco);
        }

        /* Botões de navegação do PDF */
        .botao-navegacao-pdf {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(101, 152, 210, 0.85);
            border: none;
            width: 50px;
            height: 50px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--branco);
            transition: all 0.3s ease;
            z-index: 25;
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .botao-navegacao-pdf:hover:not(:disabled) {
            background: rgba(101, 152, 210, 1);
            transform: translateY(-50%) scale(1.1);
        }

        .botao-navegacao-pdf:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .botao-anterior-pdf {
            left: 2rem;
        }

        .botao-proximo-pdf {
            right: 2rem;
        }

        /* Indicador de página */
        .indicador-pagina {
            position: absolute;
            bottom: 4rem;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(101, 152, 210, 0.9);
            color: var(--branco);
            padding: 0.7rem 1.2rem;
            border-radius: 20px;
            font-size: 0.95rem;
            font-weight: 600;
            z-index: 20;
        }

        .pdf-frame {
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            margin: 2rem auto;
            max-width: 600px;
            font-weight: 500;
        }

        .btn-voltar {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.8rem 2rem;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--botao);
            text-decoration: none;
            border: 2px solid var(--botao);
            border-radius: 8px;
            transition: all 0.3s ease;
            text-align: center;
            align-self: flex-start;
        }

        .btn-voltar:hover {
            background: var(--botao);
            color: var(--branco);
            transform: translateY(-2px);
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .header-manual {
                padding: 1.5rem;
            }

            .header-manual h1 {
                font-size: 1.6rem;
            }

            .header-manual p {
                font-size: 0.9rem;
            }

            .pdf-header {
                flex-direction: column;
                align-items: stretch;
            }

            .pdf-controls {
                width: 100%;
            }

            .pdf-btn {
                flex: 1;
                justify-content: center;
            }

            .search-box {
                width: 100%;
                min-width: unset;
            }

            .pdf-frame {
                height: 400px;
            }
        }

        .loader {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: var(--branco);
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="container linkMenu">
        <div class="header-manual">
            <img src="/CEU/Imagens/CEU-Logo-1x1.png" alt="CEU Logo">
            <h1>Manual de Uso</h1>
            <p>Guia completo para utilizar a plataforma CEU</p>
        </div>

        <?php
        // Verificar se arquivo existe (mantém validação)
        $arquivo_pdf = __DIR__ . '/ManualdeUsoCEU.pdf';
        $pdf_existe = file_exists($arquivo_pdf);
        ?>

        <?php if ($pdf_existe): ?>
            <div class="pdf-viewer-container">
                <div class="pdf-header">
                    <h2><svg class="icon-pdf" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg> Manual de Uso - CEU</h2>
                    <div class="search-box">
                        <svg class="icon-search" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>
                        <input 
                            type="text" 
                            id="searchInput" 
                            placeholder="Buscar no manual..." 
                            autocomplete="off"
                        >
                        <span class="search-counter" id="searchCounter">0/0</span>
                        <button class="search-nav-btn" id="prevBtn" title="Resultado anterior"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"></polyline></svg></button>
                        <button class="search-nav-btn" id="nextBtn" title="Próximo resultado"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg></button>
                    </div>
                    <div class="pdf-controls">
                        <a href="/CEU/PaginasGlobais/ManualdeUsoCEU.pdf" class="pdf-btn" download="ManualdeUsoCEU.pdf">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            Baixar
                        </a>
                    </div>
                </div>
                <div class="pdf-canvas-wrapper">
                    <canvas id="pdfCanvas" class="pdf-frame"></canvas>
                </div>
                <button class="botao-navegacao-pdf botao-anterior-pdf" id="botaoPaginaAnterior" title="Página anterior">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>
                <button class="botao-navegacao-pdf botao-proximo-pdf" id="botaoPaginaProxima" title="Próxima página">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
            </div>

        <?php else: ?>
            <div class="pdf-error">
                <svg class="icon-warning" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3.05h16.94a2 2 0 0 0 1.71-3.05L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                O arquivo do manual não foi encontrado.<br>
                <small>Certifique-se de que o arquivo "ManualdeUsoCEU.pdf" está na pasta correta.</small>
            </div>
        <?php endif; ?>

        <a href="#" class="btn-voltar" onclick="history.back(); return false;">← Voltar</a>
    </div>

    <script>
        // Envolver em IIFE para evitar conflitos de escopo
        (function() {
            // Permitir reinicialização quando a página é mostrada novamente
            if (window.pdfManualInitialized && window.pdfManualContainer) {
                // Se container existe, significa que a página estava carregada
                // Mas se o container não está mais no DOM, permitir reinicializar
                if (!document.getElementById('pdfCanvas')) {
                    window.pdfManualInitialized = false;
                } else {
                    return; // Já inicializada e container existe
                }
            }
            
            window.pdfManualInitialized = true;

            // Aguardar DOM estar pronto (importante para carregamento via AJAX)
            function aguardarDOM(callback) {
                if (document.getElementById('pdfCanvas')) {
                    callback();
                } else {
                    setTimeout(() => aguardarDOM(callback), 50);
                }
            }

            const canvas = document.getElementById('pdfCanvas');
            const ctx = canvas ? canvas.getContext('2d') : null;
            const searchInput = document.getElementById('searchInput');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const searchCounter = document.getElementById('searchCounter');
            const botaoPaginaAnterior = document.getElementById('botaoPaginaAnterior');
            const botaoPaginaProxima = document.getElementById('botaoPaginaProxima');

            let pdfDoc = null;
            let pageNum = 1;
            let pageRendering = false;
            let pageNumPending = null;
            let searchMatches = [];
            let currentMatchIndex = 0;
            let allPages = [];

            // Caminho do PDF (URL relativa, não Base64!)
            const pdfPath = '/CEU/PaginasGlobais/ManualdeUsoCEU.pdf';

            // Inicializar quando PDF.js estiver disponível
            function initPDF() {
                if (!window.pdfjsLib) {
                    // Se PDF.js ainda não carregou, aguardar
                    setTimeout(initPDF, 100);
                    return;
                }

                // Configurar worker SEMPRE antes de usar PDF.js
                if (!pdfjsLib.GlobalWorkerOptions.workerSrc) {
                    pdfjsLib.GlobalWorkerOptions.workerSrc = '/CEU/bibliotecas/pdfjs/pdf.worker.min.js';
                }

                if (canvas && pdfPath) {
                    // Adicionar timestamp para evitar cache
                    const loadingTask = window.pdfjsLib.getDocument({
                        url: pdfPath,
                        cMapUrl: '/CEU/bibliotecas/pdfjs/cmaps/',
                        cMapPacked: true,
                        disableRange: false,
                        disableStream: false
                    });
                    
                    loadingTask.promise
                        .then(pdf => {
                            pdfDoc = pdf;
                            console.log('PDF carregado com', pdf.numPages, 'páginas');
                            
                            // Extrair texto de todas as páginas (LAZY)
                            extractAllText();
                            
                            // Renderizar primeira página
                            renderPage(1);
                        })
                        .catch(err => {
                            console.error('Erro ao carregar PDF:', err);
                            // Tentar novamente com configuração simplificada
                            window.pdfjsLib.getDocument(pdfPath).promise
                                .then(pdf => {
                                    pdfDoc = pdf;
                                    console.log('PDF carregado (fallback) com', pdf.numPages, 'páginas');
                                    extractAllText();
                                    renderPage(1);
                                })
                                .catch(err2 => {
                                    console.error('Erro ao carregar PDF (fallback):', err2);
                                });
                        });
                }
            }

            // Verificar se PDF.js já carregou e aguardar DOM
            aguardarDOM(() => {
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initPDF);
                } else {
                    initPDF();
                }
            });

            // Extrair texto de todas as páginas (com cache)
            async function extractAllText() {
                if (!pdfDoc) return;
                
                console.log('Iniciando extração de texto...');
                allPages = [];
                
                for (let i = 1; i <= pdfDoc.numPages; i++) {
                    try {
                        const page = await pdfDoc.getPage(i);
                        const textContent = await page.getTextContent();
                        const text = textContent.items.map(item => item.str).join(' ');
                        allPages.push({ pageNum: i, text: text });
                    } catch (err) {
                        console.warn(`Erro ao extrair texto da página ${i}:`, err);
                    }
                }
                console.log('Extração concluída:', allPages.length, 'páginas processadas');
            }

            // Renderizar página
            function renderPage(num) {
                if (!canvas || !pdfDoc) return;
                
                if (pageRendering) {
                    pageNumPending = num;
                    return;
                }
                
                pageRendering = true;
                pdfDoc.getPage(num).then(page => {
                    const viewport = page.getViewport({ scale: 1.5 });
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;

                    const renderContext = {
                        canvasContext: ctx,
                        viewport: viewport
                    };
                    
                    page.render(renderContext).promise.then(() => {
                        pageRendering = false;
                        if (pageNumPending !== null) {
                            renderPage(pageNumPending);
                            pageNumPending = null;
                        }
                        
                        // Atualizar indicador de página e botões
                        updatePageIndicator();
                    });
                });

                pageNum = num;
            }

            // Atualizar indicador e controles de página
            function updatePageIndicator() {
                // Desabilitar botões de navegação se estiver na primeira/última página
                if (botaoPaginaAnterior) {
                    botaoPaginaAnterior.disabled = pageNum <= 1;
                }
                if (botaoPaginaProxima) {
                    botaoPaginaProxima.disabled = pageNum >= pdfDoc.numPages;
                }
            }

            // Navegar para página anterior
            function paginaAnterior() {
                if (pageNum > 1) {
                    renderPage(pageNum - 1);
                }
            }

            // Navegar para próxima página
            function proximaPagina() {
                if (pageNum < pdfDoc.numPages) {
                    renderPage(pageNum + 1);
                }
            }

            // Buscar no PDF
            function searchPDF(query) {
                searchMatches = [];
                currentMatchIndex = 0;

                if (!query.trim()) {
                    searchCounter.textContent = '0/0';
                    prevBtn.disabled = true;
                    nextBtn.disabled = true;
                    renderPage(pageNum);
                    return;
                }

                const lowerQuery = query.toLowerCase();

                // Procurar em todas as páginas
                allPages.forEach(page => {
                    const text = page.text.toLowerCase();
                    let startIndex = 0;
                    
                    while ((startIndex = text.indexOf(lowerQuery, startIndex)) !== -1) {
                        searchMatches.push({
                            pageNum: page.pageNum,
                            index: startIndex,
                            length: query.length
                        });
                        startIndex += 1;
                    }
                });

                if (searchMatches.length > 0) {
                    showMatch(0);
                } else {
                    searchCounter.textContent = '0/0';
                    prevBtn.disabled = true;
                    nextBtn.disabled = true;
                }
            }

            // Mostrar resultado da busca
            function showMatch(index) {
                if (index < 0) index = searchMatches.length - 1;
                if (index >= searchMatches.length) index = 0;

                currentMatchIndex = index;
                const match = searchMatches[index];

                renderPage(match.pageNum);
                searchCounter.textContent = `${index + 1}/${searchMatches.length}`;
                prevBtn.disabled = searchMatches.length === 0;
                nextBtn.disabled = searchMatches.length === 0;
            }

            // Event listeners
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    searchPDF(e.target.value);
                });

                searchInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        if (e.shiftKey) {
                            prevBtn.click();
                        } else {
                            nextBtn.click();
                        }
                    }
                });
            }

            if (prevBtn) prevBtn.addEventListener('click', () => {
                showMatch(currentMatchIndex - 1);
            });

            if (nextBtn) nextBtn.addEventListener('click', () => {
                showMatch(currentMatchIndex + 1);
            });

            // Event listeners para navegação de páginas
            if (botaoPaginaAnterior) {
                botaoPaginaAnterior.addEventListener('click', paginaAnterior);
            }

            if (botaoPaginaProxima) {
                botaoPaginaProxima.addEventListener('click', proximaPagina);
            }

            // Suporte a teclado para navegação
            document.addEventListener('keydown', (e) => {
                if (pdfDoc) {
                    if (e.key === 'ArrowLeft' && pageNum > 1) {
                        paginaAnterior();
                    } else if (e.key === 'ArrowRight' && pageNum < pdfDoc.numPages) {
                        proximaPagina();
                    }
                }
            });

            // Desabilitar botões inicialmente
            if (prevBtn) prevBtn.disabled = true;
            if (nextBtn) nextBtn.disabled = true;

            // Detector de barra lateral para ajuste dinâmico
            function updateMenuLayout() {
                const menu = document.querySelector('.Menu');
                const container = document.querySelector('.container');

                if (menu && container) {
                    // Verificar se menu tem a classe 'expanded'
                    if (menu.classList.contains('expanded')) {
                        container.classList.add('shifted');
                    } else {
                        container.classList.remove('shifted');
                    }
                }
            }

            // Monitorar mudanças na classe do menu
            if (document.querySelector('.Menu')) {
                const observer = new MutationObserver(() => {
                    updateMenuLayout();
                });

                observer.observe(document.querySelector('.Menu'), {
                    attributes: true,
                    attributeFilter: ['class']
                });

                // Verificação inicial
                updateMenuLayout();
            }

            // Cleanup quando a página é removida do DOM
            window.cleanupPDFManual = function() {
                window.pdfManualInitialized = false;
                if (pdfDoc) {
                    pdfDoc.destroy().catch(() => {});
                }
                pdfDoc = null;
                pageNum = 1;
                pageRendering = false;
                pageNumPending = null;
                searchMatches = [];
                currentMatchIndex = 0;
                allPages = [];
            };

            // Detectar quando container é removido (página saiu)
            const containerElement = document.querySelector('.container.linkMenu');
            if (containerElement) {
                const observer = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        if (mutation.removedNodes.length) {
                            mutation.removedNodes.forEach((node) => {
                                if (node.classList && node.classList.contains('linkMenu')) {
                                    window.cleanupPDFManual();
                                }
                            });
                        }
                    });
                });

                observer.observe(containerElement.parentNode, {
                    childList: true
                });
            }
        })(); // Fim da IIFE
    </script>
