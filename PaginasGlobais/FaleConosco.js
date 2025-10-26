// Usa window para evitar erro de redeclaração ao recarregar o script
window.conteudoOriginalFaleConosco = window.conteudoOriginalFaleConosco || null;

function mostrarMensagemFaleConosco() {
    var mainContent = document.getElementById('main-content');
    if (!mainContent) return;
    if (window.conteudoOriginalFaleConosco === null) {
        window.conteudoOriginalFaleConosco = mainContent.innerHTML;
    }
    mainContent.innerHTML = '';
    var container = document.createElement('div');
    container.style.display = 'flex';
    container.style.flexDirection = 'column';
    container.style.alignItems = 'center';
    container.style.justifyContent = 'center';
    container.style.background = 'var(--caixas)';
    container.style.borderRadius = '2em';
    container.style.padding = '2em 2.5em';
    container.style.maxWidth = '600px';
    container.style.margin = '0 auto';
    var titulo = document.createElement('h2');
    titulo.textContent = 'Agradecemos o contato!';
    titulo.style.color = '#fff';
    titulo.style.fontSize = '2em';
    titulo.style.marginBottom = '0.5em';
    titulo.style.textAlign = 'center';
    var mensagem = document.createElement('div');
    mensagem.textContent = 'Enviaremos uma resposta por e-mail';
    mensagem.style.color = '#fff';
    mensagem.style.fontSize = '1.1em';
    mensagem.style.marginBottom = '2em';
    mensagem.style.textAlign = 'center';
    var btnVoltar = document.createElement('button');
    btnVoltar.type = 'button';
    btnVoltar.className = 'botao botao-voltar';
    btnVoltar.textContent = 'Voltar';
    btnVoltar.onclick = function() {
        mainContent.innerHTML = window.conteudoOriginalFaleConosco;
        window.conteudoOriginalFaleConosco = null;
        // Limpa o estado de confirmação ao voltar
        sessionStorage.removeItem('faleConoscoEnviado');
        // Reatribui o evento de submit ao formulário restaurado
        if (typeof window.inicializarFaleConosco === 'function') {
            window.inicializarFaleConosco();
        }
    };
    container.appendChild(titulo);
    container.appendChild(mensagem);
    container.appendChild(btnVoltar);
    mainContent.appendChild(container);
    // Limpa o estado de confirmação após exibir
    sessionStorage.removeItem('faleConoscoEnviado');
}

// Adiciona o controle de exibição da mensagem ao carregar a página
function inicializarFaleConosco() {
    console.log('Inicializando FaleConosco...');
    
    // Adiciona evento ao formulário para interceptar o submit
    var form = document.querySelector('.cartao-formulario form');
    console.log('Formulário encontrado:', form);
    
    if (form) {
        // Remove qualquer listener anterior para evitar duplicação
        form.onsubmit = null;
        
        // Adiciona o evento de submit
        form.addEventListener('submit', function(e) {
            console.log('Submit interceptado!');
            e.preventDefault();
            
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            console.log('Formulário válido, mostrando mensagem...');
            sessionStorage.setItem('faleConoscoEnviado', '1');
            mostrarMensagemFaleConosco();
        });
        
        console.log('Event listener adicionado ao formulário');
    } else {
        console.error('Formulário não encontrado!');
    }
    
    // Se a flag está setada, mostra a mensagem e limpa a flag
    if (sessionStorage.getItem('faleConoscoEnviado')) {
        sessionStorage.removeItem('faleConoscoEnviado');
        mostrarMensagemFaleConosco();
    }
}

// Chama a inicialização imediatamente se o DOM já estiver carregado
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarFaleConosco);
} else {
    inicializarFaleConosco();
}

// Exporta a função para uso global
window.inicializarFaleConosco = inicializarFaleConosco;
