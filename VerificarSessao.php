<?php
// Arquivo de teste para verificação de sessão
header('Content-Type: application/json');

// Simula uma sessão sempre ativa para teste
echo json_encode(['ativa' => true, 'teste' => true]);
?>