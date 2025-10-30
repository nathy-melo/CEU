<?php
/**
 * Visualização/Geração do Certificado a partir de PPTX (Caminho B)
 * - Preenche placeholders no PPTX (ModeloExemplo.pptx ou templates/certificado_padrao.pptx)
 * - Converte para PDF usando LibreOffice (soffice) se disponível
 * - Exibe o PDF final com botão para download
 */

use CEU\Certificacao\ProcessadorTemplate;

$base = __DIR__;
$libsAutoload = $base . '/bibliotecas/vendor/autoload.php';
$candidatos = [
  $base . '/templates/certificado_padrao.pptx',
  $base . '/templates/ModeloExemplo.pptx',
  $base . '/ModeloExemplo.pptx',
];
$template = null;
foreach ($candidatos as $c) { if (file_exists($c)) { $template = $c; break; } }

$saidaDir = $base . '/certificados';
if (!is_dir($saidaDir)) { @mkdir($saidaDir, 0775, true); }

$dados = [
  'NomeParticipante' => $_GET['nome'] ?? 'Elisa de Souza Lima',
  'NomeEvento' => $_GET['evento'] ?? 'Palestra de História',
  'NomeOrganizador' => $_GET['organizador'] ?? 'Aurora do Nascimento',
  'LocalEvento' => $_GET['local'] ?? 'IFMG - Campus Sabará',
  'Data' => $_GET['data'] ?? date('d/m/Y'),
  'CargaHoraria' => $_GET['carga'] ?? '2 horas',
  'CodigoAutenticador' => $_GET['codigo'] ?? 'J8KLMA736F',
];

$erro = null; $info = [];
$pdfFile = $saidaDir . '/certificado_pptx_' . date('Ymd_His') . '.pdf';
$pptxPreenchido = $saidaDir . '/certificado_pptx_' . date('Ymd_His') . '.pptx';

try {
  if (!file_exists($libsAutoload)) {
    throw new Exception('Dependências não encontradas. Abra Certificacao/index.php e conclua a instalação.');
  }
  require_once $base . '/ProcessadorTemplate.php';
  $proc = new ProcessadorTemplate($libsAutoload);
  if (!$template || !file_exists($template)) {
    $msg = "Template PPTX não encontrado. Tente um dos caminhos: \n- " . implode("\n- ", $candidatos);
    throw new \Exception($msg);
  }
  // Preencher PPTX
  $proc->preencherPptx($template, $dados, $pptxPreenchido);
  // Converter para PDF
  $conv = $proc->converterPptxParaPdf($pptxPreenchido, $pdfFile);
  if (!$conv['success']) {
    $info['conversao'] = $conv;
    throw new Exception('Falha na conversão PPTX -> PDF. Verifique se o LibreOffice está instalado e o SOFFICE_PATH configurado.');
  } else {
    $info['conversao'] = $conv;
  }
} catch (Throwable $e) {
  $erro = $e->getMessage();
}

?><!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Certificado via PPTX</title>
  <style>
    body { margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; background:#eef5ff; color:#1a1a1a; }
    header { padding:16px 20px; background:#334b68; color:#fff; }
    .wrap { padding:16px; }
    .alert { padding:12px 14px; border-radius:8px; margin:12px 0; }
    .alert.info { background:#e6f0ff; color:#16365f; }
    .alert.error { background:#ffebeb; color:#7a1f1f; }
    .actions { margin:12px 0 20px; }
    .btn { background:#6598d2; color:#fff; padding:10px 16px; border:none; border-radius:8px; text-decoration:none; display:inline-block; }
    iframe { width:100%; height:75vh; border:0; background:#fff; box-shadow:0 10px 25px rgba(0,0,0,.1); border-radius:8px; }
    pre { background:#111; color:#ddd; padding:12px; border-radius:8px; overflow:auto; }
  </style>
</head>
<body>
<header>
  <strong>CEU · Certificação (PPTX → PDF)</strong>
</header>
<div class="wrap">
  <?php if ($erro): ?>
    <div class="alert error">Erro: <?= htmlspecialchars($erro) ?></div>
    <?php if (!empty($info['conversao'])): ?>
      <pre><?= htmlspecialchars(json_encode($info['conversao'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)) ?></pre>
    <?php endif; ?>
    <div class="alert info">
      Dica rápida:
      <ul>
        <li>Instale o LibreOffice (Windows).</li>
        <li>Defina a variável de ambiente SOFFICE_PATH com o caminho completo do soffice.exe.</li>
        <li>Caminhos comuns: C:\Program Files\LibreOffice\program\soffice.exe</li>
      </ul>
    </div>
  <?php else: ?>
    <div class="alert info">PDF gerado a partir do PPTX preenchido.</div>
    <div class="actions">
      <a class="btn" href="<?= 'certificados/' . rawurlencode(basename($pdfFile)) ?>" download>Baixar PDF</a>
      <a class="btn" href="<?= 'certificados/' . rawurlencode(basename($pptxPreenchido)) ?>" download>Baixar PPTX preenchido</a>
    </div>
    <iframe src="<?= 'certificados/' . rawurlencode(basename($pdfFile)) ?>"></iframe>
  <?php endif; ?>
</div>
</body>
</html>