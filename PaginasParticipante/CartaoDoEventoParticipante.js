// Função para bloquear scroll
function bloquearScroll() {
    document.body.classList.add('modal-aberto');
    document.addEventListener('wheel', prevenirScroll, { passive: false });
    document.addEventListener('touchmove', prevenirScroll, { passive: false });
    document.addEventListener('keydown', prevenirScrollTeclado, false);
}

// Função para desbloquear scroll
function desbloquearScroll() {
    document.body.classList.remove('modal-aberto');
    document.removeEventListener('wheel', prevenirScroll);
    document.removeEventListener('touchmove', prevenirScroll);
    document.removeEventListener('keydown', prevenirScrollTeclado);
}

// Previne scroll com mouse wheel e touchmove
function prevenirScroll(e) {
    if (document.body.classList.contains('modal-aberto')) {
        e.preventDefault();
    }
}

// Previne scroll com setas do teclado e Page Up/Down
function prevenirScrollTeclado(e) {
    if (!document.body.classList.contains('modal-aberto')) return;
    
    const teclas = [32, 33, 34, 35, 36, 37, 38, 39, 40];
    if (teclas.includes(e.keyCode)) {
        e.preventDefault();
    }
}

// Variável global para armazenar o código do evento
var codEventoAtualInscricao = null;

// Funções dos modais de confirmação
function abrirModalConfirmarInscricao() {
    document.getElementById('modalConfirmarInscricao').classList.add('ativo');
    bloquearScroll();
}

function fecharModalConfirmarInscricao() {
    document.getElementById('modalConfirmarInscricao').classList.remove('ativo');
    desbloquearScroll();
}

function confirmarInscricao() {
    fecharModalConfirmarInscricao();
    if (codEventoAtualInscricao) {
        inscreverNoEvento(codEventoAtualInscricao);
    }
}

// Funções do modal de desinscrição
function abrirModalConfirmarDesinscricao() {
    document.getElementById('modalConfirmarDesinscricao').classList.add('ativo');
    bloquearScroll();
}

function fecharModalConfirmarDesinscricao() {
    document.getElementById('modalConfirmarDesinscricao').classList.remove('ativo');
    desbloquearScroll();
}

function confirmarDesinscricao() {
    fecharModalConfirmarDesinscricao();
    if (codEventoAtualInscricao) {
        desinscreverDoEvento(codEventoAtualInscricao);
    }
}

// Funções do modal de inscrição confirmada
function abrirModalInscricaoConfirmada() {
    document.getElementById('modalInscricaoConfirmada').classList.add('ativo');
    bloquearScroll();
}

function fecharModalInscricaoConfirmada() {
    document.getElementById('modalInscricaoConfirmada').classList.remove('ativo');
    desbloquearScroll();
}

// Funções do modal de desinscrição confirmada
function abrirModalDesinscricaoConfirmada() {
    document.getElementById('modalDesinscricaoConfirmada').classList.add('ativo');
    bloquearScroll();
}

function fecharModalDesinscricaoConfirmada() {
    document.getElementById('modalDesinscricaoConfirmada').classList.remove('ativo');
    desbloquearScroll();
}

// Função para inscrever no evento
function inscreverNoEvento(codEvento) {
    if (!codEvento) {
        alert('Erro: código do evento não encontrado');
        return;
    }

    var formData = new FormData();
    formData.append('cod_evento', codEvento);

    fetch('InscreverEvento.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            abrirModalInscricaoConfirmada();
            // Atualiza o botão
            verificarStatusInscricao(codEvento);
            // Disparar evento personalizado para atualizar outras páginas
            if (typeof window.dispatchEvent === 'function') {
                window.dispatchEvent(new CustomEvent('inscricaoAtualizada'));
            }
        } else {
            alert(data.mensagem || 'Erro ao realizar inscrição');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao processar inscrição');
    });
}

// Função para desinscrever do evento
function desinscreverDoEvento(codEvento) {
    if (!codEvento) {
        alert('Erro: código do evento não encontrado');
        return;
    }

    var formData = new FormData();
    formData.append('cod_evento', codEvento);

    fetch('DesinscreverEvento.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            abrirModalDesinscricaoConfirmada();
            // Atualiza o botão
            verificarStatusInscricao(codEvento);
            // Disparar evento personalizado para atualizar outras páginas
            if (typeof window.dispatchEvent === 'function') {
                window.dispatchEvent(new CustomEvent('inscricaoAtualizada'));
            }
        } else {
            alert(data.mensagem || 'Erro ao cancelar inscrição');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao processar cancelamento');
    });
}

// Função para verificar status de inscrição e atualizar botão
function verificarStatusInscricao(codEvento) {
    if (!codEvento) return;

    fetch('VerificarInscricao.php?cod_evento=' + codEvento)
        .then(response => response.json())
        .then(data => {
            var btnInscrever = document.querySelector('.botao-inscrever');
            if (btnInscrever) {
                if (data.inscrito) {
                    // Mostra "Já Inscrito no Evento" em verde e desabilita o botão
                    // Para cancelar a inscrição, deve ir em "Meus Eventos"
                    btnInscrever.textContent = 'Já Inscrito no Evento';
                    btnInscrever.style.backgroundColor = '#28a745'; // Verde
                    btnInscrever.disabled = true;
                    btnInscrever.style.cursor = 'not-allowed';
                    btnInscrever.style.opacity = '0.9';
                    btnInscrever.dataset.inscrito = 'true';
                } else {
                    btnInscrever.textContent = 'Inscrever-se';
                    btnInscrever.style.backgroundColor = 'var(--botao)';
                    btnInscrever.disabled = false;
                    btnInscrever.style.cursor = 'pointer';
                    btnInscrever.style.opacity = '1';
                    btnInscrever.dataset.inscrito = 'false';
                }
            }
        })
        .catch(error => {
            console.error('Erro ao verificar inscrição:', error);
        });
}

function inicializarEventosCartaoEvento() {
    // Pegar código do evento da URL
    var params = new URLSearchParams(window.location.search);
    var codEvento = params.get('id');
    
    // Armazena o código globalmente
    codEventoAtualInscricao = codEvento;

    // Verificar status de inscrição
    if (codEvento) {
        verificarStatusInscricao(codEvento);
    }

    var btnInscrever = document.querySelector('.botao-inscrever');
    if (btnInscrever) {
        btnInscrever.onclick = function() {
            if (!this.disabled) {
                abrirModalConfirmarInscricao();
            }
        };
    }
}

// Tenta inicializar ao carregar o HTML normalmente
window.addEventListener('DOMContentLoaded', inicializarEventosCartaoEvento);

// Permite que outros scripts chamem a inicialização após AJAX
window.inicializarEventosCartaoEvento = inicializarEventosCartaoEvento;