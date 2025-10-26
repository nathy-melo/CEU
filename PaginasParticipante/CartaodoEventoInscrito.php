<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Cartão do Evento</title>
  <link rel="stylesheet" href="../styleGlobal.css" />
  <?php
  // Integração com o banco
  include_once('../BancoDados/conexao.php');

  $id_evento = isset($_GET['id']) ? (int)$_GET['id'] : 1;
  $sql = "SELECT e.*, u.Nome as nome_organizador 
          FROM evento e 
          LEFT JOIN organiza o ON e.cod_evento = o.cod_evento 
          LEFT JOIN usuario u ON o.CPF = u.CPF 
          WHERE e.cod_evento = $id_evento";
  $resultado = mysqli_query($conexao, $sql);

  // Verificar se encontrou o evento
  if ($resultado && mysqli_num_rows($resultado) > 0) {
      $evento = mysqli_fetch_assoc($resultado);

      $data_inicio = date('d/m/y', strtotime($evento['inicio']));
      $data_fim = date('d/m/y', strtotime($evento['conclusao']));
      $hora_inicio = date('H:i', strtotime($evento['inicio']));
      $hora_fim = date('H:i', strtotime($evento['conclusao']));
      $nome_organizador = isset($evento['nome_organizador']) && $evento['nome_organizador'] !== '' ? $evento['nome_organizador'] : 'Não informado';
  } else {
      // Se não encontrou o evento, usar dados padrão
      $evento = array(
          'nome' => 'Evento não encontrado',
          'lugar' => 'Local não informado',
          'descricao' => 'Descrição não disponível',
          'categoria' => 'Não informado',
          'publico_alvo' => 'Não informado',
          'certificado' => 0,
          'modalidade' => 'Presencial',
          'imagem' => 'ImagensEventos/CEU-Logo.png'
      );
      $data_inicio = '00/00/00';
      $data_fim = '00/00/00';
      $hora_inicio = '00:00';
      $hora_fim = '00:00';
      $nome_organizador = 'Não informado';
  }

  $certificado = (isset($evento['certificado']) && (int)$evento['certificado'] === 1) ? 'Sim' : 'Não';
  $modalidade = isset($evento['modalidade']) && $evento['modalidade'] !== '' ? $evento['modalidade'] : 'Presencial';

  // Ajustar caminho da imagem relativo a esta pasta
  $imagem_rel = isset($evento['imagem']) && $evento['imagem'] !== '' ? $evento['imagem'] : 'ImagensEventos/CEU-Logo.png';
  $imagem_src = '../' . ltrim($imagem_rel, "/\\");
  ?>
</head>
<style>
  .secao-detalhes-evento {
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
  }

  .cartao-evento {
    background-color: var(--caixas);
    border-radius: 2rem;
    box-shadow: 0 0.15rem 0.75rem 0 rgba(0, 0, 0, 0.4);
    padding: 2rem;
    width: 100%;
    max-width: 60rem;
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    grid-template-rows: repeat(7, auto);
    gap: 1rem;
    margin: 1rem auto 1rem auto;
  }

  /* Campos seguem grid original adaptado */
  .cartao-evento > div {
    background: none;
    border: none;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    align-items: stretch;
    font-size: 1rem;
  }

  .grupo-campo label {
    font-weight: 700;
    font-size: 1.05rem;
    line-height: 1.2;
    margin-bottom: 0.35rem;
  }

  .caixa-valor {
    background-color: var(--branco);
    color: var(--preto, #000);
    border-radius: 2rem;
    padding: 0.55rem 0.9rem;
    text-align: center;
    font-size: 0.95rem;
    font-weight: 700;
    min-height: 2.1rem;
    display: flex;
    justify-content: center;
    align-items: center;
    box-shadow: 0 0.15rem 0.75rem 0 rgba(0, 0, 0, 0.35);
  }

  .caixa-descricao {
    min-height: 14rem;
    padding: 1rem;
    text-align: left;
    line-height: 1.4;
    font-weight: 500;
    overflow-y: auto;
    resize: vertical;
    word-break: break-word;
    white-space: pre-line;
    max-width: 100%;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    padding-top: 1em;
  }

  .Nome { grid-column: span 4 / span 4; }
  .Organizador { grid-column: span 4 / span 4; grid-column-start: 5; }
  .Local { grid-column: span 8 / span 8; grid-row-start: 2; }
  .DataDeInicio { grid-column: span 2 / span 2; grid-row-start: 3; }
  .DataDeFim { grid-column: span 2 / span 2; grid-column-start: 3; grid-row-start: 3; }
  .HorarioDeInicio { grid-column: span 2 / span 2; grid-column-start: 5; grid-row-start: 3; }
  .HorarioDeFim { grid-column: span 2 / span 2; grid-column-start: 7; grid-row-start: 3; }
  .PublicoAlvo { grid-column: span 2 / span 2; grid-row-start: 4; }
  .Categoria { grid-column: span 2 / span 2; grid-column-start: 3; grid-row-start: 4; }
  .Modalidade { grid-column: span 2 / span 2; grid-column-start: 5; grid-row-start: 4; }
  .Certificado { grid-column: span 2 / span 2; grid-column-start: 7; grid-row-start: 4; }

  .Imagem {
    grid-column: span 4 / span 4;
    grid-row: span 3 / span 3;
    grid-row-start: 5;
    display: flex;
    justify-content: center;
    align-items: center;
    max-height: 16rem;
    min-height: 16rem;
    width: 100%;
    min-width: 0;
  }

  .Descricao { grid-column: span 4 / span 4; grid-row: span 3 / span 3; grid-column-start: 5; grid-row-start: 5; }

  .BotaoVoltar { grid-column: span 2 / span 2; grid-row-start: 8; }

  .BotaoDesinscrever { grid-column: span 2 / span 2; grid-column-start: 7; grid-row-start: 8; }

  /* Imagem */
  .campo-imagem {
    background: var(--branco, #fff);
    border-radius: 1.5rem;
    box-shadow: 0 0.15rem 0.75rem 0 rgba(0, 0, 0, 0.25);
    overflow: hidden;
    padding: 0;
    width: 100%;
    min-width: 0;
    height: 16rem;
    max-height: 16rem;
    min-height: 16rem;
    display: flex;
    justify-content: center;
    align-items: center;
  }

  .campo-imagem img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    margin: 0;
    padding: 0;
  }

  .botao {
    background-color: var(--botao);
    color: var(--branco, #fff);
    border-radius: 0.35rem;
    padding: 0.75rem 1.5rem;
    font-weight: 700;
    font-size: 1rem;
    border: none;
    cursor: pointer;
    box-shadow: 0 0.15rem 0.55rem 0 rgba(0, 0, 0, 0.25);
    transition: background .25s, transform .15s;
    width: 100%;
  }

  .botao:hover {
    opacity: 0.9;
    transform: translateY(-2px);
  }

  .botao-desinscrever {
    background-color: var(--vermelho);
  }

  .botao-desinscrever:hover {
    background-color: var(--vermelho-escuro);
    opacity: 1;
  }

  .modal-imagem {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0,0,0,0.85);
    z-index: 9999;
    justify-content: center;
    align-items: center;
  }

  .modal-imagem-btn-fechar {
    position: absolute;
    top: 2rem;
    right: 2rem;
    background: none;
    border: none;
    font-size: 2.5rem;
    color: var(--branco);
    cursor: pointer;
    z-index: 10001;
  }

  .modal-imagem-img {
    max-width: 90vw;
    max-height: 90vh;
    border-radius: 2rem;
    box-shadow: 0 0.5rem 2rem rgba(0,0,0,0.5);
  }

  .campo-imagem {
    cursor: pointer;
  }

  .campo-imagem:hover {
    opacity: 0.95;
  }
</style>

<body>
  <div id="main-content">
    <main id="secao-detalhes-evento" class="secao-detalhes-evento">
      <div class="cartao-evento">
        <div class="Nome grupo-campo">
          <label>Nome:</label>
          <div id="event-name" class="caixa-valor"><?= htmlspecialchars($evento['nome']) ?></div>
        </div>
        <div class="Organizador grupo-campo">
          <label>Organizado por:</label>
          <div id="event-organizer" class="caixa-valor"><?= htmlspecialchars($nome_organizador) ?></div>
        </div>
        <div class="Local grupo-campo">
          <label>Local:</label>
          <div id="event-local" class="caixa-valor"><?= htmlspecialchars($evento['lugar']) ?></div>
        </div>
        <div class="DataDeInicio grupo-campo">
          <label>Data de Início:</label>
          <div id="start-date" class="caixa-valor"><?= htmlspecialchars($data_inicio) ?></div>
        </div>
        <div class="DataDeFim grupo-campo">
          <label>Data de Fim:</label>
          <div id="end-date" class="caixa-valor"><?= htmlspecialchars($data_fim) ?></div>
        </div>
        <div class="HorarioDeInicio grupo-campo">
          <label>Horário de Início:</label>
          <div id="start-time" class="caixa-valor"><?= htmlspecialchars($hora_inicio) ?></div>
        </div>
        <div class="HorarioDeFim grupo-campo">
          <label>Horário de Fim:</label>
          <div id="end-time" class="caixa-valor"><?= htmlspecialchars($hora_fim) ?></div>
        </div>
        <div class="PublicoAlvo grupo-campo">
          <label>Público Alvo:</label>
          <div id="audience" class="caixa-valor"><?= htmlspecialchars($evento['publico_alvo']) ?></div>
        </div>
        <div class="Categoria grupo-campo">
          <label>Categoria:</label>
          <div id="category" class="caixa-valor"><?= htmlspecialchars($evento['categoria']) ?></div>
        </div>
        <div class="Modalidade grupo-campo">
          <label>Modalidade:</label>
          <div id="modality" class="caixa-valor"><?= htmlspecialchars($modalidade) ?></div>
        </div>
        <div class="Certificado grupo-campo">
          <label>Certificado:</label>
          <div id="certificate" class="caixa-valor"><?= htmlspecialchars($certificado) ?></div>
        </div>
        <div class="Imagem campo-imagem">
          <img id="event-image" src="<?= htmlspecialchars($imagem_src) ?>" alt="Imagem do evento">
        </div>
        <div class="Descricao grupo-campo">
          <label>Descrição:</label>
          <div id="description" class="caixa-valor caixa-descricao"><?= htmlspecialchars($evento['descricao']) ?></div>
        </div>
        <div class="BotaoVoltar">
          <button onclick="history.back()" class="botao">Voltar</button>
        </div>
        <div class="BotaoDesinscrever">
          <button class="botao botao-desinscrever" id="btn-desinscrever">Desinscrever-se</button>
        </div>
      </div>
    </main>

    <!-- Modal para imagem em tela cheia -->
    <div id="modal-imagem" class="modal-imagem">
      <button onclick="fecharModalImagem()" class="modal-imagem-btn-fechar">&times;</button>
      <img id="imagem-ampliada" src="" alt="Imagem ampliada" class="modal-imagem-img">
    </div>
  </div>

  <script>
    const codEvento = <?= $id_evento ?>;
    let imagens = [];

    // Carrega as imagens do evento
    async function carregarImagensEvento() {
      try {
        const response = await fetch(`BuscarImagensEvento.php?cod_evento=${codEvento}`);
        const dados = await response.json();
        
        if (dados.sucesso && dados.imagens && dados.imagens.length > 0) {
          imagens = dados.imagens.map(img => '../' + img.caminho);
        } else {
          imagens = ['<?= htmlspecialchars($imagem_src) ?>'];
        }
        
        // Atualiza a imagem inicial
        if (imagens.length > 0) {
          document.getElementById('event-image').src = imagens[0];
        }
      } catch (erro) {
        console.error('Erro ao carregar imagens:', erro);
        imagens = ['<?= htmlspecialchars($imagem_src) ?>'];
      }
    }

    // Configura o onclick da imagem
    document.getElementById('event-image').onclick = function (e) {
      e.stopPropagation();
      if (imagens.length > 0) {
        document.getElementById('imagem-ampliada').src = imagens[0];
        document.getElementById('modal-imagem').style.display = 'flex';
      }
    };

    function fecharModalImagem() {
      document.getElementById('modal-imagem').style.display = 'none';
    }

    // Fecha o modal ao clicar fora da imagem
    document.getElementById('modal-imagem').onclick = function(e) {
      if (e.target === this) {
        fecharModalImagem();
      }
    };

    // Fecha o modal ao pressionar ESC
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' || e.key === 'Esc') {
        fecharModalImagem();
      }
    });

    // Carrega as imagens quando a página é carregada
    carregarImagensEvento();
  </script>
  <script src="CartaoDoEventoInscrito.js"></script>
</body>

</html>