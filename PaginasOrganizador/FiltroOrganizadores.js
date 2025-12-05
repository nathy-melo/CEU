// FiltroOrganizadores.js
// Sistema de filtro/ordenação para tabela de organizadores

(function () {
    'use strict';

    // Estado global do filtro
    window.filtroOrganizadoresConfig = {
        ordemAtual: 'nome-az', // Ordem padrão
        tabelaId: null,
        dadosOriginais: [], // Cache dos dados originais
        botaoHandler: null,
        documentClickHandler: null
    };

    /**
     * Inicializa o sistema de filtro para organizadores
     * @param {string} tabelaId - ID da tabela de organizadores
     */
    window.inicializarFiltroOrganizadores = function (tabelaId) {
        const config = window.filtroOrganizadoresConfig;
        config.tabelaId = tabelaId;

        const botaoFiltrar = document.getElementById('btn-filtrar-organizadores');
        
        if (!botaoFiltrar) {
            return;
        }

        // Remover painel antigo se existir
        const painelAntigo = document.getElementById('filtro-container-organizadores');
        if (painelAntigo) {
            painelAntigo.remove();
        }

        // Salvar dados originais para restauração
        salvarDadosOriginais(tabelaId);

        // Criar painel de filtro lateral
        const painelFiltro = criarPainelFiltro();
        document.body.appendChild(painelFiltro);

        // Garantir que apenas um listener esteja associado ao botão
        if (config.botaoHandler) {
            botaoFiltrar.removeEventListener('click', config.botaoHandler);
        }

        const botaoHandler = function (e) {
            e.stopPropagation();
            e.preventDefault();
            toggleFiltro();
        };
        
        botaoFiltrar.addEventListener('click', botaoHandler);
        config.botaoHandler = botaoHandler;

        // Fechar painel ao clicar fora (com delay para evitar conflito)
        if (!config.documentClickHandler) {
            config.documentClickHandler = function (e) {
                const painel = document.getElementById('filtro-container-organizadores');
                const botao = document.getElementById('btn-filtrar-organizadores');
                if (painel && painel.classList.contains('ativo') && !painel.contains(e.target) && (!botao || !botao.contains(e.target))) {
                    toggleFiltro();
                }
            };

            document.addEventListener('click', config.documentClickHandler);
        }

        // Impede que cliques dentro do painel o fechem
        painelFiltro.addEventListener('click', function (e) {
            e.stopPropagation();
        });

        // Adicionar listeners aos itens de filtro
        wireFilterInputs();
    };

    /**
     * Função global para aplicar ordenação inicial (chamada de fora)
     */
    window.aplicarOrdenacaoInicialOrg = function() {
        const config = window.filtroOrganizadoresConfig;
        if (config && config.tabelaId && config.dadosOriginais.length > 0) {
            aplicarOrdenacao('nome-az');
        }
    };

    /**
     * Salva os dados originais da tabela para restauração
     * @param {string} tabelaId - ID da tabela
     */
    function salvarDadosOriginais(tabelaId) {
        const tabela = document.getElementById(tabelaId);
        if (!tabela) return;

        const tbody = tabela.querySelector('tbody');
        if (!tbody) return;

        const linhas = tbody.querySelectorAll('tr');

        const config = window.filtroOrganizadoresConfig;
        config.dadosOriginais = Array.from(linhas).map((tr) => {
            const colunaDados = tr.querySelector('td:nth-child(2)');
            const colunaStatus = tr.querySelector('td:nth-child(4)');
            
            const dados = {
                elemento: tr.cloneNode(true),
                nome: colunaDados?.textContent.match(/Nome:\s*(.*)/)?.[1]?.split('\n')[0]?.trim() || '',
                cpf: tr.getAttribute('data-cpf') || '',
                dataInscricao: colunaDados?.textContent.match(/Data de Inscrição:\s*(.*)/)?.[1]?.trim() || '',
                tipo: tr.getAttribute('data-tipo') || '',
                presenca: colunaStatus?.textContent.trim() || '',
                certificado: colunaStatus?.textContent.trim() || ''
            };
            
            return dados;
        });
    }

    /**
     * Cria o painel lateral de filtro (estilo do site)
     * @returns {HTMLElement} Painel de filtro
     */
    function criarPainelFiltro() {
        const painel = document.createElement('form');
        painel.id = 'filtro-container-organizadores';
        painel.className = 'filtro-container';

        painel.innerHTML = `
            <h1 class="filtro-titulo">Filtrar por</h1>

            <fieldset class="filtro-grupo">
                <legend class="grupo-titulo">
                    Ordenar
                    <span class="titulo-icone ativo"></span>
                </legend>
                <div class="lista-checkbox">
                    <label class="item-checkbox">
                        <input type="radio" name="ordenacao" value="nome-az" checked>
                        <span class="checkbox-personalizado"></span>
                        <span>Nome (A → Z)</span>
                    </label>
                    <label class="item-checkbox">
                        <input type="radio" name="ordenacao" value="nome-za">
                        <span class="checkbox-personalizado"></span>
                        <span>Nome (Z → A)</span>
                    </label>
                    <label class="item-checkbox">
                        <input type="radio" name="ordenacao" value="data-antigo">
                        <span class="checkbox-personalizado"></span>
                        <span>Inscrição (Mais antigos)</span>
                    </label>
                    <label class="item-checkbox">
                        <input type="radio" name="ordenacao" value="data-recente">
                        <span class="checkbox-personalizado"></span>
                        <span>Inscrição (Mais recentes)</span>
                    </label>
                </div>
            </fieldset>

            <div class="divisor">
                <div class="divisor-linha"></div>
            </div>

            <fieldset class="filtro-grupo">
                <legend class="grupo-titulo">
                    Filtrar
                    <span class="titulo-icone"></span>
                </legend>
                <div class="lista-checkbox">
                    <label class="item-checkbox">
                        <input type="checkbox" name="filtro" value="tipo-organizador">
                        <span class="checkbox-personalizado"></span>
                        <span>Apenas Organizador</span>
                    </label>
                    <label class="item-checkbox">
                        <input type="checkbox" name="filtro" value="tipo-colaborador">
                        <span class="checkbox-personalizado"></span>
                        <span>Apenas Colaboradores</span>
                    </label>
                    <label class="item-checkbox">
                        <input type="checkbox" name="filtro" value="presenca-confirmada">
                        <span class="checkbox-personalizado"></span>
                        <span>Presença confirmada</span>
                    </label>
                    <label class="item-checkbox">
                        <input type="checkbox" name="filtro" value="presenca-nao-confirmada">
                        <span class="checkbox-personalizado"></span>
                        <span>Presença não confirmada</span>
                    </label>
                    <label class="item-checkbox">
                        <input type="checkbox" name="filtro" value="certificado-emitido">
                        <span class="checkbox-personalizado"></span>
                        <span>Certificado emitido</span>
                    </label>
                    <label class="item-checkbox">
                        <input type="checkbox" name="filtro" value="certificado-nao-emitido">
                        <span class="checkbox-personalizado"></span>
                        <span>Certificado não emitido</span>
                    </label>
                </div>
            </fieldset>
        `;

        return painel;
    }

    /**
     * Toggle do painel de filtro
     */
    function toggleFiltro() {
        const painel = document.getElementById('filtro-container-organizadores');
        if (!painel) {
            return;
        }

        const isAtivo = painel.classList.contains('ativo');
        painel.classList.toggle('ativo', !isAtivo);

        // Bloquear/desbloquear scroll
        if (!isAtivo) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }

    /**
     * Conecta eventos aos inputs do filtro
     */
    function wireFilterInputs() {
        const painel = document.getElementById('filtro-container-organizadores');
        if (!painel) {
            return;
        }

        const inputs = painel.querySelectorAll('input[type="checkbox"], input[type="radio"]');
        let ultimoRadioSelecionado = null;

        inputs.forEach(input => {
            if (input.type === 'radio') {
                input.addEventListener('click', function (e) {
                    // NÃO permite desmarcar todos - sempre mantém um selecionado
                    ultimoRadioSelecionado = e.target;
                    atualizarIconeStatus(e.target.closest('.filtro-grupo'));
                });

                input.addEventListener('change', function (e) {
                    if (e.target.checked) {
                        aplicarFiltros();
                    }
                });
            } else {
                input.addEventListener('change', function (e) {
                    atualizarIconeStatus(e.target.closest('.filtro-grupo'));
                    aplicarFiltros();
                });
            }
        });

        // Listener para clicar no ícone e limpar seção
        const grupos = painel.querySelectorAll('.filtro-grupo');
        grupos.forEach(grupo => {
            const tituloIcone = grupo.querySelector('.titulo-icone');
            if (tituloIcone) {
                tituloIcone.addEventListener('click', function (e) {
                    e.stopPropagation();
                    if (tituloIcone.classList.contains('ativo')) {
                        const inputs = grupo.querySelectorAll('input');
                        inputs.forEach(input => {
                            input.checked = false;
                        });
                        atualizarIconeStatus(grupo);
                        aplicarFiltros();
                    }
                });
            }
        });

        // Atualizar ícones iniciais
        grupos.forEach(grupo => atualizarIconeStatus(grupo));
    }

    /**
     * Atualiza o ícone de status do grupo
     * @param {HTMLElement} grupo - Grupo do filtro
     */
    function atualizarIconeStatus(grupo) {
        if (!grupo) return;

        const tituloIcone = grupo.querySelector('.titulo-icone');
        const inputs = grupo.querySelectorAll('input');

        let algumSelecionado = false;
        inputs.forEach(input => {
            if (input.checked) algumSelecionado = true;
        });

        if (tituloIcone) {
            tituloIcone.classList.toggle('ativo', algumSelecionado);
        }
    }

    /**
     * Aplica os filtros selecionados
     */
    function aplicarFiltros() {
        const painel = document.getElementById('filtro-container-organizadores');
        if (!painel) {
            return;
        }

        // Obter ordenação
        const ordenacaoRadio = painel.querySelector('input[name="ordenacao"]:checked');
        const tipoOrdem = ordenacaoRadio ? ordenacaoRadio.value : 'nome-az';

        // Obter filtros
        const filtrosCheckbox = Array.from(painel.querySelectorAll('input[name="filtro"]:checked'))
            .map(input => input.value);

        // Aplicar ordenação
        aplicarOrdenacao(tipoOrdem, filtrosCheckbox);
    }

    /**
     * Aplica ordenação à tabela
     * @param {string} tipoOrdem - Tipo de ordenação
     * @param {Array} filtros - Filtros ativos
     */
    function aplicarOrdenacao(tipoOrdem, filtros = []) {
        const config = window.filtroOrganizadoresConfig;
        const tabela = document.getElementById(config.tabelaId);
        if (!tabela) {
            return;
        }

        const tbody = tabela.querySelector('tbody');
        if (!tbody) {
            return;
        }

        config.ordemAtual = tipoOrdem;

        // Copia dados originais
        let dados = config.dadosOriginais.map(item => ({
            ...item,
            elemento: item.elemento.cloneNode(true)
        }));

        // Aplicar filtros primeiro
        if (filtros.length > 0) {
            dados = dados.filter(item => {
                let passar = true;

                // Filtros de tipo
                if (filtros.includes('tipo-organizador')) {
                    passar = passar && item.tipo === 'Organizador';
                }

                if (filtros.includes('tipo-colaborador')) {
                    passar = passar && item.tipo === 'Colaborador';
                }

                // Filtros de presença
                if (filtros.includes('presenca-confirmada')) {
                    const textoPresenca = item.presenca.toLowerCase();
                    const inscricaoConfirmada = textoPresenca.includes('inscrição:confirmada');
                    const presencaConfirmada = textoPresenca.includes('presença:confirmada');
                    const temPresenca = inscricaoConfirmada && presencaConfirmada;
                    passar = passar && temPresenca;
                }

                if (filtros.includes('presenca-nao-confirmada')) {
                    const textoPresenca = item.presenca.toLowerCase();
                    const inscricaoConfirmada = textoPresenca.includes('inscrição:confirmada');
                    const presencaNaoConfirmada = textoPresenca.includes('presença:não confirmada');
                    const temPresenca = inscricaoConfirmada && presencaNaoConfirmada;
                    passar = passar && temPresenca;
                }

                // Filtros de certificado
                if (filtros.includes('certificado-emitido')) {
                    const textoCertificado = item.certificado.toLowerCase();
                    const inscricaoConfirmada = textoCertificado.includes('inscrição:confirmada');
                    const presencaConfirmada = textoCertificado.includes('presença:confirmada');
                    const certificadoEmitido = textoCertificado.includes('certificado:enviado');
                    const temCertificado = inscricaoConfirmada && presencaConfirmada && certificadoEmitido;
                    passar = passar && temCertificado;
                }

                if (filtros.includes('certificado-nao-emitido')) {
                    const textoCertificado = item.certificado.toLowerCase();
                    const inscricaoConfirmada = textoCertificado.includes('inscrição:confirmada');
                    const presencaConfirmada = textoCertificado.includes('presença:confirmada');
                    const certificadoNaoEmitido = textoCertificado.includes('certificado:não enviado');
                    const temCertificado = inscricaoConfirmada && presencaConfirmada && certificadoNaoEmitido;
                    passar = passar && temCertificado;
                }

                return passar;
            });
        }

        // Aplica ordenação
        switch (tipoOrdem) {
            case 'nome-az':
                dados.sort((a, b) => a.nome.localeCompare(b.nome, 'pt-BR'));
                break;

            case 'nome-za':
                dados.sort((a, b) => b.nome.localeCompare(a.nome, 'pt-BR'));
                break;

            case 'data-antigo':
                dados.sort((a, b) => {
                    const dataA = parseDataBR(a.dataInscricao);
                    const dataB = parseDataBR(b.dataInscricao);
                    return dataA - dataB;
                });
                break;

            case 'data-recente':
                dados.sort((a, b) => {
                    const dataA = parseDataBR(a.dataInscricao);
                    const dataB = parseDataBR(b.dataInscricao);
                    return dataB - dataA;
                });
                break;
        }

        // Limpa tbody
        tbody.innerHTML = '';

        // Adiciona linhas ordenadas
        dados.forEach(item => {
            tbody.appendChild(item.elemento);
        });

        // Reaplica paginação se existir
        if (typeof window.aplicarPaginacaoTabela === 'function') {
            window.aplicarPaginacaoTabela(config.tabelaId);
        }
    }

    /**
     * Converte string de data BR para objeto Date
     * @param {string} dataStr - Data no formato DD/MM/YYYY HH:MM:SS
     * @returns {Date} Objeto Date
     */
    function parseDataBR(dataStr) {
        if (!dataStr) return new Date(0);

        const partes = dataStr.match(/(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}):(\d{2}):(\d{2})/);
        if (!partes) return new Date(0);

        const [, dia, mes, ano, hora, min, seg] = partes;
        return new Date(ano, mes - 1, dia, hora, min, seg);
    }
})();
