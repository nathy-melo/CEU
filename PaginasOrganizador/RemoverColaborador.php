<?php
// Remove colaborador de um evento
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
    $codEvento = isset($_POST['cod_evento']) ? (int) $_POST['cod_evento'] : 0;
    $cpfRemover = preg_replace('/\D+/', '', $_POST['cpf'] ?? '');
    if ($codEvento <= 0 || strlen($cpfRemover) !== 11) {
        echo json_encode(['sucesso' => false, 'erro' => 'parametros_invalidos']);
        exit;
    }

    // Apenas organizador do evento pode remover
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

    // Não permitir remover organizadores via essa rota
    $stmt = mysqli_prepare($conexao, "SELECT 1 FROM organiza WHERE cod_evento = ? AND CPF = ?");
    mysqli_stmt_bind_param($stmt, 'is', $codEvento, $cpfRemover);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo json_encode(['sucesso' => false, 'erro' => 'nao_remover_organizador']);
        exit;
    }
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conexao, "DELETE FROM colaboradores_evento WHERE cod_evento = ? AND CPF = ?");
    mysqli_stmt_bind_param($stmt, 'is', $codEvento, $cpfRemover);
    if (!mysqli_stmt_execute($stmt)) {
        echo json_encode(['sucesso' => false, 'erro' => 'falha_remover']);
        exit;
    }
    mysqli_stmt_close($stmt);

    // Notificar usuário removido
    $nomeEvento = '';
    if ($r = mysqli_query($conexao, "SELECT nome FROM evento WHERE cod_evento = " . (int)$codEvento . " LIMIT 1")) {
        if ($e = mysqli_fetch_assoc($r)) { $nomeEvento = $e['nome'] ?? ''; }
        mysqli_free_result($r);
    }
    $msg = "Sua colaboração no evento '" . ($nomeEvento ?: (string)$codEvento) . "' foi removida.";
    $stmtN = mysqli_prepare($conexao, "INSERT INTO notificacoes (CPF, tipo, mensagem, cod_evento, lida) VALUES (?,?,?,?,0)");
    $tipo = 'colaborador_removido';
    mysqli_stmt_bind_param($stmtN, 'sssi', $cpfRemover, $tipo, $msg, $codEvento);
    @mysqli_stmt_execute($stmtN);
    @mysqli_stmt_close($stmtN);

    echo json_encode(['sucesso' => true, 'mensagem' => 'Colaborador removido']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'erro_interno', 'detalhe' => $e->getMessage()]);
}
