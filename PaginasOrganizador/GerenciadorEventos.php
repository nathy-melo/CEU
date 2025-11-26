<?php
/**
 * Gerenciador de Eventos - Arquivo consolidado
 * 
 * Este arquivo consolida todas as operações de busca/listagem de eventos:
 * - GET ?action=listar_organizador → Lista eventos que o usuário organiza
 * - GET ?action=listar_colaboracao → Lista eventos onde o usuário é colaborador
 * - GET ?action=detalhe&cod_evento=X → Busca detalhes de um evento específico
 * - GET ?action=imagens&cod_evento=X → Busca imagens de um evento
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../BancoDados/conexao.php';

// ===========================
// VERIFICAÇÕES BÁSICAS
// ===========================

if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
    echo json_encode(['erro' => 'Usuário não autenticado']);
    exit;
}

if (!isset($_SESSION['organizador']) || $_SESSION['organizador'] != 1) {
    echo json_encode(['erro' => 'Usuário não é organizador']);
    exit;
}

// ===========================
// FUNÇÕES AUXILIARES
// ===========================

function formatarEvento($dadosEvento)
{
    $dataHoraInicio = new DateTime($dadosEvento['inicio']);
    $dataHoraConclusao = new DateTime($dadosEvento['conclusao']);
    $dataHoraAtual = new DateTime();

    // Determinar status do evento
    if ($dataHoraAtual < $dataHoraInicio) {
        $statusEvento = 'Previsto';
    } elseif ($dataHoraAtual >= $dataHoraInicio && $dataHoraAtual <= $dataHoraConclusao) {
        $statusEvento = 'Em andamento';
    } else {
        $statusEvento = 'Finalizado';
    }

    // Formatar certificado
    $tipo_certificado = $dadosEvento['tipo_certificado'] ?? '';
    $tem_certificado = $dadosEvento['certificado'] == 1;
    
    if ($tem_certificado) {
        if ($tipo_certificado === 'Ensino' || $tipo_certificado === 'Pesquisa' || $tipo_certificado === 'Extensao') {
            $textoTemCertificado = $tipo_certificado;
        } else {
            $textoTemCertificado = 'Sim';
        }
    } else {
        $textoTemCertificado = 'Não';
    }

    return [
        'cod_evento' => $dadosEvento['cod_evento'],
        'categoria' => $dadosEvento['categoria'],
        'nome' => $dadosEvento['nome'],
        'lugar' => $dadosEvento['lugar'],
        'descricao' => $dadosEvento['descricao'],
        'publico_alvo' => $dadosEvento['publico_alvo'],
        'inicio' => $dadosEvento['inicio'],
        'conclusao' => $dadosEvento['conclusao'],
        'duracao' => $dadosEvento['duracao'],
        'certificado' => $textoTemCertificado,
        'certificado_numerico' => $dadosEvento['certificado'],
        'modalidade' => $dadosEvento['modalidade'],
        'imagem' => $dadosEvento['imagem'],
        'status' => $statusEvento,
        'data_formatada' => $dataHoraInicio->format('d/m/y'),
        'horario_inicio' => $dataHoraInicio->format('H:i'),
        'horario_fim' => $dataHoraConclusao->format('H:i')
    ];
}

function garantirEsquemaColaboradores($conexao)
{
    $sql = "CREATE TABLE IF NOT EXISTS colaboradores_evento (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cod_evento INT NOT NULL,
        CPF CHAR(11) NOT NULL,
        papel VARCHAR(20) NOT NULL DEFAULT 'colaborador',
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_evento_cpf (cod_evento, CPF),
        FOREIGN KEY (cod_evento) REFERENCES evento(cod_evento) ON DELETE CASCADE,
        FOREIGN KEY (CPF) REFERENCES usuario(CPF) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($conexao, $sql);
}

// ===========================
// PROCESSAMENTO
// ===========================

try {
    $action = $_GET['action'] ?? '';
    $cpfUsuario = $_SESSION['cpf'];

    // ===========================
    // LISTAR EVENTOS DO ORGANIZADOR
    // ===========================
    if ($action === 'listar_organizador') {
        $sql = "SELECT 
                    evento.cod_evento,
                    evento.categoria,
                    evento.nome,
                    evento.lugar,
                    evento.descricao,
                    evento.publico_alvo,
                    evento.inicio,
                    evento.conclusao,
                    evento.duracao,
                    evento.certificado,
                    evento.modalidade,
                    evento.imagem
                FROM evento
                INNER JOIN organiza ON evento.cod_evento = organiza.cod_evento
                WHERE organiza.CPF = ?
                ORDER BY evento.inicio DESC";

        $stmt = mysqli_prepare($conexao, $sql);
        if (!$stmt) {
            echo json_encode(['erro' => 'Erro ao preparar consulta: ' . mysqli_error($conexao)]);
            exit;
        }

        mysqli_stmt_bind_param($stmt, "s", $cpfUsuario);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);

        $eventos = [];
        while ($dadosEvento = mysqli_fetch_assoc($resultado)) {
            $eventos[] = formatarEvento($dadosEvento);
        }

        mysqli_stmt_close($stmt);
        echo json_encode(['sucesso' => true, 'eventos' => $eventos], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ===========================
    // LISTAR EVENTOS DE COLABORAÇÃO
    // ===========================
    if ($action === 'listar_colaboracao') {
        garantirEsquemaColaboradores($conexao);

        $sql = "SELECT 
                    e.cod_evento, 
                    e.nome, 
                    e.inicio, 
                    e.conclusao, 
                    e.categoria, 
                    e.lugar, 
                    e.modalidade, 
                    e.certificado,
                    e.publico_alvo,
                    e.descricao,
                    e.duracao,
                    e.imagem,
                    DATE_FORMAT(e.inicio, '%d/%m/%y') as data_formatada,
                    CASE 
                        WHEN e.conclusao < NOW() THEN 'Concluído'
                        WHEN e.inicio > NOW() THEN 'Agendado'
                        ELSE 'Em andamento'
                    END as status
                FROM colaboradores_evento c
                INNER JOIN evento e ON c.cod_evento = e.cod_evento
                WHERE c.CPF = ?
                AND NOT EXISTS (
                    SELECT 1 FROM organiza o 
                    WHERE o.cod_evento = e.cod_evento AND o.CPF = ?
                )
                ORDER BY e.inicio DESC";

        $stmt = mysqli_prepare($conexao, $sql);
        if (!$stmt) {
            echo json_encode(['sucesso' => false, 'erro' => 'erro_preparar_consulta', 'detalhe' => mysqli_error($conexao)]);
            exit;
        }

        mysqli_stmt_bind_param($stmt, 'ss', $cpfUsuario, $cpfUsuario);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);

        $eventos = [];
        while ($row = mysqli_fetch_assoc($resultado)) {
            $tipo_certificado = $row['tipo_certificado'] ?? '';
            $tem_certificado = ((int)$row['certificado'] === 1);
            
            if ($tem_certificado) {
                if ($tipo_certificado === 'Ensino' || $tipo_certificado === 'Pesquisa' || $tipo_certificado === 'Extensao') {
                    $row['certificado'] = $tipo_certificado;
                } else {
                    $row['certificado'] = 'Sim';
                }
            } else {
                $row['certificado'] = 'Não';
            }
            $eventos[] = $row;
        }

        mysqli_stmt_close($stmt);
        echo json_encode(['sucesso' => true, 'eventos' => $eventos]);
        exit;
    }

    // ===========================
    // BUSCAR DETALHES DE UM EVENTO
    // ===========================
    if ($action === 'detalhe') {
        $codEvento = isset($_GET['cod_evento']) ? intval($_GET['cod_evento']) : 0;

        if ($codEvento <= 0) {
            echo json_encode(['erro' => 'Código do evento não fornecido']);
            exit;
        }

        // Verifica permissão (organizador OU colaborador)
        $sqlPermissao = "SELECT 1 FROM organiza WHERE cod_evento = ? AND CPF = ?
                        UNION
                        SELECT 1 FROM colaboradores_evento WHERE cod_evento = ? AND CPF = ?
                        LIMIT 1";

        $stmtPermissao = mysqli_prepare($conexao, $sqlPermissao);
        if (!$stmtPermissao) {
            echo json_encode(['erro' => 'Erro ao verificar permissão: ' . mysqli_error($conexao)]);
            exit;
        }

        mysqli_stmt_bind_param($stmtPermissao, "isis", $codEvento, $cpfUsuario, $codEvento, $cpfUsuario);
        mysqli_stmt_execute($stmtPermissao);
        $resultadoPermissao = mysqli_stmt_get_result($stmtPermissao);

        if (!mysqli_fetch_assoc($resultadoPermissao)) {
            mysqli_stmt_close($stmtPermissao);
            echo json_encode(['erro' => 'Evento não encontrado ou você não tem permissão para visualizá-lo']);
            exit;
        }

        mysqli_stmt_close($stmtPermissao);

        // Busca dados do evento com informações do organizador
        $sql = "SELECT 
                    evento.cod_evento,
                    evento.categoria,
                    evento.nome,
                    evento.lugar,
                    evento.descricao,
                    evento.publico_alvo,
                    evento.inicio,
                    evento.conclusao,
                    evento.duracao,
                    evento.certificado,
                    evento.tipo_certificado,
                    evento.modalidade,
                    evento.imagem,
                    evento.inicio_inscricao,
                    evento.fim_inscricao,
                    evento.modelo_certificado_participante,
                    evento.modelo_certificado_organizador,
                    usuario.Nome as nome_organizador
                FROM evento
                INNER JOIN organiza ON evento.cod_evento = organiza.cod_evento
                INNER JOIN usuario ON organiza.CPF = usuario.CPF
                WHERE evento.cod_evento = ?
                LIMIT 1";

        $stmt = mysqli_prepare($conexao, $sql);
        if (!$stmt) {
            echo json_encode(['erro' => 'Erro ao preparar consulta: ' . mysqli_error($conexao)]);
            exit;
        }

        mysqli_stmt_bind_param($stmt, "i", $codEvento);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);

        if ($dadosEvento = mysqli_fetch_assoc($resultado)) {
            $dataHoraInicio = new DateTime($dadosEvento['inicio']);
            $dataHoraConclusao = new DateTime($dadosEvento['conclusao']);
            $dataHoraAtual = new DateTime();

            // Status do evento
            if ($dataHoraAtual < $dataHoraInicio) {
                $statusEvento = 'Previsto';
            } elseif ($dataHoraAtual >= $dataHoraInicio && $dataHoraAtual <= $dataHoraConclusao) {
                $statusEvento = 'Em andamento';
            } else {
                $statusEvento = 'Finalizado';
            }

            // Formatar datas de inscrição se existirem
            $dataInicioInscricao = '-';
            $horaInicioInscricao = '-';
            $dataFimInscricao = '-';
            $horaFimInscricao = '-';
            $dataInicioInscricaoParaInput = '';
            $dataFimInscricaoParaInput = '';
            
            if (!empty($dadosEvento['inicio_inscricao'])) {
                $dtInicioInsc = new DateTime($dadosEvento['inicio_inscricao']);
                $dataInicioInscricao = $dtInicioInsc->format('d/m/y');
                $horaInicioInscricao = $dtInicioInsc->format('H:i');
                $dataInicioInscricaoParaInput = $dtInicioInsc->format('Y-m-d');
            }
            
            if (!empty($dadosEvento['fim_inscricao'])) {
                $dtFimInsc = new DateTime($dadosEvento['fim_inscricao']);
                $dataFimInscricao = $dtFimInsc->format('d/m/y');
                $horaFimInscricao = $dtFimInsc->format('H:i');
                $dataFimInscricaoParaInput = $dtFimInsc->format('Y-m-d');
            }

            $eventoFormatado = [
                'cod_evento' => $dadosEvento['cod_evento'],
                'categoria' => $dadosEvento['categoria'],
                'nome' => $dadosEvento['nome'],
                'lugar' => $dadosEvento['lugar'],
                'descricao' => $dadosEvento['descricao'],
                'publico_alvo' => $dadosEvento['publico_alvo'],
                'inicio' => $dadosEvento['inicio'],
                'conclusao' => $dadosEvento['conclusao'],
                'duracao' => $dadosEvento['duracao'],
                'certificado' => ($dadosEvento['certificado'] == 1 && 
                    ($dadosEvento['tipo_certificado'] === 'Ensino' || 
                     $dadosEvento['tipo_certificado'] === 'Pesquisa' || 
                     $dadosEvento['tipo_certificado'] === 'Extensao')) 
                        ? $dadosEvento['tipo_certificado'] 
                        : ($dadosEvento['certificado'] == 1 ? 'Sim' : 'Não'),
                'certificado_numerico' => $dadosEvento['certificado'],
                'modalidade' => $dadosEvento['modalidade'],
                'imagem' => $dadosEvento['imagem'],
                'nome_organizador' => $dadosEvento['nome_organizador'],
                'status' => $statusEvento,
                'data_inicio_formatada' => $dataHoraInicio->format('d/m/y'),
                'data_fim_formatada' => $dataHoraConclusao->format('d/m/y'),
                'data_inicio_para_input' => $dataHoraInicio->format('Y-m-d'),
                'data_fim_para_input' => $dataHoraConclusao->format('Y-m-d'),
                'horario_inicio' => $dataHoraInicio->format('H:i'),
                'horario_fim' => $dataHoraConclusao->format('H:i'),
                'data_inicio_inscricao' => $dataInicioInscricao,
                'hora_inicio_inscricao' => $horaInicioInscricao,
                'data_fim_inscricao' => $dataFimInscricao,
                'hora_fim_inscricao' => $horaFimInscricao,
                'data_inicio_inscricao_para_input' => $dataInicioInscricaoParaInput,
                'data_fim_inscricao_para_input' => $dataFimInscricaoParaInput,
                'modelo_certificado_participante' => $dadosEvento['modelo_certificado_participante'] ?: 'ModeloExemplo.pptx',
                'modelo_certificado_organizador' => $dadosEvento['modelo_certificado_organizador'] ?: 'ModeloExemploOrganizador.pptx'
            ];

            mysqli_stmt_close($stmt);
            echo json_encode(['sucesso' => true, 'evento' => $eventoFormatado], JSON_UNESCAPED_UNICODE);
        } else {
            mysqli_stmt_close($stmt);
            echo json_encode(['erro' => 'Evento não encontrado']);
        }
        exit;
    }

    // ===========================
    // BUSCAR IMAGENS DO EVENTO
    // ===========================
    if ($action === 'imagens') {
        $codEvento = isset($_GET['cod_evento']) ? (int)$_GET['cod_evento'] : 0;

        if ($codEvento <= 0) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Código do evento inválido']);
            exit;
        }

        // Busca imagens da tabela imagens_evento
        $sql = "SELECT id, caminho_imagem, ordem, principal 
                FROM imagens_evento 
                WHERE cod_evento = ? 
                ORDER BY principal DESC, ordem ASC";

        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, "i", $codEvento);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);

        $imagens = [];
        while ($linha = mysqli_fetch_assoc($resultado)) {
            $imagens[] = [
                'id' => $linha['id'],
                'caminho' => $linha['caminho_imagem'],
                'ordem' => $linha['ordem'],
                'principal' => $linha['principal'] == 1
            ];
        }

        mysqli_stmt_close($stmt);

        // Fallback para imagem da tabela evento (compatibilidade)
        if (empty($imagens)) {
            $sqlEvento = "SELECT imagem FROM evento WHERE cod_evento = ?";
            $stmtEvento = mysqli_prepare($conexao, $sqlEvento);
            mysqli_stmt_bind_param($stmtEvento, "i", $codEvento);
            mysqli_stmt_execute($stmtEvento);
            $resultadoEvento = mysqli_stmt_get_result($stmtEvento);
            $linhaEvento = mysqli_fetch_assoc($resultadoEvento);

            if ($linhaEvento && !empty($linhaEvento['imagem'])) {
                $imagens[] = [
                    'id' => 0,
                    'caminho' => $linhaEvento['imagem'],
                    'ordem' => 0,
                    'principal' => true
                ];
            } else {
                // Logo padrão
                $imagens[] = [
                    'id' => 0,
                    'caminho' => 'ImagensEventos/CEU-ImagemEvento.png',
                    'ordem' => 0,
                    'principal' => true
                ];
            }

            mysqli_stmt_close($stmtEvento);
        }

        echo json_encode([
            'sucesso' => true,
            'imagens' => $imagens,
            'total' => count($imagens)
        ]);
        exit;
    }

    // Ação inválida
    echo json_encode(['erro' => 'Ação não especificada ou inválida']);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno', 'detalhe' => $e->getMessage()]);
    exit;
}
