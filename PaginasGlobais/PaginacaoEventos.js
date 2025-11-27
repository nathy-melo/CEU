/**
 * Sistema de Paginação de Eventos
 * 
 * Sistema completo de paginação com:
 * - Select para escolher eventos por página (16, 24, 36, 48, 56, 64, 72)
 * - Navegação por páginas numeradas
 * - Contador de eventos encontrados
 * - Oculta eventos finalizados por padrão
 */

// Configurações globais de paginação
window.paginacaoConfig = {
    eventosPorPagina: 16,
    paginaAtual: 1,
    totalPaginas: 1,
    totalEventos: 0,
    mostrarFinalizados: false,
    opcoesEventosPorPagina: [16, 24, 36, 48, 56, 64, 72]
};

/**
 * Inicializa o sistema de paginação para eventos
 * @param {string} containerId - ID do container de eventos
 * @param {boolean} ocultarFiltroFinalizados - Se true, sempre mostra finalizados e não adiciona filtro
 */
function inicializarPaginacaoEventos(containerId = 'eventos-container', ocultarFiltroFinalizados = false) {
    const container = document.getElementById(containerId);
    if (!container) {
        console.warn('Container de eventos não encontrado:', containerId);
        return;
    }

    // Se ocultar filtro, sempre mostrar finalizados
    if (ocultarFiltroFinalizados) {
        window.paginacaoConfig.mostrarFinalizados = true;
    }

    // Aplicar paginação inicial
    aplicarPaginacaoEventos(containerId);

    // Adicionar controles de paginação no rodapé
    criarControlesPaginacao(containerId);

    // Adicionar checkbox no filtro para eventos finalizados (se não for para ocultar)
    adicionarFiltroEventosFinalizados(containerId, ocultarFiltroFinalizados);
}

/**
 * Verifica se um evento está finalizado
 * @param {HTMLElement} eventoElement - Elemento do evento
 * @returns {boolean}
 */
function eventoEstaFinalizado(eventoElement) {
    const dataFimAttr = eventoElement.dataset.dataFim || eventoElement.dataset.conclusao;
    if (!dataFimAttr) return false;

    try {
        // Tenta parsear data em formato ISO (YYYY-MM-DD)
        const dataFim = new Date(dataFimAttr);
        const agora = new Date();
        return dataFim < agora;
    } catch (e) {
        console.warn('Erro ao parsear data de conclusão:', dataFimAttr);
        return false;
    }
}

/**
 * Aplica a paginação aos eventos no container
 * @param {string} containerId - ID do container de eventos
 */
function aplicarPaginacaoEventos(containerId = 'eventos-container') {
    const container = document.getElementById(containerId);
    if (!container) return;

    const todosEventos = Array.from(container.querySelectorAll('.CaixaDoEvento'));
    const config = window.paginacaoConfig;

    // Separar eventos finalizados e não finalizados
    const eventosNaoFinalizados = [];
    const eventosFinalizados = [];

    todosEventos.forEach(evento => {
        // Verificar se está oculto por filtro ou busca
        const hiddenByFilter = evento.dataset.hiddenByFilter === 'true';
        const hiddenBySearch = evento.dataset.hiddenBySearch === 'true';
        
        if (!hiddenByFilter && !hiddenBySearch) {
            if (eventoEstaFinalizado(evento)) {
                eventosFinalizados.push(evento);
            } else {
                eventosNaoFinalizados.push(evento);
            }
        }
    });

    // Determinar eventos disponíveis
    let eventosDisponiveis = [];
    if (config.mostrarFinalizados) {
        eventosDisponiveis = [...eventosNaoFinalizados, ...eventosFinalizados];
    } else {
        eventosDisponiveis = eventosNaoFinalizados;
    }

    // Calcular total de páginas
    config.totalEventos = eventosDisponiveis.length;
    config.totalPaginas = Math.ceil(config.totalEventos / config.eventosPorPagina);
    
    // Garantir que página atual é válida
    if (config.paginaAtual > config.totalPaginas) {
        config.paginaAtual = Math.max(1, config.totalPaginas);
    }

    // Calcular índices da página atual
    const indiceInicio = (config.paginaAtual - 1) * config.eventosPorPagina;
    const indiceFim = indiceInicio + config.eventosPorPagina;

    // Ocultar todos os eventos primeiro
    todosEventos.forEach(evento => {
        evento.dataset.hiddenByPagination = 'true';
        atualizarVisibilidadeEvento(evento);
    });

    // Mostrar apenas eventos da página atual
    const eventosParaMostrar = eventosDisponiveis.slice(indiceInicio, indiceFim);
    eventosParaMostrar.forEach(evento => {
        delete evento.dataset.hiddenByPagination;
        atualizarVisibilidadeEvento(evento);
    });

    // Atualizar controles de paginação
    atualizarControlesPaginacao(containerId);
}

/**
 * Atualiza a visibilidade de um evento considerando todos os filtros
 * @param {HTMLElement} evento - Elemento do evento
 */
function atualizarVisibilidadeEvento(evento) {
    const hiddenByFilter = evento.dataset.hiddenByFilter === 'true';
    const hiddenBySearch = evento.dataset.hiddenBySearch === 'true';
    const hiddenByPagination = evento.dataset.hiddenByPagination === 'true';
    
    const deveOcultar = hiddenByFilter || hiddenBySearch || hiddenByPagination;
    evento.style.display = deveOcultar ? 'none' : '';
}

/**
 * Vai para uma página específica
 * @param {number} numeroPagina - Número da página
 * @param {string} containerId - ID do container de eventos
 */
function irParaPagina(numeroPagina, containerId = 'eventos-container') {
    const config = window.paginacaoConfig;
    config.paginaAtual = Math.max(1, Math.min(numeroPagina, config.totalPaginas));
    aplicarPaginacaoEventos(containerId);
}

/**
 * Altera a quantidade de eventos por página
 * @param {number} quantidade - Nova quantidade de eventos por página
 * @param {string} containerId - ID do container de eventos
 */
function alterarEventosPorPagina(quantidade, containerId = 'eventos-container') {
    const config = window.paginacaoConfig;
    config.eventosPorPagina = quantidade;
    config.paginaAtual = 1; // Volta para primeira página
    aplicarPaginacaoEventos(containerId);
}

/**
 * Cria os controles de paginação no rodapé
 * @param {string} containerId - ID do container de eventos
 */
function criarControlesPaginacao(containerId = 'eventos-container') {
    const container = document.getElementById(containerId);
    if (!container) return;

    // Verificar se já existe
    let controlesContainer = document.getElementById('controles-paginacao-container');
    if (controlesContainer) return;

    // Criar container dos controles
    controlesContainer = document.createElement('div');
    controlesContainer.id = 'controles-paginacao-container';
    controlesContainer.style.cssText = `
        display: flex;
        background: var(--caixas);
        flex-direction: column;
        gap: 1rem;
        align-items: center;
        padding: 1rem 0 1rem 0;
        grid-column: 1 / -1;
        width: 50%;
        justify-self: center;
        border-radius: 2rem;
        margin: 0 0 1rem 0;
    `;

    // Linha 1: Total de eventos e select
    const linha1 = document.createElement('div');
    linha1.style.cssText = `
        display: flex;
        gap: 2rem;
        align-items: center;
        flex-wrap: wrap;
        justify-content: center;
    `;

    // Total de eventos
    const totalSpan = document.createElement('span');
    totalSpan.id = 'total-eventos-texto';
    totalSpan.style.cssText = `
        color: var(--texto);
        font-weight: 600;
        font-size: 0.95rem;
    `;
    linha1.appendChild(totalSpan);

    // Select de eventos por página
    const selectContainer = document.createElement('div');
    selectContainer.style.cssText = 'display: flex; gap: 0.5rem; align-items: center;';
    
    const selectLabel = document.createElement('label');
    selectLabel.textContent = 'Eventos por página:';
    selectLabel.setAttribute('for', 'select-eventos-por-pagina');
    selectLabel.style.cssText = 'color: var(--texto); font-size: 0.9rem;';
    
    const select = document.createElement('select');
    select.id = 'select-eventos-por-pagina';
    select.className = 'botao';
    select.style.cssText = `
        padding: 0.4rem 0.8rem;
        font-size: 0.9rem;
        cursor: pointer;
        border: 1px solid var(--caixas);
        border-radius: 0.3rem;
        background: var(--botao);
        color: var(--texto);
    `;

    window.paginacaoConfig.opcoesEventosPorPagina.forEach(valor => {
        const option = document.createElement('option');
        option.value = valor;
        option.textContent = valor;
        if (valor === window.paginacaoConfig.eventosPorPagina) {
            option.selected = true;
        }
        select.appendChild(option);
    });

    select.onchange = function() {
        alterarEventosPorPagina(parseInt(this.value), containerId);
    };

    selectContainer.appendChild(selectLabel);
    selectContainer.appendChild(select);
    linha1.appendChild(selectContainer);

    controlesContainer.appendChild(linha1);

    // Linha 2: Navegação de páginas
    const navegacaoContainer = document.createElement('div');
    navegacaoContainer.id = 'navegacao-paginas';
    navegacaoContainer.style.cssText = `
        display: flex;
        gap: 0.5rem;
        align-items: center;
        flex-wrap: wrap;
        justify-content: center;
    `;

    controlesContainer.appendChild(navegacaoContainer);

    // Inserir após o container de eventos
    container.parentElement.insertBefore(controlesContainer, container.nextSibling);

    // Atualizar controles inicial
    atualizarControlesPaginacao(containerId);
}

/**
 * Atualiza os controles de paginação
 * @param {string} containerId - ID do container de eventos
 */
function atualizarControlesPaginacao(containerId = 'eventos-container') {
    const config = window.paginacaoConfig;
    
    // Atualizar total de eventos
    const totalSpan = document.getElementById('total-eventos-texto');
    if (totalSpan) {
        const eventoTexto = config.totalEventos === 1 ? 'evento encontrado' : 'eventos encontrados';
        totalSpan.textContent = `${config.totalEventos} ${eventoTexto}`;
    }

    // Atualizar select
    const select = document.getElementById('select-eventos-por-pagina');
    if (select) {
        select.value = config.eventosPorPagina;
    }

    // Atualizar navegação de páginas
    const navegacaoContainer = document.getElementById('navegacao-paginas');
    if (!navegacaoContainer) return;

    navegacaoContainer.innerHTML = '';

    // Se não há eventos ou apenas uma página, ocultar navegação
    if (config.totalPaginas <= 1) {
        navegacaoContainer.style.display = 'none';
        return;
    }

    navegacaoContainer.style.display = 'flex';

    // Botão Anterior
    const btnAnterior = criarBotaoPagina('‹', config.paginaAtual > 1, () => {
        irParaPagina(config.paginaAtual - 1, containerId);
    });
    btnAnterior.style.fontWeight = 'bold';
    btnAnterior.style.fontSize = '1.2rem';
    navegacaoContainer.appendChild(btnAnterior);

    // Gerar números das páginas
    const paginasParaMostrar = gerarNumerosPaginas(config.paginaAtual, config.totalPaginas);
    
    paginasParaMostrar.forEach((pagina, index) => {
        if (pagina === '...') {
            const reticencias = document.createElement('span');
            reticencias.textContent = '...';
            reticencias.style.cssText = `
                color: var(--texto);
                padding: 0.5rem;
                font-weight: 600;
            `;
            navegacaoContainer.appendChild(reticencias);
        } else {
            const btnPagina = criarBotaoPagina(
                pagina,
                true,
                () => irParaPagina(pagina, containerId),
                pagina === config.paginaAtual
            );
            navegacaoContainer.appendChild(btnPagina);
        }
    });

    // Botão Próximo
    const btnProximo = criarBotaoPagina('›', config.paginaAtual < config.totalPaginas, () => {
        irParaPagina(config.paginaAtual + 1, containerId);
    });
    btnProximo.style.fontWeight = 'bold';
    btnProximo.style.fontSize = '1.2rem';
    navegacaoContainer.appendChild(btnProximo);
}

/**
 * Cria um botão de página
 * @param {string|number} texto - Texto do botão
 * @param {boolean} ativo - Se o botão está ativo
 * @param {Function} onClick - Função ao clicar
 * @param {boolean} atual - Se é a página atual
 * @returns {HTMLButtonElement}
 */
function criarBotaoPagina(texto, ativo, onClick, atual = false) {
    const botao = document.createElement('button');
    botao.textContent = texto;
    botao.className = 'botao';
    
    const estiloBase = `
        min-width: 2.5rem;
        height: 2.5rem;
        padding: 0.5rem;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: ${ativo ? 'pointer' : 'not-allowed'};
        border-radius: 0.3rem;
        transition: all 0.3s ease;
        opacity: ${ativo ? '1' : '0.5'};
    `;
    
    if (atual) {
        botao.style.cssText = estiloBase + `
            background: var(--botao);
            color: var(--branco);
            border: 2px solid var(--botao);
        `;
    } else {
        botao.style.cssText = estiloBase + `
            background: var(--caixas);
            color: var(--texto);
            border: 1px solid var(--botao);
        `;
        
        if (ativo) {
            botao.onmouseenter = function() {
                this.style.background = 'var(--botao)';
                this.style.color = 'var(--branco)';
            };
            botao.onmouseleave = function() {
                this.style.background = 'var(--caixas)';
                this.style.color = 'var(--texto)';
            };
        }
    }
    
    if (ativo) {
        botao.onclick = onClick;
    } else {
        botao.disabled = true;
    }
    
    return botao;
}

/**
 * Gera array de números de páginas para exibir
 * Lógica: 1, 2, 3, ... última (se mais de 5 páginas)
 * @param {number} paginaAtual - Página atual
 * @param {number} totalPaginas - Total de páginas
 * @returns {Array} Array com números e '...'
 */
function gerarNumerosPaginas(paginaAtual, totalPaginas) {
    const paginas = [];
    
    if (totalPaginas <= 5) {
        // Se tem 5 ou menos páginas, mostra todas
        for (let i = 1; i <= totalPaginas; i++) {
            paginas.push(i);
        }
    } else {
        // Sempre mostra primeira página
        paginas.push(1);
        
        if (paginaAtual <= 3) {
            // Se está nas primeiras páginas: 1, 2, 3, ..., última
            paginas.push(2);
            paginas.push(3);
            paginas.push('...');
            paginas.push(totalPaginas);
        } else if (paginaAtual >= totalPaginas - 2) {
            // Se está nas últimas páginas: 1, ..., antepenúltima, penúltima, última
            paginas.push('...');
            paginas.push(totalPaginas - 2);
            paginas.push(totalPaginas - 1);
            paginas.push(totalPaginas);
        } else {
            // Se está no meio: 1, ..., atual-1, atual, atual+1, ..., última
            paginas.push('...');
            paginas.push(paginaAtual - 1);
            paginas.push(paginaAtual);
            paginas.push(paginaAtual + 1);
            paginas.push('...');
            paginas.push(totalPaginas);
        }
    }
    
    return paginas;
}

/**
 * Adiciona checkbox no filtro lateral para mostrar eventos finalizados
 * @param {string} containerId - ID do container de eventos
 * @param {boolean} ocultarFiltroFinalizados - Se true, não adiciona o filtro (para páginas que sempre mostram finalizados)
 */
function adicionarFiltroEventosFinalizados(containerId = 'eventos-container', ocultarFiltroFinalizados = false) {
    // Se for para ocultar o filtro, não adiciona nada e sempre mostra finalizados
    if (ocultarFiltroFinalizados) {
        window.paginacaoConfig.mostrarFinalizados = true;
        return;
    }

    // Aguardar o filtro ser criado
    const intervalo = setInterval(() => {
        const filtroContainer = document.getElementById('filtro-container');
        if (!filtroContainer) return;

        clearInterval(intervalo);

        // Verificar se já foi adicionado
        if (document.getElementById('filtro-eventos-finalizados')) return;

        // Criar seção de eventos finalizados
        const divisor = document.createElement('div');
        divisor.className = 'divisor';
        divisor.innerHTML = '<div class="divisor-linha"></div>';

        const fieldset = document.createElement('fieldset');
        fieldset.className = 'filtro-grupo';
        fieldset.innerHTML = `
            <legend class="grupo-titulo">
                Status do Evento
                <span class="titulo-icone"></span>
            </legend>
            <div class="lista-checkbox">
                <label class="item-checkbox">
                    <input type="checkbox" id="filtro-eventos-finalizados" name="eventos_finalizados">
                    <span class="checkbox-personalizado"></span>
                    <span>Mostrar eventos finalizados</span>
                </label>
            </div>
        `;

        // Adicionar ao filtro (antes dos botões de ação)
        filtroContainer.appendChild(divisor);
        filtroContainer.appendChild(fieldset);

        // Adicionar listener
        const checkbox = document.getElementById('filtro-eventos-finalizados');
        if (checkbox) {
            checkbox.addEventListener('change', function() {
                window.paginacaoConfig.mostrarFinalizados = this.checked;
                
                // Resetar para primeira página ao mudar filtro
                window.paginacaoConfig.paginaAtual = 1;
                
                // Alterar o título
                atualizarTituloSecao(this.checked);
                
                // Reaplicar paginação
                aplicarPaginacaoEventos(containerId);

                // Atualizar ícone do grupo
                const tituloIcone = fieldset.querySelector('.titulo-icone');
                if (tituloIcone) {
                    if (this.checked) {
                        tituloIcone.classList.add('ativo');
                    } else {
                        tituloIcone.classList.remove('ativo');
                    }
                }
            });
        }
    }, 100);

    // Timeout de segurança (10 segundos)
    setTimeout(() => clearInterval(intervalo), 10000);
}

/**
 * Atualiza o título da seção de eventos
 * @param {boolean} mostrarFinalizados - Se está mostrando eventos finalizados
 */
function atualizarTituloSecao(mostrarFinalizados) {
    // Encontrar o elemento de título
    const sectionTitle = document.querySelector('.div-section-title .section-title');
    if (!sectionTitle) return;

    if (mostrarFinalizados) {
        sectionTitle.textContent = 'Todos Eventos';
    } else {
        sectionTitle.textContent = 'Eventos acontecendo';
    }
}

/**
 * Reseta a paginação (útil após filtros ou buscas)
 * @param {string} containerId - ID do container de eventos
 */
function resetarPaginacao(containerId = 'eventos-container') {
    window.paginacaoConfig.paginaAtual = 1;
    aplicarPaginacaoEventos(containerId);
}

/**
 * Inicializa paginação para MeusEventosOrganizador (com dois containers)
 * Combina eventos de ambos containers para uma única paginação
 */
function inicializarPaginacaoMeusEventosOrganizador() {
    const container1 = document.getElementById('eventos-container');
    const container2 = document.getElementById('colaboracao-container');
    
    if (!container1 && !container2) {
        console.warn('Nenhum container de eventos encontrado');
        return;
    }

    // Sempre mostrar eventos finalizados nesta página
    window.paginacaoConfig.mostrarFinalizados = true;

    // Aplicar paginação considerando ambos containers
    aplicarPaginacaoMeusEventosOrganizador();

    // Adicionar controles de paginação no rodapé (após o segundo container)
    criarControlesPaginacaoMeusEventosOrganizador();
}

/**
 * Aplica paginação considerando dois containers
 */
function aplicarPaginacaoMeusEventosOrganizador() {
    const container1 = document.getElementById('eventos-container');
    const container2 = document.getElementById('colaboracao-container');
    const config = window.paginacaoConfig;

    // Coletar todos os eventos de ambos containers
    let todosEventos = [];
    
    if (container1) {
        const eventos1 = Array.from(container1.querySelectorAll('.CaixaDoEvento'));
        todosEventos = todosEventos.concat(eventos1);
    }
    
    if (container2) {
        const eventos2 = Array.from(container2.querySelectorAll('.CaixaDoEvento'));
        todosEventos = todosEventos.concat(eventos2);
    }

    // Filtrar eventos que não estão ocultos por filtro ou busca
    const eventosDisponiveis = todosEventos.filter(evento => {
        const hiddenByFilter = evento.dataset.hiddenByFilter === 'true';
        const hiddenBySearch = evento.dataset.hiddenBySearch === 'true';
        return !hiddenByFilter && !hiddenBySearch;
    });

    // Calcular total de páginas
    config.totalEventos = eventosDisponiveis.length;
    config.totalPaginas = Math.ceil(config.totalEventos / config.eventosPorPagina);
    
    // Garantir que página atual é válida
    if (config.paginaAtual > config.totalPaginas) {
        config.paginaAtual = Math.max(1, config.totalPaginas);
    }

    // Calcular índices da página atual
    const indiceInicio = (config.paginaAtual - 1) * config.eventosPorPagina;
    const indiceFim = indiceInicio + config.eventosPorPagina;

    // Ocultar todos os eventos primeiro
    todosEventos.forEach(evento => {
        evento.dataset.hiddenByPagination = 'true';
        atualizarVisibilidadeEvento(evento);
    });

    // Mostrar apenas eventos da página atual
    const eventosParaMostrar = eventosDisponiveis.slice(indiceInicio, indiceFim);
    eventosParaMostrar.forEach(evento => {
        delete evento.dataset.hiddenByPagination;
        atualizarVisibilidadeEvento(evento);
    });

    // Atualizar controles de paginação
    atualizarControlesPaginacaoMeusEventosOrganizador();
}

/**
 * Cria controles de paginação após o segundo container
 */
function criarControlesPaginacaoMeusEventosOrganizador() {
    const container2 = document.getElementById('colaboracao-container');
    if (!container2) return;

    // Verificar se já existe
    let controlesContainer = document.getElementById('controles-paginacao-container');
    if (controlesContainer) return;

    // Criar container dos controles
    controlesContainer = document.createElement('div');
    controlesContainer.id = 'controles-paginacao-container';
    controlesContainer.style.cssText = `
        display: flex;
        background: var(--caixas);
        flex-direction: column;
        align-items: center;
        padding: 1rem 0 1rem 0;
        grid-column: 1 / -1;
        width: 50%;
        justify-self: center;
        border-radius: 2rem;
        margin: 1rem 0 1rem 0;
    `;

    // Linha 1: Total de eventos e select
    const linha1 = document.createElement('div');
    linha1.style.cssText = `
        display: flex;
        gap: 2rem;
        align-items: center;
        flex-wrap: wrap;
        justify-content: center;
    `;

    // Contador de eventos
    const contadorEventos = document.createElement('span');
    contadorEventos.id = 'contador-eventos';
    contadorEventos.style.cssText = `
        color: var(--branco);
        font-weight: 600;
        font-size: 0.95rem;
    `;

    // Select de eventos por página
    const selectWrapper = document.createElement('div');
    selectWrapper.style.cssText = `
        display: flex;
        align-items: center;
        gap: 0.5rem;
    `;
    
    const selectLabel = document.createElement('label');
    selectLabel.textContent = 'Eventos por página:';
    selectLabel.style.cssText = `
        color: var(--branco);
        font-size: 0.9rem;
    `;
    
    const select = document.createElement('select');
    select.id = 'select-eventos-por-pagina';
    select.className = 'botao';
    select.style.cssText = `
        padding: 0.4rem 0.8rem;
        font-size: 0.9rem;
        cursor: pointer;
        border: 1px solid var(--caixas);
        border-radius: 0.3rem;
        background: var(--botao);
        color: var(--texto);
    `;

    window.paginacaoConfig.opcoesEventosPorPagina.forEach(opcao => {
        const option = document.createElement('option');
        option.value = opcao;
        option.textContent = opcao;
        if (opcao === window.paginacaoConfig.eventosPorPagina) {
            option.selected = true;
        }
        select.appendChild(option);
    });

    select.addEventListener('change', function() {
        alterarEventosPorPaginaMeusEventosOrganizador(parseInt(this.value));
    });

    selectWrapper.appendChild(selectLabel);
    selectWrapper.appendChild(select);
    linha1.appendChild(contadorEventos);
    linha1.appendChild(selectWrapper);

    // Linha 2: Navegação de páginas
    const linha2 = document.createElement('div');
    linha2.id = 'navegacao-paginas';
    linha2.style.cssText = `
        display: flex;
        gap: 0.5rem;
        align-items: center;
        flex-wrap: wrap;
        justify-content: center;
    `;

    controlesContainer.appendChild(linha1);
    controlesContainer.appendChild(linha2);

    // Inserir após o segundo container
    container2.parentElement.appendChild(controlesContainer);

    // Atualizar controles inicial
    atualizarControlesPaginacaoMeusEventosOrganizador();
}

/**
 * Atualiza os controles de paginação
 */
function atualizarControlesPaginacaoMeusEventosOrganizador() {
    const config = window.paginacaoConfig;
    
    // Atualizar contador
    const contador = document.getElementById('contador-eventos');
    if (contador) {
        const plural = config.totalEventos !== 1 ? 's' : '';
        contador.textContent = `${config.totalEventos} evento${plural} encontrado${plural}`;
    }

    // Atualizar navegação
    const navegacao = document.getElementById('navegacao-paginas');
    if (!navegacao) return;

    navegacao.innerHTML = '';

    if (config.totalPaginas <= 1) return;

    // Botão Anterior
    const btnAnterior = criarBotaoPagina('‹ Anterior', config.paginaAtual > 1, () => {
        irParaPaginaMeusEventosOrganizador(config.paginaAtual - 1);
    });
    navegacao.appendChild(btnAnterior);

    // Números de páginas
    const numerosPaginas = gerarNumerosPaginas(config.paginaAtual, config.totalPaginas);
    numerosPaginas.forEach(num => {
        if (num === '...') {
            const span = document.createElement('span');
            span.textContent = '...';
            span.style.cssText = `
                color: var(--branco);
                padding: 0 0.25rem;
            `;
            navegacao.appendChild(span);
        } else {
            const btnPagina = criarBotaoPagina(num, true, () => {
                irParaPaginaMeusEventosOrganizador(num);
            }, num === config.paginaAtual);
            navegacao.appendChild(btnPagina);
        }
    });

    // Botão Próximo
    const btnProximo = criarBotaoPagina('Próximo ›', config.paginaAtual < config.totalPaginas, () => {
        irParaPaginaMeusEventosOrganizador(config.paginaAtual + 1);
    });
    navegacao.appendChild(btnProximo);
}

/**
 * Vai para uma página específica (MeusEventosOrganizador)
 */
function irParaPaginaMeusEventosOrganizador(numeroPagina) {
    const config = window.paginacaoConfig;
    config.paginaAtual = Math.max(1, Math.min(numeroPagina, config.totalPaginas));
    aplicarPaginacaoMeusEventosOrganizador();
}

/**
 * Altera quantidade de eventos por página (MeusEventosOrganizador)
 */
function alterarEventosPorPaginaMeusEventosOrganizador(quantidade) {
    const config = window.paginacaoConfig;
    config.eventosPorPagina = quantidade;
    config.paginaAtual = 1;
    aplicarPaginacaoMeusEventosOrganizador();
}

// Exportar funções para uso global
window.inicializarPaginacaoEventos = inicializarPaginacaoEventos;
window.aplicarPaginacaoEventos = aplicarPaginacaoEventos;
window.irParaPagina = irParaPagina;
window.alterarEventosPorPagina = alterarEventosPorPagina;
window.resetarPaginacao = resetarPaginacao;
window.eventoEstaFinalizado = eventoEstaFinalizado;
window.atualizarVisibilidadeEvento = atualizarVisibilidadeEvento;
window.inicializarPaginacaoMeusEventosOrganizador = inicializarPaginacaoMeusEventosOrganizador;
window.aplicarPaginacaoMeusEventosOrganizador = aplicarPaginacaoMeusEventosOrganizador;
window.irParaPaginaMeusEventosOrganizador = irParaPaginaMeusEventosOrganizador;
