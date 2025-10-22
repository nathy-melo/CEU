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

// Inclui a biblioteca FPDF
require_once '../vendor/autoload.php';
use Fpdf\Fpdf;

$pdf = new Fpdf();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

// Adiciona o modelo do certificado
$pdf->Image('../Imagens/Certificado.svg', 0, 0, 210, 297);

// Adiciona o nome do participante
$pdf->SetXY(30, 120);
$pdf->Cell(150, 10, utf8_decode($dados['nome_participante']), 0, 1, 'C');

// Adiciona o nome do evento e a data
$pdf->SetXY(30, 140);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(150, 10, utf8_decode("Evento: " . $dados['nome_evento']), 0, 1, 'C');
$pdf->SetXY(30, 150);
$pdf->Cell(150, 10, utf8_decode("Data: " . date('d/m/Y', strtotime($dados['data_evento']))), 0, 1, 'C');

// Gera o PDF para download
$pdf->Output('D', 'certificado.pdf');
exit();