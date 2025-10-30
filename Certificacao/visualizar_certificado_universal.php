<?php

/**
 * Visualização/Geração universal (DOCX/PPTX/ODT/ODP/FODT/FODP -> PDF via LibreOffice)
 * - Usa preenchimento por ZIP+XML quando aplicável
 * - Converte para PDF via LibreOffice (soffice). Se falhar, informa diagnóstico
 */

use CEU\Certificacao\ProcessadorTemplate;

$base = __DIR__;
$autoload = $base . '/bibliotecas/vendor/autoload.php';
$templateParam = isset($_GET['arquivo']) ? (string)$_GET['arquivo'] : '';

// Resolve template: se for caminho relativo, assumir base/ templates/
if ($templateParam) {
    $template = $templateParam;
    if (!preg_match('#^([a-zA-Z]:\\\\|/|\\\\)#', $template)) { // não é caminho absoluto
        // Normaliza dentro da pasta Certificacao
        $maybe = $base . '/' . ltrim($template, '/\\');
        if (file_exists($maybe)) {
            $template = $maybe;
        } else {
            $template = $base . '/templates/' . ltrim($template, '/\\');
        }
    }
} else {
    // Candidatos padrão
    $cands = [
        $base . '/templates/certificado_padrao.pptx',
        $base . '/templates/ModeloExemplo.pptx',
        $base . '/templates/certificado_padrao.docx',
        $base . '/templates/certificado_padrao.odt',
        $base . '/ModeloExemplo.pptx',
    ];
    $template = null;
    foreach ($cands as $c) {
        if (file_exists($c)) {
            $template = $c;
            break;
        }
    }
}

$saidaDir = $base . '/certificados';
if (!is_dir($saidaDir)) {
    @mkdir($saidaDir, 0775, true);
}

$dados = [
    'NomeParticipante' => $_GET['nome'] ?? 'Elisa de Souza Lima',
    'NomeEvento' => $_GET['evento'] ?? 'Palestra de História',
    'NomeOrganizador' => $_GET['organizador'] ?? 'Aurora do Nascimento',
    'LocalEvento' => $_GET['local'] ?? 'IFMG - Campus Sabará',
    'Data' => $_GET['data'] ?? date('d/m/Y'),
    'CargaHoraria' => $_GET['carga'] ?? '2 horas',
    'CodigoAutenticador' => $_GET['codigo'] ?? 'J8KLMA736F',
];

$erro = null;
$resp = null;
$pdfFile = $saidaDir . '/certificado_universal_' . date('Ymd_His') . '.pdf';
try {
    if (!file_exists($autoload)) {
        throw new \Exception('Dependências não encontradas. Abra Certificacao/index.php e conclua a instalação.');
    }
    if (!$template || !file_exists($template)) {
        throw new \Exception('Template não encontrado. Informe ?arquivo=templates/meu_modelo.(pptx|docx|odt|odp)');
    }
    require_once $base . '/ProcessadorTemplate.php';
    $proc = new ProcessadorTemplate($autoload);
    $resp = $proc->gerarPdfDeModelo($template, $dados, $pdfFile, sys_get_temp_dir());
    if (empty($resp['success'])) {
        throw new \Exception($resp['message'] ?? 'Falha ao gerar PDF.');
    }
} catch (\Throwable $e) {
    $erro = $e->getMessage();
}

?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Certificado Universal</title>
    <style>
        body {
            margin: 0;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
            background: #eef5ff;
            color: #1a1a1a;
        }

        header {
            padding: 16px 20px;
            background: #334b68;
            color: #fff;
        }

        .wrap {
            padding: 16px;
        }

        .alert {
            padding: 12px 14px;
            border-radius: 8px;
            margin: 12px 0;
        }

        .alert.info {
            background: #e6f0ff;
            color: #16365f;
        }

        .alert.error {
            background: #ffebeb;
            color: #7a1f1f;
        }

        .actions {
            margin: 12px 0 20px;
        }

        .btn {
            background: #6598d2;
            color: #fff;
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
        }

        iframe {
            width: 100%;
            height: 75vh;
            border: 0;
            background: #fff;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .1);
            border-radius: 8px;
        }

        code {
            background: #0001;
            padding: 2px 6px;
            border-radius: 6px;
        }
    </style>
</head>

<body>
    <header>
        <strong>CEU · Certificação (Universal)</strong>
    </header>
    <div class="wrap">
        <?php if ($erro): ?>
            <div class="alert error">Erro: <?= htmlspecialchars($erro) ?></div>
            <?php if ($resp): ?>
                <pre><?= htmlspecialchars(json_encode($resp, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></pre>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert info">PDF gerado a partir de <code><?= htmlspecialchars(basename($template)) ?></code></div>
            <div class="actions">
                <a class="btn" href="<?= 'certificados/' . rawurlencode(basename($pdfFile)) ?>" download>Baixar PDF</a>
                <a class="btn" href="?arquivo=<?= urlencode(isset($_GET['arquivo']) ? $_GET['arquivo'] : basename($template)) ?>&viewer=pdfjs&<?= http_build_query(array_diff_key($_GET, ['arquivo' => 1, 'viewer' => 1])) ?>">Visualizar sem barras</a>
            </div>
            <?php $pdfUrl = 'certificados/' . rawurlencode(basename($pdfFile)); ?>
            <?php if ((isset($_GET['viewer']) && $_GET['viewer'] === 'pdfjs')): ?>
                <iframe src="pdf_viewer.php?file=<?= rawurlencode(basename($pdfFile)) ?>" allowfullscreen></iframe>
            <?php else: ?>
                <!-- Tenta esconder barras do visualizador nativo (nem todos os navegadores respeitam) -->
                <iframe src="<?= $pdfUrl ?>#toolbar=0&navpanes=0&scrollbar=0&view=FitH" allowfullscreen></iframe>
            <?php endif; ?>
        <?php endif; ?>
        <div style="margin-top:12px; font-size:.9rem; opacity:.8;">
            Dica: informe um template com ?arquivo=templates/meu_modelo.pptx e parâmetros como ?nome=...&evento=...&data=... · Para ocultar barras do navegador, use o botão "Visualizar sem barras" (usa PDF.js simples).
        </div>
    </div>
</body>

</html>