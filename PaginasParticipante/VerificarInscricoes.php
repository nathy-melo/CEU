<?php
session_start();
header('Content-Type: application/json');

// Verifica se usuário está logado
if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado']);
    exit;
}

include_once '../BancoDados/conexao.php';

$cpf_usuario = $_SESSION['cpf'];

// Recebe JSON do corpo da requisição
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['eventos']) || !is_array($data['eventos'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos']);
    exit;
}

$eventos = array_filter(array_map('intval', $data['eventos']), function($cod) {
    return $cod > 0;
});

if (empty($eventos)) {
    echo json_encode(['sucesso' => true, 'inscricoes' => []]);
    exit;
}

// Monta query para buscar todas as inscrições de uma vez
$placeholders = implode(',', array_fill(0, count($eventos), '?'));
$sql = "SELECT cod_evento FROM inscricao WHERE CPF = ? AND cod_evento IN ($placeholders) AND status = 'ativa'";

$stmt = mysqli_prepare($conexao, $sql);

// Prepara tipos e valores para bind_param
$types = 's' . str_repeat('i', count($eventos));
$params = array_merge([$cpf_usuario], $eventos);

// Bind dos parâmetros
$bind_names[] = $types;
for ($i = 0; $i < count($params); $i++) {
    $bind_name = 'bind' . $i;
    $$bind_name = $params[$i];
    $bind_names[] = &$$bind_name;
}
call_user_func_array([$stmt, 'bind_param'], $bind_names);

mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

// Cria array com status de cada evento
$inscricoes = [];
foreach ($eventos as $cod) {
    $inscricoes[$cod] = false; // Padrão: não inscrito
}

// Marca como inscrito os eventos encontrados
while ($row = mysqli_fetch_assoc($resultado)) {
    $inscricoes[$row['cod_evento']] = true;
}

echo json_encode(['sucesso' => true, 'inscricoes' => $inscricoes]);

mysqli_stmt_close($stmt);
mysqli_close($conexao);
?>