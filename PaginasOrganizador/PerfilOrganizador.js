// Função para mostrar o modal de confirmação de exclusão de conta
function mostrarModalExcluirConta() {
    var mainContent = document.getElementById('main-content');
    if (!mainContent) return;
    // Limpa o conteúdo atual
    mainContent.innerHTML = '';

    // Cria o container do modal
    var container = document.createElement('div');
    container.style.display = 'flex';
    container.style.flexDirection = 'column';
    container.style.alignItems = 'center';
    container.style.justifyContent = 'center';
    container.style.background = 'var(--caixas)';
    container.style.borderRadius = '0.7rem';
    container.style.padding = '2rem 2.5rem 1.5rem 2.5rem';
    container.style.maxWidth = '32rem';
    container.style.margin = '3rem auto 0 auto';
    container.style.width = '100%';
    container.style.boxShadow = '0 0.25rem 1rem rgba(0,0,0,0.25)';

    // Mensagem
    var mensagem = document.createElement('div');
    mensagem.textContent = 'Você tem certeza que deseja excluir a conta?';
    mensagem.style.whiteSpace = 'nowrap';
    mensagem.style.color = '#fff';
    mensagem.style.fontWeight = '700';
    mensagem.style.fontSize = '1.35rem';
    mensagem.style.textAlign = 'center';
    mensagem.style.marginBottom = '2rem';
    mensagem.style.marginTop = '0.5rem';

    // Botões
    var botoesWrapper = document.createElement('div');
    botoesWrapper.style.display = 'flex';
    botoesWrapper.style.flexDirection = 'row';
    botoesWrapper.style.justifyContent = 'center';
    botoesWrapper.style.alignItems = 'center';
    botoesWrapper.style.gap = '2.5rem';
    
    var btnCancelar = document.createElement('button');
    btnCancelar.type = 'button';
    btnCancelar.textContent = 'Cancelar';
    btnCancelar.style.backgroundColor = 'var(--botao)';
    btnCancelar.style.color = '#fff';
    btnCancelar.style.border = 'none';
    btnCancelar.style.borderRadius = '0.3rem';
    btnCancelar.style.padding = '0.5rem 2.5rem';
    btnCancelar.style.fontWeight = '700';
    btnCancelar.style.fontSize = '1.1rem';
    btnCancelar.style.cursor = 'pointer';
    btnCancelar.style.boxShadow = '0 0.125rem 0.25rem rgba(0,0,0,0.15)';
    btnCancelar.className = 'botao';
    btnCancelar.onclick = function() { window.location.reload(); };

    var btnConfirmar = document.createElement('button');
    btnConfirmar.type = 'button';
    btnConfirmar.textContent = 'Confirmar';
    btnConfirmar.style.backgroundColor = '#7a0909';
    btnConfirmar.style.color = '#fff';
    btnConfirmar.style.border = 'none';
    btnConfirmar.style.borderRadius = '0.3rem';
    btnConfirmar.style.padding = '0.5rem 2.5rem';
    btnConfirmar.style.fontWeight = '700';
    btnConfirmar.style.fontSize = '1.1rem';
    btnConfirmar.style.cursor = 'pointer';
    btnConfirmar.style.boxShadow = '0 0.125rem 0.25rem rgba(0,0,0,0.15)';
    btnConfirmar.className = 'botao';
    btnConfirmar.onclick = function() {
        // Aqui você pode colocar a lógica real de exclusão de conta
        alert('Conta excluída!');
        window.location.href = '../PaginasPublicas/PrimeiraPagina.html';
    };

    botoesWrapper.appendChild(btnCancelar);
    botoesWrapper.appendChild(btnConfirmar);
    
    container.appendChild(mensagem);
    container.appendChild(botoesWrapper);
    mainContent.appendChild(container);
}

function inicializarEventosPerfilOrganizador() {
    var btnExcluir = document.getElementById('btn-excluir-conta') || document.querySelector('.botao-excluir');
    if (btnExcluir) {
        btnExcluir.onclick = mostrarModalExcluirConta;
    }
}

// Inicializa ao carregar normalmente
window.addEventListener('DOMContentLoaded', inicializarEventosPerfilOrganizador);
// Permite inicialização após AJAX
window.inicializarEventosPerfilOrganizador = inicializarEventosPerfilOrganizador; 