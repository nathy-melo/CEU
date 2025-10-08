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

// Verifica se o usuário está logado
if (!isset($_SESSION['cpf']) || !isset($_SESSION['organizador']) || $_SESSION['organizador'] != 1) {
    header('Location: ../PaginasPublicas/ContainerPublico.php?pagina=login&erro=login_requerido');
    exit;
}

// Inclui o arquivo de conexão
require_once('../BancoDados/conexao.php');

$cpf = $_SESSION['cpf'];

// Busca os dados do usuário no banco de dados
$sql = "SELECT Nome, Email, CPF, RA FROM usuario WHERE CPF = ?";
$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, "s", $cpf);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($resultado)) {
    $nome = $row['Nome'] ?? '';
    $email = $row['Email'] ?? '';
    $cpf_formatado = $row['CPF'] ?? '';
    $ra = $row['RA'] ?? '';
    
    // Formata o CPF para exibição (XXX.XXX.XXX-XX)
    if (strlen($cpf_formatado) == 11) {
        $cpf_formatado = substr($cpf_formatado, 0, 3) . '.' . 
                        substr($cpf_formatado, 3, 3) . '.' . 
                        substr($cpf_formatado, 6, 3) . '-' . 
                        substr($cpf_formatado, 9, 2);
    }
} else {
    // Se não encontrar o usuário, redireciona para o login
    header('Location: ../PaginasPublicas/ContainerPublico.php?pagina=login&erro=usuario_nao_encontrado');
    exit;
}

mysqli_stmt_close($stmt);
mysqli_close($conexao);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil</title>
    <link rel="stylesheet" href="../styleGlobal.css" />
</head>
<style>
    .container-perfil {
        width: 30rem;
        max-width: 100%;
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .cartao-dados {
        background-color: var(--caixas);
        border-radius: 0.5rem;
        padding: 1.25rem 1.25rem 1.5rem 1.25rem;
        box-shadow: 0 0.25rem 1rem var(--sombra-padrao);
    }

    .titulo-cartao {
        color: var(--branco);
        font-weight: 700;
        font-size: 2.25rem;
        line-height: 1.1;
        text-align: center;
        letter-spacing: 0;
        text-shadow: 0 0.125rem 0.5rem var(--sombra-padrao);
        margin-top: 0;
        margin-bottom: 1.25rem;
    }

    .grupo-formulario {
        margin-bottom: 1rem;
    }

    .grupo-formulario:last-of-type {
        margin-bottom: 0;
    }

    label {
        display: block;
        color: var(--branco);
        font-weight: 700;
        font-size: 1rem;
        margin-bottom: 0.25rem;
        letter-spacing: 0;
    }

    .controle-formulario {
        background-color: var(--branco);
        border-radius: 0.1875rem;
        padding: 0.25rem 0.5rem;
        font-size: 1rem;
        color: var(--cinza-escuro);
        width: 100%;
        line-height: 1.5;
        border: none;
        box-shadow: 0 0.0625rem 0.1875rem rgba(0, 0, 0, 0.15);
        margin-top: 0;
        margin-bottom: 0;
        min-height: 2rem;
        display: flex;
        align-items: center;
    }

    .controle-formulario-input {
        background-color: var(--branco);
        border-radius: 0.1875rem;
        padding: 0.25rem 0.5rem;
        font-size: 1rem;
        color: var(--cinza-escuro);
        width: 100%;
        line-height: 1.5;
        border: 2px solid var(--botao);
        box-shadow: 0 0.0625rem 0.1875rem rgba(0, 0, 0, 0.15);
        margin-top: 0;
        margin-bottom: 0;
        min-height: 2rem;
        font-family: 'Inter', sans-serif;
    }

    .controle-formulario-input:focus {
        outline: none;
        border-color: var(--botao);
        box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.2);
    }

    .acoes-formulario {
        margin-top: 1.25rem;
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .botao {
        border: none;
        border-radius: 0.3125rem;
        color: var(--branco);
        font-family: 'Inter', sans-serif;
        font-weight: 700;
        font-size: 1.125rem;
        cursor: pointer;
        padding: 0.5rem 2rem;
        text-align: center;
        transition: opacity 0.2s ease;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.15);
        background-color: var(--botao);
    }

    .botao:hover {
        opacity: 0.9;
    }

    .botao-editar {
        background-color: var(--botao);
        width: 7.5rem;
        margin-left: auto;
    }

    .botao-confirmar {
        background-color: #0a7a09;
        width: 8.5rem;
    }

    .botao-cancelar {
        background-color: #6c757d;
        width: 8.5rem;
    }

    .botao-excluir {
        background-color: #7a0909;
        width: 10rem;
        font-size: 1.25rem;
        padding: 0.5rem 0;
    }

    .botao-sair {
        background-color: #253542;
        width: 8.75rem;
        font-size: 1.25rem;
        padding: 0.5rem 0;
    }

    .barra-acoes {
        display: flex;
        justify-content: space-between;
        gap: 1.25rem;
        margin-top: 0.5rem;
        background: var(--caixas);
        border-radius: 0.5rem;
        box-shadow: 0 0.25rem 1rem var(--sombra-padrao);
        padding: 0.75rem 0.75rem 0.75rem 0.75rem;
        width: 30rem;
        max-width: 100%;
        box-sizing: border-box;
    }

    .escondido {
        display: none !important;
    }
</style>

<body>
    <div id="main-content">
        <div class="container-perfil">
            <div class="cartao-dados">
                <h1 class="titulo-cartao">Seus Dados</h1>
                <form id="form-perfil-organizador" name="perfil_organizador" method="post">
                    <div class="grupo-formulario">
                        <label for="name">Nome:</label>
                        <div id="name" class="controle-formulario"><?php echo htmlspecialchars($nome); ?></div>
                        <input type="text" id="name-input" class="controle-formulario-input escondido" value="<?php echo htmlspecialchars($nome); ?>" required>
                    </div>
                    <div class="grupo-formulario">
                        <label for="email">E-mail:</label>
                        <div id="email" class="controle-formulario"><?php echo htmlspecialchars($email); ?></div>
                        <input type="email" id="email-input" class="controle-formulario-input escondido" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    <div class="grupo-formulario">
                        <label for="cpf">CPF:</label>
                        <div id="cpf" class="controle-formulario"><?php echo htmlspecialchars($cpf_formatado); ?></div>
                        <input type="text" id="cpf-input" class="controle-formulario-input escondido" value="<?php echo htmlspecialchars($cpf_formatado); ?>" readonly>
                    </div>
                    <div class="grupo-formulario">
                        <label for="ra">RA (Opcional):</label>
                        <div id="ra" class="controle-formulario"><?php echo htmlspecialchars($ra); ?></div>
                        <input type="text" id="ra-input" class="controle-formulario-input escondido" value="<?php echo htmlspecialchars($ra); ?>" maxlength="7">
                    </div>
                    <div class="acoes-formulario">
                        <button type="button" class="botao botao-cancelar escondido" id="btn-cancelar">Cancelar</button>
                        <button type="submit" class="botao botao-confirmar escondido" id="btn-confirmar">Confirmar</button>
                        <button type="button" class="botao botao-editar" id="btn-editar">Editar</button>
                    </div>
                </form>
            </div>
            <div class="barra-acoes">
                <button type="button" class="botao botao-excluir" id="btn-excluir-conta">Excluir Conta</button>
                <button type="button" class="botao botao-sair" onclick="window.location.href='../PaginasPublicas/Logout.php'">Sair</button>
            </div>
        </div>
    </div>
    <script src="PerfilOrganizador.js"></script>
</body>

</html>