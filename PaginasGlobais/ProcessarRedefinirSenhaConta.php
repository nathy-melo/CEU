<?php
// Configuração do tempo de sessão para 6 minutos (5min de inatividade + 1min de extensão)
ini_set('session.gc_maxlifetime', 360);
session_set_cookie_params(360);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Verifica se a sessão expirou (5 minutos de inatividade)
if (isset($_SESSION['ultima_atividade']) && (time() - $_SESSION['ultima_atividade'] > 300)) {
    session_unset();
    session_destroy();
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão expirada']);
    exit;
}

// Atualiza o timestamp da última atividade
$_SESSION['ultima_atividade'] = time();

// Define o cabeçalho para JSON
header('Content-Type: application/json');

// Verifica se o usuário está logado
if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado']);
    exit;
}

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método de requisição inválido']);
    exit;
}

// Inclui o arquivo de conexão
require_once('../BancoDados/conexao.php');

// Obtém os dados do formulário
$senhaAtual = isset($_POST['redefinir_senha_conta']['senha_atual']) ? trim($_POST['redefinir_senha_conta']['senha_atual']) : '';
$novaSenha = isset($_POST['redefinir_senha_conta']['nova_senha']) ? trim($_POST['redefinir_senha_conta']['nova_senha']) : '';
$confirmarSenha = isset($_POST['redefinir_senha_conta']['confirmar_senha']) ? trim($_POST['redefinir_senha_conta']['confirmar_senha']) : '';

// Valida os campos obrigatórios
if (empty($senhaAtual) || empty($novaSenha) || empty($confirmarSenha)) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Todos os campos são obrigatórios.'
    ]);
    exit;
}

// Valida o tamanho mínimo da senha
if (strlen($novaSenha) < 8) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'A nova senha deve ter pelo menos 8 caracteres.'
    ]);
    exit;
}

// Valida se a nova senha e confirmação são iguais
if ($novaSenha !== $confirmarSenha) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'A nova senha e a confirmação não coincidem.'
    ]);
    exit;
}

// Valida se a nova senha é diferente da senha atual
if ($senhaAtual === $novaSenha) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'A nova senha deve ser diferente da senha atual.'
    ]);
    exit;
}

// Obtém o CPF do usuário da sessão
$cpf = $_SESSION['cpf'];

// Busca a senha atual do usuário no banco de dados
$sql = "SELECT Senha FROM usuario WHERE CPF = ?";
$stmt = mysqli_prepare($conexao, $sql);

if (!$stmt) {
    mysqli_close($conexao);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro ao preparar consulta no banco de dados.'
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $cpf);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$usuario = mysqli_fetch_assoc($resultado);
mysqli_stmt_close($stmt);

// Verifica se o usuário foi encontrado
if (!$usuario) {
    mysqli_close($conexao);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Usuário não encontrado.'
    ]);
    exit;
}

// Verifica se a senha atual está correta
$senhaHashAtual = $usuario['Senha'];

if (!$senhaHashAtual || !password_verify($senhaAtual, $senhaHashAtual)) {
    mysqli_close($conexao);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Senha atual incorreta.'
    ]);
    exit;
}

// Gera o hash da nova senha
$novaSenhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);

// Atualiza a senha no banco de dados
$sqlUpdate = "UPDATE usuario SET Senha = ? WHERE CPF = ?";
$stmtUpdate = mysqli_prepare($conexao, $sqlUpdate);

if (!$stmtUpdate) {
    mysqli_close($conexao);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro ao preparar atualização no banco de dados.'
    ]);
    exit;
}

mysqli_stmt_bind_param($stmtUpdate, "ss", $novaSenhaHash, $cpf);

if (mysqli_stmt_execute($stmtUpdate)) {
    mysqli_stmt_close($stmtUpdate);
    mysqli_close($conexao);
    
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Senha redefinida com sucesso!'
    ]);
} else {
    $erro = mysqli_error($conexao);
    mysqli_stmt_close($stmtUpdate);
    mysqli_close($conexao);
    
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro ao atualizar a senha: ' . $erro
    ]);
}
?>

