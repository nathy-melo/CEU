// Script global para adicionar botões de mostrar/ocultar senha em todos os campos de senha
(function(){
    if (window.__toggleSenhaInicializado) return;
    window.__toggleSenhaInicializado = true;

    function criarBotao(campo){
        if (!campo || campo.dataset.toggleSenhaAtivo) return;
        var wrapper = campo.parentElement; if(!wrapper) return;
        // Garante posição relativa no container do input
        if (getComputedStyle(wrapper).position === 'static') {
            wrapper.style.position = 'relative';
        }

        // Aumenta padding-right do input para não sobrepor texto (apenas uma vez)
        if (!campo.dataset.toggleSenhaPadding) {
            var paddingOriginal = window.getComputedStyle(campo).paddingRight;
            campo.dataset.toggleSenhaPadding = paddingOriginal;
            campo.style.paddingRight = '3.2em';
        }

        var botao = document.createElement('button');
        botao.type = 'button';
    botao.className = 'btn-toggle-senha';
    botao.setAttribute('aria-label', 'Mostrar senha');

        var img = document.createElement('img');
        img.alt = 'Mostrar senha';
        img.src = '../Imagens/MostrarSenha.svg';
        img.style.width = '100%';
        img.style.height = '100%';
        img.style.objectFit = 'contain';
        img.style.pointerEvents = 'none';
        botao.appendChild(img);

        botao.addEventListener('click', function(e){
            e.preventDefault();
            if (campo.type === 'password') {
                campo.type = 'text';
                img.src = '../Imagens/OcultarSenha.svg';
                img.alt = 'Ocultar senha';
                botao.setAttribute('aria-label', 'Ocultar senha');
            } else {
                campo.type = 'password';
                img.src = '../Imagens/MostrarSenha.svg';
                img.alt = 'Mostrar senha';
                botao.setAttribute('aria-label', 'Mostrar senha');
            }
        });
        wrapper.appendChild(botao);

        // Exibe somente depois que o usuário começar a digitar
        function atualizarEstadoBotao(){
            if (campo.value && campo.value.length > 0) {
                botao.classList.add('btn-toggle-senha--visivel');
            } else {
                botao.classList.remove('btn-toggle-senha--visivel');
                if (campo.type === 'text') { // volta para password caso tenha limpado
                    campo.type = 'password';
                    img.src = '../Imagens/MostrarSenha.svg';
                    img.alt = 'Mostrar senha';
                    botao.setAttribute('aria-label', 'Mostrar senha');
                }
            }
        }
        campo.addEventListener('input', atualizarEstadoBotao);
        campo.addEventListener('change', atualizarEstadoBotao);
        campo.addEventListener('blur', atualizarEstadoBotao);
        // Caso já venha preenchido (ex: navegadores salvam senha)
        setTimeout(atualizarEstadoBotao, 0);
        campo.dataset.toggleSenhaAtivo = '1';
    }

    function aplicarToggleEmTodos(){
        var campos = document.querySelectorAll('input[type="password"], input[data-tipo-senha]');
        campos.forEach(criarBotao);
    }

    // Reconhece trocas dinâmicas de conteúdo (#conteudo-dinamico)
    var alvo = document.getElementById('conteudo-dinamico') || document.body;
    var observer = new MutationObserver(function(muts){
        var precisa = false;
        for (var i=0;i<muts.length;i++){
            if (muts[i].addedNodes && muts[i].addedNodes.length){
                precisa = true; break;
            }
        }
        if (precisa) aplicarToggleEmTodos();
    });
    observer.observe(alvo, {childList:true, subtree:true});

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', aplicarToggleEmTodos);
    } else {
        aplicarToggleEmTodos();
    }

    // Expõe para chamadas manuais após carregamentos de rotas
    window.aplicarToggleSenhas = aplicarToggleEmTodos;
})();
