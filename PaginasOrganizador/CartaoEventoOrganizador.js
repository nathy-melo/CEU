function mostrarMensagemInscricaoFeita() {
    var mainContent = document.getElementById('main-content');
    if (!mainContent) return;
    mainContent.innerHTML = '';
    var container = document.createElement('div');
    container.style.display = 'flex';
    container.style.flexDirection = 'column';
    container.style.alignItems = 'center';
    container.style.justifyContent = 'center';
    container.style.background = '#4f6c8c';
    container.style.borderRadius = '1.875rem';
    container.style.padding = '1.875rem';
    container.style.maxWidth = '51.5625rem';
    container.style.margin = '0 auto';
    container.style.width = '100%';
    var titulo = document.createElement('h2');
    titulo.textContent = 'Solicitação enviada!';
    titulo.style.color = '#fff';
    titulo.style.fontSize = '1.8em';
    titulo.style.marginBottom = '0.2em';
    titulo.style.textAlign = 'center';
    var botoesWrapper = document.createElement('div');
    botoesWrapper.style.display = 'flex';
    botoesWrapper.style.flexDirection = 'row';
    botoesWrapper.style.justifyContent = 'space-between';
    botoesWrapper.style.gap = '1rem'; // Adiciona espaço entre os botões
    botoesWrapper.style.alignItems = 'flex-end';
    botoesWrapper.style.width = '100%';
    botoesWrapper.style.marginTop = '1em';
    var btnVoltar = document.createElement('button');
    btnVoltar.type = 'button';
    btnVoltar.className = 'botao-voltar';
    btnVoltar.textContent = 'Voltar';
    btnVoltar.style.alignSelf = 'flex-start';
    btnVoltar.onclick = function() { carregarPagina('inicio'); };
    var btnCancelar = document.createElement('button');
    btnCancelar.type = 'button';
    btnCancelar.className = 'botao-cancelar';
    btnCancelar.textContent = 'Cancelar Solicitação';
    btnCancelar.style.backgroundColor = '#d9534f';
    btnCancelar.style.color = '#fff';
    btnCancelar.style.border = 'none';
    btnCancelar.style.borderRadius = '0.225rem';
    btnCancelar.style.padding = '0.6rem 2rem';
    btnCancelar.style.fontWeight = '700';
    btnCancelar.style.fontSize = '1.125rem';
    btnCancelar.style.cursor = 'pointer';
    btnCancelar.style.minWidth = '8rem';
    btnCancelar.style.alignSelf = 'flex-end';
    btnCancelar.onclick = function() { location.reload(); };
    botoesWrapper.appendChild(btnVoltar);
    botoesWrapper.appendChild(btnCancelar);
    container.appendChild(titulo);
    container.appendChild(botoesWrapper);
    mainContent.appendChild(container);
}

function inicializarEventosCartaoEvento() {
    var btnInscrever = document.querySelector('.botao-inscrever');
    if (btnInscrever) {
        btnInscrever.onclick = mostrarMensagemInscricaoFeita;
    }
}

// Tenta inicializar ao carregar o HTML normalmente
window.addEventListener('DOMContentLoaded', inicializarEventosCartaoEvento);

// Permite que outros scripts chamem a inicialização após AJAX
window.inicializarEventosCartaoEvento = inicializarEventosCartaoEvento;