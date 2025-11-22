<?php
// Busca todas as mensagens de uma thread/conversa
header('Content-Type: application/json; charset=utf-8');

session_start();

if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autenticado'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

require_once '../BancoDados/conexao.php';

if (!$conexao) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro de conexão'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$cpfUsuario = $_SESSION['cpf'];
$notificacaoId = isset($_GET['notificacao_id']) ? (int)$_GET['notificacao_id'] : 0;

if ($notificacaoId <= 0) {
    echo json_encode(['erro' => 'ID inválido'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Busca a notificação inicial
$sqlNotif = "SELECT id, tipo, mensagem, cod_evento, CPF as cpf_destinatario, data_criacao 
             FROM notificacoes 
             WHERE id = ? AND CPF = ? AND tipo = 'mensagem_participante'";
$stmt = mysqli_prepare($conexao, $sqlNotif);
mysqli_stmt_bind_param($stmt, 'is', $notificacaoId, $cpfUsuario);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$notifInicial = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$notifInicial) {
    echo json_encode(['erro' => 'Notificação não encontrada'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Extrai dados da mensagem inicial
$partes = explode('|||', $notifInicial['mensagem']);
$cpfRemetenteInicial = $partes[0] ?? '';
$codEvento = $notifInicial['cod_evento'];
$cpfDestinatarioInicial = $notifInicial['cpf_destinatario']; // CPF da notificação = destinatário

// Determina os dois participantes da conversa
// Participante 1: usuário atual
// Participante 2: o outro (pode ser o remetente ou destinatário da mensagem inicial)
// Se o remetente da mensagem inicial é o usuário atual, então o outro participante é o destinatário
// Se o remetente da mensagem inicial NÍO é o usuário atual, então o outro participante é o remetente
$ehMinhaMensagemInicial = ($cpfRemetenteInicial === $cpfUsuario);
$outroParticipante = $ehMinhaMensagemInicial ? $cpfDestinatarioInicial : $cpfRemetenteInicial;

// Busca todas as mensagens relacionadas (mesmo evento, conversa entre os mesmos dois usuários)
// A thread inclui todas as mensagens onde os dois participantes são o usuário atual e o outro participante
$sqlThread = "SELECT n1.id, n1.mensagem, n1.CPF as cpf_destinatario, n1.data_criacao,
                     SUBSTRING_INDEX(n1.mensagem, '|||', 1) as cpf_remetente_msg
              FROM notificacoes n1
              WHERE n1.cod_evento = ? 
              AND n1.tipo = 'mensagem_participante'
              AND (
                  -- Mensagens onde o usuário atual recebeu e o remetente é o outro participante
                  (n1.CPF = ? AND SUBSTRING_INDEX(n1.mensagem, '|||', 1) = ?)
                  OR
                  -- Mensagens onde o remetente é o usuário atual e o destinatário é o outro participante
                  (SUBSTRING_INDEX(n1.mensagem, '|||', 1) = ? AND n1.CPF = ?)
              )
              ORDER BY n1.data_criacao ASC";

$stmtThread = mysqli_prepare($conexao, $sqlThread);
mysqli_stmt_bind_param($stmtThread, 'issss', $codEvento, $cpfUsuario, $outroParticipante, $cpfUsuario, $outroParticipante);
mysqli_stmt_execute($stmtThread);
$resultThread = mysqli_stmt_get_result($stmtThread);

$thread = [];
while ($row = mysqli_fetch_assoc($resultThread)) {
    $partesMsg = explode('|||', $row['mensagem']);
    $cpfRemetenteMsg = $partesMsg[0] ?? '';
    $nomeRemetenteMsg = $partesMsg[1] ?? '';
    $nomeEventoMsg = $partesMsg[2] ?? '';
    $mensagemTexto = implode('|||', array_slice($partesMsg, 3));
    
    // Determina se a mensagem é do usuário atual
    // Se o CPF do remetente (extraído da mensagem) é o usuário atual, então a mensagem é do usuário
    $ehMinha = ($cpfRemetenteMsg === $cpfUsuario);
    
    $thread[] = [
        'id' => $row['id'],
        'cpf_remetente' => $cpfRemetenteMsg,
        'nome_remetente' => $nomeRemetenteMsg,
        'nome_evento' => $nomeEventoMsg,
        'mensagem' => $mensagemTexto,
        'cpf_destinatario' => $row['cpf_destinatario'],
        'data_criacao' => $row['data_criacao'],
        'eh_minha' => $ehMinha
    ];
}
mysqli_stmt_close($stmtThread);
mysqli_close($conexao);

echo json_encode([
    'sucesso' => true,
    'thread' => $thread
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

