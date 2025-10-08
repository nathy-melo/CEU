<?php
// Endpoint de cadastro/ativação de organizador com suporte a AJAX e fallback.
include_once('../BancoDados/conexao.php');

function responderJsonOrg($status, $mensagem, $extras = []) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge([
        'status' => $status,
        'mensagem' => $mensagem
    ], $extras));
}

$ehAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

$nome_completo = isset($_POST['nome_completo']) ? trim($_POST['nome_completo']) : '';
$cpf = isset($_POST['cpf']) ? preg_replace('/[^0-9]/', '', $_POST['cpf']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$senha = isset($_POST['senha']) ? $_POST['senha'] : '';
$codigo_acesso = isset($_POST['codigo_acesso']) ? trim($_POST['codigo_acesso']) : '';

if (!$nome_completo || !$cpf || !$email || !$senha || !$codigo_acesso) {
    if ($isAjax) { responderJSONOrg('erro', '⚠️ Campos obrigatórios ausentes.'); exit; }
    echo "<script>alert('Preencha todos os campos.'); history.back();</script>"; exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    if ($isAjax) { responderJSONOrg('erro', '⚠️ E-mail inválido.'); exit; }
    echo "<script>alert('E-mail inválido.'); history.back();</script>"; exit;
}

$senhaCriptografada = password_hash($senha, PASSWORD_DEFAULT);

// Verifica código usando o sistema atualizado
// Verificar se o código existe e está ativo
$sql_check = "SELECT id, codigo, ativo, usado FROM codigos_organizador WHERE codigo = ? AND ativo = 1 AND usado = 0";
$stmt_check = mysqli_prepare($conexao, $sql_check);
mysqli_stmt_bind_param($stmt_check, "s", $codigo_acesso);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if (mysqli_num_rows($result_check) > 0) {
    $codigoData = mysqli_fetch_assoc($result_check);
    
    // Código é válido, criar novo usuário organizador
    $sql = "INSERT INTO usuario (CPF, Nome, Email, Senha, Codigo, Organizador) VALUES (?, ?, ?, ?, ?, 1)";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "sssss", $cpf, $nome_completo, $email, $senhaCriptografada, $codigo_acesso);
    
    if (mysqli_stmt_execute($stmt)) {
        // Marca o código como usado
        $sql_update = "UPDATE codigos_organizador SET usado = 1, data_uso = NOW(), usado_por = ? WHERE id = ?";
        $stmt_update = mysqli_prepare($conexao, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "si", $cpf, $codigoData['id']);
        mysqli_stmt_execute($stmt_update);
        
        mysqli_close($conexao);
        if ($ehAjax) { responderJsonOrg('sucesso', '✅ Cadastro realizado com sucesso!'); exit; }
        header('Location: ContainerPublico.php?pagina=login'); exit;
    } else {
        $erroBanco = mysqli_error($conexao);
        mysqli_close($conexao);
        if ($ehAjax) { responderJsonOrg('erro', '❌ Erro ao criar conta: ' . $erroBanco); exit; }
        echo "<script>alert('Erro ao criar conta: $erroBanco'); history.back();</script>"; exit;
    }
} else {
    mysqli_close($conexao);
    if ($ehAjax) { responderJsonOrg('erro', '⚠️ Código de acesso inválido ou já utilizado.'); exit; }
    echo "<script>alert('Código inválido ou já utilizado.'); history.back();</script>"; exit;
}
?>