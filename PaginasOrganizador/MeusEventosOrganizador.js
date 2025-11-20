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
    if (!containerEventos) {
        window.carregandoEventos = false;
        return;
    }
    
    const botaoAdicionarEvento = containerEventos.querySelector('.CaixaDoEventoAdicionar');

    // Verifica se já existem eventos renderizados pelo PHP
    const eventosExistentes = containerEventos.querySelectorAll('.CaixaDoEvento');
    const temEventosPHP = eventosExistentes.length > 0;
    
    // Se já tem eventos do PHP e eles têm EventoImagem, não recarrega
    if (temEventosPHP) {
        const primeiroEvento = eventosExistentes[0];
        const temImagem = primeiroEvento.querySelector('.EventoImagem');
        if (temImagem) {
            window.carregandoEventos = false;
            return; // Eventos já estão renderizados corretamente pelo PHP
        }
    }

    // Limpa eventos existentes (mantém apenas o botão adicionar)
    eventosExistentes.forEach(eventoAntigo => eventoAntigo.remove());

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

                    // Ações flutuantes: Favoritar, Mensagem, Compartilhar
                    const divAcoes = document.createElement('div');
                    divAcoes.className = 'AcoesFlutuantes';
                    
                    const btnFavorito = document.createElement('button');
                    btnFavorito.type = 'button';
                    btnFavorito.className = 'BotaoAcaoCard BotaoFavoritoCard botao';
                    btnFavorito.title = 'Favoritar';
                    btnFavorito.setAttribute('aria-label', 'Favoritar');
                    btnFavorito.setAttribute('data-cod', dadosEvento.cod_evento);
                    btnFavorito.setAttribute('data-favorito', '0');
                    btnFavorito.onclick = function(e) { e.preventDefault(); e.stopPropagation(); return false; };
                    const imgFav = document.createElement('img');
                    imgFav.src = '../Imagens/Medalha_linha.svg';
                    imgFav.alt = 'Favoritar';
                    btnFavorito.appendChild(imgFav);
                    divAcoes.appendChild(btnFavorito);

                    const btnMensagem = document.createElement('button');
                    btnMensagem.type = 'button';
                    btnMensagem.className = 'BotaoAcaoCard BotaoMensagemCard botao';
                    btnMensagem.title = 'Enviar mensagem ao organizador';
                    btnMensagem.setAttribute('aria-label', 'Mensagem');
                    btnMensagem.setAttribute('data-cod', dadosEvento.cod_evento);
                    btnMensagem.onclick = function(e) { e.preventDefault(); e.stopPropagation(); return false; };
                    const imgMsg = document.createElement('img');
                    imgMsg.src = '../Imagens/Carta.svg';
                    imgMsg.alt = 'Mensagem';
                    btnMensagem.appendChild(imgMsg);
                    divAcoes.appendChild(btnMensagem);

                    const btnCompartilhar = document.createElement('button');
                    btnCompartilhar.type = 'button';
                    btnCompartilhar.className = 'BotaoAcaoCard BotaoCompartilharCard botao';
                    btnCompartilhar.title = 'Compartilhar';
                    btnCompartilhar.setAttribute('aria-label', 'Compartilhar');
                    btnCompartilhar.setAttribute('data-cod', dadosEvento.cod_evento);
                    btnCompartilhar.onclick = function(e) { e.preventDefault(); e.stopPropagation(); return false; };
                    const imgComp = document.createElement('img');
                    imgComp.src = '../Imagens/Icone_Compartilhar.svg';
                    imgComp.alt = 'Compartilhar';
                    btnCompartilhar.appendChild(imgComp);
                    divAcoes.appendChild(btnCompartilhar);

                    // EventoImagem - adiciona a imagem do evento
                    const divImagem = document.createElement('div');
                    divImagem.className = 'EventoImagem';
                    const imgEvento = document.createElement('img');
                    const caminhoImagem = dadosEvento.imagem && dadosEvento.imagem !== '' 
                        ? '../' + dadosEvento.imagem.replace(/^[\/\\]/, '') 
                        : '../ImagensEventos/CEU-ImagemEvento.png';
                    imgEvento.src = caminhoImagem;
                    imgEvento.alt = dadosEvento.nome;
                    imgEvento.onerror = function() { this.src = '../ImagensEventos/CEU-ImagemEvento.png'; };
                    divImagem.appendChild(imgEvento);

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

                    caixaEventoHTML.appendChild(divAcoes);
                    caixaEventoHTML.appendChild(divImagem);
                    caixaEventoHTML.appendChild(tituloEvento);
                    caixaEventoHTML.appendChild(informacoesEvento);
                    containerEventos.appendChild(caixaEventoHTML);
                });
                
                // Carregar favoritos após carregar eventos para atualizar ícones
                setTimeout(async () => {
                    if (typeof window.carregarFavoritos === 'function') {
                        await window.carregarFavoritos();
                    }
                }, 100);
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
    // Proteção contra inicialização múltipla
    if (window.filtroEventosInicializado) {
        return;
    }
    window.filtroEventosInicializado = true;

    const campoInputPesquisa = document.querySelector('.campo-pesquisa');
    const botaoPesquisar = document.querySelector('.botao-pesquisa');
    const containerEventos = document.getElementById('eventos-container');
    const containerColaboracao = document.getElementById('colaboracao-container');
    
    // Garante que o botão de adicionar evento funcione (fallback para AJAX)
    const botaoAdicionar = containerEventos ? containerEventos.querySelector('.CaixaDoEventoAdicionar') : null;
    if (botaoAdicionar) {
        // Remove listeners antigos se existirem
        const novoBotao = botaoAdicionar.cloneNode(true);
        botaoAdicionar.parentNode.replaceChild(novoBotao, botaoAdicionar);
        // Adiciona listener como fallback
        novoBotao.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (typeof window.adicionarNovoEvento === 'function') {
                window.adicionarNovoEvento();
            } else if (typeof adicionarNovoEvento === 'function') {
                adicionarNovoEvento();
            }
        });
    }

    // Elementos que devem ser ocultados durante a pesquisa
    const tituloMeusEventos = document.querySelector('.titulo-meus-eventos');
    const tituloOrganizacao = document.querySelector('.titulo-organizacao');
    const wrapperMeusEventos = tituloMeusEventos ? tituloMeusEventos.closest('.section-title-wrapper') : null;
    const wrapperOrganizacao = document.querySelector('.secao-colaboracao');
    const divisoria = document.querySelector('.divisoria-secoes');
    // botaoAdicionar já foi declarado acima, reutiliza a mesma variável

    // Cria mensagem de "Sem resultados"
    let mensagemSemResultados = document.createElement('div');
    mensagemSemResultados.className = 'mensagem-sem-resultados-pesquisa';
    mensagemSemResultados.textContent = 'Sem resultados.';
    mensagemSemResultados.style.color = 'var(--botao)';
    mensagemSemResultados.style.fontWeight = 'bold';
    mensagemSemResultados.style.fontSize = '1.2rem';
    mensagemSemResultados.style.gridColumn = '1/-1';
    mensagemSemResultados.style.textAlign = 'center';
    mensagemSemResultados.style.padding = '60px 0';

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
            
            // Mostra todos os elementos das seções (wrappers inteiros)
            if (wrapperMeusEventos) wrapperMeusEventos.style.display = '';
            if (wrapperOrganizacao) wrapperOrganizacao.style.display = '';
            if (divisoria) divisoria.style.display = '';
            if (botaoAdicionar) botaoAdicionar.style.display = '';

            // Mostra todas as mensagens de "sem eventos" em ambos os containers
            const todasMensagens = [
                ...Array.from(containerEventos.children),
                ...Array.from(containerColaboracao.children)
            ];
            
            todasMensagens.forEach(elemento => {
                const texto = elemento.textContent;
                if (texto && (texto.includes('Você ainda não criou') || texto.includes('organizador em nenhum evento'))) {
                    elemento.style.display = '';
                }
            });

            // Remove mensagem de "sem resultados" se existir
            const msgExistente = document.querySelector('.mensagem-sem-resultados-pesquisa');
            if (msgExistente) msgExistente.remove();

        } else {
            // Com pesquisa ativa: oculta wrappers inteiros, divisória, botão adicionar e mensagens
            if (wrapperMeusEventos) wrapperMeusEventos.style.display = 'none';
            if (wrapperOrganizacao) wrapperOrganizacao.style.display = 'none';
            if (divisoria) divisoria.style.display = 'none';
            if (botaoAdicionar) botaoAdicionar.style.display = 'none';

            // Oculta mensagens de "sem eventos criados" e "não é organizador"
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

            // Filtra eventos por título ou informações
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
    // As funções internas verificam se já existem eventos do PHP antes de recarregar
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

    // Verifica se já existem eventos renderizados pelo PHP
    const eventosExistentes = containerColaboracao.querySelectorAll('.CaixaDoEvento');
    const temEventosPHP = eventosExistentes.length > 0;
    
    // Se já tem eventos do PHP e eles têm EventoImagem, não recarrega
    if (temEventosPHP) {
        const primeiroEvento = eventosExistentes[0];
        const temImagem = primeiroEvento.querySelector('.EventoImagem');
        if (temImagem) {
            window.carregandoColaboracao = false;
            return; // Eventos já estão renderizados corretamente pelo PHP
        }
    }

    // Limpa eventos existentes
    eventosExistentes.forEach(eventoAntigo => eventoAntigo.remove());

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

                    // Ações flutuantes: Favoritar, Mensagem, Compartilhar
                    const divAcoes = document.createElement('div');
                    divAcoes.className = 'AcoesFlutuantes';
                    
                    const btnFavorito = document.createElement('button');
                    btnFavorito.type = 'button';
                    btnFavorito.className = 'BotaoAcaoCard BotaoFavoritoCard botao';
                    btnFavorito.title = 'Favoritar';
                    btnFavorito.setAttribute('aria-label', 'Favoritar');
                    btnFavorito.setAttribute('data-cod', dadosEvento.cod_evento);
                    btnFavorito.setAttribute('data-favorito', '0');
                    btnFavorito.onclick = function(e) { e.preventDefault(); e.stopPropagation(); return false; };
                    const imgFav = document.createElement('img');
                    imgFav.src = '../Imagens/Medalha_linha.svg';
                    imgFav.alt = 'Favoritar';
                    btnFavorito.appendChild(imgFav);
                    divAcoes.appendChild(btnFavorito);

                    const btnMensagem = document.createElement('button');
                    btnMensagem.type = 'button';
                    btnMensagem.className = 'BotaoAcaoCard BotaoMensagemCard botao';
                    btnMensagem.title = 'Enviar mensagem ao organizador';
                    btnMensagem.setAttribute('aria-label', 'Mensagem');
                    btnMensagem.setAttribute('data-cod', dadosEvento.cod_evento);
                    btnMensagem.onclick = function(e) { e.preventDefault(); e.stopPropagation(); return false; };
                    const imgMsg = document.createElement('img');
                    imgMsg.src = '../Imagens/Carta.svg';
                    imgMsg.alt = 'Mensagem';
                    btnMensagem.appendChild(imgMsg);
                    divAcoes.appendChild(btnMensagem);

                    const btnCompartilhar = document.createElement('button');
                    btnCompartilhar.type = 'button';
                    btnCompartilhar.className = 'BotaoAcaoCard BotaoCompartilharCard botao';
                    btnCompartilhar.title = 'Compartilhar';
                    btnCompartilhar.setAttribute('aria-label', 'Compartilhar');
                    btnCompartilhar.setAttribute('data-cod', dadosEvento.cod_evento);
                    btnCompartilhar.onclick = function(e) { e.preventDefault(); e.stopPropagation(); return false; };
                    const imgComp = document.createElement('img');
                    imgComp.src = '../Imagens/Icone_Compartilhar.svg';
                    imgComp.alt = 'Compartilhar';
                    btnCompartilhar.appendChild(imgComp);
                    divAcoes.appendChild(btnCompartilhar);

                    // EventoImagem - adiciona a imagem do evento
                    const divImagem = document.createElement('div');
                    divImagem.className = 'EventoImagem';
                    const imgEvento = document.createElement('img');
                    const caminhoImagem = dadosEvento.imagem && dadosEvento.imagem !== '' 
                        ? '../' + dadosEvento.imagem.replace(/^[\/\\]/, '') 
                        : '../ImagensEventos/CEU-ImagemEvento.png';
                    imgEvento.src = caminhoImagem;
                    imgEvento.alt = dadosEvento.nome;
                    imgEvento.onerror = function() { this.src = '../ImagensEventos/CEU-ImagemEvento.png'; };
                    divImagem.appendChild(imgEvento);

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

                    caixaEventoHTML.appendChild(divAcoes);
                    caixaEventoHTML.appendChild(divImagem);
                    caixaEventoHTML.appendChild(tituloEvento);
                    caixaEventoHTML.appendChild(informacoesEvento);
                    containerColaboracao.appendChild(caixaEventoHTML);
                });
            } else {
                const mensagemSemEventos = document.createElement('div');
                mensagemSemEventos.textContent = 'Você não é organizador em nenhum evento ainda.';
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
window.inicializarFiltroEventos = inicializarFiltroEventos;

// Inicialização: funciona tanto no carregamento inicial quanto via AJAX
// Reseta flags quando a página é carregada via AJAX
if (typeof window.resetarInicializacaoMeusEventos === 'undefined') {
    window.resetarInicializacaoMeusEventos = function() {
        window.filtroEventosInicializado = false;
        window.carregandoEventos = false;
        window.carregandoColaboracao = false;
    };
}

if (document.readyState === 'loading') {
    // DOM ainda não está pronto, aguarda DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function() {
        // Verifica se estamos na página correta antes de inicializar
        if (document.getElementById('eventos-container')) {
            window.resetarInicializacaoMeusEventos();
            inicializarFiltroEventos();
        }
    });
} else {
    // DOM já está pronto (carregamento via AJAX ou página já carregada)
    // Usa setTimeout para garantir que o HTML foi inserido
    setTimeout(function() {
        if (document.getElementById('eventos-container')) {
            window.resetarInicializacaoMeusEventos();
            inicializarFiltroEventos();
        }
    }, 50);
}

// ====== Sistema de Favoritos, Mensagens e Compartilhar ======
// Variáveis globais
if (typeof window.codEventoOrganizador === 'undefined') {
    window.codEventoOrganizador = null;
    window.codEventoMensagemOrganizador = null;
    window.favoritosSetOrganizador = new Set();
    window.favoritosDadosOrganizador = [];
}

// ====== Modal de Compartilhar ======
function abrirModalCompartilhar() {
    if (!window.codEventoOrganizador) return;
    const modal = document.getElementById('modal-compartilhar');
    if (!modal) return;
    const linkEvento = `${window.location.origin}/CEU/PaginasPublicas/EventoPublico.php?codEvento=${window.codEventoOrganizador}`;
    const input = document.getElementById('link-inscricao');
    if (input) input.value = linkEvento;
    modal.classList.add('ativo');
    bloquearScroll();
}
function fecharModalCompartilhar() {
    const modal = document.getElementById('modal-compartilhar');
    if (!modal) return;
    modal.classList.remove('ativo');
    desbloquearScroll();
    // Garantir que o menu permaneça ativo após fechar o modal
    setTimeout(() => {
        const params = new URLSearchParams(window.location.search);
        const pagina = params.get('pagina') || 'meusEventos';
        if (typeof window.setMenuAtivoPorPagina === 'function') {
            window.setMenuAtivoPorPagina(pagina);
        }
    }, 10);
}
function copiarLink() {
    const input = document.getElementById('link-inscricao');
    if (!input) return;
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(() => {
        const iconeCopiar = document.getElementById('icone-copiar');
        const textoCopiar = document.getElementById('texto-copiar');
        if (iconeCopiar) {
            iconeCopiar.innerHTML = '<svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>';
        }
        if (textoCopiar) {
            textoCopiar.textContent = 'Copiado!';
        }
        setTimeout(() => {
            if (iconeCopiar) {
                iconeCopiar.innerHTML = '<svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>';
            }
            if (textoCopiar) {
                textoCopiar.textContent = 'Copiar';
            }
        }, 2000);
    }).catch(() => {
        try {
            input.select();
            document.execCommand('copy');
        } catch (err) {
            console.error('Erro ao copiar link:', err);
        }
    });
}
function compartilharWhatsApp() {
    if (!window.codEventoOrganizador) return;
    const linkEvento = `${window.location.origin}/CEU/PaginasPublicas/EventoPublico.php?codEvento=${window.codEventoOrganizador}`;
    const texto = `Confira este evento: ${linkEvento}`;
    window.open(`https://wa.me/?text=${encodeURIComponent(texto)}`, '_blank');
}
function compartilharInstagram() {
    if (!window.codEventoOrganizador) return;
    const linkEvento = `${window.location.origin}/CEU/PaginasPublicas/EventoPublico.php?codEvento=${window.codEventoOrganizador}`;
    navigator.clipboard.writeText(linkEvento).then(() => {
        alert('Link copiado! Cole no Instagram para compartilhar.');
    }).catch(() => {
        const input = document.getElementById('link-inscricao');
        if (input) {
            input.select();
            document.execCommand('copy');
            alert('Link copiado! Cole no Instagram para compartilhar.');
        }
    });
}
function compartilharEmail() {
    if (!window.codEventoOrganizador) return;
    const linkEvento = `${window.location.origin}/CEU/PaginasPublicas/EventoPublico.php?codEvento=${window.codEventoOrganizador}`;
    const assunto = 'Confira este evento!';
    const corpo = `Olá! Gostaria de compartilhar este evento com você: ${linkEvento}`;
    window.location.href = `mailto:?subject=${encodeURIComponent(assunto)}&body=${encodeURIComponent(corpo)}`;
}
function compartilharX() {
    if (!window.codEventoOrganizador) return;
    const linkEvento = `${window.location.origin}/CEU/PaginasPublicas/EventoPublico.php?codEvento=${window.codEventoOrganizador}`;
    const texto = `Confira este evento!`;
    window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(texto)}&url=${encodeURIComponent(linkEvento)}`, '_blank');
}
// Inicialização do modal de compartilhar movida para função inicializarModais()

// Funções de bloqueio/desbloqueio de scroll
function bloquearScroll() {
    document.body.classList.add('modal-aberto');
    document.addEventListener('wheel', prevenirScroll, { passive: false });
    document.addEventListener('touchmove', prevenirScroll, { passive: false });
    document.addEventListener('keydown', prevenirScrollTeclado, false);
}
function desbloquearScroll() {
    document.body.classList.remove('modal-aberto');
    document.removeEventListener('wheel', prevenirScroll);
    document.removeEventListener('touchmove', prevenirScroll);
    document.removeEventListener('keydown', prevenirScrollTeclado);
}
function prevenirScroll(e) { if (document.body.classList.contains('modal-aberto')) { e.preventDefault(); } }
function prevenirScrollTeclado(e) {
    if (!document.body.classList.contains('modal-aberto')) return;
    const teclas = [32, 33, 34, 35, 36, 37, 38, 39, 40];
    if (teclas.includes(e.keyCode)) e.preventDefault();
}

// ====== Modal de Mensagem ======
function abrirModalMensagem() {
    const m = document.getElementById('modal-mensagem');
    if (!m) return;
    const textarea = document.getElementById('texto-mensagem-organizador');
    if (textarea) textarea.value = '';
    m.classList.add('ativo');
    bloquearScroll();
}
function fecharModalMensagem(skipUnlock) {
    const m = document.getElementById('modal-mensagem');
    if (m) {
        m.classList.remove('ativo');
        if (!skipUnlock) {
            desbloquearScroll();
            // Garantir que o menu permaneça ativo após fechar o modal
            setTimeout(() => {
                const params = new URLSearchParams(window.location.search);
                const pagina = params.get('pagina') || 'meusEventos';
                if (typeof window.setMenuAtivoPorPagina === 'function') {
                    window.setMenuAtivoPorPagina(pagina);
                }
            }, 10);
        }
    }
}
async function enviarMensagemOrganizador() {
    const textarea = document.getElementById('texto-mensagem-organizador');
    if (!textarea) return;
    const texto = (textarea.value || '').trim();
    if (!window.codEventoMensagemOrganizador) { fecharModalMensagem(); return; }
    if (texto.length === 0) { alert('Digite sua mensagem.'); return; }
    let timeoutId = null;
    try {
        const controller = new AbortController();
        timeoutId = setTimeout(() => controller.abort(), 10000);
        const basePath = `${window.location.origin}/CEU/PaginasGlobais/EnviarMensagemOrganizador.php`;
        const r = await fetch(basePath, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            credentials: 'include',
            body: new URLSearchParams({ cod_evento: window.codEventoMensagemOrganizador, mensagem: texto }),
            signal: controller.signal
        });
        if (timeoutId) clearTimeout(timeoutId);
        const j = await r.json();
        fecharModalMensagem();
        if (j && j.sucesso) {
            alert('Mensagem enviada ao organizador!');
        } else {
            alert(j.mensagem || 'Não foi possível enviar a mensagem.');
        }
    } catch (e) {
        if (timeoutId) clearTimeout(timeoutId);
        fecharModalMensagem();
        if (e.name !== 'AbortError') {
            alert('Erro ao enviar mensagem.');
        }
    }
}

// ====== Favoritos ======
function atualizarIconeFavorito(btn, fav) {
    if (!btn) return;
    const img = btn.querySelector('img');
    if (!img) return;
    const novoSrc = fav ? '../Imagens/Medalha_preenchida.svg' : '../Imagens/Medalha_linha.svg';
    // Atualizar diretamente - navegador já tem as imagens em cache
    img.src = novoSrc;
    img.alt = fav ? 'Desfavoritar' : 'Favoritar';
    btn.title = fav ? 'Remover dos favoritos' : 'Adicionar aos favoritos';
    btn.setAttribute('data-favorito', fav ? '1' : '0');
}

async function carregarFavoritos() {
    let timeoutId = null;
    try {
        const controller = new AbortController();
        timeoutId = setTimeout(() => controller.abort(), 10000);
        const basePath = `${window.location.origin}/CEU/PaginasGlobais/ListarFavoritos.php`;
        const r = await fetch(basePath, { 
            credentials: 'include',
            signal: controller.signal
        });
        if (timeoutId) clearTimeout(timeoutId);
        if (r.status === 401) { 
            window.favoritosSetOrganizador.clear(); 
            window.favoritosDadosOrganizador = []; 
            return; 
        }
        if (!r.ok) {
            throw new Error(`HTTP error! status: ${r.status}`);
        }
        const j = await r.json();
        if (j && j.sucesso && Array.isArray(j.favoritos)) {
            window.favoritosSetOrganizador.clear();
            window.favoritosDadosOrganizador = j.favoritos.filter(f => f && f.cod_evento);
            for (const f of window.favoritosDadosOrganizador) {
                const cod = Number(f.cod_evento);
                if (cod > 0) window.favoritosSetOrganizador.add(cod);
            }
            document.querySelectorAll('.BotaoFavoritoCard').forEach(btn => {
                const cod = Number(btn.getAttribute('data-cod'));
                if (cod && !btn.dataset.processing) {
                    atualizarIconeFavorito(btn, window.favoritosSetOrganizador.has(cod));
                }
            });
        }
    } catch (e) {
        if (e.name !== 'AbortError') {
            console.warn('Erro ao carregar favoritos:', e);
        }
    } finally {
        if (timeoutId) clearTimeout(timeoutId);
    }
}

function abrirModalFavoritos() {
    renderizarFavoritos();
    const modal = document.getElementById('modal-favoritos');
    if (modal) {
        modal.classList.add('ativo');
        bloquearScroll();
    }
}

function fecharModalFavoritos() {
    const modal = document.getElementById('modal-favoritos');
    if (modal) {
        modal.classList.remove('ativo');
        desbloquearScroll();
        // Garantir que o menu permaneça ativo após fechar o modal
        setTimeout(() => {
            const params = new URLSearchParams(window.location.search);
            const pagina = params.get('pagina') || 'meusEventos';
            if (typeof window.setMenuAtivoPorPagina === 'function') {
                window.setMenuAtivoPorPagina(pagina);
            }
        }, 10);
    }
}

function renderizarFavoritos() {
    const cont = document.getElementById('lista-favoritos');
    if (!cont) return;
    cont.innerHTML = '';
    if (!window.favoritosDadosOrganizador || window.favoritosDadosOrganizador.length === 0) {
        cont.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:var(--texto);padding:1rem;">Nenhum evento favoritado.</div>';
        return;
    }
    const frag = document.createDocumentFragment();
    window.favoritosDadosOrganizador.forEach(ev => {
        if (!ev || !ev.cod_evento) return;
        const a = document.createElement('a');
        a.href = `ContainerOrganizador.php?pagina=eventoOrganizado&id=${ev.cod_evento}`;
        a.className = 'favorito-item';
        a.onclick = function(e) {
            if (e.target.closest('.BotaoAcaoCard')) {
                e.preventDefault();
                return false;
            }
        };

        const divAcoes = document.createElement('div');
        divAcoes.className = 'AcoesFlutuantes';

        const btnFavorito = document.createElement('button');
        btnFavorito.type = 'button';
        btnFavorito.className = 'BotaoAcaoCard BotaoFavoritoCard botao';
        btnFavorito.title = 'Remover dos favoritos';
        btnFavorito.setAttribute('aria-label', 'Desfavoritar');
        btnFavorito.setAttribute('data-cod', ev.cod_evento);
        btnFavorito.setAttribute('data-favorito', '1');
        btnFavorito.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        };
        const imgFavorito = document.createElement('img');
        imgFavorito.src = '../Imagens/Medalha_preenchida.svg';
        imgFavorito.alt = 'Desfavoritar';
        btnFavorito.appendChild(imgFavorito);
        divAcoes.appendChild(btnFavorito);

        const btnMensagem = document.createElement('button');
        btnMensagem.type = 'button';
        btnMensagem.className = 'BotaoAcaoCard BotaoMensagemCard botao';
        btnMensagem.title = 'Enviar mensagem ao organizador';
        btnMensagem.setAttribute('aria-label', 'Mensagem');
        btnMensagem.setAttribute('data-cod', ev.cod_evento);
        btnMensagem.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        };
        const imgMensagem = document.createElement('img');
        imgMensagem.src = '../Imagens/Carta.svg';
        imgMensagem.alt = 'Mensagem';
        btnMensagem.appendChild(imgMensagem);
        divAcoes.appendChild(btnMensagem);

        const btnCompartilhar = document.createElement('button');
        btnCompartilhar.type = 'button';
        btnCompartilhar.className = 'BotaoAcaoCard BotaoCompartilharCard botao';
        btnCompartilhar.title = 'Compartilhar';
        btnCompartilhar.setAttribute('aria-label', 'Compartilhar');
        btnCompartilhar.setAttribute('data-cod', ev.cod_evento);
        btnCompartilhar.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        };
        const imgCompartilhar = document.createElement('img');
        imgCompartilhar.src = '../Imagens/Icone_Compartilhar.svg';
        imgCompartilhar.alt = 'Compartilhar';
        btnCompartilhar.appendChild(imgCompartilhar);
        divAcoes.appendChild(btnCompartilhar);

        const divImagem = document.createElement('div');
        divImagem.className = 'favorito-item-imagem';
        const img = document.createElement('img');
        const caminho = '../' + (ev.imagem && ev.imagem !== '' ? ev.imagem.replace(/^\\/, '').replace(/^\//, '') : 'ImagensEventos/CEU-ImagemEvento.png');
        img.src = caminho;
        img.alt = (ev.nome || 'Evento').substring(0, 100);
        img.onerror = function() { this.src = '../ImagensEventos/CEU-ImagemEvento.png'; };
        divImagem.appendChild(img);

        const divTitulo = document.createElement('div');
        divTitulo.className = 'favorito-item-titulo';
        divTitulo.textContent = (ev.nome || 'Evento').substring(0, 100);

        const divInfo = document.createElement('div');
        divInfo.className = 'favorito-item-info';
        const ul = document.createElement('ul');
        ul.className = 'evento-info-list';

        const liCategoria = document.createElement('li');
        liCategoria.className = 'evento-info-item';
        const categoria = (ev.categoria || 'N/A').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        liCategoria.innerHTML = `<span class="evento-info-icone"><img src="../Imagens/info-categoria.svg" alt="" /></span><span class="evento-info-texto"><span class="evento-info-label">Categoria:</span> ${categoria}</span>`;
        ul.appendChild(liCategoria);

        const liModalidade = document.createElement('li');
        liModalidade.className = 'evento-info-item';
        const modalidade = (ev.modalidade || 'N/A').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        liModalidade.innerHTML = `<span class="evento-info-icone"><img src="../Imagens/info-modalidade.svg" alt="" /></span><span class="evento-info-texto"><span class="evento-info-label">Modalidade:</span> ${modalidade}</span>`;
        ul.appendChild(liModalidade);

        if (ev.inicio) {
            const liData = document.createElement('li');
            liData.className = 'evento-info-item';
            let dataFormatada = 'N/A';
            try {
                const data = new Date(ev.inicio);
                if (!isNaN(data.getTime())) {
                    dataFormatada = data.toLocaleDateString('pt-BR');
                }
            } catch (e) {
                console.error('Erro ao formatar data:', e);
            }
            liData.innerHTML = `<span class="evento-info-icone"><img src="../Imagens/info-data.svg" alt="" /></span><span class="evento-info-texto"><span class="evento-info-label">Data:</span> ${dataFormatada}</span>`;
            ul.appendChild(liData);
        }

        if (ev.lugar) {
            const liLocal = document.createElement('li');
            liLocal.className = 'evento-info-item';
            const lugar = (ev.lugar || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            liLocal.innerHTML = `<span class="evento-info-icone"><img src="../Imagens/info-local.svg" alt="" /></span><span class="evento-info-texto"><span class="evento-info-label">Local:</span> ${lugar}</span>`;
            ul.appendChild(liLocal);
        }

        const liCert = document.createElement('li');
        liCert.className = 'evento-info-item';
        liCert.innerHTML = `<span class="evento-info-icone"><img src="../Imagens/info-certificado.svg" alt="" /></span><span class="evento-info-texto"><span class="evento-info-label">Certificado:</span> ${ev.certificado == 1 ? 'Sim' : 'Não'}</span>`;
        ul.appendChild(liCert);

        divInfo.appendChild(ul);
        a.appendChild(divAcoes);
        a.appendChild(divImagem);
        a.appendChild(divTitulo);
        a.appendChild(divInfo);
        frag.appendChild(a);
    });
    cont.appendChild(frag);
}

function inicializarBotaoFavoritos() {
    const btnFavoritos = document.getElementById('btn-abrir-favoritos');
    if (btnFavoritos && !btnFavoritos.dataset.listenerAdicionado) {
        btnFavoritos.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            await carregarFavoritos();
            abrirModalFavoritos();
        });
        btnFavoritos.dataset.listenerAdicionado = 'true';
    }
}

// Listeners de clique para botões de ação
document.addEventListener('click', async function (e) {
    // Botão de mensagem
    const btnMsg = e.target.closest('.BotaoMensagemCard');
    if (btnMsg) {
        e.preventDefault(); e.stopPropagation();
        const cod = Number(btnMsg.getAttribute('data-cod')) || 0;
        if (!cod) return;
        window.codEventoMensagemOrganizador = cod;
        abrirModalMensagem();
        return;
    }

    // Botão de compartilhar
    const btnCompartilhar = e.target.closest('.BotaoCompartilharCard');
    if (btnCompartilhar) {
        e.preventDefault(); e.stopPropagation();
        const cod = Number(btnCompartilhar.getAttribute('data-cod')) || 0;
        if (!cod) return;
        window.codEventoOrganizador = cod;
        abrirModalCompartilhar();
        return;
    }

    // Toggle favorito
    const btnFav = e.target.closest('.BotaoFavoritoCard');
    if (btnFav) {
        e.preventDefault(); 
        e.stopPropagation();
        if (btnFav.dataset.processing === 'true') return;
        const cod = Number(btnFav.getAttribute('data-cod')) || 0;
        if (!cod) return;
        btnFav.dataset.processing = 'true';
        const estadoAtual = btnFav.getAttribute('data-favorito') === '1';
        const novoEstado = !estadoAtual;
        if (novoEstado) { 
            window.favoritosSetOrganizador.add(cod); 
        } else { 
            window.favoritosSetOrganizador.delete(cod);
            window.favoritosDadosOrganizador = window.favoritosDadosOrganizador.filter(f => Number(f.cod_evento) !== cod);
        }
        atualizarIconeFavorito(btnFav, novoEstado);
        // Atualizar TODOS os botões de favorito com o mesmo código na página (atualização imediata)
        // Buscar especificamente os botões que NÃO estão no modal de favoritos
        const atualizarTodosBotoes = () => {
            const modalFavoritos = document.getElementById('modal-favoritos');
            const todosBotoes = document.querySelectorAll('.BotaoFavoritoCard');
            let atualizados = 0;
            todosBotoes.forEach(btn => {
                if (btn === btnFav || btn.dataset.processing === 'true') return;
                const estaNoModal = modalFavoritos && modalFavoritos.contains(btn);
                const btnCod = Number(btn.getAttribute('data-cod')) || 0;
                if (btnCod === cod) {
                    if (modalFavoritos && modalFavoritos.contains(btnFav)) {
                        if (!estaNoModal) {
                            atualizarIconeFavorito(btn, novoEstado);
                            atualizados++;
                        }
                    } else {
                        if (estaNoModal) {
                            atualizarIconeFavorito(btn, novoEstado);
                            atualizados++;
                        }
                    }
                }
            });
            console.log(`Atualizados ${atualizados} botões de favorito para código ${cod}, novoEstado: ${novoEstado}`);
        };
        atualizarTodosBotoes();
        setTimeout(atualizarTodosBotoes, 100);
        setTimeout(atualizarTodosBotoes, 300);
        try {
            let timeoutId = null;
            const controller = new AbortController();
            timeoutId = setTimeout(() => controller.abort(), 10000);
            const basePath = `${window.location.origin}/CEU/PaginasGlobais/ToggleFavorito.php`;
            const r = await fetch(basePath, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                credentials: 'include',
                body: new URLSearchParams({ cod_evento: cod }),
                signal: controller.signal
            });
            if (timeoutId) clearTimeout(timeoutId);
            if (r.status === 401) { 
                if (estadoAtual) { window.favoritosSetOrganizador.add(cod); } else { window.favoritosSetOrganizador.delete(cod); }
                atualizarIconeFavorito(btnFav, estadoAtual);
                // Reverter TODOS os botões de favorito com o mesmo código na página
                document.querySelectorAll('.BotaoFavoritoCard').forEach(btn => {
                    const btnCod = Number(btn.getAttribute('data-cod')) || 0;
                    if (btnCod === cod && btn !== btnFav && !btn.dataset.processing) {
                        atualizarIconeFavorito(btn, estadoAtual);
                    }
                });
                alert('Faça login para favoritar eventos.'); 
            } else if (!r.ok) {
                const text = await r.text();
                console.error('Erro HTTP:', r.status, text);
                throw new Error(`HTTP error! status: ${r.status}`);
            } else {
                let j;
                try {
                    j = await r.json();
                } catch (parseErr) {
                    console.error('Erro ao fazer parse do JSON:', parseErr);
                    throw new Error('Resposta inválida do servidor');
                }
                if (j && j.sucesso) {
                    if (j.favoritado) { window.favoritosSetOrganizador.add(cod); } else { window.favoritosSetOrganizador.delete(cod); window.favoritosDadosOrganizador = window.favoritosDadosOrganizador.filter(f => Number(f.cod_evento) !== cod); }
                    atualizarIconeFavorito(btnFav, j.favoritado);
                    // Atualizar TODOS os botões de favorito com o mesmo código na página
                    // Buscar especificamente os botões que NÃO estão no modal de favoritos
                    const atualizarTodosBotoes = () => {
                        const modalFavoritos = document.getElementById('modal-favoritos');
                        const todosBotoes = document.querySelectorAll('.BotaoFavoritoCard');
                        let atualizados = 0;
                        todosBotoes.forEach(btn => {
                            if (btn === btnFav || btn.dataset.processing === 'true') return;
                            const estaNoModal = modalFavoritos && modalFavoritos.contains(btn);
                            const btnCod = Number(btn.getAttribute('data-cod')) || 0;
                            if (btnCod === cod) {
                                if (modalFavoritos && modalFavoritos.contains(btnFav)) {
                                    if (!estaNoModal) {
                                        atualizarIconeFavorito(btn, j.favoritado);
                                        atualizados++;
                                    }
                                } else {
                                    if (estaNoModal) {
                                        atualizarIconeFavorito(btn, j.favoritado);
                                        atualizados++;
                                    }
                                }
                            }
                        });
                        console.log(`Atualizados ${atualizados} botões de favorito para código ${cod}, favoritado: ${j.favoritado}`);
                    };
                    atualizarTodosBotoes();
                    setTimeout(atualizarTodosBotoes, 100);
                    setTimeout(atualizarTodosBotoes, 300);
                } else {
                    if (estadoAtual) { window.favoritosSetOrganizador.add(cod); } else { window.favoritosSetOrganizador.delete(cod); }
                    atualizarIconeFavorito(btnFav, estadoAtual);
                    // Reverter TODOS os botões de favorito com o mesmo código na página
                    document.querySelectorAll('.BotaoFavoritoCard').forEach(btn => {
                        const btnCod = Number(btn.getAttribute('data-cod')) || 0;
                        if (btnCod === cod && btn !== btnFav && !btn.dataset.processing) {
                            atualizarIconeFavorito(btn, estadoAtual);
                        }
                    });
                    alert(j.mensagem || 'Não foi possível atualizar favorito.');
                }
            }
        } catch (err) {
            if (estadoAtual) { window.favoritosSetOrganizador.add(cod); } else { window.favoritosSetOrganizador.delete(cod); }
            atualizarIconeFavorito(btnFav, estadoAtual);
            // Reverter TODOS os botões de favorito com o mesmo código na página
            document.querySelectorAll('.BotaoFavoritoCard').forEach(btn => {
                const btnCod = Number(btn.getAttribute('data-cod')) || 0;
                if (btnCod === cod && btn !== btnFav && !btn.dataset.processing) {
                    atualizarIconeFavorito(btn, estadoAtual);
                }
            });
            if (err.name !== 'AbortError') {
                console.error('Erro ao atualizar favorito:', err);
                alert('Erro ao atualizar favorito. Verifique sua conexão e tente novamente.');
            }
        } finally {
            btnFav.dataset.processing = 'false';
        }
        return;
    }

    // Abrir modal de favoritos (botão no topo)
    if (e.target.closest('#btn-abrir-favoritos')) {
        e.preventDefault(); e.stopPropagation();
        await carregarFavoritos();
        abrirModalFavoritos();
        return;
    }
}, true);

// Função para inicializar modais (chamada após carregamento via AJAX)
function inicializarModais() {
    // Fechar modal de favoritos ao clicar fora
    const modalFav = document.getElementById('modal-favoritos');
    if (modalFav) {
        modalFav.onclick = function (e) {
            if (e.target === this) fecharModalFavoritos();
        };
        const listaFavoritos = document.getElementById('lista-favoritos');
        if (listaFavoritos) {
            listaFavoritos.addEventListener('wheel', function (e) { e.stopPropagation(); }, { passive: false });
            listaFavoritos.addEventListener('touchmove', function (e) { e.stopPropagation(); }, { passive: false });
        }
    }
    
    // Fechar modal de compartilhar ao clicar fora
    const modalCompartilhar = document.getElementById('modal-compartilhar');
    if (modalCompartilhar) {
        modalCompartilhar.onclick = function (e) {
            if (e.target === this) {
                e.stopPropagation();
                fecharModalCompartilhar();
            }
        };
    }
}

// Inicializa modais imediatamente se já existirem
inicializarModais();

// Re-inicializa modais após carregamento via AJAX
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarModais);
} else {
    setTimeout(inicializarModais, 50);
}

// Fechar modais com ESC
document.addEventListener('keydown', function (e) { 
    if (e.key === 'Escape' || e.key === 'Esc') { 
        fecharModalCompartilhar();
        fecharModalMensagem(true); 
        fecharModalFavoritos();
    } 
});

// Carregar favoritos ao iniciar
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', carregarFavoritos);
} else {
    setTimeout(carregarFavoritos, 50);
}

// Inicializar botão de favoritos
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarBotaoFavoritos);
} else {
    inicializarBotaoFavoritos();
}
setTimeout(inicializarBotaoFavoritos, 100);

// Expor funções globalmente para serem chamadas após carregamento via AJAX
window.carregarFavoritos = carregarFavoritos;
window.inicializarBotaoFavoritos = inicializarBotaoFavoritos;
window.inicializarModais = inicializarModais;
window.fecharModalFavoritos = fecharModalFavoritos;
window.fecharModalMensagem = fecharModalMensagem;
window.enviarMensagemOrganizador = enviarMensagemOrganizador;
window.abrirModalCompartilhar = abrirModalCompartilhar;
window.fecharModalCompartilhar = fecharModalCompartilhar;
window.copiarLink = copiarLink;
window.compartilharWhatsApp = compartilharWhatsApp;
window.compartilharInstagram = compartilharInstagram;
window.compartilharEmail = compartilharEmail;
window.compartilharX = compartilharX;
