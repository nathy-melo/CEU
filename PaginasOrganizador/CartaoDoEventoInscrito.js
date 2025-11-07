// Funções para controlar os modais de cancelamento
function abrirModalConfirmarCancelamento() {
    document.getElementById('modalConfirmarCancelamento').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function fecharModalConfirmarCancelamento() {
    document.getElementById('modalConfirmarCancelamento').style.display = 'none';
    document.body.style.overflow = '';
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
    document.getElementById('modalCancelamentoConfirmado').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function fecharModalCancelamentoConfirmado() {
    document.getElementById('modalCancelamentoConfirmado').style.display = 'none';
    document.body.style.overflow = '';
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
