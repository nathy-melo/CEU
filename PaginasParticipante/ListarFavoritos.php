<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
        http_response_code(401);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado']);
        exit;
    }

    require_once __DIR__ . '/../BancoDados/conexao.php';

    // Garante a existência da tabela
    $sqlCriar = "CREATE TABLE IF NOT EXISTS favoritos_evento (
        CPF varchar(14) NOT NULL,
        cod_evento int NOT NULL,
        data_criacao timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (CPF, cod_evento)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conexao, $sqlCriar);

    $cpf = $_SESSION['cpf'];

    $sql = "SELECT e.cod_evento, e.nome, e.categoria, e.inicio, e.lugar, e.modalidade, e.imagem
            FROM favoritos_evento f
            INNER JOIN evento e ON e.cod_evento = f.cod_evento
            WHERE f.CPF = ?
            ORDER BY e.inicio DESC";

    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 's', $cpf);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $lista = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $lista[] = [
            'cod_evento' => (int)$row['cod_evento'],
            'nome' => $row['nome'],
            'categoria' => $row['categoria'],
            'inicio' => $row['inicio'],
            'lugar' => $row['lugar'],
            'modalidade' => $row['modalidade'],
            'imagem' => $row['imagem']
        ];
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexao);

    echo json_encode(['sucesso' => true, 'favoritos' => $lista]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro interno', 'detalhe' => $e->getMessage()]);
}
