// Salva o conteúdo original para restaurar ao clicar em Voltar
let conteudoOriginalRedefinirSenha = null;

// Função para mostrar mensagens de erro
function mostrarErro(mensagem) {
    var form = document.querySelector('.redefinir-senha-formulario');
    if (!form) return;

    // Remove mensagem de erro anterior
    var erroAnterior = form.querySelector('.mensagem-erro-redefinir-senha');
    if (erroAnterior) {
        erroAnterior.remove();
    }

    // Cria nova mensagem de erro
    var divErro = document.createElement('div');
    divErro.className = 'mensagem-erro-redefinir-senha';
    divErro.style.cssText = 'display: block; color: var(--vermelho); background-color: rgba(255, 0, 0, 0.1); border: 1px solid var(--vermelho); border-radius: 0.5em; padding: 0.75em; margin-bottom: 1em; text-align: center; font-family: Inter, sans-serif; font-size: 0.9em; font-weight: 500;';
    divErro.textContent = mensagem;

    // Insere antes do primeiro campo
    var primeiroCampo = form.querySelector('.grupo-formulario');
    if (primeiroCampo) {
        form.insertBefore(divErro, primeiroCampo);
    } else {
        form.insertBefore(divErro, form.firstChild);
    }

    // Scroll para o erro
    divErro.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Função para limpar mensagens de erro
function limparErro() {
    var form = document.querySelector('.redefinir-senha-formulario');
    if (!form) return;
    var erro = form.querySelector('.mensagem-erro-redefinir-senha');
    if (erro) {
        erro.remove();
    }
}

// Função para validar o formulário
function validarFormularioRedefinirSenha() {
    var senhaAtual = document.getElementById('current-password');
    var novaSenha = document.getElementById('new-password');
    var confirmarSenha = document.getElementById('confirm-password');

    if (!senhaAtual || !novaSenha || !confirmarSenha) {
        return false;
    }

    var valorSenhaAtual = senhaAtual.value.trim();
    var valorNovaSenha = novaSenha.value.trim();
    var valorConfirmarSenha = confirmarSenha.value.trim();

    // Valida campos obrigatórios
    if (!valorSenhaAtual || !valorNovaSenha || !valorConfirmarSenha) {
        mostrarErro('⚠️ Todos os campos são obrigatórios!');
        return false;
    }

    // Valida tamanho mínimo da nova senha
    if (valorNovaSenha.length < 8) {
        mostrarErro('⚠️ A nova senha deve ter pelo menos 8 caracteres!');
        return false;
    }

    // Valida se a nova senha e confirmação são iguais
    if (valorNovaSenha !== valorConfirmarSenha) {
        mostrarErro('⚠️ As senhas não coincidem!');
        return false;
    }

    // Valida se a nova senha é diferente da senha atual
    if (valorSenhaAtual === valorNovaSenha) {
        mostrarErro('⚠️ A nova senha deve ser diferente da senha atual!');
        return false;
    }

    return true;
}

function mostrarMensagemSenhaRedefinida() {
    var mainContent = document.getElementById('main-content');
    if (!mainContent) return;

    if (conteudoOriginalRedefinirSenha === null) {
        conteudoOriginalRedefinirSenha = mainContent.innerHTML;
    }

    // Limpa e monta a mensagem com os mesmos estilos do modelo fornecido
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
    titulo.textContent = 'A sua senha foi redefinida!';
    titulo.style.color = '#fff';
    titulo.style.fontSize = '2em';
    titulo.style.marginBottom = '0.5em';
    titulo.style.textAlign = 'center';

    var btnVoltar = document.createElement('button');
    btnVoltar.type = 'button';
    btnVoltar.className = 'botao';
    btnVoltar.textContent = 'Voltar';
    btnVoltar.onclick = function() {
        // Restaura o conteúdo original da página
        if (conteudoOriginalRedefinirSenha !== null) {
            mainContent.innerHTML = conteudoOriginalRedefinirSenha;
            conteudoOriginalRedefinirSenha = null;
            // Reatribui os eventos após restaurar o conteúdo
            if (typeof window.atribuirEventoRedefinirSenha === 'function') {
                setTimeout(window.atribuirEventoRedefinirSenha, 0);
            }
        } else if (typeof history !== 'undefined' && history.back) {
            history.back();
        }
    };

    container.appendChild(titulo);
    container.appendChild(btnVoltar);
    mainContent.appendChild(container);
}

function atribuirEventoRedefinirSenha() {
    var form = document.querySelector('.redefinir-senha-formulario');
    if (!form) return;

    // Verifica se já tem listeners (evita duplicação)
    if (form.dataset.eventosAtribuidos === '1') {
        // Apenas garante que o toggle está aplicado
        if (typeof window.aplicarToggleSenhas === 'function') {
            window.aplicarToggleSenhas();
        }
        return;
    }

    // Marca que os eventos foram atribuídos
    form.dataset.eventosAtribuidos = '1';

    // Aplica toggle de senha nos campos de senha
    // Usa múltiplas tentativas para garantir que funcione
    function aplicarToggle() {
        if (typeof window.aplicarToggleSenhas === 'function') {
            window.aplicarToggleSenhas();
        }
    }
    
    // Aplica imediatamente
    aplicarToggle();
    
    // Aplica novamente após um pequeno delay para garantir
    setTimeout(aplicarToggle, 50);
    setTimeout(aplicarToggle, 150);

    // Adiciona listeners para limpar erros ao digitar
    var campos = form.querySelectorAll('input[type="password"]');
    campos.forEach(function(campo) {
        campo.addEventListener('input', limparErro);
        campo.addEventListener('focus', limparErro);
    });

    // Listener de submit
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        limparErro();

        // Validação HTML5 básica
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Validações customizadas
        if (!validarFormularioRedefinirSenha()) {
            return;
        }

        // Desabilita o botão de submit
        var btnSubmit = form.querySelector('button[type="submit"]');
        var textoOriginal = btnSubmit ? btnSubmit.textContent : 'Confirmar';
        if (btnSubmit) {
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Processando...';
        }

        // Prepara os dados do formulário
        var formData = new FormData(form);

        try {
            // Envia requisição para o servidor
            const response = await fetch('../PaginasGlobais/ProcessarRedefinirSenhaConta.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.sucesso) {
                // Sucesso - mostra mensagem de confirmação
                mostrarMensagemSenhaRedefinida();
            } else {
                // Erro - mostra mensagem de erro
                mostrarErro('⚠️ ' + (data.mensagem || 'Erro ao redefinir senha. Tente novamente.'));
                
                // Reabilita o botão
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.textContent = textoOriginal;
                }
            }
        } catch (error) {
            console.error('Erro ao processar redefinição de senha:', error);
            mostrarErro('⚠️ Erro ao conectar com o servidor. Verifique sua conexão e tente novamente.');
            
            // Reabilita o botão
            if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.textContent = textoOriginal;
            }
        }
    });

    // Botão Voltar do formulário
    var btnVoltarForm = form.querySelector('.acoes-formulario .botao[type="button"], .acoes-formulario button[type="button"]');
    if (btnVoltarForm) {
        btnVoltarForm.onclick = function() {
            if (typeof carregarPagina === 'function') {
                carregarPagina('configuracoes');
            } else if (typeof history !== 'undefined' && history.back) {
                history.back();
            }
        };
    }
}

// Inicializações para funcionar com carregamento via AJAX
window.addEventListener('DOMContentLoaded', atribuirEventoRedefinirSenha);
window.atribuirEventoRedefinirSenha = atribuirEventoRedefinirSenha;
setTimeout(atribuirEventoRedefinirSenha, 0);
