<?php
session_start();
include_once('../BancoDados/conexao.php');

// Função para redirecionar com erro
function redirecionarComErro($tipoErro)
{
    header("Location: ContainerPublico.php?pagina=login&erro=$tipoErro");
    exit;
}

// Função para validar email
function validarEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    redirecionarComErro('acesso_negado');
}

// Sanitiza e valida os dados de entrada
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$senha = isset($_POST['senha']) ? trim($_POST['senha']) : '';

// Verifica se os campos estão preenchidos
if (empty($email) || empty($senha)) {
    redirecionarComErro('campos_obrigatorios');
}

// Valida o formato do email
if (!validarEmail($email)) {
    redirecionarComErro('email_invalido');
}

// Verifica o tamanho mínimo da senha
if (strlen($senha) < 4) {
    redirecionarComErro('senha_invalida');
}

// Escapa os dados para prevenir SQL injection
$email = mysqli_real_escape_string($conexao, $email);
$senha = mysqli_real_escape_string($conexao, $senha);

// Busca o usuário no banco
$sql = "SELECT CPF, Nome, Email, Organizador FROM usuario WHERE Email = '$email' AND Senha = '$senha'";
$resultado = mysqli_query($conexao, $sql);

// Verifica se houve erro na consulta
if (!$resultado) {
    mysqli_close($conexao);
    redirecionarComErro('erro_servidor');
}

if (mysqli_num_rows($resultado) == 1) {
    // Login válido - cria a sessão
    $usuario = mysqli_fetch_assoc($resultado);

    // Verifica se o usuário tem dados completos
    if (empty($usuario['CPF']) || empty($usuario['Nome'])) {
        mysqli_close($conexao);
        redirecionarComErro('dados_incompletos');
    }

    // Cria as variáveis de sessão
    $_SESSION['username'] = $usuario['Nome'];
    $_SESSION['email'] = $usuario['Email'];
    $_SESSION['cpf'] = $usuario['CPF'];
    $_SESSION['organizador'] = $usuario['Organizador'];
    $_SESSION['login_time'] = time();

    mysqli_close($conexao);

    // Redireciona baseado no tipo de usuário
    if ($usuario['Organizador'] == 1) {
        header('Location: ../PaginasOrganizador/ContainerOrganizador.php?pagina=inicio');
    } else {
        header('Location: ../PaginasParticipante/ContainerParticipante.php?pagina=inicio');
    }
    exit;
} else {
    // Login inválido - incrementar contador de tentativas (futuro)
    mysqli_close($conexao);
    redirecionarComErro('credenciais_invalidas');
}
