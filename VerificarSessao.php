<?php
// Configuração do tempo de sessão para 6 minutos (5min de inatividade + 1min de extensão)
ini_set('session.gc_maxlifetime', 360);
session_set_cookie_params(360);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
// Garante que o navegador e proxies não cacheiem esta resposta
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Verifica se foi solicitado forçar expiração
if (isset($_GET['forcar_expiracao']) && $_GET['forcar_expiracao'] == '1') {
    session_unset();
    session_destroy();
    echo json_encode(['ativa' => false, 'forcada' => true]);
    exit;
}

// Verifica se a sessão está ativa
$sessaoAtiva = isset($_SESSION['cpf']) && !empty($_SESSION['cpf']);

if ($sessaoAtiva && isset($_SESSION['ultima_atividade'])) {
    // Verifica se a sessão expirou no servidor (permite 5 minutos de inatividade)
    if (time() - $_SESSION['ultima_atividade'] > 300) {
        $sessaoAtiva = false;
        session_unset();
        session_destroy();
    } else {
        // Atualiza o timestamp da última atividade
        $_SESSION['ultima_atividade'] = time();
    }
}

echo json_encode(['ativa' => $sessaoAtiva]);
?>