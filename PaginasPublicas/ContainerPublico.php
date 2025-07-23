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
    'faleConosco' => 'FaleConosco.html',
    'termos' => 'TermosdeCondicoes.html',
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

function carregarPagina(pagina) {
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
                    'faleConosco': 'FaleConosco.js',
                    'menuBloqueado': 'MenuBloqueado.js'
                }[pagina];

                if (jsFile) {
                    const script = document.createElement('script');
                    script.src = jsFile;
                    script.onload = function() {
                        if (pagina === 'redefinirSenha' && typeof atribuirEventoRedefinirSenha === 'function') atribuirEventoRedefinirSenha();
                        if (pagina === 'solicitarCodigo') {
                            if (typeof inicializarMascaras === 'function') inicializarMascaras();
                            // Garante que o evento do botão Enviar será atribuído após o JS carregar
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
                        if (pagina === 'inicio' && typeof window.inicializarFiltroEventos === 'function') window.inicializarFiltroEventos();
                    };
                    document.getElementById('conteudo-dinamico').appendChild(script);
                }

                // Carrega termosToggle.js para páginas que usam o toggle de termos
                if (["cadastroP", "cadastroO"].includes(pagina)) {
                    var scriptToggle = document.createElement('script');
                    scriptToggle.src = 'termosToggle.js';
                    scriptToggle.onload = function() {
                        if (typeof inicializarToggleTermos === 'function') inicializarToggleTermos();
                    };
                    document.getElementById('conteudo-dinamico').appendChild(scriptToggle);
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
