<?php
// Configuração de erros: log para arquivo, não exibe na tela
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php_errors.log');

// Limpa qualquer output anterior
ob_start();

// Inicia a sessão se ainda não foi iniciada
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Processa o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Limpa qualquer output anterior
        ob_clean();
        
        // Define o cabeçalho JSON ANTES de qualquer output
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
    $modeloCertificadoParticipante = $_POST['modelo_certificado_participante'] ?? 'ModeloExemplo.pptx';
    $modeloCertificadoOrganizador = $_POST['modelo_certificado_organizador'] ?? 'ModeloExemploOrganizador.pptx';
    $cargaHorariaParticipante = $_POST['carga_horaria_participante'] ?? '';
    $cargaHorariaOrganizador = $_POST['carga_horaria_organizador'] ?? '';

    // Função para converter HH:MM para decimal
    function converterHoraParaDecimal($horaStr) {
        if (empty($horaStr) || $horaStr === '-') return 0;
        $partes = explode(':', $horaStr);
        if (count($partes) !== 2) return 0;
        $horas = intval($partes[0]);
        $minutos = intval($partes[1]);
        return $horas + ($minutos / 60.0);
    }

    // Valida e converte carga horária do participante
    if (empty($cargaHorariaParticipante)) {
        echo json_encode(['erro' => 'Carga horária do participante é obrigatória']);
        exit;
    }
    if (!preg_match('/^[0-9]{1,3}:[0-5][0-9]$/', $cargaHorariaParticipante)) {
        echo json_encode(['erro' => 'Formato de carga horária do participante inválido. Use HH:MM (ex: 08:00)']);
        exit;
    }
    $duracaoParticipante = converterHoraParaDecimal($cargaHorariaParticipante);

    // Valida e converte carga horária do organizador (opcional)
    // Se não for definido, copia a duração do participante
    if (!empty($cargaHorariaOrganizador)) {
        if (!preg_match('/^[0-9]{1,3}:[0-5][0-9]$/', $cargaHorariaOrganizador)) {
            echo json_encode(['erro' => 'Formato de carga horária do organizador inválido. Use HH:MM (ex: 16:00)']);
            exit;
        }
        $duracaoOrganizador = converterHoraParaDecimal($cargaHorariaOrganizador);
    } else {
        // Se não preencheu, copia do participante
        $duracaoOrganizador = $duracaoParticipante;
    }

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

    // Valida formato de data e hora
    try {
        $dataInicioObj = new DateTime($inicio);
        $dataConclusaoObj = new DateTime($conclusao);
    } catch (Exception $e) {
        echo json_encode(['erro' => 'Data ou horário inválidos. Verifique os valores informados.']);
        exit;
    }

    // Valida se a data de início não está no passado
    $dataHoraAtual = new DateTime();
    if ($dataInicioObj < $dataHoraAtual) {
        echo json_encode(['erro' => 'Não é possível criar um evento com data de início no passado']);
        exit;
    }

    // Combina data e hora das inscrições (se fornecidas)
    $inicioInscricao = null;
    $fimInscricao = null;
    if (!empty($dataInicioInscricao) && !empty($horarioInicioInscricao)) {
        $inicioInscricao = $dataInicioInscricao . ' ' . $horarioInicioInscricao . ':00';
        try {
            new DateTime($inicioInscricao);
        } catch (Exception $e) {
            echo json_encode(['erro' => 'Data ou horário de início das inscrições inválidos.']);
            exit;
        }
    }
    if (!empty($dataFimInscricao) && !empty($horarioFimInscricao)) {
        $fimInscricao = $dataFimInscricao . ' ' . $horarioFimInscricao . ':00';
        try {
            new DateTime($fimInscricao);
        } catch (Exception $e) {
            echo json_encode(['erro' => 'Data ou horário de fim das inscrições inválidos.']);
            exit;
        }
    }

    // Converte NULL para string vazia para bind_param (será tratado como NULL no banco)
    $inicioInscricaoStr = $inicioInscricao ?? '';
    $fimInscricaoStr = $fimInscricao ?? '';

    // Usa a carga horária do participante informada pelo usuário
    $duracao = $duracaoParticipante;

    // Valida: se o evento é no mesmo dia, não pode ter mais de 16 horas
    $intervalo = $dataInicioObj->diff($dataConclusaoObj);
    if ($intervalo->days === 0 && $duracao > 16) {
        echo json_encode(['erro' => 'Um evento de um único dia não pode ter mais de 16 horas de duração.']);
        exit;
    }

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
        function garantirColunaEvento(mysqli $cx, string $coluna, string $definicao)
        {
            $escCol = mysqli_real_escape_string($cx, $coluna);
            $res = mysqli_query($cx, "SHOW COLUMNS FROM evento LIKE '$escCol'");
            if ($res && mysqli_num_rows($res) === 0) {
                mysqli_query($cx, "ALTER TABLE evento ADD COLUMN `$coluna` $definicao");
            }
            if ($res) {
                mysqli_free_result($res);
            }
        }
        garantirColunaEvento($conexao, 'inicio_inscricao', 'DATETIME NULL');
        garantirColunaEvento($conexao, 'fim_inscricao', 'DATETIME NULL');
        garantirColunaEvento($conexao, 'tipo_certificado', "VARCHAR(50) NULL DEFAULT 'Sem certificacao'");
        garantirColunaEvento($conexao, 'modelo_certificado_participante', "VARCHAR(255) NULL DEFAULT 'ModeloExemplo.pptx'");
        garantirColunaEvento($conexao, 'modelo_certificado_organizador', "VARCHAR(255) NULL DEFAULT 'ModeloExemploOrganizador.pptx'");
        garantirColunaEvento($conexao, 'duracao_organizador', 'FLOAT NULL COMMENT "Carga horária do organizador"');

        // Insere evento (mantém campo imagem com a principal para compatibilidade)
        $sqlEvento = "INSERT INTO evento (cod_evento, categoria, nome, lugar, descricao, publico_alvo, inicio, conclusao, duracao, duracao_organizador, certificado, modalidade, imagem, inicio_inscricao, fim_inscricao, tipo_certificado, modelo_certificado_participante, modelo_certificado_organizador) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmtEvento = mysqli_prepare($conexao, $sqlEvento);
        if (!$stmtEvento) {
            throw new Exception('Erro ao preparar inserção do evento: ' . mysqli_error($conexao));
        }
        // Tipos: cod_evento(i), categoria(s), nome(s), lugar(s), descricao(s), publico_alvo(s), inicio(s), conclusao(s), duracao(d), duracao_organizador(d), certificado(i), modalidade(s), imagem(s), inicio_inscricao(s), fim_inscricao(s), tipo_certificado(s), modelo_certificado_participante(s), modelo_certificado_organizador(s)
        // Para campos NULL, usa string vazia (será tratado como NULL no banco se o campo aceitar NULL)
        $inicioInscricaoFinal = (!empty($inicioInscricaoStr)) ? $inicioInscricaoStr : '';
        $fimInscricaoFinal = (!empty($fimInscricaoStr)) ? $fimInscricaoStr : '';
        // duracao_organizador recebe o valor que foi copiado do participante se não preenchido
        $duracaoOrganizadorFinal = $duracaoOrganizador;

        mysqli_stmt_bind_param(
            $stmtEvento,
            "isssssssddisssssss",
            $codEvento,
            $categoria,
            $nome,
            $local,
            $descricao,
            $publicoAlvo,
            $inicio,
            $conclusao,
            $duracao,
            $duracaoOrganizadorFinal,
            $certificadoBool,
            $modalidade,
            $caminhoImagemPrincipal,
            $inicioInscricaoFinal,
            $fimInscricaoFinal,
            $certificado,
            $modeloCertificadoParticipante,
            $modeloCertificadoOrganizador
        );

        if (!mysqli_stmt_execute($stmtEvento)) {
            throw new Exception('Erro ao inserir evento: ' . mysqli_error($conexao));
        }

        // Insere múltiplas imagens na tabela imagens_evento
        if (!empty($imagensUpload)) {
            $sqlImagem = "INSERT INTO imagens_evento (cod_evento, caminho_imagem, ordem, principal) VALUES (?, ?, ?, ?)";
            $stmtImagem = mysqli_prepare($conexao, $sqlImagem);
            if (!$stmtImagem) {
                throw new Exception('Erro ao preparar inserção de imagem: ' . mysqli_error($conexao));
            }

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
        if (!$stmtOrganiza) {
            throw new Exception('Erro ao preparar vínculo do organizador: ' . mysqli_error($conexao));
        }
        mysqli_stmt_bind_param($stmtOrganiza, "is", $codEvento, $cpfOrganizador);

        if (!mysqli_stmt_execute($stmtOrganiza)) {
            throw new Exception('Erro ao vincular organizador: ' . mysqli_error($conexao));
        }
        mysqli_stmt_close($stmtOrganiza);

        // Adiciona colaboradores se houver
        if (isset($_POST['colaboradores']) && !empty($_POST['colaboradores'])) {
            $colaboradores = json_decode($_POST['colaboradores'], true);

            if (is_array($colaboradores) && count($colaboradores) > 0) {
                // Garante que a coluna certificado_emitido existe (compatível com MySQL < 8)
                $resCol = mysqli_query($conexao, "SHOW COLUMNS FROM colaboradores_evento LIKE 'certificado_emitido'");
                if ($resCol && mysqli_num_rows($resCol) === 0) {
                    mysqli_query($conexao, "ALTER TABLE colaboradores_evento ADD COLUMN certificado_emitido tinyint(1) DEFAULT 0");
                }
                if ($resCol) {
                    mysqli_free_result($resCol);
                }

                $sqlColab = "INSERT INTO colaboradores_evento (cod_evento, CPF, certificado_emitido) VALUES (?, ?, 0)";
                $stmtColab = mysqli_prepare($conexao, $sqlColab);
                if (!$stmtColab) {
                    throw new Exception('Erro ao preparar inserção de colaborador: ' . mysqli_error($conexao));
                }

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

        error_log('[AdicionarEvento] Erro na transação: ' . $e->getMessage());
        echo json_encode(['erro' => $e->getMessage()]);
        exit;
    }
    } catch (Throwable $th) {
        // Captura qualquer erro fatal (PHP 7+)
        ob_clean();
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        error_log('[AdicionarEvento] Erro fatal capturado: ' . $th->getMessage() . ' em ' . $th->getFile() . ':' . $th->getLine());
        error_log('[AdicionarEvento] Stack trace: ' . $th->getTraceAsString());
        echo json_encode(['erro' => 'Erro interno no servidor: ' . $th->getMessage()]);
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

        .BotaoCriar {
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
            display: flex;
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
            gap: 16px;
            padding: 24px 16px;
            background: var(--branco);
            border: 2px dashed #ddd;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            min-height: 100px;
        }

        .file-upload-label:hover {
            background: #f8f9fa;
            border-color: var(--botao);
        }

        .file-upload-icon {
            width: 56px;
            height: 56px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 0;
        }

        .file-upload-icon svg {
            width: 100%;
            height: 100%;
            fill: var(--botao);
            margin: 0;
            padding: 0;
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

        /* Bloqueia scroll da página quando modal está aberto */
        body.modal-template-aberto {
            overflow: hidden;
        }
    </style>
</head>

<body>
    <div id="main-content">
        <form id="form-evento" class="cartao-evento" method="post" action="" onsubmit="return false;">
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
            <div class="CargaHorariaParticipante grupo-campo">
                <label for="carga-horaria-participante"><span style="color: red;">*</span> Carga Horária do Participante:</label>
                <input type="text" id="carga-horaria-participante" name="carga_horaria_participante" class="campo-input" placeholder="Ex: 08:00" required autocomplete="off" pattern="[0-9]{2}:[0-9]{2}" title="Formato: HH:MM">
            </div>
            <div class="CargaHorariaOrganizador grupo-campo">
                <label for="carga-horaria-organizador">Carga Horária do Organizador:</label>
                <input type="text" id="carga-horaria-organizador" name="carga_horaria_organizador" class="campo-input" placeholder="Ex: 16:00" autocomplete="off" pattern="[0-9]{2}:[0-9]{2}" title="Formato: HH:MM">
            </div>
            <div class="DataHorarioInicio grupo-campo">
                <label for="data-inicio"><span style="color: red;">*</span> Data e Horário de Início do Evento:</label>
                <div class="campo-data-horario">
                    <input type="date" id="data-inicio" name="data_inicio" class="campo-input" min="1900-01-01" max="2099-12-31" required autocomplete="off">
                    <input type="time" id="horario-inicio" name="horario_inicio" class="campo-input" required autocomplete="off">
                </div>
            </div>
            <div class="DataHorarioFim grupo-campo">
                <label for="data-fim"><span style="color: red;">*</span> Data e Horário de Fim do Evento:</label>
                <div class="campo-data-horario">
                    <input type="date" id="data-fim" name="data_fim" class="campo-input" min="1900-01-01" max="2099-12-31" required autocomplete="off">
                    <input type="time" id="horario-fim" name="horario_fim" class="campo-input" required autocomplete="off">
                </div>
            </div>
            <div class="DataHorarioInscricaoInicio grupo-campo">
                <label for="data-inicio-inscricao">Início das Inscrições:</label>
                <div class="campo-data-horario">
                    <input type="date" id="data-inicio-inscricao" name="data_inicio_inscricao" class="campo-input" min="1900-01-01" max="2099-12-31" autocomplete="off">
                    <input type="time" id="horario-inicio-inscricao" name="horario_inicio_inscricao" class="campo-input" autocomplete="off">
                </div>
            </div>
            <div class="DataHorarioInscricaoFim grupo-campo">
                <label for="data-fim-inscricao">Fim das Inscrições:</label>
                <div class="campo-data-horario">
                    <input type="date" id="data-fim-inscricao" name="data_fim_inscricao" class="campo-input" min="1900-01-01" max="2099-12-31" autocomplete="off">
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
                    <option value="Extensão">Extensão</option>
                    <option value="Outro">Outro</option>
                </select>
            </div>
            <div class="ModeloCertificadoParticipante grupo-campo">
                <label for="modelo-certificado-participante">Modelo de Certificado (Participante):</label>
                <div class="campo-modelo-wrapper">
                    <select id="modelo-certificado-participante" name="modelo_certificado_participante" class="campo-select" autocomplete="off">
                        <option value="ModeloExemplo.pptx" selected>Modelo Padrão</option>
                    </select>
                    <button type="button" class="btn-adicionar-modelo" onclick="abrirModalTemplate('participante')" title="Adicionar modelo personalizado">+</button>
                </div>
            </div>
            <div class="ModeloCertificadoOrganizador grupo-campo">
                <label for="modelo-certificado-organizador">Modelo de Certificado (Organizador):</label>
                <div class="campo-modelo-wrapper">
                    <select id="modelo-certificado-organizador" name="modelo_certificado_organizador" class="campo-select" autocomplete="off">
                        <option value="ModeloExemploOrganizador.pptx" selected>Modelo Padrão</option>
                    </select>
                    <button type="button" class="btn-adicionar-modelo" onclick="abrirModalTemplate('organizador')" title="Adicionar modelo personalizado">+</button>
                </div>
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

        <!-- Modal Upload Modelo de Certificado -->
        <div id="modal-template" class="modal-template-overlay" style="display: none;" onclick="fecharModalTemplateSeForFundo(event)">
            <div class="modal-template-content" onclick="event.stopPropagation()">
                <div class="modal-template-header">
                    <h2>Adicionar Modelo de Certificado</h2>
                    <button class="btn-fechar-modal-template" onclick="fecharModalTemplate()">&times;</button>
                </div>
                <div class="modal-template-body">
                    <div class="info-modelo">
                        <h3>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; margin-right: 8px; vertical-align: middle;">
                                <path d="M9 11H7v6h2M13 11h-2v6h2M17 11h-2v6h2M9.5 3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V9" />
                                <circle cx="18.5" cy="5.5" r="2.5" />
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
    </div>
    <script>
        // Previne execução múltipla do script quando carregado via AJAX
        (function() {
            // Limpa flag anterior se existir
            if (window.adicionarEventoScriptExecutado) {
                console.log('[DEBUG] Reinicializando script AdicionarEvento');
            }
            window.adicionarEventoScriptExecutado = true;

            let imagens = [];
            let indiceAtual = 0;
            let colaboradores = [];
            let tipoTemplateAtual = null; // 'participante' ou 'organizador'

            // FUNÇÕES DE TEMPLATE DE CERTIFICADO

            // Carrega modelos disponíveis ao iniciar a página
            function carregarModelosDisponiveis() {
                fetch('ListarModelosCertificado.php')
                    .then(r => r.json())
                    .then(data => {
                        if (data.sucesso && data.templates) {
                            atualizarSelectsModelos(data.templates);
                        }
                    })
                    .catch(err => console.error('Erro ao carregar modelos:', err));
            }

            function atualizarSelectsModelos(templates) {
                const selectParticipante = document.getElementById('modelo-certificado-participante');
                const selectOrganizador = document.getElementById('modelo-certificado-organizador');

                // Limpa opções atuais (exceto a primeira que é o padrão)
                while (selectParticipante.options.length > 1) {
                    selectParticipante.remove(1);
                }
                while (selectOrganizador.options.length > 1) {
                    selectOrganizador.remove(1);
                }

                // Adiciona templates personalizados
                templates.forEach(template => {
                    if (!template.padrao) {
                        const optionParticipante = new Option(template.nomeExibicao, template.nome);
                        const optionOrganizador = new Option(template.nomeExibicao, template.nome);
                        selectParticipante.add(optionParticipante);
                        selectOrganizador.add(optionOrganizador);
                    }
                });
            }

            window.abrirModalTemplate = function(tipo) {
                tipoTemplateAtual = tipo;
                document.getElementById('modal-template').style.display = 'flex';
                document.body.classList.add('modal-template-aberto');
                limparModalTemplate();
            };

            window.fecharModalTemplate = function() {
                document.getElementById('modal-template').style.display = 'none';
                document.body.classList.remove('modal-template-aberto');
                limparModalTemplate();
            };

            window.fecharModalTemplateSeForFundo = function(event) {
                if (event.target.id === 'modal-template') {
                    fecharModalTemplate();
                }
            };

            function limparModalTemplate() {
                document.getElementById('template-file-input').value = '';
                document.getElementById('file-selected-info').style.display = 'none';
                document.getElementById('btn-enviar-template').disabled = true;
                tipoTemplateAtual = null;
            }

            window.arquivoTemplateSelecionado = function(event) {
                const arquivo = event.target.files[0];
                if (arquivo) {
                    const extensao = arquivo.name.split('.').pop().toLowerCase();
                    const extensoesPermitidas = ['pptx', 'ppt', 'odp'];

                    if (!extensoesPermitidas.includes(extensao)) {
                        alert('Formato não permitido. Use: PPTX, PPT ou ODP');
                        event.target.value = '';
                        return;
                    }

                    const tamanhoMaxMB = 50;
                    const tamanhoMB = arquivo.size / 1024 / 1024;

                    if (tamanhoMB > tamanhoMaxMB) {
                        alert(`Arquivo muito grande (${tamanhoMB.toFixed(2)}MB). Tamanho máximo: ${tamanhoMaxMB}MB`);
                        event.target.value = '';
                        return;
                    }

                    document.getElementById('file-name').textContent = arquivo.name;
                    document.getElementById('file-selected-info').style.display = 'block';
                    document.getElementById('btn-enviar-template').disabled = false;
                }
            };

            window.enviarModeloTemplate = function() {
                const fileInput = document.getElementById('template-file-input');
                const arquivo = fileInput.files[0];

                if (!arquivo) {
                    alert('Nenhum arquivo selecionado');
                    return;
                }

                const formData = new FormData();
                formData.append('modelo_certificado', arquivo);

                const btnEnviar = document.getElementById('btn-enviar-template');
                btnEnviar.disabled = true;
                btnEnviar.textContent = 'Enviando...';

                fetch('UploadModeloCertificado.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.sucesso) {
                            alert(data.mensagem || 'Modelo enviado com sucesso!');

                            // Adiciona o novo modelo ao select apropriado
                            const selectId = tipoTemplateAtual === 'participante' ?
                                'modelo-certificado-participante' :
                                'modelo-certificado-organizador';
                            const select = document.getElementById(selectId);
                            const novaOpcao = new Option(data.nomeOriginal, data.nomeArquivo);
                            select.add(novaOpcao);
                            select.value = data.nomeArquivo;

                            fecharModalTemplate();

                            // Recarrega a lista completa
                            carregarModelosDisponiveis();
                        } else {
                            alert('Erro: ' + (data.erro || 'Erro desconhecido'));
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        alert('Erro ao enviar arquivo: ' + error.message);
                    })
                    .finally(() => {
                        btnEnviar.disabled = false;
                        btnEnviar.textContent = 'Enviar';
                    });
            };

            // FIM FUNÇÕES DE TEMPLATE

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
                const campos = [{
                        id: 'nome',
                        nome: 'Nome',
                        elemento: document.getElementById('nome')
                    },
                    {
                        id: 'local',
                        nome: 'Local',
                        elemento: document.getElementById('local')
                    },
                    {
                        id: 'carga-horaria-participante',
                        nome: 'Carga Horária do Participante',
                        elemento: document.getElementById('carga-horaria-participante')
                    },
                    {
                        id: 'data-inicio',
                        nome: 'Data de Início',
                        elemento: document.getElementById('data-inicio')
                    },
                    {
                        id: 'horario-inicio',
                        nome: 'Horário de Início',
                        elemento: document.getElementById('horario-inicio')
                    },
                    {
                        id: 'data-fim',
                        nome: 'Data de Fim',
                        elemento: document.getElementById('data-fim')
                    },
                    {
                        id: 'horario-fim',
                        nome: 'Horário de Fim',
                        elemento: document.getElementById('horario-fim')
                    },
                    {
                        id: 'publico-alvo',
                        nome: 'Público Alvo',
                        elemento: document.getElementById('publico-alvo')
                    },
                    {
                        id: 'categoria',
                        nome: 'Categoria',
                        elemento: document.getElementById('categoria')
                    },
                    {
                        id: 'modalidade',
                        nome: 'Modalidade',
                        elemento: document.getElementById('modalidade')
                    },
                    {
                        id: 'certificado',
                        nome: 'Tipo de Certificado',
                        elemento: document.getElementById('certificado')
                    },
                    {
                        id: 'descricao',
                        nome: 'Descrição',
                        elemento: document.getElementById('descricao')
                    }
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
                            // Validação adicional para carga horária
                            if (campo.id === 'carga-horaria-participante' || campo.id === 'carga-horaria-organizador') {
                                const regex = /^[0-9]{1,3}:[0-5][0-9]$/;
                                if (!regex.test(valor)) {
                                    camposFaltantes.push(campo.nome + ' (formato inválido, use HH:MM)');
                                    elementosFaltantes.push(campo.elemento);
                                } else {
                                    campo.elemento.style.borderColor = '';
                                    campo.elemento.style.boxShadow = '';
                                }
                            } else {
                                campo.elemento.style.borderColor = '';
                                campo.elemento.style.boxShadow = '';
                            }
                        }
                    }
                });

                elementosFaltantes.forEach(elemento => {
                    elemento.style.borderColor = '#f44336';
                    elemento.style.boxShadow = '0 0 0 3px rgba(244, 67, 54, 0.2)';
                    if (elemento === elementosFaltantes[0]) {
                        elemento.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                        elemento.focus();
                    }
                });

                return camposFaltantes;
            }

            //FUNÇÕES DE COLABORADORES
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

                colaboradores.push({
                    cpf,
                    nome,
                    email
                });
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
                console.log('[DEBUG] Formulário encontrado, anexando listener de submit');
                
                // Remove listener anterior se existir
                formEvento.onsubmit = null;
                
                formEvento.addEventListener('submit', function(e) {
                    e.preventDefault();
                    console.log('[DEBUG] Submit interceptado, processando formulário...');

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
                    
                    console.log('[DEBUG] Enviando dados para AdicionarEvento.php...');

                    fetch('AdicionarEvento.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => {
                            console.log('[DEBUG] Resposta recebida. Status:', response.status);
                            console.log('[DEBUG] Content-Type:', response.headers.get('content-type'));
                            
                            if (!response.ok) {
                                throw new Error('Erro HTTP: ' + response.status);
                            }
                            const contentType = response.headers.get('content-type');
                            if (!contentType || !contentType.includes('application/json')) {
                                return response.text().then(text => {
                                    console.error('[DEBUG] Resposta NÃO é JSON. Primeiros 500 caracteres:');
                                    console.error(text.substring(0, 500));
                                    console.error('[DEBUG] Verifique se há erros PHP sendo exibidos antes do JSON');
                                    throw new Error('Resposta do servidor não é JSON válido. Verifique o console para detalhes.');
                                });
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log('[DEBUG] Resposta JSON do servidor:', data);
                            if (data.sucesso === true) {
                                console.log('[DEBUG] Evento criado com sucesso!');
                                alert(data.mensagem || 'Evento criado com sucesso!');
                                if (typeof carregarPagina === 'function') {
                                    carregarPagina('meusEventos');
                                } else {
                                    window.location.href = 'ContainerOrganizador.php?pagina=meusEventos';
                                }
                            } else {
                                console.error('[DEBUG] Erro retornado pelo servidor:', data.erro);
                                alert('Erro ao criar evento: ' + (data.erro || 'Erro desconhecido'));
                                btnCriar.disabled = false;
                                btnCriar.textContent = 'Criar evento';
                            }
                        })
                        .catch(error => {
                            console.error('[DEBUG] ERRO CAPTURADO');
                            console.error('[DEBUG] Mensagem:', error.message);
                            console.error('[DEBUG] Stack:', error.stack);
                            alert('Erro ao criar evento: ' + error.message + '\n\nVerifique o console (F12) para mais detalhes.');
                            btnCriar.disabled = false;
                            btnCriar.textContent = 'Criar evento';
                        });
                });
            } else {
                console.error('[DEBUG] ERRO: Formulário #form-evento não encontrado no DOM!');
            }

            validarFormulario();

            // Carrega modelos de certificado disponíveis
            carregarModelosDisponiveis();
        })();
    </script>
</body>

</html>