<?php
// Desabilita exibição de erros para não quebrar o JSON
error_reporting(0);
ini_set('display_errors', 0);

// Inicia a sessão apenas se não houver uma ativa
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Define o cabeçalho para JSON ANTES de qualquer output
header('Content-Type: application/json');

// Verifica se o usuário está logado
if (!isset($_SESSION['cpf']) || !isset($_SESSION['organizador']) || $_SESSION['organizador'] != 1) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Usuário não autenticado ou não é organizador.'
    ]);
    exit;
}

// Verifica se é requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Método não permitido.'
    ]);
    exit;
}

// Inclui o arquivo de conexão
require_once('../BancoDados/conexao.php');

$cpf = $_SESSION['cpf'];

// Obtém os dados do POST
$dados = json_decode(file_get_contents('php://input'), true);

// Se for apenas para verificar se há solicitação pendente
if (isset($dados['verificar_pendente']) && $dados['verificar_pendente'] === true) {
    $sql_check = "SELECT id, data_exclusao_programada FROM solicitacoes_exclusao_conta WHERE CPF = ? AND status = 'pendente'";
    $stmt_check = mysqli_prepare($conexao, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "s", $cpf);
    mysqli_stmt_execute($stmt_check);
    $resultado_check = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($resultado_check) > 0) {
        $solicitacao = mysqli_fetch_assoc($resultado_check);
        mysqli_stmt_close($stmt_check);
        echo json_encode([
            'pendente' => true,
            'data_exclusao' => $solicitacao['data_exclusao_programada']
        ]);
    } else {
        mysqli_stmt_close($stmt_check);
        echo json_encode([
            'pendente' => false
        ]);
    }
    mysqli_close($conexao);
    exit;
}

$senha = $dados['senha'] ?? '';

// Valida senha
if (empty($senha)) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Senha é obrigatória para confirmar a exclusão.'
    ]);
    exit;
}

// Verifica se a senha está correta
$sql_verifica = "SELECT Senha, Email, Nome FROM usuario WHERE CPF = ?";
$stmt_verifica = mysqli_prepare($conexao, $sql_verifica);
if (!$stmt_verifica) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro ao preparar consulta.'
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt_verifica, "s", $cpf);
mysqli_stmt_execute($stmt_verifica);
$resultado = mysqli_stmt_get_result($stmt_verifica);
$usuario = mysqli_fetch_assoc($resultado);
mysqli_stmt_close($stmt_verifica);

if (!$usuario || !password_verify($senha, $usuario['Senha'])) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Senha incorreta.'
    ]);
    mysqli_close($conexao);
    exit;
}

// Verifica se já existe uma solicitação pendente
$sql_check = "SELECT id, data_exclusao_programada FROM solicitacoes_exclusao_conta WHERE CPF = ? AND status = 'pendente'";
$stmt_check = mysqli_prepare($conexao, $sql_check);
mysqli_stmt_bind_param($stmt_check, "s", $cpf);
mysqli_stmt_execute($stmt_check);
$resultado_check = mysqli_stmt_get_result($stmt_check);

if (mysqli_num_rows($resultado_check) > 0) {
    $solicitacao = mysqli_fetch_assoc($resultado_check);
    mysqli_stmt_close($stmt_check);
    
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Já existe uma solicitação de exclusão pendente para esta conta.',
        'data_exclusao' => $solicitacao['data_exclusao_programada']
    ]);
    mysqli_close($conexao);
    exit;
}
mysqli_stmt_close($stmt_check);

// Cria a solicitação de exclusão (30 dias a partir de agora)
$data_exclusao = date('Y-m-d H:i:s', strtotime('+30 days'));

$sql_solicita = "INSERT INTO solicitacoes_exclusao_conta (CPF, data_exclusao_programada, status) VALUES (?, ?, 'pendente')";
$stmt_solicita = mysqli_prepare($conexao, $sql_solicita);
mysqli_stmt_bind_param($stmt_solicita, "ss", $cpf, $data_exclusao);

if (mysqli_stmt_execute($stmt_solicita)) {
    mysqli_stmt_close($stmt_solicita);
    
    // TODO: Enviar email de confirmação para o usuário
    // Email deve informar que a conta será excluída em 30 dias
    // e que ele pode cancelar acessando sua conta
    
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Solicitação de exclusão criada com sucesso. Sua conta será excluída em 30 dias.',
        'data_exclusao' => $data_exclusao,
        'email' => $usuario['Email']
    ]);
} else {
    mysqli_stmt_close($stmt_solicita);
    
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro ao criar solicitação de exclusão: ' . mysqli_error($conexao)
    ]);
}

mysqli_close($conexao);
?>
