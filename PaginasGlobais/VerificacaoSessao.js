// Sistema Global de Verificação de Sessão com Detecção de Atividade - CEU
(function() {
    let intervaloVerificacaoSessao = null;
    let intervaloVerificacaoInatividade = null;
    let timestampUltimaAtividade = Date.now();
    let tempoLimiteSessaoInatividade = 60000; // 60 segundos em milissegundos (aumentado de 30)
    let modalAvisoSessaoAtivo = false;

    // Lista de eventos que indicam atividade do usuário
    const eventosAtividadeUsuario = [
        'mousedown', 'mousemove', 'mouseup', 'click', 'dblclick',
        'keypress', 'keydown', 'keyup',
        'scroll', 'wheel',
        'touchstart', 'touchmove', 'touchend',
        'focus', 'input', 'change'
    ];

    // Função para atualizar timestamp da última atividade
    function atualizarTimestampUltimaAtividade() {
        // Removido console.log para evitar poluição do console
        timestampUltimaAtividade = Date.now();
        
        // Se havia modal de aviso ativo, remove
        if (modalAvisoSessaoAtivo) {
            // console.log('Removendo modal de aviso devido à atividade'); // Debug desabilitado
            removerModalAvisoSessao();
        }
    }

    // Função para remover modal de aviso
    function removerModalAvisoSessao() {
        const modalAvisoSessao = document.getElementById('avisoSessaoExpirando');
        if (modalAvisoSessao && document.body.contains(modalAvisoSessao)) {
            document.body.removeChild(modalAvisoSessao);
        }
        modalAvisoSessaoAtivo = false;
    }

    // Função para adicionar listeners de atividade
    function adicionarListenersAtividadeUsuario() {
        // console.log('Adicionando listeners de atividade'); // Debug desabilitado
        eventosAtividadeUsuario.forEach(evento => {
            document.addEventListener(evento, atualizarTimestampUltimaAtividade, {
                passive: true,
                capture: true
            });
        });
        
        // Listener especial para window também
        window.addEventListener('focus', atualizarTimestampUltimaAtividade);
        window.addEventListener('blur', atualizarTimestampUltimaAtividade);
    }

    // Função para remover listeners de atividade
    function removerListenersAtividadeUsuario() {
        // console.log('Removendo listeners de atividade'); // Debug desabilitado
        eventosAtividadeUsuario.forEach(evento => {
            document.removeEventListener(evento, atualizarTimestampUltimaAtividade, {
                passive: true,
                capture: true
            });
        });
        
        // Remove listeners especiais do window
        window.removeEventListener('focus', atualizarTimestampUltimaAtividade);
        window.removeEventListener('blur', atualizarTimestampUltimaAtividade);
    }

    // Função para mostrar modal de sessão expirada
    function mostrarModalSessaoExpirada() {
        // console.log('MOSTRAR MODAL SESSÃO EXPIRADA - Iniciando'); // Debug desabilitado
        
        // Remove modal antigo se existir
        const modalSessaoExistente = document.getElementById('modalSessaoExpirada');
        if (modalSessaoExistente) {
            modalSessaoExistente.remove();
        }

        // Cria o modal
        const modalSessaoExpirada = document.createElement('div');
        modalSessaoExpirada.id = 'modalSessaoExpirada';
        modalSessaoExpirada.className = 'modal-personalizado mostrar';
        modalSessaoExpirada.style.zIndex = '9999'; // Garantir que fica no topo
        
        modalSessaoExpirada.innerHTML = `
            <div class="conteudo-modal-personalizado">
                <div class="cabecalho-modal-personalizado">🔒 Sessão Expirada</div>
                <div class="corpo-modal-personalizado">
                    Sua sessão expirou por inatividade.<br>
                    <strong>Clique no botão abaixo para fazer login novamente.</strong>
                </div>
                <button class="botao botao-modal-personalizado" id="btnFazerLoginNovamente">Fazer Login</button>
            </div>
        `;

        // Adiciona ao body
        document.body.appendChild(modalSessaoExpirada);
        
        // console.log('MODAL SESSÃO EXPIRADA - Adicionado ao DOM'); // Debug desabilitado
        
        // Adiciona evento ao botão (sem redirecionamento automático)
        const btnLogin = modalSessaoExpirada.querySelector('#btnFazerLoginNovamente');
        btnLogin.addEventListener('click', function() {
            console.log('Usuário clicou para fazer login - redirecionando'); // Debug
            window.location.href = '../PaginasPublicas/ContainerPublico.php?pagina=login&erro=sessao_expirada';
        });
        
        // Impede fechamento do modal clicando fora
        modalSessaoExpirada.addEventListener('click', function(evento) {
            evento.stopPropagation();
        });
        
        // Bloqueia tentativas de fechar com ESC
        document.addEventListener('keydown', function(evento) {
            if (evento.key === 'Escape') {
                evento.preventDefault();
                evento.stopPropagation();
            }
        });
    }

    // Função para verificar sessão no servidor
    function verificarSessaoAtivaNoServidor() {
        fetch('./VerificarSessao.php')
            .then(response => response.json())
            .then(dadosResposta => {
                // Log apenas se sessão não estiver ativa (importante)
                if (!dadosResposta.ativa) {
                    console.log('Resposta do servidor:', dadosResposta);
                    console.log('Sessão inativa detectada pelo servidor');
                    pararVerificacaoSessao();
                    // SEMPRE mostra o modal, nunca redireciona automaticamente
                    mostrarModalSessaoExpirada();
                }
            })
            .catch(erro => {
                console.error('Erro ao verificar sessão:', erro);
                // Em caso de erro de rede, não expira automaticamente
            });
    }

    // Função para mostrar aviso de sessão prestes a expirar
    function mostrarAvisoSessaoProximaExpiracao() {
        // Remove modal antigo se existir
        removerModalAvisoSessao();

        modalAvisoSessaoAtivo = true;
        const modalAvisoSessao = document.createElement('div');
        modalAvisoSessao.id = 'avisoSessaoExpirando';
        modalAvisoSessao.className = 'modal-personalizado mostrar';
        modalAvisoSessao.innerHTML = `
            <div class="conteudo-modal-personalizado">
                <div class="cabecalho-modal-personalizado">⏰ Atenção!</div>
                <div class="corpo-modal-personalizado">
                    Sua sessão expirará em <strong>20 segundos</strong> por inatividade.<br>
                    <em>Mova o mouse ou pressione qualquer tecla para manter a sessão ativa!</em>
                </div>
            </div>
        `;
        document.body.appendChild(modalAvisoSessao);
    }

    // Função para verificar inatividade
    function verificarInatividade() {
        const timestampAtual = Date.now();
        const tempoInativo = timestampAtual - timestampUltimaAtividade;
        const segundosInativos = Math.floor(tempoInativo / 1000);
        
        // Debug apenas quando próximo do limite (reduzindo spam)
        if (segundosInativos % 10 === 0 || segundosInativos > 50) {
            console.log(`Verificando inatividade: ${segundosInativos}s de ${tempoLimiteSessaoInatividade/1000}s`);
        }
        
        // Se passou do tempo limite, expira a sessão
        if (tempoInativo >= tempoLimiteSessaoInatividade) {
            console.log('Sessão expirada por inatividade - MOSTRANDO MODAL');
            pararVerificacaoSessao();
            
            // Expira a sessão no servidor também, mas SEMPRE mostra modal
            fetch('./VerificarSessao.php?forcar_expiracao=1')
                .then(() => {
                    // console.log('Sessão expirada no servidor - mostrando modal'); // Debug reduzido
                    mostrarModalSessaoExpirada();
                })
                .catch(() => {
                    console.log('Erro ao expirar no servidor - mostrando modal mesmo assim');
                    mostrarModalSessaoExpirada();
                });
            return;
        }
        
        // Se faltam 20 segundos e não há modal ativo, mostra aviso
        const tempoRestante = tempoLimiteSessaoInatividade - tempoInativo;
        if (tempoRestante <= 20000 && !modalAvisoSessaoAtivo) {
            console.log('Mostrando aviso de sessão expirando');
            mostrarAvisoSessaoProximaExpiracao();
        }
    }

    // Função para iniciar verificação de sessão
    function iniciarVerificacaoSessao(tempoSessaoSegundos = 60) {
        // console.log(`Iniciando verificação de sessão com ${tempoSessaoSegundos} segundos`); // Debug reduzido
        
        // Para qualquer verificação anterior
        pararVerificacaoSessao();
        
        // Converte para milissegundos
        tempoLimiteSessaoInatividade = tempoSessaoSegundos * 1000;
        timestampUltimaAtividade = Date.now();
        modalAvisoSessaoAtivo = false;
        
        // console.log('Última atividade inicial:', new Date(timestampUltimaAtividade).toLocaleTimeString()); // Debug desabilitado
        
        // Adiciona listeners de atividade
        adicionarListenersAtividadeUsuario();

        // Verifica a sessão no servidor a cada 5 segundos (para detecção de logout em outra aba)
        intervaloVerificacaoSessao = setInterval(verificarSessaoAtivaNoServidor, 5000);
        
        // Verifica inatividade a cada 1 segundo
        intervaloVerificacaoInatividade = setInterval(verificarInatividade, 1000);
        
        // console.log('Sistema de verificação de sessão iniciado'); // Debug desabilitado
    }

    // Função para parar verificação de sessão
    function pararVerificacaoSessao() {
        // console.log('Parando verificação de sessão'); // Debug desabilitado
        
        // Remove listeners de atividade
        removerListenersAtividadeUsuario();
        
        if (intervaloVerificacaoSessao) {
            clearInterval(intervaloVerificacaoSessao);
            intervaloVerificacaoSessao = null;
        }
        if (intervaloVerificacaoInatividade) {
            clearInterval(intervaloVerificacaoInatividade);
            intervaloVerificacaoInatividade = null;
        }
        
        // Remove modal de aviso se estiver ativo
        removerModalAvisoSessao();
    }

    // Função para reiniciar verificação de sessão (útil após navegação)
    function reiniciarVerificacaoSessao(tempoSessaoSegundos = 60) {
        // console.log('Reiniciando verificação de sessão'); // Debug desabilitado
        pararVerificacaoSessao();
        setTimeout(() => {
            iniciarVerificacaoSessao(tempoSessaoSegundos);
        }, 100); // Pequeno delay para garantir limpeza completa
    }

    // Torna as funções globais
    window.iniciarVerificacaoSessao = iniciarVerificacaoSessao;
    window.pararVerificacaoSessao = pararVerificacaoSessao;
    window.reiniciarVerificacaoSessao = reiniciarVerificacaoSessao;
    window.verificarSessaoAtivaNoServidor = verificarSessaoAtivaNoServidor;
    window.mostrarModalSessaoExpirada = mostrarModalSessaoExpirada;
    
    // Função de debug para testar
    window.debugInformacoesSessao = function() {
        const timestampAtual = Date.now();
        const tempoInativo = timestampAtual - timestampUltimaAtividade;
        const segundosInativos = Math.floor(tempoInativo / 1000);
        console.log(`Debug Sessão:
        - Tempo inativo: ${segundosInativos}s
        - Limite: ${tempoLimiteSessaoInatividade/1000}s
        - Modal ativo: ${modalAvisoSessaoAtivo}
        - Última atividade: ${new Date(timestampUltimaAtividade).toLocaleTimeString()}
        - Intervalos ativos: verificação=${intervaloVerificacaoSessao !== null}, inatividade=${intervaloVerificacaoInatividade !== null}`);
    };
    
    // Função de debug para forçar expiração de sessão (teste)
    window.debugForcarExpiracao = function() {
        console.log('DEBUG: Forçando expiração de sessão para teste');
        timestampUltimaAtividade = Date.now() - (tempoLimiteSessaoInatividade + 1000);
    };
    
    // Função de debug para verificar status do modal
    window.debugStatusModal = function() {
        const modalSessao = document.getElementById('modalSessaoExpirada');
        const modalAviso = document.getElementById('avisoSessaoExpirando');
        console.log(`Debug Modal:
        - Modal sessão expirada existe: ${modalSessao !== null}
        - Modal aviso existe: ${modalAviso !== null}
        - Modal aviso ativo (var): ${modalAvisoSessaoAtivo}`);
    };

    // Auto-inicializa se estiver em uma página de usuário logado
    document.addEventListener('DOMContentLoaded', function() {
        const usuarioEstaLogado = window.location.pathname.includes('/PaginasParticipante/') || 
                                 window.location.pathname.includes('/PaginasOrganizador/');
        
        if (usuarioEstaLogado) {
            iniciarVerificacaoSessao(60); // 60 segundos
        }
    });
})();