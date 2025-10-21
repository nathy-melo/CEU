<?php
session_start();
header('Content-Type: application/json');

// Verifica se usuário está logado
if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado', 'eventos' => []]);
    exit;
}

include_once '../BancoDados/conexao.php';

$cpf_usuario = $_SESSION['cpf'];

// Buscar eventos em que o usuário está inscrito com status ativo
$sql = "SELECT 
            e.cod_evento,
            e.categoria,
            e.nome,
            e.inicio,
            e.conclusao,
            e.duracao,
            e.certificado,
            e.lugar,
            e.modalidade,
            e.imagem,
            i.data_inscricao,
            i.status
        FROM inscricao i
        INNER JOIN evento e ON i.cod_evento = e.cod_evento
        WHERE i.CPF = ? AND i.status = 'ativa'
        ORDER BY e.inicio ASC";

$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, 's', $cpf_usuario);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

$eventos = [];

if ($resultado) {
    while ($evento = mysqli_fetch_assoc($resultado)) {
        $eventos[] = $evento;
    }
}

echo json_encode([
    'sucesso' => true,
    'eventos' => $eventos,
    'total' => count($eventos)
]);

mysqli_stmt_close($stmt);
mysqli_close($conexao);
?>
