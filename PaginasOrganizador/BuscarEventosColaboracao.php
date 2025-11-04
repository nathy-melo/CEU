<?php
// Busca eventos onde o usuário logado é colaborador
header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
    echo json_encode(['sucesso' => false, 'erro' => 'nao_autenticado']);
    exit;
}

require_once __DIR__ . '/../BancoDados/conexao.php';

$cpf = $_SESSION['cpf'];

try {
    // Garante que a tabela colaboradores_evento existe
    $sqlCreate = "CREATE TABLE IF NOT EXISTS colaboradores_evento (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cod_evento INT NOT NULL,
        CPF CHAR(11) NOT NULL,
        papel ENUM('colaborador','coorganizador') NOT NULL DEFAULT 'colaborador',
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_evento_cpf (cod_evento, CPF),
        FOREIGN KEY (cod_evento) REFERENCES evento(cod_evento) ON DELETE CASCADE,
        FOREIGN KEY (CPF) REFERENCES usuario(CPF) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($conexao, $sqlCreate);

    // Busca eventos onde o usuário é colaborador (não organizador)
    $sql = "SELECT e.cod_evento, e.nome, e.inicio, e.conclusao, e.categoria, e.lugar, e.modalidade, e.certificado,
                   DATE_FORMAT(e.inicio, '%d/%m/%y') as data_formatada,
                   CASE 
                       WHEN e.conclusao < NOW() THEN 'Concluído'
                       WHEN e.inicio > NOW() THEN 'Agendado'
                       ELSE 'Em andamento'
                   END as status
            FROM colaboradores_evento c
            INNER JOIN evento e ON c.cod_evento = e.cod_evento
            WHERE c.CPF = ?
            AND NOT EXISTS (
                SELECT 1 FROM organiza o 
                WHERE o.cod_evento = e.cod_evento AND o.CPF = ?
            )
            ORDER BY e.inicio DESC";

    $stmt = mysqli_prepare($conexao, $sql);
    if (!$stmt) {
        echo json_encode(['sucesso' => false, 'erro' => 'erro_preparar_consulta', 'detalhe' => mysqli_error($conexao)]);
        exit;
    }

    mysqli_stmt_bind_param($stmt, 'ss', $cpf, $cpf);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    $eventos = [];
    while ($row = mysqli_fetch_assoc($resultado)) {
        $row['certificado'] = ((int)$row['certificado'] === 1) ? 'Sim' : 'Não';
        $eventos[] = $row;
    }

    mysqli_stmt_close($stmt);

    echo json_encode(['sucesso' => true, 'eventos' => $eventos]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'erro_interno', 'detalhe' => $e->getMessage()]);
}
