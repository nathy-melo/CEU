<?php
// Ajusta o cookie da sessão para ser visível em todo o site
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

// Busca o usuário no banco (inclui TemaSite)
$sql = "SELECT CPF, Nome, Email, Organizador, COALESCE(TemaSite, 0), Senha AS TemaSite FROM usuario WHERE Email = '$email'";
$resultado = mysqli_query($conexao, $sql);

// Verifica se houve erro na consulta
if (!$resultado) {
    $errno = mysqli_errno($conexao);
    $err = mysqli_error($conexao);
    if ($errno == 1054 || stripos($err, 'Unknown column') !== false) {
        // tenta adicionar a coluna e refazer a consulta
        @mysqli_query($conexao, "ALTER TABLE usuario ADD COLUMN TemaSite tinyint(1) NOT NULL DEFAULT 0");
        $resultado = mysqli_query($conexao, $sql);
    }
}

if (!$resultado) {
    mysqli_close($conexao);
    redirecionarComErro('erro_servidor');
}

$usuario = mysqli_fetch_assoc($resultado);
$senhaHash = $usuario['Senha'];

if (password_verify($senha, $senhaHash)) {  
    // Login válido - cria a sessão

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
    $_SESSION['tema_site'] = (int)$usuario['TemaSite']; // 0=claro, 1=escuro

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
