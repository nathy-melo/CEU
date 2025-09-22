<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cartão do Evento</title>
  <link rel="stylesheet" href="../styleGlobal.css" />

  <?php
  // Incluir o arquivo de conexão com o banco
  include_once('../BancoDados/conexao.php');

  // Pegar o ID do evento da URL (exemplo: CartaodoEvento.php?id=1)
  $id_evento = isset($_GET['id']) ? (int)$_GET['id'] : 1; // Se não tiver ID, usa 1 como padrão

  // Buscar os dados do evento no banco
  $sql = "SELECT * FROM evento WHERE cod_evento = $id_evento";
  $resultado = mysqli_query($conexao, $sql);

  // Verificar se encontrou o evento
  if ($resultado && mysqli_num_rows($resultado) > 0) {
    $evento = mysqli_fetch_assoc($resultado);

    // Formatar as datas para exibição
    $data_inicio = date('d/m/y', strtotime($evento['inicio']));
    $data_fim = date('d/m/y', strtotime($evento['conclusao']));
    $hora_inicio = date('H:i', strtotime($evento['inicio']));
    $hora_fim = date('H:i', strtotime($evento['conclusao']));
  } else {
    // Se não encontrou o evento, usar dados padrão
    $evento = array(
      'nome' => 'Evento não encontrado',
      'lugar' => 'Local não informado',
      'descricao' => 'Descrição não disponível',
      'categoria' => 'Não informado',
      'publico_alvo' => 'Não informado',
      'certificado' => 0
    );
    $data_inicio = '00/00/00';
    $data_fim = '00/00/00';
    $hora_inicio = '00:00';
    $hora_fim = '00:00';
  }
  $certificado = (isset($evento['certificado']) && (int)$evento['certificado'] === 1) ? 'Sim' : 'Não';
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
    border-radius: 1.875rem;
    box-shadow: 0 0.15rem 0.75rem 0 rgba(0, 0, 0, 0.6);
    padding: 1.875rem;
    width: 100%;
    max-width: 51.5625rem;
    display: flex;
    flex-direction: column;
  }

  .corpo-cartao {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.875rem 0.975rem;
    align-items: start;
  }

  .grupo-campo {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
  }

  .grupo-campo label {
    font-weight: 700;
    font-size: 1.35rem;
    line-height: 1.2;
    letter-spacing: -0.02em;
  }

  .caixa-valor {
    background-color: var(--branco);
    color: var(--preto);
    border-radius: 2rem;
    padding: 0.45rem 0.9rem;
    text-align: center;
    font-size: 1.05rem;
    font-weight: 700;
    min-height: 2.025rem;
    display: flex;
    justify-content: center;
    align-items: center;
    box-shadow: 0 0.15rem 0.75rem 0 rgba(0, 0, 0, 0.5);
  }

  .campo-nome {
    grid-column: 1 / 3;
  }

  .campo-local {
    grid-column: 3 / 5;
  }

  .campo-descricao {
    grid-column: 1 / 3;
    grid-row: 3 / 4;
  }

  .caixa-descricao {
    min-height: 15.45rem;
  }

  .campo-publico {
    grid-column: 3 / 4;
    grid-row: 3 / 4;
  }

  .campo-categoria {
    grid-column: 4 / 5;
    grid-row: 3 / 4;
  }

  .rodape-cartao {
    grid-column: 3 / 5;
    grid-row: 4 / 5;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 0.75rem;
    margin-top: 0;
    justify-content: flex-end;
    height: 100%;
    align-self: stretch;
  }

  .texto-login {
    font-weight: 700;
    font-size: 1.05rem;
    line-height: 1.2;
    margin: 0;
  }

  .botao-voltar {
    background-color: var(--botao);
    color: var(--branco);
    border-radius: 0.225rem;
    padding: 0.6rem 3rem;
    text-decoration: none;
    font-weight: 700;
    font-size: 1.125rem;
    display: inline-block;
    border: none;
    cursor: pointer;
  }
</style>

<body>
  <div id="main-content">
    <main id="secao-detalhes-evento" class="secao-detalhes-evento">
      <div class="cartao-evento">
        <div class="corpo-cartao">
          <div class="grupo-campo campo-nome">
            <label for="event-name">Nome:</label>
            <div id="event-name" class="caixa-valor"><?php echo htmlspecialchars($evento['nome']); ?></div>
          </div>
          <div class="grupo-campo campo-local">
            <label for="event-local">Local:</label>
            <div id="event-local" class="caixa-valor"><?php echo htmlspecialchars($evento['lugar']); ?></div>
          </div>
          <div class="grupo-campo campo-data-inicio">
            <label for="start-date">Data de início:</label>
            <div id="start-date" class="caixa-valor"><?php echo $data_inicio; ?></div>
          </div>
          <div class="grupo-campo campo-data-fim">
            <label for="end-date">Data de fim:</label>
            <div id="end-date" class="caixa-valor"><?php echo $data_fim; ?></div>
          </div>
          <div class="grupo-campo campo-hora-inicio">
            <label for="start-time">Horário início:</label>
            <div id="start-time" class="caixa-valor"><?php echo $hora_inicio; ?></div>
          </div>
          <div class="grupo-campo campo-hora-fim">
            <label for="end-time">Horário fim:</label>
            <div id="end-time" class="caixa-valor"><?php echo $hora_fim; ?></div>
          </div>
          <div class="grupo-campo campo-descricao">
            <label for="description">Descrição:</label>
            <div id="description" class="caixa-valor caixa-descricao"><?php echo htmlspecialchars($evento['descricao']); ?></div>
          </div>
          <div class="grupo-campo campo-publico">
            <label for="audience">Público alvo:</label>
            <div id="audience" class="caixa-valor"><?php echo htmlspecialchars($evento['publico_alvo'] ?? 'Não informado'); ?></div>
            <label for="certificado">Certificado:</label>
            <div id="certificado" class="caixa-valor"><?php echo $certificado; ?></div>
          </div>
          <div class="grupo-campo campo-categoria">
            <label for="category">Categoria:</label>
            <div id="category" class="caixa-valor"><?php echo htmlspecialchars($evento['categoria']); ?></div>
          </div>
          <div class="rodape-cartao">
            <p class="texto-login">Acesse uma conta para se inscrever</p>
            <button onclick="carregarPagina('inicio')" class="botao botao-voltar">Voltar</button>
          </div>
        </div>
      </div>
    </main>
  </div>
</body>

</html>