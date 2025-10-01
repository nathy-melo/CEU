<?php
session_start();
if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
    header('Location: ../PaginasPublicas/ContainerPublico.php?pagina=login&erro=login_requerido');
    exit;
}
$tema_site = isset($_SESSION['tema_site']) ? (int)$_SESSION['tema_site'] : 0;
?>
<!DOCTYPE html>
<html lang="pt-br" <?php if ($tema_site === 1) { echo 'data-theme="dark"'; } ?> >

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CEU</title>
    <link rel="stylesheet" href="../styleGlobal.css" />
    <link rel="icon" type="image/png" href="../Imagens/CEU-Logo-1x1.png" />
</head>

<body <?php if ((($pagina ?? ($_GET['pagina'] ?? 'inicio')) === 'inicio')) { echo 'class="pagina-inicio"'; } ?>>
    <?php
    // Definição das páginas permitidas e resolução do arquivo a incluir
    $paginasPermitidas = [
        'inicio' => 'InicioOrganizador.html',
        'evento' => 'CartaodoEventoOrganizador.html',
        'eventoOrganizado' => 'CartaoDoEventoOrganizando.html',
        'meusEventos' => 'MeusEventosOrganizador.html',
        'perfil' => 'PerfilOrganizador.html',
        'certificados' => 'CertificadosOrganizador.html',
        'configuracoes' => 'ConfiguracoesOrganizador.html',
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
    <?php include 'MenuO.html'; ?>

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
            menuContentObserver.observe(menu, { attributes: true });
        }

        function carregarFaleConoscoScript() {
            const conteudo = document.getElementById('conteudo-dinamico');
            if (!conteudo) return;
            const scripts = conteudo.querySelectorAll('script[data-faleconosco]');
            scripts.forEach(s => s.remove());
            const script = document.createElement('script');
            script.src = '../PaginasGlobais/FaleConosco.js?t=' + new Date().getTime();
            script.setAttribute('data-faleconosco', '1');
            script.onload = function () {
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
        }

        // =========================
        // Definição das rotas
        // =========================
        const rotas = {
            'inicio': {
                html: 'InicioOrganizador.html',
                js: ['../PaginasGlobais/Filtro.js', 'InicioOrganizador.js'],
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
                html: 'CartaodoEventoOrganizador.html',
                js: ['CartaoDoEventoOrganizador.js'],
                init: () => {
                    if (typeof window.inicializarEventosCartaoEvento === 'function') window.inicializarEventosCartaoEvento();
                    setTimeout(function () {
                        var btnInscrever = document.querySelector('.botao-inscrever');
                        if (btnInscrever) {
                            btnInscrever.onclick = function () {
                                if (typeof window.mostrarMensagemInscricaoFeita === 'function') {
                                    window.mostrarMensagemInscricaoFeita();
                                }
                            };
                        }
                    }, 0);
                }
            },
            'eventoOrganizado': {
                html: 'CartaoDoEventoOrganizando.html',
                js: [],
                init: () => { }
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
                init: () => { }
            },
            'configuracoes': {
                html: 'ConfiguracoesOrganizador.html',
                js: [],
                init: () => { }
            },
            'faleconosco': {
                html: '../PaginasGlobais/FaleConosco.html',
                js: [],
                init: () => { carregarFaleConoscoScript(); }
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
                init: () => { }
            },
            'emailRecuperacao': {
                html: '../PaginasGlobais/EmailDeRecuperacao.html',
                js: [],
                init: () => { }
            },
            'temaDoSite': {
                html: '../PaginasGlobais/TemaDoSite.php',
                js: [],
                init: () => { }
            },
            'manualDeUso': {
                html: '../PaginasGlobais/ManualDeUso.html',
                js: [],
                init: () => { }
            },
            'duvidasFrequentes': {
                html: '../PaginasGlobais/DuvidasFrequentes.html',
                js: [],
                init: () => { }
            },
            'sobreNos': {
                html: '../PaginasGlobais/SobreNos.html',
                js: [],
                init: () => { }
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
                carregarScripts(scripts, () => { if (typeof rota.init === 'function') rota.init(); });
            } else {
                if (typeof rota.init === 'function') rota.init();
            }
        }

        function carregarPagina(pagina) {
            if (typeof window.removerFiltroExistente === 'function') {
                try { window.removerFiltroExistente(); } catch (e) { /* noop */ }
            }
            fetch('ContainerOrganizador.php?pagina=' + encodeURIComponent(pagina))
                .then(response => response.text())
                .then(html => {
                    const temp = document.createElement('div');
                    temp.innerHTML = html;
                    const novoConteudo = temp.querySelector('#conteudo-dinamico');
                    if (novoConteudo) {
                        const alvo = document.getElementById('conteudo-dinamico');
                        alvo.innerHTML = novoConteudo.innerHTML;
                        // Executa scripts embutidos na página carregada (ex.: TemaDoSite.php)
                        executarScriptsNoConteudo(alvo);
                        sincronizarMenuComConteudo();
                        atualizarClasseBody(pagina);
                        if (typeof window.setMenuAtivoPorPagina === 'function') {
                            window.setMenuAtivoPorPagina(pagina);
                        }
                        executarRota(pagina);
                    }
                    window.history.pushState({}, '', '?pagina=' + pagina);
                });
        }

        // =========================
        // Eventos de inicialização
        // =========================
        window.onpopstate = function () {
            const params = new URLSearchParams(window.location.search);
            const pagina = params.get('pagina') || 'inicio';
            carregarPagina(pagina);
        };

        document.addEventListener('DOMContentLoaded', function () {
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
</body>

</html>