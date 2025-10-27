<?php
// Inicia a sessão apenas se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
$consultaSQL = "SELECT Nome, Email, CPF, RA, Codigo, Organizador, FotoPerfil FROM usuario WHERE CPF = ?";
$declaracaoPreparada = mysqli_prepare($conexao, $consultaSQL);
if ($declaracaoPreparada) {
    mysqli_stmt_bind_param($declaracaoPreparada, "s", $cpfUsuario);
    mysqli_stmt_execute($declaracaoPreparada);
    $resultadoConsulta = mysqli_stmt_get_result($declaracaoPreparada);
    $dadosUsuario = mysqli_fetch_assoc($resultadoConsulta);
    mysqli_stmt_close($declaracaoPreparada);
}

mysqli_close($conexao);

// Calcula o caminho base do site (ex: /CEU)
$siteRoot = rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/\\');
$defaultImg = $siteRoot . '/ImagensPerfis/FotodePerfil.webp';
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

    .avatar-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .5rem;
        margin-bottom: .75rem;
    }

    .avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, .3);
        background: #fff;
    }

    .input-foto {
        display: none;
    }

    .avatar-botoes {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        justify-content: center;
    }

    .btn-alterar-foto,
    .btn-remover-foto {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
        border: none;
        color: #fff;
        cursor: pointer;
        border-radius: 0.25rem;
        white-space: nowrap;
        box-sizing: border-box;
        text-align: center;
        width: 6.5rem;
        min-width: 6.5rem;
        max-width: 6.5rem;
    }

    .btn-alterar-foto {
        background: var(--botao);
    }

    .btn-remover-foto {
        background: var(--vermelho);
    }

    .btn-alterar-foto.hidden,
    .btn-remover-foto.hidden {
        display: none !important;
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
        background-color: var(--verde);
        width: 7.5rem;
    }

    .botao-cancelar {
        background-color: var(--vermelho);
        width: 7.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .botao-tornar-organizador {
        background-color: var(--botao);
        width: auto;
        padding: 0.5rem 1.5rem;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
    }

    .botao-excluir {
        background-color: var(--vermelho-escuro);
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

    /* Alertas específicos para o modal */
    .modal-codigo .alert {
        margin-bottom: 1rem;
        margin-top: 0.5rem;
        font-size: 0.9rem;
        text-align: center;
    }

    .modal-codigo .alert-success {
        color: #155724;
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .modal-codigo .alert-danger {
        color: #721c24;
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
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
        justify-content: space-between;
        gap: 0.75rem;
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
        background-color: var(--vermelho);
    }

    .modal-acoes .botao-salvar {
        background-color: var(--verde);
    }

    /* Força centralização do texto nos botões do modal */
    .modal-codigo .botao {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
    }

    /* Botão solicitar código */
    .modal-solicitar-codigo {
        display: flex;
        justify-content: center;
        margin-top: 0;
        margin-bottom: 1.5rem;
    }

    .modal-solicitar-codigo .botao {
        width: auto;
        padding: 0.5rem 1.5rem;
        font-size: 0.95rem;
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

    /* Ajuste final com maior especificidade para garantir prioridade sobre .botao */
    .botao.btn-alterar-foto,
    .botao.btn-remover-foto {
        padding: .25rem .5rem;
        font-size: .8rem;
        box-sizing: border-box;
        text-align: center;
        width: 6.5rem;
        min-width: 6.5rem;
        max-width: 6.5rem;
    }

    .botao.btn-alterar-foto {
        background: var(--botao);
    }

    .botao.btn-remover-foto {
        background: var(--vermelho);
    }
</style>

<body>
    <div id="main-content">
        <div class="container-perfil">
            <div id="alert-container"></div>

            <div class="cartao-dados">
                <h1 class="titulo-cartao">Seus Dados</h1>
                <form id="form-perfil-participante" name="perfil_participante" method="post"
                    enctype="multipart/form-data">
                    <div class="avatar-wrapper">
                        <?php
                        $fotoPerfil = $dadosUsuario['FotoPerfil'] ?? null;
                        if ($fotoPerfil && strpos($fotoPerfil, '/') === false) { $fotoPerfil = 'ImagensPerfis/' . $fotoPerfil; }
                        $caminhoFoto = $fotoPerfil ? ($siteRoot . '/' . htmlspecialchars($fotoPerfil)) : $defaultImg;
                        ?>
                        <img id="avatar-visualizacao" class="avatar" src="<?php echo $caminhoFoto; ?>"
                            alt="Foto de perfil"
                            data-default-src="<?php echo $defaultImg; ?>"
                            data-site-root="<?php echo $siteRoot; ?>"
                            data-tem-foto="<?php echo $fotoPerfil ? '1' : '0'; ?>">
                        <div class="avatar-botoes">
                            <button type="button" id="btn-remover-foto" class="botao btn-remover-foto hidden">Remover foto</button>
                            <button type="button" id="btn-alterar-foto" class="botao btn-alterar-foto hidden">Alterar foto</button>
                        </div>
                        <input id="foto-perfil-input" class="input-foto" type="file" name="foto_perfil"
                            accept="image/png,image/jpeg,image/webp,image/gif">
                        <input type="hidden" id="remover-foto-flag" name="remover_foto" value="false">
                    </div>

                    <div class="grupo-formulario">
                        <span class="label-formulario">Nome Completo:</span>
                        <div class="controle-formulario campo-nao-editavel"
                            data-tooltip="Para alterar este campo, entre em contato conosco através do Fale Conosco">
                            <span id="name-display">
                                <?php echo htmlspecialchars($dadosUsuario['Nome'] ?? 'Nome não encontrado'); ?>
                            </span>
                        </div>
                    </div>
                    <div class="grupo-formulario">
                        <label for="email-input">E-mail:</label>
                        <div class="controle-formulario">
                            <span id="email-display">
                                <?php echo htmlspecialchars($dadosUsuario['Email'] ?? 'Email não encontrado'); ?>
                            </span>
                            <input type="email" id="email-input" name="email"
                                value="<?php echo htmlspecialchars($dadosUsuario['Email'] ?? ''); ?>" class="hidden"
                                required autocomplete="email">
                        </div>
                    </div>
                    <div class="grupo-formulario">
                        <span class="label-formulario">CPF:</span>
                        <div class="controle-formulario campo-nao-editavel"
                            data-tooltip="Este campo não pode ser alterado">
                            <span id="cpf-display">
                                <?php 
                                $cpfFormatado = $dadosUsuario['CPF'] ?? '';
                                if($cpfFormatado) {
                                    echo substr($cpfFormatado, 0, 3) . '.' . substr($cpfFormatado, 3, 3) . '.' . substr($cpfFormatado, 6, 3) . '-' . substr($cpfFormatado, 9, 2);
                                } else {
                                    echo 'CPF não encontrado';
                                }
                            ?>
                            </span>
                        </div>
                    </div>

                    <?php if (!$dadosUsuario['Organizador']): ?>
                    <div class="grupo-formulario">
                        <label for="ra-input">RA (Registro Acadêmico):</label>
                        <div class="controle-formulario">
                            <span id="ra-display">
                                <?php echo htmlspecialchars($dadosUsuario['RA'] ?? 'Não informado'); ?>
                            </span>
                            <input type="text" id="ra-input" name="ra"
                                value="<?php echo htmlspecialchars($dadosUsuario['RA'] ?? ''); ?>" class="hidden"
                                maxlength="7" placeholder="Ex: 1234567" autocomplete="off">
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($dadosUsuario['Organizador']): ?>
                    <div class="grupo-formulario">
                        <span class="label-formulario">Código de Organizador:</span>
                        <div class="controle-formulario campo-nao-editavel"
                            data-tooltip="Para alterar este campo, entre em contato conosco através do Fale Conosco">
                            <span id="codigo-display">
                                <?php echo htmlspecialchars($dadosUsuario['Codigo'] ?? 'Código não encontrado'); ?>
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="grupo-formulario">
                        <span class="label-formulario">Tipo de Conta:</span>
                        <div class="controle-formulario campo-nao-editavel"
                            data-tooltip="Este campo não pode ser alterado">
                            <span id="tipo-conta-display">
                                <?php echo ($dadosUsuario['Organizador'] == 1) ? 'Organizador' : 'Participante'; ?>
                            </span>
                        </div>
                    </div>

                    <div class="acoes-formulario">
                        <?php if (!$dadosUsuario['Organizador']): ?>
                        <div class="linha-botao-organizador">
                            <button type="button" class="botao botao-tornar-organizador hidden"
                                id="btn-tornar-organizador">Deseja se tornar um organizador?</button>
                        </div>
                        <?php endif; ?>
                        <div class="linha-botoes-principais">
                            <button type="button" class="botao botao-editar" id="btn-editar">Editar</button>
                            <button type="button" class="botao botao-cancelar hidden"
                                id="btn-cancelar">Cancelar</button>
                            <button type="submit" class="botao botao-salvar hidden" id="btn-salvar">Salvar</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="barra-acoes">
                <button type="button" class="botao botao-excluir" id="btn-excluir-conta">Excluir Conta</button>
                <button type="button" class="botao botao-sair"
                    onclick="window.location.href='../PaginasPublicas/Logout.php'">Sair</button>
            </div>
        </div>
    </div>

    <!-- Modal para solicitar código de organizador -->
    <div id="modal-codigo" class="modal-codigo hidden">
        <div class="modal-content">
            <h2 class="modal-titulo">Tornar-se Organizador</h2>
            <p class="modal-texto">Para se tornar um organizador, você precisa de um código de acesso fornecido pela
                administração.</p>
            <div id="alert-modal"></div>
            <label for="input-codigo" class="sr-only">Código de organizador</label>
            <input type="text" id="input-codigo" name="codigo" class="modal-input"
                placeholder="Digite o código de organizador" maxlength="8" autocomplete="off">
            <div class="modal-solicitar-codigo">
                <button type="button" class="botao" id="btn-solicitar-codigo">Solicitar código de acesso</button>
            </div>
            <div class="modal-acoes">
                <button type="button" class="botao botao-cancelar" id="btn-cancelar-modal">Cancelar</button>
                <button type="button" class="botao botao-salvar" id="btn-confirmar-codigo">Confirmar</button>
            </div>
        </div>
    </div>

    <script src="../PaginasGlobais/VerificacaoSessao.js"></script>
    <script src="PerfilParticipante.js"></script>
</body>

</html>