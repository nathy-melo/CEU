<?php
// Lista solicitações de colaboração pendentes para um evento (somente organizador)
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../BancoDados/conexao.php';
session_start();

try {
    if (!isset($_SESSION['cpf'])) {
        echo json_encode(['sucesso' => false, 'erro' => 'nao_autenticado']);
        exit;
    }
    $cpfUsuario = $_SESSION['cpf'];
    $codEvento = isset($_GET['cod_evento']) ? (int) $_GET['cod_evento'] : 0;
    if ($codEvento <= 0) {
        echo json_encode(['sucesso' => false, 'erro' => 'cod_evento_invalido']);
        exit;
    }

    // Verifica se é organizador do evento
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

    $sql = "SELECT s.id, s.cpf_solicitante AS CPF, u.Nome as nome, u.Email as email, s.status, s.data_criacao
            FROM solicitacoes_colaboracao s
            JOIN usuario u ON u.CPF = s.cpf_solicitante
            WHERE s.cod_evento = ? AND s.status = 'pendente'
            ORDER BY s.data_criacao DESC";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $codEvento);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $itens = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $itens[] = $row;
    }
    mysqli_stmt_close($stmt);

    echo json_encode(['sucesso' => true, 'solicitacoes' => $itens]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'erro_interno', 'detalhe' => $e->getMessage()]);
}
