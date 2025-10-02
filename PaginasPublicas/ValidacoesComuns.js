// Funções compartilhadas entre as páginas de login e cadastro

// MENSAGENS NA TELA
function mostrarMensagem(mensagem, tipo, containerId) {
    if (!tipo) {
        tipo = 'erro';
    }

    var caixinha = null;
    if (containerId) {
        caixinha = document.getElementById(containerId);
    }
    if (!caixinha) {
        caixinha = document.getElementById('erro-login');
    }
    if (!caixinha) {
        caixinha = document.getElementById('erro-cadastro');
    }
    if (!caixinha) {
        return;
    }

    caixinha.classList.remove('sucesso');
    caixinha.classList.remove('info');

    if (tipo === 'sucesso') {
        caixinha.classList.add('sucesso');
    } else if (tipo === 'info') {
        caixinha.classList.add('info');
    }

    // Detecta se é multi-linha (permitiremos <br> depois que o chamador ajustar innerHTML)
    var multiline = /<br\s*\/?>/i.test(mensagem) || mensagem.length > 90; // heurística simples
    // Mantém somente texto inicialmente para evitar injection
    caixinha.innerHTML = '';
    caixinha.textContent = mensagem.replace(/<br\s*\/?>/ig, ' ');
    caixinha.style.display = 'block';
    caixinha.classList.toggle('mensagem-duas-linhas', multiline);

    var TERMOS_BOTTOM_BASE = 4.5;       // distância padrão em 'em'
    var TERMOS_BOTTOM_MULTILINE = 3;  // distância quando mensagem tem 2+ linhas


    // Aplicação simples: escolhe um bottom fixo dependendo se é multi-linha
    var termosEls = document.querySelectorAll('.cartao-cadastro-termos');
    termosEls.forEach(function (el) {
        if (!el.dataset.bottomOriginal) {
            el.dataset.bottomOriginal = (window.getComputedStyle(el).bottom || TERMOS_BOTTOM_BASE + 'em');
        }
        el.style.bottom = (multiline ? TERMOS_BOTTOM_MULTILINE : TERMOS_BOTTOM_BASE) + 'em';
    });

    var cartaoCadastro = caixinha.closest('.cartao-cadastro');
    if (cartaoCadastro) {
        var mainContent = document.getElementById('main-content');
        if (mainContent) {
            mainContent.classList.add('main-content--com-aviso');
        }
    }

    // Mensagens de erro e sucesso permanecem visíveis até o usuário tentar novamente
    // ou algum outro fluxo chamar limparMensagens.
}

function limparMensagens(containerId) {
    var caixinha = null;
    if (containerId) {
        caixinha = document.getElementById(containerId);
    }
    if (!caixinha) {
        caixinha = document.getElementById('erro-login');
    }
    if (!caixinha) {
        caixinha = document.getElementById('erro-cadastro');
    }
    if (caixinha) {
        caixinha.style.display = 'none';
        caixinha.textContent = '';
        caixinha.classList.remove('mensagem-duas-linhas');
        var cartao = caixinha.closest('.cartao-cadastro-formulario');
        var spacer = cartao ? cartao.querySelector('[data-spacer="cadastro"]') : null;
        if (spacer) {
            spacer.style.height = '1.65em';
        }
        caixinha.classList.remove('sucesso');
        caixinha.classList.remove('info');

        var mainContent = document.getElementById('main-content');
        if (mainContent) {
            var erroCadastroAtivo = document.getElementById('erro-cadastro');
            var erroLoginAtivo = document.getElementById('erro-login');
            var mensagemVisivel = false;

            if (erroCadastroAtivo && erroCadastroAtivo.style.display === 'block' && erroCadastroAtivo.textContent.trim()) {
                mensagemVisivel = true;
            }

            if (!mensagemVisivel && erroLoginAtivo && erroLoginAtivo.style.display === 'block' && erroLoginAtivo.textContent.trim()) {
                mensagemVisivel = true;
            }

            if (!mensagemVisivel) {
                mainContent.classList.remove('main-content--com-aviso');
            }
        }
    }
    // Restaura posição fixada para estado base
    var termosEls = document.querySelectorAll('.cartao-cadastro-termos');
    termosEls.forEach(function (el) {
        el.style.bottom = TERMOS_BOTTOM_BASE + 'em';
    });
}

// VALIDAÇÕES BÁSICAS
function validarEmail(email) {
    return email.includes('@') && email.includes('.') && email.indexOf('@') < email.lastIndexOf('.');
}


// Logica desativada por facilidade de testes
function validarCPF(cpf) {

    if (!cpf) {
        return false;
    }

    cpf = cpf.replace(/[^\d]+/g, '');
    if (cpf.length !== 11) {
        return false;
    }

    var todosIguais = true;
    for (var i = 1; i < 11; i++) {
        if (cpf.charAt(i) !== cpf.charAt(0)) {
            todosIguais = false;
            break;
        }
    }
    if (todosIguais) {
        return false;
    }

    var soma = 0;
    for (var j = 0; j < 9; j++) {
        soma += parseInt(cpf.charAt(j)) * (10 - j);
    }
    var resto = 11 - (soma % 11);
    if (resto === 10 || resto === 11) {
        resto = 0;
    }
    if (resto !== parseInt(cpf.charAt(9))) {
        return false;
    }

    soma = 0;
    for (var k = 0; k < 10; k++) {
        soma += parseInt(cpf.charAt(k)) * (11 - k);
    }
    resto = 11 - (soma % 11);
    if (resto === 10 || resto === 11) {
        resto = 0;
    }

    return resto === parseInt(cpf.charAt(10));

}

function adicionarMascara(input, mascara) {
    if (!input) {
        return;
    }

    input.addEventListener('input', function aplicarMascaraEnquantoDigita() {
        var apenasNumeros = '';
        for (var i = 0; i < this.value.length; i++) {
            var caractere = this.value.charAt(i);
            if (caractere >= '0' && caractere <= '9') {
                apenasNumeros += caractere;
            }
        }

        var formatado = '';
        var indice = 0;
        for (var j = 0; j < mascara.length && indice < apenasNumeros.length; j++) {
            if (mascara.charAt(j) === '#') {
                formatado += apenasNumeros.charAt(indice);
                indice++;
            } else {
                formatado += mascara.charAt(j);
            }
        }

        this.value = formatado;
    });
}

// ERROS DA URL
function exibirErroURLPadrao() {
    var busca = window.location.search;
    if (!busca || busca.indexOf('erro=') === -1) {
        return;
    }

    var params = new URLSearchParams(busca);
    var tipoErro = params.get('erro');
    if (!tipoErro) { return; }

    var mensagem = '❌ Erro desconhecido. Tente novamente.';

    switch (tipoErro) {
        case 'campos_obrigatorios':
            mensagem = '⚠️ Preencha todos os campos obrigatórios!';
            break;
        case 'credenciais_invalidas':
            mensagem = '❌ E-mail ou senha incorretos! Verifique suas credenciais.';
            break;
        case 'email_invalido':
            mensagem = '⚠️ Formato de e-mail inválido!';
            break;
        case 'senha_invalida':
            mensagem = '⚠️ A senha deve ter pelo menos 8 caracteres!';
            break;
        case 'acesso_negado':
            mensagem = '🔒 Acesso negado! Faça login para continuar.';
            break;
        case 'sessao_expirada':
            mensagem = '⏰ Sua sessão expirou. Faça login novamente.';
            break;
        case 'dados_incompletos':
            mensagem = '⚠️ Dados incompletos. Verifique o formulário.';
            break;
        case 'erro_servidor':
            mensagem = '🔧 Erro interno do servidor. Tente novamente em alguns minutos.';
            break;
        case 'cpf_invalido':
            mensagem = '⚠️ CPF inválido!';
            break;
        case 'senhas_diferentes':
            mensagem = '⚠️ As senhas não coincidem!';
            break;
        case 'termos_nao_aceitos':
            mensagem = '⚠️ É necessário aceitar os Termos de Condições.';
            break;
        case 'codigo_invalido':
            mensagem = '⚠️ Código de acesso inválido!';
            break;
        default:
            break;
    }

    mostrarMensagem(mensagem);

    // Remove somente o parâmetro 'erro' mantendo outros (ex: pagina=login)
    params.delete('erro');
    var novaQuery = params.toString();
    var base = window.location.href.split('?')[0];
    var novaURL = novaQuery ? (base + '?' + novaQuery) : base;
    if (window.history && window.history.replaceState) {
        window.history.replaceState({}, document.title, novaURL);
    }
}
