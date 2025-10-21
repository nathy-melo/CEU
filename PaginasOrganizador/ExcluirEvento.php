<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Verifica se o usuário está logado
if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
    echo json_encode(['erro' => 'Usuário não autenticado']);
    exit;
}

// Verifica se é organizador
if (!isset($_SESSION['organizador']) || $_SESSION['organizador'] != 1) {
    echo json_encode(['erro' => 'Usuário não é organizador']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

require_once('../BancoDados/conexao.php');

$cpfOrganizadorLogado = $_SESSION['cpf'];

// Recebe o código do evento a ser excluído
$codigoEventoParaExcluir = isset($_POST['cod_evento']) ? intval($_POST['cod_evento']) : 0;

if ($codigoEventoParaExcluir <= 0) {
    echo json_encode(['erro' => 'Código do evento inválido']);
    exit;
}

// Verifica se o organizador tem permissão para excluir este evento
$consultaVerificaPermissao = "SELECT COUNT(*) as total FROM organiza WHERE cod_evento = ? AND CPF = ?";
$declaracaoVerificaPermissao = mysqli_prepare($conexao, $consultaVerificaPermissao);
mysqli_stmt_bind_param($declaracaoVerificaPermissao, "is", $codigoEventoParaExcluir, $cpfOrganizadorLogado);
mysqli_stmt_execute($declaracaoVerificaPermissao);
$resultadoVerificacao = mysqli_stmt_get_result($declaracaoVerificaPermissao);
$linhaVerificacao = mysqli_fetch_assoc($resultadoVerificacao);
mysqli_stmt_close($declaracaoVerificaPermissao);

if ($linhaVerificacao['total'] == 0) {
    echo json_encode(['erro' => 'Você não tem permissão para excluir este evento']);
    mysqli_close($conexao);
    exit;
}

// Busca informações da imagem antes de excluir para remover do servidor
$consultaBuscaImagem = "SELECT imagem FROM evento WHERE cod_evento = ?";
$declaracaoBuscaImagem = mysqli_prepare($conexao, $consultaBuscaImagem);
mysqli_stmt_bind_param($declaracaoBuscaImagem, "i", $codigoEventoParaExcluir);
mysqli_stmt_execute($declaracaoBuscaImagem);
$resultadoBuscaImagem = mysqli_stmt_get_result($declaracaoBuscaImagem);
$linhaImagemEvento = mysqli_fetch_assoc($resultadoBuscaImagem);
mysqli_stmt_close($declaracaoBuscaImagem);

// Inicia transação para garantir integridade dos dados
mysqli_begin_transaction($conexao);

try {
    // Exclui da tabela organiza primeiro (devido à chave estrangeira)
    $consultaExcluirOrganiza = "DELETE FROM organiza WHERE cod_evento = ?";
    $declaracaoExcluirOrganiza = mysqli_prepare($conexao, $consultaExcluirOrganiza);
    mysqli_stmt_bind_param($declaracaoExcluirOrganiza, "i", $codigoEventoParaExcluir);

    if (!mysqli_stmt_execute($declaracaoExcluirOrganiza)) {
        throw new Exception('Erro ao excluir vínculo organizador-evento: ' . mysqli_error($conexao));
    }
    mysqli_stmt_close($declaracaoExcluirOrganiza);

    // Exclui o evento da tabela evento
    $consultaExcluirEvento = "DELETE FROM evento WHERE cod_evento = ?";
    $declaracaoExcluirEvento = mysqli_prepare($conexao, $consultaExcluirEvento);
    mysqli_stmt_bind_param($declaracaoExcluirEvento, "i", $codigoEventoParaExcluir);

    if (!mysqli_stmt_execute($declaracaoExcluirEvento)) {
        throw new Exception('Erro ao excluir evento: ' . mysqli_error($conexao));
    }
    mysqli_stmt_close($declaracaoExcluirEvento);

    // Confirma a transação (commit)
    mysqli_commit($conexao);
    mysqli_close($conexao);

    // Remove a imagem do servidor se existir
    if ($linhaImagemEvento && !empty($linhaImagemEvento['imagem'])) {
        $caminhoArquivoImagem = '../' . $linhaImagemEvento['imagem'];
        if (file_exists($caminhoArquivoImagem)) {
            @unlink($caminhoArquivoImagem);
        }
    }

    echo json_encode(['sucesso' => true, 'mensagem' => 'Evento excluído com sucesso!']);
} catch (Exception $excecao) {
    // Desfaz alterações em caso de erro (rollback)
    mysqli_rollback($conexao);
    mysqli_close($conexao);

    echo json_encode(['erro' => $excecao->getMessage()]);
}
