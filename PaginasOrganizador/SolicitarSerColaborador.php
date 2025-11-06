<?php
// Participante/usuário solicita para colaborar em um evento
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../BancoDados/conexao.php';
session_start();

function garantirEsquemaSolicitacoes(mysqli $conexao)
{
    $sql = "CREATE TABLE IF NOT EXISTS solicitacoes_colaboracao (
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
    mysqli_query($conexao, $sql);
}

function garantirEsquemaColaboradores(mysqli $conexao)
{
    // Tabela de colaboradores por evento (usada em verificações abaixo)
    $sqlColab = "CREATE TABLE IF NOT EXISTS colaboradores_evento (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cod_evento INT NOT NULL,
        CPF CHAR(11) NOT NULL,
        papel VARCHAR(20) NOT NULL DEFAULT 'colaborador',
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_evento_cpf (cod_evento, CPF),
        FOREIGN KEY (cod_evento) REFERENCES evento(cod_evento) ON DELETE CASCADE,
        FOREIGN KEY (CPF) REFERENCES usuario(CPF) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($conexao, $sqlColab);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['sucesso' => false, 'erro' => 'metodo_invalido']);
        exit;
    }
    if (!isset($_SESSION['cpf'])) {
        echo json_encode(['sucesso' => false, 'erro' => 'nao_autenticado']);
        exit;
    }

    $cpf = $_SESSION['cpf'];
    $codEvento = isset($_POST['cod_evento']) ? (int) $_POST['cod_evento'] : 0;
    $mensagem = trim($_POST['mensagem'] ?? '');
    if ($codEvento <= 0) {
        echo json_encode(['sucesso' => false, 'erro' => 'cod_evento_invalido']);
        exit;
    }

    garantirEsquemaSolicitacoes($conexao);
    garantirEsquemaColaboradores($conexao);

    // Não permitir solicitar se já é organizador ou colaborador
    $sqlOrg = "SELECT 1 FROM organiza WHERE cod_evento = ? AND CPF = ?";
    $stmt = mysqli_prepare($conexao, $sqlOrg);
    if (!$stmt) {
        echo json_encode(['sucesso' => false, 'erro' => 'erro_preparar_consulta', 'detalhe' => mysqli_error($conexao), 'sql' => $sqlOrg]);
        exit;
    }
    mysqli_stmt_bind_param($stmt, 'is', $codEvento, $cpf);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo json_encode(['sucesso' => false, 'erro' => 'ja_organizador']);
        exit;
    }
    mysqli_stmt_close($stmt);

    $sqlColab = "SELECT 1 FROM colaboradores_evento WHERE cod_evento = ? AND CPF = ?";
    $stmt = mysqli_prepare($conexao, $sqlColab);
    if (!$stmt) {
        echo json_encode(['sucesso' => false, 'erro' => 'erro_preparar_consulta', 'detalhe' => mysqli_error($conexao), 'sql' => $sqlColab]);
        exit;
    }
    mysqli_stmt_bind_param($stmt, 'is', $codEvento, $cpf);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo json_encode(['sucesso' => false, 'erro' => 'ja_colaborador']);
        exit;
    }
    mysqli_stmt_close($stmt);

    // Insere ou atualiza para pendente
    $sql = "INSERT INTO solicitacoes_colaboracao (cod_evento, cpf_solicitante, status, mensagem)
            VALUES (?,?, 'pendente', ?)
            ON DUPLICATE KEY UPDATE status = 'pendente', mensagem = VALUES(mensagem), data_criacao = CURRENT_TIMESTAMP, data_resolucao = NULL";
    $stmt = mysqli_prepare($conexao, $sql);
    if (!$stmt) {
        echo json_encode(['sucesso' => false, 'erro' => 'erro_preparar_consulta', 'detalhe' => mysqli_error($conexao), 'sql' => $sql]);
        exit;
    }
    mysqli_stmt_bind_param($stmt, 'iss', $codEvento, $cpf, $mensagem);
    if (!mysqli_stmt_execute($stmt)) {
        echo json_encode(['sucesso' => false, 'erro' => 'falha_solicitacao']);
        exit;
    }
    mysqli_stmt_close($stmt);

    // Notifica organizadores do evento
    $nomeUsuario = '';
    if ($r = mysqli_query($conexao, "SELECT Nome FROM usuario WHERE CPF = '" . mysqli_real_escape_string($conexao, $cpf) . "' LIMIT 1")) {
        if ($u = mysqli_fetch_assoc($r)) { $nomeUsuario = $u['Nome'] ?? ''; }
        mysqli_free_result($r);
    }
    $nomeEvento = '';
    if ($r = mysqli_query($conexao, "SELECT nome FROM evento WHERE cod_evento = " . (int)$codEvento . " LIMIT 1")) {
        if ($e = mysqli_fetch_assoc($r)) { $nomeEvento = $e['nome'] ?? ''; }
        mysqli_free_result($r);
    }
    $msg = ($nomeUsuario ?: 'Um usuário') . " solicitou ser colaborador no evento '" . ($nomeEvento ?: (string)$codEvento) . "'.";
    $qryOrgs = mysqli_query($conexao, "SELECT CPF FROM organiza WHERE cod_evento = " . (int)$codEvento);
    if ($qryOrgs) {
        while ($org = mysqli_fetch_assoc($qryOrgs)) {
            $cpfOrg = $org['CPF'];
            $stmtN = mysqli_prepare($conexao, "INSERT INTO notificacoes (CPF, tipo, mensagem, cod_evento, lida) VALUES (?,?,?,?,0)");
            $tipo = 'solicitacao_colaborador';
            mysqli_stmt_bind_param($stmtN, 'sssi', $cpfOrg, $tipo, $msg, $codEvento);
            @mysqli_stmt_execute($stmtN);
            @mysqli_stmt_close($stmtN);
        }
        mysqli_free_result($qryOrgs);
    }

    echo json_encode(['sucesso' => true, 'mensagem' => 'Solicitação enviada com sucesso! Aguarde aprovação do organizador.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'erro_interno', 'detalhe' => $e->getMessage()]);
}
