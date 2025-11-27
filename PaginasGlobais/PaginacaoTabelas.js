/**
 * Sistema de Paginação de Tabelas
 * 
 * Controla a quantidade de linhas exibidas em tabelas:
 * - Inicia com 20 linhas
 * - Permite selecionar quantidade via select (20, 30, 40, 50, 60, 80, 100)
 * - Integrado com contador de participantes
 */

// Configurações globais de paginação por tabela
window.paginacaoTabelasConfig = {};

/**
 * Inicializa o sistema de paginação para uma tabela
 * @param {string} tabelaId - ID da tabela
 * @param {Object} opcoes - Opções de configuração
 */
function inicializarPaginacaoTabela(tabelaId, opcoes = {}) {
    const tabela = document.getElementById(tabelaId);
    if (!tabela) {
        console.warn('Tabela não encontrada:', tabelaId);
        return;
    }

    // Configuração padrão
    const config = {
        linhasPorPagina: opcoes.linhasPorPagina || 20,
        maximoLinhas: opcoes.maximoLinhas || 100,
        linhasCarregadas: 20,
        todasLinhasCarregadas: false,
        selectId: opcoes.selectId || 'select-linhas-por-pagina'
    };

    // Armazenar configuração
    window.paginacaoTabelasConfig[tabelaId] = config;

    // Configurar listener do select
    configurarSelectLinhasPorPagina(tabelaId, config.selectId);

    // Aplicar paginação inicial
    aplicarPaginacaoTabela(tabelaId);
}

/**
 * Aplica a paginação às linhas da tabela
 * @param {string} tabelaId - ID da tabela
 */
function aplicarPaginacaoTabela(tabelaId) {
    const tabela = document.getElementById(tabelaId);
    if (!tabela) return;

    const config = window.paginacaoTabelasConfig[tabelaId];
    if (!config) return;

    const tbody = tabela.querySelector('tbody');
    if (!tbody) return;

    const todasLinhas = Array.from(tbody.querySelectorAll('tr'));
    
    // Filtrar apenas linhas válidas (que não sejam mensagens de carregamento/vazio)
    const linhasValidas = todasLinhas.filter(linha => {
        const temCPF = linha.hasAttribute('data-cpf');
        const ehMensagem = linha.querySelector('td[colspan]');
        return temCPF && !ehMensagem;
    });
    
    // Calcular total de páginas
    const totalLinhasValidas = linhasValidas.length;
    config.totalPaginas = Math.ceil(totalLinhasValidas / config.linhasPorPagina);
    config.paginaAtual = Math.max(1, Math.min(config.paginaAtual || 1, config.totalPaginas));
    
    // Ocultar/mostrar linhas baseado na configuração
    let indiceVisivel = 0;
    let indicePagina = 0;
    
    todasLinhas.forEach((linha) => {
        // Pular linhas que são mensagens
        if (linha.querySelector('td[colspan]')) {
            return;
        }
        
        // Se a linha está em uma página que deve ser exibida, mostrar
        const paginaLinhaAtual = Math.floor(indicePagina / config.linhasPorPagina) + 1;
        
        if (paginaLinhaAtual === config.paginaAtual) {
            linha.style.display = '';
            delete linha.dataset.hiddenByPagination;
        } else {
            linha.style.display = 'none';
            linha.dataset.hiddenByPagination = 'true';
        }
        
        indicePagina++;
    });
    
    // Atualizar navegação de páginas
    atualizarNavegacaoPaginas(tabelaId);
}

/**
 * Atualiza a navegação de páginas da tabela
 * @param {string} tabelaId - ID da tabela
 */
function atualizarNavegacaoPaginas(tabelaId) {
    const config = window.paginacaoTabelasConfig[tabelaId];
    if (!config) return;
    
    const navegacaoContainer = document.getElementById('navegacao-paginas-tabela');
    if (!navegacaoContainer) return;
    
    navegacaoContainer.innerHTML = '';
    
    // Se há apenas uma página, não mostrar navegação
    if (config.totalPaginas <= 1) {
        return;
    }
    
    // Botão Anterior
    const btnAnterior = criarBotaoPagina('‹', config.paginaAtual > 1, () => {
        irParaPaginaTabela(tabelaId, config.paginaAtual - 1);
    });
    btnAnterior.style.fontWeight = 'bold';
    btnAnterior.style.fontSize = '1.2rem';
    navegacaoContainer.appendChild(btnAnterior);
    
    // Gerar números das páginas
    const paginasParaMostrar = gerarNumerosPaginasTabela(config.paginaAtual, config.totalPaginas);
    
    paginasParaMostrar.forEach((pagina) => {
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
                () => irParaPaginaTabela(tabelaId, pagina),
                pagina === config.paginaAtual
            );
            navegacaoContainer.appendChild(btnPagina);
        }
    });
    
    // Botão Próximo
    const btnProximo = criarBotaoPagina('›', config.paginaAtual < config.totalPaginas, () => {
        irParaPaginaTabela(tabelaId, config.paginaAtual + 1);
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
function gerarNumerosPaginasTabela(paginaAtual, totalPaginas) {
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
 * Vai para uma página específica da tabela
 * @param {string} tabelaId - ID da tabela
 * @param {number} numeroPagina - Número da página
 */
function irParaPaginaTabela(tabelaId, numeroPagina) {
    const config = window.paginacaoTabelasConfig[tabelaId];
    if (!config) return;
    
    config.paginaAtual = Math.max(1, Math.min(numeroPagina, config.totalPaginas));
    aplicarPaginacaoTabela(tabelaId);
}

/**
 * Configura o listener do select de linhas por página
 * @param {string} tabelaId - ID da tabela
 * @param {string} selectId - ID do select
 */
function configurarSelectLinhasPorPagina(tabelaId, selectId) {
    const select = document.getElementById(selectId);
    if (!select) return;
    
    // Remover listener anterior se existir
    if (select.dataset.bound === 'true') return;
    select.dataset.bound = 'true';
    
    select.addEventListener('change', function() {
        alterarLinhasPorPagina(tabelaId, parseInt(this.value));
    });
}

/**
 * Altera a quantidade de linhas por página
 * @param {string} tabelaId - ID da tabela
 * @param {number} novaQuantidade - Nova quantidade de linhas
 */
function alterarLinhasPorPagina(tabelaId, novaQuantidade) {
    const config = window.paginacaoTabelasConfig[tabelaId];
    if (!config) return;

    config.linhasPorPagina = novaQuantidade;
    config.linhasCarregadas = novaQuantidade;
    
    // Reaplicar paginação
    aplicarPaginacaoTabela(tabelaId);
}

// Funções de botão "Ver mais" removidas - agora usa select

/**
 * Reseta a paginação da tabela (útil após filtros ou atualizações)
 * @param {string} tabelaId - ID da tabela
 */
function resetarPaginacaoTabela(tabelaId) {
    const config = window.paginacaoTabelasConfig[tabelaId];
    if (!config) return;

    config.linhasCarregadas = config.linhasPorPagina;
    config.todasLinhasCarregadas = false;
    aplicarPaginacaoTabela(tabelaId);
}

/**
 * Atualiza a paginação da tabela após mudanças no conteúdo
 * @param {string} tabelaId - ID da tabela
 */
function atualizarPaginacaoTabela(tabelaId) {
    const config = window.paginacaoTabelasConfig[tabelaId];
    if (!config) return;

    aplicarPaginacaoTabela(tabelaId);
}

// Exportar funções para uso global
window.inicializarPaginacaoTabela = inicializarPaginacaoTabela;
window.aplicarPaginacaoTabela = aplicarPaginacaoTabela;
window.alterarLinhasPorPagina = alterarLinhasPorPagina;
window.configurarSelectLinhasPorPagina = configurarSelectLinhasPorPagina;
window.resetarPaginacaoTabela = resetarPaginacaoTabela;
window.atualizarPaginacaoTabela = atualizarPaginacaoTabela;
window.irParaPaginaTabela = irParaPaginaTabela;
