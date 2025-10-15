<?php
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

// Verifica se o usuário está logado
if (!isset($_SESSION['cpf']) || !isset($_SESSION['organizador']) || $_SESSION['organizador'] != 1) {
    header('Location: ../PaginasPublicas/ContainerPublico.php?pagina=login&erro=login_requerido');
    exit;
}

// Inclui o arquivo de conexão
require_once('../BancoDados/conexao.php');

$cpfUsuario = $_SESSION['cpf'];

// Busca todos os dados do usuário no banco
$consultaSQL = "SELECT Nome, Email, CPF, RA, Codigo, Organizador FROM usuario WHERE CPF = ?";
$declaracaoPreparada = mysqli_prepare($conexao, $consultaSQL);
if ($declaracaoPreparada) {
    mysqli_stmt_bind_param($declaracaoPreparada, "s", $cpfUsuario);
    mysqli_stmt_execute($declaracaoPreparada);
    $resultadoConsulta = mysqli_stmt_get_result($declaracaoPreparada);
    $dadosUsuario = mysqli_fetch_assoc($resultadoConsulta);
    mysqli_stmt_close($declaracaoPreparada);
} else {
    // Se não conseguir buscar os dados, redireciona para o login
    header('Location: ../PaginasPublicas/ContainerPublico.php?pagina=login&erro=usuario_nao_encontrado');
    exit;
}

// Verifica se o usuário foi encontrado
if (!$dadosUsuario) {
    header('Location: ../PaginasPublicas/ContainerPublico.php?pagina=login&erro=usuario_nao_encontrado');
    exit;
}

// Formata o CPF para exibição (XXX.XXX.XXX-XX)
$cpf_formatado = $dadosUsuario['CPF'] ?? '';
if (strlen($cpf_formatado) == 11) {
    $cpf_formatado = substr($cpf_formatado, 0, 3) . '.' . 
                    substr($cpf_formatado, 3, 3) . '.' . 
                    substr($cpf_formatado, 6, 3) . '-' . 
                    substr($cpf_formatado, 9, 2);
}

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

    .controle-formulario input,
    .controle-formulario select {
        background: transparent;
        border: none;
        outline: none;
        width: 100%;
        font-size: 1rem;
        color: var(--cinza-escuro);
    }
    
    .controle-formulario.campo-nao-editavel {
        cursor: help;
        position: relative;
    }
    
    .controle-formulario.campo-nao-editavel:hover {
        background-color: #f8f9fa;
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
        background-color: #dc3545;
        width: 8.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .escondido {
        display: none !important;
    }

    .hidden {
        display: none !important;
    }

    .label-formulario {
        display: block;
        color: var(--branco);
        font-weight: 700;
        font-size: 1rem;
        margin-bottom: 0.25rem;
        letter-spacing: 0;
        font-family: 'Inter', sans-serif;
    }

    .controle-formulario.campo-nao-editavel {
        cursor: help;
        position: relative;
    }
    
    .controle-formulario.campo-nao-editavel:hover {
        background-color: #f8f9fa;
    }
    
    /* Tooltip customizado */
    .tooltip-custom {
        position: fixed;
        background-color: rgba(0, 0, 0, 0.9);
        color: white;
        padding: 0.5rem 0.75rem;
        border-radius: 0.25rem;
        font-size: 0.875rem;
        max-width: 200px;
        z-index: 1000;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.2s ease;
        white-space: normal;
        word-wrap: break-word;
    }

    .tooltip-custom.show {
        opacity: 1;
    }

    .hidden {
        display: none !important;
    }

    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        border: 0;
    }

    .label-formulario {
        display: block;
        color: var(--branco);
        font-weight: 700;
        font-size: 1rem;
        margin-bottom: 0.25rem;
        letter-spacing: 0;
    }

    .botao {
        border: none;
        border-radius: 0.25rem;
        font-weight: 700;
        font-size: 1.125rem;
        cursor: pointer;
        padding: 0.5rem 2rem;
        text-align: center;
        transition: opacity 0.2s ease;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.15);
        background-color: var(--botao);
        color: var(--branco);
        font-family: 'Inter', sans-serif;
        text-decoration: none;
        white-space: nowrap;
    }

    .botao:hover {
        opacity: 0.9;
    }

    .botao-editar {
        background-color: var(--botao);
        width: 7.5rem;
    }

    .botao-salvar {
        background-color: #28a745;
        width: 7.5rem;
    }

    .botao-cancelar {
        background-color: #dc3545;
        width: 7.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .botao-excluir {
        background-color: #dc3545;
        width: 10rem;
        font-size: 1.25rem;
        padding: 0.5rem 0;
    }

    .botao-sair {
        background-color: var(--botao);
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

    .linha-botoes-principais {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        width: 100%;
    }

    .alert {
        padding: 0.75rem 1rem;
        margin-bottom: 1rem;
        border-radius: 0.25rem;
        font-weight: 600;
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
</style>

<body>
    <div id="main-content">
        <div class="container-perfil">
            <div id="alert-container"></div>
            
            <div class="cartao-dados">
                <h1 class="titulo-cartao">Seus Dados</h1>
                <form id="form-perfil-organizador" name="perfil_organizador" method="post">
                    <div class="grupo-formulario">
                        <span class="label-formulario">Nome Completo:</span>
                        <div class="controle-formulario campo-nao-editavel" data-tooltip="Para alterar este campo, entre em contato conosco através do Fale Conosco">
                            <span id="name-display"><?php echo htmlspecialchars($dadosUsuario['Nome'] ?? 'Nome não encontrado'); ?></span>
                        </div>
                    </div>
                    <div class="grupo-formulario">
                        <label for="email-input">E-mail:</label>
                        <div class="controle-formulario">
                            <span id="email-display"><?php echo htmlspecialchars($dadosUsuario['Email'] ?? 'Email não encontrado'); ?></span>
                            <input type="email" id="email-input" name="email" value="<?php echo htmlspecialchars($dadosUsuario['Email'] ?? ''); ?>" class="hidden" required autocomplete="email">
                        </div>
                    </div>
                    <div class="grupo-formulario">
                        <span class="label-formulario">CPF:</span>
                        <div class="controle-formulario campo-nao-editavel" data-tooltip="Este campo não pode ser alterado">
                            <span id="cpf-display"><?php echo htmlspecialchars($cpf_formatado); ?></span>
                        </div>
                    </div>
                    <div class="grupo-formulario">
                        <label for="ra-input">RA (Registro Acadêmico):</label>
                        <div class="controle-formulario">
                            <span id="ra-display"><?php echo htmlspecialchars($dadosUsuario['RA'] ?? 'Não informado'); ?></span>
                            <input type="text" id="ra-input" name="ra" value="<?php echo htmlspecialchars($dadosUsuario['RA'] ?? ''); ?>" class="hidden" maxlength="7" placeholder="Ex: 1234567" autocomplete="off">
                        </div>
                    </div>
                    <div class="grupo-formulario">
                        <span class="label-formulario">Código de Organizador:</span>
                        <div class="controle-formulario campo-nao-editavel" data-tooltip="Este campo não pode ser alterado">
                            <span id="codigo-display"><?php echo htmlspecialchars($dadosUsuario['Codigo'] ?? 'Código não encontrado'); ?></span>
                        </div>
                    </div>
                    <div class="grupo-formulario">
                        <span class="label-formulario">Tipo de Conta:</span>
                        <div class="controle-formulario campo-nao-editavel" data-tooltip="Este campo não pode ser alterado">
                            <span id="tipo-conta-display">Organizador</span>
                        </div>
                    </div>
                    
                    <div class="acoes-formulario">
                        <div class="linha-botoes-principais">
                            <button type="button" class="botao botao-editar" id="btn-editar">Editar</button>
                            <button type="button" class="botao botao-cancelar hidden" id="btn-cancelar">Cancelar</button>
                            <button type="submit" class="botao botao-salvar hidden" id="btn-salvar">Salvar</button>
                        </div>
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