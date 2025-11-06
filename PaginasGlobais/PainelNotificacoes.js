// ==================================================
// PAINEL DE NOTIFICAÇÕES - SISTEMA COMPLETO
// ==================================================

let notificacoesGlobal = [];
let filtroAtual = 'todas';
let intervalID = null;

// Log inicial desabilitado para reduzir ruído no console

// ==================================================
// INICIALIZAÇÃO
// ==================================================
function inicializarPainel() {
    // Log inicial desabilitado para reduzir ruído no console
    
    // Verifica se elementos existem
    const btnVoltar = document.getElementById('btn-voltar');
    const btnsFiltro = document.querySelectorAll('.btn-filtro');
    const lista = document.getElementById('lista-notificacoes');
    
    if (!lista) {
        console.error('❌ Elemento lista-notificacoes não encontrado!');
        return;
    }
    
    // Log desabilitado para reduzir ruído no console
    
    // Event listener - Botão Voltar
    if (btnVoltar) {
        btnVoltar.addEventListener('click', () => {
            window.history.back();
        });
    }
    
    // Event listeners - Filtros
    btnsFiltro.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove ativo de todos
            btnsFiltro.forEach(b => b.classList.remove('ativo'));
            // Adiciona ao clicado
            this.classList.add('ativo');
            // Atualiza filtro
            filtroAtual = this.getAttribute('data-tipo');
            // Log desabilitado para reduzir ruído no console
            // Reexibe notificações
            mostrarNotificacoes();
        });
    });
    
    // Log desabilitado para reduzir ruído no console
    
    // Carrega notificações iniciais
    carregarNotificacoes();
    
    // Atualização automática a cada 15 segundos
    if (intervalID) {
        clearInterval(intervalID);
    }
    intervalID = setInterval(() => {
        // Log desabilitado para reduzir ruído no console
        carregarNotificacoes();
    }, 15000);
    
    // Log desabilitado para reduzir ruído no console
}

// Expõe função globalmente para ser chamada pelos Containers
window.inicializarPainelNotificacoes = inicializarPainel;

// Aguarda DOM estar pronto (apenas se carregado diretamente)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarPainel);
} else {
    // DOM já está pronto, espera um tick
    setTimeout(inicializarPainel, 100);
}

// ==================================================
// CARREGAR NOTIFICAÇÕES DO SERVIDOR
// ==================================================
function carregarNotificacoes() {
    // Log desabilitado para reduzir ruído no console
    
    fetch('../PaginasGlobais/BuscarNotificacoes.php?todas=true')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Log desabilitado para reduzir ruído no console
            
            if (data.sucesso && Array.isArray(data.notificacoes)) {
                notificacoesGlobal = data.notificacoes;
                // Log desabilitado para reduzir ruído no console
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

// ==================================================
// EXIBIR NOTIFICAÇÕES NA TELA
// ==================================================
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
    
    // Log desabilitado para reduzir ruído no console
    
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
        
        html += `
            <div class="notificacao-item ${lida ? 'lida' : ''}" data-id="${notif.id}">
                <div class="notificacao-topo">
                    <span class="notificacao-tipo-badge ${tipoClass}">
                        ${traduzirTipo(notif.tipo, true)}
                    </span>
                </div>
                <div class="notificacao-mensagem">
                    ${notif.mensagem}
                </div>
                <div class="notificacao-rodape">
                    <span class="notificacao-data">
                        ${formatarData(notif.data_criacao)}
                    </span>
                    ${!lida 
                        ? `<button class="btn-marcar-lida" onclick="marcarComoLida(${notif.id})">
                            Marcar como lida
                           </button>` 
                        : '<span style="color: var(--azul-claro);">✓ Lida</span>'
                    }
                </div>
            </div>
        `;
    });
    
    // Insere no DOM
    container.innerHTML = html;
    
    // Atualiza contador
    if (contador) {
        contador.textContent = naoLidas === 0 
            ? 'Tudo lido!' 
            : `${naoLidas} não lida${naoLidas > 1 ? 's' : ''}`;
    }
    
    // Log desabilitado para reduzir ruído no console
}

// ==================================================
// MENSAGEM DE LISTA VAZIA
// ==================================================
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

// ==================================================
// MARCAR NOTIFICAÇÃO COMO LIDA
// ==================================================
function marcarComoLida(id) {
    // Log desabilitado para reduzir ruído no console
    
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
            // Log desabilitado para reduzir ruído no console
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

// ==================================================
// FUNÇÕES AUXILIARES
// ==================================================

// Traduz tipo de notificação
function traduzirTipo(tipo, apenasTexto = false) {
    const tipos = {
        'inscricao': apenasTexto ? 'Inscrição' : '<img src="../Imagens/notif-inscricao.svg" class="notif-icon-badge"> Inscrição',
        'desinscricao': apenasTexto ? 'Desincrição' : '<img src="../Imagens/notif-desinscricao.svg" class="notif-icon-badge"> Desincrição',
        'evento_cancelado': apenasTexto ? 'Cancelado' : '<img src="../Imagens/notif-cancelado.svg" class="notif-icon-badge"> Cancelado',
        'evento_prestes_iniciar': apenasTexto ? 'Iniciando' : '<img src="../Imagens/notif-relogio.svg" class="notif-icon-badge"> Iniciando',
        'novo_participante': apenasTexto ? 'Novo Participante' : '<img src="../Imagens/notif-usuario.svg" class="notif-icon-badge"> Novo Participante',
        'solicitacao_colaborador': apenasTexto ? 'Solicitação de colaboração' : '<img src="../Imagens/notif-geral.svg" class="notif-icon-badge"> Solicitação de colaboração',
        'colaboracao_aprovada': apenasTexto ? 'Colaboração aprovada' : '<img src="../Imagens/notif-geral.svg" class="notif-icon-badge"> Colaboração aprovada',
        'colaboracao_recusada': apenasTexto ? 'Colaboração recusada' : '<img src="../Imagens/notif-geral.svg" class="notif-icon-badge"> Colaboração recusada',
        'colaborador_adicionado': apenasTexto ? 'Adicionado como colaborador' : '<img src="../Imagens/notif-geral.svg" class="notif-icon-badge"> Adicionado como colaborador',
        'colaborador_removido': apenasTexto ? 'Removido de colaboração' : '<img src="../Imagens/notif-geral.svg" class="notif-icon-badge"> Removido de colaboração'
    };
    return tipos[tipo] || tipo;
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

// ==================================================
// LIMPEZA AO SAIR DA PÁGINA
// ==================================================
window.addEventListener('beforeunload', () => {
    if (intervalID) {
        clearInterval(intervalID);
        // Log desabilitado para reduzir ruído no console
    }
});

// Log final desabilitado para reduzir ruído no console

