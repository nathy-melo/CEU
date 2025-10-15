<?php
// Carrega os dados do usuário da sessão do banco de dados
require_once '../BancoDados/conexao.php';

// Processa solicitação AJAX para código de organizador
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['codigo'])) {
    function responderJson($status, $mensagem) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'sucesso' => $status === 'sucesso',
            'mensagem' => $mensagem
        ]);
        exit;
    }

    // Verifica se o usuário está logado e não é organizador
    if (!isset($_SESSION['cpf']) || (isset($_SESSION['organizador']) && $_SESSION['organizador'] == 1)) {
        responderJson('erro', 'Acesso negado.');
    }

    $codigo = trim($_POST['codigo']);

    if (!$codigo) {
        responderJson('erro', '⚠️ Código é obrigatório.');
    }

    // Verifica se o código existe na tabela de códigos (sistema atualizado)
    require_once '../BancoDados/conexao.php';
    
    // Verificar se o código existe e está ativo
    $sql = "SELECT id, codigo, ativo, usado FROM codigos_organizador WHERE codigo = ? AND ativo = 1 AND usado = 0";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "s", $codigo);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        // Código é válido, pode promover o usuário
        $cpfUsuario = $_SESSION['cpf'];
        $codigoData = mysqli_fetch_assoc($result);
        
        // Marca o código como usado
        $sqlUpdate = "UPDATE codigos_organizador SET usado = 1, data_uso = NOW(), usado_por = ? WHERE id = ?";
        $stmtUpdate = mysqli_prepare($conexao, $sqlUpdate);
        mysqli_stmt_bind_param($stmtUpdate, "si", $cpfUsuario, $codigoData['id']);
        
        if (mysqli_stmt_execute($stmtUpdate)) {
            // Atualiza o usuário para organizador
            $sql = "UPDATE usuario SET Organizador = 1, Codigo = ? WHERE CPF = ?";
            $stmtUser = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param($stmtUser, "ss", $codigo, $cpfUsuario);
            
            if (mysqli_stmt_execute($stmtUser)) {
                // Atualiza a sessão
                $_SESSION['organizador'] = 1;
                $_SESSION['codigo'] = $codigo;
                
                mysqli_close($conexao);
                responderJson('sucesso', '✅ Parabéns! Você agora é um organizador.');
            } else {
                $erroBanco = mysqli_error($conexao);
                mysqli_close($conexao);
                responderJson('erro', '❌ Erro ao atualizar usuário: ' . $erroBanco);
            }
        } else {
            mysqli_close($conexao);
            responderJson('erro', '❌ Erro ao processar código.');
        }
    } else {
        mysqli_close($conexao);
        responderJson('erro', '⚠️ Código de acesso inválido ou já utilizado.');
    }
}

$cpfUsuario = $_SESSION['cpf'];
$dadosUsuario = null;

// Reconecta ao banco se a conexão foi fechada no processamento AJAX
if (!isset($conexao) || !$conexao) {
    require_once '../BancoDados/conexao.php';
}

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
        justify-content: center;
        gap: 0.75rem;
        flex-direction: column;
        align-items: center;
    }

    .linha-botoes-principais {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        width: 100%;
    }

    .linha-botao-organizador {
        display: flex;
        justify-content: center;
        width: 100%;
        margin-bottom: 0.5rem;
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

    .botao-tornar-organizador {
        background-color: var(--tema-site);
        width: auto;
        padding: 0.5rem 1.5rem;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
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

    /* Modal para solicitar código de organizador */
    .modal-codigo {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    .modal-content {
        background-color: var(--caixas);
        border-radius: 0.5rem;
        padding: 2rem;
        width: 90%;
        max-width: 400px;
        box-shadow: 0 0.5rem 2rem var(--sombra-padrao);
    }

    .modal-titulo {
        color: var(--branco);
        font-weight: 700;
        font-size: 1.5rem;
        text-align: center;
        margin-bottom: 1.5rem;
        text-shadow: 0 0.125rem 0.5rem var(--sombra-padrao);
    }

    .modal-texto {
        color: var(--branco);
        text-align: center;
        margin-bottom: 1.5rem;
        line-height: 1.5;
    }

    .modal-acoes {
        display: flex;
        gap: 0.75rem;
        justify-content: center;
        margin-top: 1.5rem;
    }

    .modal-acoes .botao {
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        width: 7.5rem;
    }

    .modal-acoes .botao-cancelar {
        background-color: #dc3545;
    }

    .modal-acoes .botao-salvar {
        background-color: #28a745;
    }

    /* Força centralização do texto nos botões do modal */
    .modal-codigo .botao {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
    }

    /* Centralização específica para o botão solicitar código */
    .modal-codigo #btn-solicitar-codigo {
        margin: 0 auto;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .modal-solicitar-codigo {
        display: flex;
        justify-content: center;
        margin-top: 1rem;
    }

    .modal-input {
        width: 100%;
        padding: 0.75rem;
        border: none;
        border-radius: 0.3125rem;
        font-size: 1rem;
        margin-bottom: 1rem;
        text-align: center;
        font-weight: 600;
        letter-spacing: 0.1rem;
    }

    .modal-input:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.3);
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
                        <?php if (!$dadosUsuario['Organizador']): ?>
                        <div class="linha-botao-organizador">
                            <button type="button" class="botao botao-tornar-organizador hidden" id="btn-tornar-organizador">Deseja se tornar um organizador?</button>
                        </div>
                        <?php endif; ?>
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

    <!-- Modal para solicitar código de organizador -->
    <div id="modal-codigo" class="modal-codigo hidden">
        <div class="modal-content">
            <h2 class="modal-titulo">Tornar-se Organizador</h2>
            <p class="modal-texto">Para se tornar um organizador, você precisa de um código de acesso fornecido pela administração.</p>
            <div id="alert-modal"></div>
            <input type="text" id="input-codigo" class="modal-input" placeholder="Digite o código de organizador" maxlength="8">
            <div class="modal-acoes">
                <button type="button" class="botao botao-cancelar" id="btn-cancelar-modal">Cancelar</button>
                <button type="button" class="botao botao-salvar" id="btn-confirmar-codigo">Confirmar</button>
            </div>
            <div class="modal-solicitar-codigo">
                <button type="button" class="botao" id="btn-solicitar-codigo" style="background-color: var(--tema-site); font-size: 0.9rem;">Solicitar código de acesso</button>
            </div>
        </div>
    </div>

    <script src="../PaginasGlobais/VerificacaoSessao.js"></script>
    <script src="PerfilParticipante.js"></script>
</body>

</html>