<?php
// Inicia a sessão
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}
session_start();

// Define o cabeçalho para JSON
header('Content-Type: application/json');

// Verifica se o usuário está logado
if (!isset($_SESSION['cpf']) || !isset($_SESSION['organizador']) || $_SESSION['organizador'] != 1) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Usuário não autenticado ou não é organizador.'
    ]);
    exit;
}

// Inclui o arquivo de conexão
require_once('../BancoDados/conexao.php');

$cpf = $_SESSION['cpf'];

// Inicia uma transação
mysqli_begin_transaction($conexao);

try {
    // Exclui registros relacionados na tabela organiza
    $sql_organiza = "DELETE FROM organiza WHERE CPF = ?";
    $stmt_organiza = mysqli_prepare($conexao, $sql_organiza);
    mysqli_stmt_bind_param($stmt_organiza, "s", $cpf);
    mysqli_stmt_execute($stmt_organiza);
    mysqli_stmt_close($stmt_organiza);

    // Exclui o usuário da tabela usuario
    $sql_usuario = "DELETE FROM usuario WHERE CPF = ?";
    $stmt_usuario = mysqli_prepare($conexao, $sql_usuario);
    mysqli_stmt_bind_param($stmt_usuario, "s", $cpf);
    mysqli_stmt_execute($stmt_usuario);
    
    if (mysqli_stmt_affected_rows($stmt_usuario) > 0) {
        // Confirma a transação
        mysqli_commit($conexao);
        mysqli_stmt_close($stmt_usuario);
        
        // Limpa a sessão
        $_SESSION = [];
        session_destroy();
        
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Conta excluída com sucesso.'
        ]);
    } else {
        // Reverte a transação
        mysqli_rollback($conexao);
        mysqli_stmt_close($stmt_usuario);
        
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Erro ao excluir a conta. Nenhuma conta foi encontrada.'
        ]);
    }
} catch (Exception $e) {
    // Reverte a transação em caso de erro
    mysqli_rollback($conexao);
    
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro ao excluir conta: ' . $e->getMessage()
    ]);
}

// Fecha a conexão
mysqli_close($conexao);
?>
