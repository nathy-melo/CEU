<?php
session_start();
header('Content-Type: application/json');

// Verifica se usuário está logado
if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado']);
    exit;
}

include_once '../BancoDados/conexao.php';

$cpf_usuario = $_SESSION['cpf'];
$cod_evento = isset($_POST['cod_evento']) ? (int)$_POST['cod_evento'] : 0;

if ($cod_evento <= 0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Código do evento inválido']);
    exit;
}

// Verifica se está inscrito
$sql_verifica = "SELECT status FROM inscricao WHERE CPF = ? AND cod_evento = ?";
$stmt_verifica = mysqli_prepare($conexao, $sql_verifica);
mysqli_stmt_bind_param($stmt_verifica, 'si', $cpf_usuario, $cod_evento);
mysqli_stmt_execute($stmt_verifica);
$resultado_verifica = mysqli_stmt_get_result($stmt_verifica);

if (!$resultado_verifica || mysqli_num_rows($resultado_verifica) === 0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Você não está inscrito neste evento']);
    exit;
}

$inscricao = mysqli_fetch_assoc($resultado_verifica);

if ($inscricao['status'] === 'cancelada') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sua inscrição já está cancelada']);
    exit;
}

// Cancelar inscrição (soft delete)
$sql_cancelar = "UPDATE inscricao SET status = 'cancelada' WHERE CPF = ? AND cod_evento = ?";
$stmt_cancelar = mysqli_prepare($conexao, $sql_cancelar);
mysqli_stmt_bind_param($stmt_cancelar, 'si', $cpf_usuario, $cod_evento);

if (mysqli_stmt_execute($stmt_cancelar)) {
    echo json_encode(['sucesso' => true, 'mensagem' => 'Inscrição cancelada com sucesso!']);
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao cancelar inscrição: ' . mysqli_error($conexao)]);
}

mysqli_stmt_close($stmt_cancelar);
mysqli_stmt_close($stmt_verifica);
mysqli_close($conexao);
?>
