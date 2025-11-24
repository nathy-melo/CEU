<?php
// Limpa qualquer output anterior
ob_start();

// Inicia a sessão se ainda não foi iniciada
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Processa o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Limpa qualquer output anterior
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');

    require_once('../BancoDados/conexao.php');

    $cpfOrganizador = $_SESSION['cpf'] ?? null;
    
    if (!$cpfOrganizador) {
        echo json_encode(['erro' => 'Sessão expirada. Faça login novamente.']);
        exit;
    }

    // Recebe os dados do formulário
    $nome = $_POST['nome'] ?? '';
    $local = $_POST['local'] ?? '';
    $dataInicio = $_POST['data_inicio'] ?? '';
    $dataFim = $_POST['data_fim'] ?? '';
    $horarioInicio = $_POST['horario_inicio'] ?? '';
    $horarioFim = $_POST['horario_fim'] ?? '';
    $dataInicioInscricao = $_POST['data_inicio_inscricao'] ?? '';
    $dataFimInscricao = $_POST['data_fim_inscricao'] ?? '';
    $horarioInicioInscricao = $_POST['horario_inicio_inscricao'] ?? '';
    $horarioFimInscricao = $_POST['horario_fim_inscricao'] ?? '';
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

    // Combina data e hora das inscrições (se fornecidas)
    $inicioInscricao = null;
    $fimInscricao = null;
    if (!empty($dataInicioInscricao) && !empty($horarioInicioInscricao)) {
        $inicioInscricao = $dataInicioInscricao . ' ' . $horarioInicioInscricao . ':00';
    }
    if (!empty($dataFimInscricao) && !empty($horarioFimInscricao)) {
        $fimInscricao = $dataFimInscricao . ' ' . $horarioFimInscricao . ':00';
    }
    
    // Converte NULL para string vazia para bind_param (será tratado como NULL no banco)
    $inicioInscricaoStr = $inicioInscricao ?? '';
    $fimInscricaoStr = $fimInscricao ?? '';

    // Calcula duração em horas
    $dataInicioObj = new DateTime($inicio);
    $dataConclusaoObj = new DateTime($conclusao);
    $intervalo = $dataInicioObj->diff($dataConclusaoObj);
    $duracao = ($intervalo->days * 24) + $intervalo->h + ($intervalo->i / 60);

    // Converte certificado para booleano
    $certificadoBool = ($certificado !== 'Sem certificacao') ? 1 : 0;

    // Processa upload de múltiplas imagens
    $imagensUpload = [];
    // Se não houver imagens, usa a imagem padrão
    $caminhoImagemPrincipal = 'ImagensEventos/CEU-ImagemEvento.png';

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
        // Garante que as colunas de inscrição existem (compatível com MySQL < 8 sem IF NOT EXISTS)
        function garantirColunaEvento(mysqli $cx, string $coluna, string $definicao) {
            $escCol = mysqli_real_escape_string($cx, $coluna);
            $res = mysqli_query($cx, "SHOW COLUMNS FROM evento LIKE '$escCol'");
            if ($res && mysqli_num_rows($res) === 0) {
                mysqli_query($cx, "ALTER TABLE evento ADD COLUMN `$coluna` $definicao");
            }
            if ($res) { mysqli_free_result($res); }
        }
        garantirColunaEvento($conexao, 'inicio_inscricao', 'DATETIME NULL');
        garantirColunaEvento($conexao, 'fim_inscricao', 'DATETIME NULL');

        // Insere evento (mantém campo imagem com a principal para compatibilidade)
        $sqlEvento = "INSERT INTO evento (cod_evento, categoria, nome, lugar, descricao, publico_alvo, inicio, conclusao, duracao, certificado, modalidade, imagem, inicio_inscricao, fim_inscricao) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmtEvento = mysqli_prepare($conexao, $sqlEvento);
        // Tipos: cod_evento(i), categoria(s), nome(s), lugar(s), descricao(s), publico_alvo(s), inicio(s), conclusao(s), duracao(d), certificado(i), modalidade(s), imagem(s), inicio_inscricao(s), fim_inscricao(s)
        // Para campos NULL, usa string vazia (será tratado como NULL no banco se o campo aceitar NULL)
        $inicioInscricaoFinal = (!empty($inicioInscricaoStr)) ? $inicioInscricaoStr : '';
        $fimInscricaoFinal = (!empty($fimInscricaoStr)) ? $fimInscricaoStr : '';
        
        mysqli_stmt_bind_param(
            $stmtEvento,
            "isssssssdissss",
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
            $caminhoImagemPrincipal,
            $inicioInscricaoFinal,
            $fimInscricaoFinal
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
        mysqli_stmt_close($stmtOrganiza);

        // Adiciona colaboradores se houver
        if (isset($_POST['colaboradores']) && !empty($_POST['colaboradores'])) {
            $colaboradores = json_decode($_POST['colaboradores'], true);

            if (is_array($colaboradores) && count($colaboradores) > 0) {
                // Garante que a coluna certificado_emitido existe
                mysqli_query($conexao, "ALTER TABLE colaboradores_evento ADD COLUMN IF NOT EXISTS certificado_emitido tinyint(1) DEFAULT 0");

                $sqlColab = "INSERT INTO colaboradores_evento (cod_evento, CPF, certificado_emitido) VALUES (?, ?, 0)";
                $stmtColab = mysqli_prepare($conexao, $sqlColab);

                foreach ($colaboradores as $colab) {
                    if (isset($colab['cpf']) && !empty($colab['cpf'])) {
                        mysqli_stmt_bind_param($stmtColab, "is", $codEvento, $colab['cpf']);

                        if (!mysqli_stmt_execute($stmtColab)) {
                            // Continua mesmo se houver erro em um colaborador
                            error_log('Erro ao adicionar colaborador: ' . mysqli_error($conexao));
                        }
                    }
                }

                mysqli_stmt_close($stmtColab);
            }
        }

        // Commit da transação
        mysqli_commit($conexao);

        mysqli_stmt_close($stmtEvento);
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

$cpfUsuario = $_SESSION['cpf'] ?? null;
$nomeOrganizador = 'Organizador';

if ($cpfUsuario) {
    $consultaSQL = "SELECT Nome FROM usuario WHERE CPF = ?";
    $declaracaoPreparada = mysqli_prepare($conexao, $consultaSQL);

    if ($declaracaoPreparada) {
        mysqli_stmt_bind_param($declaracaoPreparada, "s", $cpfUsuario);
        mysqli_stmt_execute($declaracaoPreparada);
        $resultadoConsulta = mysqli_stmt_get_result($declaracaoPreparada);
        $dadosUsuario = mysqli_fetch_assoc($resultadoConsulta);
        mysqli_stmt_close($declaracaoPreparada);
        
        if ($dadosUsuario) {
            $nomeOrganizador = $dadosUsuario['Nome'];
        }
    }
}

mysqli_close($conexao);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Adicionar Evento</title>
    <link rel="stylesheet" href="../styleGlobal.css" />
    <link rel="stylesheet" href="../styleGlobalMobile.css" media="(max-width: 767px)" />
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

        /* Lista de Colaboradores */
        .lista-colaboradores {
            margin-top: 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .colaborador-item {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 10px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.2s;
        }

        .colaborador-item:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .colaborador-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .colaborador-nome {
            font-weight: 600;
            font-size: 14px;
            color: var(--branco);
        }

        .colaborador-cpf {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
        }

        .btn-remover-colaborador {
            background: var(--vermelho);
            color: var(--branco);
            border: none;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .btn-remover-colaborador:hover {
            opacity: 0.9;
        }

        /* Modal de Colaboradores */
        .modal-overlay {
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

        .modal-colaboradores-content {
            background: var(--branco);
            border-radius: 16px;
            padding: 0;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            padding: 24px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 20px;
            color: var(--azul-escuro);
        }

        .btn-fechar-modal {
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

        .btn-fechar-modal:hover {
            color: #000;
        }

        .modal-body {
            padding: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #6598D2;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--azul-escuro);
            border-radius: 8px;
            font-size: 15px;
            box-sizing: border-box;
            background-color: var(--branco);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--azul-escuro);
            box-shadow: 0 0 0 3px rgba(20, 40, 80, 0.1);
        }

        .form-group input:disabled {
            background-color: #f5f5f5;
            color: #666;
            cursor: not-allowed;
        }

        .modal-footer {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            padding: 24px;
            border-top: 1px solid #e0e0e0;
        }

        .btn-modal {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .btn-modal:hover {
            opacity: 0.9;
        }

        .btn-modal:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-cancelar {
            background-color: var(--vermelho);
            color: var(--branco);
        }

        .btn-salvar {
            background-color: var(--verde);
            color: var(--branco);
        }

        .Local {
            grid-column: span 8 / span 8;
            grid-row-start: 2;
        }

        .DataHorarioInicio {
            grid-column: span 4 / span 4;
            grid-row-start: 3;
        }

        .DataHorarioFim {
            grid-column: span 4 / span 4;
            grid-column-start: 5;
            grid-row-start: 3;
        }

        .DataHorarioInscricaoInicio {
            grid-column: span 4 / span 4;
            grid-row-start: 4;
        }

        .DataHorarioInscricaoFim {
            grid-column: span 4 / span 4;
            grid-column-start: 5;
            grid-row-start: 4;
        }

        /* Container para campos de data e horário */
        .campo-data-horario {
            display: flex;
            gap: 0.5rem;
            align-items: stretch;
        }

        .campo-data-horario input {
            flex: 1;
        }

        .campo-data-horario input[type="date"] {
            flex: 1.2;
        }

        .campo-data-horario input[type="time"] {
            flex: 0.8;
        }

        .PublicoAlvo {
            grid-column: span 2 / span 2;
            grid-row-start: 5;
        }

        .Categoria {
            grid-column: span 2 / span 2;
            grid-column-start: 3;
            grid-row-start: 5;
        }

        .Modalidade {
            grid-column: span 2 / span 2;
            grid-column-start: 5;
            grid-row-start: 5;
        }

        .Certificado {
            grid-column: span 2 / span 2;
            grid-column-start: 7;
            grid-row-start: 5;
        }

        .Imagem {
            grid-column: span 4 / span 4;
            grid-row: span 3 / span 3;
            grid-row-start: 6;
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
            grid-row-start: 6;
        }

        .BotaoVoltar {
            grid-column: span 2 / span 2;
            grid-row-start: 9;
        }

        .BotaoCriar {
            grid-column: span 2 / span 2;
            grid-column-start: 7;
            grid-row-start: 9;
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
                    <button type="button" class="btn-adicionar-colaborador" onclick="abrirModalColaboradores()" title="Adicionar organizador">+</button>
                </div>
                <div id="lista-colaboradores" class="lista-colaboradores" style="display: none;">
                    <!-- Organizadores serão listados aqui -->
                </div>
            </div>
            <div class="Local grupo-campo">
                <label for="local"><span style="color: red;">*</span> Local:</label>
                <input type="text" id="local" name="local" class="campo-input" placeholder="Digite o local do evento" required autocomplete="off">
            </div>
            <div class="DataHorarioInicio grupo-campo">
                <label for="data-inicio"><span style="color: red;">*</span> Data e Horário de Início do Evento:</label>
                <div class="campo-data-horario">
                    <input type="date" id="data-inicio" name="data_inicio" class="campo-input" required autocomplete="off">
                    <input type="time" id="horario-inicio" name="horario_inicio" class="campo-input" required autocomplete="off">
                </div>
            </div>
            <div class="DataHorarioFim grupo-campo">
                <label for="data-fim"><span style="color: red;">*</span> Data e Horário de Fim do Evento:</label>
                <div class="campo-data-horario">
                    <input type="date" id="data-fim" name="data_fim" class="campo-input" required autocomplete="off">
                    <input type="time" id="horario-fim" name="horario_fim" class="campo-input" required autocomplete="off">
                </div>
            </div>
            <div class="DataHorarioInscricaoInicio grupo-campo">
                <label for="data-inicio-inscricao">Início das Inscrições:</label>
                <div class="campo-data-horario">
                    <input type="date" id="data-inicio-inscricao" name="data_inicio_inscricao" class="campo-input" autocomplete="off">
                    <input type="time" id="horario-inicio-inscricao" name="horario_inicio_inscricao" class="campo-input" autocomplete="off">
                </div>
            </div>
            <div class="DataHorarioInscricaoFim grupo-campo">
                <label for="data-fim-inscricao">Fim das Inscrições:</label>
                <div class="campo-data-horario">
                    <input type="date" id="data-fim-inscricao" name="data_fim_inscricao" class="campo-input" autocomplete="off">
                    <input type="time" id="horario-fim-inscricao" name="horario_fim_inscricao" class="campo-input" autocomplete="off">
                </div>
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
                    <option value="Seminario">Seminario</option>
                    <option value="Conferencia">Conferencia</option>
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
                    <option value="Hibrido">Híbrido</option>
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
                        <button type="button" class="carrossel-btn carrossel-anterior" onclick="event.stopPropagation(); mudarImagem(-1)">‹</button>
                        <img id="imagem-carrossel" src="" alt="Imagem do evento" />
                        <button type="button" class="carrossel-btn carrossel-proxima" onclick="event.stopPropagation(); mudarImagem(1)">›</button>
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
                <button type="button" class="botao" onclick="if (typeof carregarPagina === 'function') { carregarPagina('meusEventos'); } else { window.location.href = 'ContainerOrganizador.php?pagina=meusEventos'; }">Voltar</button>
            </div>
            <div class="BotaoCriar">
                <button type="submit" class="botao" id="btn-criar">Criar evento</button>
            </div>
        </form>
        <div id="modal-imagem" class="modal-imagem">
            <button onclick="fecharModalImagem()" class="modal-imagem-btn-fechar">&times;</button>
            <button class="carrossel-btn carrossel-anterior modal-imagem-btn-anterior"
                onclick="mudarImagemModal(-1)">‹</button>
            <img id="imagem-ampliada" src="" alt="Imagem ampliada" class="modal-imagem-img" />
            <button class="carrossel-btn carrossel-proxima modal-imagem-btn-proxima"
                onclick="mudarImagemModal(1)">›</button>
        </div>

        <!-- Modal Adicionar Colaborador -->
        <div id="modal-colaboradores" class="modal-overlay" style="display: none;" onclick="fecharModalColabSeForFundo(event)">
            <div class="modal-colaboradores-content" onclick="event.stopPropagation()">
                <div class="modal-header">
                    <h2>Adicionar Organizador</h2>
                    <button class="btn-fechar-modal" onclick="fecharModalColaboradores()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="colab-cpf">CPF do Organizador*</label>
                        <input type="text" id="colab-cpf" maxlength="14" placeholder="000.000.000-00">
                        <small id="msg-colab-status" style="display: none; margin-top: 4px;"></small>
                    </div>
                    <div class="form-group">
                        <label for="colab-nome">Nome</label>
                        <input type="text" id="colab-nome" disabled>
                    </div>
                    <div class="form-group">
                        <label for="colab-email">E-mail</label>
                        <input type="email" id="colab-email" disabled>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal btn-cancelar" onclick="fecharModalColaboradores()">Cancelar</button>
                    <button type="button" class="btn-modal btn-salvar" id="btn-adicionar-colab" onclick="adicionarColaborador()" disabled>Adicionar</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Previne execução múltipla do script quando carregado via AJAX
        (function() {
            // Verifica se o script já foi executado
            if (window.adicionarEventoScriptExecutado) {
                return;
            }
            window.adicionarEventoScriptExecutado = true;

            let imagens = [];
            let indiceAtual = 0;
            let colaboradores = [];

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
            }

            function validarCamposObrigatorios() {
                const campos = [
                    { id: 'nome', nome: 'Nome', elemento: document.getElementById('nome') },
                    { id: 'local', nome: 'Local', elemento: document.getElementById('local') },
                    { id: 'data-inicio', nome: 'Data de Início', elemento: document.getElementById('data-inicio') },
                    { id: 'horario-inicio', nome: 'Horário de Início', elemento: document.getElementById('horario-inicio') },
                    { id: 'data-fim', nome: 'Data de Fim', elemento: document.getElementById('data-fim') },
                    { id: 'horario-fim', nome: 'Horário de Fim', elemento: document.getElementById('horario-fim') },
                    { id: 'publico-alvo', nome: 'Público Alvo', elemento: document.getElementById('publico-alvo') },
                    { id: 'categoria', nome: 'Categoria', elemento: document.getElementById('categoria') },
                    { id: 'modalidade', nome: 'Modalidade', elemento: document.getElementById('modalidade') },
                    { id: 'certificado', nome: 'Tipo de Certificado', elemento: document.getElementById('certificado') },
                    { id: 'descricao', nome: 'Descrição', elemento: document.getElementById('descricao') }
                ];

                const camposFaltantes = [];
                const elementosFaltantes = [];

                campos.forEach(campo => {
                    let valor = '';
                    if (campo.elemento) {
                        if (campo.elemento.tagName === 'SELECT') {
                            valor = campo.elemento.value;
                        } else {
                            valor = campo.elemento.value.trim();
                        }
                        
                        if (!valor) {
                            camposFaltantes.push(campo.nome);
                            elementosFaltantes.push(campo.elemento);
                        } else {
                            campo.elemento.style.borderColor = '';
                            campo.elemento.style.boxShadow = '';
                        }
                    }
                });

                elementosFaltantes.forEach(elemento => {
                    elemento.style.borderColor = '#f44336';
                    elemento.style.boxShadow = '0 0 0 3px rgba(244, 67, 54, 0.2)';
                    if (elemento === elementosFaltantes[0]) {
                        elemento.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        elemento.focus();
                    }
                });

                return camposFaltantes;
            }

            // ==== FUNÇÕES DE COLABORADORES ====
            window.abrirModalColaboradores = function() {
                document.getElementById('modal-colaboradores').style.display = 'flex';
                limparCamposColaborador();
            };

            window.fecharModalColaboradores = function() {
                document.getElementById('modal-colaboradores').style.display = 'none';
                limparCamposColaborador();
            };

            window.fecharModalColabSeForFundo = function(event) {
                if (event.target.id === 'modal-colaboradores') {
                    fecharModalColaboradores();
                }
            };

            function limparCamposColaborador() {
                document.getElementById('colab-cpf').value = '';
                document.getElementById('colab-nome').value = '';
                document.getElementById('colab-email').value = '';
                document.getElementById('btn-adicionar-colab').disabled = true;
                document.getElementById('msg-colab-status').style.display = 'none';
            }

            function formatarCPF(cpf) {
                cpf = cpf.replace(/\D/g, '');
                if (cpf.length <= 11) {
                    cpf = cpf.replace(/(\d{3})(\d)/, '$1.$2');
                    cpf = cpf.replace(/(\d{3})(\d)/, '$1.$2');
                    cpf = cpf.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                }
                return cpf;
            }

            function verificarCPFColaborador() {
                const cpfInput = document.getElementById('colab-cpf');
                const cpf = cpfInput.value.replace(/\D/g, '');
                const msgStatus = document.getElementById('msg-colab-status');
                const btnAdicionar = document.getElementById('btn-adicionar-colab');

                if (cpf.length !== 11) {
                    msgStatus.style.display = 'none';
                    btnAdicionar.disabled = true;
                    return;
                }

                if (colaboradores.find(c => c.cpf === cpf)) {
                    msgStatus.style.display = 'block';
                    msgStatus.style.color = '#f44336';
                    msgStatus.textContent = '❌ Este organizador já foi adicionado';
                    btnAdicionar.disabled = true;
                    return;
                }

                fetch(`../BancoDados/VerificarBancoDados.php?action=verificar_usuario&cpf=${cpf}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.existe && data.usuario) {
                            document.getElementById('colab-nome').value = data.usuario.nome || '';
                            document.getElementById('colab-email').value = data.usuario.email || '';
                            msgStatus.style.display = 'block';
                            msgStatus.style.color = '#4CAF50';
                            msgStatus.textContent = '✔ Usuário encontrado no sistema';
                            btnAdicionar.disabled = false;
                        } else {
                            document.getElementById('colab-nome').value = '';
                            document.getElementById('colab-email').value = '';
                            msgStatus.style.display = 'block';
                            msgStatus.style.color = '#f44336';
                            msgStatus.textContent = '❌ Usuário não cadastrado no sistema';
                            btnAdicionar.disabled = true;
                        }
                    })
                    .catch(() => {
                        msgStatus.style.display = 'block';
                        msgStatus.style.color = '#f44336';
                        msgStatus.textContent = '❌ Erro ao verificar CPF';
                        btnAdicionar.disabled = true;
                    });
            }

            window.adicionarColaborador = function() {
                const cpf = document.getElementById('colab-cpf').value.replace(/\D/g, '');
                const nome = document.getElementById('colab-nome').value;
                const email = document.getElementById('colab-email').value;

                if (!cpf || !nome || !email) {
                    alert('Dados incompletos');
                    return;
                }

                colaboradores.push({ cpf, nome, email });
                atualizarListaColaboradores();
                fecharModalColaboradores();
            };

            window.removerColaborador = function(cpf) {
                if (confirm('Deseja remover este organizador?')) {
                    colaboradores = colaboradores.filter(c => c.cpf !== cpf);
                    atualizarListaColaboradores();
                }
            };

            function atualizarListaColaboradores() {
                const lista = document.getElementById('lista-colaboradores');

                if (colaboradores.length === 0) {
                    lista.style.display = 'none';
                    return;
                }

                lista.style.display = 'block';
                lista.innerHTML = colaboradores.map(colab => `
                    <div class="colaborador-item">
                        <div class="colaborador-info">
                            <div class="colaborador-nome">${colab.nome}</div>
                            <div class="colaborador-cpf">CPF: ${formatarCPF(colab.cpf)}</div>
                        </div>
                        <button type="button" class="btn-remover-colaborador" onclick="removerColaborador('${colab.cpf}')">
                            Remover
                        </button>
                    </div>
                `).join('');
            }

            const colabCpfInput = document.getElementById('colab-cpf');
            if (colabCpfInput) {
                colabCpfInput.addEventListener('input', function(e) {
                    e.target.value = formatarCPF(e.target.value);
                });
                colabCpfInput.addEventListener('blur', verificarCPFColaborador);
            }

            document.querySelectorAll('input, select, textarea').forEach(element => {
                element.addEventListener('input', function() {
                    validarFormulario();
                    if (element.value && element.value.trim()) {
                        element.style.borderColor = '';
                        element.style.boxShadow = '';
                    }
                });
                element.addEventListener('change', function() {
                    validarFormulario();
                    if (element.value && element.value.trim()) {
                        element.style.borderColor = '';
                        element.style.boxShadow = '';
                    }
                });
            });

            setTimeout(() => {
                validarFormulario();
            }, 100);

            window.adicionarImagens = function(event) {
                const files = Array.from(event.target.files);
                const MAX_FILE_SIZE = 10 * 1024 * 1024;

                files.forEach(file => {
                    if (file.size > MAX_FILE_SIZE) {
                        alert(`Erro: A imagem "${file.name}" excede o limite de 10MB.\nTamanho do arquivo: ${(file.size / 1024 / 1024).toFixed(2)}MB`);
                        return;
                    }

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

                event.target.value = '';
            };

            function mostrarCarrossel() {
                document.getElementById('placeholder-imagem').style.display = 'none';
                document.getElementById('carrossel-imagens').style.display = 'flex';
                document.getElementById('imagem-carrossel').src = imagens[indiceAtual];
            }

            function esconderCarrossel() {
                document.getElementById('placeholder-imagem').style.display = 'flex';
                document.getElementById('carrossel-imagens').style.display = 'none';
            }

            window.removerImagemAtual = function() {
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
            };

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

            window.mudarImagem = function(direcao) {
                if (imagens.length > 0) {
                    indiceAtual = (indiceAtual + direcao + imagens.length) % imagens.length;
                    document.getElementById('imagem-carrossel').src = imagens[indiceAtual];
                }
            };

            window.mudarImagemModal = function(direcao) {
                if (imagens.length > 0) {
                    indiceAtual = (indiceAtual + direcao + imagens.length) % imagens.length;
                    document.getElementById('imagem-ampliada').src = imagens[indiceAtual];
                }
            };

            const imgCarrossel = document.getElementById('imagem-carrossel');
            if (imgCarrossel) {
                imgCarrossel.onclick = function(e) {
                    e.stopPropagation();
                    if (imagens.length > 0) {
                        document.getElementById('imagem-ampliada').src = imagens[indiceAtual];
                        document.getElementById('modal-imagem').style.display = 'flex';
                    }
                };
            }

            window.fecharModalImagem = function() {
                document.getElementById('modal-imagem').style.display = 'none';
            };

            // CORREÇÃO PRINCIPAL: Adiciona listener do formulário de forma segura
            const formEvento = document.getElementById('form-evento');
            if (formEvento) {
                formEvento.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const camposFaltantes = validarCamposObrigatorios();
                    
                    if (camposFaltantes.length > 0) {
                        let mensagem = 'Por favor, preencha os seguintes campos obrigatórios:\n\n';
                        camposFaltantes.forEach((campo, index) => {
                            mensagem += `${index + 1}. ${campo}\n`;
                        });
                        mensagem += '\nOs campos faltantes foram destacados em vermelho.';
                        alert(mensagem);
                        return;
                    }

                    const formData = new FormData(this);

                    if (colaboradores.length > 0) {
                        formData.append('colaboradores', JSON.stringify(colaboradores));
                    }

                    const btnCriar = document.getElementById('btn-criar');
                    btnCriar.disabled = true;
                    btnCriar.textContent = 'Criando...';

                    fetch('AdicionarEvento.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Erro HTTP: ' + response.status);
                            }
                            const contentType = response.headers.get('content-type');
                            if (!contentType || !contentType.includes('application/json')) {
                                return response.text().then(text => {
                                    console.error('Resposta não é JSON:', text);
                                    throw new Error('Resposta do servidor não é JSON válido');
                                });
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log('Resposta do servidor:', data);
                            if (data.sucesso === true) {
                                alert(data.mensagem || 'Evento criado com sucesso!');
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
                            console.error('Erro completo:', error);
                            alert('Erro ao criar evento: ' + error.message + '. Verifique o console para mais detalhes.');
                            btnCriar.disabled = false;
                            btnCriar.textContent = 'Criar evento';
                        });
                });
            }

            validarFormulario();
        })();
    </script>
</body>

</html>

