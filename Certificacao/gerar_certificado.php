<?php
require_once '../BancoDados/conexao.php';

// Verifica se o ID do participante e do evento foram enviados
if (!isset($_GET['id_participante']) || !isset($_GET['id_evento'])) {
    die('Parâmetros insuficientes para gerar o certificado.');
}

$id_participante = $_GET['id_participante'];
$id_evento = $_GET['id_evento'];

// Consulta os dados do participante e do evento no banco de dados
$sql = "SELECT p.nome AS nome_participante, e.nome AS nome_evento, e.data_evento
        FROM participantes p
        JOIN eventos e ON e.id = ?
        WHERE p.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $id_evento, $id_participante);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Certificado não encontrado para os parâmetros fornecidos.');
}

$dados = $result->fetch_assoc();

// Inclui o autoloader do Composer da nova pasta de bibliotecas
require_once __DIR__ . '/bibliotecas/vendor/autoload.php';

$mpdfClass = '\\Mpdf\\Mpdf';
if (!class_exists($mpdfClass)) {
    die('Biblioteca mPDF não instalada. Acesse Certificacao/index.php e clique em "Instalar Automaticamente".');
}

$mpdf = new $mpdfClass(['format' => 'A4']);
$mpdf->SetTitle('Certificado');

$bgPath = realpath(__DIR__ . '/../Imagens/Certificado.svg');
$bgUrl = $bgPath ? $bgPath : (__DIR__ . '/../Imagens/Certificado.svg');

$html = '<html><head><style>
    body { font-family: Arial, sans-serif; }
    .container { position: relative; width: 100%; height: 100%; }
    .bg { position: absolute; top: -10mm; left: -10mm; right: -10mm; bottom: -10mm; z-index: 0; }
    .content { position: relative; z-index: 1; text-align: center; padding-top: 70mm; }
    .nome { font-size: 20pt; font-weight: bold; }
    .detalhes { margin-top: 8mm; font-size: 12pt; }
    .data { margin-top: 12mm; font-size: 12pt; }
</style></head><body>
<div class="container">
  <div class="bg"><img src="' . $bgUrl . '" style="width:100%;"></div>
  <div class="content">
    <div class="nome">' . htmlspecialchars($dados['nome_participante']) . '</div>
    <div class="detalhes">Evento: ' . htmlspecialchars($dados['nome_evento']) . '</div>
    <div class="data">Data: ' . date('d/m/Y', strtotime($dados['data_evento'])) . '</div>
  </div>
</div>
</body></html>';

$mpdf->WriteHTML($html);
$mpdf->Output('certificado.pdf', 'D');
exit();