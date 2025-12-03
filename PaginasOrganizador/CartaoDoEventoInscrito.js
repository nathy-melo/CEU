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

// Funções para controlar os modais de cancelamento
function abrirModalConfirmarCancelamento() {
    const modal = document.getElementById('modalConfirmarCancelamento');
    if (modal) {
        modal.classList.add('ativo');
        bloquearScroll();
    }
}

function fecharModalConfirmarCancelamento() {
    const modal = document.getElementById('modalConfirmarCancelamento');
    if (modal) {
        modal.classList.remove('ativo');
        desbloquearScroll();
    }
}

function confirmarCancelamento() {
    // Pegar código do evento da URL
    var params = new URLSearchParams(window.location.search);
    var codEvento = params.get('id');

    if (!codEvento) {
        alert('Erro: código do evento não encontrado');
        return;
    }

    var formData = new FormData();
    formData.append('cod_evento', codEvento);

    fetch('../PaginasParticipante/DesinscreverEvento.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                fecharModalConfirmarCancelamento();
                abrirModalCancelamentoConfirmado();
            } else {
                alert(data.mensagem || 'Erro ao cancelar inscrição');
                fecharModalConfirmarCancelamento();
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao processar cancelamento');
            fecharModalConfirmarCancelamento();
        });
}

function abrirModalCancelamentoConfirmado() {
    const modal = document.getElementById('modalCancelamentoConfirmado');
    if (modal) {
        modal.classList.add('ativo');
        bloquearScroll();
    }
}

function fecharModalCancelamentoConfirmado() {
    const modal = document.getElementById('modalCancelamentoConfirmado');
    if (modal) {
        modal.classList.remove('ativo');
        desbloquearScroll();
    }
    // Redirecionar para eventos inscritos após fechar modal
    if (typeof carregarPagina === 'function') {
        carregarPagina('eventosInscritos');
    } else {
        window.location.href = 'ContainerOrganizador.php';
    }
}

// Expor funções globalmente
window.abrirModalConfirmarCancelamento = abrirModalConfirmarCancelamento;
window.fecharModalConfirmarCancelamento = fecharModalConfirmarCancelamento;
window.confirmarCancelamento = confirmarCancelamento;
window.abrirModalCancelamentoConfirmado = abrirModalCancelamentoConfirmado;
window.fecharModalCancelamentoConfirmado = fecharModalCancelamentoConfirmado;

function inicializarEventosCartaoDoEventoInscrito() {
    var btnDesinscrever = document.querySelector('.botao-desinscrever');
    if (btnDesinscrever) {
        btnDesinscrever.onclick = abrirModalConfirmarCancelamento;
    }
}

// Inicializa ao carregar normalmente
window.addEventListener('DOMContentLoaded', inicializarEventosCartaoDoEventoInscrito);
// Permite inicialização após AJAX
window.inicializarEventosCartaoDoEventoInscrito = inicializarEventosCartaoDoEventoInscrito;

// Garante inicialização imediata após carregamento dinâmico do JS
setTimeout(inicializarEventosCartaoDoEventoInscrito, 0);
