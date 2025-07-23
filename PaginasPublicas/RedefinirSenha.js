function atribuirEventoRedefinirSenha() {
    var btnEnviar = document.getElementById('btn-enviar');
    var form = document.getElementById('form-redefinir');
    if (btnEnviar && form) {
        btnEnviar.onclick = function() {
            // Usa validação HTML5
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            var email = document.getElementById('email').value;
            var titulo = document.getElementById('titulo-redefinir');
            if (titulo) titulo.remove();
            if (form) form.remove();
            var mensagem = document.createElement('div');
            mensagem.style.textAlign = 'center';
            mensagem.style.color = '#fff';
            mensagem.style.fontSize = '1.2em';
            mensagem.style.marginBottom = '2em';
            mensagem.innerHTML = 'E-mail Enviado!<br><br>Verifique ' + email;
            var container = document.createElement('div');
            container.style.display = 'flex';
            container.style.justifyContent = 'center';
            var btnVoltar = document.createElement('button');
            btnVoltar.type = 'button';
            btnVoltar.className = 'botao-login';
            btnVoltar.textContent = 'Voltar';
            btnVoltar.onclick = function() { carregarPagina('login'); };
            container.appendChild(btnVoltar);
            var cartao = document.querySelector('.cartao-login');
            cartao.appendChild(mensagem);
            cartao.appendChild(container);
        };
    }
}
// Chama ao carregar a página normalmente
atribuirEventoRedefinirSenha();
