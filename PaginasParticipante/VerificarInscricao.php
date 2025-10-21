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
$cod_evento = isset($_GET['cod_evento']) ? (int)$_GET['cod_evento'] : 0;

if ($cod_evento <= 0) {
    echo json_encode(['inscrito' => false, 'mensagem' => 'Código inválido']);
    exit;
}

// Verifica se está inscrito com status ativo
$sql = "SELECT status FROM inscricao WHERE CPF = ? AND cod_evento = ? AND status = 'ativa'";
$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, 'si', $cpf_usuario, $cod_evento);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

$inscrito = ($resultado && mysqli_num_rows($resultado) > 0);

echo json_encode(['inscrito' => $inscrito]);

mysqli_stmt_close($stmt);
mysqli_close($conexao);
?>
