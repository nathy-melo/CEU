<?php
// Aprova ou recusa uma solicitação de colaboração
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../BancoDados/conexao.php';
session_start();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['sucesso' => false, 'erro' => 'metodo_invalido']);
        exit;
    }
    if (!isset($_SESSION['cpf'])) {
        echo json_encode(['sucesso' => false, 'erro' => 'nao_autenticado']);
        exit;
    }

    $cpfUsuario = $_SESSION['cpf'];
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $acao = $_POST['acao'] ?? '';
    if ($id <= 0 || !in_array($acao, ['aprovar', 'recusar'], true)) {
        echo json_encode(['sucesso' => false, 'erro' => 'parametros_invalidos']);
        exit;
    }

    // Descobre cod_evento da solicitação e valida permissão (organizador)
    $sql = "SELECT cod_evento, cpf_solicitante FROM solicitacoes_colaboracao WHERE id = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $sol = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if (!$sol) {
        echo json_encode(['sucesso' => false, 'erro' => 'solicitacao_nao_encontrada']);
        exit;
    }

    $codEvento = (int)$sol['cod_evento'];
    $cpfSolicitante = $sol['cpf_solicitante'];

    $sqlOrg = "SELECT 1 FROM organiza WHERE cod_evento = ? AND CPF = ? LIMIT 1";
    $stmt = mysqli_prepare($conexao, $sqlOrg);
    mysqli_stmt_bind_param($stmt, 'is', $codEvento, $cpfUsuario);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) === 0) {
        echo json_encode(['sucesso' => false, 'erro' => 'sem_permissao']);
        exit;
    }
    mysqli_stmt_close($stmt);

    if ($acao === 'aprovar') {
        // Adiciona colaborador e marca aprovada
        $stmt = mysqli_prepare($conexao, "INSERT INTO colaboradores_evento (cod_evento, CPF, papel) VALUES (?,?, 'colaborador')
            ON DUPLICATE KEY UPDATE papel = VALUES(papel)");
        mysqli_stmt_bind_param($stmt, 'is', $codEvento, $cpfSolicitante);
        if (!mysqli_stmt_execute($stmt)) {
            echo json_encode(['sucesso' => false, 'erro' => 'falha_adicionar_colaborador']);
            exit;
        }
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conexao, "UPDATE solicitacoes_colaboracao SET status='aprovada', data_resolucao = CURRENT_TIMESTAMP WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Notificar solicitante
        $nomeEvento = '';
        if ($r = mysqli_query($conexao, "SELECT nome FROM evento WHERE cod_evento = " . (int)$codEvento . " LIMIT 1")) {
            if ($e = mysqli_fetch_assoc($r)) { $nomeEvento = $e['nome'] ?? ''; }
            mysqli_free_result($r);
        }
        $msg = "Sua solicitação para colaborar no evento '" . ($nomeEvento ?: (string)$codEvento) . "' foi aprovada!";
        $stmtN = mysqli_prepare($conexao, "INSERT INTO notificacoes (CPF, tipo, mensagem, cod_evento, lida) VALUES (?,?,?,?,0)");
        $tipo = 'colaboracao_aprovada';
        mysqli_stmt_bind_param($stmtN, 'sssi', $cpfSolicitante, $tipo, $msg, $codEvento);
        @mysqli_stmt_execute($stmtN);
        @mysqli_stmt_close($stmtN);

        echo json_encode(['sucesso' => true, 'mensagem' => 'Solicitação aprovada']);
        exit;
    }

    if ($acao === 'recusar') {
        $stmt = mysqli_prepare($conexao, "UPDATE solicitacoes_colaboracao SET status='recusada', data_resolucao = CURRENT_TIMESTAMP WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        // Notificar solicitante
        $nomeEvento = '';
        if ($r = mysqli_query($conexao, "SELECT nome FROM evento WHERE cod_evento = " . (int)$codEvento . " LIMIT 1")) {
            if ($e = mysqli_fetch_assoc($r)) { $nomeEvento = $e['nome'] ?? ''; }
            mysqli_free_result($r);
        }
        $msg = "Sua solicitação para colaborar no evento '" . ($nomeEvento ?: (string)$codEvento) . "' foi recusada.";
        $stmtN = mysqli_prepare($conexao, "INSERT INTO notificacoes (CPF, tipo, mensagem, cod_evento, lida) VALUES (?,?,?,?,0)");
        $tipo = 'colaboracao_recusada';
        mysqli_stmt_bind_param($stmtN, 'sssi', $cpfSolicitante, $tipo, $msg, $codEvento);
        @mysqli_stmt_execute($stmtN);
        @mysqli_stmt_close($stmtN);
        echo json_encode(['sucesso' => true, 'mensagem' => 'Solicitação recusada']);
        exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'erro_interno', 'detalhe' => $e->getMessage()]);
}
