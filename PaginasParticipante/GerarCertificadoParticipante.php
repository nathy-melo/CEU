<?php
// Endpoint para gerar certificado do participante autenticado para um evento
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão inválida']);
    exit;
}

$cpfParticipante = $_SESSION['cpf'];
$codEvento = isset($_GET['cod_evento']) ? (int)$_GET['cod_evento'] : 0;

if ($codEvento <= 0) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Parâmetro cod_evento inválido']);
    exit;
}

try {
    // Redireciona internamente para o gerador universal com CPF e cod_evento
    // Usa output buffering para capturar a resposta
    $_GET['cpf'] = $cpfParticipante;
    $_GET['cod_evento'] = $codEvento;
    $_GET['arquivo'] = 'templates/ModeloExemplo.pptx'; // ou lógica para escolher o modelo

    ob_start();
    require __DIR__ . '/../Certificacao/visualizar_certificado_universal.php';
    $output = ob_get_clean();

    // Se chegou aqui sem exceção, certificado foi gerado
    // Busca o código recém-criado para retornar
    $caminhoConexao = realpath(__DIR__ . '/../BancoDados/conexao.php');
    require_once $caminhoConexao;
    require_once __DIR__ . '/../Certificacao/RepositorioCertificados.php';
    
    $repo = new \CEU\Certificacao\RepositorioCertificados($conexao);
    $cert = $repo->buscarPorCpfEvento($cpfParticipante, $codEvento);
    
    if ($cert) {
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Certificado gerado com sucesso',
            'codigo' => $cert['cod_verificacao']
        ]);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao gerar certificado']);
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
}
