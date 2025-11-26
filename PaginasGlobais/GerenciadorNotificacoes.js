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
            // Verifica se e.target é um elemento antes de usar .closest()
            if (!(e.target instanceof Element)) return;
            
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
            
            // Formata mensagem se for de participante ou organizador
            let isMensagemParticipante = notif.tipo === 'mensagem_participante';
            let isMensagemOrganizador = notif.tipo === 'mensagem_organizador';
            let remetenteInfo = '';
            let mensagemTexto = '';
            
            if (isMensagemParticipante || isMensagemOrganizador) {
                // Formato: CPF|||NOME|||EVENTO|||MENSAGEM (para participante) ou CPF|||NOME|||TÍTULO|||CONTEÚDO (para organizador)
                const partes = notif.mensagem.split('|||');
                if (partes.length >= 4) {
                    const cpfRemetente = partes[0].trim();
                    const cpfMascarado = this.mascararCPF(cpfRemetente);
                    const nomeRemetente = this.escaparHTML(partes[1].trim());
                    const terceiroItem = this.escaparHTML(partes[2].trim()); // EVENTO ou TÍTULO
                    const conteudoMsg = this.escaparHTML(partes.slice(3).join('|||').trim());
                    
                    // Trunca mensagem se muito longa
                    let mensagemPreview = conteudoMsg;
                    if (mensagemPreview.length > 80) {
                        mensagemPreview = mensagemPreview.substring(0, 77) + '...';
                    }
                    
                    // Formata diferente para participante e organizador
                    let detalhesAdicionais = '';
                    if (isMensagemParticipante) {
                        detalhesAdicionais = `💬 <strong>${cpfMascarado}</strong> em ${terceiroItem}`;
                    } else {
                        detalhesAdicionais = `💬 <strong>${cpfMascarado}</strong> • ${terceiroItem}`;
                    }
                    
                    remetenteInfo = `<div class="notif-remetente-info">
                        <div style="margin-bottom: 4px;">
                            <div style="font-size: 0.95rem; color: #FFF; font-weight: 600;">${nomeRemetente}</div>
                            <div style="font-size: 0.8rem; opacity: 0.8; margin-top: 2px;">${detalhesAdicionais}</div>
                        </div>
                        <div style="background: rgba(255,255,255,0.1); padding: 6px; border-radius: 3px; border-left: 3px solid #6598D2; font-size: 0.85rem; line-height: 1.4; color: #FFF; margin-top: 4px;">${mensagemPreview}</div>
                    </div>`;
                    
                    // A mensagem já será mostrada no preview acima
                    mensagemTexto = '';
                } else {
                    // Se não conseguir fazer parsing, mostra a mensagem completa
                    mensagemTexto = this.escaparHTML(notif.mensagem);
                    if (mensagemTexto.length > 100) {
                        mensagemTexto = mensagemTexto.substring(0, 97) + '...';
                    }
                }
            } else {
                // Outros tipos de notificação
                mensagemTexto = this.escaparHTML(notif.mensagem);
                // Trunca mensagem se muito longa
                if (mensagemTexto.length > 100) {
                    mensagemTexto = mensagemTexto.substring(0, 97) + '...';
                }
            }

            html += `
            <div class="notificacao-item-dropdown ${(isMensagemParticipante || isMensagemOrganizador) ? 'notif-mensagem' : ''}" onclick="window.gerenciadorNotificacoes?.marcarComoLida(${notif.id})">
                <div class="notificacao-header-dropdown">
                    <div class="notificacao-tipo">
                        ${tipoTexto}
                    </div>
                    <div class="notificacao-hora">
                        ${hora}
                    </div>
                </div>
                ${remetenteInfo}
                <div class="notificacao-mensagem">
                    ${mensagemTexto}
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
            'inscricao': '<img src="../Imagens/notif-inscricao.svg" class="notif-icon"> Inscrição',
            'desinscricao': '<img src="../Imagens/notif-desinscricao.svg" class="notif-icon"> Desincrição',
            'evento_cancelado': '<img src="../Imagens/notif-cancelado.svg" class="notif-icon"> Evento cancelado',
            'evento_prestes_iniciar': '<img src="../Imagens/notif-relogio.svg" class="notif-icon"> Evento iniciando',
            'novo_participante': '<img src="../Imagens/notif-usuario.svg" class="notif-icon"> Novo participante',
            'mensagem_participante': '<img src="../Imagens/notif-usuario.svg" class="notif-icon"> Mensagem',
            'mensagem_organizador': '<img src="../Imagens/notif-usuario.svg" class="notif-icon"> Mensagem do organizador',
            'solicitacao_colaborador': '<img src="../Imagens/notif-geral.svg" class="notif-icon"> Solicitação de colaboração',
            'colaboracao_aprovada': '<img src="../Imagens/notif-geral.svg" class="notif-icon"> Colaboração aprovada',
            'colaboracao_recusada': '<img src="../Imagens/notif-geral.svg" class="notif-icon"> Colaboração recusada',
            'colaborador_adicionado': '<img src="../Imagens/notif-geral.svg" class="notif-icon"> Adicionado como colaborador',
            'colaborador_removido': '<img src="../Imagens/notif-geral.svg" class="notif-icon"> Removido de colaboração',
            'outro': '<img src="../Imagens/notif-geral.svg" class="notif-icon"> Notificação'
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

    mascararCPF(cpf) {
        // Remove caracteres não numéricos
        const apenasNumeros = cpf.replace(/\D/g, '');
        if (apenasNumeros.length < 5) return cpf;
        
        // Mostra apenas os 3 primeiros e 2 últimos dígitos
        const primeiros3 = apenasNumeros.substring(0, 3);
        const ultimos2 = apenasNumeros.substring(apenasNumeros.length - 2);
        return `${primeiros3}.***.**-${ultimos2}`;
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
