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

            // Usa função de atualização de visibilidade se disponível (integração com paginação)
            if (typeof window.atualizarVisibilidadeEvento === 'function') {
                window.atualizarVisibilidadeEvento(caixa);
            } else {
                // Fallback para comportamento antigo
                const hiddenByFilter = caixa.dataset.hiddenByFilter === 'true';
                const hiddenBySearch = caixa.dataset.hiddenBySearch === 'true';
                const deveOcultar = hiddenByFilter || hiddenBySearch;
                caixa.style.display = deveOcultar ? 'none' : '';
            }
            
            // Verificar se está visível
            if (caixa.style.display !== 'none') algumaVisivel = true;
        });

        atualizarMensagemSemResultados(algumaVisivel);
        
        // Resetar paginação para primeira página e atualizar contador de eventos
        if (typeof window.resetarPaginacao === 'function') {
            window.resetarPaginacao('eventos-container');
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
        searchInput.addEventListener('input', filtrarEventos);
    }

    if (typeof inicializarFiltro === 'function') {
        inicializarFiltro();
    }

    filtrarEventos();
}

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
    if (pagina !== 'eventosInscritos') {
        // Não executar se não estiver na página de "eventosInscritos"
        return;
    }
    
    const container = document.getElementById('eventos-container');
    if (!container) {
        console.error('Container de eventos não encontrado');
        return;
    }

    fetch('../PaginasParticipante/BuscarEventosInscritos.php')
        .then(response => response.json())
        .then(data => {
            if (!data.sucesso) {
                console.error('Erro ao carregar eventos:', data.mensagem);
                container.innerHTML = '<p style="grid-column:1/-1;text-align:center;padding:20px;color:var(--branco);">Erro ao carregar eventos</p>';
                return;
            }

            if (data.eventos.length === 0) {
                container.innerHTML = '<p style="grid-column:1/-1;text-align:center;padding:20px;color:var(--branco);">Você ainda não está inscrito em nenhum evento</p>';
                return;
            }

            container.innerHTML = '';

            data.eventos.forEach(evento => {
                const dataInicio = new Date(evento.inicio.replace(' ', 'T'));
                const dataConclusao = new Date(evento.conclusao.replace(' ', 'T'));
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
                    ? String(evento.imagem).replace(/^\/+/, '').replace(/^\\+/, '')
                    : 'ImagensEventos/CEU-ImagemEvento.png';
                const caminhoImagem = `../${imagemEvento}`;

                const div = document.createElement('a');
                div.className = 'botao CaixaDoEvento';
                div.href = `ContainerOrganizador.php?pagina=eventoInscrito&id=${evento.cod_evento}`;
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

                // Mantém a mesma estrutura visual dos cards da página PHP
                div.innerHTML = `
                    <div class="AcoesFlutuantes">
                        <button type="button" class="BotaoAcaoCard BotaoInscreverCard botao" title="Inscrever-se" aria-label="Inscrever"
                            data-cod="${evento.cod_evento}" data-inscrito="0">
                            <img src="../Imagens/Circulo_adicionar.svg" alt="Inscrever">
                        </button>
                        <button type="button" class="BotaoAcaoCard BotaoFavoritoCard botao" title="Favoritar" aria-label="Favoritar"
                            data-cod="${evento.cod_evento}" data-favorito="0">
                            <img src="../Imagens/Medalha_linha.svg" alt="Favoritar">
                        </button>
                        <button type="button" class="BotaoAcaoCard BotaoMensagemCard botao" title="Enviar mensagem ao organizador"
                            aria-label="Mensagem" data-cod="${evento.cod_evento}">
                            <img src="../Imagens/Carta.svg" alt="Mensagem">
                        </button>
                        <button type="button" class="BotaoAcaoCard BotaoCompartilharCard botao" title="Compartilhar"
                            aria-label="Compartilhar" data-cod="${evento.cod_evento}">
                            <img src="../Imagens/Icone_Compartilhar.svg" alt="Compartilhar" />
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
                                    <img src="../Imagens/info-categoria.svg" alt="" />
                                </span>
                                <span class="evento-info-texto"><span class="evento-info-label">Categoria:</span> ${evento.categoria || ''}</span>
                            </li>
                            <li class="evento-info-item">
                                <span class="evento-info-icone" aria-hidden="true">
                                    <img src="../Imagens/info-modalidade.svg" alt="" />
                                </span>
                                <span class="evento-info-texto"><span class="evento-info-label">Modalidade:</span> ${evento.modalidade || ''}</span>
                            </li>
                            <li class="evento-info-item">
                                <span class="evento-info-icone" aria-hidden="true">
                                    <img src="../Imagens/info-data.svg" alt="" />
                                </span>
                                <span class="evento-info-texto"><span class="evento-info-label">Data:</span> ${dataFormatada}</span>
                            </li>
                            <li class="evento-info-item">
                                <span class="evento-info-icone" aria-hidden="true">
                                    <img src="../Imagens/info-local.svg" alt="" />
                                </span>
                                <span class="evento-info-texto"><span class="evento-info-label">Local:</span> ${evento.lugar || ''}</span>
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

            // Carregar favoritos e inscrições após carregar eventos para atualizar ícones
            setTimeout(async () => {
                if (typeof window.carregarFavoritos === 'function') {
                    await window.carregarFavoritos();
                }
                if (typeof window.carregarInscricoes === 'function') {
                    window.carregarInscricoes();
                }
            }, 100);

            // Reinicializa os filtros após carregar
            if (typeof window.inicializarFiltroEventos === 'function') {
                window.inicializarFiltroEventos();
            }

            // Reaplicar paginação após carregar novos eventos
            if (typeof window.aplicarPaginacaoEventos === 'function') {
                window.aplicarPaginacaoEventos('eventos-container');
            }
        })
        .catch(error => {
            console.error('Erro ao buscar eventos:', error);
            container.innerHTML = '<p style="grid-column:1/-1;text-align:center;padding:20px;color:var(--branco);">Erro ao carregar eventos</p>';
        });
}

document.addEventListener('DOMContentLoaded', function() {
    inicializarFiltroEventos();
});
window.inicializarFiltroEventos = inicializarFiltroEventos;
window.carregarEventosDoServidor = carregarEventosDoServidor;

