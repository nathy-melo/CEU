<?php
// Buscar Certificados do Usuário
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Verifica autenticação
if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
    echo json_encode(['sucesso' => false, 'erro' => 'nao_autenticado']);
    exit;
}

try {
    require_once '../BancoDados/conexao.php';
    require_once '../Certificacao/RepositorioCertificados.php';

    $cpfUsuario = $_SESSION['cpf'];
    
    // Buscar certificados emitidos para este usuário
    $sql = "SELECT 
                c.cod_verificacao,
                c.modelo,
                c.tipo,
                c.arquivo,
                c.dados,
                c.criado_em,
                c.cod_evento,
                e.nome as nome_evento,
                e.inicio as data_evento
            FROM certificado c
            LEFT JOIN evento e ON c.cod_evento = e.cod_evento
            WHERE c.cpf = ?
            ORDER BY c.criado_em DESC";
    
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 's', $cpfUsuario);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $certificados = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Decodificar dados JSON se existir
        if (!empty($row['dados'])) {
            $row['dados'] = json_decode($row['dados'], true);
        }
        $certificados[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexao);
    
    echo json_encode([
        'sucesso' => true,
        'certificados' => $certificados,
        'total' => count($certificados)
    ]);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'erro' => 'erro_interno',
        'detalhe' => $e->getMessage()
    ]);
}
?>
