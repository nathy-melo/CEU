function inicializarFiltroEventos() {
    const searchInput = document.querySelector('.campo-pesquisa');
    const searchButton = document.querySelector('.botao-pesquisa');
    const eventosContainer = document.getElementById('eventos-container');

    // Usa um ID único para garantir que só exista uma mensagem
    const MSG_ID = 'mensagem-sem-resultados-eventos';
    
    function atualizarMensagemSemResultados(existemVisiveis) {
        // Remove mensagem existente primeiro
        const mensagemExistente = document.getElementById(MSG_ID);
        if (mensagemExistente) {
            mensagemExistente.remove();
        }
        
        // Adiciona nova mensagem se necessário
        if (!existemVisiveis && eventosContainer) {
            const semResultadosMsg = document.createElement('div');
            semResultadosMsg.id = MSG_ID;
            semResultadosMsg.innerHTML = 'Sem resultados. <br>Você não possui inscrições ativas.';
            semResultadosMsg.style.color = 'var(--botao)';
            semResultadosMsg.style.fontSize = '1.2rem';
            semResultadosMsg.style.gridColumn = '1/-1';
            semResultadosMsg.style.textAlign = 'center';
            semResultadosMsg.style.padding = '30px 0';
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
