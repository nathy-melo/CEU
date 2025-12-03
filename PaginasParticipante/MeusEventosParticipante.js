// Função para formatar strings (igual ao PHP)
function formatar(txt) {
    if (!txt) return '';
    const map = {
        'Á':'A','À':'A','Â':'A','Ã':'A','Ä':'A','á':'a','à':'a','â':'a','ã':'a','ä':'a',
        'É':'E','È':'E','Ê':'E','Ë':'E','é':'e','è':'e','ê':'e','ë':'e',
        'Í':'I','Ì':'I','Î':'I','Ï':'I','í':'i','ì':'i','î':'i','ï':'i',
        'Ó':'O','Ò':'O','Ô':'O','Õ':'O','Ö':'O','ó':'o','ò':'o','ô':'o','õ':'o','ö':'o',
        'Ú':'U','Ù':'U','Û':'U','Ü':'U','ú':'u','ù':'u','û':'u','ü':'u',
        'Ç':'C','ç':'c'
    };
    let resultado = txt;
    for (const [k, v] of Object.entries(map)) {
        resultado = resultado.replace(new RegExp(k, 'g'), v);
    }
    resultado = resultado.toLowerCase();
    resultado = resultado.replace(/ /g, '_');
    resultado = resultado.replace(/[^a-z0-9_]/g, '');
    return resultado;
}

// Função para carregar eventos inscritos do servidor
function carregarEventosDoServidor() {
    // IMPORTANTE: Verificar se está na página correta antes de executar
    const params = new URLSearchParams(window.location.search);
    const pagina = params.get('pagina');
    if (pagina !== 'meusEventos') {
        // Não executar se não estiver na página de "meusEventos"
        return;
    }
    
    console.log('carregarEventosDoServidor() chamado');
    const container = document.getElementById('eventos-container');
    if (!container) {
        console.error('Container de eventos não encontrado');
        return;
    }

    fetch('BuscarEventosInscritos.php')
        .then(response => response.json())
        .then(data => {
            console.log('Resposta do servidor:', data);
            if (!data.sucesso) {
                console.error('Erro ao carregar eventos:', data.mensagem);
                container.innerHTML = '<p style="grid-column:1/-1;text-align:center;padding:20px;color:var(--branco);">Erro ao carregar eventos</p>';
                return;
            }

            if (data.eventos.length === 0) {
                console.log('Nenhum evento inscrito encontrado');
                container.innerHTML = '<p style="grid-column:1/-1;text-align:center;padding:20px;color:var(--branco);">Você ainda não está inscrito em nenhum evento</p>';
                return;
            }

            console.log(`Carregando ${data.eventos.length} eventos`);
            container.innerHTML = '';

            data.eventos.forEach(evento => {
                const dataInicio = new Date(evento.inicio.replace(' ', 'T'));
                const dataConclusao = new Date(evento.conclusao.replace(' ', 'T'));
                const agora = new Date();
                
                let status = '';
                if (agora < dataInicio) {
                    status = 'Aguardando';
                } else if (agora >= dataInicio && agora <= dataConclusao) {
                    status = 'Em andamento';
                } else {
                    status = 'Finalizado';
                }

                const dataFormatada = dataInicio.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: '2-digit' });
                const dataISO = dataInicio.toISOString().split('T')[0];
                
                const tipo = formatar(evento.categoria);
                const local = formatar(evento.lugar);
                const modalidadeAttr = formatar(evento.modalidade || '');
                
                let duracaoFaixa = '';
                const h = parseFloat(evento.duracao);
                if (!isNaN(h)) {
                    if (h < 1) duracaoFaixa = 'menos_1h';
                    else if (h < 2) duracaoFaixa = '1h_2h';
                    else if (h < 4) duracaoFaixa = '2h_4h';
                    else duracaoFaixa = 'mais_5h';
                }
                
                // Certificado: considerar tipo_certificado
                const tipoCertificado = evento.tipo_certificado || '';
                const temCertificado = parseInt(evento.certificado) === 1;
                let certTexto, cert;
                
                if (temCertificado) {
                    if (tipoCertificado === 'Ensino' || tipoCertificado === 'Pesquisa' || tipoCertificado === 'Extensao') {
                        certTexto = tipoCertificado;
                    } else {
                        certTexto = 'Sim';
                    }
                    cert = 'sim';
                } else {
                    certTexto = 'Não';
                    cert = 'nao';
                }

                // Caminho da imagem do evento, com fallback para imagem padrão
                const imagemEvento = (evento.imagem && String(evento.imagem).trim() !== '')
                    ? String(evento.imagem).replace(/^\/+/, '')
                    : 'ImagensEventos/CEU-ImagemEvento.png';
                const caminhoImagem = `../${imagemEvento}`;

                const div = document.createElement('a');
                div.className = 'botao CaixaDoEvento';
                div.href = `ContainerParticipante.php?pagina=eventoInscrito&id=${evento.cod_evento}`;
                div.style.textDecoration = 'none';
                div.style.color = 'inherit';
                div.style.display = 'block';
                div.dataset.tipo = tipo;
                div.dataset.modalidade = modalidadeAttr;
                div.dataset.localizacao = local;
                div.dataset.duracao = duracaoFaixa;
                div.dataset.data = dataISO;
                div.dataset.dataFim = dataConclusao.toISOString().split('T')[0];
                div.dataset.certificado = cert;
                div.setAttribute('data-cod-evento', evento.cod_evento);

                // Mantém a mesma estrutura visual dos cards da página de Início, com lista e ícones
                div.innerHTML = `
                    <div class="AcoesFlutuantes">
                        <button type="button" class="BotaoAcaoCard BotaoDesinscreverCard botao" title="Cancelar inscrição"
                            aria-label="Cancelar inscrição" data-cod="${evento.cod_evento}">
                            <img src="../Imagens/Circulo_check.svg" alt="Inscrito">
                        </button>
                        <button type="button" class="BotaoAcaoCard BotaoFavoritoCard botao" title="Favoritar"
                            aria-label="Favoritar" data-cod="${evento.cod_evento}" data-favorito="0">
                            <img src="../Imagens/Medalha_linha.svg" alt="Favoritar">
                        </button>
                        <button type="button" class="BotaoAcaoCard BotaoMensagemCard botao" title="Enviar mensagem ao organizador"
                            aria-label="Mensagem" data-cod="${evento.cod_evento}">
                            <img src="../Imagens/Carta.svg" alt="Mensagem">
                        </button>
                        <button type="button" class="BotaoAcaoCard BotaoCompartilharCard botao" title="Compartilhar"
                            aria-label="Compartilhar" data-cod="${evento.cod_evento}">
                            <img src="../Imagens/Icone_Compartilhar.svg" alt="Compartilhar">
                        </button>
                    </div>
                    <div class="EventoImagem">
                        <img src="${caminhoImagem}" alt="${evento.nome}">
                    </div>
                    <div class="EventoTitulo">${evento.nome}</div>
                    <div class="EventoInfo">
                        <ul class="evento-info-list" aria-label="Informações do evento">
                            <li class="evento-info-item">
                                <span class="evento-info-icone" aria-hidden="true">
                                    <img src="../Imagens/info-status.svg" alt="" />
                                </span>
                                <span class="evento-info-texto"><span class="evento-info-label">Status:</span> ${status}</span>
                            </li>
                            <li class="evento-info-item">
                                <span class="evento-info-icone" aria-hidden="true">
                                    <img src="../Imagens/info-data.svg" alt="" />
                                </span>
                                <span class="evento-info-texto"><span class="evento-info-label">Data:</span> ${dataFormatada}</span>
                            </li>
                            <li class="evento-info-item">
                                <span class="evento-info-icone" aria-hidden="true">
                                    <img src="../Imagens/info-certificado.svg" alt="" />
                                </span>
                                <span class="evento-info-texto"><span class="evento-info-label">Certificado:</span> ${certTexto}</span>
                            </li>
                        </ul>
                    </div>
                `;

                container.appendChild(div);
            });

            // Carregar favoritos após carregar eventos para atualizar ícones
            setTimeout(async () => {
                if (typeof window.carregarFavoritos === 'function') {
                    await window.carregarFavoritos();
                }
            }, 100);

            // Carregar inscrições após carregar eventos para atualizar ícones
            setTimeout(() => {
                if (typeof window.carregarInscricoes === 'function') {
                    window.carregarInscricoes();
                }
            }, 150);

            // Aplicar listener de prevenção de navegação nos novos cards
            setTimeout(() => {
                if (typeof window.prevenirNavegacaoCards === 'function') {
                    window.prevenirNavegacaoCards();
                }
            }, 200);

            // Inicializa a paginação após carregar os eventos (sempre mostra finalizados)
            if (typeof window.inicializarPaginacaoEventos === 'function') {
                window.inicializarPaginacaoEventos('eventos-container', true); // true = ocultar filtro de finalizados
            }
            
            // Reinicializa os filtros após carregar
            if (typeof window.inicializarFiltroEventos === 'function') {
                window.inicializarFiltroEventos();
            }
        })
        .catch(error => {
            console.error('Erro ao buscar eventos:', error);
            container.innerHTML = '<p style="grid-column:1/-1;text-align:center;padding:20px;color:var(--branco);">Erro ao carregar eventos</p>';
        });
}

// Função para prevenir navegação dos cards ao clicar nos botões
function prevenirNavegacaoCards() {
    document.querySelectorAll('.CaixaDoEvento').forEach(link => {
        // Remover listener anterior se existir
        if (link._clickHandlerAdded) return;
        
        link.addEventListener('click', function(e) {
            // Se o clique foi em qualquer botão de ação ou dentro de AcoesFlutuantes, prevenir navegação
            if (e.target.closest('.AcoesFlutuantes') || 
                e.target.closest('.BotaoAcaoCard') ||
                e.target.closest('.BotaoDesinscreverCard') ||
                e.target.closest('.BotaoFavoritoCard') ||
                e.target.closest('.BotaoMensagemCard') ||
                e.target.closest('.BotaoCompartilharCard')) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }, true);
        
        link._clickHandlerAdded = true;
    });
    
    // Adicionar listeners nos próprios botões para garantir que o clique não propague
    document.querySelectorAll('.BotaoAcaoCard').forEach(btn => {
        if (btn._stopPropagationAdded) return;
        
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            e.stopImmediatePropagation();
        }, true);
        
        btn._stopPropagationAdded = true;
    });
}

function inicializarFiltroEventos() {
    const searchInput = document.querySelector('.campo-pesquisa');
    const searchButton = document.querySelector('.botao-pesquisa');
    const eventosContainer = document.getElementById('eventos-container');

    // Mensagem de "Sem resultados" (garante uma única instância)
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
        // Remove instâncias repetidas se houver
        const duplicadas = eventosContainer?.querySelectorAll('#sem-resultados-msg');
        if (duplicadas && duplicadas.length > 1) {
            duplicadas.forEach((el, idx) => { if (idx > 0) el.remove(); });
        }
        if (existemVisiveis) {
            if (eventosContainer && eventosContainer.contains(semResultadosMsg)) {
                eventosContainer.removeChild(semResultadosMsg);
            }
        } else {
            if (eventosContainer && !eventosContainer.contains(semResultadosMsg)) {
                eventosContainer.appendChild(semResultadosMsg);
            }
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

        // Só mostra "Sem resultados" se houver caixas carregadas mas nenhuma visível
        if ((eventosContainer?.querySelectorAll('.CaixaDoEvento') || []).length > 0) {
            atualizarMensagemSemResultados(algumaVisivel);
        }
        
        // Resetar paginação para primeira página e atualizar contador de eventos
        if (typeof window.resetarPaginacao === 'function') {
            window.resetarPaginacao('eventos-container');
        }
    }

    if (searchButton && !searchButton.dataset.filtroMeusEventosBound) {
        searchButton.onclick = function (e) {
            e.preventDefault();
            filtrarEventos();
        };
        searchButton.dataset.filtroMeusEventosBound = '1';
    }
    if (searchInput) {
        searchInput.onkeydown = function (e) {
            if (e.key === 'Enter') {
                filtrarEventos();
            }
        };
        if (!searchInput.dataset.filtroMeusEventosInputBound) {
            searchInput.addEventListener('input', filtrarEventos);
            searchInput.dataset.filtroMeusEventosInputBound = '1';
        }
    }

    // Inicializa o filtro lateral quando disponível
    if (typeof inicializarFiltro === 'function') {
        inicializarFiltro();
    }

    // Primeira avaliação (só se tiver eventos)
    const caixas = eventosContainer?.querySelectorAll('.CaixaDoEvento') || [];
    if (caixas.length > 0) {
        filtrarEventos();
    } else {
        // Se não há eventos, garante que a mensagem não fique sobrando
        atualizarMensagemSemResultados(true);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Verificar se está na página correta antes de carregar
    const params = new URLSearchParams(window.location.search);
    const pagina = params.get('pagina');
    if (pagina === 'meusEventos') {
        carregarEventosDoServidor();
    }
});

// Listener para recarregar quando houver mudança nas inscrições
// IMPORTANTE: Verifica se está na página correta antes de executar
window.addEventListener('inscricaoAtualizada', function() {
    const params = new URLSearchParams(window.location.search);
    const pagina = params.get('pagina');
    // Só recarrega se estiver na página de "meusEventos"
    if (pagina === 'meusEventos') {
        carregarEventosDoServidor();
    }
});

// Também recarregar quando a página voltar ao foco (navegação de volta)
window.addEventListener('focus', function() {
    // Verifica se está na página de meus eventos
    const params = new URLSearchParams(window.location.search);
    const pagina = params.get('pagina');
    if (pagina === 'meusEventos') {
        carregarEventosDoServidor();
    }
});

window.inicializarFiltroEventos = inicializarFiltroEventos;
window.carregarEventosDoServidor = carregarEventosDoServidor;
window.prevenirNavegacaoCards = prevenirNavegacaoCards;

