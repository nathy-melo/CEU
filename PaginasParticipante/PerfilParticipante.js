// Função para aplicar máscara de RA (somente números)
function aplicarMascaraRA(input) {
    input.value = input.value.replace(/\D/g, '').substring(0, 7);
}

// Função para aplicar máscara de código organizador (letras e números)
function aplicarMascaraCodigo(input) {
    input.value = input.value.replace(/[^a-zA-Z0-9]/g, '').substring(0, 8);
}

// Estado dos campos
let estadoOriginal = {};

// Função para salvar estado original dos campos
function salvarEstadoOriginal() {
    estadoOriginal = {
        email: document.getElementById('email-input').value,
        ra: document.getElementById('ra-input') ? document.getElementById('ra-input').value : null
    };
}

// Função para restaurar estado original dos campos
function restaurarEstadoOriginal() {
    document.getElementById('email-input').value = estadoOriginal.email;
    
    const raInput = document.getElementById('ra-input');
    if (raInput && estadoOriginal.ra !== null) {
        raInput.value = estadoOriginal.ra;
    }
}

// Função para mostrar campos editáveis
function mostrarCamposEditaveis() {
    // Email
    document.getElementById('email-display').classList.add('hidden');
    document.getElementById('email-input').classList.remove('hidden');
    
    // RA (se existir)
    const raDisplay = document.getElementById('ra-display');
    const raInput = document.getElementById('ra-input');
    if (raDisplay && raInput) {
        raDisplay.classList.add('hidden');
        raInput.classList.remove('hidden');
    }
    
    // Botões
    document.getElementById('btn-editar').classList.add('hidden');
    document.getElementById('btn-cancelar').classList.remove('hidden');
    document.getElementById('btn-salvar').classList.remove('hidden');
    
    // Adicionar classe ao formulário
    document.getElementById('form-perfil-participante').classList.add('modo-edicao');
}

// Função para esconder campos editáveis
function esconderCamposEditaveis() {
    // Email
    document.getElementById('email-display').classList.remove('hidden');
    document.getElementById('email-input').classList.add('hidden');
    
    // RA (se existir)
    const raDisplay = document.getElementById('ra-display');
    const raInput = document.getElementById('ra-input');
    if (raDisplay && raInput) {
        raDisplay.classList.remove('hidden');
        raInput.classList.add('hidden');
    }
    
    // Botões
    document.getElementById('btn-editar').classList.remove('hidden');
    document.getElementById('btn-cancelar').classList.add('hidden');
    document.getElementById('btn-salvar').classList.add('hidden');
    
    // Remover classe do formulário
    document.getElementById('form-perfil-participante').classList.remove('modo-edicao');
}

// Função para validar formulário
function validarFormulario() {
    const email = document.getElementById('email-input').value.trim();
    
    if (!email) {
        mostrarAlerta('Por favor, preencha o email.', 'danger');
        return false;
    }
    
    // Validar formato do email
    const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!regexEmail.test(email)) {
        mostrarAlerta('Por favor, insira um email válido.', 'danger');
        return false;
    }
    
    // Validar RA se existir
    const raInput = document.getElementById('ra-input');
    if (raInput && raInput.value.trim() && raInput.value.length < 7) {
        mostrarAlerta('O RA deve ter 7 dígitos.', 'danger');
        return false;
    }
    
    return true;
}

// Função para atualizar displays após salvar
function atualizarDisplays() {
    // Email
    document.getElementById('email-display').textContent = document.getElementById('email-input').value;
    
    // RA (se existir)
    const raInput = document.getElementById('ra-input');
    const raDisplay = document.getElementById('ra-display');
    if (raInput && raDisplay) {
        raDisplay.textContent = raInput.value || 'Não informado';
    }
}

// Função para mostrar alertas
function mostrarAlerta(mensagem, tipo = 'success') {
    const container = document.getElementById('alert-container');
    if (!container) return;
    
    const alerta = document.createElement('div');
    alerta.className = `alert alert-${tipo}`;
    alerta.textContent = mensagem;
    
    container.innerHTML = '';
    container.appendChild(alerta);
    
    // Remove o alerta após 5 segundos
    if (window.timeoutAlertaAtivo) {
        clearTimeout(window.timeoutAlertaAtivo);
    }
    
    window.timeoutAlertaAtivo = setTimeout(() => {
        if (container.contains(alerta)) {
            container.removeChild(alerta);
        }
        window.timeoutAlertaAtivo = null;
    }, 5000);
}

// Função para salvar os dados do perfil
function salvarPerfil(event) {
    event.preventDefault();
    
    if (!validarFormulario()) {
        return;
    }
    
    const formData = new FormData(event.target);
    formData.append('acao', 'atualizar');
    
    fetch('PerfilParticipanteAcoes.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            mostrarAlerta('Dados atualizados com sucesso!', 'success');
            atualizarDisplays();
            esconderCamposEditaveis();
            salvarEstadoOriginal(); // Atualizar estado com os novos valores
        } else {
            mostrarAlerta('Erro ao atualizar dados: ' + data.mensagem, 'danger');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarAlerta('Erro ao atualizar dados.', 'danger');
    });
}

// Função para excluir conta
function excluirConta() {
    const formData = new FormData();
    formData.append('acao', 'excluir_conta');
    
    fetch('PerfilParticipanteAcoes.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            mostrarAlerta('Conta excluída com sucesso!', 'success');
            
            // Limpar timeout anterior se existir
            if (window.timeoutRedirecionamento) {
                clearTimeout(window.timeoutRedirecionamento);
            }
            
            window.timeoutRedirecionamento = setTimeout(() => {
                window.location.href = '../PaginasPublicas/ContainerPublico.php?pagina=login';
                window.timeoutRedirecionamento = null;
            }, 2000);
        } else {
            mostrarAlerta(data.mensagem || 'Erro ao excluir conta', 'danger');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarAlerta('Erro de conexão ao excluir conta', 'danger');
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
    btnConfirmar.onclick = excluirConta;

    botoesWrapper.appendChild(btnCancelar);
    botoesWrapper.appendChild(btnConfirmar);
    
    container.appendChild(mensagem);
    container.appendChild(botoesWrapper);
    mainContent.appendChild(container);
}

// Event listeners quando a página carrega
function inicializarEventosPerfilParticipante() {
    // Máscara para RA
    const raInput = document.getElementById('ra-input');
    if (raInput) {
        raInput.addEventListener('input', function() {
            aplicarMascaraRA(this);
        });
    }
    
    // Botão Editar
    const btnEditar = document.getElementById('btn-editar');
    if (btnEditar) {
        btnEditar.addEventListener('click', function() {
            salvarEstadoOriginal();
            mostrarCamposEditaveis();
        });
    }
    
    // Botão Cancelar
    const btnCancelar = document.getElementById('btn-cancelar');
    if (btnCancelar) {
        btnCancelar.addEventListener('click', function() {
            restaurarEstadoOriginal();
            esconderCamposEditaveis();
        });
    }
    
    // Formulário
    const formPerfil = document.getElementById('form-perfil-participante');
    if (formPerfil) {
        formPerfil.addEventListener('submit', salvarPerfil);
    }
    
    // Botão de excluir conta
    const btnExcluir = document.getElementById('btn-excluir-conta');
    if (btnExcluir) {
        btnExcluir.addEventListener('click', mostrarModalExcluirConta);
    }
    
    // Inicializar tooltips
    inicializarTooltips();
    
    // Salvar estado inicial
    salvarEstadoOriginal();
}

// Função para inicializar tooltips customizados
function inicializarTooltips() {
    let tooltip = null;
    
    // Criar elemento do tooltip
    function criarTooltip() {
        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.className = 'tooltip-custom';
            document.body.appendChild(tooltip);
        }
        return tooltip;
    }
    
    // Encontrar todos os elementos com data-tooltip
    const elementosComTooltip = document.querySelectorAll('[data-tooltip]');
    
    elementosComTooltip.forEach(elemento => {
        elemento.addEventListener('mouseenter', function(e) {
            const tooltipTexto = this.getAttribute('data-tooltip');
            if (tooltipTexto) {
                const tooltipEl = criarTooltip();
                tooltipEl.textContent = tooltipTexto;
                tooltipEl.classList.add('show');
            }
        });
        
        elemento.addEventListener('mousemove', function(e) {
            if (tooltip && tooltip.classList.contains('show')) {
                const x = e.clientX + 15; // 15px à direita do cursor
                const y = e.clientY + 15; // 15px abaixo do cursor
                
                // Verificar se o tooltip não sairá da tela
                const tooltipRect = tooltip.getBoundingClientRect();
                const maxX = window.innerWidth - tooltipRect.width - 10;
                const maxY = window.innerHeight - tooltipRect.height - 10;
                
                tooltip.style.left = Math.min(x, maxX) + 'px';
                tooltip.style.top = Math.min(y, maxY) + 'px';
            }
        });
        
        elemento.addEventListener('mouseleave', function() {
            if (tooltip) {
                tooltip.classList.remove('show');
            }
        });
    });
}

// Inicializa ao carregar normalmente
window.addEventListener('DOMContentLoaded', inicializarEventosPerfilParticipante);
// Permite inicialização após AJAX
window.inicializarEventosPerfilParticipante = inicializarEventosPerfilParticipante; 