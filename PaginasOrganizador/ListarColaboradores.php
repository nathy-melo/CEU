<?php
// Lista colaboradores de um evento
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../BancoDados/conexao.php';
session_start();

function garantirEsquemaColaboradores(mysqli $conexao)
{
    // Tabela de colaboradores por evento
    $sqlColab = "CREATE TABLE IF NOT EXISTS colaboradores_evento (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cod_evento INT NOT NULL,
        CPF CHAR(11) NOT NULL,
        papel ENUM('colaborador','coorganizador') NOT NULL DEFAULT 'colaborador',
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_evento_cpf (cod_evento, CPF),
        FOREIGN KEY (cod_evento) REFERENCES evento(cod_evento) ON DELETE CASCADE,
        FOREIGN KEY (CPF) REFERENCES usuario(CPF) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($conexao, $sqlColab);

    // Tabela de solicitações de colaboração (opcional para aprov/recusa)
    $sqlSolic = "CREATE TABLE IF NOT EXISTS solicitacoes_colaboracao (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cod_evento INT NOT NULL,
        cpf_solicitante CHAR(11) NOT NULL,
        status ENUM('pendente','aprovada','recusada') NOT NULL DEFAULT 'pendente',
        mensagem TEXT NULL,
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        data_resolucao TIMESTAMP NULL,
        FOREIGN KEY (cod_evento) REFERENCES evento(cod_evento) ON DELETE CASCADE,
        FOREIGN KEY (cpf_solicitante) REFERENCES usuario(CPF) ON DELETE CASCADE,
        UNIQUE KEY uk_pedido (cod_evento, cpf_solicitante)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($conexao, $sqlSolic);
}

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

    garantirEsquemaColaboradores($conexao);

    // Verifica permissão: organizador do evento ou colaborador/coorganizador do evento
    $temPermissao = false;
    $sqlOrg = "SELECT 1 FROM organiza WHERE cod_evento = ? AND CPF = ? LIMIT 1";
    $stmt = mysqli_prepare($conexao, $sqlOrg);
    mysqli_stmt_bind_param($stmt, 'is', $codEvento, $cpfUsuario);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        $temPermissao = true;
    }
    mysqli_stmt_close($stmt);

    if (!$temPermissao) {
        $sqlColab = "SELECT 1 FROM colaboradores_evento WHERE cod_evento = ? AND CPF = ? LIMIT 1";
        $stmt = mysqli_prepare($conexao, $sqlColab);
        mysqli_stmt_bind_param($stmt, 'is', $codEvento, $cpfUsuario);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $temPermissao = true;
        }
        mysqli_stmt_close($stmt);
    }

    if (!$temPermissao) {
        echo json_encode(['sucesso' => false, 'erro' => 'sem_permissao']);
        exit;
    }

    // Lista colaboradores
    $sql = "SELECT c.CPF, u.Nome AS nome, u.Email AS email, c.papel, c.criado_em
            FROM colaboradores_evento c
            JOIN usuario u ON u.CPF = c.CPF
            WHERE c.cod_evento = ?
            ORDER BY u.Nome";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $codEvento);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $colaboradores = [];
    while ($row = mysqli_fetch_assoc($resultado)) {
        $colaboradores[] = $row;
    }
    mysqli_stmt_close($stmt);

    // Lista solicitações pendentes se for organizador
    $solicitacoes = [];
    if ($temPermissao) {
        $sql = "SELECT s.id, s.cpf_solicitante AS CPF, u.Nome AS nome, u.Email AS email, s.status, s.data_criacao
                FROM solicitacoes_colaboracao s
                JOIN usuario u ON u.CPF = s.cpf_solicitante
                WHERE s.cod_evento = ? AND s.status = 'pendente'
                ORDER BY s.data_criacao DESC";
        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $codEvento);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $solicitacoes[] = $row;
        }
        mysqli_stmt_close($stmt);
    }

    echo json_encode(['sucesso' => true, 'colaboradores' => $colaboradores, 'solicitacoes' => $solicitacoes]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'erro_interno', 'detalhe' => $e->getMessage()]);
}
 
