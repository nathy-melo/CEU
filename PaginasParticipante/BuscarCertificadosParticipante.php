<?php
// API para buscar certificados do participante autenticado
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão inválida']);
    exit;
}

$cpfParticipante = $_SESSION['cpf'];

// Conectar ao banco
$caminhoConexao = realpath(__DIR__ . '/../../BancoDados/conexao.php');
if (!$caminhoConexao || !file_exists($caminhoConexao)) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro de configuração do banco']);
    exit;
}
require_once $caminhoConexao; // define $conexao (mysqli)

if (!isset($conexao) || !($conexao instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Falha na conexão com banco']);
    exit;
}

// Buscar eventos onde o participante está inscrito e o evento permite certificado
$sql = "SELECT 
    e.cod_evento,
    e.nome as nome_evento,
    DATE_FORMAT(e.conclusao, '%d/%m/%Y') as data_conclusao,
    DATE_FORMAT(e.conclusao, '%Y-%m-%d') as data_ordem,
    e.certificado as permite_certificado,
    c.cod_verificacao,
    c.arquivo as caminho_certificado
FROM evento e
INNER JOIN inscricao i ON i.cod_evento = e.cod_evento
LEFT JOIN certificado c ON c.cpf = ? AND c.cod_evento = e.cod_evento
WHERE i.CPF = ? AND i.status = 'ativa' AND e.certificado = 1
ORDER BY e.conclusao DESC";

$stmt = $conexao->prepare($sql);
$stmt->bind_param('ss', $cpfParticipante, $cpfParticipante);
$stmt->execute();
$resultado = $stmt->get_result();

$certificados = [];
while ($row = $resultado->fetch_assoc()) {
    $certificados[] = [
        'cod_evento' => (int)$row['cod_evento'],
        'nome_evento' => $row['nome_evento'],
        'data_conclusao' => $row['data_conclusao'],
        'data_ordem' => $row['data_ordem'],
        'emitido' => !empty($row['cod_verificacao']),
        'codigo_verificacao' => $row['cod_verificacao'] ?? null,
        'caminho_certificado' => $row['caminho_certificado'] ?? null,
    ];
}

$stmt->close();
$conexao->close();

echo json_encode(['sucesso' => true, 'certificados' => $certificados]);
