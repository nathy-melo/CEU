<?php
/**
 * Gerenciador de Colaboradores - Arquivo consolidado
 * 
 * Este arquivo consolida todas as operações relacionadas a colaboradores de eventos:
 * - Listar colaboradores e solicitações (GET)
 * - Adicionar colaborador (POST action=adicionar)
 * - Remover colaborador (POST action=remover)
 * - Aprovar solicitação (POST action=aprovar)
 * - Recusar solicitação (POST action=recusar)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../BancoDados/conexao.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// ===========================
// FUNÇÕES AUXILIARES
// ===========================

function garantirEsquemaColaboradores(mysqli $conexao)
{
    // Tabela de colaboradores por evento
    $sqlColab = "CREATE TABLE IF NOT EXISTS colaboradores_evento (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cod_evento INT NOT NULL,
        CPF CHAR(11) NOT NULL,
        papel VARCHAR(20) NOT NULL DEFAULT 'colaborador',
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_evento_cpf (cod_evento, CPF),
        FOREIGN KEY (cod_evento) REFERENCES evento(cod_evento) ON DELETE CASCADE,
        FOREIGN KEY (CPF) REFERENCES usuario(CPF) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($conexao, $sqlColab);

    // Tabela de solicitações de colaboração
    $sqlSolic = "CREATE TABLE IF NOT EXISTS solicitacoes_colaboracao (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cod_evento INT NOT NULL,
        cpf_solicitante CHAR(11) NOT NULL,
        status ENUM('pendente','aprovada','recusada') NOT NULL DEFAULT 'pendente',
        mensagem TEXT NULL,
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        data_resolucao TIMESTAMP NULL,
        FOREIGN KEY (cod_evento) REFERENCES evento(cod_evento) ON DELETE CASCADE,
        FOREIGN KEY (cpf_solicitante) REFERENCES usuario(CPF) ON DELETE CASCADE,
        UNIQUE KEY uk_pedido (cod_evento, cpf_solicitante)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($conexao, $sqlSolic);
}

function enviarNotificacao($conexao, $cpfDestino, $tipo, $mensagem, $codEvento)
{
    $stmt = mysqli_prepare($conexao, "INSERT INTO notificacoes (CPF, tipo, mensagem, cod_evento, lida) VALUES (?,?,?,?,0)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'sssi', $cpfDestino, $tipo, $mensagem, $codEvento);
        @mysqli_stmt_execute($stmt);
        @mysqli_stmt_close($stmt);
    }
}

function getNomeEvento($conexao, $codEvento)
{
    $result = mysqli_query($conexao, "SELECT nome FROM evento WHERE cod_evento = " . (int)$codEvento . " LIMIT 1");
    if ($result && $row = mysqli_fetch_assoc($result)) {
        mysqli_free_result($result);
        return $row['nome'] ?? 'Evento';
    }
    return 'Evento';
}

function verificarPermissaoOrganizador($conexao, $codEvento, $cpfUsuario)
{
    $stmt = mysqli_prepare($conexao, "SELECT 1 FROM organiza WHERE cod_evento = ? AND CPF = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'is', $codEvento, $cpfUsuario);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $temPermissao = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $temPermissao;
}

function verificarPermissaoVisualizacao($conexao, $codEvento, $cpfUsuario)
{
    // Organizador ou colaborador podem visualizar
    if (verificarPermissaoOrganizador($conexao, $codEvento, $cpfUsuario)) {
        return true;
    }
    
    $stmt = mysqli_prepare($conexao, "SELECT 1 FROM colaboradores_evento WHERE cod_evento = ? AND CPF = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'is', $codEvento, $cpfUsuario);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $temPermissao = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $temPermissao;
}

// ===========================
// VERIFICAÇÕES BÁSICAS
// ===========================

if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
    echo json_encode(['sucesso' => false, 'erro' => 'nao_autenticado']);
    exit;
}

try {
    garantirEsquemaColaboradores($conexao);
    
    // ===========================
    // GET: LISTAR COLABORADORES
    // ===========================
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $cpfUsuario = $_SESSION['cpf'];
        $codEvento = isset($_GET['cod_evento']) ? (int) $_GET['cod_evento'] : 0;
        
        if ($codEvento <= 0) {
            echo json_encode(['sucesso' => false, 'erro' => 'cod_evento_invalido']);
            exit;
        }

        if (!verificarPermissaoVisualizacao($conexao, $codEvento, $cpfUsuario)) {
            echo json_encode(['sucesso' => false, 'erro' => 'sem_permissao']);
            exit;
        }

        // Verifica se usuário é organizador
        $ehOrganizador = verificarPermissaoOrganizador($conexao, $codEvento, $cpfUsuario);

        // Lista colaboradores
        $sql = "SELECT c.CPF, u.Nome AS nome, u.Email AS email, c.papel, c.criado_em
                FROM colaboradores_evento c
                JOIN usuario u ON u.CPF = c.CPF
                WHERE c.cod_evento = ?
                ORDER BY u.Nome";
        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $codEvento);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        $colaboradores = [];
        while ($row = mysqli_fetch_assoc($resultado)) {
            $colaboradores[] = $row;
        }
        mysqli_stmt_close($stmt);

        // Lista solicitações pendentes (apenas para organizador)
        $solicitacoes = [];
        if ($ehOrganizador) {
            $sql = "SELECT s.id, s.cpf_solicitante AS CPF, u.Nome AS nome, u.Email AS email, s.status, s.data_criacao
                    FROM solicitacoes_colaboracao s
                    JOIN usuario u ON u.CPF = s.cpf_solicitante
                    WHERE s.cod_evento = ? AND s.status = 'pendente'
                    ORDER BY s.data_criacao DESC";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param($stmt, 'i', $codEvento);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($res)) {
                $solicitacoes[] = $row;
            }
            mysqli_stmt_close($stmt);
        }

        echo json_encode([
            'sucesso' => true, 
            'colaboradores' => $colaboradores, 
            'solicitacoes' => $solicitacoes,
            'eh_organizador' => $ehOrganizador,
            'cpf_usuario' => $cpfUsuario
        ]);
        exit;
    }

    // ===========================
    // POST: AÇÕES
    // ===========================
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $cpfUsuario = $_SESSION['cpf'];
        $action = $_POST['action'] ?? '';

        // ===========================
        // ADICIONAR COLABORADOR
        // ===========================
        if ($action === 'adicionar') {
            $codEvento = isset($_POST['cod_evento']) ? (int) $_POST['cod_evento'] : 0;
            $identificador = trim($_POST['identificador'] ?? ''); // CPF ou Email
            $papel = $_POST['papel'] ?? 'colaborador';
            
            if ($codEvento <= 0 || $identificador === '') {
                echo json_encode(['sucesso' => false, 'erro' => 'parametros_invalidos']);
                exit;
            }

            if (!verificarPermissaoOrganizador($conexao, $codEvento, $cpfUsuario)) {
                echo json_encode(['sucesso' => false, 'erro' => 'sem_permissao']);
                exit;
            }

            // Resolve identificador -> CPF
            if (preg_match('/^\d{11}$/', $identificador)) {
                $cpfNovo = $identificador;
            } else {
                // Busca por email
                $stmt = mysqli_prepare($conexao, "SELECT CPF FROM usuario WHERE Email = ? LIMIT 1");
                mysqli_stmt_bind_param($stmt, 's', $identificador);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
                
                if (!$row) {
                    echo json_encode(['sucesso' => false, 'erro' => 'usuario_nao_encontrado']);
                    exit;
                }
                $cpfNovo = $row['CPF'];
            }

            // Não permitir adicionar organizador como colaborador
            if (verificarPermissaoOrganizador($conexao, $codEvento, $cpfNovo)) {
                echo json_encode(['sucesso' => false, 'erro' => 'ja_organizador']);
                exit;
            }

            // Inserir colaborador
            $stmt = mysqli_prepare($conexao, "INSERT INTO colaboradores_evento (cod_evento, CPF, papel) VALUES (?,?,?)
                ON DUPLICATE KEY UPDATE papel = VALUES(papel)");
            mysqli_stmt_bind_param($stmt, 'iss', $codEvento, $cpfNovo, $papel);
            
            if (!mysqli_stmt_execute($stmt)) {
                echo json_encode(['sucesso' => false, 'erro' => 'falha_inserir', 'detalhe' => mysqli_error($conexao)]);
                exit;
            }
            mysqli_stmt_close($stmt);

            // Notificar usuário adicionado
            $nomeEvento = getNomeEvento($conexao, $codEvento);
            $mensagem = "Você foi adicionado como colaborador no evento '{$nomeEvento}'.";
            enviarNotificacao($conexao, $cpfNovo, 'colaborador_adicionado', $mensagem, $codEvento);

            echo json_encode(['sucesso' => true, 'mensagem' => 'Colaborador adicionado com sucesso']);
            exit;
        }

        // ===========================
        // SAIR DA COLABORAÇÃO
        // ===========================
        if ($action === 'sair') {
            $codEvento = isset($_POST['cod_evento']) ? (int) $_POST['cod_evento'] : 0;
            
            if ($codEvento <= 0) {
                echo json_encode(['sucesso' => false, 'erro' => 'parametros_invalidos']);
                exit;
            }

            // Verifica se usuário é colaborador
            $stmt = mysqli_prepare($conexao, "SELECT 1 FROM colaboradores_evento WHERE cod_evento = ? AND CPF = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, 'is', $codEvento, $cpfUsuario);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            $ehColaborador = mysqli_stmt_num_rows($stmt) > 0;
            mysqli_stmt_close($stmt);

            if (!$ehColaborador) {
                echo json_encode(['sucesso' => false, 'erro' => 'nao_eh_colaborador']);
                exit;
            }

            // Remove o próprio usuário da colaboração
            $stmt = mysqli_prepare($conexao, "DELETE FROM colaboradores_evento WHERE cod_evento = ? AND CPF = ?");
            mysqli_stmt_bind_param($stmt, 'is', $codEvento, $cpfUsuario);
            
            if (!mysqli_stmt_execute($stmt)) {
                echo json_encode(['sucesso' => false, 'erro' => 'falha_sair']);
                exit;
            }
            mysqli_stmt_close($stmt);

            echo json_encode(['sucesso' => true, 'mensagem' => 'Você saiu da colaboração']);
            exit;
        }

        // ===========================
        // REMOVER COLABORADOR (apenas organizador)
        // ===========================
        if ($action === 'remover') {
            $codEvento = isset($_POST['cod_evento']) ? (int) $_POST['cod_evento'] : 0;
            $cpfRemover = preg_replace('/\D+/', '', $_POST['cpf'] ?? '');
            
            if ($codEvento <= 0 || strlen($cpfRemover) !== 11) {
                echo json_encode(['sucesso' => false, 'erro' => 'parametros_invalidos']);
                exit;
            }

            if (!verificarPermissaoOrganizador($conexao, $codEvento, $cpfUsuario)) {
                echo json_encode(['sucesso' => false, 'erro' => 'sem_permissao']);
                exit;
            }

            // Não permitir remover organizadores
            if (verificarPermissaoOrganizador($conexao, $codEvento, $cpfRemover)) {
                echo json_encode(['sucesso' => false, 'erro' => 'nao_remover_organizador']);
                exit;
            }

            $stmt = mysqli_prepare($conexao, "DELETE FROM colaboradores_evento WHERE cod_evento = ? AND CPF = ?");
            mysqli_stmt_bind_param($stmt, 'is', $codEvento, $cpfRemover);
            
            if (!mysqli_stmt_execute($stmt)) {
                echo json_encode(['sucesso' => false, 'erro' => 'falha_remover']);
                exit;
            }
            mysqli_stmt_close($stmt);

            // Notificar usuário removido
            $nomeEvento = getNomeEvento($conexao, $codEvento);
            $mensagem = "Sua colaboração no evento '{$nomeEvento}' foi removida.";
            enviarNotificacao($conexao, $cpfRemover, 'colaborador_removido', $mensagem, $codEvento);

            echo json_encode(['sucesso' => true, 'mensagem' => 'Colaborador removido']);
            exit;
        }

        // ===========================
        // APROVAR/RECUSAR SOLICITAÇÃO
        // ===========================
        if ($action === 'aprovar' || $action === 'recusar') {
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            
            if ($id <= 0) {
                echo json_encode(['sucesso' => false, 'erro' => 'parametros_invalidos']);
                exit;
            }

            // Busca solicitação
            $stmt = mysqli_prepare($conexao, "SELECT cod_evento, cpf_solicitante FROM solicitacoes_colaboracao WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $sol = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);

            if (!$sol) {
                echo json_encode(['sucesso' => false, 'erro' => 'solicitacao_nao_encontrada']);
                exit;
            }

            $codEvento = (int)$sol['cod_evento'];
            $cpfSolicitante = $sol['cpf_solicitante'];

            if (!verificarPermissaoOrganizador($conexao, $codEvento, $cpfUsuario)) {
                echo json_encode(['sucesso' => false, 'erro' => 'sem_permissao']);
                exit;
            }

            if ($action === 'aprovar') {
                // Adiciona colaborador
                $stmt = mysqli_prepare($conexao, "INSERT INTO colaboradores_evento (cod_evento, CPF, papel) VALUES (?,?, 'colaborador')
                    ON DUPLICATE KEY UPDATE papel = VALUES(papel)");
                mysqli_stmt_bind_param($stmt, 'is', $codEvento, $cpfSolicitante);
                
                if (!mysqli_stmt_execute($stmt)) {
                    echo json_encode(['sucesso' => false, 'erro' => 'falha_adicionar_colaborador']);
                    exit;
                }
                mysqli_stmt_close($stmt);

                // Atualiza status da solicitação
                $stmt = mysqli_prepare($conexao, "UPDATE solicitacoes_colaboracao SET status='aprovada', data_resolucao = CURRENT_TIMESTAMP WHERE id = ?");
                mysqli_stmt_bind_param($stmt, 'i', $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                // Notifica solicitante
                $nomeEvento = getNomeEvento($conexao, $codEvento);
                $mensagem = "Sua solicitação para colaborar no evento '{$nomeEvento}' foi aprovada!";
                enviarNotificacao($conexao, $cpfSolicitante, 'colaboracao_aprovada', $mensagem, $codEvento);

                echo json_encode(['sucesso' => true, 'mensagem' => 'Solicitação aprovada']);
                exit;
            }

            if ($action === 'recusar') {
                // Atualiza status da solicitação
                $stmt = mysqli_prepare($conexao, "UPDATE solicitacoes_colaboracao SET status='recusada', data_resolucao = CURRENT_TIMESTAMP WHERE id = ?");
                mysqli_stmt_bind_param($stmt, 'i', $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                // Notifica solicitante
                $nomeEvento = getNomeEvento($conexao, $codEvento);
                $mensagem = "Sua solicitação para colaborar no evento '{$nomeEvento}' foi recusada.";
                enviarNotificacao($conexao, $cpfSolicitante, 'colaboracao_recusada', $mensagem, $codEvento);

                echo json_encode(['sucesso' => true, 'mensagem' => 'Solicitação recusada']);
                exit;
            }
        }

        // Ação inválida
        echo json_encode(['sucesso' => false, 'erro' => 'acao_invalida']);
        exit;
    }

    // Método não suportado
    echo json_encode(['sucesso' => false, 'erro' => 'metodo_invalido']);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'erro_interno', 'detalhe' => $e->getMessage()]);
    exit;
}
