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
    'faleconosco' => 'FaleConoscoParticipante.html',
    'configuracoes' => 'ConfiguracoesParticipante.html',
    'termos' => 'TermosDeCondicoesP.html'
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
    fetch('ContainerParticipante.php?pagina=' + pagina)
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
                    'inicio': 'InicioParticipante.js',
                    'evento': 'CartaoEventoParticipante.js',
                    'eventoInscrito': 'CartaoEventoInscrito.js',
                    'perfil': 'PerfilParticipante.js',
                    'faleconosco': 'FaleConoscoParticipante.js',
                    'meusEventos': 'MeusEventosParticipante.js', // ADICIONADO
                }[pagina];

                if (jsFile) {
                    const script = document.createElement('script');
                    script.src = jsFile;
                    script.onload = function() {
                        if (pagina === 'inicio' && typeof window.inicializarFiltroEventos === 'function') window.inicializarFiltroEventos();
                        if (pagina === 'perfil' && typeof window.inicializarEventosPerfilParticipante === 'function') window.inicializarEventosPerfilParticipante();
                        if (pagina === 'meusEventos' && typeof window.inicializarFiltroEventos === 'function') window.inicializarFiltroEventos(); // ADICIONADO
                    };
                    document.getElementById('conteudo-dinamico').appendChild(script);
                }

                // Garante que o botão de inscrição funcione sempre ao entrar na página 'evento'
                if (pagina === 'evento') {
                    // Aguarda o DOM do conteudo-dinamico ser atualizado
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
