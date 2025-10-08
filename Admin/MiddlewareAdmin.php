<?php
// Middleware de segurança para área administrativa
// Incluir este arquivo no início de todas as páginas admin

session_start();

// Incluir configurações centralizadas
require_once 'ConfigAdmin.php';

// Função para verificar autenticação admin
function verificarAutenticacaoAdmin() {
    // Verificar se sessão admin existe
    if (!isset($_SESSION['admin_authenticated']) || 
        $_SESSION['admin_authenticated'] !== true ||
        !isset($_SESSION['admin_hash_check']) ||
        $_SESSION['admin_hash_check'] !== ADMIN_USER_HASH) {
        
        redirecionarParaLogin(MSG_ACCESS_DENIED);
        return false;
    }
    
    // Verificar timeout da sessão
    if (isset($_SESSION['admin_login_time']) && 
        (time() - $_SESSION['admin_login_time']) > ADMIN_SESSION_TIMEOUT) {
        
        destruirSessaoAdmin();
        redirecionarParaLogin(MSG_SESSION_EXPIRED);
        return false;
    }
    
    // Verificar IP (se habilitado)
    if (ENABLE_IP_CHECK && 
        isset($_SESSION['admin_ip']) && 
        $_SESSION['admin_ip'] !== ($_SERVER['REMOTE_ADDR'] ?? 'unknown')) {
        
        destruirSessaoAdmin();
        redirecionarParaLogin(MSG_IP_CHANGED);
        return false;
    }
    
    // Renovar timestamp da sessão
    $_SESSION['admin_login_time'] = time();
    
    return true;
}

// Função para destruir sessão admin
function destruirSessaoAdmin() {
    unset($_SESSION['admin_authenticated']);
    unset($_SESSION['admin_hash_check']);
    unset($_SESSION['admin_login_time']);
    unset($_SESSION['admin_ip']);
}

// Função para redirecionar para login
function redirecionarParaLogin($mensagem = '') {
    if (!empty($mensagem)) {
        $_SESSION['admin_error'] = $mensagem;
    }
    
    // Verificar se é requisição AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $mensagem,
            'redirect' => 'LoginAdmin.html'
        ]);
        exit;
    } else {
        // Redirecionamento normal
        header('Location: LoginAdmin.html');
        exit;
    }
}

// Função para logout admin
function logoutAdmin() {
    destruirSessaoAdmin();
    session_destroy();
    header('Location: LoginAdmin.html');
    exit;
}

// Verificar se é requisição de logout
if (isset($_GET['logout']) && $_GET['logout'] === 'admin') {
    logoutAdmin();
}

// Executar verificação automática
if (!verificarAutenticacaoAdmin()) {
    // Se chegou aqui, a verificação falhou e já foi redirecionado
    exit;
}

// Se chegou até aqui, o admin está autenticado
// Definir variáveis úteis para as páginas admin
$admin_session_time = $_SESSION['admin_login_time'] ?? time();
$admin_ip = $_SESSION['admin_ip'] ?? 'unknown';
$time_remaining = ADMIN_SESSION_TIMEOUT - (time() - $admin_session_time);

// Log de acesso admin (para auditoria)
logAdminActivity('PAGE_ACCESS', 'Página: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
?>