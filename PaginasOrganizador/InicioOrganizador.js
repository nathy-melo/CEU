function inicializarFiltroEventos() {
    const searchInput = document.querySelector('.campo-pesquisa');
    const searchButton = document.querySelector('.botao-pesquisa');
    const eventosContainer = document.getElementById('eventos-container');

    // Mensagem de "Sem resultados" com instância única
    let semResultadosMsg = document.getElementById('sem-resultados-msg');
    if (!semResultadosMsg) {
        semResultadosMsg = document.createElement('div');
        semResultadosMsg.id = 'sem-resultados-msg';
        semResultadosMsg.textContent = 'Sem resultados';
        semResultadosMsg.style.color = 'var(--botao)';
        semResultadosMsg.style.fontWeight = 'bold';
        semResultadosMsg.style.fontSize = '1.2rem';
        semResultadosMsg.style.gridColumn = '1/-1';
        semResultadosMsg.style.textAlign = 'center';
        semResultadosMsg.style.padding = '30px 0';
    }

    function atualizarMensagemSemResultados(existemVisiveis) {
        const dups = eventosContainer?.querySelectorAll('#sem-resultados-msg');
        if (dups && dups.length > 1) dups.forEach((el, i) => { if (i > 0) el.remove(); });
        if (existemVisiveis) {
            if (eventosContainer && eventosContainer.contains(semResultadosMsg)) eventosContainer.removeChild(semResultadosMsg);
        } else {
            if (eventosContainer && !eventosContainer.contains(semResultadosMsg)) eventosContainer.appendChild(semResultadosMsg);
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

            // Marca estado da busca sem conflitar com o filtro lateral
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
                const hiddenByFilter = caixa.dataset.hiddenByFilter === 'true' || caixa.dataset.filterOk === 'false';
                const hiddenBySearch = caixa.dataset.hiddenBySearch === 'true';
                const deveOcultar = hiddenByFilter || hiddenBySearch;
                caixa.style.display = deveOcultar ? 'none' : '';
            }
            
            if (caixa.style.display !== 'none') algumaVisivel = true;
        });

        atualizarMensagemSemResultados(algumaVisivel);
        
        // Resetar paginação para primeira página e atualizar contador de eventos
        if (typeof window.resetarPaginacao === 'function') {
            window.resetarPaginacao('eventos-container');
        }
    }

    if (searchButton && !searchButton.dataset.buscaBound) {
        searchButton.onclick = function (e) {
            e.preventDefault();
            filtrarEventos();
        };
        searchButton.dataset.buscaBound = '1';
    }
    if (searchInput) {
        if (!searchInput.dataset.buscaEnterBound) {
            searchInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') filtrarEventos(); });
            searchInput.dataset.buscaEnterBound = '1';
        }
        if (!searchInput.dataset.buscaInputBound) {
            searchInput.addEventListener('input', filtrarEventos);
            searchInput.dataset.buscaInputBound = '1';
        }
    }

    // Inicializa o filtro lateral quando disponível
    if (typeof inicializarFiltro === 'function') {
        inicializarFiltro();
    }

    // Avaliação inicial
    filtrarEventos();
}

document.addEventListener('DOMContentLoaded', function(){ 
    inicializarFiltroEventos();
    
    // Inicializa a paginação
    if (typeof inicializarPaginacaoEventos === 'function') {
        inicializarPaginacaoEventos('eventos-container');
    }
});
// Se usar AJAX para recarregar a página, chame window.inicializarFiltroEventos() após inserir o HTML
window.inicializarFiltroEventos = inicializarFiltroEventos;
