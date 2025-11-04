<?php
// Endpoint para abrir/baixar certificado pelo código salvo no banco
use CEU\Certificacao\RepositorioCertificados;

$base = __DIR__;

// Conectar ao banco
$caminhoConexao = realpath($base . '/../BancoDados/conexao.php');
if (!$caminhoConexao || !file_exists($caminhoConexao)) {
    http_response_code(500);
    echo 'Conexão com banco de dados não encontrada.';
    exit;
}
require_once $caminhoConexao; // define $conexao (mysqli)
if (!isset($conexao) || !($conexao instanceof mysqli)) {
    http_response_code(500);
    echo 'Falha ao inicializar conexão com o banco de dados.';
    exit;
}

require_once $base . '/RepositorioCertificados.php';
$repo = new RepositorioCertificados($conexao);
$repo->garantirEsquema();

// Aceita código via GET ou POST; não aceita CPF/evento por segurança
$codigo = '';
if (isset($_POST['codigo'])) {
    $codigo = strtoupper(trim((string)$_POST['codigo']));
} else if (isset($_GET['codigo'])) {
    $codigo = strtoupper(trim((string)$_GET['codigo']));
}

if (!$codigo || !preg_match('/^[A-Z0-9]{6,16}$/', $codigo)) {
    http_response_code(400);
    echo 'Código inválido.';
    exit;
}

$registro = $repo->buscarPorCodigo($codigo);

if (!$registro) {
    http_response_code(404);
    echo 'Certificado não encontrado.';
    exit;
}

// Caminho do arquivo relativo deve apontar para a pasta certificados
$arquivoRel = $registro['arquivo'] ?? '';
if (!$arquivoRel || strpos($arquivoRel, 'certificados/') !== 0) {
    http_response_code(500);
    echo 'Caminho de arquivo inválido.';
    exit;
}

$caminhoArquivo = realpath($base . '/' . $arquivoRel);
if (!$caminhoArquivo || !file_exists($caminhoArquivo)) {
    http_response_code(404);
    echo 'Arquivo do certificado não encontrado.';
    exit;
}

// Entrega inline no navegador
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($caminhoArquivo) . '"');
header('Content-Length: ' . filesize($caminhoArquivo));
readfile($caminhoArquivo);
