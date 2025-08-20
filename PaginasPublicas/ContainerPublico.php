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
    'inicio' => 'Inicio.html',
    'login' => 'Login.html',
    'cadastroP' => 'CadastroParticipante.html',
    'cadastroO' => 'CadastroOrganizador.html',
    'redefinirSenha' => 'RedefinirSenha.html',
    'evento' => 'CartaodoEvento.html',
    'solicitarCodigo' => 'SolicitarCodigo.html',

    // Reaproveita conteúdos globais quando aplicável
    'faleConosco' => '../PaginasGlobais/FaleConosco.html',
    'termos' => '../PaginasGlobais/TermosDeCondicoes.html',
    // Adicione novas páginas conforme necessário - não se esqueça de as adicionar no menu (JS) também!
];

// Página padrão se não existir
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

// Loader de scripts reutilizável (global) para uso tanto no carregamento inicial quanto via AJAX
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

function carregarPagina(pagina) {
    // Remove o filtro lateral (se existir) antes de trocar de página (versão pública)
    if (typeof window.removerFiltroExistente === 'function') {
        try { window.removerFiltroExistente(); } catch (e) { /* noop */ }
    }

    fetch('ContainerPublico.php?pagina=' + pagina)
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

                // Mapeamento manual: associa cada nome de página ao seu respectivo arquivo JS.
                // Se adicionar uma nova página HTML e quiser que ela carregue um JS específico,
                // basta adicionar uma nova entrada aqui, usando o mesmo nome da chave usada em $paginasPermitidas do PHP.
                // Exemplo: 'minhaPagina': 'MinhaPagina.js'
                const jsFile = {
                    'inicio': 'Inicio.js',
                    'redefinirSenha': 'RedefinirSenha.js',
                    'solicitarCodigo': 'SolicitarCodigo.js',
                    'faleConosco': '../PaginasGlobais/FaleConosco.js',
                    'menuBloqueado': 'MenuBloqueado.js'
                }[pagina];

                // Para a página inicial pública, também carregamos o filtro lateral reutilizando o do Globais
                if (pagina === 'inicio') {
                    const scriptsParaCarregar = [
                        '../PaginasGlobais/Filtro.js',
                        'Inicio.js'
                    ];

                    carregarScripts(scriptsParaCarregar, () => {
                        if (typeof window.inicializarFiltroEventos === 'function') window.inicializarFiltroEventos();
                        if (typeof window.inicializarFiltro === 'function') window.inicializarFiltro();
                    });
                } else if (jsFile) {
                    // Comportamento padrão: carrega 1 script e executa inicializações específicas
                    carregarScripts([jsFile], () => {
                        if (pagina === 'redefinirSenha' && typeof atribuirEventoRedefinirSenha === 'function') atribuirEventoRedefinirSenha();
                        if (pagina === 'faleConosco') {
                            carregarFaleConoscoScript();
                        }
                        if (pagina === 'solicitarCodigo') {
                            if (typeof inicializarMascaras === 'function') inicializarMascaras();
                            var form = document.querySelector('.corpo-formulario');
                            if (form && typeof mostrarMensagemSolicitacaoEnviada === 'function') {
                                var btnEnviar = form.querySelector('button[type="submit"]');
                                if (btnEnviar) {
                                    btnEnviar.onclick = function(e) {
                                        e.preventDefault();
                                        if (!form.checkValidity()) {
                                            form.reportValidity();
                                            return;
                                        }
                                        mostrarMensagemSolicitacaoEnviada();
                                    };
                                }
                            }
                        }
                    });
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
    // Detecta a página atual pela URL (?pagina=...)
    const params = new URLSearchParams(window.location.search);
    const pagina = params.get('pagina') || 'inicio';
    if (typeof window.setMenuAtivoPorPagina === 'function') {
        window.setMenuAtivoPorPagina(pagina);
    }
    sincronizarMenuComConteudo();
    if (pagina === 'faleConosco') {
        carregarFaleConoscoScript();
    }
    // Garantir que, ao recarregar a página inicial (F5), o filtro seja carregado e inicializado
    if (pagina === 'inicio') {
        const scriptsParaCarregar = [
            '../PaginasGlobais/Filtro.js'
        ];
        carregarScripts(scriptsParaCarregar, () => {
            if (typeof window.inicializarFiltroEventos === 'function') window.inicializarFiltroEventos();
            if (typeof window.inicializarFiltro === 'function') window.inicializarFiltro();
        });
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
