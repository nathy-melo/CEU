<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Eventos Acontecendo</title>
  <link rel="stylesheet" href="../styleGlobal.css" />
  <link rel="stylesheet" href="../styleGlobalMobile.css" media="(max-width: 767px)" />
  <style>
    /* Botões flutuantes no card */
    .CaixaDoEvento {
      position: relative;
    }

    .AcoesFlutuantes {
      position: absolute;
      bottom: 1.5cqi;
      right: 2cqi;
      display: flex;
      flex-direction: column;
      gap: 2cqi;
      opacity: 0;
      visibility: hidden;
      transform: translateY(100%);
      transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1) 0.1s,
        visibility 0.4s cubic-bezier(0.4, 0, 0.2, 1) 0.1s,
        transform 0.4s cubic-bezier(0.4, 0, 0.2, 1) 0.1s;
      z-index: 50;
      pointer-events: auto;
      /* IMPORTANTE: Permitir cliques mesmo quando opacity=0 durante hover */
    }

    .CaixaDoEvento:hover .AcoesFlutuantes {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
      pointer-events: auto;
      /* Garantir que os botões sejam clicáveis */
    }

    .BotaoAcaoCard {
      width: 11cqi;
      height: 11cqi;
      border-radius: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      border: none;
      padding: 0;
      cursor: pointer;
      transition: transform 0.2s ease, background 0.2s ease;
      pointer-events: auto;
      /* IMPORTANTE: Garantir que o botão seja clicável */
      position: relative;
      /* Adicionar contexto de posicionamento */
      z-index: 100;
      /* Colocar acima de qualquer outro elemento */
    }

    .BotaoAcaoCard:hover {
      transform: scale(1.1);
    }

    .BotaoAcaoCard img {
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

    /* CSS do modal compartilhar agora em styleModais.css */

    /* CSS dos elementos de compartilhar agora em styleModais.css */

    /* Modais de confirmação inscrição/desinscrição (padrão dos cartões) */
    /* CSS dos modais de confirmação agora em styleModais.css */

    /* CSS do modal de mensagem agora em styleModais.css */

    /* Botão para abrir lista de favoritos (esquerda da barra de pesquisa) */
    .BotaoFavoritosTrigger {
      width: clamp(30px, 4vw, 48px);
      aspect-ratio: 1 / 1;
      flex-shrink: 0;
      border-radius: 100% !important;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: none;
      cursor: pointer;
    }

    .BotaoFavoritosTrigger img {
      width: 1.25rem;
      height: 1.25rem;
      display: block;
    }

    /* Modal de Favoritos - CSS agora em styleModais.css */

    /* Cards de favoritos - mesmo estilo do .CaixaDoEvento */
    .favorito-item {
      background-color: var(--branco);
      border-radius: 1cqi;
      padding: 0;
      box-shadow: 0.5cqi 0.5cqi 3cqi var(--sombra-forte);
      display: grid;
      aspect-ratio: 3 / 2;
      position: relative;
      overflow: hidden;
      container-type: inline-size;
      width: 100%;
      min-width: 0;
      transition: all 0.3s ease;
      text-decoration: none;
      color: inherit;
    }

    /* Botões de ação nos cards de favoritos */
    .favorito-item .AcoesFlutuantes {
      position: absolute;
      bottom: 0.3rem;
      right: 0.5rem;
      display: flex;
      flex-direction: column;
      gap: 0.3rem;
      opacity: 0;
      visibility: hidden;
      transform: translateY(100%);
      transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1) 0.1s,
        visibility 0.4s cubic-bezier(0.4, 0, 0.2, 1) 0.1s,
        transform 0.4s cubic-bezier(0.4, 0, 0.2, 1) 0.1s;
      z-index: 50;
    }

    .favorito-item:hover .AcoesFlutuantes {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    .favorito-item-imagem {
      width: 100%;
      height: 100%;
      border-radius: 2cqi 2cqi 0 0;
      aspect-ratio: 3 / 2;
      transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1) 0.1s;
      transform: translateY(0);
      overflow: hidden;
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: var(--branco);
    }

    .favorito-item:hover .favorito-item-imagem {
      transform: translateY(-100%);
    }

    .favorito-item-imagem img {
      width: 100%;
      height: 100%;
      max-width: none;
      object-fit: cover;
      object-position: center;
      display: block;
      transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
      flex-shrink: 0;
    }

    .favorito-item:hover .favorito-item-imagem img {
      transform: scale(1.15);
    }

    .favorito-item-titulo {
      font-size: 5cqi;
      font-weight: 800;
      padding: 4cqi 3.5cqi 4cqi;
      color: var(--branco);
      background: var(--botao);
      line-height: 1.2;
      overflow: hidden;
      text-overflow: ellipsis;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      line-clamp: 2;
      -webkit-box-orient: vertical;
      transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1) 0.1s;
      transform: translateY(0);
      text-shadow: 0 0.5cqi 1cqi rgba(0, 0, 0, 0.3);
      letter-spacing: 0.05cqi;
      grid-row: 2 / 3;
      position: relative;
      z-index: 2;
    }

    .favorito-item:hover .favorito-item-titulo {
      -webkit-line-clamp: 1;
      line-clamp: 1;
      transform: translateY(-380%);
    }

    .favorito-item-info {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      color: var(--cinza-escuro);
      line-height: 1.5;
      padding: 0 3.5cqi 2.5cqi;
      text-align: left;
      overflow: visible;
      word-wrap: break-word;
      display: block;
      opacity: 0;
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) 0.1s;
      pointer-events: none;
      font-weight: 500;
      z-index: 3;
      transform: translateY(100%);
      width: 85%;
    }

    .favorito-item:hover .favorito-item-info {
      opacity: 1;
      transform: translateY(0%);
      pointer-events: auto;
    }

    .favorito-item-info .evento-info-list {
      list-style: none;
      margin: 0;
      padding: 0;
      display: flex;
      flex-direction: column;
      gap: 1.5cqi;
    }

    .favorito-item-info .evento-info-item {
      display: flex;
      align-items: center;
      gap: 2cqi;
      background: var(--tabela_participantes);
      border-radius: 2cqi;
      padding: 1cqi 1cqi;
      box-shadow: 0 0.4cqi 1.2cqi var(--sombra-leve);
    }

    .favorito-item-info .evento-info-icone {
      width: 6cqi;
      height: 6cqi;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      background: var(--branco);
      color: var(--botao);
      box-shadow: 0 0.3cqi 0.8cqi var(--sombra-leve) inset;
    }

    .favorito-item-info .evento-info-icone img {
      width: 80%;
      height: 80%;
      display: block;
    }

    .favorito-item-info .evento-info-texto {
      font-size: 4cqi;
      color: var(--cinza-escuro);
      font-weight: 600;
      display: inline-flex;
      gap: 1cqi;
      align-items: baseline;
    }

    .favorito-item-info .evento-info-label {
      color: var(--azul-escuro);
      font-weight: 800;
    }

    .lista-favoritos::-webkit-scrollbar {
      width: 0.5rem;
    }

    .lista-favoritos::-webkit-scrollbar-track {
      background: var(--fundo-claro-transparente);
      border-radius: 0.25rem;
    }

    .lista-favoritos::-webkit-scrollbar-thumb {
      background: var(--botao);
      border-radius: 0.25rem;
    }

    .lista-favoritos::-webkit-scrollbar-thumb:hover {
      background: var(--destaque);
    }
  </style>
</head>

<body>
  <?php
  include_once '../BancoDados/conexao.php';

  // Buscar eventos do banco de dados
  $sql = "SELECT cod_evento, categoria, nome, inicio, conclusao, duracao, certificado, lugar, modalidade, imagem FROM evento ORDER BY inicio";
  $res = mysqli_query($conexao, $sql);

  // Função para formatar strings para os atributos data-*
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
        <!-- Botão de favoritos à esquerda da barra de pesquisa -->
        <button type="button" class="BotaoFavoritosTrigger botao" id="btn-abrir-favoritos" title="Ver favoritos"
          aria-label="Ver favoritos">
          <img src="../Imagens/Medalha_preenchida.svg" alt="Favoritos">
        </button>
        <div class="barra-pesquisa">
          <div class="campo-pesquisa-wrapper">
            <input class="campo-pesquisa" type="text" id="busca-eventos" name="busca_eventos"
              placeholder="Procurar eventos" autocomplete="off" />
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
          $tipo = formatar($ev['categoria']);
          $local = formatar($ev['lugar']);
          $modalidadeAttr = formatar($ev['modalidade'] ?? '');

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
            href="ContainerParticipante.php?pagina=evento&id=<?= (int)$ev['cod_evento'] ?>"
            data-tipo="<?= htmlspecialchars($tipo) ?>" data-modalidade="<?= htmlspecialchars($modalidadeAttr) ?>"
            data-localizacao="<?= htmlspecialchars($local) ?>" data-duracao="<?= htmlspecialchars($duracaoFaixa) ?>"
            data-duracaoNumero="<?= $duracaoNumero ?>"
            data-data="<?= $dataInicioISO ?>" data-data-fim="<?= date('Y-m-d', strtotime($ev['conclusao'])) ?>" data-certificado="<?= $cert ?>"
            data-cod-evento="<?= (int)$ev['cod_evento'] ?>">
            <!-- Ações flutuantes: Inscrever, Favoritar, Mensagem, Compartilhar -->
            <div class="AcoesFlutuantes">
              <button type="button" class="BotaoAcaoCard BotaoInscreverCard botao" title="Inscrever-se"
                aria-label="Inscrever" data-cod="<?= (int)$ev['cod_evento'] ?>" data-inscrito="0">
                <img src="../Imagens/Circulo_adicionar.svg" alt="Inscrever">
              </button>
              <button type="button" class="BotaoAcaoCard BotaoFavoritoCard botao" title="Favoritar" aria-label="Favoritar"
                data-cod="<?= (int)$ev['cod_evento'] ?>" data-favorito="0">
                <img src="../Imagens/Medalha_linha.svg" alt="Favoritar">
              </button>
              <button type="button" class="BotaoAcaoCard BotaoMensagemCard botao" title="Enviar mensagem ao organizador"
                aria-label="Mensagem" data-cod="<?= (int)$ev['cod_evento'] ?>">
                <img src="../Imagens/Carta.svg" alt="Mensagem">
              </button>
              <button type="button" class="BotaoAcaoCard BotaoCompartilharCard botao" title="Compartilhar"
                aria-label="Compartilhar" data-cod="<?= (int)$ev['cod_evento'] ?>">
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
                    <?= $ev['certificado'] == 1 ? 'Sim' : 'Não' ?>
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

  <!-- Modal Favoritos -->
  <div id="modal-favoritos" class="modal-overlay">
    <div class="modal-container modal-grande" onclick="event.stopPropagation()">
      <div class="modal-cabecalho">
        <h2 class="modal-titulo">Meus favoritos</h2>
        <button type="button" class="modal-btn-fechar" onclick="fecharModalFavoritos()" aria-label="Fechar">&times;</button>
      </div>
      <div class="modal-corpo">
        <div id="lista-favoritos" class="lista-favoritos"></div>
      </div>
    </div>
  </div>

  <!-- Modal Compartilhar -->
  <div class="modal-overlay" id="modal-compartilhar">
    <div class="modal-container" onclick="event.stopPropagation()">
      <div class="modal-cabecalho">
        <h2 class="modal-titulo">Compartilhar</h2>
        <button class="modal-btn-fechar" onclick="fecharModalCompartilhar()" aria-label="Fechar">&times;</button>
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
                  d="M20 4H4c-1.1 0-2 .9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
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

  <!-- Modais de confirmação inscrição/desinscrição -->
  <div class="modal-overlay" id="modalConfirmarInscricao">
    <div class="modal-container modal-pequeno" onclick="event.stopPropagation()">
      <h2 class="modal-titulo">Deseja se inscrever neste evento?</h2>
      <div class="modal-rodape centralizado">
        <button type="button" class="modal-btn cancelar"
          onclick="fecharModalConfirmarInscricao()">Cancelar</button>
        <button type="button" class="modal-btn confirmar"
          onclick="confirmarInscricaoRapida()">Confirmar</button>
      </div>
    </div>
  </div>
  <div class="modal-overlay" id="modalConfirmarDesinscricao">
    <div class="modal-container modal-pequeno" onclick="event.stopPropagation()">
      <h2 class="modal-titulo">Deseja cancelar sua inscrição neste evento?</h2>
      <div class="modal-rodape centralizado">
        <button type="button" class="modal-btn cancelar"
          onclick="fecharModalConfirmarDesinscricao()">Não</button>
        <button type="button" class="modal-btn confirmar"
          onclick="confirmarDesinscricaoRapida()">Sim, cancelar</button>
      </div>
    </div>
  </div>
  <div class="modal-overlay" id="modalInscricaoConfirmada">
    <div class="modal-container modal-pequeno" onclick="event.stopPropagation()">
      <h2 class="modal-titulo">Inscrição realizada com sucesso!</h2>
      <div class="modal-rodape centralizado">
        <button type="button" class="modal-btn ok"
          onclick="fecharModalInscricaoConfirmada()">OK</button>
      </div>
    </div>
  </div>
  <div class="modal-overlay" id="modalDesinscricaoConfirmada">
    <div class="modal-container modal-pequeno" onclick="event.stopPropagation()">
      <h2 class="modal-titulo">Inscrição cancelada com sucesso!</h2>
      <div class="modal-rodape centralizado">
        <button type="button" class="modal-btn ok"
          onclick="fecharModalDesinscricaoConfirmada()">OK</button>
      </div>
    </div>
  </div>

  <!-- Modal Mensagem ao Organizador -->
  <div class="modal-overlay" id="modal-mensagem">
    <div class="modal-container" onclick="event.stopPropagation()">

      <!-- Cabeçalho -->
      <div class="modal-cabecalho">
        <h2 class="modal-titulo">Enviar mensagem ao organizador</h2>
        <button class="modal-btn-fechar" onclick="fecharModalMensagem()" aria-label="Fechar">&times;</button>
      </div>

      <!-- Corpo -->
      <div class="modal-corpo">
        <textarea id="texto-mensagem-organizador" class="modal-mensagem-textarea" maxlength="500"
          placeholder="Escreva sua mensagem (máx. 500 caracteres)"></textarea>
        <div class="contador-caracteres" id="contador-mensagem-organizador">0 / 500</div>
      </div>

      <!-- Rodapé -->
      <div class="modal-rodape">
        <button class="modal-btn cancelar" type="button" onclick="fecharModalMensagem()">Cancelar</button>
        <button class="modal-btn enviar" type="button" onclick="enviarMensagemOrganizador()">Enviar</button>
      </div>

    </div>
  </div>

  <script>
    // Variáveis globais - verificar se já existem para evitar re-declaração
    if (typeof window.codEvento === 'undefined') {
      window.codEvento = null;
    }
    if (typeof window.codEventoAcao === 'undefined') {
      window.codEventoAcao = null;
    }
    if (typeof window.btnInscreverAtual === 'undefined') {
      window.btnInscreverAtual = null;
    }
    if (typeof window.inscricaoCache === 'undefined') {
      window.inscricaoCache = new Map();
    }

    // Criar referências locais usando var (permite re-declaração) para facilitar o uso
    var codEvento = window.codEvento;
    var codEventoAcao = window.codEventoAcao;
    var btnInscreverAtual = window.btnInscreverAtual;
    var inscricaoCache = window.inscricaoCache;

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
      const elementoAtivo = document.activeElement;
      const isInputOuTextarea = elementoAtivo && (elementoAtivo.tagName === 'TEXTAREA' || elementoAtivo.tagName === 'INPUT');
      const teclas = [33, 34, 35, 36, 37, 38, 39, 40]; // Teclas de navegação (sem espaço)
      // Se for espaço (32) e estiver em input/textarea, permitir
      if (e.keyCode === 32 && isInputOuTextarea) return;
      // Bloquear outras teclas de navegação
      if (teclas.includes(e.keyCode)) e.preventDefault();
    }

    function abrirModalCompartilhar() {
      if (!codEvento) return;
      const modal = document.getElementById('modal-compartilhar');
      if (!modal) return;
      const linkEvento = `${window.location.origin}/CEU/PaginasPublicas/EventoPublico.php?codEvento=${codEvento}`;
      const input = document.getElementById('link-inscricao');
      if (input) input.value = linkEvento;
      modal.classList.add('ativo');
      bloquearScroll();
    }

    function fecharModalCompartilhar() {
      const modal = document.getElementById('modal-compartilhar');
      if (!modal) return;
      modal.classList.remove('ativo');
      desbloquearScroll();
      // Garantir que o menu permaneça ativo após fechar o modal
      setTimeout(() => {
        const params = new URLSearchParams(window.location.search);
        const pagina = params.get('pagina') || 'inicio';
        if (typeof window.setMenuAtivoPorPagina === 'function') {
          window.setMenuAtivoPorPagina(pagina);
        }
      }, 10);
    }

    function copiarLink() {
      const input = document.getElementById('link-inscricao');
      if (!input) return;
      input.select();
      input.setSelectionRange(0, 99999);
      navigator.clipboard.writeText(input.value).then(() => {
        const iconeCopiar = document.getElementById('icone-copiar');
        const textoCopiar = document.getElementById('texto-copiar');
        if (iconeCopiar) {
          iconeCopiar.innerHTML = '<svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>';
        }
        if (textoCopiar) {
          textoCopiar.textContent = 'Copiado!';
        }
        setTimeout(() => {
          if (iconeCopiar) {
            iconeCopiar.innerHTML = '<svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>';
          }
          if (textoCopiar) {
            textoCopiar.textContent = 'Copiar';
          }
        }, 2000);
      }).catch(() => {
        // Fallback para navegadores antigos
        try {
          input.select();
          document.execCommand('copy');
        } catch (err) {
          console.error('Erro ao copiar link:', err);
        }
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
    // Função para inicializar modais (chamada após carregamento via AJAX)
    function inicializarModais() {
      // Fechar modal de compartilhar ao clicar fora
      var modalCompartilhar = document.getElementById('modal-compartilhar');
      if (modalCompartilhar) {
        modalCompartilhar.onclick = function(e) {
          if (e.target === this) {
            e.stopPropagation();
            fecharModalCompartilhar();
          }
        };
      }

      // Fechar modal de favoritos ao clicar fora
      var modalFav = document.getElementById('modal-favoritos');
      if (modalFav) {
        modalFav.onclick = function(e) {
          if (e.target === this) fecharModalFavoritos();
        };
        var listaFavoritos = document.getElementById('lista-favoritos');
        if (listaFavoritos) {
          listaFavoritos.addEventListener('wheel', function(e) {
            e.stopPropagation();
          }, {
            passive: false
          });
          listaFavoritos.addEventListener('touchmove', function(e) {
            e.stopPropagation();
          }, {
            passive: false
          });
        }
      }

      // Fechar modal de mensagem ao clicar fora
      var modalMensagem = document.getElementById('modal-mensagem');
      if (modalMensagem) {
        modalMensagem.onclick = function(e) {
          if (e.target === this) fecharModalMensagem();
        };
      }

      // Fechar modais de confirmação ao clicar fora
      var modaisConfirmacao = document.querySelectorAll('.modal-overlay');
      modaisConfirmacao.forEach(function(modal) {
        modal.onclick = function(e) {
          if (e.target === this) {
            modal.classList.remove('ativo');
            desbloquearScroll();
          }
        };
      });
    }

    // Inicializa modais imediatamente se já existirem
    inicializarModais();

    // Re-inicializa modais após carregamento via AJAX
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', inicializarModais);
    } else {
      setTimeout(inicializarModais, 50);
    }

    // Fechar modais com ESC
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' || e.key === 'Esc') {
        fecharModalCompartilhar();
        fecharModalMensagem(true);
        fecharModalFavoritos();
        fecharTodosModaisConfirmacao();
      }
    });

    // ====== Inscrição rápida ======
    function atualizarIconeInscricao(btn, inscrito) {
      if (!btn) return;
      const img = btn.querySelector('img');
      if (!img) return;
      if (inscrito) {
        img.src = '../Imagens/Circulo_check.svg';
        img.alt = 'Inscrito';
        btn.setAttribute('data-inscrito', '1');
        btn.title = 'Cancelar inscrição';
        btn.ariaLabel = 'Cancelar inscrição';
      } else {
        img.src = '../Imagens/Circulo_adicionar.svg';
        img.alt = 'Inscrever';
        btn.setAttribute('data-inscrito', '0');
        btn.title = 'Inscrever-se';
        btn.ariaLabel = 'Inscrever';
      }
    }

    async function verificarInscricao(cod, forcarAtualizacao = false) {
      if (!cod || cod <= 0) return false; // Validar código do evento
      // Se não forçar atualização e tiver no cache, usar cache
      if (!forcarAtualizacao && inscricaoCache.has(cod)) {
        return inscricaoCache.get(cod);
      }
      // Sempre verificar do servidor quando forçar atualização ou não tiver cache
      let timeoutId = null;
      try {
        const controller = new AbortController();
        timeoutId = setTimeout(() => controller.abort(), 10000); // Timeout de 10 segundos
        const r = await fetch(`../PaginasParticipante/VerificarInscricao.php?cod_evento=${cod}`, {
          credentials: 'include',
          signal: controller.signal
        });
        if (timeoutId) clearTimeout(timeoutId);
        if (!r.ok) {
          throw new Error(`HTTP error! status: ${r.status}`);
        }
        const j = await r.json();
        if (j && typeof j.inscrito !== 'undefined') {
          const val = !!j.inscrito;
          inscricaoCache.set(cod, val);
          return val;
        }
        return false;
      } catch (e) {
        if (timeoutId) clearTimeout(timeoutId);
        // Se falhar, usar cache se existir, senão retornar false
        if (e.name !== 'AbortError') {
          console.warn('Erro ao verificar inscrição:', e);
        }
        return inscricaoCache.has(cod) ? inscricaoCache.get(cod) : false;
      }
    }

    // Carregar status de inscrição de todos os eventos visíveis
    async function carregarInscricoes() {
      let timeoutId = null;
      try {
        const cards = document.querySelectorAll('.CaixaDoEvento');
        if (!cards || cards.length === 0) return;

        const codigosEventos = Array.from(cards)
          .map(card => {
            const cod = card.getAttribute('data-cod-evento');
            return cod ? Number(cod) : 0;
          })
          .filter(cod => cod > 0);

        if (codigosEventos.length === 0) return;

        // Buscar status de todas as inscrições de uma vez
        const controller = new AbortController();
        timeoutId = setTimeout(() => controller.abort(), 15000); // Timeout de 15 segundos
        const r = await fetch('../PaginasParticipante/VerificarInscricoes.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          credentials: 'include',
          body: JSON.stringify({
            eventos: codigosEventos
          }),
          signal: controller.signal
        });
        if (timeoutId) clearTimeout(timeoutId);

        if (r.status === 401) return; // Não logado
        if (!r.ok) {
          throw new Error(`HTTP error! status: ${r.status}`);
        }

        const j = await r.json();
        if (j && j.sucesso && j.inscricoes && typeof j.inscricoes === 'object') {
          // Atualizar cache e ícones
          for (const [codEvento, inscrito] of Object.entries(j.inscricoes)) {
            const cod = Number(codEvento);
            if (cod > 0 && typeof inscrito === 'boolean') {
              inscricaoCache.set(cod, inscrito);

              // Atualizar ícone do botão correspondente
              cards.forEach(card => {
                if (Number(card.getAttribute('data-cod-evento')) === cod) {
                  const btn = card.querySelector('.BotaoInscreverCard');
                  if (btn) atualizarIconeInscricao(btn, inscrito);
                }
              });
            }
          }
        }
      } catch (e) {
        // Silenciar erro - não crítico, mas logar para debug
        if (e.name !== 'AbortError') {
          console.warn('Erro ao carregar inscrições:', e);
        }
      } finally {
        // Garantir que timeout seja limpo
        if (typeof timeoutId !== 'undefined' && timeoutId) clearTimeout(timeoutId);
      }
    }

    function abrirModalConfirmarInscricao() {
      const modal = document.getElementById('modalConfirmarInscricao');
      if (!modal) return;
      modal.classList.add('ativo');
      bloquearScroll();
    }

    function fecharModalConfirmarInscricao() {
      const modal = document.getElementById('modalConfirmarInscricao');
      if (!modal) return;
      modal.classList.remove('ativo');
      desbloquearScroll();
    }

    function abrirModalConfirmarDesinscricao() {
      const modal = document.getElementById('modalConfirmarDesinscricao');
      if (!modal) return;
      modal.classList.add('ativo');
      bloquearScroll();
    }

    function fecharModalConfirmarDesinscricao() {
      const modal = document.getElementById('modalConfirmarDesinscricao');
      if (!modal) return;
      modal.classList.remove('ativo');
      desbloquearScroll();
    }

    function abrirModalInscricaoConfirmada() {
      const modal = document.getElementById('modalInscricaoConfirmada');
      if (!modal) return;
      modal.classList.add('ativo');
      bloquearScroll();
    }

    function fecharModalInscricaoConfirmada() {
      const modal = document.getElementById('modalInscricaoConfirmada');
      if (!modal) return;
      modal.classList.remove('ativo');
      desbloquearScroll();
    }

    function abrirModalDesinscricaoConfirmada() {
      const modal = document.getElementById('modalDesinscricaoConfirmada');
      if (!modal) return;
      modal.classList.add('ativo');
      bloquearScroll();
    }

    function fecharModalDesinscricaoConfirmada() {
      const modal = document.getElementById('modalDesinscricaoConfirmada');
      if (!modal) return;
      modal.classList.remove('ativo');
      desbloquearScroll();
    }

    function fecharTodosModaisConfirmacao() {
      const modais = document.querySelectorAll('.modal-overlay.ativo');
      if (modais && modais.length > 0) {
        modais.forEach(m => {
          if (m && m.classList) m.classList.remove('ativo');
        });
      }
      desbloquearScroll();
    }

    async function confirmarInscricaoRapida() {
      if (!codEventoAcao) {
        fecharModalConfirmarInscricao();
        return;
      }
      try {
        const r = await fetch('../PaginasParticipante/InscreverEvento.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          credentials: 'include',
          body: new URLSearchParams({
            cod_evento: codEventoAcao
          })
        });
        const j = await r.json();
        fecharModalConfirmarInscricao();
        if (j && j.sucesso) {
          inscricaoCache.set(codEventoAcao, true);
          atualizarIconeInscricao(btnInscreverAtual, true);
          abrirModalInscricaoConfirmada();
          // Não disparar evento inscricaoAtualizada aqui para evitar recarregamento indevido na página de início
          // O evento só deve ser disparado em páginas específicas que precisam recarregar (ex: MeusEventos)
        } else {
          alert(j.mensagem || 'Erro ao realizar inscrição.');
        }
      } catch (e) {
        fecharModalConfirmarInscricao();
        alert('Erro ao realizar inscrição.');
      }
    }

    async function confirmarDesinscricaoRapida() {
      if (!codEventoAcao) {
        fecharModalConfirmarDesinscricao();
        return;
      }
      try {
        const r = await fetch('../PaginasParticipante/DesinscreverEvento.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          credentials: 'include',
          body: new URLSearchParams({
            cod_evento: codEventoAcao
          })
        });
        const j = await r.json();
        fecharModalConfirmarDesinscricao();
        if (j && j.sucesso) {
          inscricaoCache.set(codEventoAcao, false);
          atualizarIconeInscricao(btnInscreverAtual, false);
          abrirModalDesinscricaoConfirmada();
          // Não disparar evento inscricaoAtualizada aqui para evitar recarregamento indevido na página de início
          // O evento só deve ser disparado em páginas específicas que precisam recarregar (ex: MeusEventos)
        } else {
          alert(j.mensagem || 'Erro ao cancelar inscrição.');
        }
      } catch (e) {
        fecharModalConfirmarDesinscricao();
        alert('Erro ao cancelar inscrição.');
      }
    }

    // ====== Modal de mensagem ao organizador ======
    // Variáveis globais - verificar se já existem para evitar re-declaração
    if (typeof window.codEventoMensagem === 'undefined') {
      window.codEventoMensagem = null;
    }
    if (typeof window.favoritosSet === 'undefined') {
      window.favoritosSet = new Set();
    }
    if (typeof window.favoritosDados === 'undefined') {
      window.favoritosDados = [];
    }

    // Criar referências locais usando var (permite re-declaração) para facilitar o uso
    var codEventoMensagem = window.codEventoMensagem;
    var favoritosSet = window.favoritosSet;
    var favoritosDados = window.favoritosDados;

    function atualizarContadorMensagem() {
      const textarea = document.getElementById('texto-mensagem-organizador');
      const contador = document.getElementById('contador-mensagem-organizador');
      if (!textarea || !contador) return;
      const comprimento = textarea.value.length;
      const maximo = 500;
      contador.textContent = `${comprimento} / ${maximo}`;
      if (comprimento >= maximo) {
        contador.classList.add('limite-alcancado');
      } else {
        contador.classList.remove('limite-alcancado');
      }
    }

    function abrirModalMensagem() {
      const m = document.getElementById('modal-mensagem');
      if (!m) return;
      const textarea = document.getElementById('texto-mensagem-organizador');
      if (textarea) {
        textarea.value = '';
        atualizarContadorMensagem();
        // Adicionar listener para atualizar contador em tempo real
        textarea.removeEventListener('input', atualizarContadorMensagem);
        textarea.addEventListener('input', atualizarContadorMensagem);
      }
      m.classList.add('ativo');
      bloquearScroll();
    }

    function fecharModalMensagem(skipUnlock) {
      const m = document.getElementById('modal-mensagem');
      m.classList.remove('ativo');
      if (!skipUnlock) {
        desbloquearScroll();
        // Garantir que o menu permaneça ativo após fechar o modal
        setTimeout(() => {
          const params = new URLSearchParams(window.location.search);
          const pagina = params.get('pagina') || 'inicio';
          if (typeof window.setMenuAtivoPorPagina === 'function') {
            window.setMenuAtivoPorPagina(pagina);
          }
        }, 10);
      }
    }
    async function enviarMensagemOrganizador() {
      const textarea = document.getElementById('texto-mensagem-organizador');
      if (!textarea) return;
      const texto = (textarea.value || '').trim();
      if (!codEventoMensagem) {
        fecharModalMensagem();
        return;
      }
      if (texto.length === 0) {
        alert('Digite sua mensagem.');
        return;
      }
      try {
        const r = await fetch('../PaginasGlobais/EnviarMensagemOrganizador.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          credentials: 'include',
          body: new URLSearchParams({
            cod_evento: codEventoMensagem,
            mensagem: texto
          })
        });
        const j = await r.json();
        fecharModalMensagem();
        if (j && j.sucesso) {
          alert('Mensagem enviada ao organizador!');
        } else {
          alert(j.mensagem || 'Não foi possível enviar a mensagem.');
        }
      } catch (e) {
        fecharModalMensagem();
        alert('Erro ao enviar mensagem.');
      }
    }

    // ====== Favoritos ======
    // favoritosSet e favoritosDados já foram declarados acima

    function atualizarIconeFavorito(btn, fav) {
      if (!btn) return;
      const img = btn.querySelector('img');
      if (!img) return;
      const novoSrc = fav ? '../Imagens/Medalha_preenchida.svg' : '../Imagens/Medalha_linha.svg';
      // Atualizar diretamente - navegador já tem as imagens em cache
      img.src = novoSrc;
      img.alt = fav ? 'Desfavoritar' : 'Favoritar';
      btn.title = fav ? 'Remover dos favoritos' : 'Adicionar aos favoritos';
      btn.setAttribute('data-favorito', fav ? '1' : '0');
    }

    async function carregarFavoritos() {
      let timeoutId = null;
      try {
        const controller = new AbortController();
        timeoutId = setTimeout(() => controller.abort(), 10000); // Timeout de 10 segundos
        const r = await fetch('../PaginasGlobais/ListarFavoritos.php', {
          credentials: 'include',
          signal: controller.signal
        });
        if (timeoutId) clearTimeout(timeoutId);
        if (r.status === 401) {
          favoritosSet.clear();
          favoritosDados = [];
          window.favoritosDados = [];
          return;
        }
        if (!r.ok) {
          throw new Error(`HTTP error! status: ${r.status}`);
        }
        const j = await r.json();
        if (j && j.sucesso && Array.isArray(j.favoritos)) {
          favoritosSet.clear();
          favoritosDados = j.favoritos.filter(f => f && f.cod_evento); // Filtrar favoritos inválidos
          window.favoritosDados = favoritosDados;
          for (const f of favoritosDados) {
            const cod = Number(f.cod_evento);
            if (cod > 0) favoritosSet.add(cod);
          }
          // Atualiza ícones nos cards visíveis IMEDIATAMENTE
          document.querySelectorAll('.BotaoFavoritoCard').forEach(btn => {
            const cod = Number(btn.getAttribute('data-cod'));
            if (cod && !btn.dataset.processing) {
              atualizarIconeFavorito(btn, favoritosSet.has(cod));
            }
          });
        }
      } catch (e) {
        // Logar erro para debug, mas não quebrar a aplicação
        if (e.name !== 'AbortError') {
          console.warn('Erro ao carregar favoritos:', e);
        }
      } finally {
        // Garantir que timeout seja limpo
        if (timeoutId) clearTimeout(timeoutId);
      }
    }

    function abrirModalFavoritos() {
      renderizarFavoritos();
      const modal = document.getElementById('modal-favoritos');
      if (modal) {
        modal.classList.add('ativo');
        bloquearScroll();
      }
    }

    function fecharModalFavoritos() {
      const modal = document.getElementById('modal-favoritos');
      if (modal) {
        modal.classList.remove('ativo');
        desbloquearScroll();
        // Garantir que o menu permaneça ativo após fechar o modal
        setTimeout(() => {
          const params = new URLSearchParams(window.location.search);
          const pagina = params.get('pagina') || 'inicio';
          if (typeof window.setMenuAtivoPorPagina === 'function') {
            window.setMenuAtivoPorPagina(pagina);
          }
        }, 10);
      }
    }

    function renderizarFavoritos() {
      const cont = document.getElementById('lista-favoritos');
      if (!cont) return;
      cont.innerHTML = '';
      if (!favoritosDados || favoritosDados.length === 0) {
        cont.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:var(--texto);padding:1rem;">Nenhum evento favoritado.</div>';
        return;
      }
      const frag = document.createDocumentFragment();
      favoritosDados.forEach(ev => {
        if (!ev || !ev.cod_evento) return; // Validar dados do evento
        const a = document.createElement('a');
        a.href = `ContainerParticipante.php?pagina=evento&id=${ev.cod_evento}`;
        a.className = 'favorito-item';
        a.onclick = function(e) {
          if (e.target.closest('.BotaoAcaoCard')) {
            e.preventDefault();
            return false;
          }
        };

        const divAcoes = document.createElement('div');
        divAcoes.className = 'AcoesFlutuantes';

        const btnInscrever = document.createElement('button');
        btnInscrever.type = 'button';
        btnInscrever.className = 'BotaoAcaoCard BotaoInscreverCard botao';
        btnInscrever.title = 'Inscrever-se';
        btnInscrever.setAttribute('aria-label', 'Inscrever');
        btnInscrever.setAttribute('data-cod', ev.cod_evento);
        btnInscrever.setAttribute('data-inscrito', '0');
        btnInscrever.onclick = function(e) {
          e.preventDefault();
          e.stopPropagation();
          return false;
        };
        const imgInscrever = document.createElement('img');
        imgInscrever.src = '../Imagens/Circulo_adicionar.svg';
        imgInscrever.alt = 'Inscrever';
        btnInscrever.appendChild(imgInscrever);
        divAcoes.appendChild(btnInscrever);

        const btnFavorito = document.createElement('button');
        btnFavorito.type = 'button';
        btnFavorito.className = 'BotaoAcaoCard BotaoFavoritoCard botao';
        btnFavorito.title = 'Remover dos favoritos';
        btnFavorito.setAttribute('aria-label', 'Desfavoritar');
        btnFavorito.setAttribute('data-cod', ev.cod_evento);
        btnFavorito.setAttribute('data-favorito', '1');
        btnFavorito.onclick = function(e) {
          e.preventDefault();
          e.stopPropagation();
          return false;
        };
        const imgFavorito = document.createElement('img');
        imgFavorito.src = '../Imagens/Medalha_preenchida.svg';
        imgFavorito.alt = 'Desfavoritar';
        btnFavorito.appendChild(imgFavorito);
        divAcoes.appendChild(btnFavorito);

        const btnMensagem = document.createElement('button');
        btnMensagem.type = 'button';
        btnMensagem.className = 'BotaoAcaoCard BotaoMensagemCard botao';
        btnMensagem.title = 'Enviar mensagem ao organizador';
        btnMensagem.setAttribute('aria-label', 'Mensagem');
        btnMensagem.setAttribute('data-cod', ev.cod_evento);
        btnMensagem.onclick = function(e) {
          e.preventDefault();
          e.stopPropagation();
          return false;
        };
        const imgMensagem = document.createElement('img');
        imgMensagem.src = '../Imagens/Carta.svg';
        imgMensagem.alt = 'Mensagem';
        btnMensagem.appendChild(imgMensagem);
        divAcoes.appendChild(btnMensagem);

        const btnCompartilhar = document.createElement('button');
        btnCompartilhar.type = 'button';
        btnCompartilhar.className = 'BotaoAcaoCard BotaoCompartilharCard botao';
        btnCompartilhar.title = 'Compartilhar';
        btnCompartilhar.setAttribute('aria-label', 'Compartilhar');
        btnCompartilhar.setAttribute('data-cod', ev.cod_evento);
        btnCompartilhar.onclick = function(e) {
          e.preventDefault();
          e.stopPropagation();
          return false;
        };
        const imgCompartilhar = document.createElement('img');
        imgCompartilhar.src = '../Imagens/Icone_Compartilhar.svg';
        imgCompartilhar.alt = 'Compartilhar';
        btnCompartilhar.appendChild(imgCompartilhar);
        divAcoes.appendChild(btnCompartilhar);

        const divImagem = document.createElement('div');
        divImagem.className = 'favorito-item-imagem';
        const img = document.createElement('img');
        const caminho = '../' + (ev.imagem && ev.imagem !== '' ? ev.imagem.replace(/^\\/, '').replace(/^\//, '') : 'ImagensEventos/CEU-ImagemEvento.png');
        img.src = caminho;
        img.alt = (ev.nome || 'Evento').substring(0, 100); // Limitar tamanho do alt
        img.onerror = function() {
          this.src = '../ImagensEventos/CEU-ImagemEvento.png'; // Fallback se imagem não carregar
        };
        divImagem.appendChild(img);

        const divTitulo = document.createElement('div');
        divTitulo.className = 'favorito-item-titulo';
        divTitulo.textContent = (ev.nome || 'Evento').substring(0, 100); // Limitar tamanho do título

        const divInfo = document.createElement('div');
        divInfo.className = 'favorito-item-info';
        const ul = document.createElement('ul');
        ul.className = 'evento-info-list';

        const liCategoria = document.createElement('li');
        liCategoria.className = 'evento-info-item';
        const categoria = (ev.categoria || 'N/A').replace(/</g, '&lt;').replace(/>/g, '&gt;'); // Prevenir XSS
        liCategoria.innerHTML = `<span class="evento-info-icone"><img src="../Imagens/info-categoria.svg" alt="" /></span><span class="evento-info-texto"><span class="evento-info-label">Categoria:</span> ${categoria}</span>`;
        ul.appendChild(liCategoria);

        const liModalidade = document.createElement('li');
        liModalidade.className = 'evento-info-item';
        const modalidade = (ev.modalidade || 'N/A').replace(/</g, '&lt;').replace(/>/g, '&gt;'); // Prevenir XSS
        liModalidade.innerHTML = `<span class="evento-info-icone"><img src="../Imagens/info-modalidade.svg" alt="" /></span><span class="evento-info-texto"><span class="evento-info-label">Modalidade:</span> ${modalidade}</span>`;
        ul.appendChild(liModalidade);

        if (ev.inicio) {
          const liData = document.createElement('li');
          liData.className = 'evento-info-item';
          let dataFormatada = 'N/A';
          try {
            const data = new Date(ev.inicio);
            if (!isNaN(data.getTime())) {
              dataFormatada = data.toLocaleDateString('pt-BR');
            }
          } catch (e) {
            console.error('Erro ao formatar data:', e);
          }
          liData.innerHTML = `<span class="evento-info-icone"><img src="../Imagens/info-data.svg" alt="" /></span><span class="evento-info-texto"><span class="evento-info-label">Data:</span> ${dataFormatada}</span>`;
          ul.appendChild(liData);
        }

        if (ev.lugar) {
          const liLocal = document.createElement('li');
          liLocal.className = 'evento-info-item';
          const lugar = (ev.lugar || '').replace(/</g, '&lt;').replace(/>/g, '&gt;'); // Prevenir XSS
          liLocal.innerHTML = `<span class="evento-info-icone"><img src="../Imagens/info-local.svg" alt="" /></span><span class="evento-info-texto"><span class="evento-info-label">Local:</span> ${lugar}</span>`;
          ul.appendChild(liLocal);
        }

        const liCert = document.createElement('li');
        liCert.className = 'evento-info-item';
        liCert.innerHTML = `<span class="evento-info-icone"><img src="../Imagens/info-certificado.svg" alt="" /></span><span class="evento-info-texto"><span class="evento-info-label">Certificado:</span> ${ev.certificado == 1 ? 'Sim' : 'Não'}</span>`;
        ul.appendChild(liCert);

        divInfo.appendChild(ul);
        a.appendChild(divAcoes);
        a.appendChild(divImagem);
        a.appendChild(divTitulo);
        a.appendChild(divInfo);
        frag.appendChild(a);
      });
      cont.appendChild(frag);

      // Atualizar status de inscrição nos cards de favoritos
      setTimeout(async () => {
        const codigosFavoritos = favoritosDados.map(ev => ev.cod_evento);
        if (codigosFavoritos.length === 0) return;

        try {
          const r = await fetch('../PaginasParticipante/VerificarInscricoes.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
              eventos: codigosFavoritos
            })
          });

          if (r.status === 401) return;

          const j = await r.json();
          if (j && j.sucesso && j.inscricoes) {
            for (const [codEvento, inscrito] of Object.entries(j.inscricoes)) {
              const cod = Number(codEvento);
              if (window.inscricaoCache) window.inscricaoCache.set(cod, inscrito);

              const btnInscrever = cont.querySelector(`.BotaoInscreverCard[data-cod="${cod}"]`);
              if (btnInscrever && typeof window.atualizarIconeInscricao === 'function') {
                window.atualizarIconeInscricao(btnInscrever, inscrito);
              }
            }
          }
        } catch (e) {
          // Silenciar erro
        }
      }, 100);
    }

    // Garantir que o botão de favoritos funcione sempre
    function inicializarBotaoFavoritos() {
      const btnFavoritos = document.getElementById('btn-abrir-favoritos');
      if (btnFavoritos && !btnFavoritos.dataset.listenerAdicionado) {
        btnFavoritos.addEventListener('click', async function(e) {
          e.preventDefault();
          e.stopPropagation();
          await carregarFavoritos();
          abrirModalFavoritos();
        });
        btnFavoritos.dataset.listenerAdicionado = 'true';
      }
    }

    // Inicializar imediatamente se o DOM já estiver pronto
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', inicializarBotaoFavoritos);
    } else {
      inicializarBotaoFavoritos();
    }

    // Função para adicionar listeners de clique
    function adicionarListenersDeClique() {
      // Remover listener anterior se existir
      if (window.listenerCliqueParticipante) {
        document.removeEventListener('click', window.listenerCliqueParticipante, true);
      }

      // Criar novo listener
      window.listenerCliqueParticipante = async function(e) {
        // Botão de inscrever/desinscrever
        let btnInscrever = e.target.closest('.BotaoInscreverCard');
        // Se não encontrou, pode ser que o clique foi na imagem - verificar o elemento pai
        if (!btnInscrever) {
          if (e.target.tagName === 'IMG' && e.target.closest('.AcoesFlutuantes')) {
            // Verificar se a imagem está dentro de um BotaoInscreverCard
            const img = e.target;
            let parent = img.parentElement;
            while (parent && parent !== document.body) {
              if (parent.classList && parent.classList.contains('BotaoInscreverCard')) {
                btnInscrever = parent;
                break;
              }
              parent = parent.parentElement;
            }
          }
        }
        if (btnInscrever) {
          e.preventDefault();
          e.stopPropagation();
          e.stopImmediatePropagation();
          const cod = Number(btnInscrever.getAttribute('data-cod')) || 0;
          if (!cod) return;

          // IMPORTANTE: Atualizar as variáveis ANTES de verificar status
          codEventoAcao = cod;
          window.codEventoAcao = cod;
          btnInscreverAtual = btnInscrever;
          window.btnInscreverAtual = btnInscrever;

          // IMPORTANTE: Forçar atualização do servidor para garantir dados corretos
          const inscrito = await verificarInscricao(cod, true); // forçar atualização

          // Atualizar ícone com o valor correto do servidor
          atualizarIconeInscricao(btnInscrever, inscrito);

          if (inscrito) {
            abrirModalConfirmarDesinscricao();
          } else {
            abrirModalConfirmarInscricao();
          }
          return false;
        }

        // Botão de mensagem
        const btnMsg = e.target.closest('.BotaoMensagemCard');
        if (btnMsg) {
          e.preventDefault();
          e.stopPropagation();
          e.stopImmediatePropagation();
          const cod = Number(btnMsg.getAttribute('data-cod')) || 0;
          if (!cod) return;
          codEventoMensagem = cod;
          window.codEventoMensagem = cod;
          abrirModalMensagem();
          return false;
        }

        // Botão de compartilhar
        const btnCompartilhar = e.target.closest('.BotaoCompartilharCard');
        if (btnCompartilhar) {
          e.preventDefault();
          e.stopPropagation();
          e.stopImmediatePropagation();
          const cod = Number(btnCompartilhar.getAttribute('data-cod')) || 0;
          if (!cod) return;
          codEvento = cod;
          window.codEvento = cod;
          abrirModalCompartilhar();
          return false;
        }

        // Toggle favorito
        const btnFav = e.target.closest('.BotaoFavoritoCard');
        if (btnFav) {
          e.preventDefault();
          e.stopPropagation();
          e.stopImmediatePropagation();
          if (btnFav.dataset.processing === 'true') return false;
          const cod = Number(btnFav.getAttribute('data-cod')) || 0;
          if (!cod) return;
          btnFav.dataset.processing = 'true';
          const estadoAtual = btnFav.getAttribute('data-favorito') === '1';
          const novoEstado = !estadoAtual;
          if (novoEstado) {
            favoritosSet.add(cod);
          } else {
            favoritosSet.delete(cod);
            favoritosDados = favoritosDados.filter(f => Number(f.cod_evento) !== cod);
            window.favoritosDados = favoritosDados;
          }
          atualizarIconeFavorito(btnFav, novoEstado);
          // Atualizar TODOS os botões de favorito com o mesmo código na página (atualização imediata)
          // Buscar especificamente os botões que NÃO estão no modal de favoritos
          const atualizarTodosBotoes = () => {
            const modalFavoritos = document.getElementById('modal-favoritos');
            const todosBotoes = document.querySelectorAll('.BotaoFavoritoCard');
            let atualizados = 0;
            todosBotoes.forEach(btn => {
              if (btn === btnFav || btn.dataset.processing === 'true') return;
              const estaNoModal = modalFavoritos && modalFavoritos.contains(btn);
              const btnCod = Number(btn.getAttribute('data-cod')) || 0;
              if (btnCod === cod) {
                if (modalFavoritos && modalFavoritos.contains(btnFav)) {
                  if (!estaNoModal) {
                    atualizarIconeFavorito(btn, novoEstado);
                    atualizados++;
                  }
                } else {
                  if (estaNoModal) {
                    atualizarIconeFavorito(btn, novoEstado);
                    atualizados++;
                  }
                }
              }
            });
            console.log(`Atualizados ${atualizados} botões de favorito para código ${cod}, novoEstado: ${novoEstado}`);
          };
          atualizarTodosBotoes();
          setTimeout(atualizarTodosBotoes, 100);
          setTimeout(atualizarTodosBotoes, 300);
          try {
            let timeoutId = null;
            const controller = new AbortController();
            timeoutId = setTimeout(() => controller.abort(), 10000);
            // Usar caminho absoluto baseado na origem para garantir que funcione mesmo via AJAX
            const basePath = `${window.location.origin}/CEU/PaginasGlobais/ToggleFavorito.php`;
            const r = await fetch(basePath, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
              },
              credentials: 'include',
              body: new URLSearchParams({
                cod_evento: cod
              }),
              signal: controller.signal
            });
            if (timeoutId) clearTimeout(timeoutId);
            if (r.status === 401) {
              if (estadoAtual) {
                favoritosSet.add(cod);
              } else {
                favoritosSet.delete(cod);
              }
              atualizarIconeFavorito(btnFav, estadoAtual);
              // Reverter TODOS os botões de favorito com o mesmo código na página
              document.querySelectorAll('.BotaoFavoritoCard').forEach(btn => {
                const btnCod = Number(btn.getAttribute('data-cod')) || 0;
                if (btnCod === cod && btn !== btnFav && !btn.dataset.processing) {
                  atualizarIconeFavorito(btn, estadoAtual);
                }
              });
              alert('Faça login para favoritar eventos.');
            } else if (!r.ok) {
              const text = await r.text();
              console.error('Erro HTTP:', r.status, text);
              throw new Error(`HTTP error! status: ${r.status}`);
            } else {
              let j;
              try {
                j = await r.json();
              } catch (parseErr) {
                console.error('Erro ao fazer parse do JSON:', parseErr);
                throw new Error('Resposta inválida do servidor');
              }
              if (j && j.sucesso) {
                if (j.favoritado) {
                  favoritosSet.add(cod);
                } else {
                  favoritosSet.delete(cod);
                  favoritosDados = favoritosDados.filter(f => Number(f.cod_evento) !== cod);
                  window.favoritosDados = favoritosDados;
                }
                atualizarIconeFavorito(btnFav, j.favoritado);
                // Atualizar TODOS os botões de favorito com o mesmo código na página
                // Buscar especificamente os botões que NÃO estão no modal de favoritos
                const atualizarTodosBotoes = () => {
                  const modalFavoritos = document.getElementById('modal-favoritos');
                  const todosBotoes = document.querySelectorAll('.BotaoFavoritoCard');
                  let atualizados = 0;
                  todosBotoes.forEach(btn => {
                    if (btn === btnFav || btn.dataset.processing === 'true') return;
                    const estaNoModal = modalFavoritos && modalFavoritos.contains(btn);
                    const btnCod = Number(btn.getAttribute('data-cod')) || 0;
                    if (btnCod === cod) {
                      if (modalFavoritos && modalFavoritos.contains(btnFav)) {
                        if (!estaNoModal) {
                          atualizarIconeFavorito(btn, j.favoritado);
                          atualizados++;
                        }
                      } else {
                        if (estaNoModal) {
                          atualizarIconeFavorito(btn, j.favoritado);
                          atualizados++;
                        }
                      }
                    }
                  });
                  console.log(`Atualizados ${atualizados} botões de favorito para código ${cod}, favoritado: ${j.favoritado}`);
                };
                atualizarTodosBotoes();
                setTimeout(atualizarTodosBotoes, 100);
                setTimeout(atualizarTodosBotoes, 300);
              } else {
                if (estadoAtual) {
                  favoritosSet.add(cod);
                } else {
                  favoritosSet.delete(cod);
                }
                atualizarIconeFavorito(btnFav, estadoAtual);
                // Reverter TODOS os botões de favorito com o mesmo código na página
                document.querySelectorAll('.BotaoFavoritoCard').forEach(btn => {
                  const btnCod = Number(btn.getAttribute('data-cod')) || 0;
                  if (btnCod === cod && btn !== btnFav && !btn.dataset.processing) {
                    atualizarIconeFavorito(btn, estadoAtual);
                  }
                });
                alert(j.mensagem || 'Não foi possível atualizar favorito.');
              }
            }
          } catch (err) {
            if (estadoAtual) {
              favoritosSet.add(cod);
            } else {
              favoritosSet.delete(cod);
            }
            atualizarIconeFavorito(btnFav, estadoAtual);
            // Reverter TODOS os botões de favorito com o mesmo código na página
            document.querySelectorAll('.BotaoFavoritoCard').forEach(btn => {
              const btnCod = Number(btn.getAttribute('data-cod')) || 0;
              if (btnCod === cod && btn !== btnFav && !btn.dataset.processing) {
                atualizarIconeFavorito(btn, estadoAtual);
              }
            });
            if (err.name !== 'AbortError') {
              console.error('Erro ao atualizar favorito:', err);
              alert('Erro ao atualizar favorito. Verifique sua conexão e tente novamente.');
            }
          } finally {
            btnFav.dataset.processing = 'false';
          }
          return false;
        }

        // Abrir modal de favoritos (botão no topo) - fallback caso o listener direto não funcione
        if (e.target.closest('#btn-abrir-favoritos')) {
          e.preventDefault();
          e.stopPropagation();
          e.stopImmediatePropagation();
          await carregarFavoritos();
          abrirModalFavoritos();
          return false;
        }
      };

      // Adicionar listener com capture: true
      document.addEventListener('click', window.listenerCliqueParticipante, true);
    }

    // Adicionar listeners após DOM estar pronto
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', adicionarListenersDeClique);
    } else {
      adicionarListenersDeClique();
    }
    setTimeout(adicionarListenersDeClique, 100);

    // Adicionar listeners diretos nos botões como fallback
    function adicionarListenersDiretos() {
      document.querySelectorAll('.BotaoInscreverCard').forEach(btn => {
        if (!btn.dataset.listenerDiretoAdicionado) {
          // Adicionar listener no botão
          btn.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            const cod = Number(this.getAttribute('data-cod')) || 0;
            if (!cod) return;
            codEventoAcao = cod;
            window.codEventoAcao = cod;
            btnInscreverAtual = this;
            window.btnInscreverAtual = this;
            const inscrito = await verificarInscricao(cod, true);
            atualizarIconeInscricao(this, inscrito);
            if (inscrito) {
              abrirModalConfirmarDesinscricao();
            } else {
              abrirModalConfirmarInscricao();
            }
            return false;
          }, true);

          // Adicionar listener também nas imagens dentro do botão
          const img = btn.querySelector('img');
          if (img) {
            img.addEventListener('click', async function(e) {
              e.preventDefault();
              e.stopPropagation();
              e.stopImmediatePropagation();
              const cod = Number(btn.getAttribute('data-cod')) || 0;
              if (!cod) return;
              codEventoAcao = cod;
              window.codEventoAcao = cod;
              btnInscreverAtual = btn;
              window.btnInscreverAtual = btn;
              const inscrito = await verificarInscricao(cod, true);
              atualizarIconeInscricao(btn, inscrito);
              if (inscrito) {
                abrirModalConfirmarDesinscricao();
              } else {
                abrirModalConfirmarInscricao();
              }
              return false;
            }, true);
          }

          btn.dataset.listenerDiretoAdicionado = 'true';
        }
      });
    }

    // Prevenir navegação do link quando clicar nos botões de ação
    document.querySelectorAll('.CaixaDoEvento').forEach(link => {
      link.addEventListener('click', function(e) {
        // Se o clique foi em qualquer botão de ação ou dentro de AcoesFlutuantes, prevenir navegação
        if (e.target.closest('.AcoesFlutuantes') ||
          e.target.closest('.BotaoAcaoCard') ||
          e.target.closest('.BotaoInscreverCard') ||
          e.target.closest('.BotaoFavoritoCard') ||
          e.target.closest('.BotaoMensagemCard') ||
          e.target.closest('.BotaoCompartilharCard')) {
          e.preventDefault();
          e.stopPropagation();
        }
      }, true);
    });


    // Carregar favoritos ao iniciar
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', carregarFavoritos);
    } else {
      setTimeout(carregarFavoritos, 50);
    }

    // Carregar inscrições ao iniciar
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', carregarInscricoes);
    } else {
      setTimeout(carregarInscricoes, 50);
    }

    // Inicializar botão de favoritos
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', inicializarBotaoFavoritos);
    } else {
      inicializarBotaoFavoritos();
    }
    setTimeout(inicializarBotaoFavoritos, 100);

    // Expor funções globalmente para serem chamadas após carregamento via AJAX
    window.carregarInscricoes = carregarInscricoes;
    window.carregarFavoritos = carregarFavoritos;
    window.inicializarBotaoFavoritos = inicializarBotaoFavoritos;
    window.inicializarModais = inicializarModais;
    window.confirmarInscricaoRapida = confirmarInscricaoRapida;
    window.confirmarDesinscricaoRapida = confirmarDesinscricaoRapida;
    window.atualizarIconeInscricao = atualizarIconeInscricao;
    window.abrirModalCompartilhar = abrirModalCompartilhar;
    window.fecharModalCompartilhar = fecharModalCompartilhar;
    window.abrirModalConfirmarInscricao = abrirModalConfirmarInscricao;
    window.fecharModalConfirmarInscricao = fecharModalConfirmarInscricao;
    window.abrirModalConfirmarDesinscricao = abrirModalConfirmarDesinscricao;
    window.fecharModalConfirmarDesinscricao = fecharModalConfirmarDesinscricao;
    window.abrirModalInscricaoConfirmada = abrirModalInscricaoConfirmada;
    window.fecharModalInscricaoConfirmada = fecharModalInscricaoConfirmada;
    window.abrirModalDesinscricaoConfirmada = abrirModalDesinscricaoConfirmada;
    window.fecharModalDesinscricaoConfirmada = fecharModalDesinscricaoConfirmada;
    window.abrirModalMensagem = abrirModalMensagem;
    window.fecharModalMensagem = fecharModalMensagem;
    window.enviarMensagemOrganizador = enviarMensagemOrganizador;
    window.abrirModalFavoritos = abrirModalFavoritos;
    window.fecharModalFavoritos = fecharModalFavoritos;
    window.compartilharWhatsApp = compartilharWhatsApp;
    window.compartilharInstagram = compartilharInstagram;
    window.compartilharEmail = compartilharEmail;
    window.compartilharX = compartilharX;
    window.copiarLink = copiarLink;
  </script>
</body>

</html>