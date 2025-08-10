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

    function filtrarEventos() {
        const termo = (searchInput?.value || '').trim().toLowerCase();
        const caixas = eventosContainer?.querySelectorAll('.CaixaDoEvento') || [];
        let encontrou = false;

        caixas.forEach(caixa => {
            const titulo = (caixa.querySelector('.EventoTitulo')?.textContent || '').toLowerCase();
            const info = (caixa.querySelector('.EventoInfo')?.textContent || '').toLowerCase();
            const searchOk = (termo === '' || titulo.includes(termo) || info.includes(termo));
            const filterOk = caixa.dataset.filterOk !== 'false';
            const mostrar = searchOk && filterOk;

            if (mostrar) {
                caixa.style.display = '';
                encontrou = true;
            } else {
                caixa.style.display = 'none';
            }
        });

        if (eventosContainer && eventosContainer.contains(semResultadosMsg)) {
            eventosContainer.removeChild(semResultadosMsg);
        }
        if (!encontrou && eventosContainer) {
            eventosContainer.appendChild(semResultadosMsg);
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
    }
    // Inicializa o filtro lateral quando disponível
    if (typeof inicializarFiltro === 'function') {
        inicializarFiltro();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    inicializarFiltroEventos();
});
// Se usar AJAX para recarregar a página, chame window.inicializarFiltroEventos() após inserir o HTML
window.inicializarFiltroEventos = inicializarFiltroEventos;
