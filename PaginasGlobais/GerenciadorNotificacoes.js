// Gerenciador de Notificações com Polling
class GerenciadorNotificacoes {
    constructor() {
        this.intervaloAtualizar = 30000; // 30 segundos
        this.timerNotificacoes = null;
        this.notificacoes = [];
        this.caixaNotificacoes = null;
        this.botaoNotificacoes = null;
        this.totalAnterior = 0; // Rastreia total anterior para detectar novas notificações
        this.inicializar();
    }

    inicializar() {
        // Seleciona elementos que já existem no HTML
        this.caixaNotificacoes = document.getElementById('notificacoes-caixa');
        this.botaoNotificacoes = document.getElementById('botao-notificacoes');
        
        if (!this.botaoNotificacoes || !this.caixaNotificacoes) {
            console.warn('Elementos de notificação não encontrados no DOM');
            return;
        }
        
        // Configura eventos primeiro
        this.configurarEventos();
        
        // Faz primeira busca IMEDIATAMENTE
        this.buscarNotificacoes();
        
        // Inicia polling
        this.iniciarPolling();
        
        // Re-attach link listener a cada inicialização
        this.configurarLinkVerTudo();
    }

    configurarLinkVerTudo() {
        const linkVerTudo = document.getElementById('link-ver-tudo-notificacoes');
        if (linkVerTudo) {
            // Remove listeners antigos
            const novoLink = linkVerTudo.cloneNode(true);
            linkVerTudo.parentNode.replaceChild(novoLink, linkVerTudo);
            
            novoLink.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('Link Ver tudo clicado');
                console.log('carregarPagina disponível?', typeof window.carregarPagina);
                
                // Chama carregarPagina
                if (typeof window.carregarPagina === 'function') {
                    console.log('Chamando carregarPagina');
                    window.carregarPagina('painelnotificacoes');
                } else {
                    console.warn('carregarPagina não disponível');
                }
                
                // Fecha o dropdown (sem remover classe 'ativo' do botão)
                setTimeout(() => {
                    const caixa = document.getElementById('notificacoes-caixa');
                    caixa.classList.remove('mostrar');
                }, 100);
            });
        }
    }

    configurarEventos() {
        if (!this.botaoNotificacoes) return;

        // Remove listeners antigos
        const botaoNovo = this.botaoNotificacoes.cloneNode(true);
        this.botaoNotificacoes.parentNode.replaceChild(botaoNovo, this.botaoNotificacoes);
        this.botaoNotificacoes = botaoNovo;

        // Clique para abrir/fechar - NÃO adiciona classe 'ativo'
        this.botaoNotificacoes.addEventListener('click', (e) => {
            e.stopPropagation();
            const caixa = document.getElementById('notificacoes-caixa');
            const isOpen = caixa.classList.contains('mostrar');
            
            if (isOpen) {
                caixa.classList.remove('mostrar');
            } else {
                caixa.classList.add('mostrar');
            }
        });

        // Fecha ao clicar fora
        document.addEventListener('click', (e) => {
            const caixa = document.getElementById('notificacoes-caixa');
            if (!e.target.closest('#botao-notificacoes') && 
                !e.target.closest('#notificacoes-caixa')) {
                caixa.classList.remove('mostrar');
            }
        });
    }

    buscarNotificacoes() {
        console.log('Buscando notificações do dropdown...');
        fetch('../PaginasGlobais/BuscarNotificacoes.php')
            .then(res => {
                if (!res.ok) throw new Error('Erro HTTP: ' + res.status);
                return res.json();
            })
            .then(dados => {
                console.log('Notificações recebidas:', dados);
                if (dados.sucesso) {
                    this.notificacoes = dados.notificacoes || [];
                    this.atualizarInterface();
                } else {
                    console.error('Erro na resposta:', dados.erro);
                }
            })
            .catch(err => {
                console.error('Erro ao buscar notificações:', err);
            });
    }

    atualizarInterface() {
        const total = this.notificacoes.length;
        const badge = document.getElementById('notificacoes-badge');
        const lista = document.getElementById('notificacoes-lista');
        const botao = document.getElementById('botao-notificacoes');

        // Atualiza badge
        if (badge) {
            if (total > 0) {
                badge.textContent = total > 99 ? '99+' : total;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }

        // Preenche o sino se houver notificações novas
        if (botao) {
            const svg = botao.querySelector('svg');
            if (svg) {
                if (total > 0) {
                    // Preenche o sino
                    const paths = svg.querySelectorAll('path');
                    paths.forEach(path => {
                        path.setAttribute('fill', 'currentColor');
                    });
                } else {
                    // Remove o preenchimento
                    const paths = svg.querySelectorAll('path');
                    paths.forEach(path => {
                        path.setAttribute('fill', 'none');
                    });
                }
            }
        }

        // Atualiza lista de notificações
        if (!lista) return;

        if (total === 0) {
            lista.innerHTML = `
                <div class="notificacoes-vazio">
                    Sem novas notificações
                </div>
            `;
            this.totalAnterior = 0;
            return;
        }

        let html = '';
        this.notificacoes.forEach((notif) => {
            const data = new Date(notif.data_criacao);
            const hora = data.toLocaleTimeString('pt-BR', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            const tipoTexto = this.obterTextoTipo(notif.tipo);

            html += `
            <div class="notificacao-item" onclick="window.gerenciadorNotificacoes?.marcarComoLida(${notif.id})">
                <div class="notificacao-tipo">
                    ${tipoTexto} • ${hora}
                </div>
                <div class="notificacao-mensagem">
                    ${this.escaparHTML(notif.mensagem)}
                </div>
            </div>
            `;
        });

        lista.innerHTML = html;
        this.totalAnterior = total;
    }

    marcarComoLida(id) {
        const formData = new FormData();
        formData.append('id', id);

        fetch('../PaginasGlobais/AtualizarNotificacao.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(dados => {
            if (dados.sucesso) {
                this.buscarNotificacoes();
            }
        })
        .catch(err => console.error('Erro ao marcar como lida:', err));
    }

    iniciarPolling() {
        // Para qualquer polling anterior
        if (this.timerNotificacoes) {
            clearInterval(this.timerNotificacoes);
        }
        
        console.log('Iniciando polling de notificações a cada 30 segundos');
        // Busca a cada 30 segundos
        this.timerNotificacoes = setInterval(() => {
            console.log('Polling: buscando notificações...');
            this.buscarNotificacoes();
        }, this.intervaloAtualizar);
    }

    pararPolling() {
        if (this.timerNotificacoes) {
            clearInterval(this.timerNotificacoes);
            this.timerNotificacoes = null;
        }
    }

    obterTextoTipo(tipo) {
        const tipos = {
            'inscricao': '📝 Inscrição',
            'desinscricao': '✗ Desincrição',
            'evento_cancelado': '🚫 Evento cancelado',
            'evento_prestes_iniciar': '⏰ Evento iniciando',
            'novo_participante': '👤 Novo participante',
            'outro': '📢 Notificação'
        };
        return tipos[tipo] || tipos['outro'];
    }

    escaparHTML(texto) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return texto.replace(/[&<>"']/g, m => map[m]);
    }

    destruir() {
        this.pararPolling();
    }

    reinicializar() {
        // Chamado quando conteúdo dinâmico é carregado
        this.configurarLinkVerTudo();
    }
}

// Inicializa ao carregar a página
let gerenciadorNotificacoes = null;

document.addEventListener('DOMContentLoaded', function() {
    // Espera um pouco para garantir que o DOM está pronto
    setTimeout(() => {
        if (!gerenciadorNotificacoes) {
            gerenciadorNotificacoes = new GerenciadorNotificacoes();
            window.gerenciadorNotificacoes = gerenciadorNotificacoes;
        }
    }, 100);
});

// Reinicializa quando carregar nova página (no seu sistema de rotas)
document.addEventListener('conteudo-carregado', function() {
    if (gerenciadorNotificacoes) {
        gerenciadorNotificacoes.destruir();
    }
    setTimeout(() => {
        gerenciadorNotificacoes = new GerenciadorNotificacoes();
        window.gerenciadorNotificacoes = gerenciadorNotificacoes;
    }, 100);
});
