<?php
// Processa o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    require_once('../BancoDados/conexao.php');

    $cpfOrganizador = $_SESSION['cpf'];

    // Recebe os dados do formulário
    $nome = $_POST['nome'] ?? '';
    $local = $_POST['local'] ?? '';
    $dataInicio = $_POST['data_inicio'] ?? '';
    $dataFim = $_POST['data_fim'] ?? '';
    $horarioInicio = $_POST['horario_inicio'] ?? '';
    $horarioFim = $_POST['horario_fim'] ?? '';
    $publicoAlvo = $_POST['publico_alvo'] ?? '';
    $categoria = $_POST['categoria'] ?? '';
    $modalidade = $_POST['modalidade'] ?? '';
    $certificado = $_POST['certificado'] ?? '';
    $descricao = $_POST['descricao'] ?? '';

    // Validação básica
    if (
        empty($nome) || empty($local) || empty($dataInicio) || empty($dataFim) ||
        empty($horarioInicio) || empty($horarioFim) || empty($publicoAlvo) ||
        empty($categoria) || empty($modalidade) || empty($certificado) || empty($descricao)
    ) {
        echo json_encode(['erro' => 'Todos os campos são obrigatórios']);
        exit;
    }

    // Combina data e hora
    $inicio = $dataInicio . ' ' . $horarioInicio . ':00';
    $conclusao = $dataFim . ' ' . $horarioFim . ':00';

    // Calcula duração em horas
    $dataInicioObj = new DateTime($inicio);
    $dataConclusaoObj = new DateTime($conclusao);
    $intervalo = $dataInicioObj->diff($dataConclusaoObj);
    $duracao = ($intervalo->days * 24) + $intervalo->h + ($intervalo->i / 60);

    // Converte certificado para booleano
    $certificadoBool = ($certificado !== 'Sem certificacao') ? 1 : 0;

    // Processa upload de múltiplas imagens
    $imagensUpload = [];
    $caminhoImagemPrincipal = null;
    
    if (isset($_FILES['imagens_evento']) && !empty($_FILES['imagens_evento']['name'][0])) {
        $totalImagens = count($_FILES['imagens_evento']['name']);
        $tamanhoMaximo = 10 * 1024 * 1024; // 10MB em bytes
        $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        for ($i = 0; $i < $totalImagens; $i++) {
            // Verifica se houve erro no upload
            if ($_FILES['imagens_evento']['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            
            $nomeArquivo = $_FILES['imagens_evento']['name'][$i];
            $tmpName = $_FILES['imagens_evento']['tmp_name'][$i];
            $tamanhoArquivo = $_FILES['imagens_evento']['size'][$i];
            $extensao = strtolower(pathinfo($nomeArquivo, PATHINFO_EXTENSION));
            
            // Valida tamanho do arquivo
            if ($tamanhoArquivo > $tamanhoMaximo) {
                $tamanhoMB = round($tamanhoArquivo / 1024 / 1024, 2);
                echo json_encode(['erro' => "A imagem '{$nomeArquivo}' excede o limite de 10MB. Tamanho: {$tamanhoMB}MB"]);
                exit;
            }
            
            // Valida extensão
            if (!in_array($extensao, $extensoesPermitidas)) {
                echo json_encode(['erro' => "Formato de imagem não permitido em '{$nomeArquivo}'. Use: JPG, JPEG, PNG, GIF ou WEBP"]);
                exit;
            }
            
            // Gera nome único
            $nomeUnico = uniqid() . '_' . time() . '_' . $i . '.' . $extensao;
            $destino = '../ImagensEventos/' . $nomeUnico;
            
            if (move_uploaded_file($tmpName, $destino)) {
                $caminhoCompleto = 'ImagensEventos/' . $nomeUnico;
                $imagensUpload[] = [
                    'caminho' => $caminhoCompleto,
                    'ordem' => $i,
                    'principal' => ($i === 0) ? 1 : 0
                ];
                
                // A primeira imagem é a principal
                if ($i === 0) {
                    $caminhoImagemPrincipal = $caminhoCompleto;
                }
            } else {
                echo json_encode(['erro' => "Erro ao fazer upload da imagem '{$nomeArquivo}'"]);
                exit;
            }
        }
    }

    // Gera código único para o evento
    $sqlMaxCod = "SELECT IFNULL(MAX(cod_evento), 0) + 1 as proximo_cod FROM evento";
    $resultMaxCod = mysqli_query($conexao, $sqlMaxCod);
    $rowMaxCod = mysqli_fetch_assoc($resultMaxCod);
    $codEvento = $rowMaxCod['proximo_cod'];

    // Inicia transação
    mysqli_begin_transaction($conexao);

    try {
        // Insere evento (mantém campo imagem com a principal para compatibilidade)
        $sqlEvento = "INSERT INTO evento (cod_evento, categoria, nome, lugar, descricao, publico_alvo, inicio, conclusao, duracao, certificado, modalidade, imagem) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmtEvento = mysqli_prepare($conexao, $sqlEvento);
        mysqli_stmt_bind_param(
            $stmtEvento,
            "isssssssdsss",
            $codEvento,
            $categoria,
            $nome,
            $local,
            $descricao,
            $publicoAlvo,
            $inicio,
            $conclusao,
            $duracao,
            $certificadoBool,
            $modalidade,
            $caminhoImagemPrincipal
        );

        if (!mysqli_stmt_execute($stmtEvento)) {
            throw new Exception('Erro ao inserir evento: ' . mysqli_error($conexao));
        }

        // Insere múltiplas imagens na tabela imagens_evento
        if (!empty($imagensUpload)) {
            $sqlImagem = "INSERT INTO imagens_evento (cod_evento, caminho_imagem, ordem, principal) VALUES (?, ?, ?, ?)";
            $stmtImagem = mysqli_prepare($conexao, $sqlImagem);
            
            foreach ($imagensUpload as $img) {
                mysqli_stmt_bind_param($stmtImagem, "isii", $codEvento, $img['caminho'], $img['ordem'], $img['principal']);
                if (!mysqli_stmt_execute($stmtImagem)) {
                    throw new Exception('Erro ao inserir imagem: ' . mysqli_error($conexao));
                }
            }
            mysqli_stmt_close($stmtImagem);
        }

        // Vincula organizador ao evento
        $sqlOrganiza = "INSERT INTO organiza (cod_evento, CPF) VALUES (?, ?)";
        $stmtOrganiza = mysqli_prepare($conexao, $sqlOrganiza);
        mysqli_stmt_bind_param($stmtOrganiza, "is", $codEvento, $cpfOrganizador);

        if (!mysqli_stmt_execute($stmtOrganiza)) {
            throw new Exception('Erro ao vincular organizador: ' . mysqli_error($conexao));
        }

        // Commit da transação
        mysqli_commit($conexao);

        mysqli_stmt_close($stmtEvento);
        mysqli_stmt_close($stmtOrganiza);
        mysqli_close($conexao);

        echo json_encode(['sucesso' => true, 'cod_evento' => $codEvento, 'mensagem' => 'Evento criado com sucesso!']);
        exit;
    } catch (Exception $e) {
        // Rollback em caso de erro
        mysqli_rollback($conexao);
        mysqli_close($conexao);

        echo json_encode(['erro' => $e->getMessage()]);
        exit;
    }
}

// busca o nome do usuário logado
require_once('../BancoDados/conexao.php');

$cpfUsuario = $_SESSION['cpf'];
$consultaSQL = "SELECT Nome FROM usuario WHERE CPF = ?";
$declaracaoPreparada = mysqli_prepare($conexao, $consultaSQL);

if ($declaracaoPreparada) {
    mysqli_stmt_bind_param($declaracaoPreparada, "s", $cpfUsuario);
    mysqli_stmt_execute($declaracaoPreparada);
    $resultadoConsulta = mysqli_stmt_get_result($declaracaoPreparada);
    $dadosUsuario = mysqli_fetch_assoc($resultadoConsulta);
    mysqli_stmt_close($declaracaoPreparada);
}

$nomeOrganizador = $dadosUsuario['Nome'] ?? 'Organizador';
mysqli_close($conexao);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Adicionar Evento</title>
    <link rel="stylesheet" href="styleGlobal.css" />
    <style>
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

        .grupo-campo label {
            font-weight: 700;
            font-size: 1.05rem;
            line-height: 1.2;
            margin-bottom: 0.35rem;
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

        /* Estilização específica para as opções do select */
        .campo-select option {
            background-color: var(--fundo);
            color: var(--preto, #000);
            padding: 0.75rem;
            font-weight: 600;
            text-align: center;
            font-size: 0.95rem;
        }

        /* Placeholder (primeira opção) */
        .campo-select option:disabled {
            color: #888;
            font-style: italic;
            font-weight: 500;
        }

        /* Quando nenhuma opção válida está selecionada */
        .campo-select:invalid,
        .campo-select[value=""] {
            color: #888;
            font-weight: 500;
        }

        /* Quando uma opção válida está selecionada */
        .campo-select:valid:not([value=""]) {
            color: var(--preto, #000);
            font-weight: 700;
        }

        /* Firefox: remover seta padrão */
        @-moz-document url-prefix() {
            .campo-select {
                text-indent: 0.01px;
                text-overflow: '';
            }
        }

        /* Edge/IE: remover seta padrão */
        .campo-select::-ms-expand {
            display: none;
        }

        /* Ajustes específicos para inputs de data e hora */
        input[type="date"],
        input[type="time"] {
            position: relative;
            cursor: pointer;
        }

        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="time"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
            opacity: 0.6;
            transition: opacity 0.3s;
            filter: invert(27%) sepia(88%) saturate(2598%) hue-rotate(201deg) brightness(99%) contrast(101%);
        }

        input[type="date"]:hover::-webkit-calendar-picker-indicator,
        input[type="time"]:hover::-webkit-calendar-picker-indicator {
            opacity: 1;
        }

        input[type="date"]::-webkit-datetime-edit,
        input[type="time"]::-webkit-datetime-edit {
            padding: 0;
        }

        input[type="date"]::-webkit-datetime-edit-fields-wrapper,
        input[type="time"]::-webkit-datetime-edit-fields-wrapper {
            padding: 0;
        }

        input[type="date"]::-webkit-datetime-edit-text,
        input[type="time"]::-webkit-datetime-edit-text {
            color: var(--preto, #000);
            padding: 0 0.2rem;
        }

        input[type="date"]::-webkit-datetime-edit-month-field,
        input[type="date"]::-webkit-datetime-edit-day-field,
        input[type="date"]::-webkit-datetime-edit-year-field,
        input[type="time"]::-webkit-datetime-edit-hour-field,
        input[type="time"]::-webkit-datetime-edit-minute-field {
            color: var(--preto, #000);
            font-weight: 700;
            padding: 0 0.15rem;
        }

        input[type="date"]::-webkit-datetime-edit-month-field:focus,
        input[type="date"]::-webkit-datetime-edit-day-field:focus,
        input[type="date"]::-webkit-datetime-edit-year-field:focus,
        input[type="time"]::-webkit-datetime-edit-hour-field:focus,
        input[type="time"]::-webkit-datetime-edit-minute-field:focus {
            background-color: rgba(1, 102, 255, 0.15);
            color: var(--botao, #0166ff);
            border-radius: 0.25rem;
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

        .Nome {
            grid-column: span 4 / span 4;
        }

        .Organizador {
            grid-column: span 4 / span 4;
            grid-column-start: 5;
        }

        /* Container para o campo organizador com botão */
        .campo-organizador-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Campo organizador somente leitura */
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

        /* Botão adicionar colaborador */
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

        .btn-adicionar-colaborador:active {
            transform: scale(0.95);
        }

        .Local {
            grid-column: span 8 / span 8;
            grid-row-start: 2;
        }

        .DataDeInicio {
            grid-column: span 2 / span 2;
            grid-row-start: 3;
        }

        .DataDeFim {
            grid-column: span 2 / span 2;
            grid-column-start: 3;
            grid-row-start: 3;
        }

        .HorarioDeInicio {
            grid-column: span 2 / span 2;
            grid-column-start: 5;
            grid-row-start: 3;
        }

        .HorarioDeFim {
            grid-column: span 2 / span 2;
            grid-column-start: 7;
            grid-row-start: 3;
        }

        .PublicoAlvo {
            grid-column: span 2 / span 2;
            grid-row-start: 4;
        }

        .Categoria {
            grid-column: span 2 / span 2;
            grid-column-start: 3;
            grid-row-start: 4;
        }

        .Modalidade {
            grid-column: span 2 / span 2;
            grid-column-start: 5;
            grid-row-start: 4;
        }

        .Certificado {
            grid-column: span 2 / span 2;
            grid-column-start: 7;
            grid-row-start: 4;
        }

        .Imagem {
            grid-column: span 4 / span 4;
            grid-row: span 3 / span 3;
            grid-row-start: 5;
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
            grid-row-start: 5;
        }

        .BotaoVoltar {
            grid-column: span 2 / span 2;
            grid-row-start: 8;
        }

        .BotaoCriar {
            grid-column: span 2 / span 2;
            grid-column-start: 7;
            grid-row-start: 8;
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
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            color: #888;
            font-size: 0.95rem;
            padding: 1rem;
        }

        .campo-imagem-placeholder svg {
            width: 3rem;
            height: 3rem;
            fill: #888;
        }

        /* Ajuste para quando usar <img> no lugar do SVG inline */
        .campo-imagem-placeholder img {
            width: 3rem;
            height: 3rem;
        }

        #input-imagem {
            display: none;
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
            transition: background .25s, transform .15s, opacity .25s;
            width: 100%;
        }

        .botao:active:not(:disabled) {
            transform: translateY(1px);
        }

        .botao:disabled {
            background-color: #ccc;
            cursor: not-allowed;
            opacity: 0.6;
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
            display: flex;
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
            display: flex;
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
            display: flex;
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

        .btn-adicionar-mais svg {
            width: 1rem;
            height: 1rem;
            fill: white;
        }

        /* Ajuste para quando usar <img> no lugar do SVG inline */
        .btn-adicionar-mais img {
            width: 1rem;
            height: 1rem;
        }
    </style>
</head>

<body>
    <div id="main-content">
        <form id="form-evento" class="cartao-evento">
            <div class="Nome grupo-campo">
                <label for="nome"><span style="color: red;">*</span> Nome:</label>
                <input type="text" id="nome" name="nome" class="campo-input" placeholder="Digite o nome do evento" required autocomplete="off">
            </div>
            <div class="Organizador grupo-campo">
                <label>Organizado por:</label>
                <div class="campo-organizador-wrapper">
                    <div class="campo-organizador"><?php echo htmlspecialchars($nomeOrganizador); ?></div>
                    <button type="button" class="btn-adicionar-colaborador" onclick="abrirModalColaboradores()" title="Adicionar colaborador">+</button>
                </div>
            </div>
            <div class="Local grupo-campo">
                <label for="local"><span style="color: red;">*</span> Local:</label>
                <input type="text" id="local" name="local" class="campo-input" placeholder="Digite o local do evento" required autocomplete="off">
            </div>
            <div class="DataDeInicio grupo-campo">
                <label for="data-inicio"><span style="color: red;">*</span> Data de Início:</label>
                <input type="date" id="data-inicio" name="data_inicio" class="campo-input" required autocomplete="off">
            </div>
            <div class="DataDeFim grupo-campo">
                <label for="data-fim"><span style="color: red;">*</span> Data de Fim:</label>
                <input type="date" id="data-fim" name="data_fim" class="campo-input" required autocomplete="off">
            </div>
            <div class="HorarioDeInicio grupo-campo">
                <label for="horario-inicio"><span style="color: red;">*</span> Horário de Início:</label>
                <input type="time" id="horario-inicio" name="horario_inicio" class="campo-input" required autocomplete="off">
            </div>
            <div class="HorarioDeFim grupo-campo">
                <label for="horario-fim"><span style="color: red;">*</span> Horário de Fim:</label>
                <input type="time" id="horario-fim" name="horario_fim" class="campo-input" required autocomplete="off">
            </div>
            <div class="PublicoAlvo grupo-campo">
                <label for="publico-alvo"><span style="color: red;">*</span> Público Alvo:</label>
                <input type="text" id="publico-alvo" name="publico_alvo" class="campo-input" placeholder="Ex: Estudantes" required autocomplete="off">
            </div>
            <div class="Categoria grupo-campo">
                <label for="categoria"><span style="color: red;">*</span> Categoria:</label>
                <select id="categoria" name="categoria" class="campo-select" required autocomplete="off">
                    <option value="">Selecione</option>
                    <option value="Palestra">Palestra</option>
                    <option value="Workshop">Workshop</option>
                    <option value="Seminário">Seminário</option>
                    <option value="Conferência">Conferência</option>
                    <option value="Curso">Curso</option>
                    <option value="Treinamento">Treinamento</option>
                    <option value="Outro">Outro</option>
                </select>
            </div>
            <div class="Modalidade grupo-campo">
                <label for="modalidade"><span style="color: red;">*</span> Modalidade:</label>
                <select id="modalidade" name="modalidade" class="campo-select" required autocomplete="off">
                    <option value="">Selecione</option>
                    <option value="Presencial">Presencial</option>
                    <option value="Online">Online</option>
                    <option value="Híbrido">Híbrido</option>
                </select>
            </div>
            <div class="Certificado grupo-campo">
                <label for="certificado"><span style="color: red;">*</span> Tipo de Certificado:</label>
                <select id="certificado" name="certificado" class="campo-select" required autocomplete="off">
                    <option value="">Selecione</option>
                    <option value="Sem certificacao">Sem certificação</option>
                    <option value="Ensino">Ensino</option>
                    <option value="Pesquisa">Pesquisa</option>
                    <option value="Extensao">Extensão</option>
                    <option value="Outro">Outro</option>
                </select>
            </div>
            <div class="Imagem grupo-campo">
                <div class="campo-imagem" id="campo-imagem" onclick="document.getElementById('input-imagem').click()">
                    <div class="campo-imagem-placeholder" id="placeholder-imagem">
                        <img src="../Imagens/AdicionarImagem.svg" alt="Adicionar imagem" />
                        <span>Clique para adicionar imagens</span>
                    </div>
                    <div class="carrossel-imagens" id="carrossel-imagens" style="display: none;">
                        <button type="button" class="btn-remover-imagem" id="btn-remover-imagem" onclick="event.stopPropagation(); removerImagemAtual();">&times;</button>
                        <button type="button" class="carrossel-btn carrossel-anterior" onclick="event.stopPropagation(); mudarImagem(-1)">⮜</button>
                        <img id="imagem-carrossel" src="" alt="Imagem do evento" />
                        <button type="button" class="carrossel-btn carrossel-proxima" onclick="event.stopPropagation(); mudarImagem(1)">⮞</button>
                        <button type="button" class="btn-adicionar-mais" onclick="event.stopPropagation(); document.getElementById('input-imagem').click();">
                            <img src="../Imagens/AdicionarMais.svg" alt="" aria-hidden="true" />
                            Adicionar mais imagens
                        </button>
                    </div>
                </div>
                <input type="file" id="input-imagem" name="imagens_evento" accept="image/*" multiple onchange="adicionarImagens(event)" autocomplete="off">
            </div>
            <div class="Descricao grupo-campo">
                <label for="descricao"><span style="color: red;">*</span> Descrição:</label>
                <textarea id="descricao" name="descricao" class="campo-textarea" placeholder="Descreva o evento, incluindo detalhes, objetivos, público alvo, estrutura, palestrantes, etc." required autocomplete="off"></textarea>
            </div>
            <div class="BotaoVoltar">
                <button type="button" class="botao" onclick="history.back()">Voltar</button>
            </div>
            <div class="BotaoCriar">
                <button type="submit" class="botao" id="btn-criar" disabled>Criar evento</button>
            </div>
        </form>
        <div id="modal-imagem" class="modal-imagem">
            <button onclick="fecharModalImagem()" class="modal-imagem-btn-fechar">&times;</button>
            <button class="carrossel-btn carrossel-anterior modal-imagem-btn-anterior"
                onclick="mudarImagemModal(-1)">⮜</button>
            <img id="imagem-ampliada" src="" alt="Imagem ampliada" class="modal-imagem-img" />
            <button class="carrossel-btn carrossel-proxima modal-imagem-btn-proxima"
                onclick="mudarImagemModal(1)">⮞</button>
        </div>
    </div>
    <script>
        let imagens = [];
        let indiceAtual = 0;

        function validarFormulario() {
            const nome = document.getElementById('nome').value.trim();
            const local = document.getElementById('local').value.trim();
            const dataInicio = document.getElementById('data-inicio').value;
            const dataFim = document.getElementById('data-fim').value;
            const horarioInicio = document.getElementById('horario-inicio').value;
            const horarioFim = document.getElementById('horario-fim').value;
            const publicoAlvo = document.getElementById('publico-alvo').value.trim();
            const categoria = document.getElementById('categoria').value;
            const certificado = document.getElementById('certificado').value;
            const modalidade = document.getElementById('modalidade').value;
            const descricao = document.getElementById('descricao').value.trim();

            const todosPreenchidos = nome && local && dataInicio && dataFim &&
                horarioInicio && horarioFim && publicoAlvo &&
                categoria && certificado && modalidade && descricao;

            document.getElementById('btn-criar').disabled = !todosPreenchidos;
        }

        // Função para abrir modal de colaboradores
        function abrirModalColaboradores() {
            alert('Funcionalidade de adicionar colaboradores em desenvolvimento!\n\nEm breve você poderá adicionar outros organizadores para colaborar com este evento.');
            // TODO: Implementar modal para adicionar colaboradores
        }

        // Adicionar listeners para validar em tempo real
        document.querySelectorAll('input, select, textarea').forEach(element => {
            element.addEventListener('input', validarFormulario);
            element.addEventListener('change', validarFormulario);
        });

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
            
            // Limpa o input para permitir selecionar o mesmo arquivo novamente se necessário
            event.target.value = '';
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

        document.getElementById('imagem-carrossel').onclick = function(e) {
            e.stopPropagation();
            if (imagens.length > 0) {
                document.getElementById('imagem-ampliada').src = imagens[indiceAtual];
                document.getElementById('modal-imagem').style.display = 'flex';
            }
        };

        function fecharModalImagem() {
            document.getElementById('modal-imagem').style.display = 'none';
        }

        document.getElementById('form-evento').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            // Desabilita o botão durante o envio
            const btnCriar = document.getElementById('btn-criar');
            btnCriar.disabled = true;
            btnCriar.textContent = 'Criando...';

            fetch('AdicionarEvento.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.sucesso) {
                        alert(data.mensagem || 'Evento criado com sucesso!');
                        // Redireciona para a página de meus eventos
                        if (typeof carregarPagina === 'function') {
                            carregarPagina('meusEventos');
                        } else {
                            window.location.href = 'ContainerOrganizador.php?pagina=meusEventos';
                        }
                    } else {
                        alert('Erro ao criar evento: ' + (data.erro || 'Erro desconhecido'));
                        btnCriar.disabled = false;
                        btnCriar.textContent = 'Criar evento';
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao criar evento. Por favor, tente novamente.');
                    btnCriar.disabled = false;
                    btnCriar.textContent = 'Criar evento';
                });
        });

        // Validação inicial
        validarFormulario();
    </script>
</body>

</html>