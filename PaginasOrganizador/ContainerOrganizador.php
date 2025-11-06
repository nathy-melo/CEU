<?php
// Configuração do tempo de sessão para 6 minutos (5min de inatividade + 1min de extensão)
ini_set('session.gc_maxlifetime', 360);
session_set_cookie_params(360);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Verifica se a sessão expirou (5 minutos de inatividade)
if (isset($_SESSION['ultima_atividade']) && (time() - $_SESSION['ultima_atividade'] > 300)) {
    // Sessão expirou - mostra página especial
    session_unset();
    session_destroy();

    // Mostra página de sessão expirada ao invés de redirecionamento direto
    echo '<!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sessão Expirada - CEU</title>
        <link rel="stylesheet" href="../styleGlobal.css">
        <link rel="icon" type="image/png" href="../Imagens/CEU-Logo-1x1.png">
    </head>
    <body>
        <div id="modalSessaoExpirada" class="modal-personalizado mostrar">
            <div class="conteudo-modal-personalizado">
                <div class="cabecalho-modal-personalizado">Um anjo sussurrou no seu ouvido:</div>
                <div class="corpo-modal-personalizado">Sua sessão expirou. Você precisa fazer login novamente para continuar.</div>
                <button class="botao botao-modal-personalizado" onclick="window.location.href=\'../PaginasPublicas/ContainerPublico.php?pagina=login&erro=sessao_expirada\'">Fazer Login</button>
            </div>
        </div>
    </body>
    </html>';
    exit;
}

// Atualiza o timestamp da última atividade
$_SESSION['ultima_atividade'] = time();

if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
    header('Location: ../PaginasPublicas/ContainerPublico.php?pagina=login&erro=login_requerido');
    exit;
}

// Verifica se não é um participante tentando acessar área de organizador
if (!isset($_SESSION['organizador']) || $_SESSION['organizador'] != 1) {
    header('Location: ../PaginasParticipante/ContainerParticipante.php?pagina=inicio');
    exit;
}
$tema_site = isset($_SESSION['tema_site']) ? (int)$_SESSION['tema_site'] : 0;
?>
<!DOCTYPE html>
<html lang="pt-br" <?php if ($tema_site === 1) {
                        echo 'data-theme="dark"';
                    } ?>>

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <title>CEU</title>
    <meta name="theme-color" content="#6598D2" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="mobile-web-app-capable" content="yes" />
    <link rel="manifest" href="/CEU/manifest.json" />
    <?php
        // Cache-busting para CSS global (evita centralização por CSS antigo em cache SW/navegador)
        $__cssPath = realpath(__DIR__ . '/../styleGlobal.css');
        $__cssVer = $__cssPath ? filemtime($__cssPath) : time();
    ?>
    <link rel="stylesheet" href="../styleGlobal.css?v=<?= $__cssVer ?>" />
    <link rel="icon" type="image/png" href="../Imagens/CEU-Logo-1x1.png" />
    <script src="/CEU/pwa-config.js" defer></script>
    <style>
        /* Força páginas de listagem a começarem no topo, nunca centralizadas.
           Inline para garantir precedência mesmo com CSS antigo em cache. */
        body.pagina-lista-eventos #conteudo-dinamico {
            display: block !important;
            align-items: flex-start !important;
            justify-content: flex-start !important;
            min-height: auto !important;
            padding-top: 1rem;
            width: 100%;
        }
    </style>
</head>

<body <?php 
        $paginaAtual = $pagina ?? ($_GET['pagina'] ?? 'inicio');
        $classes = [];
        if ($paginaAtual === 'inicio') {
            $classes[] = 'pagina-inicio';
        }
        if ($paginaAtual === 'eventosInscritos') {
            $classes[] = 'pagina-lista-eventos';
        }
        if (!empty($classes)) {
            echo 'class="' . implode(' ', $classes) . '"';
        }
    ?>>
    <?php
    // Definição das páginas permitidas e resolução do arquivo a incluir
    $paginasPermitidas = [
        'inicio' => 'InicioOrganizador.php',
        'eventosInscritos' => 'EventosInscritosOrganizador.php',
        'evento' => 'CartaodoEventoOrganizador.php',
        'eventoInscrito' => 'CartaoDoEventoInscrito.php',
        'eventoOrganizado' => 'CartaoDoEventoOrganizando.html',
        'meusEventos' => 'MeusEventosOrganizador.html',
        'adicionarEvento' => 'AdicionarEvento.php',
        'listaParticipantes' => 'ListadeParticipantes.php',
        'perfil' => 'PerfilOrganizador.php',
        'certificados' => 'CertificadosOrganizador.html',
        'configuracoes' => 'ConfiguracoesOrganizador.html',
        'painelnotificacoes' => '../PaginasGlobais/PainelNotificacoes.php',
        // Reaproveita conteúdos globais quando aplicável
        'termos' => '../PaginasGlobais/TermosDeCondicoes.html',
        'faleconosco' => '../PaginasGlobais/FaleConosco.html',
        'redefinirSenha' => '../PaginasGlobais/RedefinirSenhaConta.html',
        'emailRecuperacao' => '../PaginasGlobais/EmailDeRecuperacao.html',
        'temaDoSite' => '../PaginasGlobais/TemaDoSite.php',
        'manualDeUso' => '../PaginasGlobais/ManualDeUso.html',
        'duvidasFrequentes' => '../PaginasGlobais/DuvidasFrequentes.html',
        'sobreNos' => '../PaginasGlobais/SobreNos.html',
    ];
    $pagina = $_GET['pagina'] ?? 'inicio';
    $arquivo = $paginasPermitidas[$pagina] ?? $paginasPermitidas['inicio'];
    ?>

    <!-- Menu fixo -->
    <?php include 'MenuO.php'; ?>

    <!-- Conteúdo dinâmico -->
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
                if (index < lista.length) {
                    const script = document.createElement('script');
                    script.src = lista[index++] + '?t=' + new Date().getTime();
                    script.onload = proximo;
                    script.onerror = () => console.error('Falha ao carregar o script:', script.src);
                    alvo.appendChild(script);
                } else if (callback) {
                    callback();
                }
            }
            proximo();
        }

        function sincronizarMenuComConteudo() {
            const menu = document.querySelector('.Menu');
            const mainContent = document.getElementById('main-content');
            if (!menu || !mainContent) return;

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
            const conteudo = document.getElementById('conteudo-dinamico');
            if (!conteudo) return;
            const scripts = conteudo.querySelectorAll('script[data-faleconosco]');
            scripts.forEach(s => s.remove());
            const script = document.createElement('script');
            script.src = '../PaginasGlobais/FaleConosco.js?t=' + new Date().getTime();
            script.setAttribute('data-faleconosco', '1');
            script.onload = function() {
                if (typeof window.inicializarFaleConosco === 'function') window.inicializarFaleConosco();
            };
            conteudo.appendChild(script);
        }

        function executarScriptsNoConteudo(containerEl) {
            if (!containerEl) return;
            const scripts = Array.from(containerEl.querySelectorAll('script'));
            scripts.forEach((oldScript) => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                if (oldScript.src) {
                    const url = new URL(oldScript.src, window.location.href);
                    url.searchParams.set('t', Date.now());
                    newScript.src = url.toString();
                    newScript.async = false;
                } else {
                    newScript.textContent = oldScript.textContent || '';
                }
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });
        }

        // Controla a classe do body por página do organizador
        function atualizarClasseBody(pagina) {
            const b = document.body;
            if (!b) return;
            if (pagina === 'inicio') {
                b.classList.add('pagina-inicio');
            } else {
                b.classList.remove('pagina-inicio');
            }
            
            // Páginas que precisam começar do topo (não centralizadas)
            if (pagina === 'eventosInscritos') {
                b.classList.add('pagina-lista-eventos');
            } else {
                b.classList.remove('pagina-lista-eventos');
            }
        }

        // =========================
        // Definição das rotas
        // =========================
        const rotas = {
            'inicio': {
                html: 'InicioOrganizador.php',
                js: ['../PaginasGlobais/Filtro.js', 'InicioOrganizador.js'],
                init: () => {
                    if (typeof window.inicializarFiltroEventos === 'function') window.inicializarFiltroEventos();
                }
            },
            'eventosInscritos': {
                html: 'EventosInscritosOrganizador.php',
                js: ['../PaginasGlobais/Filtro.js', 'EventosInscritosOrganizador.js'],
                init: () => {
                    if (typeof window.inicializarFiltroEventos === 'function') window.inicializarFiltroEventos();
                }
            },
            'meusEventos': {
                html: 'MeusEventosOrganizador.html',
                js: ['../PaginasGlobais/Filtro.js', 'MeusEventosOrganizador.js'],
                init: () => {
                    if (typeof window.inicializarFiltroEventos === 'function') window.inicializarFiltroEventos();
                }
            },
            'evento': {
                html: 'CartaodoEventoOrganizador.php',
                js: ['CartaoDoEventoOrganizador.js'],
                init: () => {
                    if (typeof window.inicializarEventosCartaoEvento === 'function') window.inicializarEventosCartaoEvento();
                }
            },
            'eventoInscrito': {
                html: 'CartaoDoEventoInscrito.php',
                js: ['CartaoDoEventoInscrito.js'],
                init: () => {
                    if (typeof window.inicializarEventosCartaoDoEventoInscrito === 'function') window.inicializarEventosCartaoDoEventoInscrito();
                }
            },
            'eventoOrganizado': {
                html: 'CartaoDoEventoOrganizando.html',
                js: ['CartaoDoEventoOrganizando.js'],
                init: () => {}
            },
            'listaParticipantes': {
                html: 'ListadeParticipantes.php',
                js: [],
                init: () => {
                    // Força reinicialização da lista após um pequeno delay
                    setTimeout(() => {
                        if (typeof window.inicializarListaParticipantes === 'function') {
                            window.inicializarListaParticipantes();
                        }
                    }, 100);
                }
            },
            'perfil': {
                html: 'PerfilOrganizador.html',
                js: ['PerfilOrganizador.js'],
                init: () => {
                    if (typeof window.inicializarEventosPerfilOrganizador === 'function') window.inicializarEventosPerfilOrganizador();
                }
            },
            'certificados': {
                html: 'CertificadosOrganizador.html',
                js: [],
                init: () => {}
            },
            'configuracoes': {
                html: 'ConfiguracoesOrganizador.html',
                js: [],
                init: () => {}
            },
            'faleconosco': {
                html: '../PaginasGlobais/FaleConosco.html',
                js: [],
                init: () => {
                    carregarFaleConoscoScript();
                }
            },
            'redefinirSenha': {
                html: '../PaginasGlobais/RedefinirSenhaConta.html',
                js: ['../PaginasGlobais/RedefinirSenhaConta.js'],
                init: () => {
                    if (typeof window.atribuirEventoRedefinirSenha === 'function') window.atribuirEventoRedefinirSenha();
                }
            },
            'termos': {
                html: '../PaginasGlobais/TermosDeCondicoes.html',
                js: [],
                init: () => {}
            },
            'emailRecuperacao': {
                html: '../PaginasGlobais/EmailDeRecuperacao.html',
                js: [],
                init: () => {}
            },
            'temaDoSite': {
                html: '../PaginasGlobais/TemaDoSite.php',
                js: [],
                init: () => {}
            },
            'manualDeUso': {
                html: '../PaginasGlobais/ManualDeUso.html',
                js: [],
                init: () => {}
            },
            'duvidasFrequentes': {
                html: '../PaginasGlobais/DuvidasFrequentes.html',
                js: [],
                init: () => {}
            },
            'sobreNos': {
                html: '../PaginasGlobais/SobreNos.html',
                js: [],
                init: () => {}
            },
            'adicionarEvento': {
                html: 'AdicionarEvento.html',
                js: [],
                init: () => {}
            },
            'painelnotificacoes': {
                html: '../PaginasGlobais/PainelNotificacoes.php',
                js: ['../PaginasGlobais/PainelNotificacoes.js'],
                init: () => {
                    console.log('✅ Painel de Notificações carregado');
                    // Força a inicialização do script mesmo após DOMContentLoaded
                    if (typeof window.inicializarPainelNotificacoes === 'function') {
                        window.inicializarPainelNotificacoes();
                    }
                }
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

        function carregarPagina(pagina, codEvento) {
            // Limpeza completa antes de carregar nova página
            if (typeof window.limpezaCompleta === 'function') {
                window.limpezaCompleta();
            }

            if (typeof window.removerFiltroExistente === 'function') {
                try {
                    window.removerFiltroExistente();
                } catch (e) {
                    /* noop */
                }
            }

            let url = 'ContainerOrganizador.php?pagina=' + encodeURIComponent(pagina);
            if (codEvento) {
                url += '&cod_evento=' + encodeURIComponent(codEvento);
            }

            fetch(url)
                .then(response => response.text())
                .then(html => {
                    const temp = document.createElement('div');
                    temp.innerHTML = html;
                    const novoConteudo = temp.querySelector('#conteudo-dinamico');
                    if (novoConteudo) {
                        const alvo = document.getElementById('conteudo-dinamico');
                        alvo.innerHTML = novoConteudo.innerHTML;

                        // Garante que a classe js-ready está presente
                        if (!document.body.classList.contains('js-ready')) {
                            document.body.classList.add('js-ready');
                        }

                        // Força scroll para o topo ao trocar de página
                        window.scrollTo(0, 0);

                        // Executa scripts embutidos na página carregada (ex.: TemaDoSite.php)
                        executarScriptsNoConteudo(alvo);
                        sincronizarMenuComConteudo();
                        atualizarClasseBody(pagina);
                        if (typeof window.setMenuAtivoPorPagina === 'function') {
                            window.setMenuAtivoPorPagina(pagina);
                        }
                        executarRota(pagina);

                        // Reinicializa o gerenciador de notificações
                        if (typeof window.gerenciadorNotificacoes !== 'undefined' && window.gerenciadorNotificacoes) {
                            window.gerenciadorNotificacoes.reinicializar();
                        }

                        // Se for página de evento organizado, carrega dados do evento
                        if (pagina === 'eventoOrganizado' && codEvento) {
                            setTimeout(() => {
                                if (typeof window.carregarDadosEvento === 'function') {
                                    window.carregarDadosEvento(codEvento);
                                }
                            }, 100);
                        }

                        // Reinicia verificação de sessão para nova página (5 minutos)
                        if (typeof window.reiniciarVerificacaoSessao === 'function') {
                            window.reiniciarVerificacaoSessao(300);
                        }
                    }
                    window.history.pushState({}, '', url);
                });
        }

        // =========================
        // Eventos de inicialização
        // =========================

        // Desabilita restauração automática de scroll do navegador
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }

        window.carregarPagina = carregarPagina;

        window.onpopstate = function() {
            const params = new URLSearchParams(window.location.search);
            const pagina = params.get('pagina') || 'inicio';
            const codEvento = params.get('cod_evento');
            carregarPagina(pagina, codEvento);
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
        });
    </script>
    <script src="../PaginasGlobais/GerenciadorTimers.js"></script>
    <script src="../PaginasGlobais/VerificacaoSessao.js"></script>
    <script src="../PaginasGlobais/GerenciadorNotificacoes.js"></script>
    <script src="../PaginasGlobais/ResponsividadeMobile.js"></script>
</body>

</html>