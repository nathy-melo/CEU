<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Verifica se o usuário está logado
if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
    echo json_encode(['erro' => 'Usuário não autenticado']);
    exit;
}

// Verifica se é organizador
if (!isset($_SESSION['organizador']) || $_SESSION['organizador'] != 1) {
    echo json_encode(['erro' => 'Usuário não é organizador']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

// Verifica se um arquivo foi enviado
if (!isset($_FILES['modelo_certificado']) || $_FILES['modelo_certificado']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['erro' => 'Nenhum arquivo foi enviado ou ocorreu um erro no upload']);
    exit;
}

$arquivo = $_FILES['modelo_certificado'];
$nomeOriginal = $arquivo['name'];
$tmpName = $arquivo['tmp_name'];
$tamanhoArquivo = $arquivo['size'];
$extensao = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

// Validações
$tamanhoMaximo = 50 * 1024 * 1024; // 50MB
$extensoesPermitidas = ['pptx', 'ppt', 'odp'];

// Valida tamanho
if ($tamanhoArquivo > $tamanhoMaximo) {
    $tamanhoMB = round($tamanhoArquivo / 1024 / 1024, 2);
    echo json_encode(['erro' => "O arquivo excede o limite de 50MB. Tamanho: {$tamanhoMB}MB"]);
    exit;
}

// Valida extensão
if (!in_array($extensao, $extensoesPermitidas)) {
    echo json_encode(['erro' => "Formato não permitido. Use: PPTX, PPT ou ODP"]);
    exit;
}

// Remove caracteres especiais do nome do arquivo
$nomeSeguro = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($nomeOriginal, PATHINFO_FILENAME));
$nomeUnico = $nomeSeguro . '_' . uniqid() . '.' . $extensao;

// Define pasta de destino
$pastaDestino = '../Certificacao/templates/';

// Cria pasta se não existir
if (!is_dir($pastaDestino)) {
    mkdir($pastaDestino, 0755, true);
}

$caminhoCompleto = $pastaDestino . $nomeUnico;

// Move arquivo
if (move_uploaded_file($tmpName, $caminhoCompleto)) {
    echo json_encode([
        'sucesso' => true,
        'nomeArquivo' => $nomeUnico,
        'nomeOriginal' => $nomeOriginal,
        'mensagem' => 'Modelo de certificado enviado com sucesso!'
    ]);
} else {
    echo json_encode(['erro' => 'Erro ao salvar arquivo no servidor']);
}
