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
        return id;
    };
    
    // Função para limpar intervalos
    window.clearInterval = function(id) {
        clearIntervalOriginal.call(this, id);
        intervalosAtivos = intervalosAtivos.filter(timer => timer.id !== id);
        // console.log(`[Gerenciador Timers] Limpo interval ID: ${id}`); // Log desabilitado
    };
    
    // Função para limpar timeouts
    window.clearTimeout = function(id) {
        clearTimeoutOriginal.call(this, id);
        timeoutsAtivos = timeoutsAtivos.filter(timer => timer.id !== id);
        // console.log(`[Gerenciador Timers] Limpo timeout ID: ${id}`); // Log desabilitado
    };
    
    // Função para limpar todos os timers ativos
    function limparTodosOsTimers() {
        // Limpar todos os intervalos
        intervalosAtivos.forEach(timer => {
            clearIntervalOriginal(timer.id);
        });
        
        // Limpar todos os timeouts
        timeoutsAtivos.forEach(timer => {
            clearTimeoutOriginal(timer.id);
        });
        
        // Resetar arrays
        intervalosAtivos = [];
        timeoutsAtivos = [];
        
        // Log desabilitado
    }
    
    // Função para listar timers ativos (debug)
    function listarTimersAtivos() {
        return {
            intervalos: intervalosAtivos.length,
            timeouts: timeoutsAtivos.length,
            total: intervalosAtivos.length + timeoutsAtivos.length
        };
    }
    
    // Função para limpar listeners de eventos
    function limparEventListeners() {        
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
            }
        });
    }
    
    // Função principal de limpeza completa
    function limpezaCompleta() {
        // Parar verificação de sessão se estiver ativa
        if (typeof window.pararVerificacaoSessao === 'function') {
            window.pararVerificacaoSessao();
        }
        
        // Limpar listeners de cancelamento de timer se existirem
        if (window.eventosCancelamentoTimer && Array.isArray(window.eventosCancelamentoTimer)) {
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
    }
    
    // Tornar funções globais
    window.limparTodosOsTimers = limparTodosOsTimers;
    window.listarTimersAtivos = listarTimersAtivos;
    window.limpezaCompleta = limpezaCompleta;
    window.resetarVariaveisGlobais = resetarVariaveisGlobais;
    
    // Auto-limpeza antes de sair da página
    window.addEventListener('beforeunload', function() {
        limpezaCompleta();
    });
    
    // Limpeza quando a página fica invisível (melhor que unload)
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden') {
            limpezaCompleta();
        }
    });
    
})();