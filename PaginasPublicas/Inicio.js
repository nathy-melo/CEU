function inicializarFiltroEventos() {
    const searchInput = document.querySelector('.campo-pesquisa');
    const searchButton = document.querySelector('.botao-pesquisa');
    const eventosContainer = document.getElementById('eventos-container');

    // Mensagem de "Sem resultados"
    let semResultadosMsg = document.createElement('div');
    semResultadosMsg.textContent = 'Sem resultados';
    semResultadosMsg.style.color = '#0a1449';
    semResultadosMsg.style.fontWeight = 'bold';
    semResultadosMsg.style.fontSize = '1.2rem';
    semResultadosMsg.style.gridColumn = '1/-1';
    semResultadosMsg.style.textAlign = 'center';
    semResultadosMsg.style.padding = '30px 0';

    function atualizarMensagemSemResultados(existemVisiveis) {
        if (eventosContainer.contains(semResultadosMsg)) {
            eventosContainer.removeChild(semResultadosMsg);
        }
        if (!existemVisiveis) {
            eventosContainer.appendChild(semResultadosMsg);
        }
    }

    function filtrarEventos() {
        const termo = (searchInput ? searchInput.value : '').trim().toLowerCase();
        const caixas = eventosContainer ? eventosContainer.querySelectorAll('.CaixaDoEvento') : [];
        let algumaVisivel = false;

        caixas.forEach(caixa => {
            const titulo = (caixa.querySelector('.EventoTitulo')?.textContent || '').toLowerCase();
            const info = (caixa.querySelector('.EventoInfo')?.textContent || '').toLowerCase();

            // Calcula se corresponde à busca
            const correspondeBusca = termo === '' || titulo.includes(termo) || info.includes(termo);

            // Marca estado da busca sem conflitar com o filtro lateral
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
        // Busca reativa enquanto digita (opcional, não intrusivo)
        searchInput.addEventListener('input', filtrarEventos);
    }

    // Primeira avaliação para alinhar com o estado inicial do filtro lateral, se existir
    filtrarEventos();
}

document.addEventListener('DOMContentLoaded', inicializarFiltroEventos);
// Se usar AJAX para recarregar a página, chame window.inicializarFiltroEventos() após inserir o HTML
window.inicializarFiltroEventos = inicializarFiltroEventos;
