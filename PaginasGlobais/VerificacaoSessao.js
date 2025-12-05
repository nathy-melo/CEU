// Sistema Global de Verificação de Sessão com Detecção de Atividade - CEU
(function () {
    let intervaloVerificacaoSessao = null;
    let intervaloVerificacaoInatividade = null;
    let timestampUltimaAtividade = Date.now();
    let tempoLimiteSessaoInatividade = 300000; // 300 segundos (5 minutos) em milissegundos
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
        console.log('🔒 SESSÃO EXPIRADA - Mostrando modal para o usuário');

        // Remove modal antigo se existir
        const modalSessaoExistente = document.getElementById('modalSessaoExpirada');
        if (modalSessaoExistente) {
            modalSessaoExistente.remove();
        }

        // Cria o modal
        const modalSessaoExpirada = document.createElement('div');
        modalSessaoExpirada.id = 'modalSessaoExpirada';
        modalSessaoExpirada.className = 'modal-personalizado mostrar';
        modalSessaoExpirada.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; z-index: 999999;';

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

        // Adiciona evento ao botão
        const btnLogin = modalSessaoExpirada.querySelector('#btnFazerLoginNovamente');
        if (btnLogin) {
            btnLogin.addEventListener('click', function () {
                window.location.href = '../PaginasPublicas/ContainerPublico.php?pagina=login&erro=sessao_expirada';
            });

            // Efeito hover
            btnLogin.addEventListener('mouseenter', function () {
                this.style.opacity = '0.9';
            });
            btnLogin.addEventListener('mouseleave', function () {
                this.style.opacity = '1';
            });
        }

        // Impede fechamento do modal clicando fora
        modalSessaoExpirada.addEventListener('click', function (evento) {
            if (evento.target === modalSessaoExpirada) {
                evento.stopPropagation();
                evento.preventDefault();
            }
        });

        // Bloqueia tentativas de fechar com ESC
        const bloquearESC = function (evento) {
            if (evento.key === 'Escape') {
                evento.preventDefault();
                evento.stopPropagation();
            }
        };
        document.addEventListener('keydown', bloquearESC);

        // Armazena referência para cleanup futuro se necessário
        modalSessaoExpirada._bloquearESC = bloquearESC;
    }

    // Função para verificar sessão no servidor
    function verificarSessaoAtivaNoServidor() {
        fetch('./VerificarSessao.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Resposta HTTP ' + response.status);
                }
                return response.json();
            })
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
        modalAvisoSessao.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 999998; animation: slideIn 0.3s ease;';

        modalAvisoSessao.innerHTML = `
            <div class="conteudo-modal-personalizado">
                <div class="cabecalho-modal-personalizado">⏰ Atenção!</div>
                <div class="corpo-modal-personalizado">
                    Sua sessão expirará em <strong>1 minuto</strong> por inatividade.<br>
                    <em>Mova o mouse ou pressione qualquer tecla para manter a sessão ativa!</em>
                </div>
            </div>
        `;

        // Adiciona animação CSS inline
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);

        document.body.appendChild(modalAvisoSessao);
    }

    // Função para verificar inatividade
    function verificarInatividade() {
        const timestampAtual = Date.now();
        const tempoInativo = timestampAtual - timestampUltimaAtividade;
        const segundosInativos = Math.floor(tempoInativo / 1000);

        // Log básico a cada 30 segundos para monitoramento
        if (segundosInativos % 30 === 0 && segundosInativos > 0) {
            console.log(`Detectando inatividade: ${segundosInativos}s de ${tempoLimiteSessaoInatividade / 1000}s`);
        }

        // Se passou do tempo limite, expira a sessão
        if (tempoInativo >= tempoLimiteSessaoInatividade) {
            pararVerificacaoSessao();

            // Expira a sessão no servidor também, mas SEMPRE mostra modal
            fetch('./VerificarSessao.php?forcar_expiracao=1')
                .then(() => {
                    mostrarModalSessaoExpirada();
                })
                .catch(() => {
                    mostrarModalSessaoExpirada();
                });
            return;
        }

        // Se falta 1 minuto (60 segundos) e não há modal ativo, mostra aviso
        const tempoRestante = tempoLimiteSessaoInatividade - tempoInativo;
        if (tempoRestante <= 60000 && !modalAvisoSessaoAtivo) {
            console.log('⚠️ AVISO: Mostrando aviso de sessão expirando em 1 minuto');
            mostrarAvisoSessaoProximaExpiracao();
        }
    }

    // Função para iniciar verificação de sessão
    function iniciarVerificacaoSessao(tempoSessaoSegundos = 300) {
        // Para qualquer verificação anterior
        pararVerificacaoSessao();

        // Converte para milissegundos
        tempoLimiteSessaoInatividade = tempoSessaoSegundos * 1000;
        timestampUltimaAtividade = Date.now();
        modalAvisoSessaoAtivo = false;

        // Adiciona listeners de atividade
        adicionarListenersAtividadeUsuario();

        // Verifica a sessão no servidor a cada 30 segundos (para detecção de logout em outra aba)
        intervaloVerificacaoSessao = setInterval(verificarSessaoAtivaNoServidor, 30000);

        // Verifica inatividade a cada 1 segundo
        intervaloVerificacaoInatividade = setInterval(verificarInatividade, 1000);
    }

    // Função para parar verificação de sessão
    function pararVerificacaoSessao() {
        removerListenersAtividadeUsuario();

        if (intervaloVerificacaoSessao) {
            clearInterval(intervaloVerificacaoSessao);
            intervaloVerificacaoSessao = null;
        }
        if (intervaloVerificacaoInatividade) {
            clearInterval(intervaloVerificacaoInatividade);
            intervaloVerificacaoInatividade = null;
        }

        removerModalAvisoSessao();
    }

    // Função para reiniciar verificação de sessão (útil após navegação)
    function reiniciarVerificacaoSessao(tempoSessaoSegundos = 300) {
        pararVerificacaoSessao();
        setTimeout(() => {
            iniciarVerificacaoSessao(tempoSessaoSegundos);
        }, 100);
    }

    // Torna as funções globais
    window.iniciarVerificacaoSessao = iniciarVerificacaoSessao;
    window.pararVerificacaoSessao = pararVerificacaoSessao;
    window.reiniciarVerificacaoSessao = reiniciarVerificacaoSessao;
    window.verificarSessaoAtivaNoServidor = verificarSessaoAtivaNoServidor;
    window.mostrarModalSessaoExpirada = mostrarModalSessaoExpirada;

    // Função de debug para testar
    window.debugInformacoesSessao = function () {
        const timestampAtual = Date.now();
        const tempoInativo = timestampAtual - timestampUltimaAtividade;
        const segundosInativos = Math.floor(tempoInativo / 1000);
        console.log(`Debug Sessão:
        - Tempo inativo: ${segundosInativos}s
        - Limite: ${tempoLimiteSessaoInatividade / 1000}s
        - Modal ativo: ${modalAvisoSessaoAtivo}
        - Última atividade: ${new Date(timestampUltimaAtividade).toLocaleTimeString()}
        - Intervalos ativos: verificação=${intervaloVerificacaoSessao !== null}, inatividade=${intervaloVerificacaoInatividade !== null}`);
    };

    // Função de debug para forçar expiração de sessão (teste)
    window.debugForcarExpiracao = function () {
        console.log('DEBUG: Forçando expiração de sessão para teste');
        timestampUltimaAtividade = Date.now() - (tempoLimiteSessaoInatividade + 1000);
    };

    // Função de debug para verificar status do modal
    window.debugStatusModal = function () {
        const modalSessao = document.getElementById('modalSessaoExpirada');
        const modalAviso = document.getElementById('avisoSessaoExpirando');
        console.log(`Debug Modal:
        - Modal sessão expirada existe: ${modalSessao !== null}
        - Modal aviso existe: ${modalAviso !== null}
        - Modal aviso ativo (var): ${modalAvisoSessaoAtivo}`);
    };

    // Auto-inicializa se estiver em uma página de usuário logado
    document.addEventListener('DOMContentLoaded', function () {
        const usuarioEstaLogado = window.location.pathname.includes('/PaginasParticipante/') ||
            window.location.pathname.includes('/PaginasOrganizador/');

        if (usuarioEstaLogado) {
            iniciarVerificacaoSessao(300); // 300 segundos (5 minutos)
        }
    });
})();