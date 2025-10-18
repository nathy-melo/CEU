// CartaoDoEventoOrganizando.js
(function() {
  'use strict';

  let modoEdicao = false;
  let imagens = ['../Imagens/CEU-Logo.png'];
  let indiceAtual = 0;
  let dadosOriginais = {};

  function abrirModalColaboradores() {
    alert('Funcionalidade de adicionar colaboradores em desenvolvimento!\n\nEm breve você poderá adicionar outros organizadores para colaborar com este evento.');
  }

  function irParaParticipantes() {
    console.log('Navegar para página de participantes');
  }

  function editarEvento() {
    if (modoEdicao) return;
    modoEdicao = true;
    
    console.log('=== EDITANDO EVENTO ===');
    
    try {
      // Salvar dados originais
      dadosOriginais = {
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
        imagens: [...imagens]
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
      const inputPublicoAlvo = document.getElementById('input-publico-alvo');
      const inputCategoria = document.getElementById('input-categoria');
      const inputModalidade = document.getElementById('input-modalidade');
      const inputCertificado = document.getElementById('input-certificado');
      const inputDescricao = document.getElementById('input-descricao');

      if (inputNome) inputNome.value = dadosOriginais.nome;
      if (inputLocal) inputLocal.value = dadosOriginais.local;
      
      // Converter datas de dd/mm/yy para yyyy-mm-dd com validação
      if (inputDataInicio && dadosOriginais.dataInicio) {
        const partesDataInicio = dadosOriginais.dataInicio.split('/');
        if (partesDataInicio.length === 3) {
          const [diaI, mesI, anoI] = partesDataInicio;
          inputDataInicio.value = `20${anoI}-${mesI}-${diaI}`;
        }
      }
      
      if (inputDataFim && dadosOriginais.dataFim) {
        const partesDataFim = dadosOriginais.dataFim.split('/');
        if (partesDataFim.length === 3) {
          const [diaF, mesF, anoF] = partesDataFim;
          inputDataFim.value = `20${anoF}-${mesF}-${diaF}`;
        }
      }
      
      if (inputHorarioInicio) inputHorarioInicio.value = dadosOriginais.horarioInicio;
      if (inputHorarioFim) inputHorarioFim.value = dadosOriginais.horarioFim;
      if (inputPublicoAlvo) inputPublicoAlvo.value = dadosOriginais.publicoAlvo;
      if (inputCategoria) inputCategoria.value = dadosOriginais.categoria;
      if (inputModalidade) inputModalidade.value = dadosOriginais.modalidade;
      if (inputCertificado) inputCertificado.value = dadosOriginais.certificado;
      if (inputDescricao) inputDescricao.value = dadosOriginais.descricao;

      // Habilitar edição de imagem
      const campoImagem = document.getElementById('campo-imagem');
      const btnRemoverImagem = document.getElementById('btn-remover-imagem');
      const btnAdicionarMais = document.getElementById('btn-adicionar-mais');
      
      if (campoImagem) {
        campoImagem.onclick = function() {
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
    btnVoltar.onclick = function() { 
      console.log('Botão Voltar clicado');
      history.back(); 
    };

    // Botão Participantes
    btnParticipantes.textContent = 'Participantes';
    btnParticipantes.className = 'botao-participantes';
    btnParticipantes.onclick = function() {
      console.log('Botão Participantes clicado');
      irParaParticipantes();
    };

    // Botão Editar
    btnEditar.textContent = 'Editar';
    btnEditar.className = 'botao-editar';
    btnEditar.onclick = function() {
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
      const audience = document.getElementById('audience');
      const category = document.getElementById('category');
      const modality = document.getElementById('modality');
      const certificate = document.getElementById('certificate');
      const description = document.getElementById('description');
      const imagemCarrossel = document.getElementById('imagem-carrossel');
      
      if (eventName) eventName.textContent = dadosOriginais.nome;
      if (eventLocal) eventLocal.textContent = dadosOriginais.local;
      if (startDate) startDate.textContent = dadosOriginais.dataInicio;
      if (endDate) endDate.textContent = dadosOriginais.dataFim;
      if (startTime) startTime.textContent = dadosOriginais.horarioInicio;
      if (endTime) endTime.textContent = dadosOriginais.horarioFim;
      if (audience) audience.textContent = dadosOriginais.publicoAlvo;
      if (category) category.textContent = dadosOriginais.categoria;
      if (modality) modality.textContent = dadosOriginais.modalidade;
      if (certificate) certificate.textContent = dadosOriginais.certificado;
      if (description) description.textContent = dadosOriginais.descricao;
      
      imagens = [...dadosOriginais.imagens];
      indiceAtual = 0;
      if (imagemCarrossel) imagemCarrossel.src = imagens[indiceAtual];

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
      // Atualizar valores exibidos
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
      
      const inputNome = document.getElementById('input-nome');
      const inputLocal = document.getElementById('input-local');
      const inputDataInicio = document.getElementById('input-data-inicio');
      const inputDataFim = document.getElementById('input-data-fim');
      const inputHorarioInicio = document.getElementById('input-horario-inicio');
      const inputHorarioFim = document.getElementById('input-horario-fim');
      const inputPublicoAlvo = document.getElementById('input-publico-alvo');
      const inputCategoria = document.getElementById('input-categoria');
      const inputModalidade = document.getElementById('input-modalidade');
      const inputCertificado = document.getElementById('input-certificado');
      const inputDescricao = document.getElementById('input-descricao');

      if (eventName && inputNome) eventName.textContent = inputNome.value;
      if (eventLocal && inputLocal) eventLocal.textContent = inputLocal.value;
      
      // Converter datas de yyyy-mm-dd para dd/mm/yy
      if (startDate && inputDataInicio && inputDataInicio.value) {
        const [anoI, mesI, diaI] = inputDataInicio.value.split('-');
        startDate.textContent = `${diaI}/${mesI}/${anoI.slice(-2)}`;
      }
      
      if (endDate && inputDataFim && inputDataFim.value) {
        const [anoF, mesF, diaF] = inputDataFim.value.split('-');
        endDate.textContent = `${diaF}/${mesF}/${anoF.slice(-2)}`;
      }
      
      if (startTime && inputHorarioInicio) startTime.textContent = inputHorarioInicio.value;
      if (endTime && inputHorarioFim) endTime.textContent = inputHorarioFim.value;
      if (audience && inputPublicoAlvo) audience.textContent = inputPublicoAlvo.value;
      if (category && inputCategoria) category.textContent = inputCategoria.value;
      if (modality && inputModalidade) modality.textContent = inputModalidade.value;
      if (certificate && inputCertificado) certificate.textContent = inputCertificado.value;
      if (description && inputDescricao) description.textContent = inputDescricao.value;

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
      console.log('Chamando trocarParaBotoesVisualizacao...');
      trocarParaBotoesVisualizacao();

      alert('Evento atualizado com sucesso!');
      console.log('=== EVENTO SALVO ===');
    } catch (error) {
      console.error('Erro ao salvar evento:', error);
      console.error('Stack trace:', error.stack);
      alert('Erro ao salvar evento: ' + error.message);
    }
  }

  function excluirEvento() {
    if (confirm('Tem certeza que deseja excluir este evento? Esta ação não pode ser desfeita.')) {
      alert('Evento excluído com sucesso!');
      console.log('Evento excluído');
      // TODO: Implementar exclusão no backend e redirecionar
      // history.back();
    }
  }

  function adicionarImagens(event) {
    const files = Array.from(event.target.files);
    files.forEach(file => {
      const reader = new FileReader();
      reader.onload = function(e) {
        imagens.push(e.target.result);
        if (imagens.length === 1) {
          indiceAtual = 0;
          mostrarCarrossel();
        }
        atualizarVisibilidadeSetas();
      };
      reader.readAsDataURL(file);
    });
  }

  function mostrarCarrossel() {
    document.getElementById('placeholder-imagem').style.display = 'none';
    document.getElementById('carrossel-imagens').style.display = 'flex';
    document.getElementById('imagem-carrossel').src = imagens[indiceAtual];
  }

  function esconderCarrossel() {
    document.getElementById('placeholder-imagem').style.display = 'flex';
    document.getElementById('carrossel-imagens').style.display = 'none';
  }

  function removerImagemAtual() {
    if (imagens.length > 0) {
      imagens.splice(indiceAtual, 1);
      if (imagens.length === 0) {
        esconderCarrossel();
        document.getElementById('input-imagem').value = '';
      } else {
        if (indiceAtual >= imagens.length) {
          indiceAtual = imagens.length - 1;
        }
        document.getElementById('imagem-carrossel').src = imagens[indiceAtual];
        atualizarVisibilidadeSetas();
      }
    }
  }

  function atualizarVisibilidadeSetas() {
    const multiple = imagens.length > 1;
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
    if (imagens.length > 0) {
      indiceAtual = (indiceAtual + direcao + imagens.length) % imagens.length;
      document.getElementById('imagem-carrossel').src = imagens[indiceAtual];
    }
  }

  function mudarImagemModal(direcao) {
    if (imagens.length > 0) {
      indiceAtual = (indiceAtual + direcao + imagens.length) % imagens.length;
      document.getElementById('imagem-ampliada').src = imagens[indiceAtual];
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
      btnVoltar.onclick = function() { history.back(); };
      btnParticipantes.onclick = irParaParticipantes;
      btnEditar.onclick = editarEvento;
      console.log('✓ Botões inicializados');
    } else {
      console.error('✗ Erro: Botões não encontrados');
    }

    if (imagemCarrossel) {
      imagemCarrossel.onclick = function(e) {
        e.stopPropagation();
        if (imagens.length > 0) {
          document.getElementById('imagem-ampliada').src = imagens[indiceAtual];
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
    
    // Inicialização das setas
    atualizarVisibilidadeSetas();
    console.log('✓ CartaoDoEventoOrganizando pronto!');
  }

  // Inicializar quando o DOM estiver pronto
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarCartaoEventoOrganizando);
  } else {
    inicializarCartaoEventoOrganizando();
  }
})();
