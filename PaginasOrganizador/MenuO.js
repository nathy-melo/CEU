document.addEventListener("DOMContentLoaded", () => {
    const menu = document.querySelector(".Menu");
    const toggleButton = document.createElement("button");
    toggleButton.className = "menu-toggle";
    menu.appendChild(toggleButton);

    const mainContent = document.querySelector(".conteudo-principal");
  
    // Alterna o estado do menu ao clicar no botão
    toggleButton.addEventListener("click", () => {
      menu.classList.toggle("expanded");
mainContent.classList.toggle("shifted");
    });
  
    // Mantém funcionalidade existente para ativar botões
    const botoes = document.querySelectorAll(".conteudo button, .rodape");
    botoes.forEach(botao => {
      botao.addEventListener("click", () => {
        botoes.forEach(btn => btn.classList.remove("ativo"));
        botao.classList.add("ativo");
      });
    });
  
    /**
     * Ativa o botão do menu de acordo com a página aberta.
     * Para adicionar novas páginas que ativam um botão específico,
     * basta incluir o nome da página no array correspondente no mapeamento abaixo.
     * Exemplo: para que 'minhaNovaPagina' ative o botão de perfil, adicione em paginasPerfil.
     */
    function setMenuAtivoPorPagina(pagina) {
      // Mapeamento: cada array contém as páginas que ativam o respectivo botão
      const mapeamentoBotoes = {
        perfil: ['perfil', 'editarPerfil'],
        inicio: ['inicio', 'evento'],
        meusEventos: ['meusEventos', 'eventoOrganizado'],
        certificados: ['certificados'],
        configuracoes: ['configuracoes', 'termos', 'redefinirSenha', 'emailRecuperacao', 'temaDoSite', 'manualDeUso', 'duvidasFrequentes', 'sobreNos'],
        faleConosco: ['faleconosco']
      };
  
      // Remove a classe 'ativo' de todos os botões
      botoes.forEach(btn => btn.classList.remove("ativo"));
  
      // Verifica qual botão deve ser ativado
      for (const [seletor, paginas] of Object.entries(mapeamentoBotoes)) {
        if (paginas.includes(pagina)) {
          const botao = document.querySelector(`.botao-${seletor}`);
          if (botao) {
            botao.classList.add("ativo");
          }
          break;
        }
      }
    }
  
    // Torna a função global para ser chamada pelo Container.php
    globalThis.setMenuAtivoPorPagina = setMenuAtivoPorPagina;
  });

