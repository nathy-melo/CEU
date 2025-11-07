// Validações específicas das páginas de cadastro de participante e organizador

// ========== CONFIGURAÇÕES PARA TESTES ==========
// Mude estas variáveis para true/false para ativar/desativar validações
var VALIDAR_CPF = false;           // true = valida CPF, false = não valida
var VALIDAR_EMAIL = false;         // true = valida email, false = não valida  
var VALIDAR_SENHA = false;         // true = valida senha, false = não valida
var SENHA_MINIMA = 0;             // mínimo de caracteres (0 = desativar)
// ================================================

function validarCadastroParticipante() {
    var campoNome = document.getElementById('nome-completo');
    var campoCPF = document.getElementById('cpf');
    var campoEmail = document.getElementById('email');
    var campoSenha = document.getElementById('senha');
    var campoConfirmar = document.getElementById('confirmar-senha');
    var campoTermos = document.getElementById('aceite-termos');

    var nome = campoNome ? campoNome.value.trim() : '';
    var cpf = campoCPF ? campoCPF.value.trim() : '';
    var email = campoEmail ? campoEmail.value.trim() : '';
    var senha = campoSenha ? campoSenha.value.trim() : '';
    var confirmar = campoConfirmar ? campoConfirmar.value.trim() : '';
    var termos = campoTermos ? campoTermos.checked : false;

    if (!nome || !cpf || !email || !senha || !confirmar) {
        mostrarMensagem('⚠️ Todos os campos são obrigatórios!', 'erro', 'erro-cadastro');
        return false;
    }

    if (VALIDAR_CPF) {
        if (!validarCPF(cpf)) {
            mostrarMensagem('⚠️ CPF inválido!', 'erro', 'erro-cadastro');
            return false;
        }
    }

    if (VALIDAR_EMAIL) {
        if (!validarEmail(email)) {
            mostrarMensagem('⚠️ Formato de e-mail inválido!', 'erro', 'erro-cadastro');
            return false;
        }
    }

    if (VALIDAR_SENHA) {
        if (SENHA_MINIMA > 0 && senha.length < SENHA_MINIMA) {
            mostrarMensagem('⚠️ A senha deve ter pelo menos ' + SENHA_MINIMA + ' caracteres!', 'erro', 'erro-cadastro');
            return false;
        }

        if (senha !== confirmar) {
            mostrarMensagem('⚠️ As senhas não coincidem!', 'erro', 'erro-cadastro');
            return false;
        }
    }

    if (!termos) {
        mostrarMensagem('⚠️ É necessário aceitar os Termos de Condições.', 'erro', 'erro-cadastro');
        return false;
    }

    // Envio via AJAX
    enviarCadastroAjax('form-cadastro-participante', 'CadastroParticipante.php');
    return false; // evita envio padrão
}

function validarCadastroOrganizador() {
    var campoCodigo = document.getElementById('codigo-acesso');
    var campoNome = document.getElementById('nome-completo');
    var campoCPF = document.getElementById('cpf');
    var campoEmail = document.getElementById('email');
    var campoSenha = document.getElementById('senha');
    var campoConfirmar = document.getElementById('confirmar-senha');
    var campoTermos = document.getElementById('aceite-termos');

    var codigo = campoCodigo ? campoCodigo.value.trim().toUpperCase() : '';
    var nome = campoNome ? campoNome.value.trim() : '';
    var cpf = campoCPF ? campoCPF.value.trim() : '';
    var email = campoEmail ? campoEmail.value.trim() : '';
    var senha = campoSenha ? campoSenha.value.trim() : '';
    var confirmar = campoConfirmar ? campoConfirmar.value.trim() : '';
    var termos = campoTermos ? campoTermos.checked : false;

    if (!codigo || !nome || !cpf || !email || !senha || !confirmar) {
        mostrarMensagem('⚠️ Todos os campos são obrigatórios!', 'erro', 'erro-cadastro');
        return false;
    }

    // Validação específica do código de organizador (novo formato)
    if (codigo.length !== 8) {
        mostrarMensagem('⚠️ O código de acesso deve ter exatamente 8 caracteres!', 'erro', 'erro-cadastro');
        return false;
    }

    if (!/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{8}$/.test(codigo)) {
        mostrarMensagem('⚠️ Código inválido! Use apenas: A-Z (exceto I, O) e números 2-9 (exceto 0, 1)', 'erro', 'erro-cadastro');
        return false;
    }

    if (VALIDAR_CPF) {
        if (!validarCPF(cpf)) {
            mostrarMensagem('⚠️ CPF inválido!', 'erro', 'erro-cadastro');
            return false;
        }
    }

    if (VALIDAR_EMAIL) {
        if (!validarEmail(email)) {
            mostrarMensagem('⚠️ Formato de e-mail inválido!', 'erro', 'erro-cadastro');
            return false;
        }
    }

    if (VALIDAR_SENHA) {
        if (SENHA_MINIMA > 0 && senha.length < SENHA_MINIMA) {
            mostrarMensagem('⚠️ A senha deve ter pelo menos ' + SENHA_MINIMA + ' caracteres!', 'erro', 'erro-cadastro');
            return false;
        }

        if (senha !== confirmar) {
            mostrarMensagem('⚠️ As senhas não coincidem!', 'erro', 'erro-cadastro');
            return false;
        }
    }

    if (!termos) {
        mostrarMensagem('⚠️ É necessário aceitar os Termos de Condições.', 'erro', 'erro-cadastro');
        return false;
    }

    enviarCadastroAjax('form-cadastro-organizador', 'CadastroOrganizador.php');
    return false;
}

function inicializarValidacoesCadastro() {
    var mainContent = document.getElementById('main-content');
    if (mainContent) {
        mainContent.classList.remove('main-content--com-aviso');
        mainContent.classList.remove('conteudo-principal--com-aviso');
    }

    exibirErroURLPadrao();

    var formParticipante = document.getElementById('form-cadastro-participante');
    if (formParticipante && !formParticipante.dataset.validacaoCadastroAtiva) {
        formParticipante.addEventListener('submit', function validarEnvioCadastroParticipante(event) {
            if (!validarCadastroParticipante()) { event.preventDefault(); }
        });
        formParticipante.dataset.validacaoCadastroAtiva = '1';
    }

    var emailParticipante = document.getElementById('email');
    var senhaParticipante = document.getElementById('senha');
    var confirmarParticipante = document.getElementById('confirmar-senha');
    var cpfParticipante = document.getElementById('cpf');

    if (cpfParticipante && !cpfParticipante.dataset.mascaraAplicada) {
        adicionarMascara(cpfParticipante, '###.###.###-##');
        cpfParticipante.dataset.mascaraAplicada = '1';
    }

    if (emailParticipante && !emailParticipante.dataset.validacaoCadastroAtiva) {
        emailParticipante.addEventListener('focus', function limparErrosAoFocarEmailParticipante() {
            limparMensagens('erro-cadastro');
        });
        emailParticipante.addEventListener('blur', function validarEmailParticipanteAoPerderFoco() {
            var valor = emailParticipante.value.trim();
            if (valor && VALIDAR_EMAIL) {
                if (!validarEmail(valor)) {
                    mostrarMensagem('⚠️ Formato de e-mail inválido!', 'erro', 'erro-cadastro');
                }
            }
        });
        emailParticipante.dataset.validacaoCadastroAtiva = '1';
    }

    // Listeners de validação de senha (configuráveis)
    if (senhaParticipante && !senhaParticipante.dataset.validacaoCadastroAtiva) {
        senhaParticipante.addEventListener('focus', function limparErrosAoFocarSenhaParticipante() {
            limparMensagens('erro-cadastro');
        });
        senhaParticipante.addEventListener('blur', function validarSenhaParticipanteAoPerderFoco() {
            var valorSenha = senhaParticipante.value.trim();
            if (valorSenha && VALIDAR_SENHA) {
                if (SENHA_MINIMA > 0 && valorSenha.length < SENHA_MINIMA) {
                    mostrarMensagem('⚠️ A senha deve ter pelo menos ' + SENHA_MINIMA + ' caracteres!', 'erro', 'erro-cadastro');
                }
            }
        });
        senhaParticipante.dataset.validacaoCadastroAtiva = '1';
    }

    if (confirmarParticipante && !confirmarParticipante.dataset.validacaoCadastroAtiva) {
        confirmarParticipante.addEventListener('focus', function limparErrosAoFocarConfirmacaoParticipante() {
            limparMensagens('erro-cadastro');
        });
        confirmarParticipante.addEventListener('blur', function validarConfirmacaoParticipanteAoPerderFoco() {
            var valorSenha = senhaParticipante ? senhaParticipante.value.trim() : '';
            var valorConfirmar = confirmarParticipante.value.trim();
            if (valorConfirmar && VALIDAR_SENHA) {
                if (valorConfirmar !== valorSenha) {
                    mostrarMensagem('⚠️ As senhas não coincidem!', 'erro', 'erro-cadastro');
                }
            }
        });
        confirmarParticipante.dataset.validacaoCadastroAtiva = '1';
    }
    
    var formOrganizador = document.getElementById('form-cadastro-organizador');
    if (formOrganizador && !formOrganizador.dataset.validacaoCadastroAtiva) {
        formOrganizador.addEventListener('submit', function validarEnvioCadastroOrganizador(event) {
            if (!validarCadastroOrganizador()) { event.preventDefault(); }
        });
        formOrganizador.dataset.validacaoCadastroAtiva = '1';
    }

    var emailOrganizador = document.getElementById('email');
    var senhaOrganizador = document.getElementById('senha');
    var confirmarOrganizador = document.getElementById('confirmar-senha');
    var cpfOrganizador = document.getElementById('cpf');
    var codigoOrganizador = document.getElementById('codigo-acesso');

    if (cpfOrganizador && !cpfOrganizador.dataset.mascaraAplicada) {
        adicionarMascara(cpfOrganizador, '###.###.###-##');
        cpfOrganizador.dataset.mascaraAplicada = '1';
    }

    // Máscara para código de organizador (novo formato seguro)
    if (codigoOrganizador && !codigoOrganizador.dataset.mascaraAplicada) {
        codigoOrganizador.addEventListener('input', function() {
            // Remove caracteres não permitidos e converte para maiúsculo
            let valor = this.value.toUpperCase().replace(/[^ABCDEFGHJKLMNPQRSTUVWXYZ23456789]/g, '');
            // Limita a 8 caracteres
            valor = valor.substring(0, 8);
            this.value = valor;
        });
        codigoOrganizador.dataset.mascaraAplicada = '1';
    }

    if (emailOrganizador && !emailOrganizador.dataset.validacaoCadastroAtiva) {
        emailOrganizador.addEventListener('focus', function limparErrosAoFocarEmailOrganizador() {
            limparMensagens('erro-cadastro');
        });
        emailOrganizador.addEventListener('blur', function validarEmailOrganizadorAoPerderFoco() {
            var valorOrganizador = emailOrganizador.value.trim();
            if (valorOrganizador && VALIDAR_EMAIL) {
                if (!validarEmail(valorOrganizador)) {
                    mostrarMensagem('⚠️ Formato de e-mail inválido!', 'erro', 'erro-cadastro');
                }
            }
        });
        emailOrganizador.dataset.validacaoCadastroAtiva = '1';
    }

    // Listeners de validação de senha (configuráveis)
    if (senhaOrganizador && !senhaOrganizador.dataset.validacaoCadastroAtiva) {
        senhaOrganizador.addEventListener('focus', function limparErrosAoFocarSenhaOrganizador() {
            limparMensagens('erro-cadastro');
        });
        senhaOrganizador.addEventListener('blur', function validarSenhaOrganizadorAoPerderFoco() {
            var valorSenhaOrganizador = senhaOrganizador.value.trim();
            if (valorSenhaOrganizador && VALIDAR_SENHA) {
                if (SENHA_MINIMA > 0 && valorSenhaOrganizador.length < SENHA_MINIMA) {
                    mostrarMensagem('⚠️ A senha deve ter pelo menos ' + SENHA_MINIMA + ' caracteres!', 'erro', 'erro-cadastro');
                }
            }
        });
        senhaOrganizador.dataset.validacaoCadastroAtiva = '1';
    }

    if (confirmarOrganizador && !confirmarOrganizador.dataset.validacaoCadastroAtiva) {
        confirmarOrganizador.addEventListener('focus', function limparErrosAoFocarConfirmacaoOrganizador() {
            limparMensagens('erro-cadastro');
        });
        confirmarOrganizador.addEventListener('blur', function validarConfirmacaoOrganizadorAoPerderFoco() {
            var valorSenhaOrganizador = senhaOrganizador ? senhaOrganizador.value.trim() : '';
            var valorConfirmarOrganizador = confirmarOrganizador.value.trim();
            if (valorConfirmarOrganizador && VALIDAR_SENHA) {
                if (valorConfirmarOrganizador !== valorSenhaOrganizador) {
                    mostrarMensagem('⚠️ As senhas não coincidem!', 'erro', 'erro-cadastro');
                }
            }
        });
        confirmarOrganizador.dataset.validacaoCadastroAtiva = '1';
    }

    // Aplica toggle global (script ToggleSenha.js)
    if (typeof window.aplicarToggleSenhas === 'function') { window.aplicarToggleSenhas(); }
    
}

if (typeof window !== 'undefined') {
    window.inicializarValidacoesCadastro = inicializarValidacoesCadastro;
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarValidacoesCadastro);
} else {
    inicializarValidacoesCadastro();
}

// ================= Envio AJAX Reutilizável =================
function enviarCadastroAjax(idFormulario, urlDestino) {
    var formulario = document.getElementById(idFormulario);
    if (!formulario) return;

    var botaoCadastrar = document.getElementById('btnCadastrar');
    if (botaoCadastrar) { botaoCadastrar.disabled = true; botaoCadastrar.textContent = 'Cadastrando...'; }
    mostrarMensagem('🔄 Cadastrando...', 'info', 'erro-cadastro');

    var dadosFormulario = new FormData(formulario);
    var requisicao = new XMLHttpRequest();
    requisicao.open('POST', urlDestino, true);
    requisicao.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    requisicao.onreadystatechange = function() {
        if (requisicao.readyState === 4) {
            if (botaoCadastrar) { botaoCadastrar.disabled = false; botaoCadastrar.textContent = 'Cadastrar'; }
            try {
                var resposta = JSON.parse(requisicao.responseText);
                if (resposta.status === 'sucesso') {
                    var segundosRestantes = 10;
                    var timerAtivo = true;
                    var listenersCancelamento = []; // Array para armazenar as funções dos listeners
                    
                    function atualizarMensagemSucesso(){
                        var textoBase = (resposta.mensagem || '✅ Cadastro realizado!');
                        mostrarMensagem(textoBase, 'sucesso', 'erro-cadastro');
                        var caixaMensagem = document.getElementById('erro-cadastro');
                        if (caixaMensagem) {
                            caixaMensagem.classList.add('mensagem-multilinha');
                            if (timerAtivo) {
                                caixaMensagem.innerHTML = '<span>' + textoBase + '</span><br><span style="font-weight:500; font-size:0.9em; opacity:0.9;">Redirecionando em ' + segundosRestantes + 's... <em>(clique ou digite algo para cancelar)</em></span>';
                            } else {
                                caixaMensagem.innerHTML = '<span>' + textoBase + '</span><br><span style="font-weight:500; font-size:0.9em; opacity:0.7;">Redirecionamento cancelado. <a href="#" onclick="carregarPagina(\'login\')" style="color: var(--azul-principal); text-decoration: underline;">Clique aqui para ir ao login</a></span>';
                            }
                        }
                    }
                    
                    function cancelarTimer() {
                        console.log('cancelarTimer chamada. Timer ativo:', timerAtivo, 'Window timer:', !!window.temporizadorCadastro);
                        if (timerAtivo && window.temporizadorCadastro) {
                            console.log('✅ Timer de redirecionamento cancelado pela interação do usuário');
                            clearInterval(window.temporizadorCadastro);
                            window.temporizadorCadastro = null;
                            timerAtivo = false;
                            atualizarMensagemSucesso();
                            removerListenersInteracao();
                        } else {
                            console.log('❌ Timer não cancelado - já inativo ou não existe');
                        }
                    }
                    
                    function adicionarListenersInteracao() {
                        // Eventos que cancelam o timer (exceto mousemove)
                        const eventosInteracao = [
                            'click', 'dblclick',
                            'keydown', 'keypress', 'keyup',
                            'input', 'change',
                            'focus', 'blur',
                            'scroll', 'wheel',
                            'touchstart', 'touchend',
                            'mousedown', 'mouseup'
                        ];
                        
                        console.log('Adicionando listeners para cancelar timer:', eventosInteracao);
                        
                        eventosInteracao.forEach(evento => {
                            // Criar uma função wrapper para cada evento para poder remover depois
                            const listenerWrapper = function(e) {
                                console.log('Evento detectado para cancelar timer:', evento, e.type);
                                cancelarTimer();
                            };
                            
                            document.addEventListener(evento, listenerWrapper, { passive: true });
                            
                            // Guardar referência para remoção posterior
                            listenersCancelamento.push({
                                evento: evento,
                                funcao: listenerWrapper
                            });
                        });
                    }
                    
                    function removerListenersInteracao() {
                        console.log('Removendo listeners de cancelamento:', listenersCancelamento.length);
                        listenersCancelamento.forEach(item => {
                            document.removeEventListener(item.evento, item.funcao);
                        });
                        listenersCancelamento = [];
                    }
                    
                    atualizarMensagemSucesso();
                    
                    // Limpar timer anterior se existir
                    if (window.temporizadorCadastro) {
                        clearInterval(window.temporizadorCadastro);
                    }
                    
                    // Adicionar listeners para cancelar timer
                    adicionarListenersInteracao();
                    
                    // Teste rápido - adicionar um listener de clique especial para debug
                    document.addEventListener('click', function(e) {
                        console.log('CLIQUE DETECTADO:', e.target, 'Timer ativo:', timerAtivo);
                    }, { once: false });
                    
                    console.log('Timer de cadastro iniciado. Timer ativo:', timerAtivo);
                    
                    window.temporizadorCadastro = setInterval(function(){
                        if (!timerAtivo) {
                            clearInterval(window.temporizadorCadastro);
                            window.temporizadorCadastro = null;
                            return;
                        }
                        
                        segundosRestantes--;
                        if (segundosRestantes <= 0){
                            clearInterval(window.temporizadorCadastro);
                            window.temporizadorCadastro = null;
                            timerAtivo = false;
                            removerListenersInteracao();
                            carregarPagina('login');
                        } else {
                            atualizarMensagemSucesso();
                        }
                    }, 1000);
                } else {
                    mostrarMensagem(resposta.mensagem || '❌ Erro ao cadastrar.', 'erro', 'erro-cadastro');
                }
            } catch(erro){
                mostrarMensagem('❌ Erro inesperado. Tente novamente.', 'erro', 'erro-cadastro');
            }
        }
    };
    requisicao.onerror = function(){
        if (botaoCadastrar) { botaoCadastrar.disabled = false; botaoCadastrar.textContent = 'Cadastrar'; }
        mostrarMensagem('❌ Falha de rede. Verifique sua conexão.', 'erro', 'erro-cadastro');
    };
    requisicao.send(dadosFormulario);
}
