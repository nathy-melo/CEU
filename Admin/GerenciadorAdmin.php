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
        
        case 'pwa':
            getPwaServerInfo();
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
            } elseif ($method === 'POST') {
                createUsuario($conexao);
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
            
        case 'senhas':
            if ($method === 'GET') {
                getSolicitacoesSenha($conexao);
            } elseif ($method === 'POST') {
                resolverSolicitacaoSenha($conexao);
            } elseif ($method === 'DELETE') {
                deleteSolicitacaoSenha($conexao);
            }
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

function createUsuario($conexao) {
    $dadosEntrada = json_decode(file_get_contents('php://input'), true);
    
    // Validar campos obrigatórios
    $cpf = $dadosEntrada['cpf'] ?? '';
    $nome = $dadosEntrada['nome'] ?? '';
    $email = $dadosEntrada['email'] ?? '';
    $senha = $dadosEntrada['senha'] ?? '';
    $ra = $dadosEntrada['ra'] ?? null;
    $organizador = intval($dadosEntrada['organizador'] ?? 0);
    $codigo = $dadosEntrada['codigo'] ?? null;
    
    // Validações
    if (empty($cpf) || empty($nome) || empty($email) || empty($senha)) {
        echo json_encode(['success' => false, 'message' => 'Campos obrigatórios faltando: CPF, Nome, Email e Senha são obrigatórios']);
        return;
    }
    
    // Validar CPF (11 dígitos)
    if (!preg_match('/^\d{11}$/', $cpf)) {
        echo json_encode(['success' => false, 'message' => 'CPF deve conter exatamente 11 dígitos numéricos']);
        return;
    }
    
    // Validar RA (7 dígitos ou vazio)
    if (!empty($ra) && !preg_match('/^\d{7}$/', $ra)) {
        echo json_encode(['success' => false, 'message' => 'RA deve conter exatamente 7 dígitos numéricos ou ser deixado em branco']);
        return;
    }
    
    // Validar email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email inválido']);
        return;
    }
    
    // Verificar se CPF já existe
    $stmtCheck = mysqli_prepare($conexao, "SELECT CPF FROM usuario WHERE CPF = ?");
    mysqli_stmt_bind_param($stmtCheck, "s", $cpf);
    mysqli_stmt_execute($stmtCheck);
    $resultCheck = mysqli_stmt_get_result($stmtCheck);
    if (mysqli_num_rows($resultCheck) > 0) {
        echo json_encode(['success' => false, 'message' => 'CPF já cadastrado no sistema']);
        return;
    }
    mysqli_stmt_close($stmtCheck);
    
    // Verificar se Email já existe
    $stmtCheck = mysqli_prepare($conexao, "SELECT Email FROM usuario WHERE Email = ?");
    mysqli_stmt_bind_param($stmtCheck, "s", $email);
    mysqli_stmt_execute($stmtCheck);
    $resultCheck = mysqli_stmt_get_result($stmtCheck);
    if (mysqli_num_rows($resultCheck) > 0) {
        echo json_encode(['success' => false, 'message' => 'Email já cadastrado no sistema']);
        return;
    }
    mysqli_stmt_close($stmtCheck);
    
    // Se é organizador, validar código
    if ($organizador === 1) {
        if (empty($codigo)) {
            echo json_encode(['success' => false, 'message' => 'Código de organizador é obrigatório para usuários organizadores']);
            return;
        }
        
        // Verificar se código existe e está disponível
        $stmtCodigo = mysqli_prepare($conexao, "SELECT id, usado FROM codigos_organizador WHERE codigo = ? AND ativo = 1");
        mysqli_stmt_bind_param($stmtCodigo, "s", $codigo);
        mysqli_stmt_execute($stmtCodigo);
        $resultCodigo = mysqli_stmt_get_result($stmtCodigo);
        
        if (mysqli_num_rows($resultCodigo) === 0) {
            echo json_encode(['success' => false, 'message' => 'Código de organizador inválido ou inativo']);
            return;
        }
        
        $codigoData = mysqli_fetch_assoc($resultCodigo);
        if ($codigoData['usado'] == 1) {
            echo json_encode(['success' => false, 'message' => 'Código de organizador já foi utilizado']);
            return;
        }
        mysqli_stmt_close($stmtCodigo);
    }
    
    // Hash da senha
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
    
    // Inserir usuário
    $sql = "INSERT INTO usuario (CPF, Nome, Email, Senha, RA, Codigo, Organizador, TemaSite) VALUES (?, ?, ?, ?, ?, ?, ?, 0)";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "ssssssi", $cpf, $nome, $email, $senhaHash, $ra, $codigo, $organizador);
    
    if (mysqli_stmt_execute($stmt)) {
        // Se é organizador, marcar código como usado
        if ($organizador === 1 && !empty($codigo)) {
            $sqlUpdate = "UPDATE codigos_organizador SET usado = 1, data_uso = NOW(), usado_por = ? WHERE codigo = ?";
            $stmtUpdate = mysqli_prepare($conexao, $sqlUpdate);
            mysqli_stmt_bind_param($stmtUpdate, "ss", $cpf, $codigo);
            mysqli_stmt_execute($stmtUpdate);
            mysqli_stmt_close($stmtUpdate);
        }
        
        logAdminAction('USUARIO_CREATE', "CPF: $cpf, Nome: $nome, Email: $email, Organizador: $organizador");
        echo json_encode([
            'success' => true, 
            'message' => 'Usuário criado com sucesso',
            'data' => [
                'cpf' => $cpf,
                'nome' => $nome,
                'email' => $email,
                'organizador' => $organizador
            ]
        ]);
    } else {
        $erro = mysqli_error($conexao);
        logAdminAction('USUARIO_CREATE_ERROR', "CPF: $cpf, Erro: $erro");
        echo json_encode(['success' => false, 'message' => 'Erro ao criar usuário: ' . $erro]);
    }
    
    mysqli_stmt_close($stmt);
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

// ============================================
// FUNÇÕES DE SOLICITAÇÕES DE SENHA
// ============================================

function getSolicitacoesSenha($conexao) {
    // Parâmetros de busca
    $status = $_GET['status'] ?? 'all';
    $limite = intval($_GET['limit'] ?? 1000);
    $offset = intval($_GET['offset'] ?? 0);
    
    $sql = "SELECT * FROM solicitacoes_redefinicao_senha";
    $parametros = [];
    $tipos = '';
    
    // Adicionar filtro de status se fornecido
    if ($status !== 'all') {
        $sql .= " WHERE status = ?";
        $parametros[] = $status;
        $tipos = 's';
    }
    
    $sql .= " ORDER BY data_solicitacao DESC LIMIT ? OFFSET ?";
    $parametros[] = $limite;
    $parametros[] = $offset;
    $tipos .= 'ii';
    
    $stmt = mysqli_prepare($conexao, $sql);
    if ($tipos) {
        mysqli_stmt_bind_param($stmt, $tipos, ...$parametros);
    }
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    $solicitacoes = [];
    while ($linha = mysqli_fetch_assoc($resultado)) {
        $solicitacoes[] = $linha;
    }
    
    // Contar totais por status
    $sqlTotais = "SELECT status, COUNT(*) as total FROM solicitacoes_redefinicao_senha GROUP BY status";
    $resultadoTotais = mysqli_query($conexao, $sqlTotais);
    $totaisPorStatus = [];
    while ($linha = mysqli_fetch_assoc($resultadoTotais)) {
        $totaisPorStatus[$linha['status']] = $linha['total'];
    }
    
    logAdminAction('SENHAS_LIST', "Total: " . count($solicitacoes) . " | Status: '$status'");
    echo json_encode([
        'success' => true, 
        'data' => $solicitacoes,
        'meta' => [
            'total' => count($solicitacoes),
            'limit' => $limite,
            'offset' => $offset,
            'status' => $status,
            'totais_por_status' => $totaisPorStatus
        ]
    ]);
}

function resolverSolicitacaoSenha($conexao) {
    $dadosEntrada = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($dadosEntrada['id'] ?? 0);
    $cpf = $dadosEntrada['cpf'] ?? '';
    $novaSenha = $dadosEntrada['nova_senha'] ?? '';
    $observacoes = $dadosEntrada['observacoes'] ?? '';
    
    if (empty($id) || empty($novaSenha)) {
        echo json_encode(['success' => false, 'message' => 'Dados incompletos. ID e nova senha são obrigatórios.']);
        return;
    }

    // Se CPF não foi enviado pelo frontend, tentar obter pelo ID da solicitação (via email)
    if (empty($cpf)) {
        $sqlSolic = "SELECT email, CPF FROM solicitacoes_redefinicao_senha WHERE id = ?";
        $stmtSolic = mysqli_prepare($conexao, $sqlSolic);
        mysqli_stmt_bind_param($stmtSolic, "i", $id);
        mysqli_stmt_execute($stmtSolic);
        $resSolic = mysqli_stmt_get_result($stmtSolic);
        $rowSolic = mysqli_fetch_assoc($resSolic);
        mysqli_stmt_close($stmtSolic);

        if ($rowSolic) {
            if (!empty($rowSolic['CPF'])) {
                $cpf = $rowSolic['CPF'];
            } else if (!empty($rowSolic['email'])) {
                // Buscar CPF pelo email do usuário
                $sqlUserByEmail = "SELECT CPF FROM usuario WHERE Email = ?";
                $stmtUserByEmail = mysqli_prepare($conexao, $sqlUserByEmail);
                mysqli_stmt_bind_param($stmtUserByEmail, "s", $rowSolic['email']);
                mysqli_stmt_execute($stmtUserByEmail);
                $resUserByEmail = mysqli_stmt_get_result($stmtUserByEmail);
                $rowUser = mysqli_fetch_assoc($resUserByEmail);
                mysqli_stmt_close($stmtUserByEmail);
                if ($rowUser && !empty($rowUser['CPF'])) {
                    $cpf = $rowUser['CPF'];
                }
            }
        }
    }

    if (empty($cpf)) {
        echo json_encode(['success' => false, 'message' => 'Não foi possível identificar o usuário (CPF não encontrado).']);
        return;
    }
    
    // Hash da nova senha
    $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
    
    // Iniciar transação
    mysqli_begin_transaction($conexao);
    
    try {
        // Atualizar senha do usuário
        $sqlUsuario = "UPDATE usuario SET Senha = ? WHERE CPF = ?";
        $stmtUsuario = mysqli_prepare($conexao, $sqlUsuario);
        mysqli_stmt_bind_param($stmtUsuario, "ss", $senhaHash, $cpf);
        
        if (!mysqli_stmt_execute($stmtUsuario)) {
            throw new Exception('Erro ao atualizar senha do usuário');
        }
        if (mysqli_stmt_affected_rows($stmtUsuario) === 0) {
            throw new Exception('Nenhum usuário encontrado com o CPF informado.');
        }
        
        // Marcar solicitação como resolvida
        $sqlSolicitacao = "UPDATE solicitacoes_redefinicao_senha 
                          SET status = 'resolvida', 
                              data_resolucao = NOW(), 
                              resolvido_por = 'ADMIN',
                              observacoes = ? 
                          WHERE id = ?";
        $stmtSolicitacao = mysqli_prepare($conexao, $sqlSolicitacao);
        mysqli_stmt_bind_param($stmtSolicitacao, "si", $observacoes, $id);
        
        if (!mysqli_stmt_execute($stmtSolicitacao)) {
            throw new Exception('Erro ao atualizar status da solicitação');
        }
        
        // Confirmar transação
        mysqli_commit($conexao);
        
        logAdminAction('SENHA_RESOLVIDA', "ID: $id | CPF: $cpf");
        echo json_encode(['success' => true, 'message' => 'Senha redefinida com sucesso']);
        
    } catch (Exception $e) {
        // Reverter transação em caso de erro
        mysqli_rollback($conexao);
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
}

function deleteSolicitacaoSenha($conexao) {
    $id = intval($_GET['id'] ?? 0);
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        return;
    }
    
    // Pode-se optar por marcar como cancelada ao invés de deletar
    $sql = "UPDATE solicitacoes_redefinicao_senha SET status = 'cancelada' WHERE id = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        logAdminAction('SENHA_CANCELADA', "ID: $id");
        echo json_encode(['success' => true, 'message' => 'Solicitação cancelada com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao cancelar solicitação: ' . mysqli_error($conexao)]);
    }
}

mysqli_close($conexao);

// ============================================
// INFO DE PWA/REDE (SEM DEPENDER DO BD)
// ============================================

function getPwaServerInfo() {
    // Dados de ambiente/servidor
    $serverAddr = $_SERVER['SERVER_ADDR'] ?? '';
    // SERVER_ADDR pode vir vazio/127.0.0.1 em ambientes locais; tentar alternativas
    if (!$serverAddr || $serverAddr === '127.0.0.1' || $serverAddr === '::1') {
        $hostname = gethostname();
        if ($hostname) {
            $resolved = gethostbyname($hostname);
            if ($resolved && $resolved !== $hostname) {
                $serverAddr = $resolved;
            }
        }
    }

    $serverName = $_SERVER['SERVER_NAME'] ?? '';
    $httpHost = $_SERVER['HTTP_HOST'] ?? '';
    $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';

    // Calcular base path do app (ex.: /CEU)
    $rootDirName = basename(dirname(__DIR__)); // nome da pasta pai de Admin => CEU
    $basePath = '/' . $rootDirName;

    // Arquivos PWA no root do CEU
    $swPath = realpath(__DIR__ . '/../sw.js');
    $manifestPath = realpath(__DIR__ . '/../manifest.json');
    $manifestInstallPath = realpath(__DIR__ . '/../manifest-install.json');

    $data = [
        'server_addr' => $serverAddr,
        'server_name' => $serverName,
        'http_host' => $httpHost,
        'document_root' => $documentRoot,
        'base_path' => '/' . $rootDirName,
        'paths' => [
            'sw_exists' => file_exists($swPath ?? ''),
            'manifest_exists' => file_exists($manifestPath ?? ''),
            'manifest_install_exists' => file_exists($manifestInstallPath ?? ''),
            'sw_url' => '/' . $rootDirName . '/sw.js',
            'manifest_url' => '/' . $rootDirName . '/manifest.json',
            'manifest_install_url' => '/' . $rootDirName . '/manifest-install.json'
        ]
    ];

    echo json_encode(['success' => true, 'data' => $data]);
}
?>