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

$pastaTemplates = '../Certificacao/templates/';

// Cria pasta se não existir
if (!is_dir($pastaTemplates)) {
    mkdir($pastaTemplates, 0755, true);
}

// Lista todos os arquivos de template
$arquivos = glob($pastaTemplates . '*.{pptx,ppt,odp}', GLOB_BRACE);
$templates = [];

// Adiciona modelos padrão sempre no início
$templates[] = [
    'nome' => 'ModeloExemplo.pptx',
    'nomeExibicao' => 'Modelo Padrão (Participante)',
    'padrao' => true,
    'tipo' => 'participante'
];

$templates[] = [
    'nome' => 'ModeloExemploOrganizador.pptx',
    'nomeExibicao' => 'Modelo Padrão (Organizador)',
    'padrao' => true,
    'tipo' => 'organizador'
];

// Adiciona templates personalizados
foreach ($arquivos as $arquivo) {
    $nomeArquivo = basename($arquivo);
    
    // Ignora os modelos padrão (já foram adicionados)
    if ($nomeArquivo === 'ModeloExemplo.pptx' || $nomeArquivo === 'ModeloExemploOrganizador.pptx') {
        continue;
    }
    
    $templates[] = [
        'nome' => $nomeArquivo,
        'nomeExibicao' => $nomeArquivo,
        'padrao' => false,
        'tipo' => 'personalizado'
    ];
}

echo json_encode([
    'sucesso' => true,
    'templates' => $templates,
    'total' => count($templates)
]);
