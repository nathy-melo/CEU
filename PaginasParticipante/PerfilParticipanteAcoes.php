<?php
// Configuração do tempo de sessão para 6 minutos (5min de inatividade + 1min de extensão)
ini_set('session.gc_maxlifetime', 360);
session_set_cookie_params(360);

session_start();

// Verifica se a sessão expirou (permite 5 minutos de inatividade)
if (isset($_SESSION['ultima_atividade']) && (time() - $_SESSION['ultima_atividade'] > 300)) {
    session_unset();
    session_destroy();
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão expirada']);
    exit;
}

// Atualiza o timestamp da última atividade
$_SESSION['ultima_atividade'] = time();

require_once '../BancoDados/conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado']);
    exit;
}

$cpf_usuario = $_SESSION['cpf'];
$acao = $_POST['acao'] ?? '';

header('Content-Type: application/json');

switch ($acao) {
    case 'atualizar':
    case 'atualizar_perfil':
        $email = trim($_POST['email'] ?? '');
        $ra = trim($_POST['ra'] ?? '');
        
        // Validações básicas
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'E-mail válido é obrigatório']);
            break;
        }
        
        // Validar RA (se fornecido)
        if (!empty($ra) && (!is_numeric($ra) || strlen($ra) !== 7)) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'RA deve ter exatamente 7 dígitos']);
            break;
        }
        
        // Buscar dados atuais do usuário
        $sql_user = "SELECT Organizador FROM usuario WHERE CPF = ?";
        $stmt_user = mysqli_prepare($conexao, $sql_user);
        if (!$stmt_user) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro na preparação da consulta']);
            break;
        }
        
        mysqli_stmt_bind_param($stmt_user, "s", $cpf_usuario);
        mysqli_stmt_execute($stmt_user);
        $resultado_user = mysqli_stmt_get_result($stmt_user);
        $dadosUsuario = mysqli_fetch_assoc($resultado_user);
        mysqli_stmt_close($stmt_user);
        
        if (!$dadosUsuario) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não encontrado']);
            break;
        }
        
        // Verifica se o e-mail já está sendo usado por outro usuário
        $sql_check = "SELECT CPF FROM usuario WHERE Email = ? AND CPF != ?";
        $stmt_check = mysqli_prepare($conexao, $sql_check);
        if ($stmt_check) {
            mysqli_stmt_bind_param($stmt_check, "ss", $email, $cpf_usuario);
            mysqli_stmt_execute($stmt_check);
            $resultado_check = mysqli_stmt_get_result($stmt_check);
            
            if (mysqli_num_rows($resultado_check) > 0) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Este e-mail já está sendo usado por outro usuário']);
                mysqli_stmt_close($stmt_check);
                break;
            }
            mysqli_stmt_close($stmt_check);
        }
        
        // Preparar SQL baseado no tipo de usuário
        if ($dadosUsuario['Organizador'] == 1) {
            // Organizador: atualizar apenas email
            $sql = "UPDATE usuario SET Email = ? WHERE CPF = ?";
            $stmt = mysqli_prepare($conexao, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ss", $email, $cpf_usuario);
            }
        } else {
            // Participante: atualizar email e RA
            $sql = "UPDATE usuario SET Email = ?, RA = ? WHERE CPF = ?";
            $stmt = mysqli_prepare($conexao, $sql);
            if ($stmt) {
                $raValue = empty($ra) ? null : $ra;
                mysqli_stmt_bind_param($stmt, "sss", $email, $raValue, $cpf_usuario);
            }
        }
        
        if ($stmt) {
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode([
                    'sucesso' => true, 
                    'mensagem' => 'Perfil atualizado com sucesso',
                    'dados' => [
                        'email' => $email,
                        'ra' => $ra
                    ]
                ]);
            } else {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao atualizar perfil']);
            }
            
            mysqli_stmt_close($stmt);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro na preparação da consulta']);
        }
        break;
        
    case 'excluir_conta':
        // Inicia uma transação para garantir que todas as operações sejam executadas ou nenhuma
        mysqli_autocommit($conexao, false);
        
        try {
            // Remove registros relacionados primeiro (devido às chaves estrangeiras)
            // A ordem é CRÍTICA para evitar erros de constraint
            
            // 1. Remove notificações do usuário
            $sql_notif = "DELETE FROM notificacao WHERE CPF_usuario = ?";
            $stmt_notif = mysqli_prepare($conexao, $sql_notif);
            if ($stmt_notif) {
                mysqli_stmt_bind_param($stmt_notif, "s", $cpf_usuario);
                mysqli_stmt_execute($stmt_notif);
                mysqli_stmt_close($stmt_notif);
            }
            
            // 2. Remove da tabela de presença
            $sql_presenca = "DELETE FROM presenca WHERE CPF = ?";
            $stmt_presenca = mysqli_prepare($conexao, $sql_presenca);
            if ($stmt_presenca) {
                mysqli_stmt_bind_param($stmt_presenca, "s", $cpf_usuario);
                mysqli_stmt_execute($stmt_presenca);
                mysqli_stmt_close($stmt_presenca);
            }
            
            // 3. Remove da lista de espera
            $sql_espera = "DELETE FROM lista_de_espera WHERE CPF = ?";
            $stmt_espera = mysqli_prepare($conexao, $sql_espera);
            if ($stmt_espera) {
                mysqli_stmt_bind_param($stmt_espera, "s", $cpf_usuario);
                mysqli_stmt_execute($stmt_espera);
                mysqli_stmt_close($stmt_espera);
            }
            
            // 4. Remove da tabela lista_de_participantes
            $sql_participantes = "DELETE FROM lista_de_participantes WHERE CPF = ?";
            $stmt_participantes = mysqli_prepare($conexao, $sql_participantes);
            if ($stmt_participantes) {
                mysqli_stmt_bind_param($stmt_participantes, "s", $cpf_usuario);
                mysqli_stmt_execute($stmt_participantes);
                mysqli_stmt_close($stmt_participantes);
            }
            
            // 5. Remove da tabela organiza (se for organizador)
            $sql_organiza = "DELETE FROM organiza WHERE CPF = ?";
            $stmt_organiza = mysqli_prepare($conexao, $sql_organiza);
            if ($stmt_organiza) {
                mysqli_stmt_bind_param($stmt_organiza, "s", $cpf_usuario);
                mysqli_stmt_execute($stmt_organiza);
                mysqli_stmt_close($stmt_organiza);
            }
            
            // 6. Remove eventos criados pelo usuário (se for organizador)
            // Primeiro busca os IDs dos eventos para limpar tabelas relacionadas
            $sql_get_eventos = "SELECT ID_evento FROM evento WHERE CPF_organizador = ?";
            $stmt_get_eventos = mysqli_prepare($conexao, $sql_get_eventos);
            if ($stmt_get_eventos) {
                mysqli_stmt_bind_param($stmt_get_eventos, "s", $cpf_usuario);
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
                mysqli_stmt_bind_param($stmt_eventos, "s", $cpf_usuario);
                mysqli_stmt_execute($stmt_eventos);
                mysqli_stmt_close($stmt_eventos);
            }
            
            // 7. Por último, remove o usuário principal
            $sql_usuario = "DELETE FROM usuario WHERE CPF = ?";
            $stmt_usuario = mysqli_prepare($conexao, $sql_usuario);
            
            if ($stmt_usuario) {
                mysqli_stmt_bind_param($stmt_usuario, "s", $cpf_usuario);
                
                if (mysqli_stmt_execute($stmt_usuario)) {
                    // Confirma a transação
                    mysqli_commit($conexao);
                    
                    // Limpa a sessão
                    $_SESSION = [];
                    session_destroy();
                    
                    echo json_encode(['sucesso' => true, 'mensagem' => 'Conta excluída com sucesso']);
                } else {
                    // Desfaz a transação em caso de erro
                    mysqli_rollback($conexao);
                    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao excluir conta']);
                }
                
                mysqli_stmt_close($stmt_usuario);
            } else {
                mysqli_rollback($conexao);
                echo json_encode(['sucesso' => false, 'mensagem' => 'Erro na preparação da consulta']);
            }
            
        } catch (Exception $e) {
            // Desfaz a transação em caso de erro
            mysqli_rollback($conexao);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro interno do servidor: ' . $e->getMessage()]);
        }
        
        // Restaura o comportamento padrão de autocommit
        mysqli_autocommit($conexao, true);
        break;
        
    default:
        echo json_encode(['sucesso' => false, 'mensagem' => 'Ação não reconhecida']);
        break;
}

mysqli_close($conexao);
?>