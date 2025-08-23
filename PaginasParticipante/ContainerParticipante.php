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
    'inicio' => 'InicioParticipante.html',
    'evento' => 'CartaodoEventoParticipante.html',
    'eventoInscrito' => 'CartaodoEventoInscrito.html',
    'meusEventos' => 'MeusEventosParticipante.html',
    'perfil' => 'PerfilParticipante.html',
    'certificados' => 'CerticadosParticipante.html',
    'configuracoes' => 'ConfiguracoesParticipante.html',

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
<?php include 'MenuP.html'; ?>

<!-- Conteúdo dinâmico -->
<div id="conteudo-dinamico">
    <?php include $arquivo; ?>
</div>

<script>
// Variável global para guardar o estado do filtro
window.estadoFiltro = {}

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

function removerFiltroExistente() {
    const filtroContainer = document.getElementById('filtro-container');
    if (filtroContainer) {
        // Salva o estado antes de remover
        const form = filtroContainer.querySelector('form') || filtroContainer;
        const formData = new FormData(form);
        window.estadoFiltro = {};
        for (const [key, value] of formData.entries()) {
            if (!window.estadoFiltro[key]) {
                window.estadoFiltro[key] = [];
            }
            window.estadoFiltro[key].push(value);
        }
        filtroContainer.remove();
    }
    document.body.classList.remove('filtro-ativo');
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
    // 1. Remove o filtro da página anterior ANTES de carregar o novo conteúdo.
    removerFiltroExistente();

    fetch('ContainerParticipante.php?pagina=' + pagina)
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

                // Mapeamento de scripts aprimorado para carregar dependências na ordem correta.
                const scriptsParaCarregar = {
                    'inicio': ['../PaginasGlobais/FIltro.js', 'InicioParticipante.js'],
                    'meusEventos': ['../PaginasGlobais/FIltro.js', 'MeusEventosParticipante.js'],
                    'evento': ['CartaoDoEventoParticipante.js'],
                    'eventoInscrito': ['CartaoDoEventoInscrito.js'],
                    'perfil': ['PerfilParticipante.js'],
                    'faleconosco': ['../PaginasGlobais/FaleConosco.js'],
                    'redefinirSenha': ['../PaginasGlobais/RedefinirSenhaConta.js']
                }[pagina] || [];

                // Função para carregar múltiplos scripts em sequência.
                function carregarScripts(lista, callback) {
                    let index = 0;
                    function proximo() {
                        if (index < lista.length) {
                            const script = document.createElement('script');
                            // Adiciona um timestamp para evitar problemas de cache
                            script.src = lista[index++] + '?t=' + new Date().getTime();
                            script.onload = proximo;
                            script.onerror = () => console.error(`Falha ao carregar o script: ${script.src}`);
                            document.body.appendChild(script).parentNode.removeChild(script);
                        } else if (callback) {
                            callback();
                        }
                    }
                    proximo();
                }

                // Carrega os scripts da página e, em seguida, executa as funções de inicialização.
                carregarScripts(scriptsParaCarregar, () => {
                    if (pagina === 'inicio' || pagina === 'meusEventos') {
                        if (typeof inicializarFiltroEventos === 'function') inicializarFiltroEventos();
                    } else if (pagina === 'perfil') {
                        if (typeof inicializarEventosPerfilParticipante === 'function') inicializarEventosPerfilParticipante();
                    } else if (pagina === 'evento') {
                        if (typeof inicializarEventosCartaoEvento === 'function') inicializarEventosCartaoEvento();
                    } else if (pagina === 'eventoInscrito') {
                        if (typeof window.inicializarEventosCartaoDoEventoInscrito === 'function') {
                            window.inicializarEventosCartaoDoEventoInscrito();
                        }
                    } else if (pagina === 'faleconosco') {
                        carregarFaleConoscoScript();
                    }
                });
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
    // Detecta a página atual pela URL (?pagina=...)
    const params = new URLSearchParams(window.location.search);
    const pagina = params.get('pagina') || 'inicio';
    if (typeof window.setMenuAtivoPorPagina === 'function') {
        window.setMenuAtivoPorPagina(pagina);
    }
    sincronizarMenuComConteudo();

    // Garante que o FaleConosco.js seja carregado e inicializado ao abrir diretamente a página
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
