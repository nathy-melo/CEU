<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Permite apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

// Verifica se usuário está logado
if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado.']);
    exit;
}

$raw = file_get_contents('php://input');
$dados = json_decode($raw, true);
$tema = isset($dados['tema']) ? (string)$dados['tema'] : null;

if ($tema !== '0' && $tema !== '1') {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Parâmetro inválido.']);
    exit;
}

$temaInt = (int)$tema;
$cpf = $_SESSION['cpf'];

require_once('../BancoDados/conexao.php');

// Evita SQL injection em CPF por garantia
$cpfEsc = mysqli_real_escape_string($conexao, $cpf);
$sql = "UPDATE usuario SET TemaSite = $temaInt WHERE CPF = '$cpfEsc'";

if (!mysqli_query($conexao, $sql)) {
    $err = mysqli_error($conexao);
    // Código 1054: Unknown column
    if (strpos($err, 'Unknown column') !== false || mysqli_errno($conexao) == 1054) {
        // tenta migrar a tabela
        $alter = "ALTER TABLE usuario ADD COLUMN IF NOT EXISTS TemaSite tinyint(1) NOT NULL DEFAULT 0";
        @mysqli_query($conexao, $alter);
        // tenta novamente
        if (!mysqli_query($conexao, $sql)) {
            http_response_code(500);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao salvar preferências.']);
            mysqli_close($conexao);
            exit;
        }
    } else {
        http_response_code(500);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao salvar preferências.']);
        mysqli_close($conexao);
        exit;
    }
}

// Atualiza sessão
$_SESSION['tema_site'] = $temaInt;

mysqli_close($conexao);

echo json_encode(['sucesso' => true, 'tema' => $temaInt]);
