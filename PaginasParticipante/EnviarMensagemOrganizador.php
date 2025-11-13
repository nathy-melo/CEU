<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
        http_response_code(401);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Método inválido']);
        exit;
    }

    $codEvento = isset($_POST['cod_evento']) ? (int)$_POST['cod_evento'] : 0;
    $mensagem = trim($_POST['mensagem'] ?? '');

    if ($codEvento <= 0 || $mensagem === '') {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Dados incompletos']);
        exit;
    }

    if (mb_strlen($mensagem) > 500) {
        $mensagem = mb_substr($mensagem, 0, 500);
    }

    require_once __DIR__ . '/../BancoDados/conexao.php';

    $cpfRemetente = $_SESSION['cpf'];

    // Busca dados do remetente
    $sqlUser = "SELECT Nome, Email FROM usuario WHERE CPF = ?";
    $stmtUser = mysqli_prepare($conexao, $sqlUser);
    mysqli_stmt_bind_param($stmtUser, 's', $cpfRemetente);
    mysqli_stmt_execute($stmtUser);
    $resUser = mysqli_stmt_get_result($stmtUser);
    $dadosRemetente = mysqli_fetch_assoc($resUser) ?: ['Nome' => 'Participante', 'Email' => ''];
    mysqli_stmt_close($stmtUser);

    // Busca nome do evento
    $sqlEvento = "SELECT nome FROM evento WHERE cod_evento = ?";
    $stmtEvento = mysqli_prepare($conexao, $sqlEvento);
    mysqli_stmt_bind_param($stmtEvento, 'i', $codEvento);
    mysqli_stmt_execute($stmtEvento);
    $resEvento = mysqli_stmt_get_result($stmtEvento);
    $dadosEvento = mysqli_fetch_assoc($resEvento) ?: ['nome' => 'Evento'];
    mysqli_stmt_close($stmtEvento);

    // Busca organizadores do evento
    $sqlOrg = "SELECT o.CPF, u.Nome as nome_org FROM organiza o INNER JOIN usuario u ON u.CPF = o.CPF WHERE o.cod_evento = ?";
    $stmtOrg = mysqli_prepare($conexao, $sqlOrg);
    mysqli_stmt_bind_param($stmtOrg, 'i', $codEvento);
    mysqli_stmt_execute($stmtOrg);
    $resOrg = mysqli_stmt_get_result($stmtOrg);

    $organizadores = [];
    while ($row = mysqli_fetch_assoc($resOrg)) {
        $organizadores[] = $row['CPF'];
    }
    mysqli_stmt_close($stmtOrg);

    if (empty($organizadores)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhum organizador encontrado para este evento']);
        mysqli_close($conexao);
        exit;
    }

    // Insere notificações para cada organizador
    $titulo = 'Mensagem de participante';
    $corpo = "Remetente: {$dadosRemetente['Nome']} (CPF: {$cpfRemetente})\n" .
             "Evento: {$dadosEvento['nome']} (Código: {$codEvento})\n\n" .
             "Mensagem:\n{$mensagem}\n\n" .
             "Responder: utilize o Gerenciar Evento para enviar uma mensagem ao CPF informado.";

    $sqlNotif = "INSERT INTO notificacoes (CPF, titulo, mensagem, data_criacao, lida) VALUES (?, ?, ?, NOW(), 0)";
    $stmtNotif = mysqli_prepare($conexao, $sqlNotif);

    $total = 0;
    foreach ($organizadores as $cpfOrg) {
        mysqli_stmt_bind_param($stmtNotif, 'sss', $cpfOrg, $titulo, $corpo);
        if (mysqli_stmt_execute($stmtNotif)) {
            $total++;
        }
    }

    mysqli_stmt_close($stmtNotif);
    mysqli_close($conexao);

    if ($total > 0) {
        echo json_encode(['sucesso' => true, 'mensagem' => 'Mensagem enviada', 'total_destinatarios' => $total]);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Falha ao enviar a mensagem']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro interno', 'detalhe' => $e->getMessage()]);
}
