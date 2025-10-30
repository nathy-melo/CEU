function atribuirEventoRedefinirSenha() {
    var btnEnviar = document.getElementById('btn-enviar');
    var form = document.getElementById('form-redefinir');
    if (btnEnviar && form) {
        btnEnviar.onclick = async function() {
            // Usa validação HTML5
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            var email = document.getElementById('email').value;
            
            // Desabilitar botão durante o processamento
            btnEnviar.disabled = true;
            btnEnviar.textContent = 'Enviando...';
            
            try {
                // Enviar requisição para o servidor
                const response = await fetch('../PaginasGlobais/SolicitarRedefinicaoSenha.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email: email })
                });
                
                const data = await response.json();
                
                // Remover elementos do formulário
                var titulo = document.getElementById('titulo-redefinir');
                if (titulo) titulo.remove();
                if (form) form.remove();
                
                // Criar mensagem de sucesso
                var mensagem = document.createElement('div');
                mensagem.style.textAlign = 'center';
                mensagem.style.color = '#fff';
                mensagem.style.fontSize = '1.2em';
                mensagem.style.marginBottom = '2em';
                mensagem.style.lineHeight = '1.5';
                
                if (data.success) {
                    mensagem.innerHTML = '✅ Solicitação Enviada!<br><br>' +
                        'Sua solicitação de redefinição de senha foi registrada.<br>' +
                        'Um administrador irá processar sua solicitação em breve.<br><br>' +
                        '<small style="font-size: 0.85em; opacity: 0.9;">Email: ' + email + '</small>';
                } else {
                    mensagem.innerHTML = '⚠️ ' + (data.message || 'Erro ao processar solicitação') + '<br><br>' +
                        '<small style="font-size: 0.85em;">Tente novamente mais tarde.</small>';
                }
                
                // Criar botão voltar
                var container = document.createElement('div');
                container.style.display = 'flex';
                container.style.justifyContent = 'center';
                var btnVoltar = document.createElement('button');
                btnVoltar.type = 'button';
                btnVoltar.className = 'botao-login botao';
                btnVoltar.textContent = 'Voltar';
                btnVoltar.onclick = function() { carregarPagina('login'); };
                container.appendChild(btnVoltar);
                
                var cartao = document.querySelector('.cartao-login');
                cartao.appendChild(mensagem);
                cartao.appendChild(container);
                
            } catch (error) {
                console.error('Erro ao enviar solicitação:', error);
                
                // Reabilitar botão em caso de erro
                btnEnviar.disabled = false;
                btnEnviar.textContent = 'Enviar';
                
                // Mostrar mensagem de erro
                alert('❌ Erro ao enviar solicitação. Verifique sua conexão e tente novamente.');
            }
        };
    }
}
// Chama ao carregar a página normalmente
atribuirEventoRedefinirSenha();
