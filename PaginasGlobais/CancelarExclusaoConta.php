<?php
/**
 * Cancela uma solicitação de exclusão de conta
 */

// Configuração do tempo de sessão
ini_set('session.gc_maxlifetime', 360);
session_set_cookie_params(360);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Define o cabeçalho para JSON
header('Content-Type: application/json');

// Verifica se o usuário está logado
if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Usuário não autenticado.'
    ]);
    exit;
}

// Verifica se é requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Método não permitido.'
    ]);
    exit;
}

// Inclui o arquivo de conexão
require_once('../BancoDados/conexao.php');

$cpf = $_SESSION['cpf'];

try {
    // Verifica se existe uma solicitação pendente
    $sql_check = "SELECT id, data_exclusao_programada FROM solicitacoes_exclusao_conta WHERE CPF = ? AND status = 'pendente'";
    $stmt_check = mysqli_prepare($conexao, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "s", $cpf);
    mysqli_stmt_execute($stmt_check);
    $resultado = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($resultado) === 0) {
        mysqli_stmt_close($stmt_check);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Não há solicitação de exclusão pendente para esta conta.'
        ]);
        mysqli_close($conexao);
        exit;
    }
    
    $solicitacao = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt_check);
    
    // Atualiza o status para cancelada
    $data_cancelamento = date('Y-m-d H:i:s');
    $sql_cancelar = "UPDATE solicitacoes_exclusao_conta SET status = 'cancelada', data_cancelamento = ? WHERE CPF = ? AND status = 'pendente'";
    $stmt_cancelar = mysqli_prepare($conexao, $sql_cancelar);
    mysqli_stmt_bind_param($stmt_cancelar, "ss", $data_cancelamento, $cpf);
    
    if (mysqli_stmt_execute($stmt_cancelar)) {
        mysqli_stmt_close($stmt_cancelar);
        
        // Log da operação
        error_log("[" . date('Y-m-d H:i:s') . "] Exclusão cancelada - CPF: $cpf");
        
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Solicitação de exclusão cancelada com sucesso! Sua conta não será mais excluída.'
        ]);
    } else {
        throw new Exception('Erro ao cancelar solicitação: ' . mysqli_error($conexao));
    }
    
} catch (Exception $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Erro ao cancelar exclusão - CPF: $cpf - Erro: " . $e->getMessage());
    
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro ao processar cancelamento. Tente novamente.'
    ]);
}

mysqli_close($conexao);
?>
