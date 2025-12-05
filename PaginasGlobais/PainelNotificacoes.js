// Painel de Notificações

if (typeof notificacoesGlobal === 'undefined') {
    var notificacoesGlobal = [];
}
if (typeof filtroAtual === 'undefined') {
    var filtroAtual = 'todas';
}
if (typeof intervalID === 'undefined') {
    var intervalID = null;
}

// Inicialização
function inicializarPainel() {

    // Verifica se elementos existem
    const btnVoltar = document.getElementById('btn-voltar');
    const btnsFiltro = document.querySelectorAll('.btn-filtro');
    const lista = document.getElementById('lista-notificacoes');

    if (!lista) {
        console.error('❌ Elemento lista-notificacoes não encontrado!');
        return;
    }

    // Event listener - Botão Voltar
    if (btnVoltar) {
        btnVoltar.addEventListener('click', () => {
            window.history.back();
        });
    }

    // Event listeners - Filtros
    btnsFiltro.forEach(btn => {
        btn.addEventListener('click', function () {
            // Remove ativo de todos
            btnsFiltro.forEach(b => b.classList.remove('ativo'));
            // Adiciona ao clicado
            this.classList.add('ativo');
            // Atualiza filtro
            filtroAtual = this.getAttribute('data-tipo');
            // Reexibe notificações
            mostrarNotificacoes();
        });
    });

    // Carrega notificações iniciais
    carregarNotificacoes();

    // Atualização automática a cada 15 segundos
    if (intervalID) {
        clearInterval(intervalID);
    }
    intervalID = setInterval(() => {
        carregarNotificacoes();
    }, 15000);
}

// Expõe função globalmente para ser chamada pelos Containers
window.inicializarPainelNotificacoes = inicializarPainel;

// Aguarda DOM estar pronto (apenas se carregado diretamente)
function aguardarDOMPainel() {
    const lista = document.getElementById('lista-notificacoes');
    if (lista) {
        inicializarPainel();
    } else {
        setTimeout(aguardarDOMPainel, 50);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', aguardarDOMPainel);
} else {
    // DOM já está pronto, mas elementos podem não estar (carregamento AJAX)
    aguardarDOMPainel();
}

// Carregar notificações do servidor
function carregarNotificacoes() {
    fetch('../PaginasGlobais/BuscarNotificacoes.php?todas=true')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.sucesso && Array.isArray(data.notificacoes)) {
                notificacoesGlobal = data.notificacoes;
                mostrarNotificacoes();
            } else {
                console.warn('⚠️ Resposta inválida:', data);
                mostrarVazio('Erro ao carregar notificações');
            }
        })
        .catch(erro => {
            console.error('❌ Erro ao buscar notificações:', erro);
            mostrarVazio('Erro de conexão');
        });
}

// Exibir notificações na tela
function mostrarNotificacoes() {
    const container = document.getElementById('lista-notificacoes');
    const contador = document.getElementById('contador-notificacoes');

    if (!container) {
        console.error('❌ Container não encontrado!');
        return;
    }

    // Aplica filtro
    let notificacoesFiltradas = notificacoesGlobal;
    if (filtroAtual !== 'todas') {
        notificacoesFiltradas = notificacoesGlobal.filter(n => n.tipo === filtroAtual);
    }

    // Lista vazia
    if (notificacoesFiltradas.length === 0) {
        mostrarVazio(
            filtroAtual === 'todas'
                ? 'Nenhuma notificação por aqui!'
                : 'Nenhuma notificação deste tipo'
        );
        if (contador) contador.textContent = '0 não lidas';
        return;
    }

    // Gera HTML
    let html = '';
    let naoLidas = 0;

    notificacoesFiltradas.forEach(notif => {
        const lida = notif.lida == 1;
        if (!lida) naoLidas++;

        const tipoClass = notif.tipo.replace(/_/g, '-');
        const isMensagemParticipante = notif.tipo === 'mensagem_participante';
        const isMensagemOrganizador = notif.tipo === 'mensagem_organizador';
        const isMensagem = isMensagemParticipante || isMensagemOrganizador;

        // Extrai dados se for mensagem de participante ou organizador
        let cpfRemetente = null;
        let nomeRemetente = '';
        let nomeEvento = ''; // Para participante é evento, para organizador é título
        let mensagemTexto = '';
        let codEvento = notif.cod_evento || null;

        if (isMensagem) {
            // Formato: CPF|||NOME|||EVENTO/TÍTULO|||MENSAGEM/CONTEÚDO
            const partes = notif.mensagem.split('|||');
            if (partes.length >= 4) {
                cpfRemetente = partes[0];
                nomeRemetente = partes[1];
                nomeEvento = partes[2];
                mensagemTexto = partes.slice(3).join('|||'); // Junta caso a mensagem contenha |||
            }
        }

        // Formata mensagem HTML se for mensagem de participante ou organizador
        let mensagemFormatada = notif.mensagem;
        let carregandoThread = false;
        if (isMensagem && cpfRemetente) {
            // Inicialmente mostra mensagem simples, depois carrega thread se for de participante
            mensagemFormatada = formatarMensagemSimples(cpfRemetente, nomeRemetente, nomeEvento, mensagemTexto, codEvento);
            carregandoThread = isMensagemParticipante; // Só carrega thread para mensagens de participante
        }

        html += `
            <div class="notificacao-item ${lida ? 'lida' : ''}" data-id="${notif.id}" data-notif-id="${notif.id}" data-cod-evento="${codEvento || ''}" onclick="irParaEvento(${codEvento || 0}, ${notif.id})" style="cursor: pointer;">
                <div class="notificacao-topo">
                    <span class="notificacao-tipo-badge ${tipoClass}">
                        ${traduzirTipo(notif.tipo, true)}
                    </span>
                    <button class="btn-excluir-notificacao" onclick="excluirNotificacao(${notif.id})" title="Excluir notificação" aria-label="Excluir">
                        ×
                    </button>
                </div>
                <div class="notificacao-mensagem" id="mensagem-${notif.id}">
                    ${mensagemFormatada}
                </div>
                <div class="notificacao-rodape" id="rodape-${notif.id}">
                    <span class="notificacao-data">
                        ${formatarData(notif.data_criacao)}
                    </span>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        ${isMensagemParticipante && cpfRemetente && codEvento
                ? `<button class="btn-responder-mensagem" data-cpf="${escapeHtml(cpfRemetente)}" data-evento="${codEvento}" data-mensagem-original="${escapeHtml(mensagemTexto).replace(/"/g, '&quot;')}" onclick="const btn = this; responderMensagemParticipante(btn.dataset.cpf, btn.dataset.evento, btn.dataset.mensagemOriginal)">
                                Responder
                               </button>`
                : ''
            }
                        ${!lida
                ? `<button class="btn-marcar-lida" onclick="marcarComoLida(${notif.id})">
                                Marcar como lida
                               </button>`
                : '<span style="color: var(--azul-claro);">✓ Lida</span>'
            }
                    </div>
                </div>
            </div>
        `;
    });

    // Insere no DOM
    container.innerHTML = html;

    // Carrega threads para mensagens de participante
    notificacoesFiltradas.forEach(notif => {
        if (notif.tipo === 'mensagem_participante') {
            carregarThreadMensagem(notif.id);
        }
    });

    // Atualiza contador
    if (contador) {
        contador.textContent = naoLidas === 0
            ? 'Tudo lido!'
            : `${naoLidas} não lida${naoLidas > 1 ? 's' : ''}`;
    }
}

// Função para carregar thread de mensagem
async function carregarThreadMensagem(notifId) {
    try {
        const response = await fetch(`../PaginasGlobais/BuscarThreadMensagem.php?notificacao_id=${notifId}`);
        const data = await response.json();

        if (data.sucesso && data.thread && data.thread.length > 0) {
            const containerMsg = document.getElementById(`mensagem-${notifId}`);
            const rodapeNotif = document.getElementById(`rodape-${notifId}`);
            if (!containerMsg) return;

            // Busca o cod_evento da resposta ou da notificação
            const codEventoNotif = data.cod_evento || '';
            const notifItem = document.querySelector(`[data-notif-id="${notifId}"]`);
            const codEventoFromItem = notifItem ? (notifItem.dataset.codEvento || '') : '';
            const codEventoThread = codEventoNotif || codEventoFromItem;

            // Extrai dados da primeira mensagem para o cabeçalho
            const primeiraMsg = data.thread[0];
            const ultimaMsg = data.thread[data.thread.length - 1];
            const nomeEvento = primeiraMsg.nome_evento || 'Evento';

            // Determina os participantes da conversa
            const mensagemOutro = data.thread.find(m => !m.eh_minha);
            const nomeOutro = mensagemOutro ? mensagemOutro.nome_remetente : 'Usuário';

            // Para responder, usa o CPF do remetente da última mensagem (se não for do usuário)
            let cpfParaResponder = '';
            if (ultimaMsg.eh_minha) {
                cpfParaResponder = primeiraMsg.cpf_destinatario;
            } else {
                cpfParaResponder = ultimaMsg.cpf_remetente;
            }

            // Mostra apenas a última mensagem por padrão (colapsado)
            const mensagemUltimaEscapada = nl2br(escapeHtml(ultimaMsg.mensagem));
            const dataUltimaMsg = formatarData(ultimaMsg.data_criacao);
            const nomeUltimaMsg = ultimaMsg.eh_minha ? 'Você' : escapeHtml(ultimaMsg.nome_remetente || 'Usuário');

            let threadHTML = '<div class="notif-mensagem-participante">';

            // Cabeçalho compacto
            threadHTML += '<div class="notif-cabecalho-thread-compacto">';
            threadHTML += '<div class="notif-evento-thread">';
            threadHTML += '<strong>📧 ' + escapeHtml(nomeEvento) + '</strong>';
            threadHTML += '<span class="notif-thread-contador">' + data.thread.length + ' mensagem' + (data.thread.length > 1 ? 's' : '') + '</span>';
            threadHTML += '</div>';
            threadHTML += '</div>';

            // Última mensagem (sempre visível)
            threadHTML += '<div class="notif-ultima-mensagem">';
            threadHTML += '<div class="notif-mensagem-thread-item ' + (ultimaMsg.eh_minha ? 'minha-mensagem' : 'outra-mensagem') + '">';
            threadHTML += '<div class="notif-mensagem-thread-cabecalho">';
            threadHTML += '<div class="notif-mensagem-thread-remetente">';
            threadHTML += '<strong>' + nomeUltimaMsg + '</strong>';
            threadHTML += '<small>' + dataUltimaMsg + '</small>';
            threadHTML += '</div>';
            threadHTML += '</div>';
            threadHTML += '<div class="notif-mensagem-thread-conteudo">';
            threadHTML += mensagemUltimaEscapada;
            threadHTML += '</div>';
            threadHTML += '</div>';
            threadHTML += '</div>';

            // Botão de responder (após a última mensagem)
            const mensagemUltima = ultimaMsg.mensagem;
            threadHTML += '<div class="notif-thread-acoes" style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid rgba(255,255,255,0.1);">';
            threadHTML += '<button class="btn-responder-mensagem" data-cpf="' + escapeHtml(cpfParaResponder) + '" data-evento="' + codEventoThread + '" data-mensagem-original="' + escapeHtml(mensagemUltima).replace(/"/g, '&quot;') + '" onclick="const btn = this; responderMensagemParticipante(btn.dataset.cpf, btn.dataset.evento, btn.dataset.mensagemOriginal)">';
            threadHTML += 'Responder';
            threadHTML += '</button>';
            threadHTML += '</div>';

            // Botão para expandir/colapsar (só aparece se houver mais de 1 mensagem)
            if (data.thread.length > 1) {
                threadHTML += '<button class="btn-expandir-thread" onclick="toggleThread(' + notifId + ')" data-thread-id="' + notifId + '">';
                threadHTML += '<span class="thread-icon">▼</span> Ver conversa completa (' + (data.thread.length - 1) + ' mensagem' + (data.thread.length > 2 ? 's' : '') + ' anterior' + (data.thread.length > 2 ? 'es' : '') + ')';
                threadHTML += '</button>';
            }

            // Thread completa (oculta por padrão)
            threadHTML += '<div class="notif-thread-completa" id="thread-completa-' + notifId + '" style="display: none;">';
            threadHTML += '<div class="notif-thread-mensagens">';

            data.thread.forEach((msg, index) => {
                // Pula a última mensagem pois já foi exibida
                if (index === data.thread.length - 1) return;

                const ehMinha = msg.eh_minha;
                const nomeMsg = msg.nome_remetente || 'Usuário';
                const dataMsg = formatarData(msg.data_criacao);
                const mensagemEscapada = nl2br(escapeHtml(msg.mensagem));

                threadHTML += '<div class="notif-mensagem-thread-item ' + (ehMinha ? 'minha-mensagem' : 'outra-mensagem') + '">';
                threadHTML += '<div class="notif-mensagem-thread-cabecalho">';
                threadHTML += '<div class="notif-mensagem-thread-remetente">';
                threadHTML += '<strong>' + (ehMinha ? 'Você' : escapeHtml(nomeMsg)) + '</strong>';
                threadHTML += '<small>' + dataMsg + '</small>';
                threadHTML += '</div>';
                threadHTML += '</div>';
                threadHTML += '<div class="notif-mensagem-thread-conteudo">';
                threadHTML += mensagemEscapada;
                threadHTML += '</div>';
                threadHTML += '</div>';

                // Linha separadora entre mensagens
                if (index < data.thread.length - 2) {
                    threadHTML += '<div class="notif-thread-separador"></div>';
                }
            });

            threadHTML += '</div>'; // Fim thread-mensagens
            threadHTML += '</div>'; // Fim thread-completa

            // Remove botão responder antigo do rodapé (para evitar duplicação)
            if (rodapeNotif) {
                const botoesDiv = rodapeNotif.querySelector('div[style*="display: flex"]');
                if (botoesDiv) {
                    const btnResponderAntigo = botoesDiv.querySelector('.btn-responder-mensagem');
                    if (btnResponderAntigo) {
                        btnResponderAntigo.remove();
                    }
                }
            }

            threadHTML += '</div>'; // Fim notif-mensagem-participante

            containerMsg.innerHTML = threadHTML;
        }
    } catch (error) {
        console.error('Erro ao carregar thread:', error);
    }
}

// Função para expandir/colapsar thread
function toggleThread(notifId) {
    const threadCompleta = document.getElementById(`thread-completa-${notifId}`);
    const btnExpandir = document.querySelector(`[data-thread-id="${notifId}"]`);

    if (!threadCompleta || !btnExpandir) return;

    const estaExpandida = threadCompleta.style.display !== 'none';

    if (estaExpandida) {
        threadCompleta.style.display = 'none';
        btnExpandir.querySelector('.thread-icon').textContent = '▼';
        const texto = btnExpandir.textContent.replace(/Ver menos|Ver conversa completa/, '');
        const contador = threadCompleta.querySelectorAll('.notif-mensagem-thread-item').length;
        btnExpandir.innerHTML = '<span class="thread-icon">▼</span> Ver conversa completa (' + contador + ' mensagem' + (contador > 1 ? 's' : '') + ' anterior' + (contador > 1 ? 'es' : '') + ')';
    } else {
        threadCompleta.style.display = 'block';
        btnExpandir.querySelector('.thread-icon').textContent = '▲';
        btnExpandir.innerHTML = '<span class="thread-icon">▲</span> Ver menos';
    }
}

// Mensagem de lista vazia
function mostrarVazio(mensagem) {
    const container = document.getElementById('lista-notificacoes');
    if (container) {
        container.innerHTML = `
            <div class="painel-vazio">
                ${mensagem}
            </div>
        `;
    }
}

// Excluir notificações
function excluirNotificacao(id) {
    if (!confirm('Tem certeza que deseja excluir esta notificação?')) {
        return;
    }

    fetch('../PaginasGlobais/ExcluirNotificacao.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: id
        })
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.sucesso) {
                // Remove a notificação do DOM
                const notifItem = document.querySelector(`[data-id="${id}"]`);
                if (notifItem) {
                    notifItem.style.transition = 'opacity 0.3s ease';
                    notifItem.style.opacity = '0';
                    setTimeout(() => {
                        notifItem.remove();
                        // Verifica se não há mais notificações
                        const container = document.getElementById('lista-notificacoes');
                        if (container && container.children.length === 0) {
                            mostrarVazio('Nenhuma notificação encontrada.');
                        }
                        // Recarrega contador
                        if (typeof carregarNotificacoes === 'function') {
                            carregarNotificacoes();
                        }
                    }, 300);
                }
            } else {
                alert('Erro ao excluir notificação: ' + (data.erro || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro ao excluir notificação:', error);
            alert('Erro ao excluir notificação. Tente novamente.');
        });
}

// Excluir notificação
function excluirNotificacao(id) {
    if (!confirm('Tem certeza que deseja excluir esta notificação?')) {
        return;
    }

    fetch('../PaginasGlobais/ExcluirNotificacao.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: id
        })
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.sucesso) {
                // Remove a notificação do DOM
                const notifItem = document.querySelector(`[data-id="${id}"]`);
                if (notifItem) {
                    notifItem.style.transition = 'opacity 0.3s ease';
                    notifItem.style.opacity = '0';
                    setTimeout(() => {
                        notifItem.remove();
                        // Verifica se não há mais notificações
                        const container = document.getElementById('lista-notificacoes');
                        if (container && container.children.length === 0) {
                            mostrarVazio('Nenhuma notificação encontrada.');
                        }
                        // Recarrega contador
                        if (typeof carregarNotificacoes === 'function') {
                            carregarNotificacoes();
                        }
                    }, 300);
                }
            } else {
                alert('Erro ao excluir notificação: ' + (data.erro || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro ao excluir notificação:', error);
            alert('Erro ao excluir notificação. Tente novamente.');
        });
}

// Marcar notificação como lida
function marcarComoLida(id) {
    // Verifica se o endpoint existe
    const endpoint = '../PaginasGlobais/AtualizarNotificacao.php';

    fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: id,
            lida: true
        })
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.sucesso) {
                // Atualiza localmente
                const notif = notificacoesGlobal.find(n => n.id == id);
                if (notif) {
                    notif.lida = 1;
                }
                // Atualiza interface
                mostrarNotificacoes();
            } else {
                console.error('❌ Erro do servidor:', data.mensagem || data);
                alert('Erro ao marcar como lida. Tente novamente.');
            }
        })
        .catch(erro => {
            console.error('❌ Erro ao marcar como lida:', erro);
            alert('Erro de conexão. Tente novamente.');
        });
}

// Funções auxiliares

// Traduz tipo de notificação
function traduzirTipo(tipo, apenasTexto = false) {
    const tipos = {
        'inscricao': apenasTexto ? 'Inscrição' : '<img src="../Imagens/notif-inscricao.svg" class="notif-icon-badge"> Inscrição',
        'desinscricao': apenasTexto ? 'Desincrição' : '<img src="../Imagens/notif-desinscricao.svg" class="notif-icon-badge"> Desincrição',
        'evento_cancelado': apenasTexto ? 'Cancelado' : '<img src="../Imagens/notif-cancelado.svg" class="notif-icon-badge"> Cancelado',
        'evento_prestes_iniciar': apenasTexto ? 'Iniciando' : '<img src="../Imagens/notif-relogio.svg" class="notif-icon-badge"> Iniciando',
        'novo_participante': apenasTexto ? 'Novo Participante' : '<img src="../Imagens/notif-usuario.svg" class="notif-icon-badge"> Novo Participante',
        'mensagem_participante': apenasTexto ? 'Mensagem' : '<img src="../Imagens/Carta.svg" class="notif-icon-badge" style="width: 16px; height: 16px; filter: invert(1);"> Mensagem',
        'solicitacao_colaborador': apenasTexto ? 'Solicitação de colaboração' : '<img src="../Imagens/notif-geral.svg" class="notif-icon-badge"> Solicitação de colaboração',
        'colaboracao_aprovada': apenasTexto ? 'Colaboração aprovada' : '<img src="../Imagens/notif-geral.svg" class="notif-icon-badge"> Colaboração aprovada',
        'colaboracao_recusada': apenasTexto ? 'Colaboração recusada' : '<img src="../Imagens/notif-geral.svg" class="notif-icon-badge"> Colaboração recusada',
        'colaborador_adicionado': apenasTexto ? 'Adicionado como colaborador' : '<img src="../Imagens/notif-geral.svg" class="notif-icon-badge"> Adicionado como colaborador',
        'colaborador_removido': apenasTexto ? 'Removido de colaboração' : '<img src="../Imagens/notif-geral.svg" class="notif-icon-badge"> Removido de colaboração'
    };
    return tipos[tipo] || tipo;
}

// Variáveis globais para o modal de resposta
if (typeof cpfRemetenteAtual === 'undefined') {
    window.cpfRemetenteAtual = null;
    window.codEventoAtual = null;
    window.mensagemOriginalAtual = null;
}

// Função para responder mensagem de participante (exposta globalmente)
window.responderMensagemParticipante = function (cpfRemetente, codEvento, mensagemOriginal) {
    // Limpa o CPF (remove formatação se houver) e garante que é string
    const cpfLimpo = String(cpfRemetente).replace(/\D/g, '');
    const codEventoInt = parseInt(codEvento, 10);

    if (!cpfLimpo || cpfLimpo.length !== 11 || !codEventoInt || codEventoInt <= 0) {
        alert('Erro: Dados inválidos para responder a mensagem.');
        console.error('CPF:', cpfLimpo, 'CodEvento:', codEventoInt);
        return;
    }

    window.cpfRemetenteAtual = cpfLimpo;
    window.codEventoAtual = codEventoInt;
    window.mensagemOriginalAtual = mensagemOriginal || '';

    // Aguarda um pouco para garantir que o DOM está pronto
    setTimeout(() => {
        // Preenche o campo CPF (somente leitura)
        const inputCPF = document.getElementById('resposta-cpf-destinatario');
        if (inputCPF) {
            // Remove formatação se houver e formata novamente
            const cpfLimpo = cpfRemetente.replace(/\D/g, '');
            const cpfFormatado = cpfLimpo.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            inputCPF.value = cpfFormatado;
        }

        // Exibe mensagem original se existir
        const grupoMensagemOriginal = document.getElementById('grupo-mensagem-original');
        const mensagemOriginalDiv = document.getElementById('resposta-mensagem-original');
        if (mensagemOriginalDiv && grupoMensagemOriginal) {
            if (mensagemOriginalAtual) {
                mensagemOriginalDiv.innerHTML = nl2br(escapeHtml(mensagemOriginalAtual));
                grupoMensagemOriginal.style.display = 'block';
            } else {
                grupoMensagemOriginal.style.display = 'none';
            }
        }

        // Limpa campos do formulário (exceto o CPF e mensagem original)
        const tituloInput = document.getElementById('resposta-titulo');
        const conteudoTextarea = document.getElementById('resposta-conteudo');
        if (tituloInput) tituloInput.value = '';
        if (conteudoTextarea) conteudoTextarea.value = '';
        atualizarContadorResposta();

        // Abre o modal
        const modal = document.getElementById('modal-resposta-mensagem');
        if (modal) {
            modal.classList.add('ativo');
            bloquearScrollModal();
        }
    }, 50);
};

// Função para fechar modal de resposta
window.fecharModalResposta = function () {
    const modal = document.getElementById('modal-resposta-mensagem');
    if (modal) {
        modal.classList.remove('ativo');
        desbloquearScrollModal();
    }
    window.cpfRemetenteAtual = null;
    codEventoAtual = null;
    mensagemOriginalAtual = null;

    // Esconde mensagem original ao fechar
    const grupoMensagemOriginal = document.getElementById('grupo-mensagem-original');
    if (grupoMensagemOriginal) {
        grupoMensagemOriginal.style.display = 'none';
    }
};

// Função para atualizar contador de caracteres
function atualizarContadorResposta() {
    const textarea = document.getElementById('resposta-conteudo');
    const contador = document.getElementById('contador-resposta');
    if (!textarea || !contador) return;
    const comprimento = textarea.value.length;
    const maximo = 500;
    contador.textContent = `${comprimento} / ${maximo}`;
    if (comprimento >= maximo) {
        contador.classList.add('limite-alcancado');
    } else {
        contador.classList.remove('limite-alcancado');
    }
}

// Função para enviar resposta (exposta globalmente)
window.enviarRespostaMensagem = async function (event) {
    event.preventDefault();

    if (!window.cpfRemetenteAtual || !window.codEventoAtual) {
        alert('Erro: Dados da mensagem não encontrados.');
        return;
    }

    const titulo = document.getElementById('resposta-titulo').value.trim();
    const conteudo = document.getElementById('resposta-conteudo').value.trim();

    if (!titulo || !conteudo) {
        alert('Preencha todos os campos obrigatórios.');
        return;
    }

    try {
        // Limpa o CPF (remove formatação se houver)
        const cpfLimpo = window.cpfRemetenteAtual.replace(/\D/g, '');

        // Verifica se o usuário é organizador ou participante pela URL
        const isOrganizador = window.location.pathname.includes('Organizador');

        let response;
        if (isOrganizador) {
            // Se for organizador, usa GerenciarEvento.php
            response = await fetch('../PaginasOrganizador/GerenciarEvento.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                credentials: 'include',
                body: new URLSearchParams({
                    action: 'enviar_notificacao_cpf',
                    cod_evento: codEventoAtual,
                    cpf_destinatario: cpfLimpo,
                    titulo: titulo,
                    conteudo: conteudo,
                    eh_resposta: '1',
                    mensagem_original: mensagemOriginalAtual || ''
                })
            });
        } else {
            // Se for participante, usa EnviarMensagemOrganizador.php com CPF específico
            response = await fetch('../PaginasGlobais/EnviarMensagemOrganizador.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                credentials: 'include',
                body: new URLSearchParams({
                    cod_evento: codEventoAtual,
                    mensagem: conteudo,
                    cpf_organizador_destino: cpfLimpo, // Novo parâmetro para CPF específico
                    eh_resposta: '1',
                    mensagem_original: mensagemOriginalAtual || ''
                })
            });
        }

        if (!response.ok) {
            throw new Error('Erro na resposta do servidor: ' + response.status);
        }

        const data = await response.json();

        if (data.sucesso) {
            alert('Resposta enviada com sucesso!');
            fecharModalResposta();
            // Recarrega notificações para atualizar a lista
            if (typeof carregarNotificacoes === 'function') {
                carregarNotificacoes();
            }
        } else {
            let mensagemErro = 'Erro ao enviar resposta.';
            if (data.erro === 'usuario_nao_encontrado' || data.mensagem && data.mensagem.includes('não encontrado')) {
                mensagemErro = 'Organizador não encontrado para este evento.';
            } else if (data.erro === 'sem_permissao') {
                mensagemErro = 'Você não tem permissão para enviar mensagens para este evento.';
            } else if (data.mensagem) {
                mensagemErro = data.mensagem;
            } else if (data.erro) {
                mensagemErro = 'Erro: ' + data.erro;
            }
            alert(mensagemErro);
            console.error('Erro ao enviar resposta:', data);
        }
    } catch (error) {
        console.error('Erro ao enviar resposta:', error);
        alert('Erro ao enviar resposta. Verifique sua conexão e tente novamente.\n\nDetalhes: ' + error.message);
    }
};

// Funções para bloquear/desbloquear scroll do modal
function bloquearScrollModal() {
    document.body.style.overflow = 'hidden';
}

function desbloquearScrollModal() {
    document.body.style.overflow = '';
}

// Adiciona listener para atualizar contador em tempo real
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
        const textarea = document.getElementById('resposta-conteudo');
        if (textarea) {
            textarea.addEventListener('input', atualizarContadorResposta);
        }

        // Fechar modal ao clicar fora
        const modal = document.getElementById('modal-resposta-mensagem');
        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === this) {
                    fecharModalResposta();
                }
            });
        }

        // Fechar modal com ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' || e.key === 'Esc') {
                const modal = document.getElementById('modal-resposta-mensagem');
                if (modal && modal.classList.contains('ativo')) {
                    fecharModalResposta();
                }
            }
        });
    });
} else {
    const textarea = document.getElementById('resposta-conteudo');
    if (textarea) {
        textarea.addEventListener('input', atualizarContadorResposta);
    }

    const modal = document.getElementById('modal-resposta-mensagem');
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === this) {
                fecharModalResposta();
            }
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' || e.key === 'Esc') {
            const modal = document.getElementById('modal-resposta-mensagem');
            if (modal && modal.classList.contains('ativo')) {
                fecharModalResposta();
            }
        }
    });
}

// Funções auxiliares para escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function nl2br(text) {
    return text.replace(/\n/g, '<br>');
}

// Função para formatar mensagem simples (fallback)
function mascararCPF(cpf) {
    // Remove caracteres não numéricos
    const apenasNumeros = cpf.replace(/\D/g, '');
    if (apenasNumeros.length < 5) return cpf;

    // Mostra apenas os 3 primeiros e 2 últimos dígitos
    const primeiros3 = apenasNumeros.substring(0, 3);
    const ultimos2 = apenasNumeros.substring(apenasNumeros.length - 2);
    return `${primeiros3}.***.**-${ultimos2}`;
}

function formatarMensagemSimples(cpfRemetente, nomeRemetente, nomeEvento, mensagemTexto, codEvento) {
    const nomeRemetenteEscapado = escapeHtml(nomeRemetente);
    const nomeEventoEscapado = escapeHtml(nomeEvento);
    const cpfMascarado = mascararCPF(cpfRemetente);
    const mensagemEscapada = nl2br(escapeHtml(mensagemTexto));

    // Trunca mensagem se muito longa para preview
    let mensagemPreview = mensagemTexto;
    if (mensagemPreview.length > 150) {
        mensagemPreview = mensagemPreview.substring(0, 147) + '...';
    }
    const mensagemPreviewEscapada = nl2br(escapeHtml(mensagemPreview));

    return '<div class="notif-mensagem-participante" data-cpf-remetente="' + escapeHtml(cpfRemetente) + '" data-cod-evento="' + codEvento + '">' +
        '<div style="background: rgba(101, 152, 210, 0.15); padding: 0.8rem; border-radius: 0.4rem; border-left: 3px solid #6598D2; margin-bottom: 0.8rem;">' +
        '<strong style="color: #6598D2; font-size: 0.95rem;">📧 ' + nomeRemetenteEscapado + '</strong>' +
        '<div style="font-size: 0.85rem; opacity: 0.8; margin-top: 4px;">CPF: <strong>' + cpfMascarado + '</strong> em <strong style="color: #FFF;">' + nomeEventoEscapado + '</strong></div>' +
        '</div>' +
        '<div style="background: rgba(0, 0, 0, 0.2); padding: 1rem; border-radius: 0.4rem; border: 1px solid rgba(255, 255, 255, 0.1);">' +
        '<div style="font-size: 0.9rem; line-height: 1.6; color: #FFF; white-space: pre-wrap; word-wrap: break-word;">' + mensagemPreviewEscapada + '</div>' +
        '</div>' +
        '</div>';
}

// Formata data de forma amigável
function formatarData(dataStr) {
    try {
        const data = new Date(dataStr);
        const agora = new Date();
        const diffMs = agora - data;
        const diffMin = Math.floor(diffMs / 60000);
        const diffHora = Math.floor(diffMs / 3600000);
        const diffDia = Math.floor(diffMs / 86400000);

        if (diffMin < 1) return 'Agora mesmo';
        if (diffMin < 60) return `Há ${diffMin} minuto${diffMin > 1 ? 's' : ''}`;
        if (diffHora < 24) return `Há ${diffHora} hora${diffHora > 1 ? 's' : ''}`;
        if (diffDia < 7) return `Há ${diffDia} dia${diffDia > 1 ? 's' : ''}`;

        // Mais de 7 dias: exibe data completa
        return data.toLocaleDateString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        console.error('Erro ao formatar data:', e);
        return dataStr;
    }
}

// Limpeza ao sair da página
window.addEventListener('beforeunload', () => {
    if (intervalID) {
        clearInterval(intervalID);
    }
});

// Função para redirecionar ao clicar na notificação
function irParaEvento(codEvento, notifId) {
    if (!codEvento || codEvento === 0) return;

    // Marca notificação como lida
    marcarComoLida(notifId);

    // Redireciona para o evento
    const tipoUsuario = window.location.pathname.includes('Participante') ? 'Participante' :
        window.location.pathname.includes('Organizador') ? 'Organizador' : 'Publicas';

    let urlEvento = '';
    if (tipoUsuario === 'Participante') {
        urlEvento = `../PaginasParticipante/ContainerParticipante.php?pagina=evento&id=${codEvento}`;
    } else if (tipoUsuario === 'Organizador') {
        urlEvento = `../PaginasOrganizador/ContainerOrganizador.php?pagina=evento&id=${codEvento}`;
    } else {
        urlEvento = `../PaginasPublicas/EventoPublico.php?codEvento=${codEvento}`;
    }

    window.location.href = urlEvento;
}