// Validações específicas da página de login

function validarLogin() {
    var campoEmail = document.getElementById('email');
    var campoSenha = document.getElementById('password');

    if (!campoEmail || !campoSenha) {
        return true;
    }

    var email = campoEmail.value.trim();
    var senha = campoSenha.value.trim();

    if (!email || !senha) {
        mostrarMensagem('⚠️ Todos os campos são obrigatórios!', 'erro', 'erro-login');
        return false;
    }

    if (!validarEmail(email)) {
        mostrarMensagem('⚠️ Por favor, insira um e-mail válido!', 'erro', 'erro-login');
        return false;
    }

    if (senha.length < 8) {
        mostrarMensagem('⚠️ A senha deve ter pelo menos 8 caracteres!', 'erro', 'erro-login');
        return false;
    }

    mostrarMensagem('🔄 Verificando suas credenciais...', 'info', 'erro-login');

    var botaoEntrar = document.querySelector('.botao-login');
    if (botaoEntrar) {
        botaoEntrar.disabled = true;
        botaoEntrar.textContent = 'Entrando...';

        setTimeout(function reativarBotaoLoginDepoisDoAtraso() {
            botaoEntrar.disabled = false;
            botaoEntrar.textContent = 'Entrar';
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
