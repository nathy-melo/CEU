// CartaoDoEventoOrganizando.js
(function () {
  'use strict';

  let modoEdicao = false;
  let listaImagensEvento = [];
  let indiceImagemAtual = 0;
  let dadosOriginaisEvento = {};
  let codigoEventoAtual = null;
  let ultimoFocoAntesModal = null;

  // Função para bloquear scroll
  function bloquearScroll() {
    document.body.classList.add('modal-aberto');
    document.addEventListener('wheel', prevenirScroll, { passive: false });
    document.addEventListener('touchmove', prevenirScroll, { passive: false });
    document.addEventListener('keydown', prevenirScrollTeclado, false);
  }

  // Função para desbloquear scroll
  function desbloquearScroll() {
    document.body.classList.remove('modal-aberto');
    document.removeEventListener('wheel', prevenirScroll);
    document.removeEventListener('touchmove', prevenirScroll);
    document.removeEventListener('keydown', prevenirScrollTeclado);
  }

  // Previne scroll com mouse wheel e touchmove
  function prevenirScroll(e) {
    if (document.body.classList.contains('modal-aberto')) {
      e.preventDefault();
    }
  }

  // Previne scroll com setas do teclado e Page Up/Down
  function prevenirScrollTeclado(e) {
    if (!document.body.classList.contains('modal-aberto')) return;

    const teclas = [32, 33, 34, 35, 36, 37, 38, 39, 40];
    if (teclas.includes(e.keyCode)) {
      e.preventDefault();
    }
  }

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

    // Configura imagem do evento - NÃO define imagem padrão ainda para evitar flash
    if (dadosEvento.imagem) {
      listaImagensEvento = ['../' + dadosEvento.imagem];
    } else {
      listaImagensEvento = [];
    }
    
    // Carrega todas as imagens do evento da tabela imagens_evento
    console.log('Carregando imagens do evento:', dadosEvento.cod_evento);
    fetch('GerenciadorEventos.php?action=imagens&cod_evento=' + dadosEvento.cod_evento)
      .then(res => res.json())
      .then(dataImgs => {
        console.log('Resposta de imagens:', dataImgs);
        if (dataImgs.sucesso && dataImgs.imagens && dataImgs.imagens.length > 0) {
          // Se há imagens na tabela imagens_evento, usa elas
          listaImagensEvento = dataImgs.imagens.map(img => '../' + img.caminho);
          console.log('Imagens carregadas da tabela imagens_evento:', listaImagensEvento);
        } else if (listaImagensEvento.length === 0) {
          // Se não há imagens, usa padrão
          listaImagensEvento = ['../ImagensEventos/CEU-ImagemEvento.png'];
          console.log('Usando imagem padrão');
        } else {
          console.log('Usando imagem principal do evento');
        }
        indiceImagemAtual = 0;
        document.getElementById('imagem-carrossel').src = listaImagensEvento[indiceImagemAtual];
        atualizarVisibilidadeSetas();
        
        // Inicializa dadosOriginaisEvento APÓS carregar todas as imagens
        inicializarDadosOriginais(dadosEvento);
      })
      .catch(err => {
        console.error('Erro ao carregar imagens adicionais:', err);
        // Se houve erro e não há imagens, usa padrão
        if (listaImagensEvento.length === 0) {
          listaImagensEvento = ['../ImagensEventos/CEU-ImagemEvento.png'];
        }
        indiceImagemAtual = 0;
        document.getElementById('imagem-carrossel').src = listaImagensEvento[indiceImagemAtual];
        atualizarVisibilidadeSetas();
        
        // Inicializa dadosOriginaisEvento mesmo com erro
        inicializarDadosOriginais(dadosEvento);
      });
  }

  function inicializarDadosOriginais(dadosEvento) {
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

    // Preenche também os campos de input (para quando a página abrir já em modo edição)
    preencherInputsEdicao();
  }

  function preencherInputsEdicao() {
    // Verifica se dadosOriginaisEvento existe
    if (!dadosOriginaisEvento || Object.keys(dadosOriginaisEvento).length === 0) {
      console.warn('Dados do evento não disponíveis para preenchimento');
      return;
    }

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

    if (inputNome) inputNome.value = dadosOriginaisEvento.nome || '';
    if (inputLocal) inputLocal.value = dadosOriginaisEvento.local || '';

    // Usar datas no formato yyyy-mm-dd para os inputs do evento
    if (inputDataInicio) inputDataInicio.value = dadosOriginaisEvento.dataInicioParaInput || '';
    if (inputDataFim) inputDataFim.value = dadosOriginaisEvento.dataFimParaInput || '';

    if (inputHorarioInicio) inputHorarioInicio.value = dadosOriginaisEvento.horarioInicio || '';
    if (inputHorarioFim) inputHorarioFim.value = dadosOriginaisEvento.horarioFim || '';
    
    // Preencher datas e horários de inscrição (verifica se não são '-')
    if (inputDataInicioInscricao) {
      const dataInicioInsc = dadosOriginaisEvento.dataInicioInscricaoParaInput;
      inputDataInicioInscricao.value = (dataInicioInsc && dataInicioInsc !== '-') ? dataInicioInsc : '';
    }
    if (inputDataFimInscricao) {
      const dataFimInsc = dadosOriginaisEvento.dataFimInscricaoParaInput;
      inputDataFimInscricao.value = (dataFimInsc && dataFimInsc !== '-') ? dataFimInsc : '';
    }
    if (inputHorarioInicioInscricao) {
      const horaInicioInsc = dadosOriginaisEvento.horarioInicioInscricao;
      inputHorarioInicioInscricao.value = (horaInicioInsc && horaInicioInsc !== '-') ? horaInicioInsc : '';
    }
    if (inputHorarioFimInscricao) {
      const horaFimInsc = dadosOriginaisEvento.horarioFimInscricao;
      inputHorarioFimInscricao.value = (horaFimInsc && horaFimInsc !== '-') ? horaFimInsc : '';
    }
    
    if (inputPublicoAlvo) inputPublicoAlvo.value = dadosOriginaisEvento.publicoAlvo || '';
    
    if (inputCategoria) {
      inputCategoria.value = dadosOriginaisEvento.categoria || '';
    }
    
    if (inputModalidade) inputModalidade.value = dadosOriginaisEvento.modalidade || '';
    
    if (inputCertificado) {
      // Converter valor numérico para texto
      const certNumerico = dadosOriginaisEvento.certificadoNumerico;
      let certTexto = '';
      
      if (certNumerico === 0 || certNumerico === '0') {
        certTexto = 'Não';
      } else if (certNumerico === 1 || certNumerico === '1') {
        certTexto = 'Sim';
      } else if (certNumerico === 2 || certNumerico === '2') {
        certTexto = 'Ensino';
      } else if (certNumerico === 3 || certNumerico === '3') {
        certTexto = 'Pesquisa';
      } else if (certNumerico === 4 || certNumerico === '4') {
        certTexto = 'Extensão';
      } else if (certNumerico === 5 || certNumerico === '5') {
        certTexto = 'Outro';
      } else if (dadosOriginaisEvento.certificado) {
        // Fallback: usa o valor de texto se existir
        certTexto = dadosOriginaisEvento.certificado;
      }
      
      inputCertificado.value = certTexto;
    }
    
    if (inputDescricao) inputDescricao.value = dadosOriginaisEvento.descricao || '';
  }

  async function abrirModalColaboradores() {
    const modal = document.getElementById('modal-colaboradores');
    if (!modal) {
      alert('Interface de organização não encontrada. Atualize a página.');
      return;
    }
    // Guardar foco atual para restaurar ao fechar
    ultimoFocoAntesModal = document.activeElement;

    // Exibir modal e ajustar acessibilidade
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

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
      document.body.style.overflow = '';

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
        alert('Erro ao carregar organização: ' + (data.erro || 'desconhecido'));
        return;
      }
      renderizarColaboradores(data.colaboradores || [], data.eh_organizador || false, data.cpf_usuario || '', data.cpf_criador || null);
      renderizarSolicitacoes(data.solicitacoes || [], data.eh_organizador || false);
    } catch (e) {
      console.error('Falha ao carregar listas de colaboradores/solicitações', e);
      alert('Falha ao carregar organização');
    }
  }

  function renderizarColaboradores(lista, ehOrganizador, cpfUsuario, cpfCriador) {
    const container = document.getElementById('lista-colaboradores');
    if (!container) return;
    container.innerHTML = '';
    if (!lista.length) {
      container.innerHTML = '<div class="mensagem-vazio">Nenhum organizador adicionado ainda.</div>';
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

      // Se for o criador do evento, mostra apenas um texto
      if (item.CPF === cpfCriador) {
        const txtCriador = document.createElement('span');
        txtCriador.className = 'texto-criador';
        txtCriador.textContent = 'Criador do Evento';
        txtCriador.style.color = '#666';
        txtCriador.style.fontStyle = 'italic';
        txtCriador.style.fontSize = '0.9rem';
        acoes.appendChild(txtCriador);
      }
      // Se for o próprio usuário (mas não criador), mostra botão de sair
      else if (item.CPF === cpfUsuario) {
        const btnSair = document.createElement('button');
        btnSair.className = 'btn-sair';
        btnSair.textContent = 'Sair da Colaboração';
        btnSair.onclick = () => sairDaColaboracao();
        acoes.appendChild(btnSair);
      }
      // Se for outro colaborador, pode remover
      else {
        const btnRem = document.createElement('button');
        btnRem.className = 'btn-remover';
        btnRem.textContent = 'Remover';
        btnRem.onclick = () => removerColaboradorEvento(item.CPF);
        acoes.appendChild(btnRem);
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
        alert('Erro ao adicionar organizador: ' + (data.erro || 'desconhecido'));
        return;
      }
      inp.value = '';
      await carregarListasColaboradoresESolicitacoes();
    } catch (e) {
      console.error('Falha ao adicionar colaborador', e);
      alert('Falha ao adicionar organizador');
    }
  }

  async function removerColaboradorEvento(cpf) {
    if (!confirm('Remover este organizador do evento?')) return;
    try {
      const form = new FormData();
      form.append('action', 'remover');
      form.append('cod_evento', String(codigoEventoAtual));
      form.append('cpf', String(cpf));
      const resp = await fetch('GerenciadorColaboradores.php', { method: 'POST', body: form });
      const data = await resp.json();
      if (!data.sucesso) {
        alert('Erro ao remover organizador: ' + (data.erro || 'desconhecido'));
        return;
      }
      await carregarListasColaboradoresESolicitacoes();
    } catch (e) {
      console.error('Falha ao remover colaborador', e);
      alert('Falha ao remover organizador');
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
      alert('Erro: Código do evento não disponível. Recarregue a página.');
      return;
    }
    if (typeof carregarPagina === 'function') {
      carregarPagina('gerenciarEvento', codigoEventoAtual);
    }
  }

  function editarEvento() {
    if (modoEdicao) return;
    modoEdicao = true;

    try {
      // NÃO sobrescrever dadosOriginaisEvento aqui - ele já foi preenchido com dados completos do servidor
      // Apenas salvamos as imagens atuais caso o usuário cancele
      const imagensAtuais = [...listaImagensEvento];

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

      // Preencher inputs com valores usando a função centralizada
      preencherInputsEdicao();

      // Habilitar edição de imagem
      const campoImagem = document.getElementById('campo-imagem');
      const placeholderImagem = document.getElementById('placeholder-imagem');
      const btnRemoverImagem = document.getElementById('btn-remover-imagem');
      const btnAdicionarMais = document.getElementById('btn-adicionar-mais');

      // No modo edição, clicar na imagem ou placeholder abre seletor
      const abrirSeletor = function () {
        const inputImagem = document.getElementById('input-imagem');
        if (inputImagem) inputImagem.click();
      };
      
      if (campoImagem) {
        campoImagem.onclick = abrirSeletor;
      }
      
      if (placeholderImagem) {
        placeholderImagem.onclick = abrirSeletor;
        placeholderImagem.style.cursor = 'pointer';
      }

      // Mostra botões de edição de imagem no modo edição
      if (btnRemoverImagem) btnRemoverImagem.style.display = 'flex';
      if (btnAdicionarMais) btnAdicionarMais.style.display = 'flex';
      
      // Garante que a imagem está visível se houver imagens
      const imgCarrossel = document.getElementById('imagem-carrossel');
      if (imgCarrossel && listaImagensEvento.length > 0) {
        imgCarrossel.style.display = 'block';
        if (placeholderImagem) placeholderImagem.style.display = 'none';
      } else if (placeholderImagem && listaImagensEvento.length === 0) {
        // Se não há imagens, mostra placeholder
        if (imgCarrossel) imgCarrossel.style.display = 'none';
        placeholderImagem.style.display = 'flex';
      }
      
      // Atualiza visibilidade (mostra setas se houver múltiplas imagens)
      atualizarVisibilidadeSetas();
    } catch (error) {
      console.error('Erro ao editar evento:', error);
      alert('Erro ao ativar modo de edição: ' + error.message);
      modoEdicao = false;
    }
  }

  function trocarParaBotoesEdicao() {
    const btnVoltar = document.getElementById('btn-voltar');
    const btnParticipantes = document.getElementById('btn-participantes');
    const btnEditar = document.getElementById('btn-editar');

    if (!btnVoltar || !btnParticipantes || !btnEditar) {
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
  }

  function trocarParaBotoesVisualizacao() {
    const btnVoltar = document.getElementById('btn-voltar');
    const btnParticipantes = document.getElementById('btn-participantes');
    const btnEditar = document.getElementById('btn-editar');

    if (!btnVoltar || !btnParticipantes || !btnEditar) {
      return;
    }

    // Botão Voltar
    btnVoltar.textContent = 'Voltar';
    btnVoltar.className = 'botao-voltar';
    btnVoltar.onclick = function () {
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
      irParaParticipantes();
    };

    // Botão Editar
    btnEditar.textContent = 'Editar';
    btnEditar.className = 'botao-editar';
    btnEditar.onclick = function () {
      editarEvento();
    };
  }

  function cancelarEdicao() {
    if (!modoEdicao) return;
    modoEdicao = false;

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
      
      // Limpa a lista de imagens para remover
      window.imagensParaRemover = [];

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
    } catch (error) {
      console.error('Erro ao cancelar edição:', error);
    }
  }

  function salvarEvento() {
    if (!modoEdicao) return;

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
      console.log('Input de imagem:', inputImagem, 'Arquivos selecionados:', inputImagem.files.length);
      if (inputImagem.files.length > 0) {
        console.log('Enviando', inputImagem.files.length, 'imagens ao servidor');
        for (let i = 0; i < inputImagem.files.length; i++) {
          formData.append('imagens_evento[]', inputImagem.files[i]);
          console.log('Imagem', i, ':', inputImagem.files[i].name);
        }
      }
      
      // Adiciona lista de imagens para remover
      if (window.imagensParaRemover && window.imagensParaRemover.length > 0) {
        console.log('Imagens para remover:', window.imagensParaRemover);
        formData.append('imagens_remover', JSON.stringify(window.imagensParaRemover));
      }

      // Envia para o servidor
      fetch('AtualizarEvento.php', {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          console.log('Resposta do servidor ao salvar:', data);
          if (data.sucesso) {
            alert(data.mensagem || 'Evento atualizado com sucesso!');

            // Se houve atualização de imagens, atualiza o carrossel com todas as imagens
            if (data.imagens && data.imagens.length > 0) {
              console.log('Imagens retornadas pelo servidor:', data.imagens);
              listaImagensEvento = data.imagens;
              indiceImagemAtual = 0;
              const imagemCarrossel = document.getElementById('imagem-carrossel');
              if (imagemCarrossel) {
                imagemCarrossel.src = listaImagensEvento[indiceImagemAtual];
              }
              atualizarVisibilidadeSetas();
            } else {
              console.log('Nenhuma imagem retornada pelo servidor');
            }
            
            // Limpa a lista de imagens para remover
            window.imagensParaRemover = [];

            // Atualiza valores exibidos
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

            // Atualizar datas e horários de inscrição
            if (inicioInscricao) {
              if (inputDataInicioInscricao.value) {
                const [anoII, mesII, diaII] = inputDataInicioInscricao.value.split('-');
                inicioInscricao.textContent = `${diaII}/${mesII}/${anoII.slice(-2)}`;
              } else {
                inicioInscricao.textContent = '-';
              }
            }

            if (fimInscricao) {
              if (inputDataFimInscricao.value) {
                const [anoFI, mesFI, diaFI] = inputDataFimInscricao.value.split('-');
                fimInscricao.textContent = `${diaFI}/${mesFI}/${anoFI.slice(-2)}`;
              } else {
                fimInscricao.textContent = '-';
              }
            }

            if (horarioInicioInscricao) {
              horarioInicioInscricao.textContent = inputHorarioInicioInscricao.value || '-';
            }

            if (horarioFimInscricao) {
              horarioFimInscricao.textContent = inputHorarioFimInscricao.value || '-';
            }

            if (startTime) startTime.textContent = inputHorarioInicio.value;
            if (endTime) endTime.textContent = inputHorarioFim.value;
            if (audience) audience.textContent = inputPublicoAlvo.value;
            if (category) category.textContent = inputCategoria.value;
            if (modality) modality.textContent = inputModalidade.value;

            // Converter valor numérico do certificado para texto
            if (certificate) {
              const certTexto = inputCertificado.value == '1' ? 'Sim' :
                inputCertificado.value == '0' ? 'Não' :
                  inputCertificado.value;
              certificate.textContent = certTexto;
            }

            if (description) description.textContent = inputDescricao.value;

            // Atualizar dadosOriginaisEvento para refletir as mudanças
            dadosOriginaisEvento.nome = inputNome.value;
            dadosOriginaisEvento.local = inputLocal.value;
            dadosOriginaisEvento.publicoAlvo = inputPublicoAlvo.value;
            dadosOriginaisEvento.categoria = inputCategoria.value;
            dadosOriginaisEvento.modalidade = inputModalidade.value;
            dadosOriginaisEvento.certificado = inputCertificado.value;
            dadosOriginaisEvento.descricao = inputDescricao.value;
            
            // Atualizar datas formatadas
            if (inputDataInicio.value) {
              const [anoI, mesI, diaI] = inputDataInicio.value.split('-');
              dadosOriginaisEvento.dataInicio = `${diaI}/${mesI}/${anoI.slice(-2)}`;
              dadosOriginaisEvento.dataInicioParaInput = inputDataInicio.value;
            }
            if (inputDataFim.value) {
              const [anoF, mesF, diaF] = inputDataFim.value.split('-');
              dadosOriginaisEvento.dataFim = `${diaF}/${mesF}/${anoF.slice(-2)}`;
              dadosOriginaisEvento.dataFimParaInput = inputDataFim.value;
            }
            
            dadosOriginaisEvento.horarioInicio = inputHorarioInicio.value;
            dadosOriginaisEvento.horarioFim = inputHorarioFim.value;
            
            // Atualizar datas de inscrição
            if (inputDataInicioInscricao.value) {
              const [anoII, mesII, diaII] = inputDataInicioInscricao.value.split('-');
              dadosOriginaisEvento.dataInicioInscricao = `${diaII}/${mesII}/${anoII.slice(-2)}`;
              dadosOriginaisEvento.dataInicioInscricaoParaInput = inputDataInicioInscricao.value;
            } else {
              dadosOriginaisEvento.dataInicioInscricao = '-';
              dadosOriginaisEvento.dataInicioInscricaoParaInput = '';
            }
            
            if (inputDataFimInscricao.value) {
              const [anoFI, mesFI, diaFI] = inputDataFimInscricao.value.split('-');
              dadosOriginaisEvento.dataFimInscricao = `${diaFI}/${mesFI}/${anoFI.slice(-2)}`;
              dadosOriginaisEvento.dataFimInscricaoParaInput = inputDataFimInscricao.value;
            } else {
              dadosOriginaisEvento.dataFimInscricao = '-';
              dadosOriginaisEvento.dataFimInscricaoParaInput = '';
            }
            
            dadosOriginaisEvento.horarioInicioInscricao = inputHorarioInicioInscricao.value || '-';
            dadosOriginaisEvento.horarioFimInscricao = inputHorarioFimInscricao.value || '-';
            
            // Atualizar imagens se foram alteradas
            if (inputImagem.files.length > 0) {
              dadosOriginaisEvento.imagens = [...listaImagensEvento];
            }

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
            
            // Mostra primeira imagem
            indiceImagemAtual = 0;
            if (listaImagensEvento.length > 0) {
              document.getElementById('imagem-carrossel').src = listaImagensEvento[0];
            }
            
            // Limpar input de imagem para permitir nova seleção futura
            inputImagem.value = '';
            
            // Atualiza visibilidade (esconde setas)
            atualizarVisibilidadeSetas();

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

      // Cria preview em base64 para exibir no carrossel
      const reader = new FileReader();
      reader.onload = function (e) {
        listaImagensEvento.push(e.target.result);
        
        // Se era a primeira imagem, mostra ela e esconde o placeholder
        if (listaImagensEvento.length === 1) {
          const imgCarrossel = document.getElementById('imagem-carrossel');
          const placeholderDiv = document.getElementById('placeholder-imagem');
          
          if (imgCarrossel) {
            imgCarrossel.src = e.target.result;
            imgCarrossel.style.display = 'block';
          }
          if (placeholderDiv) {
            placeholderDiv.style.display = 'none';
          }
          indiceImagemAtual = 0;
        }
        
        atualizarVisibilidadeSetas();
      };
      reader.readAsDataURL(file);
    });

    // NÃO limpa o input para que os arquivos fiquem disponíveis para upload
    // event.target.value = ''; <- REMOVIDO
    console.log('Imagens adicionadas ao preview. Total de arquivos no input:', event.target.files.length);
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
      const imagemRemovida = listaImagensEvento[indiceImagemAtual];
      
      // Se a imagem não é base64 (já está no servidor), marca para exclusão
      if (!imagemRemovida.startsWith('data:')) {
        if (!window.imagensParaRemover) {
          window.imagensParaRemover = [];
        }
        window.imagensParaRemover.push(imagemRemovida);
      }
      
      listaImagensEvento.splice(indiceImagemAtual, 1);
      if (listaImagensEvento.length === 0) {
        // Quando não há mais imagens, mostra o placeholder
        const imgCarrossel = document.getElementById('imagem-carrossel');
        const placeholderDiv = document.getElementById('placeholder-imagem');
        
        if (imgCarrossel) imgCarrossel.style.display = 'none';
        if (placeholderDiv) placeholderDiv.style.display = 'flex';
        
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
    // Mostra setas quando há múltiplas imagens (em qualquer modo)
    const deveExibirSetas = listaImagensEvento.length > 1;
    
    document.querySelectorAll('.carrossel-anterior, .carrossel-proxima').forEach(el => {
      el.style.display = deveExibirSetas ? 'flex' : 'none';
    });

    // Botões do modal também
    document.querySelectorAll('.modal-imagem-btn-anterior, .modal-imagem-btn-proxima').forEach(el => {
      el.style.display = deveExibirSetas ? '' : 'none';
    });

    // Controla o botão de remover/adicionar apenas no modo edição
    const btnRemover = document.getElementById('btn-remover-imagem');
    const btnAdicionar = document.querySelector('.btn-adicionar-mais');
    if (btnRemover) btnRemover.style.display = modoEdicao ? 'flex' : 'none';
    if (btnAdicionar) btnAdicionar.style.display = modoEdicao ? 'flex' : 'none';
  }

  function mudarImagem(direcao) {
    // Navega entre as imagens quando há múltiplas
    if (listaImagensEvento.length > 1) {
      indiceImagemAtual = (indiceImagemAtual + direcao + listaImagensEvento.length) % listaImagensEvento.length;
      const imgCarrossel = document.getElementById('imagem-carrossel');
      if (imgCarrossel) {
        imgCarrossel.src = listaImagensEvento[indiceImagemAtual];
      }
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
    document.body.style.overflow = '';
  }

  function abrirModalCompartilhar() {
    const modal = document.getElementById('modal-compartilhar');
    if (!modal) return;

    // Gera o link de inscrição do evento
    const linkInscricao = window.location.origin + '/CEU/PaginasPublicas/ContainerPublico.php?pagina=evento&cod_evento=' + codigoEventoAtual;
    document.getElementById('link-inscricao').value = linkInscricao;

    modal.classList.add('ativo');
    bloquearScroll();
  }

  function fecharModalCompartilhar() {
    const modal = document.getElementById('modal-compartilhar');
    if (modal) {
      modal.classList.remove('ativo');
      desbloquearScroll();
    }
  }

  function copiarLink() {
    const linkInput = document.getElementById('link-inscricao');
    const textoSpan = document.getElementById('texto-copiar');
    const iconeDiv = document.getElementById('icone-copiar');

    linkInput.select();
    linkInput.setSelectionRange(0, 99999); // Para mobile

    navigator.clipboard.writeText(linkInput.value).then(() => {
      // Feedback visual
      if (textoSpan) textoSpan.textContent = 'Copiado!';
      if (iconeDiv) {
        const corOriginal = iconeDiv.style.backgroundColor;
        iconeDiv.style.backgroundColor = '#28a745';

        setTimeout(() => {
          if (textoSpan) textoSpan.textContent = 'Copiar';
          if (iconeDiv) iconeDiv.style.backgroundColor = corOriginal;
        }, 2000);
      }
    }).catch(err => {
      console.error('Erro ao copiar:', err);
      // Fallback para navegadores antigos
      try {
        linkInput.focus();
        document.execCommand('copy');
        if (textoSpan) textoSpan.textContent = 'Copiado!';
        setTimeout(() => {
          if (textoSpan) textoSpan.textContent = 'Copiar';
        }, 2000);
      } catch (e) {
        alert('Por favor, copie o link manualmente.');
      }
    });
  }

  function compartilharWhatsApp() {
    const link = document.getElementById('link-inscricao').value;
    const texto = encodeURIComponent('Confira este evento! Inscreva-se aqui: ' + link);
    window.open('https://wa.me/?text=' + texto, '_blank');
  }

  function compartilharInstagram() {
    const link = document.getElementById('link-inscricao').value;
    // Instagram não suporta compartilhamento direto de links via URL scheme
    // Então vamos copiar o link e informar ao usuário
    navigator.clipboard.writeText(link).then(() => {
      alert('Link copiado! Cole no Instagram para compartilhar.\n\nDica: Você pode colar o link na sua bio, em stories ou em posts.');
    }).catch(() => {
      // Fallback
      const textarea = document.createElement('textarea');
      textarea.value = link;
      textarea.style.position = 'fixed';
      textarea.style.opacity = '0';
      document.body.appendChild(textarea);
      textarea.select();
      document.execCommand('copy');
      document.body.removeChild(textarea);
      alert('Link copiado! Cole no Instagram para compartilhar.\n\nDica: Você pode colar o link na sua bio, em stories ou em posts.');
    });
  }

  function compartilharEmail() {
    const link = document.getElementById('link-inscricao').value;
    const assunto = encodeURIComponent('Convite para Evento');
    const corpo = encodeURIComponent('Olá!\n\nGostaria de convidá-lo(a) para participar deste evento.\n\nInscreva-se através do link: ' + link + '\n\nAté breve!');
    window.location.href = 'mailto:?subject=' + assunto + '&body=' + corpo;
  }

  function compartilharX() {
    const link = document.getElementById('link-inscricao').value;
    const texto = encodeURIComponent('Confira este evento! 🎉');
    window.open('https://twitter.com/intent/tweet?text=' + texto + '&url=' + encodeURIComponent(link), '_blank');
  }

  function inicializarCartaoEventoOrganizando() {
    const btnVoltar = document.getElementById('btn-voltar');
    const btnCompartilhar = document.getElementById('btn-compartilhar');
    const btnParticipantes = document.getElementById('btn-participantes');
    const btnEditar = document.getElementById('btn-editar');
    const imagemCarrossel = document.getElementById('imagem-carrossel');
    const inputImagem = document.getElementById('input-imagem');
    const btnAnterior = document.getElementById('btn-anterior');
    const btnProxima = document.getElementById('btn-proxima');
    const btnRemoverImg = document.getElementById('btn-remover-imagem');
    const btnAdicionarMaisImgs = document.getElementById('btn-adicionar-mais-imagens');

    if (btnVoltar && btnCompartilhar && btnParticipantes && btnEditar) {
      btnVoltar.onclick = function () {
        if (typeof carregarPagina === 'function') {
          carregarPagina('meusEventos');
        } else {
          window.location.href = 'ContainerOrganizador.php?pagina=meusEventos';
        }
      };
      btnCompartilhar.onclick = abrirModalCompartilhar;
      btnParticipantes.onclick = irParaParticipantes;
      btnEditar.onclick = editarEvento;
    }

    // Event listeners para setas do carrossel
    if (btnAnterior) {
      btnAnterior.onclick = function (e) {
        e.stopPropagation();
        mudarImagem(-1);
      };
    }
    
    if (btnProxima) {
      btnProxima.onclick = function (e) {
        e.stopPropagation();
        mudarImagem(1);
      };
    }
    
    // Event listener para remover imagem
    if (btnRemoverImg) {
      btnRemoverImg.onclick = function (e) {
        e.stopPropagation();
        removerImagemAtual();
      };
    }
    
    // Event listener para adicionar mais imagens
    if (btnAdicionarMaisImgs) {
      btnAdicionarMaisImgs.onclick = function (e) {
        e.stopPropagation();
        if (inputImagem) inputImagem.click();
      };
    }

    if (imagemCarrossel) {
      imagemCarrossel.onclick = function (e) {
        e.stopPropagation();
        if (listaImagensEvento.length > 0) {
          document.getElementById('imagem-ampliada').src = listaImagensEvento[indiceImagemAtual];
          document.getElementById('modal-imagem').style.display = 'flex';
          document.body.style.overflow = 'hidden';
        }
      };
    }

    if (inputImagem) {
      inputImagem.onchange = adicionarImagens;
    }

    // Expor funções globais necessárias
    window.abrirModalColaboradores = abrirModalColaboradores;
    window.abrirModalCompartilhar = abrirModalCompartilhar;
    window.fecharModalCompartilhar = fecharModalCompartilhar;
    window.copiarLink = copiarLink;
    window.compartilharWhatsApp = compartilharWhatsApp;
    window.compartilharInstagram = compartilharInstagram;
    window.compartilharEmail = compartilharEmail;
    window.compartilharX = compartilharX;
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
    let codEvento = urlParams.get('cod_evento');

    // Se não vier da URL, tenta pegar da variável global (quando carregado via AJAX)
    if (!codEvento && window.codigoEventoParaGerenciar) {
      codEvento = window.codigoEventoParaGerenciar;
    }

    if (codEvento) {
      carregarDadosEventoDoServidor(codEvento);
    }
  }

  // Inicializar quando o DOM estiver pronto
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarCartaoEventoOrganizando);
  } else {
    inicializarCartaoEventoOrganizando();
  }
})();
