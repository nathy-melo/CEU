let conteudoOriginalFaleConosco = null;
function mostrarMensagemFaleConosco() {
    var mainContent = document.getElementById('main-content');
    if (!mainContent) return;
    if (conteudoOriginalFaleConosco === null) {
        conteudoOriginalFaleConosco = mainContent.innerHTML;
    }
    mainContent.innerHTML = '';
    var container = document.createElement('div');
    container.style.display = 'flex';
    container.style.flexDirection = 'column';
    container.style.alignItems = 'center';
    container.style.justifyContent = 'center';
    container.style.background = '#4f6c8c';
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
    btnVoltar.className = ' botao-voltar';
    btnVoltar.textContent = 'Voltar';
    btnVoltar.onclick = function() {
        mainContent.innerHTML = conteudoOriginalFaleConosco;
        conteudoOriginalFaleConosco = null;
        // Limpa o estado de confirmação ao voltar
        sessionStorage.removeItem('faleConoscoEnviado');
    };
    container.appendChild(titulo);
    container.appendChild(mensagem);
    container.appendChild(btnVoltar);
    mainContent.appendChild(container);
    // Limpa o estado de confirmação após exibir
    sessionStorage.removeItem('faleConoscoEnviado');
}

// Adiciona o controle de exibição da mensagem ao carregar a página
window.addEventListener('DOMContentLoaded', function() {
    // Adiciona evento ao botão Enviar do formulário
    var form = document.querySelector('.cartao-formulario form');
    if (form) {
        var btnEnviar = form.querySelector('button[type="submit"]');
        if (btnEnviar) {
            btnEnviar.onclick = function(e) {
                e.preventDefault();
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }
                sessionStorage.setItem('faleConoscoEnviado', '1');
                mostrarMensagemFaleConosco();
            };
        }
    }
    // Se a flag está setada, mostra a mensagem e limpa a flag
    if (sessionStorage.getItem('faleConoscoEnviado')) {
        sessionStorage.removeItem('faleConoscoEnviado');
        mostrarMensagemFaleConosco();
    }
});
