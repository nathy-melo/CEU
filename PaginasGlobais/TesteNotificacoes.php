<?php
// Script de teste - cria notificações fictícias no banco
session_start();

require_once '../BancoDados/conexao.php';

// Verifica se está autenticado (para segurança básica)
if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
    // Permite acesso com parâmetro de teste (apenas para desenvolvimento)
    if ($_GET['testando'] !== 'sim') {
        die('Erro: Você precisa estar autenticado. Adicione ?testando=sim para teste.');
    }
    // Para teste, usa um CPF fictício
    $cpf = '12345678901';
} else {
    $cpf = $_SESSION['cpf'];
}

// Define tipo de teste
$tipo = $_GET['tipo'] ?? 'inscricao';
$tipos_validos = ['inscricao', 'desinscricao', 'evento_cancelado', 'evento_prestes_iniciar', 'novo_participante'];

if (!in_array($tipo, $tipos_validos)) {
    die('Tipo inválido. Use: ' . implode(', ', $tipos_validos));
}

// Mensagens para cada tipo
$mensagens = [
    'inscricao' => 'Você se inscreveu com sucesso no evento!',
    'desinscricao' => 'Você foi desinscrito do evento.',
    'evento_cancelado' => 'Um evento foi cancelado.',
    'evento_prestes_iniciar' => 'O evento vai começar em 1 hora!',
    'novo_participante' => 'Uma nova pessoa se inscreveu no seu evento!'
];

$mensagem = $mensagens[$tipo] ?? 'Notificação de teste';

// Insere notificação
$query = "INSERT INTO notificacoes (CPF, tipo, mensagem, cod_evento, lida) 
          VALUES (?, ?, ?, NULL, 0)";

$stmt = $conexao->prepare($query);
$stmt->bind_param('sss', $cpf, $tipo, $mensagem);
$sucesso = $stmt->execute();
$stmt->close();
$conexao->close();

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'sucesso' => $sucesso,
    'mensagem' => $sucesso ? 'Notificação de teste criada!' : 'Erro ao criar notificação',
    'cpf' => $cpf,
    'tipo' => $tipo,
    'nota' => 'Verifique o navegador - a notificação deve aparecer em até 30 segundos'
]);
?>
