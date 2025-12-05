// Função para aplicar máscara de RA (somente números)
function aplicarMascaraRA(input) {
    input.value = input.value.replace(/\D/g, '').substring(0, 7);
}

// Função para aplicar máscara de código organizador (formato seguro: 8 caracteres A-Z, 1-9)
function aplicarMascaraCodigo(input) {
    // Remove caracteres não permitidos e converte para maiúsculo
    let valor = input.value.toUpperCase().replace(/[^ABCDEFGHJKLMNPQRSTUVWXYZ123456789]/g, '');

    // Limita a 8 caracteres
    valor = valor.substring(0, 8);

    input.value = valor;
}

// Estado dos campos
var estadoOriginal = {};
var previewTempUrl = null;
var removerFotoAtivado = false;

// Função para salvar estado original dos campos
function salvarEstadoOriginal() {
    estadoOriginal = {
        email: document.getElementById('email-input').value,
        ra: document.getElementById('ra-input') ? document.getElementById('ra-input').value : null,
        avatarSrc: (document.getElementById('avatar-visualizacao') || {}).src || null,
        tinhaFotoCustom: (((document.getElementById('avatar-visualizacao') || {}).dataset) || {}).temFoto === '1'
    };
}

// Função para restaurar estado original dos campos
function restaurarEstadoOriginal() {
    document.getElementById('email-input').value = estadoOriginal.email;

    const raInput = document.getElementById('ra-input');
    if (raInput && estadoOriginal.ra !== null) {
        raInput.value = estadoOriginal.ra;
    }

    const img = document.getElementById('avatar-visualizacao');
    if (estadoOriginal.avatarSrc && img) {
        img.src = estadoOriginal.avatarSrc;
    }

    // Limpa arquivo selecionado
    const fileInput = document.getElementById('foto-perfil-input');
    if (fileInput) fileInput.value = '';

    // Reset flags de remoção
    const flag = document.getElementById('remover-foto-flag');
    if (flag) flag.value = 'false';
    removerFotoAtivado = false;

    // Atualiza dataset tem-foto conforme estado original
    if (img) {
        img.dataset.temFoto = estadoOriginal.tinhaFotoCustom ? '1' : '0';
    }

    // Esconde botão remover fora do modo edição
    const btnRemover = document.getElementById('btn-remover-foto');
    if (btnRemover) btnRemover.classList.add('hidden');
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

    // Botão "Tornar-se organizador" (se existir)
    const btnTornarOrganizador = document.getElementById('btn-tornar-organizador');
    if (btnTornarOrganizador) {
        btnTornarOrganizador.classList.remove('hidden');
    }

    // Mostrar botão de alterar foto
    const btnAlterarFoto = document.getElementById('btn-alterar-foto');
    if (btnAlterarFoto) btnAlterarFoto.classList.remove('hidden');

    // Mostrar "Remover foto" apenas se houver foto customizada
    const img = document.getElementById('avatar-visualizacao');
    const btnRemover = document.getElementById('btn-remover-foto');
    if (btnRemover && img && img.dataset.temFoto === '1') {
        btnRemover.classList.remove('hidden');
    }

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

    // Esconder botão "Tornar-se organizador" (se existir)
    const btnTornarOrganizador = document.getElementById('btn-tornar-organizador');
    if (btnTornarOrganizador) {
        btnTornarOrganizador.classList.add('hidden');
    }

    // Esconde alterar foto
    const btnAlterarFoto = document.getElementById('btn-alterar-foto');
    if (btnAlterarFoto) btnAlterarFoto.classList.add('hidden');

    const btnRemover = document.getElementById('btn-remover-foto');
    if (btnRemover) btnRemover.classList.add('hidden');

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

    // Validar imagem (cliente) opcional
    const fileInput = document.getElementById('foto-perfil-input');
    if (fileInput && fileInput.files && fileInput.files[0]) {
        const f = fileInput.files[0];
        const okType = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'].includes(f.type);
        if (!okType) { mostrarAlerta('Imagem inválida. Use JPG, PNG, WEBP ou GIF.', 'danger'); return false; }
        if (f.size > 2 * 1024 * 1024) { mostrarAlerta('A imagem deve ter no máximo 2MB.', 'danger'); return false; }
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

// Pré-visualização da imagem
function configurarUploadFoto() {
    const btnAlterar = document.getElementById('btn-alterar-foto');
    const input = document.getElementById('foto-perfil-input');
    const img = document.getElementById('avatar-visualizacao');
    const btnRemover = document.getElementById('btn-remover-foto');
    const flag = document.getElementById('remover-foto-flag');
    if (!img) return;

    if (btnAlterar && input) {
        // Remove listeners antigos para evitar duplicação
        const novoBtn = btnAlterar.cloneNode(true);
        btnAlterar.parentNode.replaceChild(novoBtn, btnAlterar);

        const novoInput = input.cloneNode(true);
        input.parentNode.replaceChild(novoInput, input);

        // Anexa novos listeners
        novoBtn.addEventListener('click', () => novoInput.click());
        novoInput.addEventListener('change', () => {
            // Ao escolher nova imagem, cancelar remoção se estava ativa
            if (removerFotoAtivado && flag) {
                removerFotoAtivado = false;
                flag.value = 'false';
            }
            if (previewTempUrl) URL.revokeObjectURL(previewTempUrl);
            if (novoInput.files && novoInput.files[0]) {
                const f = novoInput.files[0];
                previewTempUrl = URL.createObjectURL(f);
                img.src = previewTempUrl;
                // Ao selecionar nova foto, mostra o botão remover para permitir cancelar
                const btnRemoverAtual = document.getElementById('btn-remover-foto');
                if (btnRemoverAtual && img.dataset.temFoto === '1') {
                    btnRemoverAtual.classList.remove('hidden');
                }
            }
        });
    }

    if (btnRemover && flag) {
        // Remove listeners antigos para evitar duplicação
        const novoBtnRemover = btnRemover.cloneNode(true);
        btnRemover.parentNode.replaceChild(novoBtnRemover, btnRemover);

        // Anexa novo listener
        novoBtnRemover.addEventListener('click', () => {
            const defaultSrc = img.getAttribute('data-default-src');
            // Toggle remoção
            removerFotoAtivado = !removerFotoAtivado;
            flag.value = removerFotoAtivado ? 'true' : 'false';
            if (removerFotoAtivado) {
                // Preview da imagem padrão e limpar seleção de arquivo
                const fileInput = document.getElementById('foto-perfil-input');
                if (fileInput) fileInput.value = '';
                if (previewTempUrl) { URL.revokeObjectURL(previewTempUrl); previewTempUrl = null; }
                img.src = defaultSrc;
            } else {
                // Restaurar imagem original
                if (estadoOriginal.avatarSrc) img.src = estadoOriginal.avatarSrc;
            }
        });
    }
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
                // Atualiza avatar/menu dependendo do que o backend retornou
                const img = document.getElementById('avatar-visualizacao');
                const defaultSrc = img ? img.getAttribute('data-default-src') : null;
                const siteRoot = img ? img.getAttribute('data-site-root') : '';
                if (data.dados) {
                    if (data.dados.fotoPerfil) {
                        const novo = (siteRoot ? siteRoot + '/' : '../') + data.dados.fotoPerfil + '?t=' + Date.now();
                        if (img) { img.src = novo; img.dataset.temFoto = '1'; }
                        const imgMenu = document.querySelector('.header-menu .perfil img');
                        if (imgMenu) imgMenu.src = novo;
                    } else if (data.dados.fotoRemovida && defaultSrc) {
                        const novoPadrao = defaultSrc + '?t=' + Date.now();
                        if (img) { img.src = novoPadrao; img.dataset.temFoto = '0'; }
                        const imgMenu = document.querySelector('.header-menu .perfil img');
                        if (imgMenu) imgMenu.src = novoPadrao;
                    }
                }
                // Salvar novo estado original
                salvarEstadoOriginal();
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
    const senhaInput = document.getElementById('senha-confirmar-exclusao');
    if (!senhaInput) {
        mostrarAlerta('Erro: campo de senha não encontrado', 'danger');
        return;
    }

    const senha = senhaInput.value.trim();
    if (!senha) {
        mostrarAlerta('Por favor, digite sua senha para confirmar', 'danger');
        return;
    }

    const btnConfirmar = document.querySelector('.modal-exclusao-conta .botao-confirmar-exclusao');
    if (btnConfirmar) {
        btnConfirmar.disabled = true;
        btnConfirmar.textContent = 'Processando...';
    }

    const formData = new FormData();
    formData.append('acao', 'excluir_conta');
    formData.append('senha', senha);

    fetch('PerfilParticipanteAcoes.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData,
        credentials: 'same-origin'
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na resposta do servidor: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            // Fecha o modal primeiro
            const modal = document.querySelector('.modal-exclusao-conta');
            if (modal) {
                modal.remove();
                document.body.style.overflow = '';
                window.modalExclusaoAberto = false;
            }

            if (data.sucesso) {
                mostrarMensagemExclusaoProgramada(data.data_exclusao, data.email);
            } else if (data.mensagem && data.mensagem.includes('já existe uma solicitação')) {
                // Se já existe solicitação pendente, mostra aviso específico
                mostrarAvisoSolicitacaoPendente(data.data_exclusao);
            } else {
                mostrarAlerta(data.mensagem || 'Erro ao solicitar exclusão de conta', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro ao excluir conta:', error);
            mostrarAlerta('Erro de conexão ao excluir conta: ' + error.message, 'danger');
            if (btnConfirmar) {
                btnConfirmar.disabled = false;
                btnConfirmar.textContent = 'Confirmar Exclusão';
            }
        });
}

// Função para mostrar aviso de solicitação pendente
function mostrarAvisoSolicitacaoPendente(dataExclusao) {
    var mainContent = document.getElementById('main-content');
    if (!mainContent) return;

    mainContent.innerHTML = '';

    var container = document.createElement('div');
    container.style.cssText = 'display:flex;flex-direction:column;align-items:center;justify-content:center;background:var(--caixas);border-radius:1em;padding:2.5rem;max-width:600px;margin:2rem auto;box-shadow:0 0.25rem 1rem rgba(0,0,0,0.25)';

    var dataFormatada = new Date(dataExclusao).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });

    container.innerHTML = '<div style="margin-bottom:1rem;display:flex;justify-content:center"><svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--amarelo)"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div><h2 style="color:var(--amarelo);font-size:2rem;margin-bottom:1.5rem;text-align:center">Exclusão Já Programada</h2><div style="color:#fff;font-size:1.1rem;line-height:1.6;text-align:center;margin-bottom:2rem"><p style="margin-bottom:1.5rem;font-size:1.2rem;font-weight:bold;color:var(--amarelo);">Você já possui uma solicitação de exclusão de conta em andamento.</p><p style="margin-bottom:1rem;"><strong>Data programada para exclusão:</strong><br><span style="font-size:1.3rem;color:var(--amarelo);">' + dataFormatada + '</span></p><p style="margin-bottom:1rem;color:var(--amarelo);">Durante este período:</p><ul style="text-align:left;display:inline-block;margin-bottom:1rem;"><li>Você pode cancelar a exclusão a qualquer momento</li><li>Seus dados permanecem intactos</li><li>Você pode continuar usando sua conta normalmente</li></ul><p style="margin-top:1.5rem;padding:1rem;background:rgba(255,193,7,0.1);border-radius:0.5rem;border-left:4px solid var(--amarelo);"><strong>Importante:</strong> Não é possível criar uma nova solicitação enquanto houver uma pendente.</p></div><button type="button" class="botao" onclick="window.location.href=\'ContainerParticipante.php?pagina=perfil\'" style="margin-top:1rem;">Entendi</button>';

    mainContent.appendChild(container);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function mostrarMensagemExclusaoProgramada(dataExclusao, email) {
    var mainContent = document.getElementById('main-content');
    if (!mainContent) return;
    mainContent.innerHTML = '';

    var container = document.createElement('div');
    container.style.cssText = 'display:flex;flex-direction:column;align-items:center;justify-content:center;background:var(--caixas);border-radius:1em;padding:2.5rem;max-width:600px;margin:2rem auto;box-shadow:0 0.25rem 1rem rgba(0,0,0,0.25)';

    var dataFormatada = new Date(dataExclusao).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });

    container.innerHTML = '<div style="margin-bottom:1rem;display:flex;justify-content:center"><svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--amarelo)"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2 2"/><path d="M5 3L2 6"/><path d="M22 6l-3-3"/><path d="M6 19l-2 2"/><path d="M18 19l2 2"/></svg></div><h2 style="color:var(--amarelo);font-size:2rem;margin-bottom:1.5rem;text-align:center">Exclusão Programada</h2><div style="color:#fff;font-size:1.1rem;line-height:1.6;text-align:center;margin-bottom:2rem"><p style="margin-bottom:1rem">Sua solicitação de exclusão de conta foi registrada com sucesso.</p><p style="margin-bottom:1rem"><strong>Data programada para exclusão:</strong><br>' + dataFormatada + '</p><p style="margin-bottom:1rem">Um email de confirmação foi enviado para: <strong>' + email + '</strong></p><p style="margin-bottom:1rem;color:var(--amarelo)">Durante este período de 30 dias:</p><ul style="text-align:left;display:inline-block;margin-bottom:1rem"><li>Você pode cancelar a exclusão acessando sua conta</li><li>Seus dados permanecerão intactos</li><li>Você pode continuar usando sua conta normalmente</li></ul><p style="margin-top:1.5rem;color:var(--vermelho);font-weight:bold;display:flex;align-items:center;gap:0.5rem;justify-content:center"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>Após 30 dias, sua conta e todos os dados associados serão permanentemente excluídos.</p></div>';

    var btnVoltar = document.createElement('button');
    btnVoltar.type = 'button';
    btnVoltar.className = 'botao';
    btnVoltar.textContent = 'Entendi';
    btnVoltar.style.marginTop = '1rem';
    btnVoltar.onclick = function () { window.location.href = '../PaginasPublicas/ContainerPublico.php?pagina=login'; };

    container.appendChild(btnVoltar);
    mainContent.appendChild(container);
}

// Função para mostrar o modal de confirmação de exclusão de conta
function mostrarModalExcluirConta() {
    // Previne múltiplas chamadas simultâneas
    if (window.modalExclusaoAberto) {
        return;
    }
    window.modalExclusaoAberto = true;

    const modalExistente = document.querySelector('.modal-exclusao-conta');
    if (modalExistente) modalExistente.remove();

    // Previne scroll ao abrir modal
    document.body.style.overflow = 'hidden';

    var modalOverlay = document.createElement('div');
    modalOverlay.className = 'modal-exclusao-conta';
    modalOverlay.style.cssText = `position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:center;z-index:99999;backdrop-filter:blur(5px)`

    var container = document.createElement('div');
    container.style.cssText = 'background:var(--caixas);border-radius:1rem;padding:2rem 2.5rem;max-width:550px;width:90%;box-shadow:0 0.5rem 2rem rgba(0,0,0,0.5);position:relative;max-height:90vh;overflow-y:auto';

    container.innerHTML = '<div style="text-align:center;margin-bottom:1rem;display:flex;justify-content:center"><svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--vermelho)"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div><h2 style="color:var(--vermelho);font-size:1.8rem;font-weight:700;text-align:center;margin-bottom:1.5rem">Confirmar Exclusão de Conta</h2><div style="color:#fff;font-size:1rem;line-height:1.6;margin-bottom:1.5rem"><p style="margin-bottom:1rem;font-weight:bold">Você está prestes a solicitar a exclusão permanente de sua conta.</p><p style="margin-bottom:1rem">Ao confirmar, os seguintes dados serão excluídos após <strong style="color:var(--amarelo)">30 dias</strong>:</p><ul style="margin-left:1.5rem;margin-bottom:1rem"><li>Todos os seus dados pessoais</li><li>Suas inscrições em eventos</li><li>Seus certificados</li><li>Seu histórico de presença</li><li>Todas as notificações</li></ul><p style="margin-bottom:1rem;color:var(--amarelo);font-weight:bold;display:flex;align-items:center;gap:0.5rem"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2 2"/></svg>Você terá 30 dias para cancelar esta solicitação caso mude de ideia.</p><p style="margin-bottom:1.5rem">Para confirmar, digite sua senha abaixo:</p></div><div style="margin-bottom:1.5rem"><label style="display:block;color:#fff;font-weight:600;margin-bottom:0.5rem">Senha:</label><input type="password" id="senha-confirmar-exclusao" placeholder="Digite sua senha" style="width:100%;padding:0.75rem;border:2px solid var(--cinza-medio);border-radius:0.5rem;font-size:1rem;background:var(--branco);color:var(--cinza-escuro);box-sizing:border-box"></div>';

    var botoesWrapper = document.createElement('div');
    botoesWrapper.style.cssText = 'display:flex;gap:1rem;justify-content:center;margin-top:2rem';

    var btnCancelar = document.createElement('button');
    btnCancelar.type = 'button';
    btnCancelar.textContent = 'Cancelar';
    btnCancelar.className = 'botao';
    btnCancelar.style.cssText = 'background-color:var(--cinza-medio);color:#fff;border:none;border-radius:0.5rem;padding:0.75rem 2rem;font-weight:700;font-size:1rem;cursor:pointer';
    btnCancelar.onclick = function () {
        modalOverlay.remove();
        document.body.style.overflow = '';
        window.modalExclusaoAberto = false;
    };

    var btnConfirmar = document.createElement('button');
    btnConfirmar.type = 'button';
    btnConfirmar.textContent = 'Confirmar Exclusão';
    btnConfirmar.className = 'botao botao-confirmar-exclusao';
    btnConfirmar.style.cssText = 'background-color:var(--vermelho);color:#fff;border:none;border-radius:0.5rem;padding:0.75rem 2rem;font-weight:700;font-size:1rem;cursor:pointer';
    btnConfirmar.onclick = excluirConta;

    botoesWrapper.appendChild(btnCancelar);
    botoesWrapper.appendChild(btnConfirmar);
    container.appendChild(botoesWrapper);
    modalOverlay.appendChild(container);
    document.body.appendChild(modalOverlay);

    setTimeout(() => document.getElementById('senha-confirmar-exclusao').focus(), 100);

    document.getElementById('senha-confirmar-exclusao').addEventListener('keypress', function (e) {
        if (e.key === 'Enter') excluirConta();
    });

    modalOverlay.addEventListener('click', function (e) {
        if (e.target === modalOverlay) modalOverlay.remove();
    });
}

// Event listeners quando a página carrega
function inicializarEventosPerfilParticipante() {
    // Máscara para RA
    const raInput = document.getElementById('ra-input');
    if (raInput) {
        raInput.addEventListener('input', function () {
            aplicarMascaraRA(this);
        });
    }

    // Botão Editar
    const btnEditar = document.getElementById('btn-editar');
    if (btnEditar) {
        btnEditar.addEventListener('click', function () {
            salvarEstadoOriginal();
            mostrarCamposEditaveis();
        });
    }

    // Botão Cancelar
    const btnCancelar = document.getElementById('btn-cancelar');
    if (btnCancelar) {
        btnCancelar.addEventListener('click', function () {
            restaurarEstadoOriginal();
            esconderCamposEditaveis();
        });
    }

    // Formulário
    const formPerfil = document.getElementById('form-perfil-participante');
    if (formPerfil) {
        formPerfil.addEventListener('submit', salvarPerfil);
    }

    // Upload foto + remover foto
    configurarUploadFoto();

    // Botão de excluir conta
    const btnExcluir = document.getElementById('btn-excluir-conta');
    if (btnExcluir) {
        btnExcluir.addEventListener('click', function () {
            // Primeiro verifica se já existe solicitação pendente
            const formData = new FormData();
            formData.append('acao', 'excluir_conta');
            formData.append('verificar_pendente', 'true');

            fetch('PerfilParticipanteAcoes.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.pendente) {
                        // Já existe solicitação pendente - mostra aviso
                        mostrarAvisoSolicitacaoPendente(data.data_exclusao);
                    } else {
                        // Não existe - mostra modal de confirmação
                        mostrarModalExcluirConta();
                    }
                })
                .catch(error => {
                    console.error('Erro ao verificar solicitação:', error);
                    mostrarAlerta('Erro ao verificar solicitação de exclusão', 'danger');
                });
        });
    }

    //  EVENTOS DO MODAL DE CÓDIGO ORGANIZADOR 
    // Botão para abrir modal
    const btnTornarOrganizador = document.getElementById('btn-tornar-organizador');
    if (btnTornarOrganizador) {
        btnTornarOrganizador.addEventListener('click', mostrarModalCodigo);
    }

    // Botão fechar modal (X)
    const btnFecharModal = document.getElementById('btn-fechar-modal-codigo');
    if (btnFecharModal) {
        btnFecharModal.addEventListener('click', esconderModalCodigo);
    }

    // Botão cancelar modal
    const btnCancelarModal = document.getElementById('btn-cancelar-modal');
    if (btnCancelarModal) {
        btnCancelarModal.addEventListener('click', esconderModalCodigo);
    }

    // Botão confirmar código
    const btnConfirmarCodigo = document.getElementById('btn-confirmar-codigo');
    if (btnConfirmarCodigo) {
        btnConfirmarCodigo.addEventListener('click', processarCodigoOrganizador);
    }

    // Botão solicitar código
    const btnSolicitarCodigo = document.getElementById('btn-solicitar-codigo');
    if (btnSolicitarCodigo) {
        btnSolicitarCodigo.addEventListener('click', solicitarCodigo);
    }

    // Input código (Enter para confirmar e máscara)
    const inputCodigo = document.getElementById('input-codigo');
    if (inputCodigo) {
        // Aplicar máscara durante digitação
        inputCodigo.addEventListener('input', function () {
            aplicarMascaraCodigo(this);
        });

        inputCodigo.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                processarCodigoOrganizador();
            }
        });
    }

    // Fechar modal ao clicar fora
    const modalCodigo = document.getElementById('modal-codigo');
    if (modalCodigo) {
        modalCodigo.addEventListener('click', function (e) {
            if (e.target === modalCodigo) {
                esconderModalCodigo();
            }
        });
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

    // Remover tooltip existente
    function removerTooltip() {
        if (tooltip) {
            tooltip.classList.remove('show');
            tooltip.textContent = '';
        }
    }

    // Encontrar todos os elementos com data-tooltip
    const elementosComTooltip = document.querySelectorAll('[data-tooltip]');

    elementosComTooltip.forEach(elemento => {
        elemento.addEventListener('mouseenter', function (e) {
            const tooltipTexto = this.getAttribute('data-tooltip');
            if (tooltipTexto) {
                removerTooltip(); // Remove qualquer tooltip anterior
                const tooltipEl = criarTooltip();
                tooltipEl.textContent = tooltipTexto;

                // Posicionar inicialmente
                const rect = this.getBoundingClientRect();
                const x = e.clientX + 15;
                const y = e.clientY + 15;

                tooltipEl.style.left = x + 'px';
                tooltipEl.style.top = y + 'px';
                tooltipEl.classList.add('show');
            }
        });

        elemento.addEventListener('mousemove', function (e) {
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

        elemento.addEventListener('mouseleave', function () {
            removerTooltip();
        });
    });

    // Remover tooltip se o mouse sair da janela
    document.addEventListener('mouseleave', function () {
        removerTooltip();
    });
}

// Inicializa ao carregar normalmente
window.addEventListener('DOMContentLoaded', inicializarEventosPerfilParticipante);
// Permite inicialização após AJAX
window.inicializarEventosPerfilParticipante = inicializarEventosPerfilParticipante;

//  FUNCIONALIDADES PARA TORNAR-SE ORGANIZADOR 

// Função para mostrar modal de código
function mostrarModalCodigo() {
    document.getElementById('modal-codigo').classList.add('ativo');
    document.getElementById('input-codigo').focus();
}

// Função para esconder modal de código
function esconderModalCodigo() {
    document.getElementById('modal-codigo').classList.remove('ativo');
    document.getElementById('input-codigo').value = '';
    document.getElementById('alert-modal').innerHTML = '';
}

// Função para mostrar alerta no modal
function mostrarAlertaModal(mensagem, tipo) {
    const alertContainer = document.getElementById('alert-modal');
    alertContainer.innerHTML = `<div class="alert alert-${tipo}">${mensagem}</div>`;
}

// Função para processar código de organizador
function processarCodigoOrganizador() {
    const codigo = document.getElementById('input-codigo').value.trim().toUpperCase();

    if (!codigo) {
        mostrarAlertaModal('Por favor, digite um código.', 'danger');
        return;
    }

    // Validação do novo formato (8 caracteres seguros)
    if (codigo.length !== 8) {
        mostrarAlertaModal('O código deve ter exatamente 8 caracteres.', 'danger');
        return;
    }

    // Verifica se contém apenas caracteres permitidos
    if (!/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{8}$/.test(codigo)) {
        mostrarAlertaModal('Código inválido. Use apenas: A-Z (exceto I, O) e números 2-9 (exceto 0, 1).', 'danger');
        return;
    }

    // Enviar requisição AJAX para verificar código
    fetch('PerfilParticipante.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'codigo=' + encodeURIComponent(codigo)
    })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                // Mostra o alerta no modal primeiro
                mostrarAlertaModal('Parabéns! Você agora é um organizador. A página será recarregada.', 'success');
                setTimeout(() => {
                    // Depois esconde o modal e mostra o alerta principal
                    esconderModalCodigo();
                    mostrarAlerta('Parabéns! Você agora é um organizador. A página será recarregada.', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                }, 1500);
            } else {
                mostrarAlertaModal(data.mensagem || 'Código inválido.', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarAlertaModal('Erro ao processar solicitação.', 'danger');
        });
}

// Função para solicitar código (redireciona para Fale Conosco)
function solicitarCodigo() {
    // Mostra alerta no modal primeiro
    mostrarAlertaModal('Redirecionando para o Fale Conosco para solicitar código...', 'success');
    setTimeout(() => {
        esconderModalCodigo();
        // Mostra alerta na página principal também
        mostrarAlerta('Redirecionando para o Fale Conosco para solicitar código...', 'success');
        setTimeout(() => {
            carregarPagina('faleconosco');
        }, 1000);
    }, 1500);
}