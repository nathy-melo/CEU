<?php
// Arquivo de proteção para pasta Admin
// Redireciona tentativas de acesso direto para o login

session_start();

// Verificar se é uma tentativa de acesso direto à pasta
if (!isset($_GET['page']) && !isset($_POST['action'])) {
    // Redirecionar para o login administrativo
    header('Location: LoginAdmin.html');
    exit();
}

// Se chegou aqui, algo está errado - redirecionar para segurança
header('Location: LoginAdmin.html');
exit();
?>