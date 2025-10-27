function carregarEventosDoServidor() {
    const containerEventos = document.getElementById('eventos-container');
    const botaoAdicionarEvento = containerEventos.querySelector('.CaixaDoEventoAdicionar');
    
    // Limpa eventos existentes (mantém apenas o botão adicionar)
    const eventosAntigos = containerEventos.querySelectorAll('.CaixaDoEvento');
    eventosAntigos.forEach(eventoAntigo => eventoAntigo.remove());
    
    // Remove mensagens antigas de carregamento e "sem eventos"
    const mensagensAntigas = containerEventos.querySelectorAll('.loading-eventos, div:not(.CaixaDoEventoAdicionar):not(.CaixaDoEvento)');
    mensagensAntigas.forEach(msg => {
        if (msg.textContent.includes('Carregando eventos') || 
            msg.textContent.includes('Você ainda não criou nenhum evento') ||
            msg.textContent.includes('Sem resultados')) {
            msg.remove();
        }
    });
    
    // Mostra mensagem de carregamento
    const mensagemCarregando = document.createElement('div');
    mensagemCarregando.className = 'loading-eventos';
    mensagemCarregando.textContent = 'Carregando eventos...';
    mensagemCarregando.style.gridColumn = '1/-1';
    mensagemCarregando.style.textAlign = 'center';
    mensagemCarregando.style.padding = '30px 0';
    mensagemCarregando.style.color = 'var(--botao)';
    containerEventos.appendChild(mensagemCarregando);
    
    fetch('BuscarEventosOrganizador.php')
        .then(respostaServidor => respostaServidor.json())
        .then(dadosRecebidos => {
            mensagemCarregando.remove();
            
            if (dadosRecebidos.erro) {
                console.error('Erro ao buscar eventos:', dadosRecebidos.erro);
                alert('Erro ao carregar eventos: ' + dadosRecebidos.erro);
                return;
            }
            
            if (dadosRecebidos.sucesso && dadosRecebidos.eventos.length > 0) {
                dadosRecebidos.eventos.forEach(dadosEvento => {
                    const caixaEventoHTML = document.createElement('div');
                    caixaEventoHTML.className = 'botao CaixaDoEvento';
                    caixaEventoHTML.setAttribute('data-tipo', dadosEvento.categoria.toLowerCase());
                    caixaEventoHTML.setAttribute('data-modalidade', dadosEvento.modalidade.toLowerCase());
                    caixaEventoHTML.setAttribute('data-localizacao', dadosEvento.lugar.toLowerCase());
                    caixaEventoHTML.setAttribute('data-data', dadosEvento.inicio.split(' ')[0]);
                    caixaEventoHTML.setAttribute('data-certificado', dadosEvento.certificado === 'Sim' ? 'sim' : 'nao');
                    caixaEventoHTML.setAttribute('data-cod-evento', dadosEvento.cod_evento);
                    
                    caixaEventoHTML.onclick = function() {
                        carregarPagina('eventoOrganizado', dadosEvento.cod_evento);
                    };
                    
                    const tituloEvento = document.createElement('div');
                    tituloEvento.className = 'EventoTitulo';
                    tituloEvento.textContent = dadosEvento.nome;
                    
                    const informacoesEvento = document.createElement('div');
                    informacoesEvento.className = 'EventoInfo';
                    informacoesEvento.innerHTML = `
                        <ul class="evento-info-list" aria-label="Informações do evento">
                            <li class="evento-info-item">
                                <span class="evento-info-icone" aria-hidden="true">
                                    <img src="../Imagens/info-status.svg" alt="" />
                                </span>
                                <span class="evento-info-texto"><span class="evento-info-label">Status:</span> ${dadosEvento.status}</span>
                            </li>
                            <li class="evento-info-item">
                                <span class="evento-info-icone" aria-hidden="true">
                                    <img src="../Imagens/info-data.svg" alt="" />
                                </span>
                                <span class="evento-info-texto"><span class="evento-info-label">Data:</span> ${dadosEvento.data_formatada}</span>
                            </li>
                            <li class="evento-info-item">
                                <span class="evento-info-icone" aria-hidden="true">
                                    <img src="../Imagens/info-certificado.svg" alt="" />
                                </span>
                                <span class="evento-info-texto"><span class="evento-info-label">Certificado:</span> ${dadosEvento.certificado}</span>
                            </li>
                        </ul>
                    `;
                    
                    caixaEventoHTML.appendChild(tituloEvento);
                    caixaEventoHTML.appendChild(informacoesEvento);
                    containerEventos.appendChild(caixaEventoHTML);
                });
            } else {
                const mensagemSemEventos = document.createElement('div');
                mensagemSemEventos.textContent = 'Você ainda não criou nenhum evento';
                mensagemSemEventos.style.gridColumn = '1/-1';
                mensagemSemEventos.style.textAlign = 'center';
                mensagemSemEventos.style.padding = '30px 0';
                mensagemSemEventos.style.color = 'var(--botao)';
                mensagemSemEventos.style.fontSize = '1.1rem';
                containerEventos.appendChild(mensagemSemEventos);
            }
        })
        .catch(erroRequisicao => {
            mensagemCarregando.remove();
            console.error('Erro ao carregar eventos:', erroRequisicao);
            alert('Erro ao carregar eventos. Por favor, tente novamente.');
        });
}

function inicializarFiltroEventos() {
    const campoInputPesquisa = document.querySelector('.campo-pesquisa');
    const botaoPesquisar = document.querySelector('.botao-pesquisa');
    const containerEventos = document.getElementById('eventos-container');

    // Cria mensagem de "Sem resultados"
    let mensagemSemResultados = document.createElement('div');
    mensagemSemResultados.textContent = 'Sem resultados';
    mensagemSemResultados.style.color = 'var(--botao)';
    mensagemSemResultados.style.fontWeight = 'bold';
    mensagemSemResultados.style.fontSize = '1.2rem';
    mensagemSemResultados.style.gridColumn = '1/-1';
    mensagemSemResultados.style.textAlign = 'center';
    mensagemSemResultados.style.padding = '30px 0';

    function filtrarEventosPorTermoBusca() {
        const termoBusca = campoInputPesquisa.value.trim().toLowerCase();
        const caixasEventos = containerEventos.querySelectorAll('.CaixaDoEvento');
        let encontrouResultados = false;

        caixasEventos.forEach(caixaEvento => {
            const elementoTitulo = caixaEvento.querySelector('.EventoTitulo');
            const elementoInfo = caixaEvento.querySelector('.EventoInfo');
            
            if (elementoTitulo && elementoInfo) {
                const textoTitulo = elementoTitulo.textContent.toLowerCase();
                const textoInfo = elementoInfo.textContent.toLowerCase();
                
                if (termoBusca === '' || textoTitulo.includes(termoBusca) || textoInfo.includes(termoBusca)) {
                    caixaEvento.style.display = '';
                    encontrouResultados = true;
                } else {
                    caixaEvento.style.display = 'none';
                }
            }
        });

        // Remove mensagem anterior se existir
        if (containerEventos.contains(mensagemSemResultados)) {
            containerEventos.removeChild(mensagemSemResultados);
        }

        // Mostra mensagem apenas se não encontrou resultados e há termo de busca
        if (!encontrouResultados && termoBusca !== '') {
            containerEventos.appendChild(mensagemSemResultados);
        }
    }

    if (botaoPesquisar) {
        botaoPesquisar.onclick = function (eventoClick) {
            eventoClick.preventDefault();
            filtrarEventosPorTermoBusca();
        };
    }
    
    if (campoInputPesquisa) {
        campoInputPesquisa.onkeydown = function (eventoTeclado) {
            if (eventoTeclado.key === 'Enter') {
                filtrarEventosPorTermoBusca();
            }
        };
    }
    
    // Inicializa o filtro lateral quando disponível
    if (typeof inicializarFiltro === 'function') {
        inicializarFiltro();
    }
    
    // Carrega eventos do servidor automaticamente
    carregarEventosDoServidor();
}

// Função para adicionar novo evento
function adicionarNovoEvento() {
    if (typeof carregarPagina === 'function') {
        carregarPagina('adicionarEvento');
    }
}

// Torna as funções globais
window.adicionarNovoEvento = adicionarNovoEvento;
window.carregarEventosDoServidor = carregarEventosDoServidor;

document.addEventListener('DOMContentLoaded', inicializarFiltroEventos);
// Se usar AJAX para recarregar a página, chame window.inicializarFiltroEventos() após inserir o HTML
window.inicializarFiltroEventos = inicializarFiltroEventos;
