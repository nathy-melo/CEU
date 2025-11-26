// Validações específicas da página de login

// ============= MODO TESTE =============
// Defina como true para desativar algumas validações durante testes
const MODO_TESTE_LOGIN = false; // Mude para false para ativar validações

function validarLogin() {
    var campoEmail = document.getElementById('email');
    var campoSenha = document.getElementById('password');

    if (!campoEmail || !campoSenha) {
        return true;
    }

    var email = campoEmail.value.trim();
    var senha = campoSenha.value.trim();

    // Validação campo por campo para mensagens específicas
    if (!email) {
        mostrarMensagem('⚠️ O e-mail é obrigatório!', 'erro', 'erro-login');
        campoEmail.focus();
        return false;
    }

    if (!validarEmail(email)) {
        mostrarMensagem('⚠️ Formato de e-mail inválido!', 'erro', 'erro-login');
        campoEmail.focus();
        return false;
    }

    if (!senha) {
        mostrarMensagem('⚠️ A senha é obrigatória!', 'erro', 'erro-login');
        campoSenha.focus();
        return false;
    }

    if (senha.length < 8) {
        mostrarMensagem('⚠️ A senha deve ter pelo menos 8 caracteres!', 'erro', 'erro-login');
        campoSenha.focus();
        return false;
    }

    mostrarMensagem('🔄 Verificando suas credenciais...', 'info', 'erro-login');

    var botaoEntrar = document.querySelector('.botao-login');
    if (botaoEntrar) {
        botaoEntrar.disabled = true;
        botaoEntrar.textContent = 'Entrando...';

        // Limpar timeout anterior se existir
        if (window.timeoutBotaoLogin) {
            clearTimeout(window.timeoutBotaoLogin);
        }

        window.timeoutBotaoLogin = setTimeout(function reativarBotaoLoginDepoisDoAtraso() {
            botaoEntrar.disabled = false;
            botaoEntrar.textContent = 'Entrar';
            window.timeoutBotaoLogin = null;
        }, 5000);
    }

    return true;
}

function inicializarValidacoesLogin() {
    exibirErroURLPadrao();

    // Aplica toggle global (script ToggleSenha.js)
    if (typeof window.aplicarToggleSenhas === 'function') { window.aplicarToggleSenhas(); }

    var formularioLogin = document.getElementById('form-login');
    if (formularioLogin && !formularioLogin.dataset.validacaoLoginAtiva) {
        formularioLogin.addEventListener('submit', function validarEnvioDoFormularioLogin(event) {
            if (!validarLogin()) {
                event.preventDefault();
            }
        });
        formularioLogin.dataset.validacaoLoginAtiva = '1';
    }
    var campoEmailLogin = document.getElementById('email');
    if (campoEmailLogin && !campoEmailLogin.dataset.validacaoLoginAtiva) {
        campoEmailLogin.addEventListener('focus', function limparErrosAoFocarEmailLogin() {
            limparMensagens('erro-login');
        });
        campoEmailLogin.addEventListener('blur', function validarEmailLoginAoPerderFoco() {
            var valor = campoEmailLogin.value.trim();
            if (valor && !validarEmail(valor)) {
                mostrarMensagem('⚠️ Formato de e-mail inválido!', 'erro', 'erro-login');
            }
        });
        campoEmailLogin.dataset.validacaoLoginAtiva = '1';
    }
    var campoSenhaLogin = document.getElementById('password');
    if (campoSenhaLogin && !campoSenhaLogin.dataset.validacaoLoginAtiva) {
        campoSenhaLogin.addEventListener('focus', function limparErrosAoFocarSenhaLogin() {
            limparMensagens('erro-login');
        });
        campoSenhaLogin.addEventListener('blur', function validarSenhaLoginAoPerderFoco() {
            var valor = campoSenhaLogin.value.trim();
            if (valor && valor.length < 8) {
                mostrarMensagem('⚠️ A senha deve ter pelo menos 8 caracteres!', 'erro', 'erro-login');
            }
        });
        campoSenhaLogin.dataset.validacaoLoginAtiva = '1';
    }
}

if (typeof window !== 'undefined') {
    window.inicializarValidacoesLogin = inicializarValidacoesLogin;
}

if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', inicializarValidacoesLogin); }
else { inicializarValidacoesLogin(); }
