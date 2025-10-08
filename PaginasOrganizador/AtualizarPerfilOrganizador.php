<?php
// Inicia a sessão
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}
session_start();

// Define o cabeçalho para JSON
header('Content-Type: application/json');

// Verifica se o usuário está logado
if (!isset($_SESSION['cpf']) || !isset($_SESSION['organizador']) || $_SESSION['organizador'] != 1) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Usuário não autenticado ou não é organizador.'
    ]);
    exit;
}

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Método de requisição inválido.'
    ]);
    exit;
}

// Inclui o arquivo de conexão
require_once('../BancoDados/conexao.php');

// Obtém os dados do formulário
$nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$telefone = isset($_POST['telefone']) ? trim($_POST['telefone']) : null;
$ra = isset($_POST['ra']) ? trim($_POST['ra']) : null;

// Valida os campos obrigatórios
if (empty($nome) || empty($email)) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Nome e e-mail são obrigatórios.'
    ]);
    exit;
}

// Valida o formato do e-mail
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'E-mail inválido.'
    ]);
    exit;
}

// Valida o RA (se fornecido, deve ter no máximo 7 caracteres)
if (!empty($ra) && strlen($ra) > 7) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'RA deve ter no máximo 7 caracteres.'
    ]);
    exit;
}

$cpf = $_SESSION['cpf'];

// Verifica se o e-mail já está em uso por outro usuário
$sql_verifica = "SELECT CPF FROM usuario WHERE Email = ? AND CPF != ?";
$stmt_verifica = mysqli_prepare($conexao, $sql_verifica);
mysqli_stmt_bind_param($stmt_verifica, "ss", $email, $cpf);
mysqli_stmt_execute($stmt_verifica);
$resultado_verifica = mysqli_stmt_get_result($stmt_verifica);

if (mysqli_num_rows($resultado_verifica) > 0) {
    mysqli_stmt_close($stmt_verifica);
    mysqli_close($conexao);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Este e-mail já está sendo usado por outro usuário.'
    ]);
    exit;
}
mysqli_stmt_close($stmt_verifica);

// Atualiza os dados do usuário
$sql_atualiza = "UPDATE usuario SET Nome = ?, Email = ?, RA = ? WHERE CPF = ?";
$stmt_atualiza = mysqli_prepare($conexao, $sql_atualiza);

// Se RA estiver vazio, define como NULL
$ra_value = empty($ra) ? null : $ra;
mysqli_stmt_bind_param($stmt_atualiza, "ssss", $nome, $email, $ra_value, $cpf);

if (mysqli_stmt_execute($stmt_atualiza)) {
    // Atualiza os dados na sessão
    $_SESSION['nome'] = $nome;
    $_SESSION['email'] = $email;
    
    mysqli_stmt_close($stmt_atualiza);
    mysqli_close($conexao);
    
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Perfil atualizado com sucesso.'
    ]);
} else {
    mysqli_stmt_close($stmt_atualiza);
    mysqli_close($conexao);
    
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro ao atualizar o perfil: ' . mysqli_error($conexao)
    ]);
}
?>
