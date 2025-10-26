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

// Define o cabeçalho para JSON
header('Content-Type: application/json');

// Verifica se o usuário está logado
if (!isset($_SESSION['cpf']) || !isset($_SESSION['organizador']) || $_SESSION['organizador'] != 1) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Usuário não autenticado ou não é organizador.'
    ]);
    exit;
}

// Inclui o arquivo de conexão
require_once('../BancoDados/conexao.php');

$cpf = $_SESSION['cpf'];

// Inicia uma transação
mysqli_begin_transaction($conexao);

try {
    // Remove registros relacionados primeiro (devido às chaves estrangeiras)
    // A ordem é CRÍTICA para evitar erros de constraint
    
    // 1. Remove notificações do usuário
    $sql_notif = "DELETE FROM notificacao WHERE CPF_usuario = ?";
    $stmt_notif = mysqli_prepare($conexao, $sql_notif);
    if ($stmt_notif) {
        mysqli_stmt_bind_param($stmt_notif, "s", $cpf);
        mysqli_stmt_execute($stmt_notif);
        mysqli_stmt_close($stmt_notif);
    }
    
    // 2. Remove da tabela de presença
    $sql_presenca = "DELETE FROM presenca WHERE CPF = ?";
    $stmt_presenca = mysqli_prepare($conexao, $sql_presenca);
    if ($stmt_presenca) {
        mysqli_stmt_bind_param($stmt_presenca, "s", $cpf);
        mysqli_stmt_execute($stmt_presenca);
        mysqli_stmt_close($stmt_presenca);
    }
    
    // 3. Remove da lista de espera
    $sql_espera = "DELETE FROM lista_de_espera WHERE CPF = ?";
    $stmt_espera = mysqli_prepare($conexao, $sql_espera);
    if ($stmt_espera) {
        mysqli_stmt_bind_param($stmt_espera, "s", $cpf);
        mysqli_stmt_execute($stmt_espera);
        mysqli_stmt_close($stmt_espera);
    }
    
    // 4. Remove da tabela lista_de_participantes
    $sql_participantes = "DELETE FROM lista_de_participantes WHERE CPF = ?";
    $stmt_participantes = mysqli_prepare($conexao, $sql_participantes);
    if ($stmt_participantes) {
        mysqli_stmt_bind_param($stmt_participantes, "s", $cpf);
        mysqli_stmt_execute($stmt_participantes);
        mysqli_stmt_close($stmt_participantes);
    }
    
    // 5. Remove da tabela organiza
    $sql_organiza = "DELETE FROM organiza WHERE CPF = ?";
    $stmt_organiza = mysqli_prepare($conexao, $sql_organiza);
    if ($stmt_organiza) {
        mysqli_stmt_bind_param($stmt_organiza, "s", $cpf);
        mysqli_stmt_execute($stmt_organiza);
        mysqli_stmt_close($stmt_organiza);
    }
    
    // 6. Remove eventos criados pelo organizador
    // Primeiro busca os IDs dos eventos para limpar tabelas relacionadas
    $sql_get_eventos = "SELECT ID_evento FROM evento WHERE CPF_organizador = ?";
    $stmt_get_eventos = mysqli_prepare($conexao, $sql_get_eventos);
    if ($stmt_get_eventos) {
        mysqli_stmt_bind_param($stmt_get_eventos, "s", $cpf);
        mysqli_stmt_execute($stmt_get_eventos);
        $result_eventos = mysqli_stmt_get_result($stmt_get_eventos);
        
        while ($row = mysqli_fetch_assoc($result_eventos)) {
            $id_evento = $row['ID_evento'];
            
            // Remove registros de presença do evento
            $sql_del_presenca_evt = "DELETE FROM presenca WHERE ID_evento = ?";
            $stmt_del_presenca = mysqli_prepare($conexao, $sql_del_presenca_evt);
            if ($stmt_del_presenca) {
                mysqli_stmt_bind_param($stmt_del_presenca, "i", $id_evento);
                mysqli_stmt_execute($stmt_del_presenca);
                mysqli_stmt_close($stmt_del_presenca);
            }
            
            // Remove lista de espera do evento
            $sql_del_espera_evt = "DELETE FROM lista_de_espera WHERE ID_evento = ?";
            $stmt_del_espera = mysqli_prepare($conexao, $sql_del_espera_evt);
            if ($stmt_del_espera) {
                mysqli_stmt_bind_param($stmt_del_espera, "i", $id_evento);
                mysqli_stmt_execute($stmt_del_espera);
                mysqli_stmt_close($stmt_del_espera);
            }
            
            // Remove participantes do evento
            $sql_del_part_evt = "DELETE FROM lista_de_participantes WHERE ID_evento = ?";
            $stmt_del_part = mysqli_prepare($conexao, $sql_del_part_evt);
            if ($stmt_del_part) {
                mysqli_stmt_bind_param($stmt_del_part, "i", $id_evento);
                mysqli_stmt_execute($stmt_del_part);
                mysqli_stmt_close($stmt_del_part);
            }
            
            // Remove da tabela organiza para este evento
            $sql_del_org_evt = "DELETE FROM organiza WHERE ID_evento = ?";
            $stmt_del_org = mysqli_prepare($conexao, $sql_del_org_evt);
            if ($stmt_del_org) {
                mysqli_stmt_bind_param($stmt_del_org, "i", $id_evento);
                mysqli_stmt_execute($stmt_del_org);
                mysqli_stmt_close($stmt_del_org);
            }
        }
        mysqli_stmt_close($stmt_get_eventos);
    }
    
    // Remove os eventos do organizador
    $sql_eventos = "DELETE FROM evento WHERE CPF_organizador = ?";
    $stmt_eventos = mysqli_prepare($conexao, $sql_eventos);
    if ($stmt_eventos) {
        mysqli_stmt_bind_param($stmt_eventos, "s", $cpf);
        mysqli_stmt_execute($stmt_eventos);
        mysqli_stmt_close($stmt_eventos);
    }

    // 7. Por último, exclui o usuário da tabela usuario
    $sql_usuario = "DELETE FROM usuario WHERE CPF = ?";
    $stmt_usuario = mysqli_prepare($conexao, $sql_usuario);
    mysqli_stmt_bind_param($stmt_usuario, "s", $cpf);
    mysqli_stmt_execute($stmt_usuario);
    
    if (mysqli_stmt_affected_rows($stmt_usuario) > 0) {
        // Confirma a transação
        mysqli_commit($conexao);
        mysqli_stmt_close($stmt_usuario);
        
        // Limpa a sessão
        $_SESSION = [];
        session_destroy();
        
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Conta excluída com sucesso.'
        ]);
    } else {
        // Reverte a transação
        mysqli_rollback($conexao);
        mysqli_stmt_close($stmt_usuario);
        
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Erro ao excluir a conta. Nenhuma conta foi encontrada.'
        ]);
    }
} catch (Exception $e) {
    // Reverte a transação em caso de erro
    mysqli_rollback($conexao);
    
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro ao excluir conta: ' . $e->getMessage()
    ]);
}

// Fecha a conexão
mysqli_close($conexao);
?>
