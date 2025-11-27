async function createFilterElement() {
    // Buscar opções do banco de dados
    let opcoesFiltro = null;
    try {
        const response = await fetch('../PaginasGlobais/BuscarOpcoesFiltro.php');
        opcoesFiltro = await response.json();
    } catch (error) {
        console.error('Erro ao carregar opções de filtro:', error);
        // Usar valores padrão se falhar
        opcoesFiltro = {
            tipos: [],
            localizacoes: [],
            duracoes: [],
            modalidades: [],
            certificados: [
                { valor: 'sim', label: 'Com certificado' },
                { valor: 'nao', label: 'Sem certificado' }
            ]
        };
    }

    // Gerar HTML dos tipos de evento dinamicamente
    const tiposHtml = opcoesFiltro.tipos.length > 0
        ? opcoesFiltro.tipos.map(tipo =>
            `<label class="item-checkbox"><input type="checkbox" name="tipo_evento" value="${tipo.valor}"><span class="checkbox-personalizado"></span><span>${tipo.label}</span></label>`
        ).join('')
        : '<p style="color: #999; font-size: 0.9rem; padding: 0.5rem;">Nenhum tipo disponível</p>';

    // Gerar HTML das localizações dinamicamente
    const locaisHtml = opcoesFiltro.localizacoes.length > 0
        ? opcoesFiltro.localizacoes.map(local =>
            `<label class="item-checkbox"><input type="checkbox" name="localizacao" value="${local.valor}"><span class="checkbox-personalizado"></span><span>${local.label}</span></label>`
        ).join('')
        : '<p style="color: #999; font-size: 0.9rem; padding: 0.5rem;">Nenhuma localização disponível</p>';

    // Gerar HTML das durações dinamicamente
    const duracoesHtml = opcoesFiltro.duracoes.length > 0
        ? opcoesFiltro.duracoes.map(duracao =>
            `<label class="item-checkbox"><input type="checkbox" name="duracao" value="${duracao.valor}"><span class="checkbox-personalizado"></span><span>${duracao.label}</span></label>`
        ).join('')
        : '<p style="color: #999; font-size: 0.9rem; padding: 0.5rem;">Nenhuma duração disponível</p>';

    // Gerar HTML das modalidades dinamicamente
    const modalidadesHtml = opcoesFiltro.modalidades.length > 0
        ? opcoesFiltro.modalidades.map(modalidade =>
            `<label class="item-checkbox"><input type="checkbox" name="modalidade" value="${modalidade.valor}"><span class="checkbox-personalizado"></span><span>${modalidade.label}</span></label>`
        ).join('')
        : '<p style="color: #999; font-size: 0.9rem; padding: 0.5rem;">Nenhuma modalidade disponível</p>';

    // Gerar HTML dos certificados
    const certificadosHtml = opcoesFiltro.certificados.length > 0
        ? opcoesFiltro.certificados.map(cert =>
            `<label class="item-checkbox"><input type="checkbox" name="certificado" value="${cert.valor}"><span class="checkbox-personalizado"></span><span>${cert.label}</span></label>`
        ).join('')
        : '<p style="color: #999; font-size: 0.9rem; padding: 0.5rem;">Nenhuma opção disponível</p>';

    // Layout único para tipos de evento (sem divisão em colunas)
    const tiposLayout = `<div class="lista-checkbox">${tiposHtml}</div>`; const filterHtml = `
        <form class="filtro-container" id="filtro-container">
            <h1 class="filtro-titulo">Filtrar por</h1>

            <fieldset class="filtro-grupo">
            <legend class="grupo-titulo">
                Ordenar
                <span class="titulo-icone"></span>
            </legend>
            <div class="lista-checkbox" style="flex-direction: row; gap: 15px;">
                <label class="item-checkbox"><input type="radio" name="ordenacao" value="az"><span class="checkbox-personalizado"></span><span>A - Z</span></label>
                <label class="item-checkbox"><input type="radio" name="ordenacao" value="za"><span class="checkbox-personalizado"></span><span>Z - A</span></label>
            </div>
            </fieldset>

            <div class="divisor">
            <div class="divisor-linha"></div>
            </div>

            <fieldset class="filtro-grupo">
            <legend class="grupo-titulo">
                Período
                <span class="titulo-icone"></span>
            </legend>
            <div class="entradas-data" style="display: flex; flex-direction: column; gap: 0.8rem;">
                <div class="entrada-data" style="display: flex; flex-direction: column; gap: 0.3rem;">
                <label for="data-inicio" class="texto-data" style="font-size: 0.85rem; font-weight: 500;">De</label>
                <input type="date" id="data-inicio" name="data-inicio" class="input-data" style="font-size: 0.85rem; padding: 0.4rem; width: 100%;">
                </div>
                <div class="entrada-data" style="display: flex; flex-direction: column; gap: 0.3rem;">
                <label for="data-fim" class="texto-data" style="font-size: 0.85rem; font-weight: 500;">Até</label>
                <input type="date" id="data-fim" name="data-fim" class="input-data" style="font-size: 0.85rem; padding: 0.4rem; width: 100%;">
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
            ${tiposLayout}
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
                ${modalidadesHtml}
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
                ${locaisHtml}
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
                ${duracoesHtml}
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
                ${certificadosHtml}
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

    // Adiciona funcionalidade para desmarcar radio buttons ao clicar novamente
    const radioButtons = form.querySelectorAll('input[type="radio"][name="ordenacao"]');
    let ultimoRadioSelecionado = null;

    radioButtons.forEach(radio => {
        radio.addEventListener('click', (e) => {
            if (ultimoRadioSelecionado === e.target) {
                e.target.checked = false;
                ultimoRadioSelecionado = null;

                // Atualizar o ícone de status do grupo de ordenação
                const grupoOrdenacao = e.target.closest('.filtro-grupo');
                const tituloIcone = grupoOrdenacao?.querySelector('.titulo-icone');
                if (tituloIcone) {
                    tituloIcone.classList.remove('ativo');
                }

                applyFiltersParticipante();
            } else {
                ultimoRadioSelecionado = e.target;

                // Atualizar o ícone de status do grupo de ordenação
                const grupoOrdenacao = e.target.closest('.filtro-grupo');
                const tituloIcone = grupoOrdenacao?.querySelector('.titulo-icone');
                if (tituloIcone) {
                    tituloIcone.classList.add('ativo');
                }
            }
        });
    });

    return form;
}

function applyFiltersParticipante() {
    const containerEventos = document.getElementById('eventos-container');
    const containerColaboracao = document.getElementById('eventos-colaboracao-container');

    if (!containerEventos) return;

    const form = document.getElementById('filtro-container');
    if (!form) {
        console.warn('Filtro.js: form #filtro-container não encontrado');
        return;
    }

    const getChecked = (name) => {
        return Array.from(form.querySelectorAll(`input[name="${name}"]:checked`)).map(i => i.value);
    };

    const tipos = getChecked('tipo_evento');
    const locais = getChecked('localizacao');
    const duracoes = getChecked('duracao');
    const certificados = getChecked('certificado');
    const modalidades = getChecked('modalidade');

    const dataInicio = form.querySelector('input[name="data-inicio"]').value || '';
    const dataFim = form.querySelector('input[name="data-fim"]').value || '';

    // Obter a ordenação selecionada
    const ordenacaoSelecionada = form.querySelector('input[name="ordenacao"]:checked')?.value || 'nenhuma';

    // Buscar eventos em ambos os containers
    const cardsEventos = Array.from(containerEventos.querySelectorAll('.CaixaDoEvento'));
    const cardsColaboracao = containerColaboracao ? Array.from(containerColaboracao.querySelectorAll('.CaixaDoEvento')) : [];
    const cards = [...cardsEventos, ...cardsColaboracao];

    // Armazenar ordem original se ainda não foi armazenada ou se os cards mudaram
    if (!window.ordemOriginalHrefs) {
        // Primeira vez: armazena a ordem atual
        window.ordemOriginalHrefs = cards.map(card => card.href || card.getAttribute('href') || '');
    } else {
        // Verificar se os cards mudaram (comparando conjuntos, não ordem)
        const hrefsAtuais = cards.map(card => card.href || card.getAttribute('href') || '');
        const conjuntoOriginal = new Set(window.ordemOriginalHrefs);
        const conjuntoAtual = new Set(hrefsAtuais);

        // Se os conjuntos são diferentes (card novo ou removido), os cards mudaram - resetar ordem original
        if (conjuntoOriginal.size !== conjuntoAtual.size ||
            Array.from(conjuntoAtual).some(href => !conjuntoOriginal.has(href))) {
            window.ordemOriginalHrefs = [...hrefsAtuais];
        }
    }

    cards.forEach(card => {
        // Normalizar tipo de evento removendo acentos
        let tipo = (card.dataset.tipo || '').toLowerCase();
        tipo = tipo.replace(/[áàâã]/g, 'a').replace(/[éèê]/g, 'e').replace(/[íìî]/g, 'i')
            .replace(/[óòôõ]/g, 'o').replace(/[úùû]/g, 'u').replace(/ç/g, 'c').replace(/ /g, '_');

        let local = (card.dataset.localizacao || '').toLowerCase();
        const duracao = (card.dataset.duracao || '').toLowerCase();
        const certificado = (card.dataset.certificado || '').toLowerCase();
        const modalidade = (card.dataset.modalidade || '').toLowerCase();
        const data = card.dataset.data || '';

        // Normalizar localização do card para corresponder às opções do filtro
        // Remover acentos da localização
        local = local.replace(/[áàâã]/g, 'a').replace(/[éèê]/g, 'e').replace(/[íìî]/g, 'i')
            .replace(/[óòôõ]/g, 'o').replace(/[úùû]/g, 'u').replace(/ç/g, 'c');

        if (local.includes('sala')) {
            local = 'salas';
        } else if (local.includes('laboratorio') || local.includes('lab')) {
            local = 'laboratorio';
        } else if (local.includes('auditorio')) {
            local = 'auditorio';
        } else if (local.includes('quadra')) {
            local = 'quadra';
        } else if (local.includes('biblioteca')) {
            local = 'biblioteca';
        } else if (local.includes('patio') || local.includes('pátio')) {
            local = 'patio';
        } else if (local.includes('ginasio') || local.includes('ginásio')) {
            local = 'ginasio';
        } else if (local.includes('online') || local.includes('virtual') || local.includes('remoto')) {
            local = 'online';
        }

        let ok = true;
        if (ok && tipos.length) ok = tipos.includes(tipo);
        if (ok && locais.length) ok = locais.includes(local);

        // Filtrar por duração usando os intervalos corretos
        if (ok && duracoes.length) {
            const duracaoNum = parseFloat(card.dataset.duracaonumero || '0');
            let duracaoMatch = false;
            for (const d of duracoes) {
                if (d === 'menos_1h' && duracaoNum >= 0 && duracaoNum < 1) duracaoMatch = true;
                else if (d === '1h_2h' && duracaoNum >= 1 && duracaoNum < 2) duracaoMatch = true;
                else if (d === '2h_4h' && duracaoNum >= 2 && duracaoNum < 4) duracaoMatch = true;
                else if (d === '4h_6h' && duracaoNum >= 4 && duracaoNum < 6) duracaoMatch = true;
                else if (d === '6h_8h' && duracaoNum >= 6 && duracaoNum < 8) duracaoMatch = true;
                else if (d === '8h_10h' && duracaoNum >= 8 && duracaoNum < 10) duracaoMatch = true;
                else if (d === '10h_20h' && duracaoNum >= 10 && duracaoNum < 20) duracaoMatch = true;
                else if (d === 'mais_20h' && duracaoNum >= 20) duracaoMatch = true;
            }
            ok = duracaoMatch;
        }

        if (ok && certificados.length) ok = certificados.includes(certificado);
        if (ok && modalidades.length) ok = modalidades.includes(modalidade);
        if (ok && dataInicio) ok = data >= dataInicio;
        if (ok && dataFim) ok = data <= dataFim;

        card.dataset.filterOk = ok ? 'true' : 'false';

        // Atualiza flag de filtro sem conflitar com a busca
        if (!ok) {
            card.dataset.hiddenByFilter = 'true';
        } else {
            delete card.dataset.hiddenByFilter;
        }

        // Usa função de atualização de visibilidade se disponível (integração com paginação)
        if (typeof window.atualizarVisibilidadeEvento === 'function') {
            window.atualizarVisibilidadeEvento(card);
        } else {
            // Fallback para comportamento antigo
            const hiddenByFilter = card.dataset.hiddenByFilter === 'true';
            const hiddenBySearch = card.dataset.hiddenBySearch === 'true';
            const deveOcultar = hiddenByFilter || hiddenBySearch;
            card.style.display = deveOcultar ? 'none' : '';
        }
    });

    // Resetar paginação para primeira página e atualizar contador de eventos
    if (typeof window.resetarPaginacao === 'function') {
        window.resetarPaginacao('eventos-container');
    }

    // Aplicar ordenação alfabética ou restaurar ordem original
    if (ordenacaoSelecionada !== 'nenhuma') {
        // Aplicar ordenação alfabética
        const cardsOrdenados = [...cards].sort((a, b) => {
            const nomeA = (a.querySelector('.EventoTitulo')?.textContent || '').toLowerCase();
            const nomeB = (b.querySelector('.EventoTitulo')?.textContent || '').toLowerCase();

            if (ordenacaoSelecionada === 'az') {
                return nomeA.localeCompare(nomeB);
            } else if (ordenacaoSelecionada === 'za') {
                return nomeB.localeCompare(nomeA);
            }
            return 0;
        });

        // Reorganizar os cards nos seus respectivos containers
        cardsOrdenados.forEach(card => {
            const parentContainer = card.parentElement;
            if (parentContainer) {
                parentContainer.appendChild(card);
            }
        });
    } else {
        // Restaurar ordem original baseada nos hrefs armazenados
        if (window.ordemOriginalHrefs && window.ordemOriginalHrefs.length > 0) {
            // Criar um Map para acesso rápido aos cards por href
            const cardsPorHref = new Map();
            cards.forEach(card => {
                const href = card.href || card.getAttribute('href') || '';
                cardsPorHref.set(href, card);
            });

            // Reorganizar na ordem original em seus respectivos containers
            window.ordemOriginalHrefs.forEach(href => {
                const card = cardsPorHref.get(href);
                if (card) {
                    const parentContainer = card.parentElement;
                    if (parentContainer && (parentContainer === containerEventos || parentContainer === containerColaboracao)) {
                        parentContainer.appendChild(card);
                    }
                }
            });
        }
    }
}

function wireFilterInputsParticipante() {
    const form = document.getElementById('filtro-container');
    if (!form) return;
    const inputs = form.querySelectorAll('input[type="checkbox"], input[type="date"], input[type="radio"]');
    inputs.forEach(inp => {
        inp.addEventListener('change', applyFiltersParticipante);
    });
} async function inicializarFiltro() {
    const filterButton = document.querySelector('.botao-filtrar');
    if (!filterButton) return;

    // Evitar adicionar o filtro se ele já existir
    if (document.getElementById('filtro-container')) {
        // Apenas liga os eventos caso já exista
        wireFilterInputsParticipante();
        applyFiltersParticipante();
        return;
    }

    const filterContainer = await createFilterElement();
    document.body.appendChild(filterContainer);

    const toggleFiltro = (event) => {
        if (event) event.stopPropagation();
        const isAtivo = filterContainer.classList.contains('ativo');
        const mainContent = document.getElementById('main-content');

        filterContainer.classList.toggle('ativo', !isAtivo);

        // Adiciona ou remove a classe que empurra o conteúdo para a esquerda
        if (mainContent) {
            if (!isAtivo) {
                mainContent.classList.add('filtro-shifted');
                // Bloquear scroll quando filtro abre
                document.body.style.overflow = 'hidden';
            } else {
                mainContent.classList.remove('filtro-shifted');
                // Restaurar scroll quando filtro fecha
                document.body.style.overflow = '';
            }
        }
    }; filterButton.addEventListener('click', toggleFiltro);

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
        const radioButtons = grupo.querySelectorAll('input[type="radio"]');
        const tituloIcone = grupo.querySelector('.titulo-icone');

        if ((checkboxes.length > 0 || dateInputs.length > 0 || radioButtons.length > 0) && tituloIcone) {
            const atualizarStatusIcone = () => {
                let algumSelecionado = false;
                checkboxes.forEach(checkbox => { if (checkbox.checked) algumSelecionado = true; });
                dateInputs.forEach(dateInput => { if (dateInput.value) algumSelecionado = true; });
                radioButtons.forEach(radio => { if (radio.checked) algumSelecionado = true; });
                tituloIcone.classList.toggle('ativo', algumSelecionado);
            };

            // Adicionar evento de clique no ícone para limpar filtros da seção
            tituloIcone.addEventListener('click', (e) => {
                e.stopPropagation();
                if (tituloIcone.classList.contains('ativo')) {
                    // Desmarcar todos os checkboxes
                    checkboxes.forEach(checkbox => { checkbox.checked = false; });
                    // Limpar todos os campos de data
                    dateInputs.forEach(dateInput => { dateInput.value = ''; });
                    // Desmarcar todos os radio buttons
                    radioButtons.forEach(radio => { radio.checked = false; });
                    // Atualizar status do ícone
                    atualizarStatusIcone();
                    // Aplicar filtros novamente
                    applyFiltersParticipante();
                }
            });

            checkboxes.forEach(checkbox => checkbox.addEventListener('change', atualizarStatusIcone));
            dateInputs.forEach(dateInput => dateInput.addEventListener('change', atualizarStatusIcone));
            radioButtons.forEach(radio => radio.addEventListener('change', atualizarStatusIcone));
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

