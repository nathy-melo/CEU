<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CEU</title>
    <link rel="stylesheet" href="../styleGlobal.css" />
</head>

<body>
    <?php
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
?>

    <!-- Menu fixo -->
    <?php include 'MenuBloqueado.html'; ?>

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
                if (index >= lista.length) {
                    if (typeof callback === 'function') {
                        callback();
                    }
                    return;
                }

                const caminho = lista[index++];
                const jaCarregado = Array.from(document.getElementsByTagName('script')).some(function (scriptExistente) {
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
                script.onerror = function () {
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
            menuContentObserver.observe(menu, { attributes: true });
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
            script.onload = function () {
                if (typeof window.inicializarFaleConosco === 'function') {
                    window.inicializarFaleConosco();
                }
            };
            conteudo.appendChild(script);
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
                init: () => { }
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
                            btnEnviar.onclick = function (e) {
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
                init: () => { carregarFaleConoscoScript(); }
            },
            'termos': {
                html: '../PaginasGlobais/TermosDeCondicoes.html',
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
            // Remove o filtro lateral (se existir) antes de trocar de página (versão pública)
            if (typeof window.removerFiltroExistente === 'function') {
                try { window.removerFiltroExistente(); } catch (e) { /* noop */ }
            }
            fetch('ContainerPublico.php?pagina=' + encodeURIComponent(pagina))
                .then(response => response.text())
                .then(html => {
                    const temp = document.createElement('div');
                    temp.innerHTML = html;
                    const novoConteudo = temp.querySelector('#conteudo-dinamico');
                    if (novoConteudo) {
                        document.getElementById('conteudo-dinamico').innerHTML = novoConteudo.innerHTML;
                        sincronizarMenuComConteudo();
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
            executarRota(pagina);
        });
    </script>
</body>

</html>