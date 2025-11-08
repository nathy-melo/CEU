function mostrarMensagemInscricaoFeita() {
    var mainContent = document.getElementById('main-content');
    if (!mainContent) return;
    mainContent.innerHTML = '';

    var container = document.createElement('div');
    container.style.display = 'flex';
    container.style.flexDirection = 'column';
    container.style.alignItems = 'center';
    container.style.justifyContent = 'center';
    container.style.background = 'var(--caixas)';
    container.style.borderRadius = '1.875rem';
    container.style.padding = '2.5rem';
    container.style.maxWidth = '51.5625rem';
    container.style.margin = '2rem auto';
    container.style.width = '100%';
    container.style.boxShadow = '0 0.15rem 0.75rem 0 rgba(0, 0, 0, 0.4)';

    var titulo = document.createElement('h2');
    titulo.textContent = 'Inscrição feita com sucesso!';
    titulo.style.color = 'var(--branco)';
    titulo.style.fontSize = '2rem';
    titulo.style.fontWeight = '700';
    titulo.style.marginBottom = '2rem';
    titulo.style.textAlign = 'center';

    var botoesWrapper = document.createElement('div');
    botoesWrapper.style.display = 'flex';
    botoesWrapper.style.flexDirection = 'row';
    botoesWrapper.style.justifyContent = 'space-between';
    botoesWrapper.style.alignItems = 'center';
    botoesWrapper.style.width = '100%';
    botoesWrapper.style.gap = '1rem';

    var btnVoltar = document.createElement('button');
    btnVoltar.type = 'button';
    btnVoltar.className = 'botao';
    btnVoltar.textContent = 'Voltar';
    btnVoltar.style.backgroundColor = 'var(--botao)';
    btnVoltar.style.color = 'var(--branco)';
    btnVoltar.style.padding = '0.75rem 1.5rem';
    btnVoltar.style.fontSize = '1rem';
    btnVoltar.style.fontWeight = '700';
    btnVoltar.style.width = '180px';
    btnVoltar.style.whiteSpace = 'nowrap';
    btnVoltar.style.textAlign = 'center';
    btnVoltar.style.display = 'flex';
    btnVoltar.style.alignItems = 'center';
    btnVoltar.style.justifyContent = 'center';
    btnVoltar.onclick = function () { carregarPagina('inicio'); };

    var btnCancelar = document.createElement('button');
    btnCancelar.type = 'button';
    btnCancelar.className = 'botao';
    btnCancelar.textContent = 'Cancelar Inscrição';
    btnCancelar.style.backgroundColor = 'var(--vermelho)';
    btnCancelar.style.color = 'var(--branco)';
    btnCancelar.style.padding = '0.75rem 1.5rem';
    btnCancelar.style.fontSize = '1rem';
    btnCancelar.style.fontWeight = '700';
    btnCancelar.style.width = '180px';
    btnCancelar.style.whiteSpace = 'nowrap';
    btnCancelar.style.textAlign = 'center';
    btnCancelar.style.display = 'flex';
    btnCancelar.style.alignItems = 'center';
    btnCancelar.style.justifyContent = 'center';
    btnCancelar.onclick = function () {
        var params = new URLSearchParams(window.location.search);
        var codEvento = params.get('id');
        if (!codEvento) { alert('Erro: código do evento não encontrado'); return; }
        desinscreverDoEvento(codEvento);
    };

    botoesWrapper.appendChild(btnCancelar);
    botoesWrapper.appendChild(btnVoltar);
    container.appendChild(titulo);
    container.appendChild(botoesWrapper);
    mainContent.appendChild(container);
}

function inscreverNoEvento(codEvento) {
    if (!codEvento) { alert('Erro: código do evento não encontrado'); return; }
    var formData = new FormData();
    formData.append('cod_evento', codEvento);
    fetch('../PaginasParticipante/InscreverEvento.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.sucesso) {
                // Mostra modal de sucesso
                abrirModalInscricaoConfirmada();
                // Atualiza o botão
                verificarStatusInscricao(codEvento);
                if (typeof window.dispatchEvent === 'function') {
                    window.dispatchEvent(new CustomEvent('inscricaoAtualizada'));
                }
            } else {
                alert(data.mensagem || 'Erro ao realizar inscrição');
            }
        })
        .catch(err => { console.error('Erro:', err); alert('Erro ao processar inscrição'); });
}

function desinscreverDoEvento(codEvento) {
    if (!codEvento) { alert('Erro: código do evento não encontrado'); return; }
    var formData = new FormData();
    formData.append('cod_evento', codEvento);
    fetch('../PaginasParticipante/DesinscreverEvento.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.sucesso) {
                // Mostra modal de sucesso
                abrirModalDesinscricaoConfirmada();
                // Atualiza o botão
                verificarStatusInscricao(codEvento);
                if (typeof window.dispatchEvent === 'function') {
                    window.dispatchEvent(new CustomEvent('inscricaoAtualizada'));
                }
            } else {
                alert(data.mensagem || 'Erro ao cancelar inscrição');
            }
        })
        .catch(err => { console.error('Erro:', err); alert('Erro ao processar cancelamento'); });
}

// Variável global para armazenar o código do evento
var codEventoAtualInscricao = null;

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

// Funções dos modais de sucesso
function abrirModalInscricaoConfirmada() {
    document.getElementById('modalInscricaoConfirmada').classList.add('ativo');
    bloquearScroll();
}

function fecharModalInscricaoConfirmada() {
    document.getElementById('modalInscricaoConfirmada').classList.remove('ativo');
    desbloquearScroll();
}

function abrirModalDesinscricaoConfirmada() {
    document.getElementById('modalDesinscricaoConfirmada').classList.add('ativo');
    bloquearScroll();
}

function fecharModalDesinscricaoConfirmada() {
    document.getElementById('modalDesinscricaoConfirmada').classList.remove('ativo');
    desbloquearScroll();
}

function verificarStatusInscricao(codEvento) {
    if (!codEvento) return;
    fetch('../PaginasParticipante/VerificarInscricao.php?cod_evento=' + encodeURIComponent(codEvento))
        .then(r => r.json())
        .then(data => {
            var btnInscrever = document.querySelector('.BotaoInscrever .botao');
            if (!btnInscrever) return;
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
        })
        .catch(err => console.error('Erro ao verificar inscrição:', err));
}

function inicializarEventosCartaoEvento() {
    var params = new URLSearchParams(window.location.search);
    var codEvento = params.get('id');
    codEventoAtualInscricao = codEvento; // Armazena globalmente
    if (codEvento) verificarStatusInscricao(codEvento);

    var btnInscrever = document.querySelector('.BotaoInscrever .botao');
    if (btnInscrever) {
        btnInscrever.classList.add('botao-inscrever');
        btnInscrever.onclick = function () {
            // Se estiver desabilitado (já inscrito), não faz nada
            if (this.disabled) {
                return;
            }
            // Se não estiver inscrito, permite inscrever-se
            if (this.dataset.inscrito === 'false') {
                abrirModalConfirmarInscricao();
            }
        };
    }
}

window.addEventListener('DOMContentLoaded', inicializarEventosCartaoEvento);
window.inicializarEventosCartaoEvento = inicializarEventosCartaoEvento;
window.abrirModalInscricaoConfirmada = abrirModalInscricaoConfirmada;
window.fecharModalInscricaoConfirmada = fecharModalInscricaoConfirmada;
window.abrirModalDesinscricaoConfirmada = abrirModalDesinscricaoConfirmada;
window.fecharModalDesinscricaoConfirmada = fecharModalDesinscricaoConfirmada;
