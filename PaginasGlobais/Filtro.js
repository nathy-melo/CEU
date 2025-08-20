// Moderniza o Filtro.js para funcionar de forma robusta em todos os containers (Público/Participante/Organizador)
// - API unificada via window.CEUFiltro
// - Idempotente (não duplica listeners)
// - Estado persistido por sessão (sessionStorage) e compatível com window.estadoFiltro
// - Observa mudanças no container de eventos e reaplica filtros
// - Backwards-compat: mantém as funções globais usadas hoje

(function () {
  const NAMESPACE = 'CEUFiltro';

  const defaults = {
    containerSelector: '#eventos-container',
    cardSelector: '.CaixaDoEvento',
    triggerSelector: '.botao-filtrar',
    filterFormId: 'filtro-container',
    bodyActiveClass: 'filtro-ativo',
    // Mapeamento de chaves do formulário -> data-atributos nos cards
    attributes: {
      tipo_evento: 'tipo',
      modalidade: 'modalidade',
      localizacao: 'localizacao',
      duracao: 'duracao',
      certificado: 'certificado',
      data: 'data'
    },
    storageKey: 'ceu.filtro.estado'
  };

  const state = {
    options: { ...defaults },
    initialized: false,
    filterEl: null,
    triggerEl: null,
    observer: null,
    handlers: {},
    pageKey: null
  };

  function getPageKey() {
    try {
      const params = new URLSearchParams(window.location.search);
      const pagina = params.get('pagina') || 'default';
      return `${window.location.pathname}?pagina=${pagina}`;
    } catch (_) {
      return window.location.pathname || 'default';
    }
  }

  function storageKey() {
    return `${state.options.storageKey}|${state.pageKey || getPageKey()}`;
  }

  function saveState() {
    // Coleta o estado atual do formulário
    const form = document.getElementById(state.options.filterFormId) || state.filterEl;
    const result = {};
    if (form) {
      form.querySelectorAll('input').forEach(el => {
        if (el.type === 'checkbox') {
          const name = el.name;
          if (el.checked) {
            if (!result[name]) result[name] = [];
            result[name].push(el.value);
          }
        } else {
          const name = el.name;
          if (!result[name]) result[name] = [];
          if (el.value) result[name].push(el.value);
        }
      });
    }
    // Compat com código legado
    window.estadoFiltro = result;
    try {
      sessionStorage.setItem(storageKey(), JSON.stringify(result));
    } catch (_) { /* ignore */ }
  }

  function readLegacyState() {
    // Lê do window.estadoFiltro se existir
    if (window.estadoFiltro && typeof window.estadoFiltro === 'object') {
      return window.estadoFiltro;
    }
    return null;
  }

  function loadState() {
    // Prioriza estado legado, depois sessionStorage
    const legacy = readLegacyState();
    if (legacy) return legacy;
    try {
      const raw = sessionStorage.getItem(storageKey());
      if (raw) return JSON.parse(raw);
    } catch (_) { /* ignore */ }
    return null;
  }

  function buildFilterElement() {
    const filterHtml = `
        <form class="filtro-container" id="${state.options.filterFormId}">
            <h1 class="filtro-titulo">Filtrar por</h1>

            <fieldset class="filtro-grupo">
            <legend class="grupo-titulo">
                Período
                <span class="titulo-icone"></span>
            </legend>
            <div class="entradas-data">
                <div class="entrada-data">
                <label for="data-inicio" class="texto-data">De</label>
                <input type="date" id="data-inicio" name="data-inicio" class="input-data">
                </div>
                <div class="entrada-data">
                <label for="data-fim" class="texto-data">Até</label>
                <input type="date" id="data-fim" name="data-fim" class="input-data">
                </div>
            </div>
            </fieldset>

            <div class="divisor"><div class="divisor-linha"></div></div>

            <fieldset class="filtro-grupo">
            <legend class="grupo-titulo">
                Tipo de Evento
                <span class="titulo-icone"></span>
            </legend>
            <div class="colunas-checkbox">
                <div class="coluna">
                <label class="item-checkbox"><input type="checkbox" name="tipo_evento" value="congresso"><span class="checkbox-personalizado"></span><span>Congresso</span></label>
                <label class="item-checkbox"><input type="checkbox" name="tipo_evento" value="palestra"><span class="checkbox-personalizado"></span><span>Palestra</span></label>
                <label class="item-checkbox"><input type="checkbox" name="tipo_evento" value="workshop"><span class="checkbox-personalizado"></span><span>Workshop</span></label>
                <label class="item-checkbox"><input type="checkbox" name="tipo_evento" value="forum"><span class="checkbox-personalizado"></span><span>Fórum</span></label>
                <label class="item-checkbox"><input type="checkbox" name="tipo_evento" value="conferencia"><span class="checkbox-personalizado"></span><span>Conferência</span></label>
                <label class="item-checkbox"><input type="checkbox" name="tipo_evento" value="seminario"><span class="checkbox-personalizado"></span><span>Seminário</span></label>
                </div>
                <div class="coluna">
                <label class="item-checkbox"><input type="checkbox" name="tipo_evento" value="visita_tecnica"><span class="checkbox-personalizado"></span><span>Visita Técnica</span></label>
                <label class="item-checkbox"><input type="checkbox" name="tipo_evento" value="curso"><span class="checkbox-personalizado"></span><span>Curso</span></label>
                <label class="item-checkbox"><input type="checkbox" name="tipo_evento" value="oficina"><span class="checkbox-personalizado"></span><span>Oficina</span></label>
                </div>
            </div>
            </fieldset>

            <div class="divisor"><div class="divisor-linha"></div></div>

            <fieldset class="filtro-grupo">
            <legend class="grupo-titulo">
                Modalidade do Evento
                <span class="titulo-icone"></span>
            </legend>
            <div class="lista-checkbox">
                <label class="item-checkbox"><input type="checkbox" name="modalidade" value="presencial"><span class="checkbox-personalizado"></span><span>Presencial</span></label>
                <label class="item-checkbox"><input type="checkbox" name="modalidade" value="online"><span class="checkbox-personalizado"></span><span>Online</span></label>
                <label class="item-checkbox"><input type="checkbox" name="modalidade" value="hibrido"><span class="checkbox-personalizado"></span><span>Híbrido</span></label>
            </div>
            </fieldset>

            <div class="divisor"><div class="divisor-linha"></div></div>

            <fieldset class="filtro-grupo">
            <legend class="grupo-titulo">
                Localização
                <span class="titulo-icone"></span>
            </legend>
            <div class="lista-checkbox">
                <label class="item-checkbox"><input type="checkbox" name="localizacao" value="auditorio"><span class="checkbox-personalizado"></span><span>Auditório</span></label>
                <label class="item-checkbox"><input type="checkbox" name="localizacao" value="quadra"><span class="checkbox-personalizado"></span><span>Quadra</span></label>
                <label class="item-checkbox"><input type="checkbox" name="localizacao" value="biblioteca"><span class="checkbox-personalizado"></span><span>Biblioteca</span></label>
                <label class="item-checkbox"><input type="checkbox" name="localizacao" value="sala_x"><span class="checkbox-personalizado"></span><span>Sala X</span></label>
            </div>
            </fieldset>

            <div class="divisor"><div class="divisor-linha"></div></div>

            <fieldset class="filtro-grupo">
            <legend class="grupo-titulo">
                Duração do Evento
                <span class="titulo-icone"></span>
            </legend>
            <div class="lista-checkbox">
                <label class="item-checkbox"><input type="checkbox" name="duracao" value="menos_1h"><span class="checkbox-personalizado"></span><span>Menos de 1h</span></label>
                <label class="item-checkbox"><input type="checkbox" name="duracao" value="1h_2h"><span class="checkbox-personalizado"></span><span>1h-2h</span></label>
                <label class="item-checkbox"><input type="checkbox" name="duracao" value="2h_4h"><span class="checkbox-personalizado"></span><span>2h-4h</span></label>
                <label class="item-checkbox"><input type="checkbox" name="duracao" value="mais_5h"><span class="checkbox-personalizado"></span><span>+5h</span></label>
            </div>
            </fieldset>

            <div class="divisor"><div class="divisor-linha"></div></div>

            <fieldset class="filtro-grupo">
            <legend class="grupo-titulo">
                Categorização do Certificado
                <span class="titulo-icone"></span>
            </legend>
            <div class="lista-checkbox">
                <label class="item-checkbox"><input type="checkbox" name="certificado" value="ensino"><span class="checkbox-personalizado"></span><span>Ensino</span></label>
                <label class="item-checkbox"><input type="checkbox" name="certificado" value="pesquisa"><span class="checkbox-personalizado"></span><span>Pesquisa</span></label>
                <label class="item-checkbox"><input type="checkbox" name="certificado" value="extensao"><span class="checkbox-personalizado"></span><span>Extensão</span></label>
            </div>
            </fieldset>
        </form>
    `;
    const div = document.createElement('div');
    div.innerHTML = filterHtml;
    const form = div.firstElementChild;

    // Restaura o estado salvo
    const saved = loadState();
    if (saved) {
      for (const key in saved) {
        const values = saved[key];
        if (!values) continue;
        if (key.endsWith('[]')) {
          const cleanKey = key.slice(0, -2);
          const elements = form.querySelectorAll(`input[name="${cleanKey}"]`);
          elements.forEach(el => { if (values.includes(el.value)) el.checked = true; });
        } else {
          const element = form.querySelector(`[name="${key}"]`);
          if (element) element.value = values[0] || '';
        }
      }
    }
    return form;
  }

  function getElements() {
    const container = document.querySelector(state.options.containerSelector);
    const trigger = document.querySelector(state.options.triggerSelector);
    const form = document.getElementById(state.options.filterFormId);
    return { container, trigger, form };
  }

  function getCheckedValues(form, name) {
    return Array.from(form.querySelectorAll(`input[name="${name}"]:checked`)).map(i => i.value);
  }

  function applyFilters() {
    const { container } = getElements();
    if (!container) return;
    const form = document.getElementById(state.options.filterFormId);
    if (!form) return;

    const getChecked = (name) => getCheckedValues(form, name);

    const tipos = getChecked('tipo_evento');
    const modalidades = getChecked('modalidade');
    const locais = getChecked('localizacao');
    const duracoes = getChecked('duracao');
    const certificados = getChecked('certificado');

    const dataInicio = form.querySelector('input[name="data-inicio"]').value || '';
    const dataFim = form.querySelector('input[name="data-fim"]').value || '';

    const cards = container.querySelectorAll(state.options.cardSelector);
    cards.forEach(card => {
      const attr = (key) => (card.dataset[state.options.attributes[key]] || '').toLowerCase();
      const tipo = attr('tipo_evento');
      const modalidade = attr('modalidade');
      const local = attr('localizacao');
      const duracao = attr('duracao');
      const certificado = attr('certificado');
      const data = card.dataset[state.options.attributes['data']] || '';

      let ok = true;
      if (ok && tipos.length) ok = tipos.includes(tipo);
      if (ok && modalidades.length) ok = modalidades.includes(modalidade);
      if (ok && locais.length) ok = locais.includes(local);
      if (ok && duracoes.length) ok = duracoes.includes(duracao);
      if (ok && certificados.length) ok = certificados.includes(certificado);
      if (ok && dataInicio) ok = data >= dataInicio;
      if (ok && dataFim) ok = data <= dataFim;

      card.dataset.filterOk = ok ? 'true' : 'false';

      if (!ok) {
        card.dataset.hiddenByFilter = 'true';
      } else {
        delete card.dataset.hiddenByFilter;
      }

      const hiddenByFilter = card.dataset.hiddenByFilter === 'true';
      const hiddenBySearch = card.dataset.hiddenBySearch === 'true';
      const hide = hiddenByFilter || hiddenBySearch;
      card.style.display = hide ? 'none' : '';
    });
  }

  function wireInputs() {
    const form = document.getElementById(state.options.filterFormId);
    if (!form) return;
    const inputs = form.querySelectorAll('input[type="checkbox"], input[type="date"]');
    // Evita listeners duplicados usando um atributo marcador
    inputs.forEach(inp => {
      if (!inp.__ceuFiltroBound) {
        inp.addEventListener('change', () => {
          applyFilters();
          saveState();
          atualizarIconesDeGrupo(form);
        });
        inp.__ceuFiltroBound = true;
      }
    });
  }

  function atualizarIconesDeGrupo(root) {
    const filtroGrupos = root.querySelectorAll('.filtro-grupo');
    filtroGrupos.forEach(grupo => {
      const checkboxes = grupo.querySelectorAll('input[type="checkbox"]');
      const dateInputs = grupo.querySelectorAll('input[type="date"]');
      const tituloIcone = grupo.querySelector('.titulo-icone');
      if (!tituloIcone) return;
      let algumSelecionado = false;
      checkboxes.forEach(checkbox => { if (checkbox.checked) algumSelecionado = true; });
      dateInputs.forEach(dateInput => { if (dateInput.value) algumSelecionado = true; });
      tituloIcone.classList.toggle('ativo', algumSelecionado);
    });
  }

  function toggleOpen(e) {
    if (e) e.stopPropagation();
    const form = state.filterEl || document.getElementById(state.options.filterFormId);
    if (!form) return;
    const ativo = form.classList.contains('ativo');
    form.classList.toggle('ativo', !ativo);
    document.body.classList.toggle(state.options.bodyActiveClass, !ativo);
  }

  function montarFiltro() {
    // Evita duplicar
    if (document.getElementById(state.options.filterFormId)) {
      state.filterEl = document.getElementById(state.options.filterFormId);
      wireInputs();
      applyFilters();
      atualizarIconesDeGrupo(state.filterEl);
      return;
    }
    const filterContainer = buildFilterElement();
    document.body.appendChild(filterContainer);
    state.filterEl = filterContainer;

    // Eventos de abrir/fechar
    const { trigger } = getElements();
    state.triggerEl = trigger || state.triggerEl;

    if (state.triggerEl && !state.handlers.triggerClick) {
      state.handlers.triggerClick = (ev) => toggleOpen(ev);
      state.triggerEl.addEventListener('click', state.handlers.triggerClick);
    }

    if (!state.handlers.windowClick) {
      state.handlers.windowClick = (event) => {
        if (!state.filterEl) return;
        if (state.filterEl.classList.contains('ativo') && !state.filterEl.contains(event.target)) {
          toggleOpen();
        }
      };
      window.addEventListener('click', state.handlers.windowClick);
    }

    if (!state.handlers.filterClick) {
      state.handlers.filterClick = (event) => event.stopPropagation();
      state.filterEl.addEventListener('click', state.handlers.filterClick);
    }

    // Liga inputs e aplica uma passada inicial
    wireInputs();
    applyFilters();
    atualizarIconesDeGrupo(state.filterEl);

    // Observa mudanças no container para reaplicar
    const { container } = getElements();
    if (container) {
      if (state.observer) {
        state.observer.disconnect();
        state.observer = null;
      }
      state.observer = new MutationObserver(() => applyFilters());
      state.observer.observe(container, { childList: true, subtree: true, attributes: true });
    }
  }

  function init(options) {
    state.options = { ...defaults, ...(options || {}) };
    state.pageKey = getPageKey();
    montarFiltro();
    state.initialized = true;
  }

  function destroy() {
    // Salva o estado e desmonta listeners/observer/DOM
    try { saveState(); } catch (_) {}

    if (state.observer) {
      try { state.observer.disconnect(); } catch (_) {}
      state.observer = null;
    }

    if (state.triggerEl && state.handlers.triggerClick) {
      try { state.triggerEl.removeEventListener('click', state.handlers.triggerClick); } catch (_) {}
    }
    if (state.handlers.windowClick) {
      try { window.removeEventListener('click', state.handlers.windowClick); } catch (_) {}
    }
    if (state.filterEl && state.handlers.filterClick) {
      try { state.filterEl.removeEventListener('click', state.handlers.filterClick); } catch (_) {}
    }

    state.handlers = {};

    const el = document.getElementById(state.options.filterFormId) || state.filterEl;
    if (el && el.parentNode) {
      try { el.parentNode.removeChild(el); } catch (_) {}
    }
    document.body.classList.remove(state.options.bodyActiveClass);

    state.filterEl = null;
    state.triggerEl = null;
    state.initialized = false;
  }

  function isActive() {
    return !!document.getElementById(state.options.filterFormId);
  }

  // Expor API moderna
  window.CEUFiltro = {
    init,
    destroy,
    apply: applyFilters,
    saveState,
    isActive,
    setOptions(opts) { state.options = { ...state.options, ...(opts || {}) }; },
    wireInputs
  };

  // Backwards compatibility
  window.applyFiltersParticipante = function () { return window.CEUFiltro.apply(); };
  window.wireFilterInputsParticipante = function () { return window.CEUFiltro.wireInputs(); };
  window.removerFiltroExistente = function () { return window.CEUFiltro.destroy(); };
  window.inicializarFiltro = function (opts) { return window.CEUFiltro.init(opts); };
})();

