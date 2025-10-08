// Variáveis para armazenar valores originais
let valoresOriginais = {};

// Função para habilitar modo de edição
function habilitarEdicao() {
    // Armazena os valores originais
    valoresOriginais = {
        name: document.getElementById('name').textContent,
        email: document.getElementById('email').textContent,
        phone: document.getElementById('phone').textContent,
        ra: document.getElementById('ra').textContent
    };

    // Esconde as divs de visualização e mostra os inputs
    document.getElementById('name').classList.add('escondido');
    document.getElementById('name-input').classList.remove('escondido');
    
    document.getElementById('email').classList.add('escondido');
    document.getElementById('email-input').classList.remove('escondido');
    
    document.getElementById('phone').classList.add('escondido');
    document.getElementById('phone-input').classList.remove('escondido');
    
    document.getElementById('ra').classList.add('escondido');
    document.getElementById('ra-input').classList.remove('escondido');

    // Alterna os botões
    document.getElementById('btn-editar').classList.add('escondido');
    document.getElementById('btn-cancelar').classList.remove('escondido');
    document.getElementById('btn-confirmar').classList.remove('escondido');
}

// Função para cancelar edição
function cancelarEdicao() {
    // Restaura os valores originais nos inputs
    document.getElementById('name-input').value = valoresOriginais.name;
    document.getElementById('email-input').value = valoresOriginais.email;
    document.getElementById('phone-input').value = valoresOriginais.phone;
    document.getElementById('ra-input').value = valoresOriginais.ra;

    // Mostra as divs de visualização e esconde os inputs
    document.getElementById('name').classList.remove('escondido');
    document.getElementById('name-input').classList.add('escondido');
    
    document.getElementById('email').classList.remove('escondido');
    document.getElementById('email-input').classList.add('escondido');
    
    document.getElementById('phone').classList.remove('escondido');
    document.getElementById('phone-input').classList.add('escondido');
    
    document.getElementById('ra').classList.remove('escondido');
    document.getElementById('ra-input').classList.add('escondido');

    // Alterna os botões
    document.getElementById('btn-editar').classList.remove('escondido');
    document.getElementById('btn-cancelar').classList.add('escondido');
    document.getElementById('btn-confirmar').classList.add('escondido');
}

// Função para confirmar edição
function confirmarEdicao(event) {
    event.preventDefault();

    // Valida os campos
    const nome = document.getElementById('name-input').value.trim();
    const email = document.getElementById('email-input').value.trim();
    
    if (!nome || !email) {
        alert('Por favor, preencha todos os campos obrigatórios.');
        return;
    }

    // Coleta os dados do formulário
    const formData = new FormData();
    formData.append('nome', nome);
    formData.append('email', email);
    formData.append('telefone', document.getElementById('phone-input').value.trim());
    formData.append('ra', document.getElementById('ra-input').value.trim());

    // Envia os dados para o servidor
    fetch('AtualizarPerfilOrganizador.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            // Atualiza as divs de visualização com os novos valores
            document.getElementById('name').textContent = nome;
            document.getElementById('email').textContent = email;
            document.getElementById('phone').textContent = document.getElementById('phone-input').value;
            document.getElementById('ra').textContent = document.getElementById('ra-input').value;

            // Volta para o modo de visualização
            cancelarEdicao();
            
            alert('Dados atualizados com sucesso!');
        } else {
            alert('Erro ao atualizar dados: ' + (data.mensagem || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao atualizar dados. Por favor, tente novamente.');
    });
}

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
        excluirConta();
    };

    botoesWrapper.appendChild(btnCancelar);
    botoesWrapper.appendChild(btnConfirmar);
    
    container.appendChild(mensagem);
    container.appendChild(botoesWrapper);
    mainContent.appendChild(container);
}

// Função para excluir conta
function excluirConta() {
    fetch('ExcluirContaOrganizador.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            alert('Conta excluída com sucesso!');
            window.location.href = '../PaginasPublicas/PrimeiraPagina.html';
        } else {
            alert('Erro ao excluir conta: ' + (data.mensagem || 'Erro desconhecido'));
            window.location.reload();
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao excluir conta. Por favor, tente novamente.');
        window.location.reload();
    });
}

function inicializarEventosPerfilOrganizador() {
    var btnExcluir = document.getElementById('btn-excluir-conta') || document.querySelector('.botao-excluir');
    if (btnExcluir) {
        btnExcluir.onclick = mostrarModalExcluirConta;
    }

    var btnEditar = document.getElementById('btn-editar');
    if (btnEditar) {
        btnEditar.onclick = habilitarEdicao;
    }

    var btnCancelar = document.getElementById('btn-cancelar');
    if (btnCancelar) {
        btnCancelar.onclick = cancelarEdicao;
    }

    var formPerfil = document.getElementById('form-perfil-organizador');
    if (formPerfil) {
        formPerfil.onsubmit = confirmarEdicao;
    }
}

// Inicializa ao carregar normalmente
window.addEventListener('DOMContentLoaded', inicializarEventosPerfilOrganizador);
// Permite inicialização após AJAX
window.inicializarEventosPerfilOrganizador = inicializarEventosPerfilOrganizador;