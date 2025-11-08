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
    desinscreverDoEvento();
}

// Funções do modal de desinscrição confirmada
function abrirModalDesinscricaoConfirmada() {
    document.getElementById('modalDesinscricaoConfirmada').classList.add('ativo');
    bloquearScroll();
}

function fecharModalDesinscricaoConfirmada() {
    document.getElementById('modalDesinscricaoConfirmada').classList.remove('ativo');
    desbloquearScroll();
    // Voltar para "Meus Eventos"
    window.location.href = 'ContainerParticipante.php?pagina=meusEventos';
}

// Função para desinscrever do evento
function desinscreverDoEvento() {
    var params = new URLSearchParams(window.location.search);
    var codEvento = params.get('id');
    
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

// Inicializar eventos
document.addEventListener('DOMContentLoaded', function() {
    var btnDesinscrever = document.getElementById('btn-desinscrever');
    if (btnDesinscrever) {
        btnDesinscrever.onclick = function() {
            abrirModalConfirmarDesinscricao();
        };
    }
});
