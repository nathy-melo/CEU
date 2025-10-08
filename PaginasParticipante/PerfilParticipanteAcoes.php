<?php
// Configuração do tempo de sessão para 60 segundos
ini_set('session.gc_maxlifetime', 60);
session_set_cookie_params(60);

session_start();

// Verifica se a sessão expirou
if (isset($_SESSION['ultima_atividade']) && (time() - $_SESSION['ultima_atividade'] > 60)) {
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
            
            // Remove da tabela organiza (se for organizador)
            $sql_organiza = "DELETE FROM organiza WHERE CPF = ?";
            $stmt_organiza = mysqli_prepare($conexao, $sql_organiza);
            if ($stmt_organiza) {
                mysqli_stmt_bind_param($stmt_organiza, "s", $cpf_usuario);
                mysqli_stmt_execute($stmt_organiza);
                mysqli_stmt_close($stmt_organiza);
            }
            
            // Remove da tabela lista_de_participantes (se existir)
            $sql_participantes = "DELETE FROM lista_de_participantes WHERE CPF = ?";
            $stmt_participantes = mysqli_prepare($conexao, $sql_participantes);
            if ($stmt_participantes) {
                mysqli_stmt_bind_param($stmt_participantes, "s", $cpf_usuario);
                mysqli_stmt_execute($stmt_participantes);
                mysqli_stmt_close($stmt_participantes);
            }
            
            // Remove o usuário principal
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
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro interno do servidor']);
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