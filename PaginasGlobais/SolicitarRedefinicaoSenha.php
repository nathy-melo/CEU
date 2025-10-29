<?php
/**
 * Processa solicitações de redefinição de senha
 * Registra a solicitação no banco de dados para o admin gerenciar
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir conexão com banco de dados
require_once '../BancoDados/conexao.php';

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'message' => 'Método não permitido. Use POST.'
    ]);
    exit;
}

// Obter dados do POST
$input = json_decode(file_get_contents('php://input'), true);

// Validar email
$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);

if (!$email) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Email inválido.'
    ]);
    exit;
}

try {
    // Verificar se o email existe no banco de dados
    $stmt = $conexao->prepare("SELECT CPF, Nome FROM usuario WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $cpf = null;
    $nome = null;
    
    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();
        $cpf = $usuario['CPF'];
        $nome = $usuario['Nome'];
    }
    
    $stmt->close();
    
    // Verificar se já existe uma solicitação pendente para este email nos últimos 30 minutos
    $stmt = $conexao->prepare("
        SELECT id FROM solicitacoes_redefinicao_senha 
        WHERE email = ? 
        AND status = 'pendente' 
        AND data_solicitacao > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        $stmt->close();
        echo json_encode([
            'success' => true,
            'message' => 'Já existe uma solicitação pendente para este email. Por favor, aguarde.',
            'email_exists' => ($cpf !== null)
        ]);
        exit;
    }
    
    $stmt->close();
    
    // Inserir nova solicitação
    $stmt = $conexao->prepare("
        INSERT INTO solicitacoes_redefinicao_senha 
        (email, CPF, nome_usuario, status) 
        VALUES (?, ?, ?, 'pendente')
    ");
    $stmt->bind_param("sss", $email, $cpf, $nome);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Log da operação
        error_log("[" . date('Y-m-d H:i:s') . "] Nova solicitação de redefinição de senha: $email" . ($cpf ? " (CPF: $cpf)" : " (usuário não encontrado)"));
        
        echo json_encode([
            'success' => true,
            'message' => 'Solicitação registrada com sucesso. Um administrador irá processar sua solicitação em breve.',
            'email_exists' => ($cpf !== null)
        ]);
    } else {
        throw new Exception('Erro ao registrar solicitação: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Erro ao processar solicitação de redefinição de senha: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao processar solicitação. Tente novamente mais tarde.'
    ]);
}

$conexao->close();
?>
