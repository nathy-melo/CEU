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
    btnVoltar.style.padding = '0.75rem 2rem';
    btnVoltar.style.fontSize = '1rem';
    btnVoltar.style.fontWeight = '700';
    btnVoltar.style.flex = '1';
    btnVoltar.onclick = function() { carregarPagina('inicio'); };
    
    var btnCancelar = document.createElement('button');
    btnCancelar.type = 'button';
    btnCancelar.className = 'botao';
    btnCancelar.textContent = 'Cancelar Inscrição';
    btnCancelar.style.backgroundColor = '#d9534f';
    btnCancelar.style.color = 'var(--branco)';
    btnCancelar.style.padding = '0.75rem 2rem';
    btnCancelar.style.fontSize = '1rem';
    btnCancelar.style.fontWeight = '700';
    btnCancelar.style.flex = '1';
    btnCancelar.onclick = function() { 
        // Pegar código do evento da URL
        var params = new URLSearchParams(window.location.search);
        var codEvento = params.get('id');
        
        if (!codEvento) {
            alert('Erro: código do evento não encontrado');
            return;
        }
        
        // Confirmar cancelamento
        if (confirm('Deseja realmente cancelar sua inscrição neste evento?')) {
            desinscreverDoEvento(codEvento);
        }
    };
    
    botoesWrapper.appendChild(btnVoltar);
    botoesWrapper.appendChild(btnCancelar);
    container.appendChild(titulo);
    container.appendChild(botoesWrapper);
    mainContent.appendChild(container);
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
            mostrarMensagemInscricaoFeita();
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
            alert('Inscrição cancelada com sucesso!');
            // Disparar evento personalizado para atualizar outras páginas
            if (typeof window.dispatchEvent === 'function') {
                window.dispatchEvent(new CustomEvent('inscricaoAtualizada'));
            }
            // Recarregar a página para atualizar o botão
            location.reload();
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
                    btnInscrever.textContent = 'Já Inscrito';
                    btnInscrever.style.backgroundColor = '#5cb85c';
                    btnInscrever.disabled = true;
                    btnInscrever.style.cursor = 'not-allowed';
                } else {
                    btnInscrever.textContent = 'Inscrever-se';
                    btnInscrever.disabled = false;
                    btnInscrever.style.cursor = 'pointer';
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

    // Verificar status de inscrição
    if (codEvento) {
        verificarStatusInscricao(codEvento);
    }

    var btnInscrever = document.querySelector('.botao-inscrever');
    if (btnInscrever) {
        btnInscrever.onclick = function() {
            if (!this.disabled) {
                inscreverNoEvento(codEvento);
            }
        };
    }
}

// Tenta inicializar ao carregar o HTML normalmente
window.addEventListener('DOMContentLoaded', inicializarEventosCartaoEvento);

// Permite que outros scripts chamem a inicialização após AJAX
window.inicializarEventosCartaoEvento = inicializarEventosCartaoEvento; 