<?php
// Adiciona colaborador a um evento por CPF ou Email
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../BancoDados/conexao.php';
session_start();

function garantirEsquemaColaboradores(mysqli $conexao)
{
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

    $cpfUsuario = $_SESSION['cpf'];
    $codEvento = isset($_POST['cod_evento']) ? (int) $_POST['cod_evento'] : 0;
    $identificador = trim($_POST['identificador'] ?? ''); // CPF (11 dígitos) ou Email
    $papel = $_POST['papel'] ?? 'colaborador';
    if ($codEvento <= 0 || $identificador === '') {
        echo json_encode(['sucesso' => false, 'erro' => 'parametros_invalidos']);
        exit;
    }

    garantirEsquemaColaboradores($conexao);

    // Apenas organizador do evento pode adicionar
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

    // Resolve identificador -> CPF de usuário existente
    if (preg_match('/^\d{11}$/', $identificador)) {
        $cpfNovo = $identificador;
    } else {
        // busca por email
        $sql = "SELECT CPF FROM usuario WHERE Email = ? LIMIT 1";
        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, 's', $identificador);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
        if (!$row) {
            echo json_encode(['sucesso' => false, 'erro' => 'usuario_nao_encontrado']);
            exit;
        }
        $cpfNovo = $row['CPF'];
    }

    // Não permitir adicionar o próprio organizador como colaborador duplicado
    $stmt = mysqli_prepare($conexao, "SELECT 1 FROM organiza WHERE cod_evento = ? AND CPF = ?");
    mysqli_stmt_bind_param($stmt, 'is', $codEvento, $cpfNovo);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo json_encode(['sucesso' => false, 'erro' => 'ja_organizador']);
        exit;
    }
    mysqli_stmt_close($stmt);

    // Inserir colaborador
    $sql = "INSERT INTO colaboradores_evento (cod_evento, CPF, papel) VALUES (?,?,?)
            ON DUPLICATE KEY UPDATE papel = VALUES(papel)";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'iss', $codEvento, $cpfNovo, $papel);
    if (!mysqli_stmt_execute($stmt)) {
        echo json_encode(['sucesso' => false, 'erro' => 'falha_inserir', 'detalhe' => mysqli_error($conexao)]);
        exit;
    }
    mysqli_stmt_close($stmt);

    // Notificar usuário adicionado
    $nomeEvento = '';
    if ($r = mysqli_query($conexao, "SELECT nome FROM evento WHERE cod_evento = " . (int)$codEvento . " LIMIT 1")) {
        if ($e = mysqli_fetch_assoc($r)) { $nomeEvento = $e['nome'] ?? ''; }
        mysqli_free_result($r);
    }
    $msg = "Você foi adicionado como colaborador no evento '" . ($nomeEvento ?: (string)$codEvento) . "'.";
    $stmtN = mysqli_prepare($conexao, "INSERT INTO notificacoes (CPF, tipo, mensagem, cod_evento, lida) VALUES (?,?,?,?,0)");
    $tipo = 'colaborador_adicionado';
    mysqli_stmt_bind_param($stmtN, 'sssi', $cpfNovo, $tipo, $msg, $codEvento);
    @mysqli_stmt_execute($stmtN);
    @mysqli_stmt_close($stmtN);

    echo json_encode(['sucesso' => true, 'mensagem' => 'Colaborador adicionado com sucesso']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'erro_interno', 'detalhe' => $e->getMessage()]);
}
