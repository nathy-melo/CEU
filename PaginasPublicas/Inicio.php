<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Eventos Acontecendo</title>
  <link rel="stylesheet" href="../styleGlobal.css" />
  <link rel="stylesheet" href="../styleGlobalMobile.css" media="(max-width: 767px)" />
  <style>
    /* Botão compartilhar no card */
    .CaixaDoEvento {
      position: relative;
    }

    .EventoInfo {
      width: 100%;
    }

    .BotaoAcaoCard {
      position: absolute;
      bottom: 2cqi;
      right: 2cqi;
      width: 10cqi;
      height: 10cqi;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1) 0.1s;
      border: none;
      padding: 0;
      cursor: pointer;
      z-index: 50;
      visibility: hidden;
      opacity: 0;
    }

    .CaixaDoEvento:hover .BotaoAcaoCard {
      transform: translateY(-530%);
      visibility: visible;
      opacity: 1;
    }

    .BotaoCompartilharCard {
      width: 10cqi;
      height: 10cqi;
      border-radius: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      border: none;
      padding: 0;
      cursor: pointer;
      transition: transform 0.2s ease, background 0.2s ease;
    }

    .BotaoCompartilharCard:hover {
      transform: scale(1.1);
    }

    .BotaoCompartilharCard img {
      width: 7cqi;
      height: 7cqi;
      display: block;
    }

    /* Modal de Compartilhar - mesmo padrão do CartaodoEventoParticipante */
    body.modal-aberto {
      overflow: hidden !important;
    }

    body.modal-aberto #main-content {
      overflow: hidden !important;
    }

    /* Modal de compartilhar agora em styleModais.css */
  </style>
</head>

<body>
  <?php
  include_once '../BancoDados/conexao.php';

  // Buscar eventos trazendo campos usados no filtro
  $sql = "SELECT cod_evento, categoria, nome, inicio, conclusao, duracao, certificado, lugar, modalidade, imagem FROM evento ORDER BY inicio";
  $res = mysqli_query($conexao, $sql);

  // Mapear texto e converter para string simples
  function formatar($txt)
  {
    $map = [
      'Í' => 'A',
      'Í€' => 'A',
      'Í‚' => 'A',
      'Í' => 'A',
      'Í„' => 'A',
      'á' => 'a',
      'Í ' => 'a',
      'Í¢' => 'a',
      'ã' => 'a',
      'Í¤' => 'a',
      'É' => 'E',
      'É' => 'E',
      'È' => 'E',
      'Ê' => 'E',
      'Ë' => 'E',
      'è' => 'e',
      'ê' => 'e',
      'ë' => 'e',
      'Íˆ' => 'E',
      'ÍŠ' => 'E',
      'Í‹' => 'E',
      'é' => 'e',
      'Í¨' => 'e',
      'Íª' => 'e',
      'Í«' => 'e',
      'Í' => 'I',
      'ÍŒ' => 'I',
      'ÍŽ' => 'I',
      'Í' => 'I',
      'í' => 'i',
      'Í¬' => 'i',
      'Í®' => 'i',
      'Í¯' => 'i',
      'Í“' => 'O',
      'Í’' => 'O',
      'Í”' => 'O',
      'Õ' => 'O',
      'Í–' => 'O',
      'ó' => 'o',
      'Í²' => 'o',
      'Í´' => 'o',
      'õ' => 'o',
      'Í¶' => 'o',
      'Ú' => 'U',
      'Í™' => 'U',
      'Í›' => 'U',
      'Íœ' => 'U',
      'ú' => 'u',
      'Í¹' => 'u',
      'Í»' => 'u',
      'Í¼' => 'u',
      'Í‡' => 'C',
      'ç' => 'c'
    ];
    $txt = strtr($txt ?? '', $map);
    $txt = strtolower($txt);
    $txt = str_replace(' ', '_', $txt);
    return preg_replace('/[^a-z0-9_]/', '', $txt);
  }
  ?>

  <div id="main-content">
    <div class="section-title-wrapper">
      <div class="barra-pesquisa-container">
        <div class="barra-pesquisa">
          <div class="campo-pesquisa-wrapper">
            <input class="campo-pesquisa" type="text" placeholder="Procurar eventos" />
            <button class="botao-pesquisa" aria-label="Procurar">
              <div class="icone-pesquisa">
                <img src="../Imagens/lupa.png" alt="Lupa">
              </div>
            </button>
          </div>
        </div>
        <button class="botao botao-filtrar">
          <span>Filtrar</span>
          <img src="../Imagens/filtro.png" alt="Filtro">
        </button>
      </div>
      <div class="div-section-title">
        <h1 class="section-title">Eventos acontecendo</h1>
      </div>
    </div>

    <div class="container" id="eventos-container">
      <?php if ($res && mysqli_num_rows($res) > 0): ?>
        <?php while ($ev = mysqli_fetch_assoc($res)):
          $dataInicioISO = date('Y-m-d', strtotime($ev['inicio']));
          $dataFormatada = date('d/m/y', strtotime($ev['inicio']));
          $tipo = formatar($ev['categoria']); // liga com os checkboxes tipo_evento
          $local = formatar($ev['lugar']);    // liga com localizacao
          $modalidadeAttr = formatar($ev['modalidade'] ?? ''); // liga com modalidade
          // Mapeia duração numérica (horas) para faixas usadas no filtro
          $duracaoFaixa = '';
          $duracaoNumero = 0;
          if (is_numeric($ev['duracao'])) {
            $h = (float)$ev['duracao'];
            $duracaoNumero = $h;
            if ($h < 1) {
              $duracaoFaixa = 'menos_1h';
            } elseif ($h < 2) {
              $duracaoFaixa = '1h_2h';
            } elseif ($h < 4) {
              $duracaoFaixa = '2h_4h';
            } elseif ($h < 6) {
              $duracaoFaixa = '4h_6h';
            } elseif ($h < 8) {
              $duracaoFaixa = '6h_8h';
            } elseif ($h < 10) {
              $duracaoFaixa = '8h_10h';
            } elseif ($h < 20) {
              $duracaoFaixa = '10h_20h';
            } else {
              $duracaoFaixa = 'mais_20h';
            }
          }
          // Certificado: considerar tipo_certificado
          $tipo_certificado = $ev['tipo_certificado'] ?? '';
          $tem_certificado = ((int)$ev['certificado'] === 1);

          if ($tem_certificado) {
            if ($tipo_certificado === 'Ensino' || $tipo_certificado === 'Pesquisa' || $tipo_certificado === 'Extensao') {
              $certTexto = $tipo_certificado;
            } else {
              $certTexto = 'Sim';
            }
            $cert = 'sim';
          } else {
            $certTexto = 'Não';
            $cert = 'nao';
          }

          // Preparar caminho da imagem
          $imagem_evento = isset($ev['imagem']) && $ev['imagem'] !== '' ? $ev['imagem'] : 'ImagensEventos/CEU-ImagemEvento.png';
          $caminho_imagem = '../' . ltrim($imagem_evento, "/\\");
        ?>
          <a class="botao CaixaDoEvento" style="text-decoration:none;color:inherit;display:block;"
            href="ContainerPublico.php?pagina=evento&id=<?= (int)$ev['cod_evento'] ?>"
            data-tipo="<?= htmlspecialchars($tipo) ?>" data-localizacao="<?= htmlspecialchars($local) ?>"
            data-duracao="<?= htmlspecialchars($duracaoFaixa) ?>" data-duracaoNumero="<?= $duracaoNumero ?>"
            data-certificado="<?= $cert ?>"
            data-data="<?= $dataInicioISO ?>" data-data-fim="<?= date('Y-m-d', strtotime($ev['conclusao'])) ?>" data-modalidade="<?= htmlspecialchars($modalidadeAttr) ?>"
            data-cod-evento="<?= (int)$ev['cod_evento'] ?>">
            <div class="BotaoAcaoCard">
              <button type="button" class="BotaoCompartilharCard botao" title="Compartilhar" aria-label="Compartilhar"
                data-cod="<?= (int)$ev['cod_evento'] ?>">
                <img src="../Imagens/Icone_Compartilhar.svg" alt="Compartilhar" />
              </button>
            </div>
            <div class="EventoImagem">
              <img src="<?= htmlspecialchars($caminho_imagem) ?>" alt="<?= htmlspecialchars($ev['nome']) ?>">
            </div>
            <div class="EventoTitulo">
              <?= htmlspecialchars($ev['nome']) ?>
            </div>
            <div class="EventoInfo">
              <ul class="evento-info-list" aria-label="Informações do evento">
                <li class="evento-info-item">
                  <span class="evento-info-icone" aria-hidden="true">
                    <img src="../Imagens/info-categoria.svg" alt="" />
                  </span>
                  <span class="evento-info-texto"><span class="evento-info-label">Categoria:</span>
                    <?= htmlspecialchars($ev['categoria']) ?>
                  </span>
                </li>
                <li class="evento-info-item">
                  <span class="evento-info-icone" aria-hidden="true">
                    <img src="../Imagens/info-modalidade.svg" alt="" />
                  </span>
                  <span class="evento-info-texto"><span class="evento-info-label">Modalidade:</span>
                    <?= htmlspecialchars($ev['modalidade'] ?? '') ?>
                  </span>
                </li>
                <li class="evento-info-item">
                  <span class="evento-info-icone" aria-hidden="true">
                    <img src="../Imagens/info-data.svg" alt="" />
                  </span>
                  <span class="evento-info-texto"><span class="evento-info-label">Data:</span>
                    <?= $dataFormatada ?>
                  </span>
                </li>
                <li class="evento-info-item">
                  <span class="evento-info-icone" aria-hidden="true">
                    <img src="../Imagens/info-local.svg" alt="" />
                  </span>
                  <span class="evento-info-texto"><span class="evento-info-label">Local:</span>
                    <?= htmlspecialchars($ev['lugar']) ?>
                  </span>
                </li>
                <li class="evento-info-item">
                  <span class="evento-info-icone" aria-hidden="true">
                    <img src="../Imagens/info-certificado.svg" alt="" />
                  </span>
                  <span class="evento-info-texto"><span class="evento-info-label">Certificado:</span>
                    <?= htmlspecialchars($ev['certificado'] == 1 ? 'Sim' : 'Não') ?>
                  </span>
                </li>
              </ul>
            </div>
          </a>
        <?php endwhile; ?>
      <?php else: ?>
        <p style="grid-column:1/-1;text-align:center;padding:20px;">Nenhum evento cadastrado.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Modal Compartilhar (mesmo do CartaodoEventoParticipante) -->
  <div id="modal-compartilhar" class="modal-overlay">
    <div class="modal-container" onclick="event.stopPropagation()">
      <div class="modal-cabecalho">
        <h2 class="modal-titulo">Compartilhar</h2>
        <button type="button" class="modal-btn-fechar" onclick="fecharModalCompartilhar()" aria-label="Fechar">&times;</button>
      </div>
      <div class="modal-corpo">

        <div class="opcoes-compartilhamento">
          <button class="btn-compartilhar-app" onclick="compartilharWhatsApp()" title="Compartilhar no WhatsApp">
            <div class="icone-app icone-whatsapp">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
              </svg>
            </div>
            <span>WhatsApp</span>
          </button>

          <button class="btn-compartilhar-app" onclick="compartilharInstagram()" title="Compartilhar no Instagram">
            <div class="icone-app icone-instagram">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
              </svg>
            </div>
            <span>Instagram</span>
          </button>

          <button class="btn-compartilhar-app" onclick="compartilharEmail()" title="Compartilhar por E-mail">
            <div class="icone-app icone-email">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
              </svg>
            </div>
            <span>E-mail</span>
          </button>

          <button class="btn-compartilhar-app" onclick="compartilharX()" title="Compartilhar no X (Twitter)">
            <div class="icone-app icone-x">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
              </svg>
            </div>
            <span>X</span>
          </button>

          <button class="btn-compartilhar-app" onclick="copiarLink()" title="Copiar Link">
            <div class="icone-app icone-copiar" id="icone-copiar">
              <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z" />
              </svg>
            </div>
            <span id="texto-copiar">Copiar</span>
          </button>
        </div>

        <div class="campo-link">
          <input type="text" id="link-inscricao" readonly />
        </div>

        <div class="modal-alerta info">
          <strong>ℹ️ Informação:</strong> Compartilhe este evento com seus amigos e familiares!
        </div>
      </div>
    </div>
  </div>

  <script>
    // Adaptação do código do CartaodoEventoParticipante para lista de cards
    let codEvento = null;

    function bloquearScroll() {
      document.body.classList.add('modal-aberto');
      document.addEventListener('wheel', prevenirScroll, {
        passive: false
      });
      document.addEventListener('touchmove', prevenirScroll, {
        passive: false
      });
      document.addEventListener('keydown', prevenirScrollTeclado, false);
    }

    function desbloquearScroll() {
      document.body.classList.remove('modal-aberto');
      document.removeEventListener('wheel', prevenirScroll);
      document.removeEventListener('touchmove', prevenirScroll);
      document.removeEventListener('keydown', prevenirScrollTeclado);
    }

    function prevenirScroll(e) {
      if (document.body.classList.contains('modal-aberto')) {
        e.preventDefault();
      }
    }

    function prevenirScrollTeclado(e) {
      if (!document.body.classList.contains('modal-aberto')) return;
      const teclas = [32, 33, 34, 35, 36, 37, 38, 39, 40];
      if (teclas.includes(e.keyCode)) e.preventDefault();
    }

    function abrirModalCompartilhar() {
      if (!codEvento) return;
      const modal = document.getElementById('modal-compartilhar');
      const linkEvento = `${window.location.origin}/CEU/PaginasPublicas/EventoPublico.php?codEvento=${codEvento}`;
      const input = document.getElementById('link-inscricao');
      if (input) input.value = linkEvento;
      modal.classList.add('ativo');
      bloquearScroll();
    }

    function fecharModalCompartilhar() {
      const modal = document.getElementById('modal-compartilhar');
      modal.classList.remove('ativo');
      desbloquearScroll();
    }

    function copiarLink() {
      const input = document.getElementById('link-inscricao');
      input.select();
      input.setSelectionRange(0, 99999);
      navigator.clipboard.writeText(input.value).then(() => {
        const iconeCopiar = document.getElementById('icone-copiar');
        const textoCopiar = document.getElementById('texto-copiar');
        iconeCopiar.innerHTML = '<svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>';
        textoCopiar.textContent = 'Copiado!';
        setTimeout(() => {
          iconeCopiar.innerHTML = '<svg width=\"26\" height=\"26\" viewBox=\"0 0 24 24\" fill=\"currentColor\"><path d=\"M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z\"/></svg>';
          textoCopiar.textContent = 'Copiar';
        }, 2000);
      });
    }

    function compartilharWhatsApp() {
      if (!codEvento) return;
      const linkEvento = `${window.location.origin}/CEU/PaginasPublicas/EventoPublico.php?codEvento=${codEvento}`;
      const texto = `Confira este evento: ${linkEvento}`;
      window.open(`https://wa.me/?text=${encodeURIComponent(texto)}`, '_blank');
    }

    function compartilharInstagram() {
      if (!codEvento) return;
      const linkEvento = `${window.location.origin}/CEU/PaginasPublicas/EventoPublico.php?codEvento=${codEvento}`;
      navigator.clipboard.writeText(linkEvento).then(() => {
        alert('Link copiado! Cole no Instagram para compartilhar.');
      }).catch(() => {
        const input = document.getElementById('link-inscricao');
        input.select();
        document.execCommand('copy');
        alert('Link copiado! Cole no Instagram para compartilhar.');
      });
    }

    function compartilharEmail() {
      if (!codEvento) return;
      const linkEvento = `${window.location.origin}/CEU/PaginasPublicas/EventoPublico.php?codEvento=${codEvento}`;
      const assunto = 'Confira este evento!';
      const corpo = `Olá! Gostaria de compartilhar este evento com você: ${linkEvento}`;
      window.location.href = `mailto:?subject=${encodeURIComponent(assunto)}&body=${encodeURIComponent(corpo)}`;
    }

    function compartilharX() {
      if (!codEvento) return;
      const linkEvento = `${window.location.origin}/CEU/PaginasPublicas/EventoPublico.php?codEvento=${codEvento}`;
      const texto = `Confira este evento!`;
      window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(texto)}&url=${encodeURIComponent(linkEvento)}`, '_blank');
    }
    // Fechar modal ao clicar fora
    document.getElementById('modal-compartilhar').onclick = function(e) {
      if (e.target === this) fecharModalCompartilhar();
    };
    // Fechar modal com ESC
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' || e.key === 'Esc') fecharModalCompartilhar();
    });

    // Listeners para os botões de compartilhar nos cards
    document.addEventListener('click', function(e) {
      const btn = e.target.closest('.BotaoCompartilharCard');
      if (btn) {
        e.preventDefault();
        e.stopPropagation();
        codEvento = parseInt(btn.getAttribute('data-cod')) || null;
        abrirModalCompartilhar();
      }
    }, true);
  </script>
  <!-- Script de Responsividade Mobile -->
  <script src="../PaginasGlobais/ResponsividadeMobile.js"></script>

</body>

</html>