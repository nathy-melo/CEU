function mostrarMensagemDesinscricao() {
    var mainContent = document.getElementById('main-content');
    if (!mainContent) return;
    mainContent.innerHTML = '';
    var container = document.createElement('div');
    container.style.display = 'flex';
    container.style.flexDirection = 'column';
    container.style.alignItems = 'center';
    container.style.justifyContent = 'center';
    container.style.background = '#4f6c8c';
    container.style.borderRadius = '1.875rem';
    container.style.padding = '1.875rem';
    container.style.maxWidth = '32rem';
    container.style.margin = '3rem auto 0 auto';
    container.style.width = '100%';
    container.style.boxShadow = '0 0.25rem 1rem rgba(0,0,0,0.25)';

    var titulo = document.createElement('h2');
    titulo.textContent = 'Você deseja cancelar a inscrição?';
    titulo.style.color = '#fff';
    titulo.style.fontSize = '1.5em';
    titulo.style.marginBottom = '2rem';
    titulo.style.textAlign = 'center';

    var botoesWrapper = document.createElement('div');
    botoesWrapper.style.display = 'flex';
    botoesWrapper.style.flexDirection = 'row';
    botoesWrapper.style.justifyContent = 'center';
    botoesWrapper.style.alignItems = 'center';
    botoesWrapper.style.gap = '2.5rem';

    var btnCancelar = document.createElement('button');
    btnCancelar.className = 'botao';
    btnCancelar.type = 'button';
    btnCancelar.textContent = 'Cancelar';
    btnCancelar.style.backgroundColor = '#6598d2';
    btnCancelar.style.color = '#fff';
    btnCancelar.style.border = 'none';
    btnCancelar.style.borderRadius = '0.3rem';
    btnCancelar.style.padding = '0.5rem 2.5rem';
    btnCancelar.style.fontWeight = '700';
    btnCancelar.style.fontSize = '1.1rem';
    btnCancelar.style.cursor = 'pointer';
    btnCancelar.style.boxShadow = '0 0.125rem 0.25rem rgba(0,0,0,0.15)';
    btnCancelar.onclick = function() { window.location.reload(); };

    var btnContinuar = document.createElement('button');
    btnContinuar.className = 'botao';
    btnContinuar.type = 'button';
    btnContinuar.textContent = 'Continuar';
    btnContinuar.style.backgroundColor = '#d9534f';
    btnContinuar.style.color = '#fff';
    btnContinuar.style.border = 'none';
    btnContinuar.style.borderRadius = '0.3rem';
    btnContinuar.style.padding = '0.5rem 2.5rem';
    btnContinuar.style.fontWeight = '700';
    btnContinuar.style.fontSize = '1.1rem';
    btnContinuar.style.cursor = 'pointer';
    btnContinuar.style.boxShadow = '0 0.125rem 0.25rem rgba(0,0,0,0.15)';
    btnContinuar.onclick = function() {
        // Pegar código do evento da URL
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
                alert('Inscrição cancelada com sucesso!');
                carregarPagina('meusEventos');
            } else {
                alert(data.mensagem || 'Erro ao cancelar inscrição');
                window.location.reload();
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao processar cancelamento');
            window.location.reload();
        });
    };

    botoesWrapper.appendChild(btnCancelar);
    botoesWrapper.appendChild(btnContinuar);
    container.appendChild(titulo);
    container.appendChild(botoesWrapper);
    mainContent.appendChild(container);
}

function inicializarEventosCartaoDoEventoInscrito() {
    var btnDesinscrever = document.querySelector('.botao-desinscrever');
    if (btnDesinscrever) {
        btnDesinscrever.onclick = mostrarMensagemDesinscricao;
    }
}

// Inicializa ao carregar normalmente
window.addEventListener('DOMContentLoaded', inicializarEventosCartaoDoEventoInscrito);
// Permite inicialização após AJAX
window.inicializarEventosCartaoDoEventoInscrito = inicializarEventosCartaoDoEventoInscrito;

// Garante inicialização imediata após carregamento dinâmico do JS
setTimeout(inicializarEventosCartaoDoEventoInscrito, 0);
