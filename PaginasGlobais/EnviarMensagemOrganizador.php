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
    $cpfOrganizadorDestino = trim($_POST['cpf_organizador_destino'] ?? ''); // CPF específico (opcional, para respostas)
    $ehResposta = isset($_POST['eh_resposta']) && $_POST['eh_resposta'] === '1';
    $mensagemOriginal = trim($_POST['mensagem_original'] ?? '');

    if ($codEvento <= 0 || $mensagem === '') {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Dados incompletos'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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

    // Se foi especificado um CPF de organizador destino (resposta), envia apenas para ele
    if (!empty($cpfOrganizadorDestino)) {
        // Verifica se o CPF é um organizador válido do evento
        $sqlVerificarOrg = "SELECT o.CPF FROM organiza o WHERE o.cod_evento = ? AND o.CPF = ? LIMIT 1";
        $stmtVerificarOrg = mysqli_prepare($conexao, $sqlVerificarOrg);
        mysqli_stmt_bind_param($stmtVerificarOrg, 'is', $codEvento, $cpfOrganizadorDestino);
        mysqli_stmt_execute($stmtVerificarOrg);
        $resVerificarOrg = mysqli_stmt_get_result($stmtVerificarOrg);
        
        if (mysqli_fetch_assoc($resVerificarOrg)) {
            $organizadores = [$cpfOrganizadorDestino];
        } else {
            mysqli_stmt_close($stmtVerificarOrg);
            mysqli_close($conexao);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Organizador não encontrado para este evento'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        mysqli_stmt_close($stmtVerificarOrg);
    } else {
        // Busca todos os organizadores do evento
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
            echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhum organizador encontrado para este evento'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            mysqli_close($conexao);
            exit;
        }
    }

    // Insere notificações para cada organizador
    $titulo = 'Mensagem de participante';
    
    // Armazena APENAS a mensagem atual (não inclui mensagens anteriores)
    // A thread completa será buscada quando necessário
    $mensagemCompleta = $mensagem;
    
    // Armazena dados de forma compacta
    // Formato: CPF|||NOME|||EVENTO|||MENSAGEM
    $nomeRemetente = $dadosRemetente['Nome'];
    $nomeEvento = $dadosEvento['nome'];
    
    // Calcula tamanho total considerando o formato CPF|||NOME|||EVENTO|||MENSAGEM
    $tamanhoBase = mb_strlen($cpfRemetente . '|||' . $nomeRemetente . '|||' . $nomeEvento . '|||');
    $tamanhoDisponivel = 255 - $tamanhoBase;
    
    // Se a mensagem for muito longa, trunca
    if (mb_strlen($mensagemCompleta) > $tamanhoDisponivel) {
        $mensagemCompleta = mb_substr($mensagemCompleta, 0, $tamanhoDisponivel);
    }
    
    // Usa um separador que não aparece no conteúdo normal
    $corpo = $cpfRemetente . '|||' . $nomeRemetente . '|||' . $nomeEvento . '|||' . $mensagemCompleta;

    $sqlNotif = "INSERT INTO notificacoes (CPF, titulo, tipo, mensagem, cod_evento, data_criacao, lida) VALUES (?, ?, 'mensagem_participante', ?, ?, NOW(), 0)";
    $stmtNotif = mysqli_prepare($conexao, $sqlNotif);

    $total = 0;
    foreach ($organizadores as $cpfOrg) {
        mysqli_stmt_bind_param($stmtNotif, 'sssi', $cpfOrg, $titulo, $corpo, $codEvento);
        if (mysqli_stmt_execute($stmtNotif)) {
            $total++;
        }
    }

    mysqli_stmt_close($stmtNotif);
    mysqli_close($conexao);

    if ($total > 0) {
        echo json_encode(['sucesso' => true, 'mensagem' => 'Mensagem enviada', 'total_destinatarios' => $total], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Falha ao enviar a mensagem'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro interno', 'detalhe' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

