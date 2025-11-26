<?php
session_start();
header('Content-Type: application/json');

// Verifica se usuário está logado
if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado']);
    exit;
}

include_once '../BancoDados/conexao.php';

// Se for organizador, apenas impede inscrição caso seja organizador deste evento específico
if (isset($_SESSION['organizador']) && $_SESSION['organizador'] == 1) {
    $cod_evento_teste = isset($_POST['cod_evento']) ? (int)$_POST['cod_evento'] : 0;
    if ($cod_evento_teste > 0) {
        $sql_org = "SELECT 1 FROM organiza WHERE CPF = ? AND cod_evento = ? LIMIT 1";
        $stmt_org = mysqli_prepare($conexao, $sql_org);
        if ($stmt_org) {
            mysqli_stmt_bind_param($stmt_org, 'si', $_SESSION['cpf'], $cod_evento_teste);
            mysqli_stmt_execute($stmt_org);
            $res_org = mysqli_stmt_get_result($stmt_org);
            if ($res_org && mysqli_num_rows($res_org) > 0) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Você já é organizador deste evento']);
                mysqli_stmt_close($stmt_org);
                exit;
            }
            mysqli_stmt_close($stmt_org);
        }
    }
}

$cpf_usuario = $_SESSION['cpf'];
$cod_evento = isset($_POST['cod_evento']) ? (int)$_POST['cod_evento'] : 0;

if ($cod_evento <= 0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Código do evento inválido']);
    exit;
}

// Verifica se já está inscrito
$sql_verifica = "SELECT status FROM inscricao WHERE CPF = ? AND cod_evento = ?";
$stmt_verifica = mysqli_prepare($conexao, $sql_verifica);
mysqli_stmt_bind_param($stmt_verifica, 'si', $cpf_usuario, $cod_evento);
mysqli_stmt_execute($stmt_verifica);
$resultado_verifica = mysqli_stmt_get_result($stmt_verifica);

if ($resultado_verifica && mysqli_num_rows($resultado_verifica) > 0) {
    $inscricao = mysqli_fetch_assoc($resultado_verifica);
    
    if ($inscricao['status'] === 'ativa') {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Você já está inscrito neste evento']);
        exit;
    } else {
        // Reativar inscrição cancelada
        $sql_reativar = "UPDATE inscricao SET status = 'ativa', data_inscricao = NOW() WHERE CPF = ? AND cod_evento = ?";
        $stmt_reativar = mysqli_prepare($conexao, $sql_reativar);
        mysqli_stmt_bind_param($stmt_reativar, 'si', $cpf_usuario, $cod_evento);
        
        if (mysqli_stmt_execute($stmt_reativar)) {
            // Buscar dados do participante e evento para notificação
            $sql_usuario = "SELECT Nome FROM usuario WHERE CPF = ? LIMIT 1";
            $stmt_usuario = mysqli_prepare($conexao, $sql_usuario);
            mysqli_stmt_bind_param($stmt_usuario, 's', $cpf_usuario);
            mysqli_stmt_execute($stmt_usuario);
            $res_usuario = mysqli_stmt_get_result($stmt_usuario);
            $usuario = mysqli_fetch_assoc($res_usuario);
            $nomeParticipante = $usuario ? $usuario['Nome'] : 'Participante';
            mysqli_stmt_close($stmt_usuario);
            
            // Buscar dados do evento
            $sql_evento = "SELECT nome FROM evento WHERE cod_evento = ? LIMIT 1";
            $stmt_evento = mysqli_prepare($conexao, $sql_evento);
            mysqli_stmt_bind_param($stmt_evento, 'i', $cod_evento);
            mysqli_stmt_execute($stmt_evento);
            $res_evento = mysqli_stmt_get_result($stmt_evento);
            $evento = mysqli_fetch_assoc($res_evento);
            $nomeEvento = $evento ? $evento['nome'] : 'Evento';
            mysqli_stmt_close($stmt_evento);
            
            // Buscar organizadores do evento
            $sql_org = "SELECT CPF FROM organiza WHERE cod_evento = ? LIMIT 10";
            $stmt_org = mysqli_prepare($conexao, $sql_org);
            mysqli_stmt_bind_param($stmt_org, 'i', $cod_evento);
            mysqli_stmt_execute($stmt_org);
            $res_org = mysqli_stmt_get_result($stmt_org);
            
            // Enviar notificação para cada organizador
            while ($org = mysqli_fetch_assoc($res_org)) {
                $cpfOrganizador = $org['CPF'];
                $titulo = 'Novo participante inscrito';
                $mensagem = "{$nomeParticipante} se inscreveu no evento \"{$nomeEvento}\"";
                $tipo = 'novo_participante';
                
                $sql_notif = "INSERT INTO notificacoes (CPF, titulo, tipo, mensagem, cod_evento, data_criacao, lida) VALUES (?, ?, ?, ?, ?, NOW(), 0)";
                $stmt_notif = mysqli_prepare($conexao, $sql_notif);
                mysqli_stmt_bind_param($stmt_notif, 'ssssi', $cpfOrganizador, $titulo, $tipo, $mensagem, $cod_evento);
                mysqli_stmt_execute($stmt_notif);
                mysqli_stmt_close($stmt_notif);
            }
            mysqli_stmt_close($stmt_org);
            
            echo json_encode(['sucesso' => true, 'mensagem' => 'Inscrição reativada com sucesso!']);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao reativar inscrição']);
        }
        mysqli_stmt_close($stmt_reativar);
        exit;
    }
}

// Inserir nova inscrição
$sql_inscricao = "INSERT INTO inscricao (CPF, cod_evento, status) VALUES (?, ?, 'ativa')";
$stmt_inscricao = mysqli_prepare($conexao, $sql_inscricao);
mysqli_stmt_bind_param($stmt_inscricao, 'si', $cpf_usuario, $cod_evento);

if (mysqli_stmt_execute($stmt_inscricao)) {
    // Buscar dados do participante e evento para notificação
    $sql_usuario = "SELECT Nome FROM usuario WHERE CPF = ? LIMIT 1";
    $stmt_usuario = mysqli_prepare($conexao, $sql_usuario);
    mysqli_stmt_bind_param($stmt_usuario, 's', $cpf_usuario);
    mysqli_stmt_execute($stmt_usuario);
    $res_usuario = mysqli_stmt_get_result($stmt_usuario);
    $usuario = mysqli_fetch_assoc($res_usuario);
    $nomeParticipante = $usuario ? $usuario['Nome'] : 'Participante';
    mysqli_stmt_close($stmt_usuario);
    
    // Buscar dados do evento
    $sql_evento = "SELECT nome FROM evento WHERE cod_evento = ? LIMIT 1";
    $stmt_evento = mysqli_prepare($conexao, $sql_evento);
    mysqli_stmt_bind_param($stmt_evento, 'i', $cod_evento);
    mysqli_stmt_execute($stmt_evento);
    $res_evento = mysqli_stmt_get_result($stmt_evento);
    $evento = mysqli_fetch_assoc($res_evento);
    $nomeEvento = $evento ? $evento['nome'] : 'Evento';
    mysqli_stmt_close($stmt_evento);
    
    // Buscar organizadores do evento
    $sql_org = "SELECT CPF FROM organiza WHERE cod_evento = ? LIMIT 10";
    $stmt_org = mysqli_prepare($conexao, $sql_org);
    mysqli_stmt_bind_param($stmt_org, 'i', $cod_evento);
    mysqli_stmt_execute($stmt_org);
    $res_org = mysqli_stmt_get_result($stmt_org);
    
    // Enviar notificação para cada organizador
    while ($org = mysqli_fetch_assoc($res_org)) {
        $cpfOrganizador = $org['CPF'];
        $titulo = 'Novo participante inscrito';
        $mensagem = "{$nomeParticipante} se inscreveu no evento \"{$nomeEvento}\"";
        $tipo = 'novo_participante';
        
        $sql_notif = "INSERT INTO notificacoes (CPF, titulo, tipo, mensagem, cod_evento, data_criacao, lida) VALUES (?, ?, ?, ?, ?, NOW(), 0)";
        $stmt_notif = mysqli_prepare($conexao, $sql_notif);
        mysqli_stmt_bind_param($stmt_notif, 'ssssi', $cpfOrganizador, $titulo, $tipo, $mensagem, $cod_evento);
        mysqli_stmt_execute($stmt_notif);
        mysqli_stmt_close($stmt_notif);
    }
    mysqli_stmt_close($stmt_org);
    
    echo json_encode(['sucesso' => true, 'mensagem' => 'Inscrição realizada com sucesso!']);
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao realizar inscrição: ' . mysqli_error($conexao)]);
}

mysqli_stmt_close($stmt_inscricao);
mysqli_stmt_close($stmt_verifica);
mysqli_close($conexao);
?>
