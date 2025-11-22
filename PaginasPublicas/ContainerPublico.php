<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <title>CEU</title>
    <meta name="theme-color" content="#6598D2" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="mobile-web-app-capable" content="yes" />
    <link rel="manifest" href="/CEU/manifest.json" />
    <link rel="stylesheet" href="../styleGlobal.css" />
    <link rel="stylesheet" href="../styleGlobalMobile.css" media="(max-width: 767px)" />
    <link rel="icon" type="image/png" href="../Imagens/CEU-Logo-1x1.png" />
    <script src="/CEU/pwa-config.js" defer></script>
</head>

<body <?php if (($pagina ?? ($_GET['pagina'] ?? 'inicio')) === 'inicio') {
            echo 'class="pagina-inicio"';
        } ?>>
    <?php
    // Inicia sessão para verificar se usuário está logado
    session_start();

    // Se usuário já está logado, redireciona para a área apropriada
    // EXCETO se estiver tentando acessar um evento específico
    $paginaAtual = $_GET['pagina'] ?? 'inicio';
    $codEvento = $_GET['cod_evento'] ?? null;
    
    if (isset($_SESSION['cpf']) && !empty($_SESSION['cpf'])) {
        // Atualiza timestamp de atividade
        $_SESSION['ultima_atividade'] = time();

        // Se está tentando ver um evento, redireciona para a área dele com o evento
        if ($paginaAtual === 'evento' && $codEvento) {
            if (isset($_SESSION['organizador']) && $_SESSION['organizador'] == 1) {
                header('Location: ../PaginasOrganizador/ContainerOrganizador.php?pagina=evento&cod_evento=' . $codEvento);
            } else {
                header('Location: ../PaginasParticipante/ContainerParticipante.php?pagina=evento&cod_evento=' . $codEvento);
            }
            exit();
        }

        // Senão, redireciona normalmente para a área apropriada
        if (isset($_SESSION['organizador']) && $_SESSION['organizador'] == 1) {
            header('Location: ../PaginasOrganizador/ContainerOrganizador.php?pagina=inicio');
        } else {
            header('Location: ../PaginasParticipante/ContainerParticipante.php?pagina=inicio');
        }
        exit();
    }

    // Definição das páginas permitidas e resolução do arquivo a incluir
    $paginasPermitidas = [
        'inicio' => 'Inicio.php',
        'login' => 'Login.html',
        'cadastroP' => 'CadastroParticipante.html',
        'cadastroO' => 'CadastroOrganizador.html',
        'redefinirSenha' => 'RedefinirSenha.html',
        'evento' => 'CartaodoEvento.php',
        'solicitarCodigo' => 'SolicitarCodigo.html',
        // Globais reutilizáveis
        'faleConosco' => '../PaginasGlobais/FaleConosco.html',
        'termos' => '../PaginasGlobais/TermosDeCondicoes.html',
    ];
    $pagina = $_GET['pagina'] ?? 'inicio';
    $arquivo = $paginasPermitidas[$pagina] ?? $paginasPermitidas['inicio'];

    // Se for uma requisição AJAX, retorna apenas o conteúdo
    if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
        include $arquivo;
        exit();
    }
    ?>

    <!-- Menu fixo -->
    <?php include 'MenuBloqueado.html'; ?>

    <!-- Conteúdo dinÍ¢mico -->
    <div id="conteudo-dinamico">
        <?php include $arquivo; ?>
    </div>

    <script>
        // =========================
        // Variáveis globais
        // =========================
        let menuContentObserver = null; // Observer para sincronizar menu/conteúdo

        // =========================
        // Funções utilitárias (helpers)
        // =========================
        function carregarScripts(lista, callback) {
            const alvo = document.getElementById('conteudo-dinamico') || document.body;
            let index = 0;

            function proximo() {
                if (index >= lista.length) {
                    if (typeof callback === 'function') {
                        callback();
                    }
                    return;
                }

                const caminho = lista[index++];
                const jaCarregado = Array.from(document.getElementsByTagName('script')).some(function(scriptExistente) {
                    const idDinamico = scriptExistente.getAttribute('data-dynamic-id');
                    if (idDinamico && idDinamico === caminho) {
                        return true;
                    }
                    if (scriptExistente.src && scriptExistente.src.indexOf(caminho) !== -1) {
                        return true;
                    }
                    return false;
                });

                if (jaCarregado) {
                    proximo();
                    return;
                }

                const script = document.createElement('script');
                script.setAttribute('data-dynamic-id', caminho);
                script.src = caminho + (caminho.indexOf('?') === -1 ? '?t=' : '&t=') + new Date().getTime();
                script.onload = proximo;
                script.onerror = function() {
                    console.error('Falha ao carregar o script:', script.src);
                    proximo();
                };
                alvo.appendChild(script);
            }

            proximo();
        }

        function sincronizarMenuComConteudo() {
            const menu = document.querySelector('.Menu');
            const mainContent = document.getElementById('main-content');
            if (!menu || !mainContent) return;

            // Remove observer antigo, se existir
            if (menuContentObserver) {
                menuContentObserver.disconnect();
                menuContentObserver = null;
            }

            if (menu.classList.contains('expanded')) {
                mainContent.classList.add('shifted');
            } else {
                mainContent.classList.remove('shifted');
            }

            menuContentObserver = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.attributeName === 'class') {
                        if (menu.classList.contains('expanded')) {
                            mainContent.classList.add('shifted');
                        } else {
                            mainContent.classList.remove('shifted');
                        }
                    }
                });
            });
            menuContentObserver.observe(menu, {
                attributes: true
            });
        }

        function carregarFaleConoscoScript() {
            // Remove qualquer script antigo de FaleConosco.js dentro do conteúdo
            const conteudo = document.getElementById('conteudo-dinamico');
            if (!conteudo) return;
            const scripts = conteudo.querySelectorAll('script[data-faleconosco]');
            scripts.forEach(s => s.remove());
            const script = document.createElement('script');
            script.src = '../PaginasGlobais/FaleConosco.js?t=' + new Date().getTime();
            script.setAttribute('data-faleconosco', '1');
            script.onload = function() {
                if (typeof window.inicializarFaleConosco === 'function') {
                    window.inicializarFaleConosco();
                }
            };
            conteudo.appendChild(script);
        }

        // Controla a classe do body por página pública
        function atualizarClasseBody(pagina) {
            const b = document.body;
            if (!b) return;
            if (pagina === 'inicio') {
                b.classList.add('pagina-inicio');
            } else {
                b.classList.remove('pagina-inicio');
            }
        }

        // =========================
        // Definição das rotas
        // =========================
        const rotas = {
            'inicio': {
                html: 'Inicio.php',
                js: ['../PaginasGlobais/Filtro.js', 'Inicio.js'],
                init: () => {
                    if (typeof window.inicializarFiltroEventos === 'function') window.inicializarFiltroEventos();
                    if (typeof window.inicializarFiltro === 'function') window.inicializarFiltro();
                }
            },
            'login': {
                html: 'Login.html',
                js: ['ValidacoesComuns.js', 'ValidacoesLogin.js'],
                init: () => {
                    if (typeof window.inicializarValidacoesLogin === 'function') {
                        window.inicializarValidacoesLogin();
                    }
                }
            },
            'cadastroP': {
                html: 'CadastroParticipante.html',
                js: ['ValidacoesComuns.js', 'ValidacoesCadastro.js'],
                init: () => {
                    if (typeof window.inicializarValidacoesCadastro === 'function') {
                        window.inicializarValidacoesCadastro();
                    }
                }
            },
            'cadastroO': {
                html: 'CadastroOrganizador.html',
                js: ['ValidacoesComuns.js', 'ValidacoesCadastro.js'],
                init: () => {
                    if (typeof window.inicializarValidacoesCadastro === 'function') {
                        window.inicializarValidacoesCadastro();
                    }
                }
            },
            'redefinirSenha': {
                html: 'RedefinirSenha.html',
                js: ['RedefinirSenha.js'],
                init: () => {
                    if (typeof window.atribuirEventoRedefinirSenha === 'function') window.atribuirEventoRedefinirSenha();
                }
            },
            'evento': {
                html: 'CartaodoEvento.php',
                js: [],
                init: () => {}
            },
            'solicitarCodigo': {
                html: 'SolicitarCodigo.html',
                js: ['SolicitarCodigo.js'],
                init: () => {
                    if (typeof window.inicializarMascaras === 'function') window.inicializarMascaras();
                    const form = document.querySelector('.corpo-formulario');
                    if (form && typeof window.mostrarMensagemSolicitacaoEnviada === 'function') {
                        const btnEnviar = form.querySelector('button[type="submit"]');
                        if (btnEnviar) {
                            btnEnviar.onclick = function(e) {
                                e.preventDefault();
                                if (!form.checkValidity()) {
                                    form.reportValidity();
                                    return;
                                }
                                window.mostrarMensagemSolicitacaoEnviada();
                            };
                        }
                    }
                }
            },
            'faleConosco': {
                html: '../PaginasGlobais/FaleConosco.html',
                js: [],
                init: () => {
                    carregarFaleConoscoScript();
                }
            },
            'termos': {
                html: '../PaginasGlobais/TermosDeCondicoes.html',
                js: [],
                init: () => {}
            }
        };
        globalThis.rotas = rotas;

        // =========================
        // Funções de navegação e carregamento
        // =========================
        function executarRota(pagina) {
            const rota = rotas[pagina];
            if (!rota) return;
            const scripts = Array.isArray(rota.js) ? rota.js : [];
            if (scripts.length) {
                carregarScripts(scripts, () => {
                    if (typeof rota.init === 'function') rota.init();
                });
            } else {
                if (typeof rota.init === 'function') rota.init();
            }
        }

        function carregarPagina(pagina) {
            // Limpeza completa antes de carregar nova página
            if (typeof window.limpezaCompleta === 'function') {
                window.limpezaCompleta();
            }

            // Remove o filtro lateral (se existir) antes de trocar de página (versão pública)
            if (typeof window.removerFiltroExistente === 'function') {
                try {
                    window.removerFiltroExistente();
                } catch (e) {
                    /* noop */ }
            }

            // Usa ajax=1 para buscar apenas o conteúdo, sem menu
            fetch('ContainerPublico.php?pagina=' + encodeURIComponent(pagina) + '&ajax=1')
                .then(response => response.text())
                .then(html => {
                    // Como o servidor retorna apenas o conteúdo da página, não precisa extrair
                    document.getElementById('conteudo-dinamico').innerHTML = html;
                    
                    // Garante que a classe js-ready está presente
                    if (!document.body.classList.contains('js-ready')) {
                        document.body.classList.add('js-ready');
                    }
                    
                    // Força scroll para o topo ao trocar de página
                    window.scrollTo(0, 0);
                    
                    sincronizarMenuComConteudo();
                    atualizarClasseBody(pagina);

                    if (typeof window.setMenuAtivoPorPagina === 'function') {
                        window.setMenuAtivoPorPagina(pagina);
                    }
                    executarRota(pagina);

                    window.history.pushState({}, '', '?pagina=' + pagina);
                });
        }

        // =========================
        // Eventos de inicialização
        // =========================
        
        // Desabilita restauração automática de scroll do navegador
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }
        
        window.onpopstate = function() {
            const params = new URLSearchParams(window.location.search);
            const pagina = params.get('pagina') || 'inicio';
            carregarPagina(pagina);
        };

        // Marca que JS está pronto IMEDIATAMENTE para prevenir FOUC
        document.documentElement.classList.add('js-loading');
        
        document.addEventListener('DOMContentLoaded', function() {
            // Marca que o DOM está pronto
            document.body.classList.add('js-ready');
            document.documentElement.classList.remove('js-loading');
            
            // Força scroll para o topo ao carregar/recarregar a página
            window.scrollTo(0, 0);
            
            const params = new URLSearchParams(window.location.search);
            const pagina = params.get('pagina') || 'inicio';
            if (typeof window.setMenuAtivoPorPagina === 'function') {
                window.setMenuAtivoPorPagina(pagina);
            }
            sincronizarMenuComConteudo();
            atualizarClasseBody(pagina);
            executarRota(pagina);
            // Garante aplicação dos botões de senha após primeira carga
            if (typeof window.aplicarToggleSenhas === 'function') {
                window.aplicarToggleSenhas();
            }
        });
    </script>
    <script src="../PaginasGlobais/GerenciadorTimers.js"></script>
    <script src="MenuBloqueado.js"></script>
    <script src="ToggleSenha.js"></script>
    <!-- Responsividade Mobile - Overlay e Prevenção de Scroll -->
    <script src="../PaginasGlobais/ResponsividadeMobile.js"></script>
</body>

</html>

