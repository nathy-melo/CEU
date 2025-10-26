// Flag para evitar inicialização múltipla
let menuBloqueadoIniciado = false;
let eventosCaixaPretaConfigurados = false;

/**
 * Garante que o modal existe no DOM
 * Se não existir, cria dinamicamente
 */
function garantirModalNoDOM() {
  let modal = document.getElementById("modalCustom");
  
  if (!modal) {
    // Cria o modal dinamicamente
    modal = document.createElement("div");
    modal.id = "modalCustom";
    modal.className = "modal-personalizado";
    modal.innerHTML = `
      <div class="conteudo-modal-personalizado">
        <div class="cabecalho-modal-personalizado">Um anjo sussurrou no seu ouvido:</div>
        <div class="corpo-modal-personalizado">Crie seu perfil para desbloquear o menu!</div>
        <button class="botao botao-modal-personalizado" onclick="fecharModal()">OK</button>
      </div>
    `;
    
    // Adiciona o modal ao body
    document.body.appendChild(modal);
  }
  
  return modal;
}

/**
 * Configura a delegação de eventos para a caixa preta
 * Usa delegação no document para funcionar sempre, mesmo com AJAX
 */
function configurarEventoCaixaPreta() {
  if (eventosCaixaPretaConfigurados) return;
  
  // Usa delegação de eventos para a caixa preta (funciona mesmo com AJAX)
  // O evento é adicionado ao document, então funciona sempre, mesmo após AJAX
  document.addEventListener("click", function(e) {
    const caixaPreta = e.target.closest(".caixa-preta");
    if (caixaPreta) {
      e.preventDefault();
      e.stopPropagation();
      mostrarModal();
    }
  });
  
  eventosCaixaPretaConfigurados = true;
}

/**
 * Inicializa o menu bloqueado
 * Pode ser chamada manualmente a qualquer momento
 */
function inicializarMenuBloqueado() {
  const menu = document.querySelector(".Menu");
  if (!menu) return;

  // Cria o botão toggle se ainda não existe
  if (!menu.querySelector(".menu-toggle")) {
    const toggleButton = document.createElement("button");
    toggleButton.className = "menu-toggle";
    menu.appendChild(toggleButton);

    const mainContent = document.querySelector(".conteudo-principal");

    // Alterna o estado do menu ao clicar no botão
    toggleButton.addEventListener("click", () => {
      menu.classList.toggle("expanded");
      if (mainContent) {
        mainContent.classList.toggle("shifted");
      }
    });
  }

  // Garante que o modal existe
  garantirModalNoDOM();

  // Garante que os eventos da caixa preta estejam configurados
  configurarEventoCaixaPreta();

  menuBloqueadoIniciado = true;
}

/**
 * Ativa o botão do menu de acordo com a página aberta.
 * Para adicionar novas páginas que ativam um botão específico,
 * basta incluir o nome da página no array correspondente no mapeamento abaixo.
 * Exemplo: para que 'minhaNovaPagina' ative o botão de perfil, adicione em paginasPerfil.
 */
function setMenuAtivoPorPagina(pagina) {
  // Mapeamento: cada página é associada a um seletor de botão específico
  const mapeamentoBotoes = {
    'perfil': ['login', 'cadastroO', 'cadastroP', 'solicitarCodigo', 'redefinirSenha', 'termos'],
    'inicio': ['inicio', 'evento'],
    'faleConosco': ['faleConosco']
  };

  // Remove a classe 'ativo' de todos os botões
  const botoes = document.querySelectorAll(".Menu .conteudo button, .Menu .rodape");
  botoes.forEach(btn => btn.classList.remove("ativo"));

  // Verifica qual botão deve ser ativado
  for (const [seletor, paginas] of Object.entries(mapeamentoBotoes)) {
    if (paginas.includes(pagina)) {
      // Melhora o seletor para pegar também botões no rodapé
      const botao = document.querySelector(`.botao-${seletor}, .Menu .rodape.botao-${seletor}`);
      if (botao) {
        botao.classList.add("ativo");
      }
      break;
    }
  }
}

// Função para mostrar o modal personalizado
function mostrarModal() {
  // Garante que o modal existe antes de tentar mostrá-lo
  const modal = garantirModalNoDOM();
  
  if (modal) {
    modal.classList.add("mostrar");
  }
}

// Função para fechar o modal
function fecharModal() {
  const modal = document.getElementById("modalCustom");
  if (modal) {
    modal.classList.remove("mostrar");
  }
}

// Torna as funções globais
globalThis.inicializarMenuBloqueado = inicializarMenuBloqueado;
globalThis.setMenuAtivoPorPagina = setMenuAtivoPorPagina;
globalThis.mostrarModal = mostrarModal;
globalThis.fecharModal = fecharModal;

// Inicializa automaticamente quando o DOM estiver pronto
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', inicializarMenuBloqueado);
} else {
  // DOM já está pronto, inicializa imediatamente
  inicializarMenuBloqueado();
}