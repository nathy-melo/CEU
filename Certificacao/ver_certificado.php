<?php
// Endpoint para abrir/baixar certificado pelo código salvo no banco
use CEU\Certificacao\RepositorioCertificados;
use CEU\Certificacao\ProcessadorTemplate;

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
require_once $base . '/ProcessadorTemplate.php';

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

// Se arquivo não existe, tenta regenerar do banco de dados
if (!$caminhoArquivo || !file_exists($caminhoArquivo)) {
    // Verifica se temos dados no banco para regenerar
    if (empty($registro['dados_array']) && empty($registro['dados'])) {
        http_response_code(404);
        echo 'Arquivo do certificado não encontrado e dados insuficientes para regeneração.';
        exit;
    }

    try {
        // Extrai dados JSON
        $dados = $registro['dados_array'] ?? json_decode($registro['dados'], true) ?? [];
        if (empty($dados)) {
            throw new Exception('Dados do certificado vazios.');
        }

        // Localiza template
        $modelo = $registro['modelo'] ?? 'certificado.docx';
        $templatePath = $base . '/templates/' . $modelo;
        if (!file_exists($templatePath)) {
            throw new Exception('Template não encontrado: ' . $modelo);
        }

        // Garante diretório certificados
        $dirCertificados = $base . '/certificados';
        if (!is_dir($dirCertificados)) {
            @mkdir($dirCertificados, 0775, true);
        }

        // Regenera PDF
        $nomeArquivo = basename($arquivoRel);
        $caminhoArquivo = $dirCertificados . DIRECTORY_SEPARATOR . $nomeArquivo;
        
        $autoloadPath = $base . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        if (!file_exists($autoloadPath)) {
            throw new Exception('Autoload das bibliotecas não encontrado.');
        }

        $processador = new ProcessadorTemplate($autoloadPath);
        $resultado = $processador->gerarPdfDeModelo($templatePath, $dados, $caminhoArquivo);

        if (!$resultado['success']) {
            throw new Exception('Falha ao gerar PDF: ' . ($resultado['message'] ?? 'Erro desconhecido'));
        }

        // Atualiza banco com novo caminho
        $repo->salvarCertificado(
            $codigo,
            'certificados/' . $nomeArquivo,
            $registro['modelo'] ?? null,
            $registro['tipo'] ?? null,
            $dados,
            null,
            null
        );

    } catch (Exception $e) {
        http_response_code(500);
        echo 'Erro ao regenerar certificado: ' . htmlspecialchars($e->getMessage());
        exit;
    }
}

// Entrega inline no navegador
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($caminhoArquivo) . '"');
header('Content-Length: ' . filesize($caminhoArquivo));
readfile($caminhoArquivo);
