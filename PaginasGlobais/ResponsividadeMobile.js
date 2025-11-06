/**
 * ResponsividadeMobile.js
 * Gerenciamento MINIMALISTA de overlay para menu e filtro no mobile
 * Não interfere no comportamento existente do desktop
 */

(function() {
    'use strict';
    
    // Variável para guardar a posição de scroll
    let scrollPosition = 0;
    
    // Detectar se é mobile
    function isMobile() {
        return window.innerWidth <= 768;
    }
    
    // Gerenciar estado do menu
    function gerenciarMenu() {
        const menu = document.querySelector('.Menu');
        if (!menu) return;
        
        const mainContent = document.getElementById('main-content');
        
        // CORREÇÃO: No mobile, forçar menu fechado ao carregar
        if (isMobile()) {
            menu.classList.remove('expanded');
            if (mainContent) mainContent.classList.remove('shifted');
            document.body.classList.remove('menu-open');
            document.body.style.overflow = '';
            document.body.style.top = '';
        }
        
        // Observer para detectar quando o menu abre/fecha
        const observer = new MutationObserver(function() {
            if (!isMobile()) {
                document.body.classList.remove('menu-open');
                document.body.style.overflow = '';
                document.body.style.top = '';
                return;
            }
            
            // Apenas no mobile: adicionar classe e prevenir scroll
            if (menu.classList.contains('expanded')) {
                // Salvar posição atual de scroll
                scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
                
                document.body.classList.add('menu-open');
                document.body.style.overflow = 'hidden';
                document.body.style.top = `-${scrollPosition}px`;
                
                // NO MOBILE: Remover .shifted para evitar redimensionamento do container
                if (mainContent) mainContent.classList.remove('shifted');
            } else {
                document.body.classList.remove('menu-open');
                
                // Só restaurar scroll se filtro também não estiver aberto
                if (!document.body.classList.contains('filtro-open')) {
                    document.body.style.overflow = '';
                    document.body.style.top = '';
                    // Restaurar posição de scroll
                    window.scrollTo(0, scrollPosition);
                }
            }
        });
        
        observer.observe(menu, { attributes: true, attributeFilter: ['class'] });
        
        // Fechar ao clicar no overlay
        document.addEventListener('click', function(e) {
            if (!isMobile()) return;
            if (!menu.classList.contains('expanded')) return;
            
            // Se clicou fora do menu
            if (!menu.contains(e.target) && !e.target.closest('.menu-toggle')) {
                menu.classList.remove('expanded');
                if (mainContent) mainContent.classList.remove('shifted');
            }
        });
    }
    
    // Gerenciar estado do filtro
    function gerenciarFiltro() {
        const filtro = document.querySelector('.filtro-container');
        if (!filtro) return;
        
        const mainContent = document.getElementById('main-content');
        
        // CORREÇÃO: No mobile, forçar filtro fechado ao carregar
        if (isMobile()) {
            filtro.classList.remove('ativo');
            if (mainContent) mainContent.classList.remove('filtro-shifted');
            document.body.classList.remove('filtro-open');
            document.body.style.overflow = '';
            document.body.style.top = '';
        }
        
        // Observer para detectar quando o filtro abre/fecha
        const observer = new MutationObserver(function() {
            if (!isMobile()) {
                document.body.classList.remove('filtro-open');
                document.body.style.overflow = '';
                document.body.style.top = '';
                return;
            }
            
            // Apenas no mobile: adicionar classe e prevenir scroll
            if (filtro.classList.contains('ativo')) {
                // Salvar posição atual de scroll
                scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
                
                document.body.classList.add('filtro-open');
                document.body.style.overflow = 'hidden';
                document.body.style.top = `-${scrollPosition}px`;
                
                // NO MOBILE: Remover .filtro-shifted para evitar redimensionamento do container
                if (mainContent) mainContent.classList.remove('filtro-shifted');
            } else {
                document.body.classList.remove('filtro-open');
                
                // Só restaurar scroll se menu também não estiver aberto
                if (!document.body.classList.contains('menu-open')) {
                    document.body.style.overflow = '';
                    document.body.style.top = '';
                    // Restaurar posição de scroll
                    window.scrollTo(0, scrollPosition);
                }
            }
        });
        
        observer.observe(filtro, { attributes: true, attributeFilter: ['class'] });
        
        // Fechar ao clicar no overlay
        document.addEventListener('click', function(e) {
            if (!isMobile()) return;
            if (!filtro.classList.contains('ativo')) return;
            
            // Se clicou fora do filtro
            if (!filtro.contains(e.target) && !e.target.closest('.botao-filtrar')) {
                filtro.classList.remove('ativo');
                if (mainContent) mainContent.classList.remove('filtro-shifted');
            }
        });
    }
    
    // Gestos de swipe para abrir/fechar menu
    function gerenciarSwipe() {
        if (!isMobile()) return;
        
        const menu = document.querySelector('.Menu');
        const mainContent = document.getElementById('main-content');
        if (!menu) return;
        
        let touchStartX = 0;
        let touchStartY = 0;
        let touchEndX = 0;
        let touchEndY = 0;
        
        // Detectar início do toque
        document.addEventListener('touchstart', function(e) {
            if (!isMobile()) return;
            touchStartX = e.changedTouches[0].screenX;
            touchStartY = e.changedTouches[0].screenY;
        }, { passive: true });
        
        // Detectar fim do toque
        document.addEventListener('touchend', function(e) {
            if (!isMobile()) return;
            touchEndX = e.changedTouches[0].screenX;
            touchEndY = e.changedTouches[0].screenY;
            handleSwipe();
        }, { passive: true });
        
        function handleSwipe() {
            const deltaX = touchEndX - touchStartX;
            const deltaY = Math.abs(touchEndY - touchStartY);
            const minSwipeDistance = 50; // mínimo de 50px
            const maxVerticalMovement = 100; // máximo movimento vertical permitido
            
            // Verificar se é um swipe horizontal (não vertical)
            if (deltaY > maxVerticalMovement) return;
            
            // Swipe da ESQUERDA para DIREITA (abrir menu)
            if (deltaX > minSwipeDistance && touchStartX < 50) {
                // Começou do canto esquerdo e deslizou para direita
                if (!menu.classList.contains('expanded')) {
                    menu.classList.add('expanded');
                    if (mainContent) mainContent.classList.add('shifted');
                }
            }
            
            // Swipe da DIREITA para ESQUERDA (fechar menu se aberto)
            else if (deltaX < -minSwipeDistance && menu.classList.contains('expanded')) {
                menu.classList.remove('expanded');
                if (mainContent) mainContent.classList.remove('shifted');
            }
        }
    }
    
    // Limpar estados ao redimensionar para desktop/mobile
    function gerenciarResize() {
        let timeoutResize;
        window.addEventListener('resize', function() {
            clearTimeout(timeoutResize);
            timeoutResize = setTimeout(function() {
                const menu = document.querySelector('.Menu');
                const filtro = document.querySelector('.filtro-container');
                const mainContent = document.getElementById('main-content');
                
                if (!isMobile()) {
                    // Desktop: limpar apenas overlays
                    document.body.classList.remove('menu-open', 'filtro-open');
                    document.body.style.overflow = '';
                    document.body.style.top = '';
                } else {
                    // Mobile: forçar menu e filtro fechados
                    if (menu) menu.classList.remove('expanded');
                    if (filtro) filtro.classList.remove('ativo');
                    if (mainContent) {
                        mainContent.classList.remove('shifted', 'filtro-shifted');
                    }
                    document.body.classList.remove('menu-open', 'filtro-open');
                    document.body.style.overflow = '';
                    document.body.style.top = '';
                }
            }, 150);
        });
    }

    // Ajuste de safe-area para navegadores com barra inferior (Samsung Internet, Safari iOS)
    function aplicarSafeAreaDinamica() {
        function atualizar() {
            try {
                const vv = window.visualViewport;
                let occludedBottom = 0;
                if (vv) {
                    // Parte inferior oculta = altura do layout viewport - (altura visível + offset do topo)
                    occludedBottom = Math.max(0, (window.innerHeight - (vv.height + vv.offsetTop)));
                }
                // Atualiza variável CSS global usada no layout
                document.documentElement.style.setProperty('--ceu-safe-bottom', occludedBottom + 'px');
            } catch (_) {}
        }
        // Atualiza em eventos relevantes
        atualizar();
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', atualizar);
            window.visualViewport.addEventListener('scroll', atualizar);
        }
        window.addEventListener('orientationchange', atualizar);
        window.addEventListener('resize', atualizar);
    }
    
    // Inicializar
    function init() {
        gerenciarMenu();
        gerenciarFiltro();
        gerenciarSwipe();
        gerenciarResize();
        aplicarSafeAreaDinamica();
        
        // Log desabilitado para reduzir ruído no console
    }
    
    // Rodar quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})();
