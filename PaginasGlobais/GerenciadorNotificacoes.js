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
        console.log('[NOTIF DEBUG] Inicializando gerenciador de notificações...');
        // Seleciona elementos que já existem no HTML
        this.caixaNotificacoes = document.getElementById('notificacoes-caixa');
        this.botaoNotificacoes = document.getElementById('botao-notificacoes');
        
        console.log('[NOTIF DEBUG] Elementos encontrados:', {
            caixa: !!this.caixaNotificacoes,
            botao: !!this.botaoNotificacoes
        });
        
        if (!this.botaoNotificacoes || !this.caixaNotificacoes) {
            console.warn('Elementos de notificação não encontrados no DOM');
            return;
        }
        
        // Configura eventos primeiro
        this.configurarEventos();
        
        // Faz primeira busca IMEDIATAMENTE (mas não renderiza lista ainda)
        this.buscarNotificacoes(true); // true = apenas badge, não lista
        
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
        if (!this.botaoNotificacoes) {
            console.error('[NOTIF DEBUG] botaoNotificacoes não existe!');
            return;
        }

        console.log('[NOTIF DEBUG] Configurando eventos do botão');

        // Remove listeners antigos COMPLETAMENTE
        const botaoNovo = this.botaoNotificacoes.cloneNode(true);
        this.botaoNotificacoes.parentNode.replaceChild(botaoNovo, this.botaoNotificacoes);
        this.botaoNotificacoes = botaoNovo;

        console.log('[NOTIF DEBUG] Botão clonado e substituído');

        // Clique para abrir/fechar - usando addEventListener direto
        this.botaoNotificacoes.addEventListener('click', (e) => {
            console.log('[NOTIF DEBUG] CLIQUE CAPTURADO');
            e.preventDefault();
            e.stopPropagation();
            
            const caixa = document.getElementById('notificacoes-caixa');
            if (!caixa) {
                console.error('[NOTIF DEBUG] Caixa de notificações não encontrada!');
                return;
            }
            
            const isOpen = caixa.classList.contains('mostrar');
            
            console.log('[NOTIF DEBUG] Clique no botão. isOpen:', isOpen);
            console.log('[NOTIF DEBUG] Total de notificações armazenadas:', this.notificacoes.length);
            
            if (isOpen) {
                console.log('[NOTIF DEBUG] Fechando dropdown');
                caixa.classList.remove('mostrar');
            } else {
                console.log('[NOTIF DEBUG] Abrindo dropdown');
                caixa.classList.add('mostrar');
                
                // FORÇAR renderização IMEDIATA
                console.log('[NOTIF DEBUG] Forçando renderização...');
                this.atualizarInterface();
            }
        }, true); // useCapture = true

        console.log('[NOTIF DEBUG] Listener anexado com sucesso');
        
        // TESTE: Adicionar onclick inline também como fallback
        this.botaoNotificacoes.onclick = (e) => {
            console.log('[NOTIF DEBUG] ONCLICK INLINE CAPTURADO');
            e.preventDefault();
            e.stopPropagation();
            
            const caixa = document.getElementById('notificacoes-caixa');
            if (!caixa) {
                console.error('[NOTIF DEBUG] Caixa não encontrada!');
                return;
            }
            
            const isOpen = caixa.classList.contains('mostrar');
            console.log('[NOTIF DEBUG] onclick - isOpen:', isOpen);
            
            if (isOpen) {
                caixa.classList.remove('mostrar');
            } else {
                caixa.classList.add('mostrar');
                this.atualizarInterface();
            }
        };
        
        console.log('[NOTIF DEBUG] Onclick inline também configurado');

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

    buscarNotificacoes(apenasContador = false) {
        console.log('[NOTIF DEBUG] Buscando notificações... apenasContador:', apenasContador);
        fetch('../PaginasGlobais/BuscarNotificacoes.php')
            .then(res => {
                if (!res.ok) throw new Error('Erro HTTP: ' + res.status);
                return res.json();
            })
            .then(dados => {
                console.log('[NOTIF DEBUG] Notificações recebidas:', dados);
                if (dados.sucesso) {
                    this.notificacoes = dados.notificacoes || [];
                    console.log('[NOTIF DEBUG] Notificações armazenadas:', this.notificacoes.length);
                    if (apenasContador) {
                        console.log('[NOTIF DEBUG] Atualizando apenas badge e sino');
                        // Apenas atualiza badge e sino, não renderiza lista
                        this.atualizarBadgeESino();
                    } else {
                        console.log('[NOTIF DEBUG] Atualizando interface completa');
                        // Atualiza tudo
                        this.atualizarInterface();
                    }
                } else {
                    console.error('Erro na resposta:', dados.erro);
                }
            })
            .catch(err => {
                console.error('Erro ao buscar notificações:', err);
            });
    }

    atualizarBadgeESino() {
        const total = this.notificacoes.length;
        const badge = document.getElementById('notificacoes-badge');
        const botao = document.getElementById('botao-notificacoes');

        if (badge) {
            if (total > 0) {
                badge.textContent = total > 99 ? '99+' : total;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }

        if (botao) {
            const svg = botao.querySelector('svg');
            if (svg) {
                if (total > 0) {
                    const paths = svg.querySelectorAll('path');
                    paths.forEach(path => {
                        path.setAttribute('fill', 'currentColor');
                    });
                } else {
                    const paths = svg.querySelectorAll('path');
                    paths.forEach(path => {
                        path.setAttribute('fill', 'none');
                    });
                }
            }
        }
    }

    atualizarInterface() {
        console.log('[NOTIF DEBUG] === atualizarInterface iniciado ===');
        const total = this.notificacoes.length;
        const badge = document.getElementById('notificacoes-badge');
        const lista = document.getElementById('notificacoes-lista');
        const botao = document.getElementById('botao-notificacoes');

        console.log('[NOTIF DEBUG] Total:', total);
        console.log('[NOTIF DEBUG] Elementos:', {
            badge: !!badge,
            lista: !!lista,
            botao: !!botao
        });

        // Aguarda o DOM estar completamente pronto (importante para carregamento via AJAX)
        const atualizarQuandoPronto = () => {
            if (!badge || !lista || !botao) {
                console.log('[NOTIF DEBUG] Aguardando elementos DOM...');
                setTimeout(atualizarQuandoPronto, 50);
                return;
            }
            
            console.log('[NOTIF DEBUG] Todos elementos DOM prontos!');

            // Atualiza badge
            if (total > 0) {
                badge.textContent = total > 99 ? '99+' : total;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }

            // Preenche o sino se houver notificações novas
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

            // Atualiza lista de notificações
            console.log('[NOTIF DEBUG] Renderizando lista. Total:', total);
            
            if (total === 0) {
                console.log('[NOTIF DEBUG] Sem notificações, mostrando mensagem vazia');
                lista.innerHTML = `
                    <div class="notificacoes-vazio">
                        Sem novas notificações
                    </div>
                `;
                this.totalAnterior = 0;
                return;
            }

            console.log('[NOTIF DEBUG] Gerando HTML para', total, 'notificações');
            let html = '';
            this.notificacoes.forEach((notif, index) => {
                console.log('[NOTIF DEBUG] Processando notif', index + 1, '- Tipo:', notif.tipo);
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
                console.log('[NOTIF DEBUG] Mensagem tipo:', notif.tipo, '| Partes:', partes.length);
                if (partes.length >= 4) {
                    const cpfRemetente = partes[0].trim();
                    const cpfMascarado = this.mascararCPF(cpfRemetente);
                    const nomeRemetente = this.escaparHTML(partes[1].trim());
                    const terceiroItem = this.escaparHTML(partes[2].trim()); // EVENTO ou TÍTULO
                    const conteudoMsg = this.escaparHTML(partes.slice(3).join('|||').trim());
                    
                    // Trunca mensagem se muito longa
                    let mensagemPreview = conteudoMsg;
                    if (mensagemPreview.length > 60) {
                        mensagemPreview = mensagemPreview.substring(0, 57) + '...';
                    }
                    
                    // Formata de forma simples e clara
                    if (isMensagemParticipante) {
                        mensagemTexto = `${nomeRemetente}, CPF ${cpfMascarado}, enviou uma mensagem no evento "${terceiroItem}": ${mensagemPreview}`;
                    } else {
                        mensagemTexto = `${nomeRemetente}, CPF ${cpfMascarado}, enviou: ${mensagemPreview}`;
                    }
                    
                    remetenteInfo = '';
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

        console.log('[NOTIF DEBUG] HTML gerado. Tamanho:', html.length, 'caracteres');
        console.log('[NOTIF DEBUG] Primeiros 200 caracteres:', html.substring(0, 200));
        
        lista.innerHTML = html;
        console.log('[NOTIF DEBUG] HTML inserido na lista');
        
        // Forçar reflow para garantir que CSS seja aplicado
        void lista.offsetHeight;
        console.log('[NOTIF DEBUG] Reflow forçado');
        
        // Verificar se as classes CSS estão aplicadas
        const items = lista.querySelectorAll('.notificacao-item-dropdown');
        console.log('[NOTIF DEBUG] Items renderizados:', items.length);
        if (items.length > 0) {
            const firstItem = items[0];
            const computedStyle = window.getComputedStyle(firstItem);
            console.log('[NOTIF DEBUG] Estilo do primeiro item:', {
                display: computedStyle.display,
                padding: computedStyle.padding,
                background: computedStyle.background
            });
        }
        
        this.totalAnterior = total;
        console.log('[NOTIF DEBUG] === atualizarInterface concluído ===');
        };

        // Chamar imediatamente ou aguardar DOM
        atualizarQuandoPronto();
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

console.log('[NOTIF DEBUG] Script GerenciadorNotificacoes.js carregado');

document.addEventListener('DOMContentLoaded', function() {
    console.log('[NOTIF DEBUG] DOMContentLoaded - Criando gerenciador...');
    // Espera um pouco para garantir que o DOM está pronto
    setTimeout(() => {
        if (!gerenciadorNotificacoes) {
            console.log('[NOTIF DEBUG] Instanciando GerenciadorNotificacoes...');
            gerenciadorNotificacoes = new GerenciadorNotificacoes();
            window.gerenciadorNotificacoes = gerenciadorNotificacoes;
            console.log('[NOTIF DEBUG] Gerenciador criado e disponível em window.gerenciadorNotificacoes');
        } else {
            console.log('[NOTIF DEBUG] Gerenciador já existe');
        }
    }, 100);
});

// Reinicializa quando carregar nova página (no seu sistema de rotas)
document.addEventListener('conteudo-carregado', function() {
    console.log('[NOTIF DEBUG] conteudo-carregado - Reinicializando...');
    if (gerenciadorNotificacoes) {
        gerenciadorNotificacoes.destruir();
    }
    setTimeout(() => {
        gerenciadorNotificacoes = new GerenciadorNotificacoes();
        window.gerenciadorNotificacoes = gerenciadorNotificacoes;
        console.log('[NOTIF DEBUG] Gerenciador reinicializado');
    }, 100);
});
