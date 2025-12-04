<?php
session_start();

require_once 'ConfigAdmin.php';

function verificarAutenticacaoAdmin()
{
    if (
        !isset($_SESSION['admin_authenticated']) ||
        $_SESSION['admin_authenticated'] !== true ||
        !isset($_SESSION['admin_hash_check']) ||
        $_SESSION['admin_hash_check'] !== ADMIN_USER_HASH
    ) {
        redirecionarParaLogin(MSG_ACCESS_DENIED);
        return false;
    }

    if (
        isset($_SESSION['admin_login_time']) &&
        (time() - $_SESSION['admin_login_time']) > ADMIN_SESSION_TIMEOUT
    ) {
        destruirSessaoAdmin();
        redirecionarParaLogin(MSG_SESSION_EXPIRED);
        return false;
    }

    if (
        ENABLE_IP_CHECK &&
        isset($_SESSION['admin_ip']) &&
        $_SESSION['admin_ip'] !== ($_SERVER['REMOTE_ADDR'] ?? 'unknown')
    ) {
        destruirSessaoAdmin();
        redirecionarParaLogin(MSG_IP_CHANGED);
        return false;
    }

    $_SESSION['admin_login_time'] = time();
    return true;
}

function destruirSessaoAdmin()
{
    unset($_SESSION['admin_authenticated']);
    unset($_SESSION['admin_hash_check']);
    unset($_SESSION['admin_login_time']);
    unset($_SESSION['admin_ip']);
}

function redirecionarParaLogin($mensagem = '')
{
    if (!empty($mensagem)) {
        $_SESSION['admin_error'] = $mensagem;
    }

    if (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
    ) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $mensagem,
            'redirect' => 'LoginAdmin.html'
        ]);
        exit;
    } else {
        header('Location: LoginAdmin.html');
        exit;
    }
}

function logoutAdmin()
{
    destruirSessaoAdmin();
    session_destroy();
    header('Location: LoginAdmin.html');
    exit;
}

if (isset($_GET['logout']) && $_GET['logout'] === 'admin') {
    logoutAdmin();
}

if (!verificarAutenticacaoAdmin()) {
    exit;
}

$admin_session_time = $_SESSION['admin_login_time'] ?? time();
$admin_ip = $_SESSION['admin_ip'] ?? 'unknown';
$time_remaining = ADMIN_SESSION_TIMEOUT - (time() - $admin_session_time);

logAdminActivity('PAGE_ACCESS', 'Página: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
