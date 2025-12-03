<?php
/**
 * Script para processar exclusões de conta após 30 dias
 * 
 * Este script deve ser executado via cron job diariamente.
 * Exemplo de cron job (executar diariamente às 3h da manhã):
 * 0 3 * * * cd /caminho/para/CEU/Admin && php ProcessarExclusoesPendentes.php
 */

// Inclui o arquivo de conexão
require_once('../BancoDados/conexao.php');

// Log de execução
$logFile = __DIR__ . '/logs/exclusoes_' . date('Y-m') . '.log';
$logDir = dirname($logFile);
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

function registrarLog($mensagem) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $mensagem\n", FILE_APPEND);
}

registrarLog("===== Iniciando processamento de exclusões pendentes =====");

try {
    // Busca todas as solicitações pendentes que já passaram dos 30 dias
    $sql = "SELECT s.id, s.CPF, s.data_exclusao_programada, u.Nome, u.Email 
            FROM solicitacoes_exclusao_conta s
            INNER JOIN usuario u ON s.CPF = u.CPF
            WHERE s.status = 'pendente' 
            AND s.data_exclusao_programada <= NOW()";
    
    $resultado = mysqli_query($conexao, $sql);
    
    if (!$resultado) {
        throw new Exception('Erro ao buscar solicitações: ' . mysqli_error($conexao));
    }
    
    $total = mysqli_num_rows($resultado);
    registrarLog("Encontradas $total contas para excluir");
    
    if ($total === 0) {
        registrarLog("Nenhuma conta para processar. Finalizando.");
        mysqli_close($conexao);
        exit(0);
    }
    
    $excluidas = 0;
    $erros = 0;
    
    while ($solicitacao = mysqli_fetch_assoc($resultado)) {
        $cpf = $solicitacao['CPF'];
        $nome = $solicitacao['Nome'];
        $email = $solicitacao['Email'];
        $id_solicitacao = $solicitacao['id'];
        
        registrarLog("Processando CPF: $cpf - Nome: $nome");
        
        // Inicia uma transação
        mysqli_begin_transaction($conexao);
        
        try {
            // 1. Remove notificações
            mysqli_query($conexao, "DELETE FROM notificacoes WHERE CPF = '$cpf'");
            
            // 2. Remove inscrições
            mysqli_query($conexao, "DELETE FROM inscricao WHERE CPF = '$cpf'");
            
            // 3. Remove da lista de participantes
            mysqli_query($conexao, "DELETE FROM lista_de_participantes WHERE CPF = '$cpf'");
            
            // 4. Remove certificados
            mysqli_query($conexao, "DELETE FROM certificado WHERE cpf = '$cpf'");
            
            // 5. Remove eventos organizados (CASCADE vai deletar organiza, inscricao, etc)
            $sql_eventos = "SELECT cod_evento FROM organiza WHERE CPF = '$cpf'";
            $result_eventos = mysqli_query($conexao, $sql_eventos);
            
            if ($result_eventos) {
                $eventos_a_deletar = [];
                while ($row = mysqli_fetch_assoc($result_eventos)) {
                    $eventos_a_deletar[] = $row['cod_evento'];
                }
                
                foreach ($eventos_a_deletar as $cod_evento) {
                    mysqli_query($conexao, "DELETE FROM evento WHERE cod_evento = $cod_evento");
                }
                
                registrarLog("  - " . count($eventos_a_deletar) . " eventos excluídos");
            }
            
            // 6. Remove colaborações
            mysqli_query($conexao, "DELETE FROM colaboradores_evento WHERE CPF = '$cpf'");
            
            // 7. Remove solicitações de colaboração
            mysqli_query($conexao, "DELETE FROM solicitacoes_colaboracao WHERE cpf_solicitante = '$cpf'");
            
            // 8. Remove favoritos
            mysqli_query($conexao, "DELETE FROM favoritos_evento WHERE CPF = '$cpf'");
            
            // 9. Atualiza status da solicitação
            $data_conclusao = date('Y-m-d H:i:s');
            mysqli_query($conexao, "UPDATE solicitacoes_exclusao_conta SET status = 'concluida', data_conclusao = '$data_conclusao' WHERE id = $id_solicitacao");
            
            // 10. Por último, remove o usuário
            $resultado_usuario = mysqli_query($conexao, "DELETE FROM usuario WHERE CPF = '$cpf'");
            
            if ($resultado_usuario && mysqli_affected_rows($conexao) > 0) {
                // Confirma a transação
                mysqli_commit($conexao);
                $excluidas++;
                registrarLog("  ✓ Conta excluída com sucesso: $nome ($email)");
                
                // TODO: Enviar email de confirmação de exclusão
            } else {
                throw new Exception('Erro ao excluir usuário');
            }
            
        } catch (Exception $e) {
            // Reverte a transação em caso de erro
            mysqli_rollback($conexao);
            $erros++;
            registrarLog("  ✗ ERRO ao excluir CPF $cpf: " . $e->getMessage());
        }
    }
    
    registrarLog("===== Processamento finalizado =====");
    registrarLog("Total: $total | Excluídas: $excluidas | Erros: $erros");
    
} catch (Exception $e) {
    registrarLog("ERRO FATAL: " . $e->getMessage());
}

mysqli_close($conexao);
registrarLog("===== Fim da execução =====\n");
?>
