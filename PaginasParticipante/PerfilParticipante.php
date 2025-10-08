<?php
// Carrega os dados do usuário da sessão do banco de dados
require_once '../BancoDados/conexao.php';

$cpfUsuario = $_SESSION['cpf'];
$dadosUsuario = null;

// Busca todos os dados do usuário no banco
$consultaSQL = "SELECT Nome, Email, CPF, RA, Codigo, Organizador FROM usuario WHERE CPF = ?";
$declaracaoPreparada = mysqli_prepare($conexao, $consultaSQL);
if ($declaracaoPreparada) {
    mysqli_stmt_bind_param($declaracaoPreparada, "s", $cpfUsuario);
    mysqli_stmt_execute($declaracaoPreparada);
    $resultadoConsulta = mysqli_stmt_get_result($declaracaoPreparada);
    $dadosUsuario = mysqli_fetch_assoc($resultadoConsulta);
    mysqli_stmt_close($declaracaoPreparada);
}

mysqli_close($conexao);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
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
    
    .controle-formulario.cpf-readonly {
        background-color: var(--cinza-claro);
        opacity: 0.7;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        padding: 0.5rem;
    }
    
    .controle-formulario small {
        margin-top: 0.25rem;
        font-size: 0.875rem;
    }

    .modo-edicao .controle-formulario {
        background-color: #f0f0f0;
    }

    .acoes-formulario {
        margin-top: 1.25rem;
        display: flex;
        justify-content: flex-end;
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

    .hidden {
        display: none !important;
    }

    .alert {
        padding: 0.75rem 1rem;
        margin-bottom: 1rem;
        border-radius: 0.25rem;
        font-weight: 600;
    }

    .alert-success {
        color: #155724;
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
    }

    .alert-danger {
        color: #721c24;
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
    }
</style>

<body>
    <div id="main-content">
        <div class="container-perfil">
            <div id="alert-container"></div>
            
            <div class="cartao-dados">
                <h1 class="titulo-cartao">Seus Dados</h1>
                <form id="form-perfil-participante" name="perfil_participante" method="post">
                    <div class="grupo-formulario">
                        <label for="name">Nome Completo:</label>
                        <div class="controle-formulario campo-nao-editavel" data-tooltip="Para alterar este campo, entre em contato conosco através do Fale Conosco">
                            <span id="name-display"><?php echo htmlspecialchars($dadosUsuario['Nome'] ?? 'Nome não encontrado'); ?></span>
                        </div>
                    </div>
                    <div class="grupo-formulario">
                        <label for="email">E-mail:</label>
                        <div class="controle-formulario">
                            <span id="email-display"><?php echo htmlspecialchars($dadosUsuario['Email'] ?? 'Email não encontrado'); ?></span>
                            <input type="email" id="email-input" name="email" value="<?php echo htmlspecialchars($dadosUsuario['Email'] ?? ''); ?>" class="hidden" required>
                        </div>
                    </div>
                    <div class="grupo-formulario">
                        <label for="cpf">CPF:</label>
                        <div class="controle-formulario campo-nao-editavel" data-tooltip="Este campo não pode ser alterado">
                            <span id="cpf-display"><?php 
                                $cpfFormatado = $dadosUsuario['CPF'] ?? '';
                                if($cpfFormatado) {
                                    echo substr($cpfFormatado, 0, 3) . '.' . substr($cpfFormatado, 3, 3) . '.' . substr($cpfFormatado, 6, 3) . '-' . substr($cpfFormatado, 9, 2);
                                } else {
                                    echo 'CPF não encontrado';
                                }
                            ?></span>
                        </div>
                    </div>
                    
                    <?php if (!$dadosUsuario['Organizador']): ?>
                    <div class="grupo-formulario">
                        <label for="ra">RA (Registro Acadêmico):</label>
                        <div class="controle-formulario">
                            <span id="ra-display"><?php echo htmlspecialchars($dadosUsuario['RA'] ?? 'Não informado'); ?></span>
                            <input type="text" id="ra-input" name="ra" value="<?php echo htmlspecialchars($dadosUsuario['RA'] ?? ''); ?>" class="hidden" maxlength="7" placeholder="Ex: 1234567">
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($dadosUsuario['Organizador']): ?>
                    <div class="grupo-formulario">
                        <label for="codigo">Código de Organizador:</label>
                        <div class="controle-formulario campo-nao-editavel" data-tooltip="Para alterar este campo, entre em contato conosco através do Fale Conosco">
                            <span id="codigo-display"><?php echo htmlspecialchars($dadosUsuario['Codigo'] ?? 'Código não encontrado'); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="grupo-formulario">
                        <label for="tipo-conta">Tipo de Conta:</label>
                        <div class="controle-formulario campo-nao-editavel" data-tooltip="Este campo não pode ser alterado">
                            <span id="tipo-conta-display"><?php echo ($dadosUsuario['Organizador'] == 1) ? 'Organizador' : 'Participante'; ?></span>
                        </div>
                    </div>
                    
                    <div class="acoes-formulario">
                        <button type="button" class="botao botao-editar" id="btn-editar">Editar</button>
                        <button type="button" class="botao botao-cancelar hidden" id="btn-cancelar">Cancelar</button>
                        <button type="submit" class="botao botao-salvar hidden" id="btn-salvar">Salvar</button>
                    </div>
                </form>
            </div>
            <div class="barra-acoes">
                <button type="button" class="botao botao-excluir" id="btn-excluir-conta">Excluir Conta</button>
                <button type="button" class="botao botao-sair" onclick="window.location.href='../PaginasPublicas/Logout.php'">Sair</button>
            </div>
        </div>
    </div>
    <script src="../PaginasGlobais/VerificacaoSessao.js"></script>
    <script src="PerfilParticipante.js"></script>
</body>

</html>