document.addEventListener("DOMContentLoaded", () => {
  const menu = document.querySelector(".Menu");
  const toggleButton = document.createElement("button");
  toggleButton.className = "menu-toggle";
  menu.appendChild(toggleButton);

  const mainContent = document.querySelector(".conteudo-principal");

  // Alterna o estado do menu ao clicar no botão
  toggleButton.addEventListener("click", () => {
    menu.classList.toggle("expanded");
    if (mainContent) mainContent.classList.toggle("shifted");
  });

  // Ativa/desativa botões ao clicar
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
    const paginasPerfil = ['perfil', 'editarPerfil'];
    const paginasInicio = ['inicio'];
    const paginasMeusEventos = ['meusEventos'];
    const paginasCertificados = ['certificados'];
    const paginasConfiguracoes = ['configuracoes'];
    const paginasFaleConosco = ['faleConosco'];

    // Ordem dos botões: 0-Perfil, 1-Início, 2-Meus eventos, 3-Certificados, 4-Configurações, último: Fale Conosco
    let idx = -1;
    if (paginasPerfil.includes(pagina)) idx = 0;
    else if (paginasInicio.includes(pagina)) idx = 1;
    else if (paginasMeusEventos.includes(pagina)) idx = 2;
    else if (paginasCertificados.includes(pagina)) idx = 3;
    else if (paginasConfiguracoes.includes(pagina)) idx = 4;
    else if (paginasFaleConosco.includes(pagina)) idx = botoes.length - 1;

    botoes.forEach(btn => btn.classList.remove("ativo"));
    if (idx >= 0 && botoes[idx]) botoes[idx].classList.add("ativo");
  }

  // Torna a função global para ser chamada externamente
  globalThis.setMenuAtivoPorPagina = setMenuAtivoPorPagina;
});
