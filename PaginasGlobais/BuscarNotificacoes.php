<?php
// Define header antes de qualquer saída
header('Content-Type: application/json; charset=utf-8');

// Inicia sessão e valida autenticação
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autenticado']);
    exit;
}

// Inclui conexão com o banco
require_once '../BancoDados/conexao.php';

if (!$conexao) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro de conexão com banco de dados']);
    exit;
}

$cpf = $_SESSION['cpf'];

// Verifica se é requisição do painel (quer todas) ou do dropdown (só não lidas)
$buscarTodas = isset($_GET['todas']) && $_GET['todas'] === 'true';

if ($buscarTodas) {
    // Busca todas as notificações (para o painel)
    $query = "SELECT id, tipo, mensagem, cod_evento, lida, data_criacao 
              FROM notificacoes 
              WHERE CPF = ? 
              ORDER BY data_criacao DESC 
              LIMIT 100";
} else {
    // Busca apenas não lidas (para o dropdown)
    $query = "SELECT id, tipo, mensagem, cod_evento, lida, data_criacao 
              FROM notificacoes 
              WHERE CPF = ? AND lida = 0 
              ORDER BY data_criacao DESC 
              LIMIT 50";
}

$stmt = $conexao->prepare($query);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao preparar query: ' . $conexao->error]);
    exit;
}

$stmt->bind_param('s', $cpf);
$stmt->execute();
$resultado = $stmt->get_result();

$notificacoes = [];
while ($row = $resultado->fetch_assoc()) {
    $notificacoes[] = $row;
}

$stmt->close();
$conexao->close();

echo json_encode([
    'sucesso' => true,
    'total' => count($notificacoes),
    'notificacoes' => $notificacoes
]);
?>

