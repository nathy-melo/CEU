function inicializarFiltroEventos() {
    console.log('[EventosInscritosOrganizador] inicializando listagem e filtros');
    const searchInput = document.querySelector('.campo-pesquisa');
    const searchButton = document.querySelector('.botao-pesquisa');
    const eventosContainer = document.getElementById('eventos-container');

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

            const hiddenByFilter = caixa.dataset.hiddenByFilter === 'true';
            const hiddenBySearch = caixa.dataset.hiddenBySearch === 'true';
            const deveOcultar = hiddenByFilter || hiddenBySearch;

            caixa.style.display = deveOcultar ? 'none' : '';
            if (!deveOcultar) algumaVisivel = true;
        });

        atualizarMensagemSemResultados(algumaVisivel);
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

    if (typeof inicializarFiltro === 'function') {
        inicializarFiltro();
    }

    filtrarEventos();
}

document.addEventListener('DOMContentLoaded', function() {
    inicializarFiltroEventos();
});

window.inicializarFiltroEventos = inicializarFiltroEventos;
