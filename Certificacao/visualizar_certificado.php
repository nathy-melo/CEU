<?php
/**
 * Visualização/Geração do Certificado (Opção A)
 * - Tenta usar um template DOCX em Certificacao/templates/certificado_padrao.docx
 * - Se não existir, usa um HTML de fallback (layout do site) e gera PDF com mPDF
 */

use CEU\Certificacao\ProcessadorTemplate;

$baseDir = __DIR__;
$libsAutoload = $baseDir . '/bibliotecas/vendor/autoload.php';
$templateDocx = $baseDir . '/templates/certificado_padrao.docx';
$saidaDir = $baseDir . '/certificados';
if (!is_dir($saidaDir)) { @mkdir($saidaDir, 0775, true); }

// Dados (podem vir por GET/POST)
$dados = [
  'NomeParticipante' => $_GET['nome'] ?? 'Elisa de Souza Lima',
  'NomeEvento' => $_GET['evento'] ?? 'Palestra de História',
  'NomeOrganizador' => $_GET['organizador'] ?? 'Aurora do Nascimento',
  'LocalEvento' => $_GET['local'] ?? 'IFMG - Campus Sabará',
  'Data' => $_GET['data'] ?? date('d/m/Y'),
  'CargaHoraria' => $_GET['carga'] ?? '2 horas',
  'CodigoAutenticador' => $_GET['codigo'] ?? 'J8KLMA736F',
];

$arquivoPdf = $saidaDir . '/certificado_' . date('Ymd_His') . '.pdf';
$geradoCom = 'html-fallback';
$erro = null;

try {
  if (file_exists($templateDocx)) {
    require_once $baseDir . '/ProcessadorTemplate.php';
    $proc = new ProcessadorTemplate($libsAutoload);
    $proc->gerarPdfDeDocx($templateDocx, $dados, $arquivoPdf, sys_get_temp_dir());
    $geradoCom = 'docx';
  } else {
    // Fallback: gerar HTML simples com mPDF direto
    if (!file_exists($libsAutoload)) {
      throw new Exception('Dependências não encontradas. Execute a instalação em Certificacao/index.php');
    }
    require_once $libsAutoload;
    // Monta HTML usando layout simplificado (não precisa CSS externo)
    $nome = htmlspecialchars($dados['NomeParticipante']);
    $evento = htmlspecialchars($dados['NomeEvento']);
    $org = htmlspecialchars($dados['NomeOrganizador']);
    $local = htmlspecialchars($dados['LocalEvento']);
    $data = htmlspecialchars($dados['Data']);
    $carga = htmlspecialchars($dados['CargaHoraria']);
    $codigo = htmlspecialchars($dados['CodigoAutenticador']);

    $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>
      @page { size: A4 landscape; margin: 20mm; }
      body { font-family: DejaVu Sans, Arial, sans-serif; color: #333; }
      .card { background:#4f6c8c; color:#fff; border-radius:16px; padding:32px 40px; }
      h1 { text-align:center; letter-spacing:4px; margin:0 0 24px; }
      p { line-height:1.4; }
      .body { font-size:16px; margin: 12px 0 24px; }
      .date { font-size:14px; margin: 4px 0 10px; }
      .verify { font-size:12px; opacity:.9; }
    </style></head><body>';
    $html .= '<div class="card">';
    $html .= '<h1>Certificado</h1>';
    $html .= '<p class="body">Certificamos que <strong>' . $nome . '</strong>, participou do(a) <strong>' . $evento . '</strong>, evento organizado por ' . $org . ', realizado no ' . $local . ' no dia ' . $data . ', com carga horária de ' . $carga . '.</p>';
    $html .= '<p class="date">Sabará, ' . $data . '.</p>';
    $html .= '<p class="verify">Este certificado é concedido como comprovação da participação no referido evento, tendo sido registrado na plataforma CEU. Sua autenticidade pode ser verificada por meio do código ' . $codigo . '.</p>';
    $html .= '</div></body></html>';

    $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4-L']);
    $mpdf->WriteHTML($html);
    $mpdf->Output($arquivoPdf, 'F');
  }
} catch (Throwable $e) {
  $erro = $e->getMessage();
}

// Saída HTML de visualização
?><!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Visualizar Certificado</title>
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
  </style>
</head>
<body>
<header>
  <strong>CEU · Certificação</strong>
</header>
<div class="wrap">
  <?php if ($erro): ?>
    <div class="alert error">Erro ao gerar certificado: <?= htmlspecialchars($erro) ?></div>
  <?php else: ?>
    <div class="alert info">Gerado com: <strong><?= htmlspecialchars($geradoCom) ?></strong> · Arquivo: <code><?= htmlspecialchars(basename($arquivoPdf)) ?></code></div>
    <div class="actions">
      <a class="btn" href="<?= 'certificados/' . rawurlencode(basename($arquivoPdf)) ?>" download>Baixar PDF</a>
    </div>
    <iframe src="<?= 'certificados/' . rawurlencode(basename($arquivoPdf)) ?>"></iframe>
  <?php endif; ?>
  <div style="margin-top:12px; font-size:.9rem; opacity:.8;">
    Para usar um template DOCX, envie um arquivo para <code>Certificacao/templates/certificado_padrao.docx</code> com placeholders como <code>{NomeParticipante}</code>.
  </div>
</div>
</body>
</html>