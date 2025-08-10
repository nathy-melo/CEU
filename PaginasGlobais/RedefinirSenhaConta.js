// Salva o conteúdo original para restaurar ao clicar em Voltar
let conteudoOriginalRedefinirSenha = null;

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
    container.style.background = '#4f6c8c';
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
    btnVoltar.className = ' botao';
    btnVoltar.textContent = 'Voltar';
    btnVoltar.onclick = function() {
        // Restaura o conteúdo original da página
        if (conteudoOriginalRedefinirSenha !== null) {
            mainContent.innerHTML = conteudoOriginalRedefinirSenha;
            conteudoOriginalRedefinirSenha = null;
            // Reatribui os eventos após restaurar o conteúdo
            if (typeof window.inicializarRedefinirSenhaParticipante === 'function') {
                setTimeout(window.inicializarRedefinirSenhaParticipante, 0);
            }
        } else if (typeof history !== 'undefined' && history.back) {
            history.back();
        }
    };

    container.appendChild(titulo);
    container.appendChild(btnVoltar);
    mainContent.appendChild(container);
}

function inicializarRedefinirSenhaParticipante() {
    var form = document.querySelector('.redefinir-senha-formulario');
    if (form) {
        // Garante que não adicionaremos múltiplos listeners
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            mostrarMensagemSenhaRedefinida();
        }, { once: true });

        // Botão Voltar do formulário (antes da confirmação)
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
}

// Inicializações para funcionar com carregamento via AJAX
window.addEventListener('DOMContentLoaded', inicializarRedefinirSenhaParticipante);
window.inicializarRedefinirSenhaParticipante = inicializarRedefinirSenhaParticipante;
setTimeout(inicializarRedefinirSenhaParticipante, 0);
