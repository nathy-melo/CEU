// Função para adicionar barras automaticamente em campos de data no formato dd/mm/aaaa
  // e pontos/traço no CPF
  function aplicarMascaraData(input, tipo) {
    // Remove event listeners antigos (se houver)
    input.removeEventListener('_mascaraInput', input._mascaraHandler || (()=>{}));
    // Define o novo handler
    input._mascaraHandler = function () {
      let value = input.value.replace(/\D/g, '');
      if (tipo === 'cpf') {
        value = value.slice(0, 11);
        if (value.length > 9) {
          value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
        } else if (value.length > 6) {
          value = value.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
        } else if (value.length > 3) {
          value = value.replace(/(\d{3})(\d{1,3})/, '$1.$2');
        }
      } else {
        value = value.slice(0, 8);
        if (value.length >= 5) {
          value = value.replace(/(\d{2})(\d{2})(\d{1,4})/, '$1/$2/$3');
        } else if (value.length >= 3) {
          value = value.replace(/(\d{2})(\d{1,2})/, '$1/$2');
        }
      }
      input.value = value;
    };
    // Adiciona o novo event listener
    input.addEventListener('input', input._mascaraHandler, false);
    // Marca o tipo para evitar múltiplos handlers
    input.addEventListener('_mascaraInput', input._mascaraHandler, false);
  }

  function inicializarMascaras() {
    const dataInput = document.getElementById('data-nascimento');
    if (dataInput) aplicarMascaraData(dataInput, 'data');
    const cpfInput = document.getElementById('cpf');
    if (cpfInput) aplicarMascaraData(cpfInput, 'cpf');
  }

  function mostrarMensagemSolicitacaoEnviada() {
    var mainContent = document.getElementById('main-content');
    if (!mainContent) return;
    mainContent.innerHTML = '';
    var container = document.createElement('div');
    container.style.display = 'flex';
    container.style.flexDirection = 'column';
    container.style.alignItems = 'center';
    container.style.justifyContent = 'center';
    container.style.background = '#4f6c8c';
    container.style.borderRadius = '2em';
    container.style.padding = '2em 2.5em';
    container.style.maxWidth = '600px';
    container.style.margin = '0 auto';
    var titulo = document.createElement('h2');
    titulo.textContent = 'Seu pedido foi enviado!';
    titulo.style.color = '#fff';
    titulo.style.fontSize = '2em';
    titulo.style.marginBottom = '0.5em';
    titulo.style.textAlign = 'center';
    var mensagem = document.createElement('div');
    mensagem.textContent = 'Fique atento à caixa de entrada do seu e-mail';
    mensagem.style.color = '#fff';
    mensagem.style.fontSize = '1.1em';
    mensagem.style.marginBottom = '2em';
    mensagem.style.textAlign = 'center';
    var btnVoltar = document.createElement('button');
    btnVoltar.type = 'button';
    btnVoltar.className = 'botao botao-voltar';
    btnVoltar.textContent = 'Voltar';
    btnVoltar.onclick = function() { carregarPagina('login'); };
    container.appendChild(titulo);
    container.appendChild(mensagem);
    container.appendChild(btnVoltar);
    mainContent.appendChild(container);
  }

  document.addEventListener('DOMContentLoaded', function () {
    inicializarMascaras();
  });

  // Se você recarregar o formulário via AJAX, chame inicializarMascaras() após inserir o HTML do formulário.
  // Exemplo:
  // document.getElementById('main-content').innerHTML = ...novoHTML...
  // inicializarMascaras();