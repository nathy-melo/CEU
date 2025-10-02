<?php
// Endpoint de cadastro do participante com suporte a AJAX (JSON) e fallback tradicional.
include_once('../BancoDados/conexao.php');

function responderJson($status, $mensagem, $extras = []) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge([
        'status' => $status,
        'mensagem' => $mensagem
    ], $extras));
}

$ehAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Campos obrigatórios
$nome_completo = isset($_POST['nome_completo']) ? trim($_POST['nome_completo']) : '';
$cpf = isset($_POST['cpf']) ? preg_replace('/[^0-9]/', '', $_POST['cpf']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$senha = isset($_POST['senha']) ? $_POST['senha'] : '';

if (!$nome_completo || !$cpf || !$email || !$senha) {
    if ($isAjax) { responderJSON('erro', '⚠️ Campos obrigatórios ausentes.'); exit; }
    echo "<script>alert('Preencha todos os campos.'); history.back();</script>"; exit;
}

// Validar formato básico de email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    if ($isAjax) { responderJSON('erro', '⚠️ E-mail inválido.'); exit; }
    echo "<script>alert('E-mail inválido.'); history.back();</script>"; exit;
}

// Verifica CPF duplicado
$sqlVerificarCPF = "SELECT 1 FROM usuario WHERE CPF = '$cpf' LIMIT 1";
$resultadoVerificarCPF = mysqli_query($conexao, $sqlVerificarCPF);
if ($resultadoVerificarCPF && mysqli_num_rows($resultadoVerificarCPF) > 0) {
    if ($ehAjax) { responderJson('erro', '⚠️ CPF já cadastrado.'); exit; }
    echo "<script>alert('CPF já cadastrado.'); history.back();</script>"; exit;
}

// Verifica e-mail duplicado
$sqlVerificarEmail = "SELECT 1 FROM usuario WHERE Email = '" . mysqli_real_escape_string($conexao, $email) . "' LIMIT 1";
$resultadoVerificarEmail = mysqli_query($conexao, $sqlVerificarEmail);
if ($resultadoVerificarEmail && mysqli_num_rows($resultadoVerificarEmail) > 0) {
    if ($ehAjax) { responderJson('erro', '⚠️ E-mail já cadastrado.'); exit; }
    echo "<script>alert('E-mail já cadastrado.'); history.back();</script>"; exit;
}

$senhaCriptografada = password_hash($senha, PASSWORD_DEFAULT);

$sql = "INSERT INTO usuario (CPF, Nome, Email, Senha, Organizador) VALUES (
    '$cpf', '" . mysqli_real_escape_string($conexao, $nome_completo) . "', '" . mysqli_real_escape_string($conexao, $email) . "', '$senhaCriptografada', 0)";

if (!mysqli_query($conexao, $sql)) {
    $erroBanco = mysqli_error($conexao);
    mysqli_close($conexao);
    if ($ehAjax) { responderJson('erro', '❌ Erro ao cadastrar: ' . $erroBanco); exit; }
    echo "<script>alert('Erro ao cadastrar: $erro'); history.back();</script>"; exit;
}

mysqli_close($conexao);

if ($ehAjax) {
    responderJson('sucesso', '✅ Cadastro realizado com sucesso!');
    exit;
}

header('Location: ContainerPublico.php?pagina=login');
exit;
?>