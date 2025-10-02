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

$sqlVerificarCodigo = "SELECT CPF, Nome, Email FROM usuario WHERE Codigo = '" . mysqli_real_escape_string($conexao, $codigo_acesso) . "' LIMIT 1";
$resultadoVerificarCodigo = mysqli_query($conexao, $sqlVerificarCodigo);

if ($resultadoVerificarCodigo && mysqli_num_rows($resultadoVerificarCodigo) > 0) {
    $usuario = mysqli_fetch_assoc($resultadoVerificarCodigo);
    if (is_null($usuario['CPF']) || is_null($usuario['Nome']) || is_null($usuario['Email'])) {
        $sql = "UPDATE usuario SET CPF = '$cpf', Nome = '" . mysqli_real_escape_string($conexao, $nome_completo) . "', Email = '" . mysqli_real_escape_string($conexao, $email) . "', Senha = '$senhaCriptografada', Organizador = 1 WHERE Codigo = '" . mysqli_real_escape_string($conexao, $codigo_acesso) . "'";
        if (!mysqli_query($conexao, $sql)) {
            $erroBanco = mysqli_error($conexao);
            mysqli_close($conexao);
            if ($ehAjax) { responderJsonOrg('erro', '❌ Erro ao atualizar: ' . $erroBanco); exit; }
            echo "<script>alert('Erro ao atualizar: $erro'); history.back();</script>"; exit;
        }
        mysqli_close($conexao);
        if ($ehAjax) { responderJsonOrg('sucesso', '✅ Cadastro realizado com sucesso!'); exit; }
        header('Location: ContainerPublico.php?pagina=login'); exit;
    } else {
        mysqli_close($conexao);
        if ($ehAjax) { responderJsonOrg('erro', '⚠️ Código já utilizado por outro organizador.'); exit; }
        echo "<script>alert('Código já utilizado.'); history.back();</script>"; exit;
    }
} else {
    mysqli_close($conexao);
    if ($ehAjax) { responderJsonOrg('erro', '⚠️ Código de acesso inválido.'); exit; }
    echo "<script>alert('Código inválido.'); history.back();</script>"; exit;
}
?>