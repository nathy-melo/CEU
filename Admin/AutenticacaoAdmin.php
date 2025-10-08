<?php
header('Content-Type: application/json');

// Configurações de sessão mais robustas
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Desabilitado para localhost
session_start();

// Incluir configurações centralizadas
require_once 'ConfigAdmin.php';

// Função para verificar se já existe sessão admin ativa
function isAdminLoggedIn() {
    return isset($_SESSION['admin_authenticated']) && 
           $_SESSION['admin_authenticated'] === true &&
           isset($_SESSION['admin_hash_check']) &&
           $_SESSION['admin_hash_check'] === ADMIN_USER_HASH;
}

// Função para criar sessão admin
function createAdminSession() {
    $_SESSION['admin_authenticated'] = true;
    $_SESSION['admin_hash_check'] = ADMIN_USER_HASH;
    $_SESSION['admin_login_time'] = time();
    $_SESSION['admin_ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Regenerar ID da sessão por segurança
    session_regenerate_id(true);
}

// Função para destruir sessão admin
function destroyAdminSession() {
    unset($_SESSION['admin_authenticated']);
    unset($_SESSION['admin_hash_check']);
    unset($_SESSION['admin_login_time']);
    unset($_SESSION['admin_ip']);
    session_destroy();
}

// Verificar se é requisição POST para login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode([
            'success' => false,
            'message' => 'Dados de entrada inválidos.'
        ]);
        exit;
    }
    
    $usuario = trim($input['usuario'] ?? '');
    $senha = trim($input['senha'] ?? '');
    
    // Validações básicas
    if (empty($usuario) || empty($senha)) {
        echo json_encode([
            'success' => false,
            'message' => 'Usuário e senha são obrigatórios.'
        ]);
        exit;
    }
    
    // Gerar hashes das credenciais fornecidas
    $usuarioHash = generateSecureHash($usuario);
    $senhaHash = generateSecureHash($senha);
    
    // Verificar credenciais
    if ($usuarioHash === ADMIN_USER_HASH && $senhaHash === ADMIN_PASS_HASH) {
        // Credenciais corretas, criar sessão
        createAdminSession();
        
        // Log da atividade
        logAdminActivity('LOGIN_SUCCESS', "Usuário: $usuario");
        
        echo json_encode([
            'success' => true,
            'message' => MSG_LOGIN_SUCCESS,
            'redirect' => 'PainelAdmin.html'
        ]);
    } else {
        // Log de tentativa de acesso inválida (para segurança)
        logAdminActivity('LOGIN_FAILED', "Tentativa inválida - Usuário: $usuario - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        
        echo json_encode([
            'success' => false,
            'message' => MSG_LOGIN_INVALID
        ]);
    }
    
} else {
    // Verificar se admin já está logado
    if (isAdminLoggedIn()) {
        echo json_encode([
            'success' => true,
            'message' => 'Administrador já autenticado.',
            'redirect' => 'PainelAdmin.html'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Sessão não autenticada.'
        ]);
    }
}
?>