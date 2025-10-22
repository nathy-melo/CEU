<?php
// Define header antes de qualquer saída
header('Content-Type: application/json; charset=utf-8');

// Inicia sessão e valida autenticação
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'erro' => 'Não autenticado']);
    exit;
}

// Inclui conexão com o banco
require_once '../BancoDados/conexao.php';

if (!$conexao) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'Erro de conexão com banco de dados']);
    exit;
}

$cpf = $_SESSION['cpf'];

// Lê dados do JSON (suporta tanto POST form quanto JSON body)
$dados = json_decode(file_get_contents('php://input'), true);
if (!$dados) {
    // Fallback para POST form
    $dados = $_POST;
}

$id = $dados['id'] ?? null;

// Valida ID
if (!$id || !is_numeric($id)) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'ID inválido']);
    exit;
}

// Atualiza notificação como lida
$query = "UPDATE notificacoes 
          SET lida = 1 
          WHERE id = ? AND CPF = ?";

$stmt = $conexao->prepare($query);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao preparar query: ' . $conexao->error]);
    exit;
}

$stmt->bind_param('is', $id, $cpf);
$sucesso = $stmt->execute();
$linhasAfetadas = $stmt->affected_rows;
$stmt->close();
$conexao->close();

if ($sucesso && $linhasAfetadas > 0) {
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Notificação marcada como lida'
    ]);
} else if ($sucesso && $linhasAfetadas === 0) {
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Notificação já estava lida ou não pertence ao usuário'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'erro' => 'Erro ao atualizar notificação'
    ]);
}
?>


