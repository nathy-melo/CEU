function createFilterElement() {
    const filterHtml = `
        <form class="filtro-container" id="filtro-container">
            <h1 class="filtro-titulo">Filtrar por</h1>

            <fieldset class="filtro-grupo">
            <legend class="grupo-titulo">
                Período
                <span class="titulo-icone"></span>
            </legend>
            <div class="entradas-data">
                <div class="entrada-data">
                <label for="data-inicio" class="texto-data">De</label>
                <input type="date" id="data-inicio" name="data-inicio" class="input-data">
                </div>
                <div class="entrada-data">
                <label for="data-fim" class="texto-data">Até</label>
                <input type="date" id="data-fim" name="data-fim" class="input-data">
                </div>
            </div>
            </fieldset>

            <div class="divisor">
            <div class="divisor-linha"></div>
            </div>

            <fieldset class="filtro-grupo">
            <legend class="grupo-titulo">
                Tipo de Evento
                <span class="titulo-icone"></span>
            </legend>
            <div class="colunas-checkbox">
                <div class="coluna">
                <label class="item-checkbox"><input type="checkbox" name="tipo_evento" value="congresso"><span class="checkbox-personalizado"></span><span>Congresso</span></label>
                <label class="item-checkbox"><input type="checkbox" name="tipo_evento" value="palestra"><span class="checkbox-personalizado"></span><span>Palestra</span></label>
                <label class="item-checkbox"><input type="checkbox" name="tipo_evento" value="workshop"><span class="checkbox-personalizado"></span><span>Workshop</span></label>
                <label class="item-checkbox"><input type="checkbox" name="tipo_evento" value="forum"><span class="checkbox-personalizado"></span><span>Fórum</span></label>
                <label class="item-checkbox"><input type="checkbox" name="tipo_evento" value="conferencia"><span class="checkbox-personalizado"></span><span>Conferência</span></label>
                <label class="item-checkbox"><input type="checkbox" name="tipo_evento" value="seminario"><span class="checkbox-personalizado"></span><span>Seminário</span></label>
                </div>
                <div class="coluna">
                <label class="item-checkbox"><input type="checkbox" name="tipo_evento" value="visita_tecnica"><span class="checkbox-personalizado"></span><span>Visita Técnica</span></label>
                <label class="item-checkbox"><input type="checkbox" name="tipo_evento" value="curso"><span class="checkbox-personalizado"></span><span>Curso</span></label>
                <label class="item-checkbox"><input type="checkbox" name="tipo_evento" value="oficina"><span class="checkbox-personalizado"></span><span>Oficina</span></label>
                </div>
            </div>
            </fieldset>

            <div class="divisor">
            <div class="divisor-linha"></div>
            </div>

            <fieldset class="filtro-grupo">
            <legend class="grupo-titulo">
                Modalidade do Evento
                <span class="titulo-icone"></span>
            </legend>
            <div class="lista-checkbox">
                <label class="item-checkbox"><input type="checkbox" name="modalidade" value="presencial"><span class="checkbox-personalizado"></span><span>Presencial</span></label>
                <label class="item-checkbox"><input type="checkbox" name="modalidade" value="online"><span class="checkbox-personalizado"></span><span>Online</span></label>
                <label class="item-checkbox"><input type="checkbox" name="modalidade" value="hibrido"><span class="checkbox-personalizado"></span><span>Híbrido</span></label>
            </div>
            </fieldset>

            <div class="divisor">
            <div class="divisor-linha"></div>
            </div>

            <fieldset class="filtro-grupo">
            <legend class="grupo-titulo">
                Localização
                <span class="titulo-icone"></span>
            </legend>
            <div class="lista-checkbox">
                <label class="item-checkbox"><input type="checkbox" name="localizacao" value="auditorio"><span class="checkbox-personalizado"></span><span>Auditório</span></label>
                <label class="item-checkbox"><input type="checkbox" name="localizacao" value="quadra"><span class="checkbox-personalizado"></span><span>Quadra</span></label>
                <label class="item-checkbox"><input type="checkbox" name="localizacao" value="biblioteca"><span class="checkbox-personalizado"></span><span>Biblioteca</span></label>
                <label class="item-checkbox"><input type="checkbox" name="localizacao" value="sala_x"><span class="checkbox-personalizado"></span><span>Sala X</span></label>
            </div>
            </fieldset>

            <div class="divisor">
            <div class="divisor-linha"></div>
            </div>

            <fieldset class="filtro-grupo">
            <legend class="grupo-titulo">
                Duração do Evento
                <span class="titulo-icone"></span>
            </legend>
            <div class="lista-checkbox">
                <label class="item-checkbox"><input type="checkbox" name="duracao" value="menos_1h"><span class="checkbox-personalizado"></span><span>Menos de 1h</span></label>
                <label class="item-checkbox"><input type="checkbox" name="duracao" value="1h_2h"><span class="checkbox-personalizado"></span><span>1h-2h</span></label>
                <label class="item-checkbox"><input type="checkbox" name="duracao" value="2h_4h"><span class="checkbox-personalizado"></span><span>2h-4h</span></label>
                <label class="item-checkbox"><input type="checkbox" name="duracao" value="mais_5h"><span class="checkbox-personalizado"></span><span>+5h</span></label>
            </div>
            </fieldset>

            <div class="divisor">
            <div class="divisor-linha"></div>
            </div>

            <fieldset class="filtro-grupo">
            <legend class="grupo-titulo">
                Certificado
                <span class="titulo-icone"></span>
            </legend>
            <div class="lista-checkbox">
                <label class="item-checkbox"><input type="checkbox" name="certificado" value="sim"><span class="checkbox-personalizado"></span><span>Com certificado</span></label>
                <label class="item-checkbox"><input type="checkbox" name="certificado" value="nao"><span class="checkbox-personalizado"></span><span>Sem certificado</span></label>
            </div>
            </fieldset>
        </form>
    `;
    const div = document.createElement('div');
    div.innerHTML = filterHtml;
    const form = div.firstElementChild;

    // Restaura o estado do filtro se existir
    if (window.estadoFiltro) {
        for (const key in window.estadoFiltro) {
            const values = window.estadoFiltro[key];
            if (!values) continue;

            if (key.endsWith('[]')) { // Para checkboxes
                const cleanKey = key.slice(0, -2);
                const elements = form.querySelectorAll(`input[name="${cleanKey}"]`);
                elements.forEach(el => {
                    if (values.includes(el.value)) {
                        el.checked = true;
                    }
                });
            } else { // Para campos de data e outros
                const element = form.querySelector(`[name="${key}"]`);
                if (element) {
                    element.value = values[0] || '';
                }
            }
        }
    }

    return form;
}

function applyFiltersParticipante() {
    const container = document.getElementById('eventos-container');
    if (!container) return;

    const form = document.getElementById('filtro-container');
    if (!form) return;

    const getChecked = (name) => Array.from(form.querySelectorAll(`input[name="${name}"]:checked`)).map(i => i.value);

    const tipos = getChecked('tipo_evento');
    const locais = getChecked('localizacao');
    const duracoes = getChecked('duracao');
    const certificados = getChecked('certificado'); // novo: tipo de certificado

    const dataInicio = form.querySelector('input[name="data-inicio"]').value || '';
    const dataFim = form.querySelector('input[name="data-fim"]').value || '';

    const cards = container.querySelectorAll('.CaixaDoEvento');
    cards.forEach(card => {
        const tipo = (card.dataset.tipo || '').toLowerCase();
        const local = (card.dataset.localizacao || '').toLowerCase();
        const duracao = (card.dataset.duracao || '').toLowerCase();
        const certificado = (card.dataset.certificado || '').toLowerCase(); // novo
        const data = card.dataset.data || '';

        let ok = true;
        if (ok && tipos.length) ok = tipos.includes(tipo);
        if (ok && locais.length) ok = locais.includes(local);
        if (ok && duracoes.length) ok = duracoes.includes(duracao);
        if (ok && certificados.length) ok = certificados.includes(certificado);
        if (ok && dataInicio) ok = data >= dataInicio;
        if (ok && dataFim) ok = data <= dataFim;

        card.dataset.filterOk = ok ? 'true' : 'false';

        // Atualiza flag de filtro sem conflitar com a busca
        if (!ok) {
            card.dataset.hiddenByFilter = 'true';
        } else {
            delete card.dataset.hiddenByFilter;
        }

        const hiddenByFilter = card.dataset.hiddenByFilter === 'true';
        const hiddenBySearch = card.dataset.hiddenBySearch === 'true';
        const deveOcultar = hiddenByFilter || hiddenBySearch;
        card.style.display = deveOcultar ? 'none' : '';
    });
}

function wireFilterInputsParticipante() {
    const form = document.getElementById('filtro-container');
    if (!form) return;
    const inputs = form.querySelectorAll('input[type="checkbox"], input[type="date"]');
    inputs.forEach(inp => inp.addEventListener('change', applyFiltersParticipante));
}

function inicializarFiltro() {
    const filterButton = document.querySelector('.botao-filtrar');
    if (!filterButton) return;

    // Evitar adicionar o filtro se ele já existir
    if (document.getElementById('filtro-container')) {
        // Apenas liga os eventos caso já exista
        wireFilterInputsParticipante();
        applyFiltersParticipante();
        return;
    }

    const filterContainer = createFilterElement();
    document.body.appendChild(filterContainer);

    const toggleFiltro = (event) => {
        if (event) event.stopPropagation();
        const isAtivo = filterContainer.classList.contains('ativo');
        filterContainer.classList.toggle('ativo', !isAtivo);
        document.body.classList.toggle('filtro-ativo', !isAtivo);
    };

    filterButton.addEventListener('click', toggleFiltro);

    // Fecha o filtro se clicar fora dele (no overlay implícito)
    window.addEventListener('click', (event) => {
        if (filterContainer.classList.contains('ativo') && !filterContainer.contains(event.target)) {
            toggleFiltro();
        }
    });

    // Impede que cliques dentro do filtro o fechem
    filterContainer.addEventListener('click', (event) => {
        event.stopPropagation();
    });

    // Status dos ícones por grupo
    const filtroGrupos = filterContainer.querySelectorAll('.filtro-grupo');
    filtroGrupos.forEach(grupo => {
        const checkboxes = grupo.querySelectorAll('input[type="checkbox"]');
        const dateInputs = grupo.querySelectorAll('input[type="date"]');
        const tituloIcone = grupo.querySelector('.titulo-icone');

        if ((checkboxes.length > 0 || dateInputs.length > 0) && tituloIcone) {
            const atualizarStatusIcone = () => {
                let algumSelecionado = false;
                checkboxes.forEach(checkbox => { if (checkbox.checked) algumSelecionado = true; });
                dateInputs.forEach(dateInput => { if (dateInput.value) algumSelecionado = true; });
                tituloIcone.classList.toggle('ativo', algumSelecionado);
            };
            checkboxes.forEach(checkbox => checkbox.addEventListener('change', atualizarStatusIcone));
            dateInputs.forEach(dateInput => dateInput.addEventListener('change', atualizarStatusIcone));
            atualizarStatusIcone();
        }
    });

    // Liga inputs e aplica uma passada inicial
    wireFilterInputsParticipante();
    applyFiltersParticipante();
}

// Garante que a função de remoção esteja disponível globalmente e salve o estado
function removerFiltroExistente() {
    const filtroContainer = document.getElementById('filtro-container');
    if (filtroContainer) {
        const form = filtroContainer.tagName === 'FORM' ? filtroContainer : (filtroContainer.querySelector('form') || filtroContainer);
        window.estadoFiltro = {};
        form.querySelectorAll('input').forEach(el => {
            if (el.type === 'checkbox') {
                const name = `${el.name}[]`;
                if (el.checked) {
                    if (!window.estadoFiltro[name]) window.estadoFiltro[name] = [];
                    window.estadoFiltro[name].push(el.value);
                }
            } else {
                const name = el.name;
                if (!window.estadoFiltro[name]) window.estadoFiltro[name] = [];
                if (el.value) window.estadoFiltro[name].push(el.value);
            }
        });
        filtroContainer.remove();
    }
    document.body.classList.remove('filtro-ativo');
}

// Expor utilitários
window.applyFiltersParticipante = applyFiltersParticipante;
window.wireFilterInputsParticipante = wireFilterInputsParticipante;
window.removerFiltroExistente = removerFiltroExistente;
window.inicializarFiltro = inicializarFiltro;

