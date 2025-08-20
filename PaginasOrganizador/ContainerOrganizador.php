<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CEU</title>
</head>
</html>


<?php
// Páginas permitidas (adicionar novas aqui!)
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
    'temaDoSite' => '../PaginasGlobais/TemaDoSite.html',
    'manualDeUso' => '../PaginasGlobais/ManualDeUso.html',
    'duvidasFrequentes' => '../PaginasGlobais/DuvidasFrequentes.html',
    'sobreNos' => '../PaginasGlobais/SobreNos.html',
        // Adicione novas páginas conforme necessário - não se esqueça de as adicionar no menu (JS) também!
];

// Página padrão se não existir
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
// Variável global para guardar o observer
let menuContentObserver = null;

function sincronizarMenuComConteudo() {
    const menu = document.querySelector(".Menu");
    const mainContent = document.getElementById("main-content");
    if (!menu || !mainContent) return;

    // Remove observer antigo, se existir
    if (menuContentObserver) {
        menuContentObserver.disconnect();
        menuContentObserver = null;
    }

    // Aplica classe inicial
    if (menu.classList.contains("expanded")) {
        mainContent.classList.add("shifted");
    } else {
        mainContent.classList.remove("shifted");
    }

    // Cria novo observer para o mainContent atual
    menuContentObserver = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.attributeName === 'class') {
                if (menu.classList.contains("expanded")) {
                    mainContent.classList.add("shifted");
                } else {
                    mainContent.classList.remove("shifted");
                }
            }
        });
    });
    menuContentObserver.observe(menu, { attributes: true });
}

function carregarFaleConoscoScript() {
    // Remove qualquer script antigo de FaleConosco.js
    const conteudo = document.getElementById('conteudo-dinamico');
    if (!conteudo) return;
    const scripts = conteudo.querySelectorAll('script[data-faleconosco]');
    scripts.forEach(s => s.remove());
    // Adiciona o novo script
    var script = document.createElement('script');
    script.src = '../PaginasGlobais/FaleConosco.js?t=' + new Date().getTime();
    script.setAttribute('data-faleconosco', '1');
    script.onload = function() {
        if (typeof window.inicializarFaleConosco === 'function') {
            window.inicializarFaleConosco();
        }
    };
    conteudo.appendChild(script);
}

function carregarPagina(pagina) {
    // Remove o filtro lateral (se existir) antes de trocar de página
    if (typeof window.removerFiltroExistente === 'function') {
        try { window.removerFiltroExistente(); } catch (e) { /* noop */ }
    }

    fetch('ContainerOrganizador.php?pagina=' + pagina)
        .then(response => response.text())
        .then(html => {
            const temp = document.createElement('div');
            temp.innerHTML = html;
            const novoConteudo = temp.querySelector('#conteudo-dinamico');
            if (novoConteudo) {
                document.getElementById('conteudo-dinamico').innerHTML = novoConteudo.innerHTML;
                sincronizarMenuComConteudo();

                // Ativa o botão correto do menu conforme a página carregada
                if (typeof window.setMenuAtivoPorPagina === 'function') {
                    window.setMenuAtivoPorPagina(pagina);
                }

                // Carregamento sequencial de scripts por página
                const scriptsParaCarregar = {
                    'inicio': ['../PaginasGlobais/Filtro.js', 'InicioOrganizador.js'],
                    'meusEventos': ['../PaginasGlobais/Filtro.js', 'MeusEventosOrganizador.js'],
                    'evento': ['CartaoDoEventoOrganizador.js'],
                    'eventoOrganizador': ['EventoOrganizador.js'],
                    'perfil': ['PerfilOrganizador.js'],
                    'faleconosco': ['../PaginasGlobais/FaleConosco.js'],
                    'redefinirSenha': ['../PaginasGlobais/RedefinirSenhaConta.js']
                }[pagina] || [];

                function carregarScripts(lista, callback) {
                    let index = 0;
                    function proximo() {
                        if (index < lista.length) {
                            const script = document.createElement('script');
                            script.src = lista[index++] + '?t=' + new Date().getTime();
                            script.onload = proximo;
                            script.onerror = () => console.error('Falha ao carregar o script:', script.src);
                            document.getElementById('conteudo-dinamico').appendChild(script);
                        } else if (callback) {
                            callback();
                        }
                    }
                    proximo();
                }

                carregarScripts(scriptsParaCarregar, () => {
                    if (pagina === 'inicio' || pagina === 'meusEventos') {
                        if (typeof window.inicializarFiltroEventos === 'function') window.inicializarFiltroEventos();
                    } else if (pagina === 'perfil') {
                        if (typeof window.inicializarEventosPerfilOrganizador === 'function') window.inicializarEventosPerfilOrganizador();
                    } else if (pagina === 'eventoOrganizador') {
                        if (typeof window.inicializarEventosCartaoDoEventoOrganizador === 'function') window.inicializarEventosCartaoDoEventoOrganizador();
                    } else if (pagina === 'faleconosco') {
                        carregarFaleConoscoScript();
                    }
                });

                // Garante que o botão de inscrição funcione sempre ao entrar na página 'evento'
                if (pagina === 'evento') {
                    setTimeout(function() {
                        var btnInscrever = document.querySelector('.botao-inscrever');
                        if (btnInscrever) {
                            btnInscrever.onclick = function() {
                                if (typeof window.mostrarMensagemInscricaoFeita === 'function') {
                                    window.mostrarMensagemInscricaoFeita();
                                }
                            };
                        }
                    }, 0);
                }
                
            }
            window.history.pushState({}, '', '?pagina=' + pagina);
        });
}
window.onpopstate = function() {
    const params = new URLSearchParams(window.location.search);
    const pagina = params.get('pagina') || 'inicio';
    carregarPagina(pagina);
};

document.addEventListener("DOMContentLoaded", function() {
    const params = new URLSearchParams(window.location.search);
    const pagina = params.get('pagina') || 'inicio';
    if (typeof window.setMenuAtivoPorPagina === 'function') {
        window.setMenuAtivoPorPagina(pagina);
    }
    sincronizarMenuComConteudo();
    if (pagina === 'faleconosco') {
        carregarFaleConoscoScript();
    }
});
</script>

<style>
#main-content {
    transition: margin-left 0.3s;
    margin-left: 0;
}
#main-content.shifted {
    margin-left: 220px; /* ajuste conforme a largura do menu expandido */
}
</style>
