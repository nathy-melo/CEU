// Validações específicas da página de login

function validarLogin() {
    var emailInput = document.getElementById('email');
    var senhaInput = document.getElementById('password');

    if (!emailInput || !senhaInput) {
        return true;
    }

    var email = emailInput.value.trim();
    var senha = senhaInput.value.trim();

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

    var botao = document.querySelector('.botao-login');
    if (botao) {
        botao.disabled = true;
        botao.textContent = 'Entrando...';

        setTimeout(function reativarBotaoLoginDepoisDoAtraso() {
            botao.disabled = false;
            botao.textContent = 'Entrar';
        }, 5000);
    }

    return true;
}

function inicializarValidacoesLogin() {
    exibirErroURLPadrao();

    var formLogin = document.getElementById('form-login');
    if (formLogin && !formLogin.dataset.validacaoLoginAtiva) {
        formLogin.addEventListener('submit', function validarEnvioDoFormularioLogin(event) {
            if (!validarLogin()) {
                event.preventDefault();
            }
        });
        formLogin.dataset.validacaoLoginAtiva = '1';
    }

    var emailLogin = document.getElementById('email');
    if (emailLogin && !emailLogin.dataset.validacaoLoginAtiva) {
        emailLogin.addEventListener('focus', function limparErrosAoFocarEmailLogin() {
            limparMensagens('erro-login');
        });
        emailLogin.addEventListener('blur', function validarEmailLoginAoPerderFoco() {
            var valor = emailLogin.value.trim();
            if (valor && !validarEmail(valor)) {
                mostrarMensagem('⚠️ Formato de e-mail inválido!', 'erro', 'erro-login');
            }
        });
        emailLogin.dataset.validacaoLoginAtiva = '1';
    }

    var senhaLogin = document.getElementById('password');
    if (senhaLogin && !senhaLogin.dataset.validacaoLoginAtiva) {
        senhaLogin.addEventListener('focus', function limparErrosAoFocarSenhaLogin() {
            limparMensagens('erro-login');
        });
        senhaLogin.addEventListener('blur', function validarSenhaLoginAoPerderFoco() {
            var valor = senhaLogin.value.trim();
            if (valor && valor.length < 8) {
                mostrarMensagem('⚠️ A senha deve ter pelo menos 8 caracteres!', 'erro', 'erro-login');
            }
        });
        senhaLogin.dataset.validacaoLoginAtiva = '1';
    }
}

if (typeof window !== 'undefined') {
    window.inicializarValidacoesLogin = inicializarValidacoesLogin;
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarValidacoesLogin);
} else {
    inicializarValidacoesLogin();
}
