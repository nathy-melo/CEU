// Sistema Global de Limpeza de Timers e Funções - CEU
(function() {
    // Array para armazenar todos os timers ativos
    let timersAtivos = [];
    let intervalosAtivos = [];
    let timeoutsAtivos = [];
    
    // Sobrescrever funções originais para rastreamento
    const setIntervalOriginal = window.setInterval;
    const setTimeoutOriginal = window.setTimeout;
    const clearIntervalOriginal = window.clearInterval;
    const clearTimeoutOriginal = window.clearTimeout;
    
    // Função para registrar intervalos
    window.setInterval = function(callback, delay, ...args) {
        const id = setIntervalOriginal.call(this, callback, delay, ...args);
        intervalosAtivos.push({
            id: id,
            tipo: 'interval',
            callback: callback.name || 'anonymous',
            delay: delay,
            timestamp: Date.now()
        });
        console.log(`[Gerenciador Timers] Registrado interval ID: ${id}, delay: ${delay}ms`);
        return id;
    };
    
    // Função para registrar timeouts
    window.setTimeout = function(callback, delay, ...args) {
        const id = setTimeoutOriginal.call(this, callback, delay, ...args);
        timeoutsAtivos.push({
            id: id,
            tipo: 'timeout',
            callback: callback.name || 'anonymous',
            delay: delay,
            timestamp: Date.now()
        });
        console.log(`[Gerenciador Timers] Registrado timeout ID: ${id}, delay: ${delay}ms`);
        return id;
    };
    
    // Função para limpar intervalos
    window.clearInterval = function(id) {
        clearIntervalOriginal.call(this, id);
        intervalosAtivos = intervalosAtivos.filter(timer => timer.id !== id);
        console.log(`[Gerenciador Timers] Limpo interval ID: ${id}`);
    };
    
    // Função para limpar timeouts
    window.clearTimeout = function(id) {
        clearTimeoutOriginal.call(this, id);
        timeoutsAtivos = timeoutsAtivos.filter(timer => timer.id !== id);
        console.log(`[Gerenciador Timers] Limpo timeout ID: ${id}`);
    };
    
    // Função para limpar todos os timers ativos
    function limparTodosOsTimers() {
        console.log(`[Gerenciador Timers] Limpando ${intervalosAtivos.length} intervalos e ${timeoutsAtivos.length} timeouts`);
        
        // Limpar todos os intervalos
        intervalosAtivos.forEach(timer => {
            clearIntervalOriginal(timer.id);
            console.log(`[Gerenciador Timers] Limpando interval: ${timer.callback} (${timer.delay}ms)`);
        });
        
        // Limpar todos os timeouts
        timeoutsAtivos.forEach(timer => {
            clearTimeoutOriginal(timer.id);
            console.log(`[Gerenciador Timers] Limpando timeout: ${timer.callback} (${timer.delay}ms)`);
        });
        
        // Resetar arrays
        intervalosAtivos = [];
        timeoutsAtivos = [];
        
        console.log('[Gerenciador Timers] Todos os timers foram limpos');
    }
    
    // Função para listar timers ativos (debug)
    function listarTimersAtivos() {
        console.log('[Gerenciador Timers] Timers ativos:');
        console.log('Intervalos:', intervalosAtivos);
        console.log('Timeouts:', timeoutsAtivos);
        return {
            intervalos: intervalosAtivos.length,
            timeouts: timeoutsAtivos.length,
            total: intervalosAtivos.length + timeoutsAtivos.length
        };
    }
    
    // Função para limpar listeners de eventos
    function limparEventListeners() {
        console.log('[Gerenciador Timers] Limpando event listeners...');
        
        // Remove todos os listeners de elementos específicos que podem causar problemas
        const elementosProblematicos = [
            'form', 'button', 'input', 'select', 'textarea'
        ];
        
        elementosProblematicos.forEach(seletor => {
            const elementos = document.querySelectorAll(seletor);
            elementos.forEach(elemento => {
                // Clona o elemento para remover todos os listeners
                const novoElemento = elemento.cloneNode(true);
                elemento.parentNode.replaceChild(novoElemento, elemento);
            });
        });
    }
    
    // Função para resetar variáveis globais específicas do CEU
    function resetarVariaveisGlobais() {
        console.log('[Gerenciador Timers] Resetando variáveis globais...');
        
        // Lista de variáveis globais que devem ser resetadas
        const variaveisParaResetar = [
            'conteudoOriginalFaleConosco',
            'conteudoOriginalRedefinirSenha',
            'estadoFiltro',
            'menuContentObserver',
            'temporizadorCadastro',
            'timeoutBotaoLogin',
            'timeoutAlertaAtivo',
            'timeoutRedirecionamento',
            'eventosCancelamentoTimer'
        ];
        
        variaveisParaResetar.forEach(nomeVariavel => {
            if (window[nomeVariavel] !== undefined) {
                window[nomeVariavel] = null;
                console.log(`[Gerenciador Timers] Resetada variável: ${nomeVariavel}`);
            }
        });
        
        // Limpar sessionStorage de flags específicas
        const sessionKeysParaLimpar = [
            'faleConoscoEnviado',
            'redefinirSenhaEnviado'
        ];
        
        sessionKeysParaLimpar.forEach(key => {
            if (sessionStorage.getItem(key)) {
                sessionStorage.removeItem(key);
                console.log(`[Gerenciador Timers] Removida sessionStorage key: ${key}`);
            }
        });
    }
    
    // Função principal de limpeza completa
    function limpezaCompleta() {
        console.log('[Gerenciador Timers] Iniciando limpeza completa...');
        
        // Parar verificação de sessão se estiver ativa
        if (typeof window.pararVerificacaoSessao === 'function') {
            window.pararVerificacaoSessao();
        }
        
        // Limpar listeners de cancelamento de timer se existirem
        if (window.eventosCancelamentoTimer && Array.isArray(window.eventosCancelamentoTimer)) {
            console.log('[Gerenciador Timers] Removendo listeners de cancelamento de timer');
            window.eventosCancelamentoTimer.forEach(evento => {
                try {
                    document.removeEventListener(evento, window.cancelarTimer);
                } catch (e) {
                    // Ignore erros se a função não existir
                }
            });
        }
        
        // Limpar todos os timers
        limparTodosOsTimers();
        
        // Resetar variáveis globais
        resetarVariaveisGlobais();
        
        // Limpar modais e alertas ativos
        const modaisAtivos = document.querySelectorAll('.modal-personalizado, .alert, .tooltip-custom');
        modaisAtivos.forEach(modal => {
            if (modal.parentNode) {
                modal.parentNode.removeChild(modal);
            }
        });
        
        console.log('[Gerenciador Timers] Limpeza completa finalizada');
    }
    
    // Tornar funções globais
    window.limparTodosOsTimers = limparTodosOsTimers;
    window.listarTimersAtivos = listarTimersAtivos;
    window.limpezaCompleta = limpezaCompleta;
    window.resetarVariaveisGlobais = resetarVariaveisGlobais;
    
    // Auto-limpeza antes de sair da página
    window.addEventListener('beforeunload', function() {
        console.log('[Gerenciador Timers] Limpeza automática ao sair da página');
        limpezaCompleta();
    });
    
    // Limpeza quando a página fica invisível (melhor que unload)
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden') {
            console.log('[Gerenciador Timers] Limpeza automática - página oculta');
            limpezaCompleta();
        }
    });
    
    console.log('[Gerenciador Timers] Sistema de limpeza inicializado');
})();