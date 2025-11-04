document.addEventListener("DOMContentLoaded", () => {
    const menu = document.querySelector(".Menu");
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
  
    // Mantém funcionalidade existente para ativar botões - EXCETO O BOTÃO DE NOTIFICAÇÕES
    const botoes = document.querySelectorAll(".conteudo button, .rodape");
    botoes.forEach(botao => {
      botao.addEventListener("click", () => {
        // Remove ativo de todos os botões, incluindo notificações
        document.querySelectorAll(".conteudo button, .rodape, .botao-notificacoes").forEach(btn => btn.classList.remove("ativo"));
        botao.classList.add("ativo");
      });
    });

    // Fallback: garante que os botões chamem a rota correta
    const btnEventosInscritos = document.querySelector('.botao-eventosInscritos');
    if (btnEventosInscritos && !btnEventosInscritos.dataset.boundRota) {
      btnEventosInscritos.addEventListener('click', (e) => {
        if (typeof globalThis.carregarPagina === 'function') {
          globalThis.carregarPagina('eventosInscritos');
        }
      });
      btnEventosInscritos.dataset.boundRota = '1';
    }
  
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
        inicio: ['inicio'],
        eventosInscritos: ['eventosInscritos'],
        meusEventos: ['meusEventos', 'eventoOrganizado', 'adicionarEvento'],
        certificados: ['certificados'],
        configuracoes: ['configuracoes', 'termos', 'redefinirSenha', 'emailRecuperacao', 'temaDoSite', 'manualDeUso', 'duvidasFrequentes', 'sobreNos'],
        faleConosco: ['faleconosco'],
        notificacoes: ['painelnotificacoes']
      };
  
      // Remove a classe 'ativo' de todos os botões, incluindo notificações
      document.querySelectorAll(".conteudo button, .rodape, .botao-notificacoes").forEach(btn => btn.classList.remove("ativo"));
  
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

