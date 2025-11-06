// CartaoDoEventoOrganizando.js
(function () {
  'use strict';

  let modoEdicao = false;
  let listaImagensEvento = [];
  let indiceImagemAtual = 0;
  let dadosOriginaisEvento = {};
  let codigoEventoAtual = null;
  let ultimoFocoAntesModal = null;

  function elementoContem(parent, child) {
    if (!parent || !child) return false;
    return parent === child || parent.contains(child);
  }

  function carregarDadosEventoDoServidor(codigoEvento) {
    if (!codigoEvento) {
      alert('Código do evento não fornecido');
      return;
    }

    codigoEventoAtual = codigoEvento;

    fetch('GerenciadorEventos.php?action=detalhe&cod_evento=' + codigoEvento)
      .then(respostaServidor => respostaServidor.json())
      .then(dadosRecebidos => {
        if (dadosRecebidos.erro) {
          alert('Erro ao carregar evento: ' + dadosRecebidos.erro);
          history.back();
          return;
        }

        if (dadosRecebidos.sucesso && dadosRecebidos.evento) {
          preencherCamposComDadosEvento(dadosRecebidos.evento);
        }
      })
      .catch(erroRequisicao => {
        console.error('Erro ao carregar evento:', erroRequisicao);
        alert('Erro ao carregar dados do evento');
        history.back();
      });
  }

  function preencherCamposComDadosEvento(dadosEvento) {
    // Preenche os campos de visualização com os dados do evento
    document.getElementById('event-name').textContent = dadosEvento.nome;
    document.querySelector('.campo-organizador').textContent = dadosEvento.nome_organizador;
    document.getElementById('event-local').textContent = dadosEvento.lugar;
    document.getElementById('start-date').textContent = dadosEvento.data_inicio_formatada;
    document.getElementById('end-date').textContent = dadosEvento.data_fim_formatada;
    document.getElementById('start-time').textContent = dadosEvento.horario_inicio;
    document.getElementById('end-time').textContent = dadosEvento.horario_fim;
    
    // Preenche datas e horários de inscrição
    document.getElementById('inicio-inscricao').textContent = dadosEvento.data_inicio_inscricao || '-';
    document.getElementById('fim-inscricao').textContent = dadosEvento.data_fim_inscricao || '-';
    document.getElementById('horario-inicio-inscricao').textContent = dadosEvento.hora_inicio_inscricao || '-';
    document.getElementById('horario-fim-inscricao').textContent = dadosEvento.hora_fim_inscricao || '-';
    
    document.getElementById('audience').textContent = dadosEvento.publico_alvo;
    document.getElementById('category').textContent = dadosEvento.categoria;
    document.getElementById('modality').textContent = dadosEvento.modalidade;
    document.getElementById('certificate').textContent = dadosEvento.certificado;
    document.getElementById('description').textContent = dadosEvento.descricao;

    // Configura imagem do evento
    if (dadosEvento.imagem) {
      listaImagensEvento = ['../' + dadosEvento.imagem];
    } else {
      listaImagensEvento = ['../ImagensEventos/CEU-Logo.png'];
    }
    indiceImagemAtual = 0;
    document.getElementById('imagem-carrossel').src = listaImagensEvento[indiceImagemAtual];
    atualizarVisibilidadeSetas();

    // Salva cópia dos dados originais para restaurar ao cancelar
    dadosOriginaisEvento = {
      cod_evento: dadosEvento.cod_evento,
      nome: dadosEvento.nome,
      local: dadosEvento.lugar,
      dataInicio: dadosEvento.data_inicio_formatada,
      dataFim: dadosEvento.data_fim_formatada,
      dataInicioParaInput: dadosEvento.data_inicio_para_input,
      dataFimParaInput: dadosEvento.data_fim_para_input,
      horarioInicio: dadosEvento.horario_inicio,
      horarioFim: dadosEvento.horario_fim,
      dataInicioInscricao: dadosEvento.data_inicio_inscricao || '-',
      dataFimInscricao: dadosEvento.data_fim_inscricao || '-',
      dataInicioInscricaoParaInput: dadosEvento.data_inicio_inscricao_para_input || '',
      dataFimInscricaoParaInput: dadosEvento.data_fim_inscricao_para_input || '',
      horarioInicioInscricao: dadosEvento.hora_inicio_inscricao || '-',
      horarioFimInscricao: dadosEvento.hora_fim_inscricao || '-',
      publicoAlvo: dadosEvento.publico_alvo,
      categoria: dadosEvento.categoria,
      modalidade: dadosEvento.modalidade,
      certificado: dadosEvento.certificado,
      certificadoNumerico: dadosEvento.certificado_numerico,
      descricao: dadosEvento.descricao,
      imagens: [...listaImagensEvento]
    };
  }

  async function abrirModalColaboradores() {
    const modal = document.getElementById('modal-colaboradores');
    if (!modal) {
      alert('Interface de colaboradores não encontrada. Atualize a página.');
      return;
    }
    // Guardar foco atual para restaurar ao fechar
    ultimoFocoAntesModal = document.activeElement;

    // Exibir modal e ajustar acessibilidade
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');

    // Tenta focar o campo de entrada dentro do modal
    setTimeout(() => {
      const input = document.getElementById('input-identificador-colab');
      if (input && elementoContem(modal, input)) {
        try { input.focus(); } catch (e) { /* noop */ }
      }
    }, 0);

    await carregarListasColaboradoresESolicitacoes();
  }

  function fecharModalColaboradores() {
    const modal = document.getElementById('modal-colaboradores');
    if (modal) {
      // Se o foco estiver dentro do modal, remova-o antes de esconder/aria-hidden
      if (elementoContem(modal, document.activeElement)) {
        try { document.activeElement.blur(); } catch (e) { /* noop */ }
      }
      modal.setAttribute('aria-hidden', 'true');
      modal.style.display = 'none';

      // Restaurar foco para o elemento que abriu o modal, se ainda estiver no DOM
      if (ultimoFocoAntesModal && document.contains(ultimoFocoAntesModal)) {
        try { ultimoFocoAntesModal.focus(); } catch (e) { /* noop */ }
      }
      ultimoFocoAntesModal = null;
    }
  }

  async function carregarListasColaboradoresESolicitacoes() {
    try {
      const url = `GerenciadorColaboradores.php?cod_evento=${encodeURIComponent(codigoEventoAtual)}`;
      const resp = await fetch(url);
      const data = await resp.json();
      if (!data.sucesso) {
        alert('Erro ao carregar colaboradores: ' + (data.erro || 'desconhecido'));
        return;
      }
      renderizarColaboradores(data.colaboradores || [], data.eh_organizador || false, data.cpf_usuario || '');
      renderizarSolicitacoes(data.solicitacoes || []);
    } catch (e) {
      console.error('Falha ao carregar listas de colaboradores/solicitações', e);
      alert('Falha ao carregar colaboradores');
    }
  }

  function renderizarColaboradores(lista, ehOrganizador, cpfUsuario) {
    const container = document.getElementById('lista-colaboradores');
    if (!container) return;
    container.innerHTML = '';
    if (!lista.length) {
      container.innerHTML = '<div class="mensagem-vazio">Nenhum colaborador adicionado ainda.</div>';
      return;
    }
    lista.forEach(item => {
      const linha = document.createElement('div');
      linha.className = 'item-colab';

      const info = document.createElement('div');
      info.className = 'info-colab';

      const nome = document.createElement('div');
      nome.className = 'nome-colab';
      nome.textContent = item.nome;

      const email = document.createElement('div');
      email.className = 'email-colab';
      email.textContent = item.email;

      info.appendChild(nome);
      info.appendChild(email);

      const acoes = document.createElement('div');
      acoes.className = 'acoes';
      
      // Se é organizador, pode remover outros colaboradores
      if (ehOrganizador) {
        const btnRem = document.createElement('button');
        btnRem.className = 'btn-remover';
        btnRem.textContent = 'Remover';
        btnRem.onclick = () => removerColaboradorEvento(item.CPF);
        acoes.appendChild(btnRem);
      } 
      // Se é colaborador e é ele mesmo, pode sair
      else if (item.CPF === cpfUsuario) {
        const btnSair = document.createElement('button');
        btnSair.className = 'btn-sair';
        btnSair.textContent = 'Sair da Colaboração';
        btnSair.onclick = () => sairDaColaboracao();
        acoes.appendChild(btnSair);
      }

      linha.appendChild(info);
      linha.appendChild(acoes);
      container.appendChild(linha);
    });
  }

  function renderizarSolicitacoes(lista) {
    const container = document.getElementById('lista-solicitacoes');
    if (!container) return;
    container.innerHTML = '';
    if (!lista.length) {
      container.innerHTML = '<div class="mensagem-vazio">Nenhuma solicitação pendente.</div>';
      return;
    }
    lista.forEach(item => {
      const linha = document.createElement('div');
      linha.className = 'item-solic';

      const info = document.createElement('div');
      info.className = 'info-solic';

      const nome = document.createElement('div');
      nome.className = 'nome-solic';
      nome.textContent = item.nome;

      const email = document.createElement('div');
      email.className = 'email-solic';
      email.textContent = item.email;

      info.appendChild(nome);
      info.appendChild(email);

      const acoes = document.createElement('div');
      acoes.className = 'acoes';

      const btnOk = document.createElement('button');
      btnOk.className = 'btn-aprovar';
      btnOk.textContent = 'Aprovar';
      btnOk.onclick = () => atualizarSolicitacao(item.id, 'aprovar');

      const btnNo = document.createElement('button');
      btnNo.className = 'btn-recusar';
      btnNo.textContent = 'Recusar';
      btnNo.onclick = () => atualizarSolicitacao(item.id, 'recusar');

      acoes.appendChild(btnOk);
      acoes.appendChild(btnNo);

      linha.appendChild(info);
      linha.appendChild(acoes);
      container.appendChild(linha);
    });
  }

  async function adicionarColaboradorEvento() {
    const inp = document.getElementById('input-identificador-colab');
    if (!inp) return;
    const identificador = (inp.value || '').trim();
    if (!identificador) {
      alert('Informe o CPF (11 dígitos) ou Email do usuário');
      return;
    }
    try {
      const form = new FormData();
      form.append('action', 'adicionar');
      form.append('cod_evento', String(codigoEventoAtual));
      form.append('identificador', identificador);
      form.append('papel', 'colaborador'); // Sempre colaborador
      const resp = await fetch('GerenciadorColaboradores.php', { method: 'POST', body: form });
      const data = await resp.json();
      if (!data.sucesso) {
        alert('Erro ao adicionar colaborador: ' + (data.erro || 'desconhecido'));
        return;
      }
      inp.value = '';
      await carregarListasColaboradoresESolicitacoes();
    } catch (e) {
      console.error('Falha ao adicionar colaborador', e);
      alert('Falha ao adicionar colaborador');
    }
  }

  async function removerColaboradorEvento(cpf) {
    if (!confirm('Remover este colaborador do evento?')) return;
    try {
      const form = new FormData();
      form.append('action', 'remover');
      form.append('cod_evento', String(codigoEventoAtual));
      form.append('cpf', String(cpf));
      const resp = await fetch('GerenciadorColaboradores.php', { method: 'POST', body: form });
      const data = await resp.json();
      if (!data.sucesso) {
        alert('Erro ao remover colaborador: ' + (data.erro || 'desconhecido'));
        return;
      }
      await carregarListasColaboradoresESolicitacoes();
    } catch (e) {
      console.error('Falha ao remover colaborador', e);
      alert('Falha ao remover colaborador');
    }
  }

  async function sairDaColaboracao() {
    if (!confirm('Deseja sair da colaboração deste evento?')) return;
    try {
      const form = new FormData();
      form.append('action', 'sair');
      form.append('cod_evento', String(codigoEventoAtual));
      const resp = await fetch('GerenciadorColaboradores.php', { method: 'POST', body: form });
      const data = await resp.json();
      if (!data.sucesso) {
        alert('Erro ao sair da colaboração: ' + (data.erro || 'desconhecido'));
        return;
      }
      alert('Você saiu da colaboração do evento');
      fecharModalColaboradores();
      // Volta para a página de eventos
      if (typeof carregarPagina === 'function') {
        carregarPagina('meusEventos');
      } else {
        window.location.href = 'ContainerOrganizador.php?pagina=meusEventos';
      }
    } catch (e) {
      console.error('Falha ao sair da colaboração', e);
      alert('Falha ao sair da colaboração');
    }
  }

  async function atualizarSolicitacao(id, acao) {
    try {
      const form = new FormData();
      form.append('action', acao); // 'aprovar' ou 'recusar'
      form.append('id', String(id));
      const resp = await fetch('GerenciadorColaboradores.php', { method: 'POST', body: form });
      const data = await resp.json();
      if (!data.sucesso) {
        alert('Erro ao atualizar solicitação: ' + (data.erro || 'desconhecido'));
        return;
      }
      await carregarListasColaboradoresESolicitacoes();
    } catch (e) {
      console.error('Falha ao atualizar solicitação', e);
      alert('Falha ao atualizar solicitação');
    }
  }

  function irParaParticipantes() {
    if (!codigoEventoAtual) {
      console.error('Código do evento não encontrado');
      alert('Erro: Código do evento não disponível. Recarregue a página.');
      return;
    }
    console.log('Navegar para página de participantes do evento:', codigoEventoAtual);
    if (typeof carregarPagina === 'function') {
      carregarPagina('listaParticipantes', codigoEventoAtual);
    }
  }

  function editarEvento() {
    if (modoEdicao) return;
    modoEdicao = true;

    console.log('=== EDITANDO EVENTO ===');

    try {
      // Salvar dados originais
      dadosOriginaisEvento = {
        nome: document.getElementById('event-name').textContent,
        local: document.getElementById('event-local').textContent,
        dataInicio: document.getElementById('start-date').textContent,
        dataFim: document.getElementById('end-date').textContent,
        horarioInicio: document.getElementById('start-time').textContent,
        horarioFim: document.getElementById('end-time').textContent,
        publicoAlvo: document.getElementById('audience').textContent,
        categoria: document.getElementById('category').textContent,
        modalidade: document.getElementById('modality').textContent,
        certificado: document.getElementById('certificate').textContent,
        descricao: document.getElementById('description').textContent,
        imagens: [...listaImagensEvento]
      };

      // PRIMEIRO: Trocar os botões
      trocarParaBotoesEdicao();

      // DEPOIS: Alterar os campos
      document.querySelectorAll('.caixa-valor:not(.caixa-descricao)').forEach(el => {
        if (el) el.style.display = 'none';
      });

      const descriptionEl = document.getElementById('description');
      if (descriptionEl) descriptionEl.style.display = 'none';

      document.querySelectorAll('.campo-input, .campo-select, .campo-textarea').forEach(el => {
        if (el) el.style.display = 'flex';
      });

      // Preencher inputs com valores atuais
      const inputNome = document.getElementById('input-nome');
      const inputLocal = document.getElementById('input-local');
      const inputDataInicio = document.getElementById('input-data-inicio');
      const inputDataFim = document.getElementById('input-data-fim');
      const inputHorarioInicio = document.getElementById('input-horario-inicio');
      const inputHorarioFim = document.getElementById('input-horario-fim');
      const inputDataInicioInscricao = document.getElementById('input-data-inicio-inscricao');
      const inputDataFimInscricao = document.getElementById('input-data-fim-inscricao');
      const inputHorarioInicioInscricao = document.getElementById('input-horario-inicio-inscricao');
      const inputHorarioFimInscricao = document.getElementById('input-horario-fim-inscricao');
      const inputPublicoAlvo = document.getElementById('input-publico-alvo');
      const inputCategoria = document.getElementById('input-categoria');
      const inputModalidade = document.getElementById('input-modalidade');
      const inputCertificado = document.getElementById('input-certificado');
      const inputDescricao = document.getElementById('input-descricao');

      if (inputNome) inputNome.value = dadosOriginaisEvento.nome;
      if (inputLocal) inputLocal.value = dadosOriginaisEvento.local;

      // Usar datas no formato yyyy-mm-dd para os inputs do evento
      if (inputDataInicio) inputDataInicio.value = dadosOriginaisEvento.dataInicioParaInput;
      if (inputDataFim) inputDataFim.value = dadosOriginaisEvento.dataFimParaInput;

      if (inputHorarioInicio) inputHorarioInicio.value = dadosOriginaisEvento.horarioInicio;
      if (inputHorarioFim) inputHorarioFim.value = dadosOriginaisEvento.horarioFim;
      
      // Preencher datas e horários de inscrição
      if (inputDataInicioInscricao) inputDataInicioInscricao.value = dadosOriginaisEvento.dataInicioInscricaoParaInput || '';
      if (inputDataFimInscricao) inputDataFimInscricao.value = dadosOriginaisEvento.dataFimInscricaoParaInput || '';
      if (inputHorarioInicioInscricao) inputHorarioInicioInscricao.value = dadosOriginaisEvento.horarioInicioInscricao || '';
      if (inputHorarioFimInscricao) inputHorarioFimInscricao.value = dadosOriginaisEvento.horarioFimInscricao || '';
      
      if (inputPublicoAlvo) inputPublicoAlvo.value = dadosOriginaisEvento.publicoAlvo;
      if (inputCategoria) inputCategoria.value = dadosOriginaisEvento.categoria;
      if (inputModalidade) inputModalidade.value = dadosOriginaisEvento.modalidade;
      if (inputCertificado) inputCertificado.value = dadosOriginaisEvento.certificadoNumerico;
      if (inputDescricao) inputDescricao.value = dadosOriginaisEvento.descricao;

      // Habilitar edição de imagem
      const campoImagem = document.getElementById('campo-imagem');
      const btnRemoverImagem = document.getElementById('btn-remover-imagem');
      const btnAdicionarMais = document.getElementById('btn-adicionar-mais');

      if (campoImagem) {
        campoImagem.onclick = function () {
          const inputImagem = document.getElementById('input-imagem');
          if (inputImagem) inputImagem.click();
        };
      }

      if (btnRemoverImagem) btnRemoverImagem.style.display = 'flex';
      if (btnAdicionarMais) btnAdicionarMais.style.display = 'flex';

      console.log('=== MODO EDIÇÃO ATIVO ===');
    } catch (error) {
      console.error('Erro ao editar evento:', error);
      console.error('Stack trace:', error.stack);
      alert('Erro ao ativar modo de edição: ' + error.message);
      modoEdicao = false;
    }
  }

  function trocarParaBotoesEdicao() {
    console.log('Trocando para botões de edição...');

    const btnVoltar = document.getElementById('btn-voltar');
    const btnParticipantes = document.getElementById('btn-participantes');
    const btnEditar = document.getElementById('btn-editar');

    if (!btnVoltar || !btnParticipantes || !btnEditar) {
      console.error('Botões não encontrados!');
      return;
    }

    // Botão Cancelar
    btnVoltar.textContent = 'Cancelar';
    btnVoltar.className = 'botao-cancelar';
    btnVoltar.onclick = cancelarEdicao;

    // Botão Excluir
    btnParticipantes.textContent = 'Excluir Evento';
    btnParticipantes.className = 'botao-excluir';
    btnParticipantes.onclick = excluirEvento;

    // Botão Salvar
    btnEditar.textContent = 'Salvar';
    btnEditar.className = 'botao-salvar';
    btnEditar.onclick = salvarEvento;

    console.log('Botões trocados:', {
      voltar: btnVoltar.textContent,
      participantes: btnParticipantes.textContent,
      editar: btnEditar.textContent
    });
  }

  function trocarParaBotoesVisualizacao() {
    console.log('=== INICIANDO TROCA PARA VISUALIZAÇÃO ===');

    const btnVoltar = document.getElementById('btn-voltar');
    const btnParticipantes = document.getElementById('btn-participantes');
    const btnEditar = document.getElementById('btn-editar');

    console.log('Botões encontrados:', {
      btnVoltar: btnVoltar ? 'SIM' : 'NÃO',
      btnParticipantes: btnParticipantes ? 'SIM' : 'NÃO',
      btnEditar: btnEditar ? 'SIM' : 'NÃO'
    });

    if (!btnVoltar || !btnParticipantes || !btnEditar) {
      console.error('✗ Botões não encontrados ao restaurar!');
      console.error('btnVoltar:', btnVoltar);
      console.error('btnParticipantes:', btnParticipantes);
      console.error('btnEditar:', btnEditar);
      return;
    }

    console.log('Texto atual dos botões ANTES:', {
      voltar: btnVoltar.textContent,
      participantes: btnParticipantes.textContent,
      editar: btnEditar.textContent
    });

    // Botão Voltar
    btnVoltar.textContent = 'Voltar';
    btnVoltar.className = 'botao-voltar';
    btnVoltar.onclick = function () {
      console.log('Botão Voltar clicado - voltando para Meus Eventos');
      if (typeof carregarPagina === 'function') {
        carregarPagina('meusEventos');
      } else {
        window.location.href = 'ContainerOrganizador.php?pagina=meusEventos';
      }
    };

    // Botão Participantes
    btnParticipantes.textContent = 'Participantes';
    btnParticipantes.className = 'botao-participantes';
    btnParticipantes.onclick = function () {
      console.log('Botão Participantes clicado');
      irParaParticipantes();
    };

    // Botão Editar
    btnEditar.textContent = 'Editar';
    btnEditar.className = 'botao-editar';
    btnEditar.onclick = function () {
      console.log('Botão Editar clicado');
      editarEvento();
    };

    console.log('Texto atual dos botões DEPOIS:', {
      voltar: btnVoltar.textContent,
      participantes: btnParticipantes.textContent,
      editar: btnEditar.textContent
    });

    console.log('✓ Botões restaurados com sucesso');
    console.log('=== FIM DA TROCA PARA VISUALIZAÇÃO ===');
  }

  function cancelarEdicao() {
    if (!modoEdicao) return;
    modoEdicao = false;

    console.log('=== CANCELANDO EDIÇÃO ===');

    try {
      // Restaurar dados originais
      const eventName = document.getElementById('event-name');
      const eventLocal = document.getElementById('event-local');
      const startDate = document.getElementById('start-date');
      const endDate = document.getElementById('end-date');
      const startTime = document.getElementById('start-time');
      const endTime = document.getElementById('end-time');
      const inicioInscricao = document.getElementById('inicio-inscricao');
      const fimInscricao = document.getElementById('fim-inscricao');
      const horarioInicioInscricao = document.getElementById('horario-inicio-inscricao');
      const horarioFimInscricao = document.getElementById('horario-fim-inscricao');
      const audience = document.getElementById('audience');
      const category = document.getElementById('category');
      const modality = document.getElementById('modality');
      const certificate = document.getElementById('certificate');
      const description = document.getElementById('description');
      const imagemCarrossel = document.getElementById('imagem-carrossel');

      if (eventName) eventName.textContent = dadosOriginaisEvento.nome;
      if (eventLocal) eventLocal.textContent = dadosOriginaisEvento.local;
      if (startDate) startDate.textContent = dadosOriginaisEvento.dataInicio;
      if (endDate) endDate.textContent = dadosOriginaisEvento.dataFim;
      if (startTime) startTime.textContent = dadosOriginaisEvento.horarioInicio;
      if (endTime) endTime.textContent = dadosOriginaisEvento.horarioFim;
      if (inicioInscricao) inicioInscricao.textContent = dadosOriginaisEvento.dataInicioInscricao;
      if (fimInscricao) fimInscricao.textContent = dadosOriginaisEvento.dataFimInscricao;
      if (horarioInicioInscricao) horarioInicioInscricao.textContent = dadosOriginaisEvento.horarioInicioInscricao;
      if (horarioFimInscricao) horarioFimInscricao.textContent = dadosOriginaisEvento.horarioFimInscricao;
      if (audience) audience.textContent = dadosOriginaisEvento.publicoAlvo;
      if (category) category.textContent = dadosOriginaisEvento.categoria;
      if (modality) modality.textContent = dadosOriginaisEvento.modalidade;
      if (certificate) certificate.textContent = dadosOriginaisEvento.certificado;
      if (description) description.textContent = dadosOriginaisEvento.descricao;

      listaImagensEvento = [...dadosOriginaisEvento.imagens];
      indiceImagemAtual = 0;
      if (imagemCarrossel) imagemCarrossel.src = listaImagensEvento[indiceImagemAtual];

      // Mostrar caixas de valor e esconder inputs
      document.querySelectorAll('.caixa-valor').forEach(el => {
        if (el) el.style.display = 'flex';
      });

      document.querySelectorAll('.campo-input, .campo-select, .campo-textarea').forEach(el => {
        if (el) el.style.display = 'none';
      });

      // Desabilitar edição de imagem
      const campoImagem = document.getElementById('campo-imagem');
      const btnRemoverImagem = document.getElementById('btn-remover-imagem');
      const btnAdicionarMais = document.getElementById('btn-adicionar-mais');

      if (campoImagem) campoImagem.onclick = null;
      if (btnRemoverImagem) btnRemoverImagem.style.display = 'none';
      if (btnAdicionarMais) btnAdicionarMais.style.display = 'none';

      // Restaurar botões
      trocarParaBotoesVisualizacao();

      console.log('=== EDIÇÃO CANCELADA ===');
    } catch (error) {
      console.error('Erro ao cancelar edição:', error);
      console.error('Stack trace:', error.stack);
    }
  }

  function salvarEvento() {
    if (!modoEdicao) return;

    console.log('=== SALVANDO EVENTO ===');

    try {
      const inputNome = document.getElementById('input-nome');
      const inputLocal = document.getElementById('input-local');
      const inputDataInicio = document.getElementById('input-data-inicio');
      const inputDataFim = document.getElementById('input-data-fim');
      const inputHorarioInicio = document.getElementById('input-horario-inicio');
      const inputHorarioFim = document.getElementById('input-horario-fim');
      const inputDataInicioInscricao = document.getElementById('input-data-inicio-inscricao');
      const inputDataFimInscricao = document.getElementById('input-data-fim-inscricao');
      const inputHorarioInicioInscricao = document.getElementById('input-horario-inicio-inscricao');
      const inputHorarioFimInscricao = document.getElementById('input-horario-fim-inscricao');
      const inputPublicoAlvo = document.getElementById('input-publico-alvo');
      const inputCategoria = document.getElementById('input-categoria');
      const inputModalidade = document.getElementById('input-modalidade');
      const inputCertificado = document.getElementById('input-certificado');
      const inputDescricao = document.getElementById('input-descricao');

      // Prepara FormData
      const formData = new FormData();
      formData.append('cod_evento', codigoEventoAtual);
      formData.append('nome', inputNome.value);
      formData.append('local', inputLocal.value);
      formData.append('data_inicio', inputDataInicio.value);
      formData.append('data_fim', inputDataFim.value);
      formData.append('horario_inicio', inputHorarioInicio.value);
      formData.append('horario_fim', inputHorarioFim.value);
      formData.append('data_inicio_inscricao', inputDataInicioInscricao.value || '');
      formData.append('data_fim_inscricao', inputDataFimInscricao.value || '');
      formData.append('horario_inicio_inscricao', inputHorarioInicioInscricao.value || '');
      formData.append('horario_fim_inscricao', inputHorarioFimInscricao.value || '');
      formData.append('publico_alvo', inputPublicoAlvo.value);
      formData.append('categoria', inputCategoria.value);
      formData.append('modalidade', inputModalidade.value);
      formData.append('certificado', inputCertificado.value);
      formData.append('descricao', inputDescricao.value);

      // Adiciona imagens se houver novas
      const inputImagem = document.getElementById('input-imagem');
      if (inputImagem.files.length > 0) {
        for (let i = 0; i < inputImagem.files.length; i++) {
          formData.append('imagens_evento[]', inputImagem.files[i]);
        }
      }

      // Envia para o servidor
      fetch('AtualizarEvento.php', {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          if (data.sucesso) {
            alert(data.mensagem || 'Evento atualizado com sucesso!');

            // Atualiza valores exibidos
            const eventName = document.getElementById('event-name');
            const eventLocal = document.getElementById('event-local');
            const startDate = document.getElementById('start-date');
            const endDate = document.getElementById('end-date');
            const startTime = document.getElementById('start-time');
            const endTime = document.getElementById('end-time');
            const audience = document.getElementById('audience');
            const category = document.getElementById('category');
            const modality = document.getElementById('modality');
            const certificate = document.getElementById('certificate');
            const description = document.getElementById('description');

            if (eventName) eventName.textContent = inputNome.value;
            if (eventLocal) eventLocal.textContent = inputLocal.value;

            // Converter datas de yyyy-mm-dd para dd/mm/yy
            if (startDate && inputDataInicio.value) {
              const [anoI, mesI, diaI] = inputDataInicio.value.split('-');
              startDate.textContent = `${diaI}/${mesI}/${anoI.slice(-2)}`;
            }

            if (endDate && inputDataFim.value) {
              const [anoF, mesF, diaF] = inputDataFim.value.split('-');
              endDate.textContent = `${diaF}/${mesF}/${anoF.slice(-2)}`;
            }

            if (startTime) startTime.textContent = inputHorarioInicio.value;
            if (endTime) endTime.textContent = inputHorarioFim.value;
            if (audience) audience.textContent = inputPublicoAlvo.value;
            if (category) category.textContent = inputCategoria.value;
            if (modality) modality.textContent = inputModalidade.value;
            if (certificate) certificate.textContent = inputCertificado.value;
            if (description) description.textContent = inputDescricao.value;

            modoEdicao = false;

            // Mostrar caixas de valor e esconder inputs
            document.querySelectorAll('.caixa-valor').forEach(el => {
              if (el) el.style.display = 'flex';
            });

            document.querySelectorAll('.campo-input, .campo-select, .campo-textarea').forEach(el => {
              if (el) el.style.display = 'none';
            });

            // Desabilitar edição de imagem
            const campoImagem = document.getElementById('campo-imagem');
            const btnRemoverImagem = document.getElementById('btn-remover-imagem');
            const btnAdicionarMais = document.getElementById('btn-adicionar-mais');

            if (campoImagem) campoImagem.onclick = null;
            if (btnRemoverImagem) btnRemoverImagem.style.display = 'none';
            if (btnAdicionarMais) btnAdicionarMais.style.display = 'none';

            // Restaurar botões
            trocarParaBotoesVisualizacao();

          } else {
            alert('Erro ao atualizar evento: ' + (data.erro || 'Erro desconhecido'));
          }
        })
        .catch(error => {
          console.error('Erro ao salvar evento:', error);
          alert('Erro ao salvar evento. Por favor, tente novamente.');
        });

    } catch (error) {
      console.error('Erro ao salvar evento:', error);
      console.error('Stack trace:', error.stack);
      alert('Erro ao salvar evento: ' + error.message);
    }
  }

  function excluirEvento() {
    if (!confirm('Tem certeza que deseja excluir este evento? Esta ação não pode ser desfeita.')) {
      return;
    }

    const formData = new FormData();
    formData.append('cod_evento', codigoEventoAtual);

    fetch('ExcluirEvento.php', {
      method: 'POST',
      body: formData
    })
      .then(response => response.json())
      .then(data => {
        if (data.sucesso) {
          alert(data.mensagem || 'Evento excluído com sucesso!');
          // Redireciona para a página de meus eventos
          if (typeof carregarPagina === 'function') {
            carregarPagina('meusEventos');
          } else {
            window.location.href = 'ContainerOrganizador.php?pagina=meusEventos';
          }
        } else {
          alert('Erro ao excluir evento: ' + (data.erro || 'Erro desconhecido'));
        }
      })
      .catch(error => {
        console.error('Erro ao excluir evento:', error);
        alert('Erro ao excluir evento. Por favor, tente novamente.');
      });
  }

  function adicionarImagens(event) {
    const files = Array.from(event.target.files);
    const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB em bytes

    files.forEach(file => {
      // Validar tamanho do arquivo
      if (file.size > MAX_FILE_SIZE) {
        alert(`Erro: A imagem "${file.name}" excede o limite de 10MB.\nTamanho do arquivo: ${(file.size / 1024 / 1024).toFixed(2)}MB`);
        return; // Pula este arquivo
      }

      // Validar tipo de arquivo
      const tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
      if (!tiposPermitidos.includes(file.type)) {
        alert(`Erro: O arquivo "${file.name}" não é uma imagem válida.\nFormatos aceitos: JPG, JPEG, PNG, GIF, WEBP`);
        return;
      }

      const reader = new FileReader();
      reader.onload = function (e) {
        listaImagensEvento.push(e.target.result);
        if (listaImagensEvento.length === 1) {
          indiceImagemAtual = 0;
          mostrarCarrossel();
        }
        atualizarVisibilidadeSetas();
      };
      reader.readAsDataURL(file);
    });

    // Limpa o input para permitir selecionar o mesmo arquivo novamente se necessário
    event.target.value = '';
  }

  function mostrarCarrossel() {
    document.getElementById('placeholder-imagem').style.display = 'none';
    document.getElementById('carrossel-imagens').style.display = 'flex';
    document.getElementById('imagem-carrossel').src = listaImagensEvento[indiceImagemAtual];
  }

  function esconderCarrossel() {
    document.getElementById('placeholder-imagem').style.display = 'flex';
    document.getElementById('carrossel-imagens').style.display = 'none';
  }

  function removerImagemAtual() {
    if (listaImagensEvento.length > 0) {
      listaImagensEvento.splice(indiceImagemAtual, 1);
      if (listaImagensEvento.length === 0) {
        esconderCarrossel();
        document.getElementById('input-imagem').value = '';
      } else {
        if (indiceImagemAtual >= listaImagensEvento.length) {
          indiceImagemAtual = listaImagensEvento.length - 1;
        }
        document.getElementById('imagem-carrossel').src = listaImagensEvento[indiceImagemAtual];
        atualizarVisibilidadeSetas();
      }
    }
  }

  function atualizarVisibilidadeSetas() {
    const multiple = listaImagensEvento.length > 1;
    const setDisplay = (sel) => {
      document.querySelectorAll(sel).forEach(el => {
        el.style.display = multiple ? '' : 'none';
      });
    };
    setDisplay('.carrossel-anterior');
    setDisplay('.carrossel-proxima');
    setDisplay('.modal-imagem-btn-anterior');
    setDisplay('.modal-imagem-btn-proxima');
  }

  function mudarImagem(direcao) {
    if (listaImagensEvento.length > 0) {
      indiceImagemAtual = (indiceImagemAtual + direcao + listaImagensEvento.length) % listaImagensEvento.length;
      document.getElementById('imagem-carrossel').src = listaImagensEvento[indiceImagemAtual];
    }
  }

  function mudarImagemModal(direcao) {
    if (listaImagensEvento.length > 0) {
      indiceImagemAtual = (indiceImagemAtual + direcao + listaImagensEvento.length) % listaImagensEvento.length;
      document.getElementById('imagem-ampliada').src = listaImagensEvento[indiceImagemAtual];
    }
  }

  function fecharModalImagem() {
    document.getElementById('modal-imagem').style.display = 'none';
  }

  function inicializarCartaoEventoOrganizando() {
    console.log('📋 Inicializando Cartão do Evento Organizando...');

    const btnVoltar = document.getElementById('btn-voltar');
    const btnParticipantes = document.getElementById('btn-participantes');
    const btnEditar = document.getElementById('btn-editar');
    const imagemCarrossel = document.getElementById('imagem-carrossel');
    const inputImagem = document.getElementById('input-imagem');

    if (btnVoltar && btnParticipantes && btnEditar) {
      btnVoltar.onclick = function () {
        if (typeof carregarPagina === 'function') {
          carregarPagina('meusEventos');
        } else {
          window.location.href = 'ContainerOrganizador.php?pagina=meusEventos';
        }
      };
      btnParticipantes.onclick = irParaParticipantes;
      btnEditar.onclick = editarEvento;
      console.log('✓ Botões inicializados');
    } else {
      console.error('✗ Erro: Botões não encontrados');
    }

    if (imagemCarrossel) {
      imagemCarrossel.onclick = function (e) {
        e.stopPropagation();
        if (listaImagensEvento.length > 0) {
          document.getElementById('imagem-ampliada').src = listaImagensEvento[indiceImagemAtual];
          document.getElementById('modal-imagem').style.display = 'flex';
        }
      };
      console.log('✓ Imagem do carrossel inicializada');
    }

    if (inputImagem) {
      inputImagem.onchange = adicionarImagens;
      console.log('✓ Input de imagem inicializado');
    }

    // Expor funções globais necessárias
    window.abrirModalColaboradores = abrirModalColaboradores;
    window.mudarImagem = mudarImagem;
    window.mudarImagemModal = mudarImagemModal;
    window.fecharModalImagem = fecharModalImagem;
    window.removerImagemAtual = removerImagemAtual;
    window.carregarDadosEvento = carregarDadosEventoDoServidor;
    // Expor helpers colaboradores
    window.fecharModalColaboradores = fecharModalColaboradores;
    window.adicionarColaboradorEvento = adicionarColaboradorEvento;

    // Inicialização das setas
    atualizarVisibilidadeSetas();

    // Carrega dados do evento se o código foi passado
    const urlParams = new URLSearchParams(window.location.search);
    const codEvento = urlParams.get('cod_evento');

    if (codEvento) {
      carregarDadosEventoDoServidor(codEvento);
    } else {
      console.warn('⚠ Código do evento não fornecido na URL');
    }

    console.log('✓ CartaoDoEventoOrganizando pronto!');
  }

  // Inicializar quando o DOM estiver pronto
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarCartaoEventoOrganizando);
  } else {
    inicializarCartaoEventoOrganizando();
  }
})();
