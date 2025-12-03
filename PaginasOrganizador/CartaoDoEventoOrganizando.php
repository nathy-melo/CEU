<?php
// Sessão e banco - DEVE VIR ANTES DE QUALQUER HTML
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Evita cache do navegador
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

include_once('../BancoDados/conexao.php');

// Aceita tanto 'id' quanto 'cod_evento' para compatibilidade
$id_evento = isset($_GET['cod_evento']) ? (int)$_GET['cod_evento'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
$cpfUsuario = $_SESSION['cpf'] ?? null;

if ($id_evento <= 0) {
  header('Location: ContainerOrganizador.php?pagina=meusEventos');
  exit;
}

// Verifica permissão (organizador OU colaborador)
$sqlPermissao = "SELECT 1 FROM organiza WHERE cod_evento = ? AND CPF = ?
                UNION
                SELECT 1 FROM colaboradores_evento WHERE cod_evento = ? AND CPF = ?
                LIMIT 1";
$stmtPermissao = mysqli_prepare($conexao, $sqlPermissao);
if ($stmtPermissao && $cpfUsuario) {
  mysqli_stmt_bind_param($stmtPermissao, "isis", $id_evento, $cpfUsuario, $id_evento, $cpfUsuario);
  mysqli_stmt_execute($stmtPermissao);
  $resultadoPermissao = mysqli_stmt_get_result($stmtPermissao);
  if (!mysqli_fetch_assoc($resultadoPermissao)) {
    mysqli_stmt_close($stmtPermissao);
    mysqli_close($conexao);
    header('Location: ContainerOrganizador.php?pagina=meusEventos');
    exit;
  }
  mysqli_stmt_close($stmtPermissao);
} else {
  mysqli_close($conexao);
  header('Location: ContainerOrganizador.php?pagina=meusEventos');
  exit;
}

// Busca dados do evento
  $sql = "SELECT e.*, u.Nome as nome_organizador 
          FROM evento e 
          LEFT JOIN organiza o ON e.cod_evento = o.cod_evento 
          LEFT JOIN usuario u ON o.CPF = u.CPF 
          WHERE e.cod_evento = ?";
  $stmt = mysqli_prepare($conexao, $sql);
  mysqli_stmt_bind_param($stmt, "i", $id_evento);
  mysqli_stmt_execute($stmt);
  $resultado = mysqli_stmt_get_result($stmt);

  // Verificar se encontrou o evento
  if ($resultado && mysqli_num_rows($resultado) > 0) {
    $evento = mysqli_fetch_assoc($resultado);

    // Formata datas e horários do evento
    $data_inicio = date('d/m/y', strtotime($evento['inicio']));
    $data_fim = date('d/m/y', strtotime($evento['conclusao']));
    $hora_inicio = date('H:i', strtotime($evento['inicio']));
    $hora_fim = date('H:i', strtotime($evento['conclusao']));

    // Datas para inputs (formato YYYY-MM-DD)
    $data_inicio_input = date('Y-m-d', strtotime($evento['inicio']));
    $data_fim_input = date('Y-m-d', strtotime($evento['conclusao']));
    $hora_inicio_input = date('H:i', strtotime($evento['inicio']));
    $hora_fim_input = date('H:i', strtotime($evento['conclusao']));

    // Datas de inscrição
    $data_inicio_inscricao = '-';
    $data_fim_inscricao = '-';
    $hora_inicio_inscricao = '-';
    $hora_fim_inscricao = '-';
    $data_inicio_inscricao_input = '';
    $data_fim_inscricao_input = '';
    $hora_inicio_inscricao_input = '';
    $hora_fim_inscricao_input = '';

    if (!empty($evento['inicio_inscricao'])) {
      $data_inicio_inscricao = date('d/m/y', strtotime($evento['inicio_inscricao']));
      $hora_inicio_inscricao = date('H:i', strtotime($evento['inicio_inscricao']));
      $data_inicio_inscricao_input = date('Y-m-d', strtotime($evento['inicio_inscricao']));
      $hora_inicio_inscricao_input = date('H:i', strtotime($evento['inicio_inscricao']));
    }
    if (!empty($evento['fim_inscricao'])) {
      $data_fim_inscricao = date('d/m/y', strtotime($evento['fim_inscricao']));
      $hora_fim_inscricao = date('H:i', strtotime($evento['fim_inscricao']));
      $data_fim_inscricao_input = date('Y-m-d', strtotime($evento['fim_inscricao']));
      $hora_fim_inscricao_input = date('H:i', strtotime($evento['fim_inscricao']));
    }

    $nome_organizador = isset($evento['nome_organizador']) && $evento['nome_organizador'] !== '' ? $evento['nome_organizador'] : 'Não informado';

    // Certificado - usa a lógica de tipos
    $tipo_certificado = isset($evento['tipo_certificado']) ? $evento['tipo_certificado'] : '';
    $tem_certificado = isset($evento['certificado']) && (int)$evento['certificado'] === 1;
    $certificado_numerico = $tem_certificado ? 1 : 0;
    
    // Modelos de certificado
    $modelo_certificado_participante = isset($evento['modelo_certificado_participante']) && !empty($evento['modelo_certificado_participante']) 
        ? $evento['modelo_certificado_participante'] 
        : 'ModeloExemplo.pptx';
    $modelo_certificado_organizador = isset($evento['modelo_certificado_organizador']) && !empty($evento['modelo_certificado_organizador']) 
        ? $evento['modelo_certificado_organizador'] 
        : 'ModeloExemploOrganizador.pptx';
    
    if ($tem_certificado) {
        // Se tem certificado, verifica o tipo
        if ($tipo_certificado === 'Ensino' || $tipo_certificado === 'Pesquisa' || $tipo_certificado === 'Extensão') {
            $certificado = $tipo_certificado;
        } else if ($tipo_certificado === 'Outro') {
            $certificado = 'Outro';
        } else {
            $certificado = 'Sim';
        }
    } else {
        $certificado = 'Não';
    }

    // Verifica se o evento já foi finalizado
    $dataHoraAtual = new DateTime();
    $dataConclusaoEvento = new DateTime($evento['conclusao']);
    $eventoFinalizado = ($dataConclusaoEvento < $dataHoraAtual);

    // Modalidade
    $modalidade = isset($evento['modalidade']) && $evento['modalidade'] !== '' ? $evento['modalidade'] : 'Presencial';

    // Imagem padrão
    $imagem_rel = (isset($evento['imagem']) && $evento['imagem'] !== '' && $evento['imagem'] !== null)
      ? $evento['imagem']
      : 'ImagensEventos/CEU-ImagemEvento.png';
    $imagem_src = '../' . ltrim($imagem_rel, "/\\");
  } else {
    mysqli_stmt_close($stmt);
    mysqli_close($conexao);
    header('Location: ContainerOrganizador.php?pagina=meusEventos');
    exit;
  }

  mysqli_stmt_close($stmt);
  mysqli_close($conexao);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Cartão do Evento</title>
  <link rel="stylesheet" href="../styleGlobal.css" />
  <link rel="stylesheet" href="../styleGlobalMobile.css" media="(max-width: 767px)" />
  <style>
    .secao-detalhes-evento {
      width: 100%;
      display: flex;
      justify-content: center;
      align-items: center;
      position: relative;
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
      grid-template-rows: repeat(8, auto);
      gap: 1rem;
      margin: 1rem auto;
    }

    .cartao-evento>div {
      background: none;
      border: none;
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
      align-items: stretch;
      font-size: 1rem;
    }

    .grupo-campo label,
    .grupo-campo .rotulo-campo {
      font-weight: 700;
      font-size: 1.05rem;
      line-height: 1.2;
      margin-bottom: 0.35rem;
    }

    /* Asterisco de campo obrigatório - esconde por padrão */
    .rotulo-campo span[style*="--vermelho"] {
      display: none;
    }

    /* Mostra asterisco apenas quando está em modo de edição */
    .cartao-evento.modo-edicao .rotulo-campo span[style*="--vermelho"] {
      display: inline;
    }

    /* Classe unificada para todos os campos de input */
    .campo-input,
    .campo-select,
    .campo-textarea {
      background-color: var(--branco);
      color: var(--preto, #000);
      border-radius: 2rem;
      padding: 0.55rem 0.9rem;
      text-align: center;
      font-size: 0.95rem;
      font-weight: 700;
      min-height: 2.1rem;
      border: 2px solid transparent;
      box-shadow: 0 0.15rem 0.75rem 0 rgba(0, 0, 0, 0.35);
      transition: all 0.3s ease;
      display: none;
      width: 100%;
    }

    .campo-input:hover,
    .campo-select:hover,
    .campo-textarea:hover {
      border-color: var(--botao, #0166ff);
      background-color: #f8f9fa;
      transform: scale(1.02);
      box-shadow: 0 0.25rem 1rem 0 rgba(1, 102, 255, 0.25);
    }

    .campo-input:focus,
    .campo-select:focus,
    .campo-textarea:focus {
      outline: none;
      border-color: var(--botao, #0166ff);
      background-color: var(--branco);
      box-shadow: 0 0 0 3px rgba(1, 102, 255, 0.15);
    }

    .campo-input::placeholder,
    .campo-textarea::placeholder {
      color: #888;
      font-weight: 500;
    }

    /* Ajustes específicos para textarea */
    .campo-textarea {
      padding: 1rem;
      text-align: left;
      font-weight: 500;
      min-height: 14rem;
      resize: vertical;
      font-family: inherit;
      line-height: 1.4;
    }

    /* Ajustes específicos para select */
    .campo-select {
      text-align-last: center;
      cursor: pointer;
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
      background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%230166ff'%3e%3cpath d='M7 10l5 5 5-5z'/%3e%3c/svg%3e");
      background-repeat: no-repeat;
      background-position: calc(100% - 0.75rem) center;
      background-size: 1.75rem;
      padding-right: 2.5rem;
    }

    .campo-select option {
      background-color: var(--fundo);
      color: var(--preto, #000);
      padding: 0.75rem;
      font-weight: 600;
      text-align: center;
      font-size: 0.95rem;
    }

    .campo-select option:disabled {
      color: #888;
      font-style: italic;
      font-weight: 500;
    }

    .campo-select:invalid,
    .campo-select[value=""] {
      color: #888;
      font-weight: 500;
    }

    .campo-select:valid:not([value=""]) {
      color: var(--preto, #000);
      font-weight: 700;
    }

    /* Ajustes específicos para inputs de data e hora */
    input[type="date"],
    input[type="time"] {
      cursor: pointer;
      width: 100%;
      padding: 0.55rem 0.9rem;
      position: relative;
    }

    input[type="date"]::-webkit-calendar-picker-indicator,
    input[type="time"]::-webkit-calendar-picker-indicator {
      cursor: pointer;
      opacity: 0.7;
      transition: opacity 0.3s;
      filter: invert(27%) sepia(88%) saturate(2598%) hue-rotate(201deg) brightness(99%) contrast(101%);
      position: absolute;
      right: 0.5rem;
      margin-left: auto;
    }

    input[type="date"]:hover::-webkit-calendar-picker-indicator,
    input[type="time"]:hover::-webkit-calendar-picker-indicator {
      opacity: 1;
    }

    input[type="date"]::-webkit-datetime-edit-month-field,
    input[type="date"]::-webkit-datetime-edit-day-field,
    input[type="date"]::-webkit-datetime-edit-year-field,
    input[type="time"]::-webkit-datetime-edit-hour-field,
    input[type="time"]::-webkit-datetime-edit-minute-field,
    input[type="time"]::-webkit-datetime-edit-ampm-field {
      color: var(--preto, #000);
      font-weight: 700;
      text-align: center;
    }

    input[type="date"]::-webkit-datetime-edit-text,
    input[type="time"]::-webkit-datetime-edit-text {
      color: var(--preto, #000);
      font-weight: 700;
    }

    .caixa-valor {
      background-color: var(--branco);
      border-radius: 2rem;
      color: var(--preto);
      padding: 0.55rem 0.9rem;
      text-align: center;
      font-size: 0.95rem;
      font-weight: 700;
      min-height: 2.1rem;
      display: flex;
      justify-content: center;
      align-items: center;
      box-shadow: 0 0.15rem 0.75rem 0 rgba(0, 0, 0, 0.35);
      width: 100%;
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

    .Nome {
      grid-column: span 4 / span 4;
    }

    .Organizador {
      grid-column: span 4 / span 4;
      grid-column-start: 5;
    }

    .campo-organizador-wrapper {
      position: relative;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .campo-organizador {
      background-color: var(--branco);
      color: var(--preto, #000);
      border-radius: 2rem;
      padding: 0.55rem 0.9rem;
      text-align: center;
      font-size: 0.95rem;
      font-weight: 700;
      min-height: 2.1rem;
      border: 2px solid transparent;
      box-shadow: 0 0.15rem 0.75rem 0 rgba(0, 0, 0, 0.35);
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: default;
      opacity: 0.9;
    }

    .btn-adicionar-colaborador {
      background-color: var(--botao);
      color: var(--branco);
      border: none;
      border-radius: 50%;
      width: 2.5rem;
      height: 2.5rem;
      min-width: 2.5rem;
      font-size: 1.5rem;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 0.15rem 0.55rem 0 rgba(0, 0, 0, 0.25);
      transition: background 0.25s, transform 0.15s;
      flex-shrink: 0;
    }

    .btn-adicionar-colaborador:hover {
      background-color: var(--botao);
      opacity: 0.9;
      transform: scale(1.05);
    }

    .btn-adicionar-colaborador:hover {
      transform: scale(0.95);
    }

    .Local {
      grid-column: span 8 / span 8;
      grid-row-start: 2;
    }

    .CargaHorariaParticipante {
      grid-column: span 4 / span 4;
      grid-row-start: 3;
    }

    .CargaHorariaOrganizador {
      grid-column: span 4 / span 4;
      grid-column-start: 5;
      grid-row-start: 3;
    }

    .DataHorarioInicio {
      grid-column: span 4 / span 4;
      grid-row-start: 4;
    }

    .DataHorarioFim {
      grid-column: span 4 / span 4;
      grid-column-start: 5;
      grid-row-start: 4;
    }

    .DataHorarioInscricaoInicio {
      grid-column: span 4 / span 4;
      grid-row-start: 5;
    }

    .DataHorarioInscricaoFim {
      grid-column: span 4 / span 4;
      grid-column-start: 5;
      grid-row-start: 5;
    }

    /* Container para campos de data e horário */
    .campo-data-horario {
      display: flex;
      gap: 0.5rem;
      align-items: stretch;
      width: 100%;
    }

    .campo-data-horario .caixa-valor {
      flex: 1;
      min-width: 0;
    }

    .campo-data-horario .caixa-valor:first-child {
      flex: 1.2;
    }

    .campo-data-horario .caixa-valor:last-child {
      flex: 0.8;
    }

    .campo-data-horario input[type="date"] {
      flex: 1.2;
      min-width: 0;
    }

    .campo-data-horario input[type="time"] {
      flex: 0.8;
      min-width: 0;
    }

    .PublicoAlvo {
      grid-column: span 2 / span 2;
      grid-row-start: 6;
    }

    .Categoria {
      grid-column: span 2 / span 2;
      grid-column-start: 3;
      grid-row-start: 6;
    }

    .Modalidade {
      grid-column: span 2 / span 2;
      grid-column-start: 5;
      grid-row-start: 6;
    }

    .Certificado {
      grid-column: span 2 / span 2;
      grid-column-start: 7;
      grid-row-start: 6;
    }

    .ModeloCertificadoParticipante {
      grid-column: span 4 / span 4;
      grid-row-start: 7;
    }

    .ModeloCertificadoOrganizador {
      grid-column: span 4 / span 4;
      grid-column-start: 5;
      grid-row-start: 7;
    }

    .Imagem {
      grid-column: span 4 / span 4;
      grid-row: span 3 / span 3;
      grid-row-start: 8;
      display: flex;
      justify-content: center;
      align-items: center;
      max-height: 16rem;
      min-height: 16rem;
    }

    .Descricao {
      grid-column: span 4 / span 4;
      grid-row: span 3 / span 3;
      grid-column-start: 5;
      grid-row-start: 8;
    }

    .BotaoVoltar {
      grid-column: span 2 / span 2;
      grid-row-start: 11;
    }

    .BotaoGerenciar {
      grid-column: span 2 / span 2;
      grid-column-start: 4;
      grid-row-start: 11;
    }

    .BotaoEditar {
      grid-column: span 2 / span 2;
      grid-column-start: 7;
      grid-row-start: 11;
    }

    .campo-imagem {
      background: var(--branco, #fff);
      border-radius: 1.5rem;
      box-shadow: 0 0.15rem 0.75rem 0 rgba(0, 0, 0, 0.25);
      overflow: hidden;
      padding: 0;
      width: 100%;
      height: 100%;
      max-height: 16rem;
      min-height: 16rem;
      display: flex;
      justify-content: center;
      align-items: center;
      position: relative;
      cursor: pointer;
      transition: background 0.3s;
    }

    .campo-imagem:hover {
      background: var(--cinza-claro, #f0f0f0);
    }

    .campo-imagem img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      margin: 0;
      padding: 0;
    }

    .campo-imagem-placeholder {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      color: #888;
      font-size: 0.95rem;
      padding: 1rem;
      z-index: 1;
    }

    .campo-imagem-placeholder img {
      width: 3rem;
      height: 3rem;
    }

    #input-imagem {
      display: none;
    }

    .botao-voltar,
    .botao-editar,
    .botao-gerenciar,
    .botao-compartilhar,
    .botao-cancelar,
    .botao-excluir,
    .botao-salvar {
      background-color: var(--botao);
      border-radius: 1rem;
      color: var(--branco);
      padding: 0.75rem 1.5rem;
      text-decoration: none;
      font-weight: 700;
      font-size: 1rem;
      display: inline-block;
      border: none;
      cursor: pointer;
      box-shadow: 0 0.15rem 0.55rem 0 rgba(0, 0, 0, 0.25);
      transition: background .25s, transform .15s;
      width: 100%;
      text-align: center;
    }

    .botao-voltar:hover,
    .botao-editar:hover,
    .botao-gerenciar:hover,
    .botao-compartilhar:hover,
    .botao-cancelar:hover,
    .botao-excluir:hover,
    .botao-salvar:hover {
      opacity: 0.9;
    }

    .botao-excluir {
      background-color: var(--vermelho);
    }

    .botao-excluir:hover {
      background-color: var(--vermelho);
    }

    .carrossel-imagens {
      position: relative;
      width: 100%;
      height: 100%;
      display: flex;
      align-items: stretch;
      justify-content: center;
    }

    .carrossel-imagens img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 1.5rem;
      box-shadow: 0 0.15rem 0.75rem 0 rgba(0, 0, 0, 0.25);
    }

    .carrossel-btn {
      background: none;
      color: var(--azul-escuro);
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      border: none;
      width: 2.5rem;
      height: 2.5rem;
      font-size: 2rem;
      cursor: pointer;
      opacity: 0.8;
      z-index: 2;
      transition: background 0.2s;
      display: none;
      align-items: center;
      justify-content: center;
    }

    .carrossel-btn:hover {
      color: var(--botao);
    }

    .carrossel-anterior {
      left: 0.5rem;
    }

    .carrossel-proxima {
      right: 0.5rem;
    }

    .btn-remover-imagem {
      position: absolute;
      top: 0.5rem;
      right: 0.5rem;
      background: rgba(255, 0, 0, 0.8);
      color: white;
      border: none;
      border-radius: 50%;
      width: 2rem;
      height: 2rem;
      font-size: 1.2rem;
      cursor: pointer;
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 3;
      transition: background 0.3s;
    }

    .btn-remover-imagem:hover {
      background: rgba(255, 0, 0, 1);
    }

    .btn-adicionar-mais {
      position: absolute;
      bottom: 0.5rem;
      left: 50%;
      transform: translateX(-50%);
      background: var(--botao);
      color: var(--branco);
      border: none;
      border-radius: 1.5rem;
      padding: 0.4rem 1rem;
      font-size: 0.85rem;
      font-weight: 600;
      cursor: pointer;
      display: none;
      align-items: center;
      justify-content: center;
      gap: 0.3rem;
      z-index: 3;
      transition: all 0.3s ease;
      box-shadow: 0 0.15rem 0.5rem rgba(0, 0, 0, 0.3);
    }

    .btn-adicionar-mais:hover {
      background: var(--botao);
      opacity: 0.9;
      transform: translateX(-50%) scale(1.05);
    }

    .btn-adicionar-mais img {
      width: 1rem;
      height: 1rem;
      filter: brightness(0) invert(1);
    }

    .modal-imagem {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(0, 0, 0, 0.85);
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

    .modal-imagem-btn-anterior {
      left: 2rem;
      top: 50%;
      position: absolute;
    }

    .modal-imagem-btn-proxima {
      right: 2rem;
      top: 50%;
      position: absolute;
    }

    .modal-imagem-img {
      max-width: 90vw;
      max-height: 90vh;
      border-radius: 2rem;
      box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.5);
    }

    /* Modal de Colaboradores */
    .modal-colaboradores {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.6);
      z-index: 10000;
      align-items: center;
      justify-content: center;
      padding: 1rem;
      backdrop-filter: blur(4px);
    }

    .modal-colaboradores .conteudo {
      background: var(--caixas);
      color: #fff;
      width: 100%;
      max-width: 42rem;
      max-height: 75vh;
      border-radius: 1rem;
      padding: 1.35rem;
      box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.35);
      display: flex;
      flex-direction: column;
      overflow-y: auto;
      overflow-x: hidden;
    }

    .modal-colaboradores .conteudo::-webkit-scrollbar {
      width: 6px;
    }

    .modal-colaboradores .conteudo::-webkit-scrollbar-track {
      background: rgba(255, 255, 255, 0.1);
      border-radius: 10px;
    }

    .modal-colaboradores .conteudo::-webkit-scrollbar-thumb {
      background: rgba(255, 255, 255, 0.3);
      border-radius: 10px;
    }

    .modal-colaboradores .conteudo::-webkit-scrollbar-thumb:hover {
      background: rgba(255, 255, 255, 0.5);
    }

    .modal-colaboradores .cabecalho {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: .5rem;
      margin-bottom: 1rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
      font-weight: 800;
      font-size: 1.15rem;
      flex-shrink: 0;
    }

    .modal-colaboradores button.fechar {
      background: none;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      color: #fff;
      transition: opacity 0.2s;
    }

    .modal-colaboradores button.fechar:hover {
      opacity: 0.7;
    }

    .linha-form {
      display: flex;
      gap: .75rem;
      margin: 0.8rem 0 1.2rem;
      align-items: stretch;
      flex-shrink: 0;
    }

    .linha-form input {
      flex: 1;
      border-radius: 1rem;
      padding: .7rem 1rem;
      border: 2px solid transparent;
      box-shadow: 0 0.15rem 0.75rem rgba(0, 0, 0, .2);
      font-size: 0.95rem;
    }

    .linha-form .btn-adicionar {
      background-color: var(--botao);
      color: var(--branco);
      border: none;
      border-radius: .5rem;
      padding: .7rem 1.25rem;
      font-weight: 700;
      font-size: 0.95rem;
      cursor: pointer;
      transition: opacity 0.2s;
      white-space: nowrap;
    }

    .linha-form .btn-adicionar:hover {
      opacity: 0.9;
    }

    .secao-titulo {
      margin: 1rem 0 0.5rem;
      font-weight: 700;
      font-size: 1rem;
      color: var(--branco);
    }

    .lista-colabs,
    .lista-solic {
      display: flex;
      flex-direction: column;
      gap: 0.3rem;
      flex: 1;
      overflow-y: auto;
      min-height: 0;
    }

    .lista-solic {
      min-height: 75px;
      max-height: 250px;
    }

    .item-colab,
    .item-solic {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: .75rem;
      background: var(--branco);
      color: var(--preto);
      border-radius: .75rem;
      padding: 0.75rem 0.6rem;
      box-shadow: 0 0.15rem 0.75rem rgba(0, 0, 0, .15);
      font-size: 0.95rem;
      min-height: 52px;
    }

    .item-solic {
      background: rgba(255, 255, 255, 0.95);
      border-left: 4px solid var(--botao);
      padding-left: 0.6rem;
    }

    .item-colab .info-colab,
    .item-solic .info-solic {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: .15rem;
    }

    .item-colab .nome-colab,
    .item-solic .nome-solic {
      font-weight: 700;
      font-size: 0.95rem;
      color: var(--preto);
    }

    .item-colab .email-colab,
    .item-solic .email-solic {
      font-size: 0.85rem;
      color: #666;
    }

    .acoes {
      display: flex;
      gap: .5rem;
      align-items: center;
    }

    .acoes .btn-aprovar {
      background-color: #28a745;
      color: var(--branco);
      border: none;
      border-radius: .4rem;
      padding: .5rem .9rem;
      font-weight: 700;
      font-size: 0.9rem;
      cursor: pointer;
      transition: opacity 0.2s;
      white-space: nowrap;
    }

    .acoes .btn-aprovar:hover {
      opacity: 0.9;
    }

    .acoes .btn-recusar,
    .acoes .btn-remover {
      background-color: var(--vermelho);
      color: var(--branco);
      border: none;
      border-radius: .4rem;
      padding: .5rem .9rem;
      font-weight: 700;
      font-size: 0.9rem;
      cursor: pointer;
      transition: opacity 0.2s;
      white-space: nowrap;
    }

    .acoes .btn-recusar:hover,
    .acoes .btn-remover:hover {
      opacity: 0.9;
    }

    .acoes .btn-sair {
      background-color: var(--vermelho);
      color: var(--branco);
      border: none;
      border-radius: .4rem;
      padding: .5rem .9rem;
      font-weight: 700;
      font-size: 0.9rem;
      cursor: pointer;
      transition: opacity 0.2s;
      white-space: nowrap;
    }

    .acoes .btn-sair:hover {
      opacity: 0.9;
    }

    .mensagem-vazio {
      text-align: center;
      padding: 1rem;
      color: #d6d6d6;
      font-style: italic;
      background: rgba(255, 255, 255, 0.05);
      border-radius: .5rem;
    }

    /* Botões de ação Í  direita do cartão */
    .botoes-acao-cartao {
      position: absolute;
      left: calc(50% + 30rem + 1rem);
      display: flex;
      flex-direction: column;
      gap: 1rem;
      z-index: 10;
    }

    .BotaoAcaoCartao {
      width: 3.5rem;
      height: 3.5rem;
      border-radius: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      border: none;
      padding: 0;
      cursor: pointer;
      transition: transform 0.2s ease, background 0.2s ease;
      box-shadow: 0 0.15rem 0.5rem rgba(0, 0, 0, 0.3);
    }

    .BotaoAcaoCartao:hover {
      transform: scale(1.1);
    }

    .BotaoAcaoCartao img {
      width: 1.75rem;
      height: 1.75rem;
      display: block;
    }

    /* Responsividade - esconder botões em telas menores */
    @media (max-width: 1200px) {
      .botoes-acao-cartao {
        left: calc(50% + 30rem + 0.5rem);
      }

      .BotaoAcaoCartao {
        width: 3rem;
        height: 3rem;
      }

      .BotaoAcaoCartao img {
        width: 1.5rem;
        height: 1.5rem;
      }
    }

    @media (max-width: 1100px) {
      .botoes-acao-cartao {
        position: static;
        transform: none;
        flex-direction: row;
        justify-content: center;
        margin-top: 1rem;
        gap: 0.75rem;
        left: auto;
      }

      .secao-detalhes-evento {
        flex-direction: column;
      }
    }

    /* Manter botões de ação visíveis quando modal está aberto */
    body.modal-aberto .botoes-acao-cartao {
      z-index: 10001 !important;
    }

    /* Container para select de modelo com botão */
    .campo-modelo-wrapper {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .campo-modelo-wrapper .campo-select {
      flex: 1;
    }

    .btn-adicionar-modelo {
      background-color: var(--botao);
      color: var(--branco);
      border: none;
      border-radius: 50%;
      width: 2.5rem;
      height: 2.5rem;
      min-width: 2.5rem;
      font-size: 1.5rem;
      font-weight: 700;
      cursor: pointer;
      display: none;
      align-items: center;
      justify-content: center;
      box-shadow: 0 0.15rem 0.55rem 0 rgba(0, 0, 0, 0.25);
      transition: background 0.25s, transform 0.15s;
      flex-shrink: 0;
    }

    .btn-adicionar-modelo:hover {
      background-color: var(--botao);
      opacity: 0.9;
      transform: scale(1.05);
    }

    .btn-adicionar-modelo:active {
      transform: scale(0.95);
    }

    .cartao-evento.modo-edicao .btn-adicionar-modelo {
      display: flex;
    }

    /* Modal de Upload de Modelo */
    .modal-template-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.7);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 10000;
    }

    .modal-template-content {
      background: var(--caixas);
      border-radius: 16px;
      padding: 0;
      width: 90%;
      max-width: 600px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
      color: var(--preto, #000);
    }

    .modal-template-header {
      padding: 24px;
      border-bottom: 1px solid rgba(0, 0, 0, 0.1);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .modal-template-header h2 {
      margin: 0;
      font-size: 20px;
      color: var(--azul-escuro);
    }

    .btn-fechar-modal-template {
      background: none;
      border: none;
      font-size: 28px;
      color: #666;
      cursor: pointer;
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: color 0.2s;
    }

    .btn-fechar-modal-template:hover {
      color: var(--preto, #000);
    }

    .modal-template-body {
      padding: 24px;
    }

    .info-modelo {
      background: rgba(1, 102, 255, 0.1);
      border-left: 4px solid var(--botao);
      padding: 16px;
      margin-bottom: 24px;
      border-radius: 8px;
      font-size: 14px;
      line-height: 1.6;
      color: var(--preto, #000);
    }

    .info-modelo h3 {
      margin: 0 0 12px 0;
      color: var(--botao);
      font-size: 16px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .info-modelo h3 svg {
      width: 20px;
      height: 20px;
      flex-shrink: 0;
    }

    .info-modelo ul {
      margin: 8px 0 0 20px;
      padding: 0;
      color: var(--preto, #000);
    }

    .info-modelo li {
      margin: 6px 0;
      color: var(--preto, #000);
    }

    .form-group-upload {
      margin-bottom: 20px;
    }

    .form-group-upload label {
      display: block;
      margin-bottom: 8px;
      color: var(--preto, #000);
      font-weight: 600;
      font-size: 14px;
    }

    .file-upload-wrapper {
      position: relative;
      display: inline-block;
      width: 100%;
    }

    .file-upload-input {
      position: absolute;
      opacity: 0;
      width: 100%;
      height: 100%;
      cursor: pointer;
    }

    .file-upload-label {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      padding: 16px;
      background: var(--branco);
      border: 2px dashed #ddd;
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.3s;
      min-height: 80px;
    }

    .file-upload-label:hover {
      background: #f8f9fa;
      border-color: var(--botao);
    }

    .file-upload-icon {
      width: 40px;
      height: 40px;
      flex-shrink: 0;
    }

    .file-upload-icon svg {
      width: 100%;
      height: 100%;
      stroke: var(--botao);
      fill: none;
    }

    .file-upload-text {
      text-align: center;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }

    .file-upload-text strong {
      display: block;
      font-size: 16px;
      margin-bottom: 4px;
      color: var(--preto, #000);
      font-weight: 600;
    }

    .file-upload-text small {
      display: block;
      font-size: 13px;
      color: #666;
    }

    .file-selected {
      margin-top: 12px;
      padding: 12px;
      background: rgba(76, 175, 80, 0.1);
      border: 1px solid rgba(76, 175, 80, 0.3);
      border-radius: 8px;
      color: #2e7d32;
      font-size: 14px;
      font-weight: 500;
    }

    .modal-template-footer {
      display: flex;
      gap: 12px;
      justify-content: flex-end;
      padding: 24px;
      border-top: 1px solid rgba(0, 0, 0, 0.1);
    }

    .btn-modal-template {
      padding: 12px 24px;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: opacity 0.2s;
    }

    .btn-modal-template:hover {
      opacity: 0.9;
    }

    .btn-modal-template:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    .btn-cancelar-template {
      background-color: var(--vermelho);
      color: var(--branco);
    }

    .btn-enviar-template {
      background-color: var(--verde);
      color: var(--branco);
    }

    /* Bloqueia scroll da página quando modal está aberto */
    body.modal-template-aberto {
      overflow: hidden;
    }

    /* Modal Adicionar Modelo de Certificado */
    .modal-template-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background-color: rgba(0, 0, 0, 0.6);
      z-index: 10000;
      backdrop-filter: blur(4px);
    }

    .modal-template-overlay.ativo {
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .modal-template-content {
      background: var(--branco);
      border-radius: 12px;
      padding: 0;
      width: 90%;
      max-width: 600px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
      position: relative;
      z-index: 10001;
    }

    .modal-template-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 24px;
      border-bottom: 1px solid rgba(0, 0, 0, 0.1);
      color: #6598D2;
    }

    .modal-template-header h2 {
      margin: 0;
      color: #6598D2;
      font-size: 24px;
    }

    .btn-fechar-modal-template {
      background: none;
      border: none;
      font-size: 28px;
      color: var(--azul-escuro);
      cursor: pointer;
      padding: 0;
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 4px;
      transition: background-color 0.2s ease;
    }

    .btn-fechar-modal-template:hover {
      background-color: rgba(0, 0, 0, 0.1);
    }

    .modal-template-body {
      padding: 24px;
      color: #000;
    }

    .info-modelo {
      background: rgba(1, 102, 255, 0.1);
      border-left: 4px solid var(--botao);
      padding: 16px;
      margin-bottom: 24px;
      border-radius: 8px;
      font-size: 14px;
      line-height: 1.6;
      color: #000;
    }

    .info-modelo h3 {
      margin: 0 0 12px 0;
      color: var(--botao);
      font-size: 16px;
      font-weight: 600;
    }

    .info-modelo ul {
      margin: 8px 0 0 20px;
      padding: 0;
      color: #000;
    }

    .info-modelo li {
      margin: 6px 0;
      color: #000;
    }

    .form-group-upload {
      margin-bottom: 20px;
    }

    .form-group-upload label {
      display: block;
      margin-bottom: 8px;
      color: #6598D2;
      font-weight: 600;
      font-size: 14px;
    }

    .file-upload-wrapper {
      position: relative;
      display: inline-block;
      width: 100%;
    }

    .file-upload-input {
      position: absolute;
      opacity: 0;
      width: 100%;
      height: 100%;
      cursor: pointer;
    }

    .file-upload-label {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      padding: 16px;
      background: var(--branco);
      border: 2px dashed #ddd;
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.3s;
      min-height: 80px;
      flex-direction: column;
    }

    .file-upload-label:hover {
      background: #f8f9fa;
      border-color: var(--botao);
    }

    .file-upload-icon {
      width: 48px;
      height: 48px;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .file-upload-icon svg {
      width: 100%;
      height: 100%;
      fill: var(--botao);
    }

    .file-upload-text {
      text-align: center;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }

    .file-upload-text strong {
      display: block;
      font-size: 16px;
      margin-bottom: 4px;
      color: #000;
      font-weight: 600;
    }

    .file-upload-text small {
      display: block;
      font-size: 13px;
      color: #666;
    }

    .file-selected {
      margin-top: 12px;
      padding: 12px;
      background: rgba(76, 175, 80, 0.1);
      border: 1px solid rgba(76, 175, 80, 0.3);
      border-radius: 8px;
      color: #2e7d32;
      font-size: 14px;
      font-weight: 500;
    }

    .modal-template-footer {
      display: flex;
      gap: 12px;
      justify-content: flex-end;
      padding: 24px;
      border-top: 1px solid rgba(0, 0, 0, 0.1);
      margin-top: 28px;
    }

    .btn-modal-template {
      padding: 12px 24px;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: opacity 0.2s ease;
    }

    .btn-modal-template:hover {
      opacity: 0.9;
    }

    .btn-modal-template:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    .btn-cancelar-template {
      background-color: var(--vermelho);
      color: var(--branco);
    }

    .btn-enviar-template {
      background-color: var(--verde);
      color: var(--branco);
    }
  </style>
</head>

<body>
  <div id="main-content">
    <main id="secao-detalhes-evento" class="secao-detalhes-evento">
      <!-- Botões de ação Í  direita do cartão -->
      <div class="botoes-acao-cartao">
        <button type="button" class="BotaoAcaoCartao BotaoFavoritoCartao" title="Favoritar" aria-label="Favoritar"
          data-cod="<?= $id_evento ?>" data-favorito="0">
          <img src="../Imagens/Medalha_linha.svg" alt="Favoritar">
        </button>
        <button type="button" class="BotaoAcaoCartao BotaoMensagemCartao" title="Enviar mensagem ao organizador"
          aria-label="Mensagem" data-cod="<?= $id_evento ?>">
          <img src="../Imagens/Carta.svg" alt="Mensagem">
        </button>
        <button type="button" class="BotaoAcaoCartao BotaoCompartilharCartao" title="Compartilhar"
          aria-label="Compartilhar" data-cod="<?= $id_evento ?>">
          <img src="../Imagens/Icone_Compartilhar.svg" alt="Compartilhar" />
        </button>
      </div>
      <div class="cartao-evento">
        <div class="Nome grupo-campo">
          <span class="rotulo-campo">Nome: <span style="color: var(--vermelho);">*</span></span>
          <div id="event-name" class="caixa-valor"><?php echo htmlspecialchars($evento['nome']); ?></div>
          <input type="text" id="input-nome" class="campo-input" placeholder="Digite o nome do evento" autocomplete="off">
        </div>
        <div class="Organizador grupo-campo">
          <span class="rotulo-campo">Organizado por:</span>
          <div class="campo-organizador-wrapper">
            <div class="campo-organizador"><?php echo htmlspecialchars($nome_organizador); ?></div>
            <button type="button" class="btn-adicionar-colaborador" onclick="abrirModalColaboradores()" title="Adicionar organizador">+</button>
          </div>
        </div>
        <div class="Local grupo-campo">
          <span class="rotulo-campo">Local: <span style="color: var(--vermelho);">*</span></span>
          <div id="event-local" class="caixa-valor"><?php echo htmlspecialchars($evento['lugar']); ?></div>
          <input type="text" id="input-local" class="campo-input" placeholder="Digite o local do evento" autocomplete="off">
        </div>
        <div class="CargaHorariaParticipante grupo-campo">
          <span class="rotulo-campo">Carga Horária do Participante: <span style="color: var(--vermelho);">*</span></span>
          <div class="caixa-valor" id="carga-horaria-participante-visualizacao"><?php 
            $carga_horaria = isset($evento['duracao']) && $evento['duracao'] > 0 ? floatval($evento['duracao']) : 0;
            $horas = intval($carga_horaria);
            $minutos = round(($carga_horaria - $horas) * 60);
            echo str_pad($horas, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutos, 2, '0', STR_PAD_LEFT);
          ?></div>
          <input type="text" id="input-carga-horaria-participante" class="campo-input" placeholder="Ex: 08:00" pattern="[0-9]{2}:[0-9]{2}" title="Formato: HH:MM" value="<?php echo str_pad($horas, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutos, 2, '0', STR_PAD_LEFT); ?>" autocomplete="off" required>
        </div>
        <div class="CargaHorariaOrganizador grupo-campo">
          <span class="rotulo-campo">Carga Horária do Organizador:</span>
          <div class="caixa-valor" id="carga-horaria-organizador-visualizacao"><?php 
            // Se duracao_organizador é NULL ou 0, copia do participante
            $carga_horaria_org = isset($evento['duracao_organizador']) && $evento['duracao_organizador'] !== null && $evento['duracao_organizador'] > 0 
              ? floatval($evento['duracao_organizador']) 
              : $carga_horaria; // Copia da duração do participante
            $horas_org = intval($carga_horaria_org);
            $minutos_org = round(($carga_horaria_org - $horas_org) * 60);
            echo str_pad($horas_org, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutos_org, 2, '0', STR_PAD_LEFT);
          ?></div>
          <input type="text" id="input-carga-horaria-organizador" class="campo-input" placeholder="Ex: 16:00" pattern="[0-9]{2}:[0-9]{2}" title="Formato: HH:MM" value="<?php echo str_pad($horas_org, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutos_org, 2, '0', STR_PAD_LEFT); ?>" autocomplete="off">
        </div>
        <div class="DataHorarioInicio grupo-campo">
          <span class="rotulo-campo">Data e Horário de Início do Evento: <span style="color: var(--vermelho);">*</span></span>
          <div class="campo-data-horario" id="campo-data-horario-inicio-visualizacao">
            <div id="start-date" class="caixa-valor"><?php echo $data_inicio; ?></div>
            <div id="start-time" class="caixa-valor"><?php echo $hora_inicio; ?></div>
          </div>
          <div class="campo-data-horario" id="campo-data-horario-inicio-edicao" style="display: none;">
            <input type="date" id="input-data-inicio" class="campo-input" value="<?php echo $data_inicio_input; ?>" min="2025-11-20" max="2026-12-31" autocomplete="off" required>
            <input type="time" id="input-horario-inicio" class="campo-input" value="<?php echo $hora_inicio_input; ?>" autocomplete="off" required>
          </div>
        </div>
        <div class="DataHorarioFim grupo-campo">
          <span class="rotulo-campo">Data e Horário de Fim do Evento: <span style="color: var(--vermelho);">*</span></span>
          <div class="campo-data-horario" id="campo-data-horario-fim-visualizacao">
            <div id="end-date" class="caixa-valor"><?php echo $data_fim; ?></div>
            <div id="end-time" class="caixa-valor"><?php echo $hora_fim; ?></div>
          </div>
          <div class="campo-data-horario" id="campo-data-horario-fim-edicao" style="display: none;">
            <input type="date" id="input-data-fim" class="campo-input" value="<?php echo $data_fim_input; ?>" min="2025-11-20" max="2026-12-31" autocomplete="off" required>
            <input type="time" id="input-horario-fim" class="campo-input" value="<?php echo $hora_fim_input; ?>" autocomplete="off" required>
          </div>
        </div>
        <div class="DataHorarioInscricaoInicio grupo-campo">
          <span class="rotulo-campo">Início das Inscrições: <span style="color: var(--vermelho);">*</span></span>
          <div class="campo-data-horario" id="campo-data-horario-inscricao-inicio-visualizacao">
            <div id="inicio-inscricao" class="caixa-valor"><?php echo htmlspecialchars($data_inicio_inscricao); ?></div>
            <div id="horario-inicio-inscricao" class="caixa-valor"><?php echo htmlspecialchars($hora_inicio_inscricao); ?></div>
          </div>
          <div class="campo-data-horario" id="campo-data-horario-inscricao-inicio-edicao" style="display: none;">
            <input type="date" id="input-data-inicio-inscricao" class="campo-input" value="<?php echo $data_inicio_inscricao_input; ?>" min="2025-11-20" max="2026-12-31" autocomplete="off" required>
            <input type="time" id="input-horario-inicio-inscricao" class="campo-input" value="<?php echo $hora_inicio_inscricao_input; ?>" autocomplete="off" required>
          </div>
        </div>
        <div class="DataHorarioInscricaoFim grupo-campo">
          <span class="rotulo-campo">Fim das Inscrições: <span style="color: var(--vermelho);">*</span></span>
          <div class="campo-data-horario" id="campo-data-horario-inscricao-fim-visualizacao">
            <div id="fim-inscricao" class="caixa-valor"><?php echo htmlspecialchars($data_fim_inscricao); ?></div>
            <div id="horario-fim-inscricao" class="caixa-valor"><?php echo htmlspecialchars($hora_fim_inscricao); ?></div>
          </div>
          <div class="campo-data-horario" id="campo-data-horario-inscricao-fim-edicao" style="display: none;">
            <input type="date" id="input-data-fim-inscricao" class="campo-input" value="<?php echo $data_fim_inscricao_input; ?>" min="2025-11-20" max="2026-12-31" autocomplete="off" required>
            <input type="time" id="input-horario-fim-inscricao" class="campo-input" value="<?php echo $hora_fim_inscricao_input; ?>" autocomplete="off" required>
          </div>
        </div>
        <div class="PublicoAlvo grupo-campo">
          <span class="rotulo-campo">Público alvo: <span style="color: var(--vermelho);">*</span></span>
          <div id="audience" class="caixa-valor"><?php echo htmlspecialchars($evento['publico_alvo'] ?? 'Não informado'); ?></div>
          <input type="text" id="input-publico-alvo" class="campo-input" placeholder="Ex: Estudantes" autocomplete="off">
        </div>
        <div class="Categoria grupo-campo">
          <span class="rotulo-campo">Categoria: <span style="color: var(--vermelho);">*</span></span>
          <div id="category" class="caixa-valor"><?php echo htmlspecialchars($evento['categoria'] ?? ''); ?></div>
          <select id="input-categoria" class="campo-select" autocomplete="off">
            <option value="">Selecione</option>
            <option value="Palestra" <?php echo ($evento['categoria'] ?? '') === 'Palestra' ? 'selected' : ''; ?>>Palestra</option>
            <option value="Workshop" <?php echo ($evento['categoria'] ?? '') === 'Workshop' ? 'selected' : ''; ?>>Workshop</option>
            <option value="Seminario" <?php echo ($evento['categoria'] ?? '') === 'Seminario' ? 'selected' : ''; ?>>Seminário</option>
            <option value="Conferencia" <?php echo ($evento['categoria'] ?? '') === 'Conferencia' ? 'selected' : ''; ?>>Conferência</option>
            <option value="Curso" <?php echo ($evento['categoria'] ?? '') === 'Curso' ? 'selected' : ''; ?>>Curso</option>
            <option value="Treinamento" <?php echo ($evento['categoria'] ?? '') === 'Treinamento' ? 'selected' : ''; ?>>Treinamento</option>
            <option value="Hackathon" <?php echo ($evento['categoria'] ?? '') === 'Hackathon' ? 'selected' : ''; ?>>Hackathon</option>
            <option value="Outro" <?php echo ($evento['categoria'] ?? '') === 'Outro' ? 'selected' : ''; ?>>Outro</option>
          </select>
        </div>
        <div class="Modalidade grupo-campo">
          <span class="rotulo-campo">Modalidade: <span style="color: var(--vermelho);">*</span></span>
          <div id="modality" class="caixa-valor"><?php echo htmlspecialchars($modalidade); ?></div>
          <select id="input-modalidade" class="campo-select" autocomplete="off">
            <option value="">Selecione</option>
            <option value="Presencial" <?php echo $modalidade === 'Presencial' ? 'selected' : ''; ?>>Presencial</option>
            <option value="Online" <?php echo $modalidade === 'Online' ? 'selected' : ''; ?>>Online</option>
            <option value="Híbrido" <?php echo $modalidade === 'Híbrido' ? 'selected' : ''; ?>>Híbrido</option>
          </select>
        </div>
        <div class="Certificado grupo-campo">
          <span class="rotulo-campo">Certificado: <span style="color: var(--vermelho);">*</span></span>
          <div id="certificate" class="caixa-valor"><?php echo htmlspecialchars($certificado); ?></div>
          <select id="input-certificado" class="campo-select" autocomplete="off">
            <option value="">Selecione</option>
            <option value="Sem certificacao" <?php echo $certificado_numerico === 0 && $certificado !== 'Ensino' && $certificado !== 'Pesquisa' && $certificado !== 'Extensão' && $certificado !== 'Outro' ? 'selected' : ''; ?>>Sem certificação</option>
            <option value="Ensino" <?php echo $certificado === 'Ensino' ? 'selected' : ''; ?>>Ensino</option>
            <option value="Pesquisa" <?php echo $certificado === 'Pesquisa' ? 'selected' : ''; ?>>Pesquisa</option>
            <option value="Extensao" <?php echo $certificado === 'Extensão' || $certificado === 'Extensao' ? 'selected' : ''; ?>>Extensão</option>
            <option value="Outro" <?php echo $certificado === 'Outro' ? 'selected' : ''; ?>>Outro</option>
          </select>
        </div>
        <div class="ModeloCertificadoParticipante grupo-campo">
          <span class="rotulo-campo">Modelo de Certificado (Participante):</span>
          <div id="modelo-participante" class="caixa-valor"><?php echo ($modelo_certificado_participante === 'ModeloExemplo.pptx' ? 'Modelo Padrão' : htmlspecialchars(basename($modelo_certificado_participante))); ?></div>
          <div class="campo-modelo-wrapper">
            <select id="input-modelo-certificado-participante" class="campo-select" autocomplete="off">
              <option value="ModeloExemplo.pptx" <?php echo $modelo_certificado_participante === 'ModeloExemplo.pptx' ? 'selected' : ''; ?>>Modelo Padrão</option>
            </select>
            <button type="button" class="btn-adicionar-modelo" onclick="abrirModalTemplate('participante')" title="Adicionar modelo personalizado">+</button>
          </div>
        </div>
        <div class="ModeloCertificadoOrganizador grupo-campo">
          <span class="rotulo-campo">Modelo de Certificado (Organizador):</span>
          <div id="modelo-organizador" class="caixa-valor"><?php echo ($modelo_certificado_organizador === 'ModeloExemploOrganizador.pptx' ? 'Modelo Padrão' : htmlspecialchars(basename($modelo_certificado_organizador))); ?></div>
          <div class="campo-modelo-wrapper">
            <select id="input-modelo-certificado-organizador" class="campo-select" autocomplete="off">
              <option value="ModeloExemploOrganizador.pptx" <?php echo $modelo_certificado_organizador === 'ModeloExemploOrganizador.pptx' ? 'selected' : ''; ?>>Modelo Padrão</option>
            </select>
            <button type="button" class="btn-adicionar-modelo" onclick="abrirModalTemplate('organizador')" title="Adicionar modelo personalizado">+</button>
          </div>
        </div>
        <div class="Imagem grupo-campo">
          <div class="campo-imagem" id="campo-imagem">
            <div class="campo-imagem-placeholder" id="placeholder-imagem" style="display: none;">
              <img src="../Imagens/AdicionarImagem.svg" alt="Adicionar imagem" />
              <span>Clique para adicionar imagens</span>
            </div>
            <div class="carrossel-imagens" id="carrossel-imagens">
              <button type="button" class="btn-remover-imagem" id="btn-remover-imagem">&times;</button>
              <button type="button" class="carrossel-btn carrossel-anterior" id="btn-anterior">◄</button>
              <img id="imagem-carrossel" src="<?php echo htmlspecialchars($imagem_src); ?>" alt="Imagem do evento" />
              <button type="button" class="carrossel-btn carrossel-proxima" id="btn-proxima">►</button>
              <button type="button" class="btn-adicionar-mais" id="btn-adicionar-mais-imagens">
                <img src="../Imagens/AdicionarMais.svg" alt="" aria-hidden="true" />
                Adicionar mais imagens
              </button>
            </div>
          </div>
          <input type="file" id="input-imagem" name="imagens_evento" accept="image/*" multiple onchange="adicionarImagens(event)" autocomplete="off">
        </div>
        <div class="Descricao grupo-campo">
          <span class="rotulo-campo">Descrição: <span style="color: var(--vermelho);">*</span></span>
          <div id="description" class="caixa-valor caixa-descricao"><?php echo htmlspecialchars($evento['descricao']); ?></div>
          <textarea id="input-descricao" class="campo-textarea" placeholder="Descreva o evento..." autocomplete="off"><?php echo htmlspecialchars($evento['descricao']); ?></textarea>
        </div>
        <div class="BotaoVoltar">
          <button id="btn-voltar" class="botao botao-voltar">Voltar</button>
        </div>
        <div class="BotaoGerenciar">
          <button id="btn-gerenciar" class="botao botao-gerenciar">Gerenciar</button>
        </div>
        <div class="BotaoEditar">
          <button id="btn-editar" class="botao botao-editar">Editar</button>
        </div>
      </div>
    </main>

    <div id="modal-imagem" class="modal-imagem">
      <button onclick="fecharModalImagem()" class="modal-imagem-btn-fechar">&times;</button>
      <button class="carrossel-btn carrossel-anterior modal-imagem-btn-anterior" onclick="mudarImagemModal(-1)">◄</button>
      <img id="imagem-ampliada" src="" alt="Imagem ampliada" class="modal-imagem-img" />
      <button class="carrossel-btn carrossel-proxima modal-imagem-btn-proxima" onclick="mudarImagemModal(1)">►</button>
    </div>
  </div>

  <!-- Modal Colaboradores -->
  <div id="modal-colaboradores" class="modal-colaboradores" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="conteudo">
      <div class="cabecalho">
        <span>Organização do Evento</span>
        <button type="button" class="fechar" onclick="fecharModalColaboradores()" aria-label="Fechar">×</button>
      </div>

      <div class="linha-form">
        <input id="input-identificador-colab" type="text" placeholder="CPF (11 dígitos) ou Email" autocomplete="off" />
        <button class="btn-adicionar" onclick="adicionarColaboradorEvento()">Adicionar</button>
      </div>

      <div class="secao-titulo">Solicitações pendentes</div>
      <div id="lista-solicitacoes" class="lista-solic"></div>

      <div class="secao-titulo">Organizadores</div>
      <div id="lista-colaboradores" class="lista-colabs"></div>
    </div>
  </div>

  <!-- Modal Mensagem ao Organizador -->
  <div id="modal-mensagem" class="modal-overlay">
    <div class="modal-container" onclick="event.stopPropagation()">
      <div class="modal-cabecalho">
        <h2 class="modal-titulo">Enviar mensagem ao organizador</h2>
        <button type="button" class="modal-btn-fechar" onclick="fecharModalMensagem()" aria-label="Fechar">&times;</button>
      </div>
      <div class="modal-corpo">
        <textarea id="texto-mensagem-organizador" class="modal-mensagem-textarea" maxlength="500"
          placeholder="Escreva sua mensagem (máx. 500 caracteres)"></textarea>
        <div id="contador-mensagem-organizador" class="contador-caracteres">0 / 500</div>
        <div class="modal-mensagem-acoes">
          <button class="modal-btn cancelar" type="button" onclick="fecharModalMensagem()">Cancelar</button>
          <button class="modal-btn enviar" type="button" onclick="enviarMensagemOrganizador()">Enviar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Compartilhar -->
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
              <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
            </svg>
          </div>
          <span>WhatsApp</span>
        </button>

        <button class="btn-compartilhar-app" onclick="compartilharInstagram()" title="Compartilhar no Instagram">
          <div class="icone-app icone-instagram">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
            </svg>
          </div>
          <span>Instagram</span>
        </button>

        <button class="btn-compartilhar-app" onclick="compartilharEmail()" title="Compartilhar por E-mail">
          <div class="icone-app icone-email">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
              <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
            </svg>
          </div>
          <span>E-mail</span>
        </button>

        <button class="btn-compartilhar-app" onclick="compartilharX()" title="Compartilhar no X (Twitter)">
          <div class="icone-app icone-x">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
              <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
            </svg>
          </div>
          <span>X</span>
        </button>

        <button class="btn-compartilhar-app" onclick="copiarLink()" title="Copiar Link">
          <div class="icone-app icone-copiar" id="icone-copiar">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor">
              <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z" />
            </svg>
          </div>
          <span id="texto-copiar">Copiar</span>
        </button>
      </div>

      <div class="campo-link">
        <input type="text" id="link-inscricao" readonly />
      </div>

      <div class="modal-alerta info">
        <strong>ℹ️ Informação:</strong> Este link é para <strong>inscrição no evento</strong>. Para adicionar um novo organizador, use o botão <strong>"+"</strong> ao lado campo "Organizado por:".
      </div>
      </div>
    </div>
  </div>

  <!-- Modal Upload Modelo de Certificado -->
  <div id="modal-template" class="modal-template-overlay" onclick="fecharModalTemplateSeForFundo(event)">
    <div class="modal-template-content" onclick="event.stopPropagation()">
      <div class="modal-template-header">
        <h2>Adicionar Modelo de Certificado</h2>
        <button class="btn-fechar-modal-template" onclick="fecharModalTemplate()">&times;</button>
      </div>
      <div class="modal-template-body">
        <div class="info-modelo">
          <h3>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; margin-right: 8px; vertical-align: middle;">
              <path d="M9 11H7v6h2M13 11h-2v6h2M17 11h-2v6h2M9.5 3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V9"/><circle cx="18.5" cy="5.5" r="2.5"/>
            </svg>
            Informações Importantes
          </h3>
          <ul>
            <li><strong>Formatos aceitos:</strong> PPTX, PPT, ODP</li>
            <li><strong>Tamanho máximo:</strong> 50MB</li>
            <li><strong>O modelo deve conter marcadores de texto</strong> que serão substituídos pelos dados do certificado (Nome, Evento, Data, etc.)</li>
            <li>O arquivo será salvo na pasta de templates e ficará disponível para todos os seus eventos</li>
          </ul>
        </div>
        <div class="form-group-upload">
          <label>Selecione o arquivo do modelo</label>
          <div class="file-upload-wrapper">
            <input type="file" id="template-file-input" class="file-upload-input" accept=".pptx,.ppt,.odp" onchange="arquivoTemplateSelecionado(event)">
            <label for="template-file-input" class="file-upload-label">
              <div class="file-upload-icon">
                <svg viewBox="0 0 24 24" fill="currentColor" stroke="none">
                  <path d="M20 6h-8l-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2z" />
                </svg>
              </div>
              <div class="file-upload-text">
                <strong>Clique para selecionar</strong>
                <small>ou arraste o arquivo aqui</small>
              </div>
            </label>
          </div>
          <div id="file-selected-info" class="file-selected" style="display: none;">
            <strong>✓ Arquivo selecionado:</strong> <span id="file-name"></span>
          </div>
        </div>
      </div>
      <div class="modal-template-footer">
        <button type="button" class="btn-modal-template btn-cancelar-template" onclick="fecharModalTemplate()">Cancelar</button>
        <button type="button" class="btn-modal-template btn-enviar-template" id="btn-enviar-template" onclick="enviarModeloTemplate()" disabled>Enviar</button>
      </div>
    </div>
  </div>

  <script>
    // Passa o ID do evento para o JavaScript
    window.codEventoAtual = <?php echo $id_evento; ?>;
    var codEvento = <?php echo $id_evento; ?>;

    // ====== Variáveis globais ======
    if (typeof window.favoritosSet === 'undefined') {
      window.favoritosSet = new Set();
    }
    if (typeof window.codEventoMensagem === 'undefined') {
      window.codEventoMensagem = null;
    }
    if (typeof window.codEvento === 'undefined') {
      window.codEvento = null;
    }
    window.codEvento = codEvento;
    var favoritosSet = window.favoritosSet;
    var codEventoMensagem = window.codEventoMensagem;
    var codEventoCompartilhar = codEvento;

    // ====== Funções de bloqueio/desbloqueio de scroll ======
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
      const elementoAtivo = document.hoverElement;
      const isInputOuTextarea = elementoAtivo && (elementoAtivo.tagName === 'TEXTAREA' || elementoAtivo.tagName === 'INPUT');
      const teclas = [33, 34, 35, 36, 37, 38, 39, 40];
      if (e.keyCode === 32 && isInputOuTextarea) return;
      if (teclas.includes(e.keyCode)) e.preventDefault();
    }

    // ====== Funções de Favorito ======
    function atualizarIconeFavorito(btn, fav) {
      if (!btn) return;
      const img = btn.querySelector('img');
      if (!img) return;
      const novoSrc = fav ? '../Imagens/Medalha_preenchida.svg' : '../Imagens/Medalha_linha.svg';
      img.src = novoSrc;
      img.alt = fav ? 'Desfavoritar' : 'Favoritar';
      btn.title = fav ? 'Remover dos favoritos' : 'Adicionar aos favoritos';
      btn.setAttribute('data-favorito', fav ? '1' : '0');
    }

    async function carregarFavoritos() {
      let timeoutId = null;
      try {
        const controller = new AbortController();
        timeoutId = setTimeout(() => controller.abort(), 10000);
        const basePath = `${window.location.origin}/CEU/PaginasGlobais/ListarFavoritos.php`;
        const r = await fetch(basePath, {
          credentials: 'include',
          signal: controller.signal
        });
        if (timeoutId) clearTimeout(timeoutId);
        if (r.status === 401) {
          favoritosSet.clear();
          return;
        }
        if (!r.ok) {
          throw new Error(`HTTP error! status: ${r.status}`);
        }
        const j = await r.json();
        if (j && j.sucesso && Array.isArray(j.favoritos)) {
          favoritosSet.clear();
          for (const f of j.favoritos) {
            const cod = Number(f.cod_evento);
            if (cod > 0) favoritosSet.add(cod);
          }
          const btnFavorito = document.querySelector('.BotaoFavoritoCartao');
          if (btnFavorito) {
            atualizarIconeFavorito(btnFavorito, favoritosSet.has(codEvento));
          }
        }
      } catch (e) {
        if (e.name !== 'AbortError') {
          console.warn('Erro ao carregar favoritos:', e);
        }
      } finally {
        if (timeoutId) clearTimeout(timeoutId);
      }
    }

    // ====== Funções de Mensagem ao Organizador ======
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
        textarea.removeEventListener('input', atualizarContadorMensagem);
        textarea.addEventListener('input', atualizarContadorMensagem);
      }
      m.classList.add('ativo');
      bloquearScroll();
    }

    function fecharModalMensagem(skipUnlock) {
      const m = document.getElementById('modal-mensagem');
      if (m) {
        m.classList.remove('ativo');
        if (!skipUnlock) {
          desbloquearScroll();
        }
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
      let timeoutId = null;
      try {
        const controller = new AbortController();
        timeoutId = setTimeout(() => controller.abort(), 10000);
        const basePath = `${window.location.origin}/CEU/PaginasGlobais/EnviarMensagemOrganizador.php`;
        const r = await fetch(basePath, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          credentials: 'include',
          body: new URLSearchParams({
            cod_evento: codEventoMensagem,
            mensagem: texto
          }),
          signal: controller.signal
        });
        if (timeoutId) clearTimeout(timeoutId);
        const j = await r.json();
        fecharModalMensagem();
        if (j && j.sucesso) {
          alert('Mensagem enviada ao organizador!');
        } else {
          alert(j.mensagem || 'Não foi possível enviar a mensagem.');
        }
      } catch (e) {
        if (timeoutId) clearTimeout(timeoutId);
        fecharModalMensagem();
        if (e.name !== 'AbortError') {
          alert('Erro ao enviar mensagem.');
        }
      }
    }

    // ====== Modal de Compartilhar ======
    function abrirModalCompartilhar() {
      if (!codEventoCompartilhar) return;
      const modal = document.getElementById('modal-compartilhar');
      if (!modal) return;
      const linkEvento = `${window.location.origin}/CEU/PaginasPublicas/EventoPublico.php?codEvento=${codEventoCompartilhar}`;
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
        try {
          input.select();
          document.execCommand('copy');
        } catch (err) {
          console.error('Erro ao copiar link:', err);
        }
      });
    }

    function compartilharWhatsApp() {
      if (!codEventoCompartilhar) return;
      const linkEvento = `${window.location.origin}/CEU/PaginasPublicas/EventoPublico.php?codEvento=${codEventoCompartilhar}`;
      const texto = `Confira este evento: ${linkEvento}`;
      window.open(`https://wa.me/?text=${encodeURIComponent(texto)}`, '_blank');
    }

    function compartilharInstagram() {
      if (!codEventoCompartilhar) return;
      const linkEvento = `${window.location.origin}/CEU/PaginasPublicas/EventoPublico.php?codEvento=${codEventoCompartilhar}`;
      navigator.clipboard.writeText(linkEvento).then(() => {
        alert('Link copiado! Cole no Instagram para compartilhar.');
      }).catch(() => {
        const input = document.getElementById('link-inscricao');
        if (input) {
          input.select();
          document.execCommand('copy');
          alert('Link copiado! Cole no Instagram para compartilhar.');
        }
      });
    }

    function compartilharEmail() {
      if (!codEventoCompartilhar) return;
      const linkEvento = `${window.location.origin}/CEU/PaginasPublicas/EventoPublico.php?codEvento=${codEventoCompartilhar}`;
      const assunto = 'Confira este evento!';
      const corpo = `Olá! Gostaria de compartilhar este evento com você: ${linkEvento}`;
      window.location.href = `mailto:?subject=${encodeURIComponent(assunto)}&body=${encodeURIComponent(corpo)}`;
    }

    function compartilharX() {
      if (!codEventoCompartilhar) return;
      const linkEvento = `${window.location.origin}/CEU/PaginasPublicas/EventoPublico.php?codEvento=${codEventoCompartilhar}`;
      const texto = `Confira este evento!`;
      window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(texto)}&url=${encodeURIComponent(linkEvento)}`, '_blank');
    }

    // ====== Event Listeners para os botões ======
    document.addEventListener('DOMContentLoaded', function() {
      // Botão de favoritar
      const btnFavorito = document.querySelector('.BotaoFavoritoCartao');
      if (btnFavorito) {
        btnFavorito.addEventListener('click', async function(e) {
          e.preventDefault();
          e.stopPropagation();
          if (this.dataset.processing === 'true') return false;
          const cod = Number(this.getAttribute('data-cod')) || 0;
          if (!cod) return;
          this.dataset.processing = 'true';
          const estadoAtual = this.getAttribute('data-favorito') === '1';
          const novoEstado = !estadoAtual;
          if (novoEstado) {
            favoritosSet.add(cod);
          } else {
            favoritosSet.delete(cod);
          }
          atualizarIconeFavorito(this, novoEstado);
          try {
            let timeoutId = null;
            const controller = new AbortController();
            timeoutId = setTimeout(() => controller.abort(), 10000);
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
              atualizarIconeFavorito(this, estadoAtual);
              alert('Faça login para favoritar eventos.');
            } else if (!r.ok) {
              throw new Error(`HTTP error! status: ${r.status}`);
            } else {
              const j = await r.json();
              if (j && j.sucesso) {
                if (j.favoritado) {
                  favoritosSet.add(cod);
                } else {
                  favoritosSet.delete(cod);
                }
                atualizarIconeFavorito(this, j.favoritado);
              } else {
                if (estadoAtual) {
                  favoritosSet.add(cod);
                } else {
                  favoritosSet.delete(cod);
                }
                atualizarIconeFavorito(this, estadoAtual);
                alert(j.mensagem || 'Não foi possível atualizar favorito.');
              }
            }
          } catch (err) {
            if (estadoAtual) {
              favoritosSet.add(cod);
            } else {
              favoritosSet.delete(cod);
            }
            atualizarIconeFavorito(this, estadoAtual);
            if (err.name !== 'AbortError') {
              console.error('Erro ao atualizar favorito:', err);
              alert('Erro ao atualizar favorito. Verifique sua conexão e tente novamente.');
            }
          } finally {
            this.dataset.processing = 'false';
          }
          return false;
        });
      }

      // Botão de mensagem
      const btnMensagem = document.querySelector('.BotaoMensagemCartao');
      if (btnMensagem) {
        btnMensagem.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          const cod = Number(this.getAttribute('data-cod')) || 0;
          if (!cod) return;
          codEventoMensagem = cod;
          window.codEventoMensagem = cod;
          abrirModalMensagem();
          return false;
        });
      }

      // Botão de compartilhar
      const btnCompartilhar = document.querySelector('.BotaoCompartilharCartao');
      if (btnCompartilhar) {
        btnCompartilhar.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          const cod = Number(this.getAttribute('data-cod')) || 0;
          if (!cod) return;
          codEventoCompartilhar = cod;
          window.codEvento = cod;
          abrirModalCompartilhar();
          return false;
        });
      }

      // Fechar modais ao clicar fora
      const modalMensagem = document.getElementById('modal-mensagem');
      if (modalMensagem) {
        modalMensagem.onclick = function(e) {
          if (e.target === this) fecharModalMensagem();
        };
      }

      const modalCompartilhar = document.getElementById('modal-compartilhar');
      if (modalCompartilhar) {
        modalCompartilhar.onclick = function(e) {
          if (e.target === this) {
            e.stopPropagation();
            fecharModalCompartilhar();
          }
        };
      }

      // Fechar modais com ESC
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' || e.key === 'Esc') {
          fecharModalMensagem(true);
          fecharModalCompartilhar();
        }
      });

      // Carregar favoritos ao iniciar
      carregarFavoritos();
    });
  </script>
  <script src="CartaoDoEventoOrganizando.js?v=<?php echo time(); ?>"></script>
</body>

</html>