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
                mostrarMensagemInscricaoFeita();
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
                if (typeof window.dispatchEvent === 'function') {
                    window.dispatchEvent(new CustomEvent('inscricaoAtualizada'));
                }
                location.reload();
            } else {
                alert(data.mensagem || 'Erro ao cancelar inscrição');
            }
        })
        .catch(err => { console.error('Erro:', err); alert('Erro ao processar cancelamento'); });
}

function verificarStatusInscricao(codEvento) {
    if (!codEvento) return;
    fetch('../PaginasParticipante/VerificarInscricao.php?cod_evento=' + encodeURIComponent(codEvento))
        .then(r => r.json())
        .then(data => {
            var btnInscrever = document.querySelector('.BotaoInscrever .botao');
            if (!btnInscrever) return;
            if (data.inscrito) {
                btnInscrever.textContent = 'Desinscrever-se';
                btnInscrever.style.backgroundColor = 'var(--vermelho)';
                btnInscrever.disabled = false;
                btnInscrever.style.cursor = 'pointer';
                btnInscrever.dataset.inscrito = 'true';
            } else {
                btnInscrever.textContent = 'Inscrever-se';
                btnInscrever.style.backgroundColor = 'var(--botao)';
                btnInscrever.disabled = false;
                btnInscrever.style.cursor = 'pointer';
                btnInscrever.dataset.inscrito = 'false';
            }
        })
        .catch(err => console.error('Erro ao verificar inscrição:', err));
}

function inicializarEventosCartaoEvento() {
    var params = new URLSearchParams(window.location.search);
    var codEvento = params.get('id');
    if (codEvento) verificarStatusInscricao(codEvento);

    var btnInscrever = document.querySelector('.BotaoInscrever .botao');
    if (btnInscrever) {
        btnInscrever.classList.add('botao-inscrever');
        btnInscrever.onclick = function () {
            if (this.disabled) return;
            // Verifica se já está inscrito
            if (this.dataset.inscrito === 'true') {
                desinscreverDoEvento(codEvento);
            } else {
                inscreverNoEvento(codEvento);
            }
        };
    }
}

window.addEventListener('DOMContentLoaded', inicializarEventosCartaoEvento);
window.inicializarEventosCartaoEvento = inicializarEventosCartaoEvento;