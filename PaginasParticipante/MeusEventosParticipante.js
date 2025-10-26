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
                
                const cert = (parseInt(evento.certificado) === 1) ? 'sim' : 'nao';
                const certTexto = (cert === 'sim') ? 'Sim' : 'Não';

                // Caminho da imagem do evento, com fallback para imagem padrão
                const imagemEvento = (evento.imagem && String(evento.imagem).trim() !== '')
                    ? String(evento.imagem).replace(/^\/+/, '')
                    : 'ImagensEventos/CEU-Logo.png';
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
                div.dataset.certificado = cert;

                // Mantém a mesma estrutura visual dos cards da página de Início
                div.innerHTML = `
                    <div class="EventoImagem">
                        <img src="${caminhoImagem}" alt="${evento.nome}">
                    </div>
                    <div class="EventoTitulo">${evento.nome}</div>
                    <div class="EventoInfo">${status}<br>Data: ${dataFormatada}<br>Certificado: ${certTexto}</div>
                `;

                container.appendChild(div);
            });

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
    carregarEventosDoServidor();
});

// Listener para recarregar quando houver mudança nas inscrições
window.addEventListener('inscricaoAtualizada', function() {
    carregarEventosDoServidor();
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
