<?php
/**
 * Sistema de Gerenciamento Administrativo - CEU
 * API para operações CRUD completas
 */

header('Content-Type: application/json');
session_start();

// Incluir conexão com banco de dados
require_once '../BancoDados/conexao.php';

// Verificação básica de autenticação - aceitar tanto sessão quanto localStorage
$tempAuth = $_SESSION['admin_temp_auth'] ?? '';
if (empty($tempAuth)) {
    // Permitir criação de sessão temporária via POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['admin_temp_auth'])) {
            $_SESSION['admin_temp_auth'] = $input['admin_temp_auth'];
            echo json_encode(['success' => true, 'message' => 'Sessão criada']);
            exit;
        }
    }
    
    // Se não tem autenticação, usar credenciais básicas para testes
    $_SESSION['admin_temp_auth'] = 'authenticated';
}

// Se é uma requisição POST para criar sessão sem action, retornar sucesso
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_GET['action'])) {
    echo json_encode(['success' => true, 'message' => 'Autenticação confirmada']);
    exit;
}

// Função para logs de segurança
function logAdminAction($action, $details = '') {
    error_log("[" . date('Y-m-d H:i:s') . "] Admin Action: $action | Details: $details");
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        case 'dashboard':
            getDashboardStats($conexao);
            break;
            
        case 'eventos':
            if ($method === 'GET') {
                getEventos($conexao);
            } elseif ($method === 'POST') {
                createEvento($conexao);
            } elseif ($method === 'PUT') {
                updateEvento($conexao);
            } elseif ($method === 'DELETE') {
                deleteEvento($conexao);
            }
            break;
            
        case 'usuarios':
            if ($method === 'GET') {
                getUsuarios($conexao);
            } elseif ($method === 'PUT') {
                updateUsuario($conexao);
            } elseif ($method === 'DELETE') {
                deleteUsuario($conexao);
            }
            break;
            
        case 'codigos':
            if ($method === 'GET') {
                getCodigos($conexao);
            } elseif ($method === 'POST') {
                createCodigo($conexao);
            } elseif ($method === 'PUT') {
                updateCodigo($conexao);
            } elseif ($method === 'DELETE') {
                deleteCodigo($conexao);
            }
            break;
            
        case 'certificados':
            getCertificados($conexao);
            break;
            
        default:
            if (empty($action)) {
                echo json_encode(['success' => true, 'message' => 'API Admin funcionando']);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Ação não reconhecida: ' . $action]);
            }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
    logAdminAction('ERROR', $e->getMessage());
}

// ============================================
// FUNÇÕES DO DASHBOARD
// ============================================

function getDashboardStats($conexao) {
    $estatisticas = [];
    
    // Total de eventos
    $resultado = mysqli_query($conexao, "SELECT COUNT(*) as total FROM evento");
    $estatisticas['eventos'] = mysqli_fetch_assoc($resultado)['total'];
    
    // Total de usuários
    $resultado = mysqli_query($conexao, "SELECT COUNT(*) as total FROM usuario");
    $estatisticas['usuarios'] = mysqli_fetch_assoc($resultado)['total'];
    
    // Total de organizadores
    $resultado = mysqli_query($conexao, "SELECT COUNT(*) as total FROM usuario WHERE Organizador = 1");
    $estatisticas['organizadores'] = mysqli_fetch_assoc($resultado)['total'];
    
    // Total de códigos ativos
    $resultado = mysqli_query($conexao, "SELECT COUNT(*) as total FROM codigos_organizador WHERE ativo = 1 AND usado = 0");
    $estatisticas['codigos'] = mysqli_fetch_assoc($resultado)['total'];
    
    // Eventos por categoria
    $resultado = mysqli_query($conexao, "SELECT categoria, COUNT(*) as total FROM evento GROUP BY categoria ORDER BY total DESC");
    $categorias = [];
    while ($linha = mysqli_fetch_assoc($resultado)) {
        $categorias[] = $linha;
    }
    $estatisticas['eventos_por_categoria'] = $categorias;
    
    // Últimos eventos criados
    $resultado = mysqli_query($conexao, "SELECT nome, inicio, lugar, modalidade FROM evento ORDER BY cod_evento DESC LIMIT 5");
    $ultimosEventos = [];
    while ($linha = mysqli_fetch_assoc($resultado)) {
        $ultimosEventos[] = $linha;
    }
    $estatisticas['ultimos_eventos'] = $ultimosEventos;
    
    logAdminAction('DASHBOARD_VIEW', 'Estatísticas carregadas');
    echo json_encode(['success' => true, 'data' => $estatisticas]);
}

// ============================================
// FUNÇÕES DE EVENTOS
// ============================================

function getEventos($conexao) {
    $sql = "SELECT * FROM evento ORDER BY inicio DESC";
    $resultado = mysqli_query($conexao, $sql);
    
    $eventos = [];
    while ($linha = mysqli_fetch_assoc($resultado)) {
        $eventos[] = $linha;
    }
    
    logAdminAction('EVENTOS_LIST', 'Total: ' . count($eventos));
    echo json_encode(['success' => true, 'data' => $eventos]);
}

function createEvento($conexao) {
    $dadosEntrada = json_decode(file_get_contents('php://input'), true);
    
    $codigoEvento = $dadosEntrada['cod_evento'];
    $categoria = $dadosEntrada['categoria'];
    $nome = $dadosEntrada['nome'];
    $lugar = $dadosEntrada['lugar'];
    $descricao = $dadosEntrada['descricao'];
    $publicoAlvo = $dadosEntrada['publico_alvo'];
    $inicio = $dadosEntrada['inicio'];
    $conclusao = $dadosEntrada['conclusao'];
    $duracao = $dadosEntrada['duracao'];
    $certificado = $dadosEntrada['certificado'] ? 1 : 0;
    $modalidade = $dadosEntrada['modalidade'];
    $imagem = $dadosEntrada['imagem'] ?? null;
    
    $sql = "INSERT INTO evento (cod_evento, categoria, nome, lugar, descricao, publico_alvo, inicio, conclusao, duracao, certificado, modalidade, imagem) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "isssssssdiSs", $codigoEvento, $categoria, $nome, $lugar, $descricao, $publicoAlvo, $inicio, $conclusao, $duracao, $certificado, $modalidade, $imagem);
    
    if (mysqli_stmt_execute($stmt)) {
        logAdminAction('EVENTO_CREATE', "Evento: $nome (ID: $codigoEvento)");
        echo json_encode(['success' => true, 'message' => 'Evento criado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao criar evento: ' . mysqli_error($conexao)]);
    }
}

function updateEvento($conexao) {
    $dadosEntrada = json_decode(file_get_contents('php://input'), true);
    
    $codigoEvento = $dadosEntrada['cod_evento'];
    $categoria = $dadosEntrada['categoria'];
    $nome = $dadosEntrada['nome'];
    $lugar = $dadosEntrada['lugar'];
    $descricao = $dadosEntrada['descricao'];
    $publicoAlvo = $dadosEntrada['publico_alvo'];
    $inicio = $dadosEntrada['inicio'];
    $conclusao = $dadosEntrada['conclusao'];
    $duracao = $dadosEntrada['duracao'];
    $certificado = $dadosEntrada['certificado'] ? 1 : 0;
    $modalidade = $dadosEntrada['modalidade'];
    $imagem = $dadosEntrada['imagem'] ?? null;
    
    $sql = "UPDATE evento SET categoria=?, nome=?, lugar=?, descricao=?, publico_alvo=?, inicio=?, conclusao=?, duracao=?, certificado=?, modalidade=?, imagem=? WHERE cod_evento=?";
    
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "sssssssdissi", $categoria, $nome, $lugar, $descricao, $publicoAlvo, $inicio, $conclusao, $duracao, $certificado, $modalidade, $imagem, $codigoEvento);
    
    if (mysqli_stmt_execute($stmt)) {
        logAdminAction('EVENTO_UPDATE', "Evento: $nome (ID: $codigoEvento)");
        echo json_encode(['success' => true, 'message' => 'Evento atualizado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar evento: ' . mysqli_error($conexao)]);
    }
}

function deleteEvento($conexao) {
    $codigoEvento = $_GET['id'] ?? 0;
    
    $sql = "DELETE FROM evento WHERE cod_evento = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "i", $codigoEvento);
    
    if (mysqli_stmt_execute($stmt)) {
        logAdminAction('EVENTO_DELETE', "ID: $codigoEvento");
        echo json_encode(['success' => true, 'message' => 'Evento excluído com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir evento: ' . mysqli_error($conexao)]);
    }
}

// ============================================
// FUNÇÕES DE USUÁRIOS
// ============================================

function getUsuarios($conexao) {
    // Parâmetros de busca
    $termoBusca = $_GET['search'] ?? '';
    $limite = intval($_GET['limit'] ?? 1000);
    $offset = intval($_GET['offset'] ?? 0);
    
    $sql = "SELECT CPF, Nome, Email, RA, Codigo, Organizador, TemaSite FROM usuario";
    $parametros = [];
    $tipos = '';
    
    // Adicionar filtro de busca se fornecido
    if (!empty($termoBusca)) {
        $sql .= " WHERE Nome LIKE ? OR CPF LIKE ? OR Email LIKE ? OR RA LIKE ?";
        $termoBuscaFormatado = "%$termoBusca%";
        $parametros = [$termoBuscaFormatado, $termoBuscaFormatado, $termoBuscaFormatado, $termoBuscaFormatado];
        $tipos = 'ssss';
    }
    
    $sql .= " ORDER BY Nome LIMIT ? OFFSET ?";
    $parametros[] = $limite;
    $parametros[] = $offset;
    $tipos .= 'ii';
    
    $stmt = mysqli_prepare($conexao, $sql);
    if ($tipos) {
        mysqli_stmt_bind_param($stmt, $tipos, ...$parametros);
    }
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    $usuarios = [];
    while ($linha = mysqli_fetch_assoc($resultado)) {
        // Não retornar senhas por segurança
        $usuarios[] = $linha;
    }
    
    // Contar total para paginação (se busca ativa)
    $totalRegistros = count($usuarios);
    if (!empty($termoBusca)) {
        $sqlContagem = "SELECT COUNT(*) as total FROM usuario WHERE Nome LIKE ? OR CPF LIKE ? OR Email LIKE ? OR RA LIKE ?";
        $stmtContagem = mysqli_prepare($conexao, $sqlContagem);
        mysqli_stmt_bind_param($stmtContagem, 'ssss', $termoBuscaFormatado, $termoBuscaFormatado, $termoBuscaFormatado, $termoBuscaFormatado);
        mysqli_stmt_execute($stmtContagem);
        $resultadoContagem = mysqli_stmt_get_result($stmtContagem);
        $totalRegistros = mysqli_fetch_assoc($resultadoContagem)['total'];
    }
    
    logAdminAction('USUARIOS_LIST', "Total: " . count($usuarios) . " | Busca: '$termoBusca'");
    echo json_encode([
        'success' => true, 
        'data' => $usuarios,
        'meta' => [
            'total' => $totalRegistros,
            'limit' => $limite,
            'offset' => $offset,
            'search' => $termoBusca
        ]
    ]);
}

function updateUsuario($conexao) {
    $dadosEntrada = json_decode(file_get_contents('php://input'), true);
    
    $cpf = $dadosEntrada['CPF'];
    $organizador = $dadosEntrada['Organizador'] ? 1 : 0;
    $codigo = $dadosEntrada['Codigo'] ?? null;
    
    $sql = "UPDATE usuario SET Organizador=?, Codigo=? WHERE CPF=?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "iss", $organizador, $codigo, $cpf);
    
    if (mysqli_stmt_execute($stmt)) {
        logAdminAction('USUARIO_UPDATE', "CPF: $cpf, Organizador: $organizador");
        echo json_encode(['success' => true, 'message' => 'Usuário atualizado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar usuário: ' . mysqli_error($conexao)]);
    }
}

function deleteUsuario($conexao) {
    $cpf = $_GET['id'] ?? '';
    
    $sql = "DELETE FROM usuario WHERE CPF = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "s", $cpf);
    
    if (mysqli_stmt_execute($stmt)) {
        logAdminAction('USUARIO_DELETE', "CPF: $cpf");
        echo json_encode(['success' => true, 'message' => 'Usuário excluído com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir usuário: ' . mysqli_error($conexao)]);
    }
}

// ============================================
// FUNÇÕES DE CÓDIGOS
// ============================================

function getCodigos($conexao) {
    // Parâmetros de busca
    $termoBusca = $_GET['search'] ?? '';
    $limite = intval($_GET['limit'] ?? 1000);
    $offset = intval($_GET['offset'] ?? 0);
    
    $sql = "SELECT * FROM codigos_organizador";
    $parametros = [];
    $tipos = '';
    
    // Adicionar filtro de busca se fornecido
    if (!empty($termoBusca)) {
        $sql .= " WHERE codigo LIKE ? OR observacoes LIKE ? OR usado_por LIKE ? OR id = ?";
        $termoBuscaFormatado = "%$termoBusca%";
        $parametros = [$termoBuscaFormatado, $termoBuscaFormatado, $termoBuscaFormatado, $termoBusca];
        $tipos = 'ssss';
    }
    
    $sql .= " ORDER BY data_criacao DESC LIMIT ? OFFSET ?";
    $parametros[] = $limite;
    $parametros[] = $offset;
    $tipos .= 'ii';
    
    $stmt = mysqli_prepare($conexao, $sql);
    if ($tipos) {
        mysqli_stmt_bind_param($stmt, $tipos, ...$parametros);
    }
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    $codigos = [];
    while ($linha = mysqli_fetch_assoc($resultado)) {
        $codigos[] = $linha;
    }
    
    // Contar total para paginação (se busca ativa)
    $totalRegistros = count($codigos);
    if (!empty($termoBusca)) {
        $sqlContagem = "SELECT COUNT(*) as total FROM codigos_organizador WHERE codigo LIKE ? OR observacoes LIKE ? OR usado_por LIKE ? OR id = ?";
        $stmtContagem = mysqli_prepare($conexao, $sqlContagem);
        mysqli_stmt_bind_param($stmtContagem, 'ssss', $termoBuscaFormatado, $termoBuscaFormatado, $termoBuscaFormatado, $termoBusca);
        mysqli_stmt_execute($stmtContagem);
        $resultadoContagem = mysqli_stmt_get_result($stmtContagem);
        $totalRegistros = mysqli_fetch_assoc($resultadoContagem)['total'];
    }
    
    logAdminAction('CODIGOS_LIST', "Total: " . count($codigos) . " | Busca: '$termoBusca'");
    echo json_encode([
        'success' => true, 
        'data' => $codigos,
        'meta' => [
            'total' => $totalRegistros,
            'limit' => $limite,
            'offset' => $offset,
            'search' => $termoBusca
        ]
    ]);
}

function createCodigo($conexao) {
    // Inclui o gerador seguro
    require_once('GeradorCodigoSeguro.php');
    
    $dadosEntrada = json_decode(file_get_contents('php://input'), true);
    
    // Debug: Log dos dados recebidos
    error_log("CreateCodigo - Dados recebidos: " . json_encode($dadosEntrada));
    
    // Se código foi especificado manualmente, valida formato
    if (!empty($dadosEntrada['codigo'])) {
        $codigo = strtoupper(trim($dadosEntrada['codigo']));
        error_log("CreateCodigo - Código manual recebido: '$codigo'");
        
        if (!GeradorCodigoSeguro::validarFormato($codigo)) {
            echo json_encode(['success' => false, 'message' => 'Formato de código inválido. Use 8 caracteres: A-Z, 2-9 (sem 0, O, I, 1, l)']);
            return;
        }
    } else {
        // Gera código seguro automaticamente
        $codigo = GeradorCodigoSeguro::gerarCodigo($conexao);
        error_log("CreateCodigo - Código automático gerado: '$codigo'");
    }
    
    $observacoes = $dadosEntrada['observacoes'] ?? '';
    error_log("CreateCodigo - Final: Código='$codigo', Observações='$observacoes'");
    
    $sql = "INSERT INTO codigos_organizador (codigo, criado_por, observacoes) VALUES (?, 'ADMIN', ?)";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $codigo, $observacoes);
    
    if (mysqli_stmt_execute($stmt)) {
        logAdminAction('CODIGO_CREATE', "Código: $codigo");
        echo json_encode(['success' => true, 'message' => 'Código criado com sucesso', 'codigo' => $codigo]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao criar código: ' . mysqli_error($conexao)]);
    }
}

function updateCodigo($conexao) {
    $dadosEntrada = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($dadosEntrada['id']) || !is_numeric($dadosEntrada['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        return;
    }
    
    $id = intval($dadosEntrada['id']);
    $ativo = isset($dadosEntrada['ativo']) ? ($dadosEntrada['ativo'] ? 1 : 0) : 0;
    $observacoes = $dadosEntrada['observacoes'] ?? '';
    
    // Log para debug
    error_log("UpdateCodigo - ID: $id, Ativo recebido: " . var_export($dadosEntrada['ativo'], true) . ", Ativo final: $ativo");
    
    $sql = "UPDATE codigos_organizador SET ativo=?, observacoes=? WHERE id=?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "isi", $ativo, $observacoes, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        $linhasAfetadas = mysqli_stmt_affected_rows($stmt);
        logAdminAction('CODIGO_UPDATE', "ID: $id, Ativo: $ativo, Linhas afetadas: $linhasAfetadas");
        echo json_encode([
            'success' => true, 
            'message' => 'Código atualizado com sucesso',
            'debug' => [
                'id' => $id,
                'ativo' => $ativo,
                'affected_rows' => $linhasAfetadas
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar código: ' . mysqli_error($conexao)]);
    }
}

function deleteCodigo($conexao) {
    $id = $_GET['id'] ?? 0;
    
    $sql = "DELETE FROM codigos_organizador WHERE id = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        logAdminAction('CODIGO_DELETE', "ID: $id");
        echo json_encode(['success' => true, 'message' => 'Código excluído com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir código: ' . mysqli_error($conexao)]);
    }
}

// ============================================
// FUNÇÕES DE CERTIFICADOS
// ============================================

function getCertificados($conexao) {
    $sql = "SELECT * FROM certificado ORDER BY cod_verificacao";
    $resultado = mysqli_query($conexao, $sql);
    
    $certificados = [];
    while ($linha = mysqli_fetch_assoc($resultado)) {
        $certificados[] = $linha;
    }
    
    logAdminAction('CERTIFICADOS_LIST', 'Total: ' . count($certificados));
    echo json_encode(['success' => true, 'data' => $certificados]);
}

mysqli_close($conexao);
?>