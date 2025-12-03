function inicializarFiltroEventos() {
    const searchInput = document.querySelector('.campo-pesquisa');
    const searchButton = document.querySelector('.botao-pesquisa');
    const eventosContainer = document.getElementById('eventos-container');

    // Mensagem de "Sem resultados"
    let semResultadosMsg = document.createElement('div');
    semResultadosMsg.textContent = 'Sem resultados';
    semResultadosMsg.style.color = 'var(--botao)';
    semResultadosMsg.style.fontWeight = 'bold';
    semResultadosMsg.style.fontSize = '1.2rem';
    semResultadosMsg.style.gridColumn = '1/-1';
    semResultadosMsg.style.textAlign = 'center';
    semResultadosMsg.style.padding = '30px 0';

    function atualizarMensagemSemResultados(existemVisiveis) {
        if (eventosContainer && eventosContainer.contains(semResultadosMsg)) {
            eventosContainer.removeChild(semResultadosMsg);
        }
        if (!existemVisiveis && eventosContainer) {
            eventosContainer.appendChild(semResultadosMsg);
        }
    }

    function filtrarEventos() {
        const termo = (searchInput?.value || '').trim().toLowerCase();
        const caixas = eventosContainer?.querySelectorAll('.CaixaDoEvento') || [];
        let algumaVisivel = false;

        caixas.forEach(caixa => {
            const titulo = (caixa.querySelector('.EventoTitulo')?.textContent || '').toLowerCase();
            const info = (caixa.querySelector('.EventoInfo')?.textContent || '').toLowerCase();

            const correspondeBusca = (termo === '' || titulo.includes(termo) || info.includes(termo));

            if (!correspondeBusca) {
                caixa.dataset.hiddenBySearch = 'true';
            } else {
                delete caixa.dataset.hiddenBySearch;
            }

            // Usa função de atualização de visibilidade se disponível (integração com paginação)
            if (typeof window.atualizarVisibilidadeEvento === 'function') {
                window.atualizarVisibilidadeEvento(caixa);
            } else {
                // Fallback para comportamento antigo
                const hiddenByFilter = caixa.dataset.hiddenByFilter === 'true';
                const hiddenBySearch = caixa.dataset.hiddenBySearch === 'true';
                const deveOcultar = hiddenByFilter || hiddenBySearch;
                caixa.style.display = deveOcultar ? 'none' : '';
            }
            
            // Verificar se está visível
            if (caixa.style.display !== 'none') algumaVisivel = true;
        });

        atualizarMensagemSemResultados(algumaVisivel);
        
        // Resetar paginação para primeira página e atualizar contador de eventos
        if (typeof window.resetarPaginacao === 'function') {
            window.resetarPaginacao('eventos-container');
        }
    }

    if (searchButton) {
        searchButton.onclick = function (e) {
            e.preventDefault();
            filtrarEventos();
        };
    }
    if (searchInput) {
        searchInput.onkeydown = function (e) {
            if (e.key === 'Enter') {
                filtrarEventos();
            }
        };
        searchInput.addEventListener('input', filtrarEventos);
    }

    // Primeira avaliação para alinhar com o estado inicial do filtro lateral, se existir
    filtrarEventos();
}

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar paginação primeiro (se disponível)
    if (typeof window.inicializarPaginacaoEventos === 'function') {
        window.inicializarPaginacaoEventos('eventos-container');
    }
    
    inicializarFiltroEventos();
    
    // Inicializa o filtro lateral quando disponível
    if (typeof inicializarFiltro === 'function') {
        inicializarFiltro();
    }
});
// Se usar AJAX para recarregar a página, chame window.inicializarFiltroEventos() após inserir o HTML
window.inicializarFiltroEventos = inicializarFiltroEventos;
