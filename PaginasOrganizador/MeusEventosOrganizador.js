// Usa variáveis globais para evitar redeclaração
if (typeof window.carregandoEventos === 'undefined') {
    window.carregandoEventos = false;
}
if (typeof window.carregandoColaboracao === 'undefined') {
    window.carregandoColaboracao = false;
}

function carregarEventosDoServidor() {
    if (window.carregandoEventos) return; // Evita execução duplicada
    window.carregandoEventos = true;

    const containerEventos = document.getElementById('eventos-container');
    const botaoAdicionarEvento = containerEventos.querySelector('.CaixaDoEventoAdicionar');

    // Limpa eventos existentes (mantém apenas o botão adicionar)
    const eventosAntigos = containerEventos.querySelectorAll('.CaixaDoEvento');
    eventosAntigos.forEach(eventoAntigo => eventoAntigo.remove());

    // Remove TODAS as mensagens antigas (loading, sem eventos, sem resultados)
    const todasMensagens = Array.from(containerEventos.children).filter(child => {
        return !child.classList.contains('CaixaDoEventoAdicionar') &&
            !child.classList.contains('CaixaDoEvento');
    });
    todasMensagens.forEach(msg => msg.remove());

    // Mostra mensagem de carregamento
    const mensagemCarregando = document.createElement('div');
    mensagemCarregando.className = 'loading-eventos';
    mensagemCarregando.textContent = 'Carregando eventos...';
    mensagemCarregando.style.gridColumn = '1/-1';
    mensagemCarregando.style.textAlign = 'center';
    mensagemCarregando.style.padding = '30px 0';
    mensagemCarregando.style.color = 'var(--botao)';
    containerEventos.appendChild(mensagemCarregando);

    fetch('GerenciadorEventos.php?action=listar_organizador')
        .then(respostaServidor => respostaServidor.json())
        .then(dadosRecebidos => {
            mensagemCarregando.remove();
            window.carregandoEventos = false; // Libera para próxima chamada

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

                    caixaEventoHTML.onclick = function () {
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
                mensagemSemEventos.style.padding = '5px 0';
                mensagemSemEventos.style.color = 'var(--botao)';
                mensagemSemEventos.style.fontSize = '1.1rem';
                containerEventos.appendChild(mensagemSemEventos);
            }
        })
        .catch(erroRequisicao => {
            mensagemCarregando.remove();
            window.carregandoEventos = false; // Libera para próxima chamada
            console.error('Erro ao carregar eventos:', erroRequisicao);
            alert('Erro ao carregar eventos. Por favor, tente novamente.');
        });
}

function inicializarFiltroEventos() {
    const campoInputPesquisa = document.querySelector('.campo-pesquisa');
    const botaoPesquisar = document.querySelector('.botao-pesquisa');
    const containerEventos = document.getElementById('eventos-container');
    const containerColaboracao = document.getElementById('colaboracao-container');

    // Elementos das seções (apenas os títulos h1, não a barra de pesquisa)
    const tituloMeusEventos = document.querySelector('.div-section-title');
    const secaoColaboracao = document.querySelector('.secao-colaboracao');
    const divisoria = document.querySelector('.divisoria-secoes');
    const botaoAdicionar = containerEventos.querySelector('.CaixaDoEventoAdicionar');

    // Cria mensagem de "Sem resultados"
    let mensagemSemResultados = document.createElement('div');
    mensagemSemResultados.className = 'mensagem-sem-resultados-pesquisa';
    mensagemSemResultados.textContent = 'Sem resultados';
    mensagemSemResultados.style.color = 'var(--botao)';
    mensagemSemResultados.style.fontWeight = 'bold';
    mensagemSemResultados.style.fontSize = '1.2rem';
    mensagemSemResultados.style.gridColumn = '1/-1';
    mensagemSemResultados.style.textAlign = 'center';
    mensagemSemResultados.style.padding = '30px 0';

    function filtrarEventosPorTermoBusca() {
        const termoBusca = campoInputPesquisa.value.trim().toLowerCase();
        const todosEventos = [
            ...containerEventos.querySelectorAll('.CaixaDoEvento'),
            ...containerColaboracao.querySelectorAll('.CaixaDoEvento')
        ];
        let encontrouResultados = false;

        if (termoBusca === '') {
            // Sem pesquisa: mostra as seções separadas normalmente
            todosEventos.forEach(evento => evento.style.display = '');
            
            // Mostra todos os elementos das seções
            if (tituloMeusEventos) tituloMeusEventos.style.display = '';
            if (secaoColaboracao) secaoColaboracao.style.display = '';
            if (divisoria) divisoria.style.display = '';
            if (botaoAdicionar) botaoAdicionar.style.display = '';

            // Mostra todas as mensagens de "sem eventos" em ambos os containers
            const todasMensagens = [
                ...Array.from(containerEventos.children),
                ...Array.from(containerColaboracao.children)
            ];
            
            todasMensagens.forEach(elemento => {
                const texto = elemento.textContent;
                if (texto && (texto.includes('Você ainda não criou') || texto.includes('colaborador em nenhum evento'))) {
                    elemento.style.display = '';
                }
            });

            // Remove mensagem de "sem resultados" se existir
            const msgExistente = document.querySelector('.mensagem-sem-resultados-pesquisa');
            if (msgExistente) msgExistente.remove();

        } else {
            // Com pesquisa: oculta títulos, divisória, botão adicionar e mensagens de "sem eventos"
            if (tituloMeusEventos) tituloMeusEventos.style.display = 'none';
            if (secaoColaboracao) secaoColaboracao.style.display = 'none';
            if (divisoria) divisoria.style.display = 'none';
            if (botaoAdicionar) botaoAdicionar.style.display = 'none';

            // Oculta mensagens de "sem eventos"
            const mensagensSemEventos = [
                ...Array.from(containerEventos.children).filter(el => 
                    !el.classList.contains('CaixaDoEvento') && 
                    !el.classList.contains('CaixaDoEventoAdicionar') &&
                    !el.classList.contains('loading-eventos') &&
                    !el.classList.contains('mensagem-sem-resultados-pesquisa')
                ),
                ...Array.from(containerColaboracao.children).filter(el => 
                    !el.classList.contains('CaixaDoEvento') &&
                    !el.classList.contains('loading-eventos') &&
                    !el.classList.contains('mensagem-sem-resultados-pesquisa')
                )
            ];
            mensagensSemEventos.forEach(msg => msg.style.display = 'none');

            // Filtra eventos
            todosEventos.forEach(caixaEvento => {
                const elementoTitulo = caixaEvento.querySelector('.EventoTitulo');
                const elementoInfo = caixaEvento.querySelector('.EventoInfo');

                if (elementoTitulo && elementoInfo) {
                    const textoTitulo = elementoTitulo.textContent.toLowerCase();
                    const textoInfo = elementoInfo.textContent.toLowerCase();

                    if (textoTitulo.includes(termoBusca) || textoInfo.includes(termoBusca)) {
                        caixaEvento.style.display = '';
                        encontrouResultados = true;
                    } else {
                        caixaEvento.style.display = 'none';
                    }
                }
            });

            // Remove mensagem anterior se existir
            const msgExistente = document.querySelector('.mensagem-sem-resultados-pesquisa');
            if (msgExistente) msgExistente.remove();

            // Mostra mensagem se não encontrou resultados
            if (!encontrouResultados) {
                containerEventos.appendChild(mensagemSemResultados);
            }
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

        // Filtra em tempo real enquanto digita
        campoInputPesquisa.oninput = function () {
            filtrarEventosPorTermoBusca();
        };
    }

    // Inicializa o filtro lateral quando disponível
    if (typeof inicializarFiltro === 'function') {
        inicializarFiltro();
    }

    // Carrega eventos do servidor automaticamente
    carregarEventosDoServidor();
    carregarEventosColaboracao();
}

function carregarEventosColaboracao() {
    if (window.carregandoColaboracao) return; // Evita execução duplicada
    window.carregandoColaboracao = true;

    const containerColaboracao = document.getElementById('colaboracao-container');
    if (!containerColaboracao) {
        window.carregandoColaboracao = false;
        return;
    }

    // Limpa eventos existentes
    const eventosAntigos = containerColaboracao.querySelectorAll('.CaixaDoEvento');
    eventosAntigos.forEach(eventoAntigo => eventoAntigo.remove());

    // Remove mensagens antigas
    const mensagensAntigas = Array.from(containerColaboracao.children).filter(child => {
        return !child.classList.contains('CaixaDoEvento');
    });
    mensagensAntigas.forEach(msg => msg.remove());

    // Mostra mensagem de carregamento
    const mensagemCarregando = document.createElement('div');
    mensagemCarregando.className = 'loading-eventos';
    mensagemCarregando.textContent = 'Carregando eventos de colaboração...';
    mensagemCarregando.style.gridColumn = '1/-1';
    mensagemCarregando.style.textAlign = 'center';
    mensagemCarregando.style.padding = '30px 0';
    mensagemCarregando.style.color = 'var(--botao)';
    containerColaboracao.appendChild(mensagemCarregando);

    fetch('GerenciadorEventos.php?action=listar_colaboracao')
        .then(respostaServidor => respostaServidor.json())
        .then(dadosRecebidos => {
            mensagemCarregando.remove();
            window.carregandoColaboracao = false; // Libera para próxima chamada

            if (dadosRecebidos.erro) {
                console.error('Erro ao buscar eventos de colaboração:', dadosRecebidos.erro);
                const mensagemErro = document.createElement('div');
                mensagemErro.textContent = 'Erro ao carregar eventos de colaboração';
                mensagemErro.style.gridColumn = '1/-1';
                mensagemErro.style.textAlign = 'center';
                mensagemErro.style.padding = '30px 0';
                mensagemErro.style.color = 'var(--vermelho)';
                containerColaboracao.appendChild(mensagemErro);
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

                    caixaEventoHTML.onclick = function () {
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
                    containerColaboracao.appendChild(caixaEventoHTML);
                });
            } else {
                const mensagemSemEventos = document.createElement('div');
                mensagemSemEventos.textContent = 'Você não é colaborador em nenhum evento ainda';
                mensagemSemEventos.style.gridColumn = '1/-1';
                mensagemSemEventos.style.textAlign = 'center';
                mensagemSemEventos.style.padding = '30px 0';
                mensagemSemEventos.style.color = 'var(--botao)';
                mensagemSemEventos.style.fontSize = '1.1rem';
                containerColaboracao.appendChild(mensagemSemEventos);
            }
        })
        .catch(erroRequisicao => {
            mensagemCarregando.remove();
            window.carregandoColaboracao = false; // Libera para próxima chamada
            console.error('Erro ao carregar eventos de colaboração:', erroRequisicao);
            const mensagemErro = document.createElement('div');
            mensagemErro.textContent = 'Erro ao carregar eventos de colaboração';
            mensagemErro.style.gridColumn = '1/-1';
            mensagemErro.style.textAlign = 'center';
            mensagemErro.style.padding = '30px 0';
            mensagemErro.style.color = 'var(--vermelho)';
            containerColaboracao.appendChild(mensagemErro);
        });
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
window.carregarEventosColaboracao = carregarEventosColaboracao;

document.addEventListener('DOMContentLoaded', inicializarFiltroEventos);
// Se usar AJAX para recarregar a página, chame window.inicializarFiltroEventos() após inserir o HTML
window.inicializarFiltroEventos = inicializarFiltroEventos;
