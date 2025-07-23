function mostrarMensagemDesinscricao() {
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
    container.style.maxWidth = '32rem';
    container.style.margin = '3rem auto 0 auto';
    container.style.width = '100%';
    container.style.boxShadow = '0 0.25rem 1rem rgba(0,0,0,0.25)';

    var titulo = document.createElement('h2');
    titulo.textContent = 'Você deseja cancelar a inscrição?';
    titulo.style.color = '#fff';
    titulo.style.fontSize = '1.5em';
    titulo.style.marginBottom = '2rem';
    titulo.style.textAlign = 'center';

    var botoesWrapper = document.createElement('div');
    botoesWrapper.style.display = 'flex';
    botoesWrapper.style.flexDirection = 'row';
    botoesWrapper.style.justifyContent = 'center';
    botoesWrapper.style.alignItems = 'center';
    botoesWrapper.style.gap = '2.5rem';

    var btnCancelar = document.createElement('button');
    btnCancelar.type = 'button';
    btnCancelar.textContent = 'Cancelar';
    btnCancelar.style.backgroundColor = '#6598d2';
    btnCancelar.style.color = '#fff';
    btnCancelar.style.border = 'none';
    btnCancelar.style.borderRadius = '0.3rem';
    btnCancelar.style.padding = '0.5rem 2.5rem';
    btnCancelar.style.fontWeight = '700';
    btnCancelar.style.fontSize = '1.1rem';
    btnCancelar.style.cursor = 'pointer';
    btnCancelar.style.boxShadow = '0 0.125rem 0.25rem rgba(0,0,0,0.15)';
    btnCancelar.onclick = function() { window.location.reload(); };

    var btnContinuar = document.createElement('button');
    btnContinuar.type = 'button';
    btnContinuar.textContent = 'Continuar';
    btnContinuar.style.backgroundColor = '#d9534f';
    btnContinuar.style.color = '#fff';
    btnContinuar.style.border = 'none';
    btnContinuar.style.borderRadius = '0.3rem';
    btnContinuar.style.padding = '0.5rem 2.5rem';
    btnContinuar.style.fontWeight = '700';
    btnContinuar.style.fontSize = '1.1rem';
    btnContinuar.style.cursor = 'pointer';
    btnContinuar.style.boxShadow = '0 0.125rem 0.25rem rgba(0,0,0,0.15)';
    btnContinuar.onclick = function() {
        // Aqui você pode colocar a lógica real de desinscrição
        alert('Inscrição cancelada!');
        carregarPagina('meusEventos');
    };

    botoesWrapper.appendChild(btnCancelar);
    botoesWrapper.appendChild(btnContinuar);
    container.appendChild(titulo);
    container.appendChild(botoesWrapper);
    mainContent.appendChild(container);
}

function inicializarEventosCartaoEventoInscrito() {
    var btnDesinscrever = document.querySelector('.botao-desinscrever');
    if (btnDesinscrever) {
        btnDesinscrever.onclick = mostrarMensagemDesinscricao;
    }
}

// Inicializa ao carregar normalmente
window.addEventListener('DOMContentLoaded', inicializarEventosCartaoEventoInscrito);
// Permite inicialização após AJAX
window.inicializarEventosCartaoEventoInscrito = inicializarEventosCartaoEventoInscrito;

// Garante inicialização imediata após carregamento dinâmico do JS
setTimeout(inicializarEventosCartaoEventoInscrito, 0);
