<?php
// Configuração do tempo de sessão para 60 segundos
ini_set('session.gc_maxlifetime', 60);
session_set_cookie_params(60);

session_start();

header('Content-Type: application/json');

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
    // Verifica se a sessão expirou no servidor (backup de segurança)
    if (time() - $_SESSION['ultima_atividade'] > 60) {
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