<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
        http_response_code(401);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Método inválido']);
        exit;
    }

    $codEvento = isset($_POST['cod_evento']) ? (int)$_POST['cod_evento'] : 0;
    if ($codEvento <= 0) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Código do evento inválido']);
        exit;
    }

    require_once __DIR__ . '/../BancoDados/conexao.php';

    // Garante a existência da tabela de favoritos
    $sqlCriar = "CREATE TABLE IF NOT EXISTS favoritos_evento (
        CPF varchar(14) NOT NULL,
        cod_evento int NOT NULL,
        data_criacao timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (CPF, cod_evento)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conexao, $sqlCriar);

    $cpf = $_SESSION['cpf'];

    // Verifica se já está favoritado
    $sqlCheck = "SELECT 1 FROM favoritos_evento WHERE CPF = ? AND cod_evento = ? LIMIT 1";
    $stmtCheck = mysqli_prepare($conexao, $sqlCheck);
    mysqli_stmt_bind_param($stmtCheck, 'si', $cpf, $codEvento);
    mysqli_stmt_execute($stmtCheck);
    $resCheck = mysqli_stmt_get_result($stmtCheck);
    $jaFav = $resCheck && mysqli_fetch_assoc($resCheck);
    mysqli_stmt_close($stmtCheck);

    if ($jaFav) {
        // Remover
        $sqlDel = "DELETE FROM favoritos_evento WHERE CPF = ? AND cod_evento = ?";
        $stmtDel = mysqli_prepare($conexao, $sqlDel);
        mysqli_stmt_bind_param($stmtDel, 'si', $cpf, $codEvento);
        $ok = mysqli_stmt_execute($stmtDel);
        mysqli_stmt_close($stmtDel);
        mysqli_close($conexao);
        echo json_encode(['sucesso' => $ok, 'favoritado' => false]);
        exit;
    } else {
        // Adicionar
        $sqlIns = "INSERT INTO favoritos_evento (CPF, cod_evento) VALUES (?, ?)";
        $stmtIns = mysqli_prepare($conexao, $sqlIns);
        mysqli_stmt_bind_param($stmtIns, 'si', $cpf, $codEvento);
        $ok = mysqli_stmt_execute($stmtIns);
        mysqli_stmt_close($stmtIns);
        mysqli_close($conexao);
        echo json_encode(['sucesso' => $ok, 'favoritado' => true]);
        exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro interno', 'detalhe' => $e->getMessage()]);
}
