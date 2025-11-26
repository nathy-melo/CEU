<?php
// Lista de Participantes - Arquivo consolidado
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Verifica autenticação
if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
    header('Location: ../index.php');
    exit;
}

// Verifica se é organizador
if (!isset($_SESSION['organizador']) || $_SESSION['organizador'] != 1) {
    header('Location: ../index.php');
    exit;
}

// Função auxiliar para exportar em diferentes formatos
function exportarArquivo($dados, $colunas, $nomeBase, $formato = 'csv')
{
    switch ($formato) {
        case 'xlsx':
        case 'ods':
            // Para formatos mais complexos, vamos usar uma biblioteca simples ou gerar XML
            exportarPlanilha($dados, $colunas, $nomeBase, $formato);
            break;
        case 'csv':
        default:
            exportarCSV($dados, $colunas, $nomeBase);
            break;
    }
}

function exportarCSV($dados, $colunas, $nomeBase)
{
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"{$nomeBase}.csv\"");

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM para UTF-8
    fputcsv($output, $colunas);

    foreach ($dados as $linha) {
        fputcsv($output, $linha);
    }

    fclose($output);
}

function exportarPlanilha($dados, $colunas, $nomeBase, $formato)
{
    // Gera XML básico compatível com Excel e LibreOffice
    $isODS = ($formato === 'ods');

    if ($isODS) {
        header('Content-Type: application/vnd.oasis.opendocument.spreadsheet');
        header("Content-Disposition: attachment; filename=\"{$nomeBase}.ods\"");
    } else {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$nomeBase}.xlsx\"");
    }

    // Cria XML simples que funciona tanto para Excel quanto LibreOffice
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
    echo '  xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
    echo '  <Worksheet ss:Name="Dados">' . "\n";
    echo '    <Table>' . "\n";

    // Cabeçalho
    echo '      <Row>' . "\n";
    foreach ($colunas as $coluna) {
        echo '        <Cell><Data ss:Type="String">' . htmlspecialchars($coluna) . '</Data></Cell>' . "\n";
    }
    echo '      </Row>' . "\n";

    // Dados
    foreach ($dados as $linha) {
        echo '      <Row>' . "\n";
        foreach ($linha as $valor) {
            $tipo = is_numeric($valor) ? 'Number' : 'String';
            echo '        <Cell><Data ss:Type="' . $tipo . '">' . htmlspecialchars($valor) . '</Data></Cell>' . "\n";
        }
        echo '      </Row>' . "\n";
    }

    echo '    </Table>' . "\n";
    echo '  </Worksheet>' . "\n";
    echo '</Workbook>';
}

// Se for requisição AJAX para buscar código de verificação do certificado
if (isset($_GET['action']) && $_GET['action'] === 'buscar_codigo_certificado' && isset($_GET['cpf']) && isset($_GET['cod_evento'])) {
    header('Content-Type: application/json; charset=utf-8');

    require_once __DIR__ . '/../BancoDados/conexao.php';

    $cpfParticipante = $_GET['cpf'];
    $codEvento = intval($_GET['cod_evento']);

    try {
        // Buscar código de verificação do certificado
        $consultaCertificado = "SELECT cod_verificacao 
                                FROM certificado 
                                WHERE cpf = ? AND cod_evento = ?
                                LIMIT 1";

        $stmt = mysqli_prepare($conexao, $consultaCertificado);
        mysqli_stmt_bind_param($stmt, "si", $cpfParticipante, $codEvento);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        $certificado = mysqli_fetch_assoc($resultado);
        mysqli_stmt_close($stmt);

        if ($certificado) {
            echo json_encode([
                'sucesso' => true,
                'codigo_verificacao' => $certificado['cod_verificacao']
            ]);
        } else {
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Certificado não encontrado para este participante.'
            ]);
        }

        mysqli_close($conexao);
    } catch (Exception $e) {
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Erro ao buscar certificado: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Se for requisição AJAX para buscar participantes
if (isset($_GET['action']) && $_GET['action'] === 'buscar' && isset($_GET['cod_evento'])) {
    header('Content-Type: application/json; charset=utf-8');

    require_once __DIR__ . '/../BancoDados/conexao.php';

    $cpfOrganizador = $_SESSION['cpf'];
    $codEvento = intval($_GET['cod_evento']);

    try {
        // Verifica permissão
        $consultaPermissao = "SELECT 1 FROM organiza WHERE cod_evento = ? AND CPF = ?
                              UNION
                              SELECT 1 FROM colaboradores_evento WHERE cod_evento = ? AND CPF = ?
                              LIMIT 1";

        $stmtPermissao = mysqli_prepare($conexao, $consultaPermissao);
        mysqli_stmt_bind_param($stmtPermissao, "isis", $codEvento, $cpfOrganizador, $codEvento, $cpfOrganizador);
        mysqli_stmt_execute($stmtPermissao);
        $resultadoPermissao = mysqli_stmt_get_result($stmtPermissao);

        if (!mysqli_fetch_assoc($resultadoPermissao)) {
            mysqli_stmt_close($stmtPermissao);
            mysqli_close($conexao);
            echo json_encode(['sucesso' => false, 'erro' => 'sem_permissao']);
            exit;
        }

        mysqli_stmt_close($stmtPermissao);

        // Garante que as colunas de inscrição existam
        mysqli_query($conexao, "ALTER TABLE evento ADD COLUMN IF NOT EXISTS inicio_inscricao datetime NULL");
        mysqli_query($conexao, "ALTER TABLE evento ADD COLUMN IF NOT EXISTS fim_inscricao datetime NULL");

        // Busca informações do evento
        $consultaEvento = "SELECT nome, categoria, lugar, modalidade, 
                           DATE_FORMAT(inicio, '%d/%m/%Y %H:%i') as inicio_formatado, 
                           DATE_FORMAT(conclusao, '%d/%m/%Y %H:%i') as conclusao_formatado,
                           DATE_FORMAT(inicio_inscricao, '%d/%m/%Y %H:%i') as inicio_inscricao_formatado,
                           DATE_FORMAT(fim_inscricao, '%d/%m/%Y %H:%i') as fim_inscricao_formatado,
                           duracao 
                           FROM evento WHERE cod_evento = ?";
        $stmtEvento = mysqli_prepare($conexao, $consultaEvento);
        mysqli_stmt_bind_param($stmtEvento, "i", $codEvento);
        mysqli_stmt_execute($stmtEvento);
        $resultadoEvento = mysqli_stmt_get_result($stmtEvento);
        $dadosEvento = mysqli_fetch_assoc($resultadoEvento);
        mysqli_stmt_close($stmtEvento);

        // Busca participantes
        $consultaParticipantes = "SELECT 
                                    i.CPF,
                                    u.Nome,
                                    u.Email,
                                    u.RA,
                                    i.data_inscricao,
                                    i.presenca_confirmada,
                                    i.certificado_emitido,
                                    cert.cod_verificacao,
                                    DATE_FORMAT(i.data_inscricao, '%d/%m/%y - %H:%i') as data_inscricao_formatada
                                  FROM inscricao i
                                  INNER JOIN usuario u ON i.CPF = u.CPF
                                  LEFT JOIN certificado cert ON cert.cpf = i.CPF AND cert.cod_evento = i.cod_evento
                                  WHERE i.cod_evento = ? AND i.status = 'ativa'
                                  ORDER BY i.data_inscricao DESC";

        $stmtParticipantes = mysqli_prepare($conexao, $consultaParticipantes);
        mysqli_stmt_bind_param($stmtParticipantes, "i", $codEvento);
        mysqli_stmt_execute($stmtParticipantes);
        $resultadoParticipantes = mysqli_stmt_get_result($stmtParticipantes);

        $participantes = [];
        while ($row = mysqli_fetch_assoc($resultadoParticipantes)) {
            $participantes[] = [
                'cpf' => $row['CPF'],
                'nome' => $row['Nome'],
                'email' => $row['Email'],
                'ra' => $row['RA'] ?? 'Não informado',
                'data_inscricao' => $row['data_inscricao_formatada'],
                'presenca_confirmada' => (int)$row['presenca_confirmada'] === 1,
                'certificado_emitido' => (int)$row['certificado_emitido'] === 1,
                'cod_verificacao' => $row['cod_verificacao'] ?? null
            ];
        }

        mysqli_stmt_close($stmtParticipantes);
        mysqli_close($conexao);

        echo json_encode([
            'sucesso' => true,
            'evento' => [
                'cod_evento' => $codEvento,
                'nome' => $dadosEvento['nome'] ?? 'Evento',
                'categoria' => $dadosEvento['categoria'] ?? '',
                'lugar' => $dadosEvento['lugar'] ?? '',
                'modalidade' => $dadosEvento['modalidade'] ?? '',
                'inicio' => $dadosEvento['inicio_formatado'] ?? '',
                'conclusao' => $dadosEvento['conclusao_formatado'] ?? '',
                'inicio_inscricao' => $dadosEvento['inicio_inscricao_formatado'] ?? '',
                'fim_inscricao' => $dadosEvento['fim_inscricao_formatado'] ?? '',
                'duracao' => $dadosEvento['duracao'] ?? ''
            ],
            'participantes' => $participantes,
            'total' => count($participantes)
        ]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['sucesso' => false, 'erro' => 'erro_interno', 'detalhe' => $e->getMessage()]);
    }
    exit;
}

// Se for requisição AJAX para buscar organizadores/colaboradores
if (isset($_GET['action']) && $_GET['action'] === 'buscar_organizacao' && isset($_GET['cod_evento'])) {
    header('Content-Type: application/json; charset=utf-8');

    require_once __DIR__ . '/../BancoDados/conexao.php';

    $cpfOrganizador = $_SESSION['cpf'];
    $codEvento = intval($_GET['cod_evento']);

    try {
        // Verifica permissão
        $consultaPermissao = "SELECT 1 FROM organiza WHERE cod_evento = ? AND CPF = ?
                              UNION
                              SELECT 1 FROM colaboradores_evento WHERE cod_evento = ? AND CPF = ?
                              LIMIT 1";

        $stmtPermissao = mysqli_prepare($conexao, $consultaPermissao);
        mysqli_stmt_bind_param($stmtPermissao, "isis", $codEvento, $cpfOrganizador, $codEvento, $cpfOrganizador);
        mysqli_stmt_execute($stmtPermissao);
        $resultadoPermissao = mysqli_stmt_get_result($stmtPermissao);

        if (!mysqli_fetch_assoc($resultadoPermissao)) {
            mysqli_stmt_close($stmtPermissao);
            mysqli_close($conexao);
            echo json_encode(['sucesso' => false, 'erro' => 'sem_permissao']);
            exit;
        }

        mysqli_stmt_close($stmtPermissao);

        // Garante que a coluna certificado_emitido existe
        mysqli_query($conexao, "ALTER TABLE colaboradores_evento ADD COLUMN IF NOT EXISTS certificado_emitido tinyint(1) DEFAULT 0");
        mysqli_query($conexao, "ALTER TABLE colaboradores_evento ADD COLUMN IF NOT EXISTS presenca_confirmada tinyint(1) DEFAULT 0");

        // Garante que a tabela organiza tem as colunas necessárias
        mysqli_query($conexao, "ALTER TABLE organiza ADD COLUMN IF NOT EXISTS presenca_confirmada tinyint(1) DEFAULT 0");
        mysqli_query($conexao, "ALTER TABLE organiza ADD COLUMN IF NOT EXISTS certificado_emitido tinyint(1) DEFAULT 0");

        // Busca organizador principal
        $consultaOrganizador = "SELECT 
                                    o.CPF,
                                    u.Nome,
                                    u.Email,
                                    u.RA,
                                    'Organizador' as tipo,
                                    o.presenca_confirmada,
                                    o.certificado_emitido,
                                    cert.cod_verificacao
                                  FROM organiza o
                                  INNER JOIN usuario u ON o.CPF = u.CPF
                                  LEFT JOIN certificado cert ON cert.cpf = o.CPF AND cert.cod_evento = o.cod_evento
                                  WHERE o.cod_evento = ?";

        $stmtOrg = mysqli_prepare($conexao, $consultaOrganizador);
        mysqli_stmt_bind_param($stmtOrg, "i", $codEvento);
        mysqli_stmt_execute($stmtOrg);
        $resultadoOrg = mysqli_stmt_get_result($stmtOrg);

        $membrosOrganizacao = [];
        while ($row = mysqli_fetch_assoc($resultadoOrg)) {
            $membrosOrganizacao[] = [
                'cpf' => $row['CPF'],
                'nome' => $row['Nome'],
                'email' => $row['Email'],
                'ra' => $row['RA'] ?? 'Não informado',
                'tipo' => 'Organizador',
                'presenca_confirmada' => (int)$row['presenca_confirmada'] === 1,
                'certificado_emitido' => (int)$row['certificado_emitido'] === 1,
                'cod_verificacao' => $row['cod_verificacao'] ?? null
            ];
        }

        mysqli_stmt_close($stmtOrg);

        // Busca colaboradores
        $consultaColaboradores = "SELECT 
                                    c.CPF,
                                    u.Nome,
                                    u.Email,
                                    u.RA,
                                    'Colaborador' as tipo,
                                    c.presenca_confirmada,
                                    c.certificado_emitido,
                                    cert.cod_verificacao
                                  FROM colaboradores_evento c
                                  INNER JOIN usuario u ON c.CPF = u.CPF
                                  LEFT JOIN certificado cert ON cert.cpf = c.CPF AND cert.cod_evento = c.cod_evento
                                  WHERE c.cod_evento = ?
                                  ORDER BY u.Nome";

        $stmtColab = mysqli_prepare($conexao, $consultaColaboradores);
        mysqli_stmt_bind_param($stmtColab, "i", $codEvento);
        mysqli_stmt_execute($stmtColab);
        $resultadoColab = mysqli_stmt_get_result($stmtColab);

        while ($row = mysqli_fetch_assoc($resultadoColab)) {
            $membrosOrganizacao[] = [
                'cpf' => $row['CPF'],
                'nome' => $row['Nome'],
                'email' => $row['Email'],
                'ra' => $row['RA'] ?? 'Não informado',
                'tipo' => 'Colaborador',
                'presenca_confirmada' => (int)$row['presenca_confirmada'] === 1,
                'certificado_emitido' => (int)$row['certificado_emitido'] === 1,
                'cod_verificacao' => $row['cod_verificacao'] ?? null
            ];
        }

        mysqli_stmt_close($stmtColab);
        mysqli_close($conexao);

        echo json_encode([
            'sucesso' => true,
            'membros' => $membrosOrganizacao,
            'total' => count($membrosOrganizacao)
        ]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['sucesso' => false, 'erro' => 'erro_interno', 'detalhe' => $e->getMessage()]);
    }
    exit;
}

// Verificar se CPF existe
if (isset($_GET['action']) && $_GET['action'] === 'verificar_cpf' && isset($_GET['cpf'])) {
    header('Content-Type: application/json; charset=utf-8');
    require_once __DIR__ . '/../BancoDados/conexao.php';

    $cpf = $_GET['cpf'];

    try {
        $sql = "SELECT CPF, Nome, Email, RA FROM usuario WHERE CPF = ?";
        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, "s", $cpf);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $usuario = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        mysqli_close($conexao);

        if ($usuario) {
            echo json_encode([
                'existe' => true,
                'usuario' => [
                    'nome' => $usuario['Nome'],
                    'email' => $usuario['Email'],
                    'ra' => $usuario['RA']
                ]
            ]);
        } else {
            echo json_encode(['existe' => false]);
        }
    } catch (Exception $e) {
        echo json_encode(['existe' => false, 'erro' => $e->getMessage()]);
    }
    exit;
}

// Exportar lista de presença
if (isset($_GET['action']) && $_GET['action'] === 'exportar_presenca' && isset($_GET['cod_evento'])) {
    require_once __DIR__ . '/../BancoDados/conexao.php';

    $codEvento = intval($_GET['cod_evento']);
    $formato = $_GET['formato'] ?? 'csv';

    try {
        $sql = "SELECT u.Nome, u.Email, u.RA, u.CPF, i.data_inscricao, i.presenca_confirmada
                FROM inscricao i
                INNER JOIN usuario u ON i.CPF = u.CPF
                WHERE i.cod_evento = ? AND i.status = 'ativa' AND i.presenca_confirmada = 1
                ORDER BY u.Nome";

        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, "i", $codEvento);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $dados = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $dados[] = [
                $row['Nome'],
                $row['Email'],
                $row['RA'] ?? '',
                $row['CPF'],
                $row['data_inscricao'],
                $row['presenca_confirmada'] ? 'Sim' : 'Não'
            ];
        }

        mysqli_stmt_close($stmt);
        mysqli_close($conexao);

        exportarArquivo(
            $dados,
            ['Nome', 'Email', 'RA', 'CPF', 'Data Inscrição', 'Presença Confirmada'],
            "lista_presenca_evento_{$codEvento}",
            $formato
        );
    } catch (Exception $e) {
        http_response_code(500);
        echo "Erro ao exportar: " . $e->getMessage();
    }
    exit;
}

// Exportar lista de inscritos
if (isset($_GET['action']) && $_GET['action'] === 'exportar_inscritos' && isset($_GET['cod_evento'])) {
    require_once __DIR__ . '/../BancoDados/conexao.php';

    $codEvento = intval($_GET['cod_evento']);
    $formato = $_GET['formato'] ?? 'csv';

    try {
        $sql = "SELECT u.Nome, u.Email, u.RA, u.CPF, i.data_inscricao
                FROM inscricao i
                INNER JOIN usuario u ON i.CPF = u.CPF
                WHERE i.cod_evento = ? AND i.status = 'ativa'
                ORDER BY i.data_inscricao DESC";

        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, "i", $codEvento);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $dados = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $dados[] = [
                $row['Nome'],
                $row['Email'],
                $row['RA'] ?? '',
                $row['CPF'],
                $row['data_inscricao']
            ];
        }

        mysqli_stmt_close($stmt);
        mysqli_close($conexao);

        exportarArquivo(
            $dados,
            ['Nome', 'Email', 'RA', 'CPF', 'Data Inscrição'],
            "lista_inscritos_evento_{$codEvento}",
            $formato
        );
    } catch (Exception $e) {
        http_response_code(500);
        echo "Erro ao exportar: " . $e->getMessage();
    }
    exit;
}

// Se for requisição POST para ação (confirmar presença ou excluir)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    // Verifica se é requisição de edição de dados (form-data)
    if (isset($_POST['action']) && $_POST['action'] === 'editar_dados') {
        require_once __DIR__ . '/../BancoDados/conexao.php';

        $cpfParticipante = $_POST['cpf'] ?? '';
        $nome = $_POST['nome'] ?? '';
        $email = $_POST['email'] ?? '';
        $ra = $_POST['ra'] ?? null;

        if (empty($cpfParticipante) || empty($nome) || empty($email)) {
            echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos']);
            exit;
        }

        try {
            // Atualiza dados do usuário
            $sql = "UPDATE usuario SET Nome = ?, Email = ?, RA = ? WHERE CPF = ?";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param($stmt, "ssss", $nome, $email, $ra, $cpfParticipante);

            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['sucesso' => true, 'mensagem' => 'Dados atualizados com sucesso']);
            } else {
                echo json_encode(['sucesso' => false, 'erro' => 'Erro ao atualizar dados']);
            }

            mysqli_stmt_close($stmt);
            mysqli_close($conexao);
        } catch (Exception $e) {
            echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
        }
        exit;
    }

    // Adicionar participante
    if (isset($_POST['action']) && $_POST['action'] === 'adicionar_participante') {
        require_once __DIR__ . '/../BancoDados/conexao.php';

        $codEvento = intval($_POST['cod_evento'] ?? 0);
        $cpf = $_POST['cpf'] ?? '';
        $nome = $_POST['nome'] ?? '';
        $email = $_POST['email'] ?? '';
        $ra = $_POST['ra'] ?? null;

        if (empty($cpf) || empty($nome) || empty($email) || $codEvento === 0) {
            echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos']);
            exit;
        }

        try {
            // Verifica se o usuário já existe
            $sqlCheck = "SELECT CPF FROM usuario WHERE CPF = ?";
            $stmtCheck = mysqli_prepare($conexao, $sqlCheck);
            mysqli_stmt_bind_param($stmtCheck, "s", $cpf);
            mysqli_stmt_execute($stmtCheck);
            $resultCheck = mysqli_stmt_get_result($stmtCheck);
            $usuarioExiste = mysqli_fetch_assoc($resultCheck);
            mysqli_stmt_close($stmtCheck);

            // Se não existe, cria o usuário
            if (!$usuarioExiste) {
                $sqlUser = "INSERT INTO usuario (CPF, Nome, Email, RA, Senha, Organizador) VALUES (?, ?, ?, ?, '', 0)";
                $stmtUser = mysqli_prepare($conexao, $sqlUser);
                mysqli_stmt_bind_param($stmtUser, "ssss", $cpf, $nome, $email, $ra);
                mysqli_stmt_execute($stmtUser);
                mysqli_stmt_close($stmtUser);
            }

            // Verifica se já está inscrito
            $sqlCheckInscricao = "SELECT CPF FROM inscricao WHERE CPF = ? AND cod_evento = ?";
            $stmtCheckInscricao = mysqli_prepare($conexao, $sqlCheckInscricao);
            mysqli_stmt_bind_param($stmtCheckInscricao, "si", $cpf, $codEvento);
            mysqli_stmt_execute($stmtCheckInscricao);
            $resultInscricao = mysqli_stmt_get_result($stmtCheckInscricao);

            if (mysqli_fetch_assoc($resultInscricao)) {
                mysqli_stmt_close($stmtCheckInscricao);
                mysqli_close($conexao);
                echo json_encode(['sucesso' => false, 'erro' => 'Participante já está inscrito neste evento']);
                exit;
            }
            mysqli_stmt_close($stmtCheckInscricao);

            // Inscreve no evento
            $sqlInscricao = "INSERT INTO inscricao (CPF, cod_evento, status) VALUES (?, ?, 'ativa')";
            $stmtInscricao = mysqli_prepare($conexao, $sqlInscricao);
            mysqli_stmt_bind_param($stmtInscricao, "si", $cpf, $codEvento);

            if (mysqli_stmt_execute($stmtInscricao)) {
                echo json_encode(['sucesso' => true, 'mensagem' => 'Participante adicionado com sucesso']);
            } else {
                echo json_encode(['sucesso' => false, 'erro' => 'Erro ao adicionar inscrição']);
            }

            mysqli_stmt_close($stmtInscricao);
            mysqli_close($conexao);
        } catch (Exception $e) {
            echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
        }
        exit;
    }

    // Adicionar colaborador
    if (isset($_POST['action']) && $_POST['action'] === 'adicionar_colaborador') {
        require_once __DIR__ . '/../BancoDados/conexao.php';

        $codEvento = intval($_POST['cod_evento'] ?? 0);
        $cpf = $_POST['cpf'] ?? '';
        $nome = $_POST['nome'] ?? '';
        $email = $_POST['email'] ?? '';
        $ra = $_POST['ra'] ?? null;

        if (empty($cpf) || empty($nome) || empty($email) || $codEvento === 0) {
            echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos']);
            exit;
        }

        try {
            // Verifica se o usuário já existe
            $sqlCheck = "SELECT CPF FROM usuario WHERE CPF = ?";
            $stmtCheck = mysqli_prepare($conexao, $sqlCheck);
            mysqli_stmt_bind_param($stmtCheck, "s", $cpf);
            mysqli_stmt_execute($stmtCheck);
            $resultCheck = mysqli_stmt_get_result($stmtCheck);
            $usuarioExiste = mysqli_fetch_assoc($resultCheck);
            mysqli_stmt_close($stmtCheck);

            // Se não existe, cria o usuário
            if (!$usuarioExiste) {
                $sqlUser = "INSERT INTO usuario (CPF, Nome, Email, RA, Senha, Organizador) VALUES (?, ?, ?, ?, '', 0)";
                $stmtUser = mysqli_prepare($conexao, $sqlUser);
                mysqli_stmt_bind_param($stmtUser, "ssss", $cpf, $nome, $email, $ra);
                mysqli_stmt_execute($stmtUser);
                mysqli_stmt_close($stmtUser);
            }

            // Verifica se já é organizador principal
            $sqlCheckOrg = "SELECT CPF FROM organiza WHERE CPF = ? AND cod_evento = ?";
            $stmtCheckOrg = mysqli_prepare($conexao, $sqlCheckOrg);
            mysqli_stmt_bind_param($stmtCheckOrg, "si", $cpf, $codEvento);
            mysqli_stmt_execute($stmtCheckOrg);
            $resultOrg = mysqli_stmt_get_result($stmtCheckOrg);

            if (mysqli_fetch_assoc($resultOrg)) {
                mysqli_stmt_close($stmtCheckOrg);
                mysqli_close($conexao);
                echo json_encode(['sucesso' => false, 'erro' => 'Esta pessoa já é o organizador principal do evento']);
                exit;
            }
            mysqli_stmt_close($stmtCheckOrg);

            // Verifica se já é colaborador
            $sqlCheckColab = "SELECT CPF FROM colaboradores_evento WHERE CPF = ? AND cod_evento = ?";
            $stmtCheckColab = mysqli_prepare($conexao, $sqlCheckColab);
            mysqli_stmt_bind_param($stmtCheckColab, "si", $cpf, $codEvento);
            mysqli_stmt_execute($stmtCheckColab);
            $resultColab = mysqli_stmt_get_result($stmtCheckColab);

            if (mysqli_fetch_assoc($resultColab)) {
                mysqli_stmt_close($stmtCheckColab);
                mysqli_close($conexao);
                echo json_encode(['sucesso' => false, 'erro' => 'Esta pessoa já é colaborador deste evento']);
                exit;
            }
            mysqli_stmt_close($stmtCheckColab);

            // Adiciona como colaborador
            $sqlColaborador = "INSERT INTO colaboradores_evento (CPF, cod_evento) VALUES (?, ?)";
            $stmtColaborador = mysqli_prepare($conexao, $sqlColaborador);
            mysqli_stmt_bind_param($stmtColaborador, "si", $cpf, $codEvento);

            if (mysqli_stmt_execute($stmtColaborador)) {
                echo json_encode(['sucesso' => true, 'mensagem' => 'Colaborador adicionado com sucesso']);
            } else {
                echo json_encode(['sucesso' => false, 'erro' => 'Erro ao adicionar colaborador']);
            }

            mysqli_stmt_close($stmtColaborador);
            mysqli_close($conexao);
        } catch (Exception $e) {
            echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
        }
        exit;
    }

    // Enviar notificação (aceita JSON ou POST)
    $jsonInput = file_get_contents('php://input');
    $jsonData = json_decode($jsonInput, true);
    
    // Determina se é requisição de notificação
    $isEnviarNotificacao = false;
    if (isset($_POST['action']) && $_POST['action'] === 'enviar_notificacao') {
        $isEnviarNotificacao = true;
    } elseif ($jsonData && isset($jsonData['action']) && $jsonData['action'] === 'enviar_notificacao') {
        $isEnviarNotificacao = true;
    }
    
    if ($isEnviarNotificacao) {
        // Garantir resposta JSON mesmo se houver avisos/erros gerados internamente
        header('Content-Type: application/json; charset=utf-8');
        // Captura qualquer saída acidental (warnings/notices) para evitar que HTML quebre o JSON
        ob_start();
        // Não exibir erros diretamente na saída (serão capturados no buffer)
        ini_set('display_errors', '0');
        error_reporting(0);

        require_once __DIR__ . '/../BancoDados/conexao.php';

        // Usa dados JSON se disponível, senão usa POST
        $dados = $jsonData ? $jsonData : $_POST;
        
        $codEvento = intval($dados['cod_evento'] ?? 0);
        $titulo = $dados['titulo'] ?? '';
        $conteudo = $dados['conteudo'] ?? '';
        $destinatarios = is_array($dados['destinatarios'] ?? null) ? $dados['destinatarios'] : json_decode($dados['destinatarios'] ?? '[]', true);

        if (empty($titulo) || empty($conteudo) || empty($destinatarios)) {
            echo json_encode(['sucesso' => false, 'erro' => 'dados_incompletos', 'debug' => ['titulo' => $titulo, 'conteudo' => $conteudo, 'destinatarios' => $destinatarios]]);
            exit;
        }

        try {
            // Verifica permissão do organizador
            $cpfOrganizador = $_SESSION['cpf'];
            $consultaPermissao = "SELECT 1 FROM organiza WHERE cod_evento = ? AND CPF = ?
                                  UNION
                                  SELECT 1 FROM colaboradores_evento WHERE cod_evento = ? AND CPF = ?
                                  LIMIT 1";
            $stmtPermissao = mysqli_prepare($conexao, $consultaPermissao);
            mysqli_stmt_bind_param($stmtPermissao, "isis", $codEvento, $cpfOrganizador, $codEvento, $cpfOrganizador);
            mysqli_stmt_execute($stmtPermissao);
            $resultadoPermissao = mysqli_stmt_get_result($stmtPermissao);

            if (!mysqli_fetch_assoc($resultadoPermissao)) {
                mysqli_stmt_close($stmtPermissao);
                mysqli_close($conexao);
                echo json_encode(['sucesso' => false, 'erro' => 'sem_permissao']);
                exit;
            }
            mysqli_stmt_close($stmtPermissao);

            $totalEnviadas = 0;
            $cpfRemetente = $_SESSION['cpf'];
            
            // Buscar nome do organizador que está enviando
            $sqlNomeOrg = "SELECT Nome FROM usuario WHERE CPF = ? LIMIT 1";
            $stmtNomeOrg = mysqli_prepare($conexao, $sqlNomeOrg);
            mysqli_stmt_bind_param($stmtNomeOrg, "s", $cpfRemetente);
            mysqli_stmt_execute($stmtNomeOrg);
            $resultNomeOrg = mysqli_stmt_get_result($stmtNomeOrg);
            $nomeOrganizador = '';
            if ($rowOrg = mysqli_fetch_assoc($resultNomeOrg)) {
                $nomeOrganizador = $rowOrg['Nome'];
            }
            mysqli_stmt_close($stmtNomeOrg);
            
            // Formata mensagem com CPF do remetente: CPF|||NOME|||TÍTULO|||CONTEÚDO
            $mensagemFormatada = $cpfRemetente . '|||' . $nomeOrganizador . '|||' . $titulo . '|||' . $conteudo;
            
            $sqlNotificacao = "INSERT INTO notificacoes (CPF, titulo, tipo, mensagem, cod_evento, data_criacao, lida) VALUES (?, ?, 'mensagem_organizador', ?, ?, NOW(), 0)";
            $stmtNotificacao = mysqli_prepare($conexao, $sqlNotificacao);

            // Verificar se cada CPF é um usuário válido (não precisa estar inscrito)
            $sqlVerificarUsuario = "SELECT CPF FROM usuario WHERE CPF = ? LIMIT 1";
            $stmtVerificarUsuario = mysqli_prepare($conexao, $sqlVerificarUsuario);

            foreach ($destinatarios as $cpf) {
                // Verificar se é um usuário válido
                mysqli_stmt_bind_param($stmtVerificarUsuario, "s", $cpf);
                mysqli_stmt_execute($stmtVerificarUsuario);
                $resultVerificar = mysqli_stmt_get_result($stmtVerificarUsuario);

                if (mysqli_fetch_assoc($resultVerificar)) {
                    // É um usuário válido, pode enviar mensagem
                    // Bind: CPF (s), titulo (s), mensagem (s), cod_evento (i)
                    mysqli_stmt_bind_param($stmtNotificacao, "sssi", $cpf, $titulo, $mensagemFormatada, $codEvento);
                    if (mysqli_stmt_execute($stmtNotificacao)) {
                        $totalEnviadas++;
                    }
                }
                mysqli_free_result($resultVerificar);
            }

            mysqli_stmt_close($stmtVerificarUsuario);
            mysqli_stmt_close($stmtNotificacao);
            mysqli_close($conexao);

            // Limpa buffer de saída (não envia HTML inesperado) e retorna JSON limpo
            ob_end_clean();
            echo json_encode(['sucesso' => true, 'total_enviadas' => $totalEnviadas]);
        } catch (Exception $e) {
            // Captura buffer e inclui no debug (sem expor em produção), mas garante JSON válido
            $captured = '';
            if (ob_get_length() !== false) {
                $captured = ob_get_clean();
            }
            $resp = ['sucesso' => false, 'erro' => $e->getMessage()];
            if (!empty($captured)) $resp['debug_output'] = $captured;
            echo json_encode($resp);
        }
        exit;
    }

    // Enviar notificação para CPF específico (permite responder mensagens de usuários não inscritos)
    $isEnviarNotificacaoCpf = false;
    if (isset($_POST['action']) && $_POST['action'] === 'enviar_notificacao_cpf') {
        $isEnviarNotificacaoCpf = true;
    } elseif ($jsonData && isset($jsonData['action']) && $jsonData['action'] === 'enviar_notificacao_cpf') {
        $isEnviarNotificacaoCpf = true;
    }
    
    if ($isEnviarNotificacaoCpf) {
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/../BancoDados/conexao.php';

        // Usa dados JSON se disponível, senão usa POST
        $dados = $jsonData ? $jsonData : $_POST;
        
        $codEvento = intval($dados['cod_evento'] ?? 0);
        $titulo = $dados['titulo'] ?? '';
        $conteudo = $dados['conteudo'] ?? '';
        $cpfDestinatario = trim($dados['cpf_destinatario'] ?? '');

        if (empty($titulo) || empty($conteudo) || empty($cpfDestinatario)) {
            echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        try {
            // Verifica permissão do organizador
            $cpfOrganizador = $_SESSION['cpf'];
            $consultaPermissao = "SELECT 1 FROM organiza WHERE cod_evento = ? AND CPF = ?
                                  UNION
                                  SELECT 1 FROM colaboradores_evento WHERE cod_evento = ? AND CPF = ?
                                  LIMIT 1";
            $stmtPermissao = mysqli_prepare($conexao, $consultaPermissao);
            mysqli_stmt_bind_param($stmtPermissao, "isis", $codEvento, $cpfOrganizador, $codEvento, $cpfOrganizador);
            mysqli_stmt_execute($stmtPermissao);
            $resultadoPermissao = mysqli_stmt_get_result($stmtPermissao);

            if (!mysqli_fetch_assoc($resultadoPermissao)) {
                mysqli_stmt_close($stmtPermissao);
                mysqli_close($conexao);
                echo json_encode(['sucesso' => false, 'erro' => 'sem_permissao'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            mysqli_stmt_close($stmtPermissao);

            // Verificar se o CPF é um usuário válido
            $sqlVerificarUsuario = "SELECT CPF, Nome FROM usuario WHERE CPF = ? LIMIT 1";
            $stmtVerificarUsuario = mysqli_prepare($conexao, $sqlVerificarUsuario);
            mysqli_stmt_bind_param($stmtVerificarUsuario, "s", $cpfDestinatario);
            mysqli_stmt_execute($stmtVerificarUsuario);
            $resultVerificar = mysqli_stmt_get_result($stmtVerificarUsuario);
            $usuario = mysqli_fetch_assoc($resultVerificar);
            mysqli_stmt_close($stmtVerificarUsuario);

            if (!$usuario) {
                mysqli_close($conexao);
                echo json_encode(['sucesso' => false, 'erro' => 'usuario_nao_encontrado', 'mensagem' => 'CPF não encontrado no sistema'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            // Verifica se é uma resposta (quando vem do modal de resposta)
            $ehResposta = isset($_POST['eh_resposta']) && $_POST['eh_resposta'] === '1';
            $mensagemOriginal = trim($_POST['mensagem_original'] ?? '');

            // Busca dados do remetente (organizador)
            $sqlRemetente = "SELECT Nome FROM usuario WHERE CPF = ? LIMIT 1";
            $stmtRemetente = mysqli_prepare($conexao, $sqlRemetente);
            mysqli_stmt_bind_param($stmtRemetente, "s", $cpfOrganizador);
            mysqli_stmt_execute($stmtRemetente);
            $resultRemetente = mysqli_stmt_get_result($stmtRemetente);
            $dadosRemetente = mysqli_fetch_assoc($resultRemetente) ?: ['Nome' => 'Organizador'];
            mysqli_stmt_close($stmtRemetente);

            // Busca dados do evento
            $sqlEvento = "SELECT nome FROM evento WHERE cod_evento = ? LIMIT 1";
            $stmtEvento = mysqli_prepare($conexao, $sqlEvento);
            mysqli_stmt_bind_param($stmtEvento, "i", $codEvento);
            mysqli_stmt_execute($stmtEvento);
            $resultEvento = mysqli_stmt_get_result($stmtEvento);
            $dadosEvento = mysqli_fetch_assoc($resultEvento) ?: ['nome' => 'Evento'];
            mysqli_stmt_close($stmtEvento);

            // Formata mensagem no mesmo formato das mensagens de participante
            $nomeRemetente = $dadosRemetente['Nome'];
            $nomeEvento = $dadosEvento['nome'];

            // Armazena APENAS a mensagem atual (não inclui mensagens anteriores)
            // A thread completa será buscada quando necessário
            $mensagemCompleta = $conteudo;

            // Calcula tamanho total considerando o formato CPF|||NOME|||EVENTO|||MENSAGEM
            $tamanhoBase = mb_strlen($cpfOrganizador . '|||' . $nomeRemetente . '|||' . $nomeEvento . '|||');
            $tamanhoDisponivel = 255 - $tamanhoBase;

            // Se a mensagem for muito longa, trunca
            if (mb_strlen($mensagemCompleta) > $tamanhoDisponivel) {
                $mensagemCompleta = mb_substr($mensagemCompleta, 0, $tamanhoDisponivel);
            }

            // Formato: CPF|||NOME|||EVENTO|||MENSAGEM
            $mensagemFormatada = $cpfOrganizador . '|||' . $nomeRemetente . '|||' . $nomeEvento . '|||' . $mensagemCompleta;

            // Define título e tipo
            $tituloNotificacao = $ehResposta ? 'Resposta de organizador' : $titulo;
            $tipoNotificacao = 'mensagem_participante';

            // Enviar notificação com tipo e cod_evento
            $sqlNotificacao = "INSERT INTO notificacoes (CPF, titulo, tipo, mensagem, cod_evento, data_criacao, lida) VALUES (?, ?, ?, ?, ?, NOW(), 0)";
            $stmtNotificacao = mysqli_prepare($conexao, $sqlNotificacao);
            mysqli_stmt_bind_param($stmtNotificacao, "ssssi", $cpfDestinatario, $tituloNotificacao, $tipoNotificacao, $mensagemFormatada, $codEvento);

            if (mysqli_stmt_execute($stmtNotificacao)) {
                mysqli_stmt_close($stmtNotificacao);
                mysqli_close($conexao);
                echo json_encode(['sucesso' => true, 'total_enviadas' => 1, 'nome_destinatario' => $usuario['Nome']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                mysqli_stmt_close($stmtNotificacao);
                mysqli_close($conexao);
                echo json_encode(['sucesso' => false, 'erro' => 'falha_envio'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        } catch (Exception $e) {
            echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        exit;
    }

    // Requisições JSON (confirmar presença, excluir, emitir certificado organização)
    $dadosJSON = file_get_contents('php://input');
    $dados = json_decode($dadosJSON, true);

    if (!isset($dados['action']) || !isset($dados['cod_evento']) || !isset($dados['cpf'])) {
        echo json_encode(['sucesso' => false, 'erro' => 'dados_incompletos']);
        exit;
    }

    require_once __DIR__ . '/../BancoDados/conexao.php';

    $cpfOrganizador = $_SESSION['cpf'];
    $codEvento = intval($dados['cod_evento']);
    $cpfParticipante = $dados['cpf'];
    $acao = $dados['action'];

    try {
        // Verifica permissão
        $consultaPermissao = "SELECT 1 FROM organiza WHERE cod_evento = ? AND CPF = ?
                              UNION
                              SELECT 1 FROM colaboradores_evento WHERE cod_evento = ? AND CPF = ?
                              LIMIT 1";

        $stmtPermissao = mysqli_prepare($conexao, $consultaPermissao);
        mysqli_stmt_bind_param($stmtPermissao, "isis", $codEvento, $cpfOrganizador, $codEvento, $cpfOrganizador);
        mysqli_stmt_execute($stmtPermissao);
        $resultadoPermissao = mysqli_stmt_get_result($stmtPermissao);

        if (!mysqli_fetch_assoc($resultadoPermissao)) {
            mysqli_stmt_close($stmtPermissao);
            mysqli_close($conexao);
            echo json_encode(['sucesso' => false, 'erro' => 'sem_permissao']);
            exit;
        }

        mysqli_stmt_close($stmtPermissao);

        // Executa ação
        if ($acao === 'confirmar_presenca') {
            $consultaUpdate = "UPDATE inscricao 
                               SET presenca_confirmada = 1 
                               WHERE cod_evento = ? AND CPF = ? AND status = 'ativa'";
            $mensagemSucesso = 'Presença confirmada com sucesso';

            $stmtUpdate = mysqli_prepare($conexao, $consultaUpdate);

            if ($stmtUpdate) {
                mysqli_stmt_bind_param($stmtUpdate, "is", $codEvento, $cpfParticipante);

                if (mysqli_stmt_execute($stmtUpdate)) {
                    $linhasAfetadas = mysqli_stmt_affected_rows($stmtUpdate);
                    mysqli_stmt_close($stmtUpdate);

                    if ($linhasAfetadas > 0) {
                        echo json_encode(['sucesso' => true, 'mensagem' => $mensagemSucesso]);
                    } else {
                        echo json_encode(['sucesso' => false, 'erro' => 'nenhuma_alteracao']);
                    }
                } else {
                    echo json_encode(['sucesso' => false, 'erro' => 'falha_execucao', 'detalhe' => mysqli_stmt_error($stmtUpdate)]);
                    mysqli_stmt_close($stmtUpdate);
                }
            } else {
                echo json_encode(['sucesso' => false, 'erro' => 'falha_preparacao', 'detalhe' => mysqli_error($conexao)]);
            }

            mysqli_close($conexao);
            exit;
        } elseif ($acao === 'emitir_certificado') {
            // Verifica se a presença está confirmada e busca dados do participante
            $consultaVerifica = "SELECT i.presenca_confirmada, u.Nome, u.Email 
                                FROM inscricao i
                                JOIN usuario u ON i.CPF = u.CPF
                                WHERE i.cod_evento = ? AND i.CPF = ? AND i.status = 'ativa'";
            $stmtVerifica = mysqli_prepare($conexao, $consultaVerifica);
            mysqli_stmt_bind_param($stmtVerifica, "is", $codEvento, $cpfParticipante);
            mysqli_stmt_execute($stmtVerifica);
            $resultVerifica = mysqli_stmt_get_result($stmtVerifica);
            $inscricao = mysqli_fetch_assoc($resultVerifica);
            mysqli_stmt_close($stmtVerifica);

            if (!$inscricao) {
                mysqli_close($conexao);
                echo json_encode(['sucesso' => false, 'erro' => 'participante_nao_encontrado']);
                exit;
            }

            if ($inscricao['presenca_confirmada'] != 1) {
                mysqli_close($conexao);
                echo json_encode(['sucesso' => false, 'erro' => 'presenca_nao_confirmada']);
                exit;
            }

            // Buscar dados do evento (incluindo tipo_certificado)
            $consultaEvento = "SELECT nome, inicio, conclusao, lugar, duracao, tipo_certificado FROM evento WHERE cod_evento = ?";
            $stmtEvento = mysqli_prepare($conexao, $consultaEvento);
            mysqli_stmt_bind_param($stmtEvento, "i", $codEvento);
            mysqli_stmt_execute($stmtEvento);
            $resultEvento = mysqli_stmt_get_result($stmtEvento);
            $dadosEvento = mysqli_fetch_assoc($resultEvento);
            mysqli_stmt_close($stmtEvento);

            if (!$dadosEvento) {
                echo json_encode(['sucesso' => false, 'erro' => 'evento_nao_encontrado']);
                mysqli_close($conexao);
                exit;
            }

            // Buscar nome do organizador
            $consultaOrganizador = "SELECT u.Nome FROM organiza o 
                                    JOIN usuario u ON o.CPF = u.CPF 
                                    WHERE o.cod_evento = ? LIMIT 1";
            $stmtOrg = mysqli_prepare($conexao, $consultaOrganizador);
            mysqli_stmt_bind_param($stmtOrg, "i", $codEvento);
            mysqli_stmt_execute($stmtOrg);
            $resultOrg = mysqli_stmt_get_result($stmtOrg);
            $dadosOrganizador = mysqli_fetch_assoc($resultOrg);
            mysqli_stmt_close($stmtOrg);

            // Gerar certificado usando o sistema de certificação
            try {
                require_once __DIR__ . '/../Certificacao/config.php';
                require_once __DIR__ . '/../Certificacao/ProcessadorTemplate.php';
                require_once __DIR__ . '/../Certificacao/RepositorioCertificados.php';

                $autoload = __DIR__ . '/../Certificacao/bibliotecas/vendor/autoload.php';
                $processador = new \CEU\Certificacao\ProcessadorTemplate($autoload);
                $repositorio = new \CEU\Certificacao\RepositorioCertificados($conexao);
                $repositorio->garantirEsquema();

                // Verifica se já existe certificado para este CPF + evento
                $certificadoExistente = $repositorio->buscarPorCpfEvento($cpfParticipante, $codEvento);
                $codigoVerificacao = $certificadoExistente['cod_verificacao'] ?? $repositorio->gerarCodigoUnico(8);
                $excluirPdfAnterior = !empty($certificadoExistente); // Flag para deletar PDF antigo se reatualizar

                // Dados para o template
                $dados = [
                    'NomeParticipante' => $inscricao['Nome'],
                    'Email' => $inscricao['Email'],
                    'NumeroCPF' => $cpfParticipante,
                    'NomeEvento' => $dadosEvento['nome'],
                    'Categoria' => strtolower($dadosEvento['tipo_certificado'] ?? 'sem certificacao'),
                    'NomeOrganizador' => $dadosOrganizador['Nome'] ?? 'CEU',
                    'LocalEvento' => $dadosEvento['lugar'] ?? 'Online',
                    'Data' => date('d/m/Y', strtotime($dadosEvento['inicio'])),
                    'DataEvento' => date('d/m/Y', strtotime($dadosEvento['inicio'])),
                    'CargaHoraria' => $dadosEvento['duracao'] ? $dadosEvento['duracao'] . ' horas' : 'A definir',
                    'TipoCertificado' => $dadosEvento['tipo_certificado'] ?? 'Sem certificacao',
                    'CodigoVerificacao' => $codigoVerificacao,
                    'CodigoAutenticador' => $codigoVerificacao,
                    'TipoParticipacao' => 'Participante'
                ];

                // Procura por templates em ordem de prioridade
                $templatesPath = __DIR__ . '/../Certificacao/templates/';
                $possiveisTemplates = [
                    'ModeloExemplo.pptx',
                    'ModeloExemplo.docx',
                    'certificado_participante.pptx',
                    'certificado_participante.docx',
                    'certificado_padrao.pptx',
                    'certificado_padrao.docx',
                    'certificado_participante.odt',
                    'certificado_padrao.odt'
                ];

                $templatePath = null;
                foreach ($possiveisTemplates as $template) {
                    $caminho = $templatesPath . $template;
                    if (file_exists($caminho)) {
                        $templatePath = $caminho;
                        break;
                    }
                }

                if (!$templatePath) {
                    throw new Exception('Nenhum template de certificado encontrado na pasta templates/');
                }

                // Diretório de saída
                $dirCertificados = __DIR__ . '/../Certificacao/certificados/';
                if (!is_dir($dirCertificados)) {
                    mkdir($dirCertificados, 0755, true);
                }

                // Se certificado existe, remove o arquivo antigo (participante)
                if ($excluirPdfAnterior && !empty($certificadoExistente['arquivo'])) {
                    $caminhoAntigoCompleto = __DIR__ . '/../' . $certificadoExistente['arquivo'];
                    if (file_exists($caminhoAntigoCompleto)) {
                        @unlink($caminhoAntigoCompleto);
                    }
                }

                $nomeArquivo = 'Certificado_CEU_' . $codigoVerificacao . '.pdf';
                $caminhoCompleto = $dirCertificados . $nomeArquivo;
                $caminhoRelativo = 'Certificacao/certificados/' . $nomeArquivo;

                // Gerar PDF em background (não-bloqueante)
                $resultado = $processador->gerarPdfDeModeloBackground($templatePath, $dados, $caminhoCompleto);

                if ($resultado['success']) {
                    // Salvar no repositório com status pending
                    $repositorio->salvarCertificado(
                        $codigoVerificacao,
                        $caminhoRelativo,
                        'participante',
                        'participacao',
                        $dados,
                        $cpfParticipante,
                        $codEvento
                    );

                    // Atualizar status no banco
                    $consultaUpdate = "UPDATE inscricao 
                                       SET certificado_emitido = 1 
                                       WHERE cod_evento = ? AND CPF = ? AND status = 'ativa'";
                    $stmtUpdate = mysqli_prepare($conexao, $consultaUpdate);
                    mysqli_stmt_bind_param($stmtUpdate, "is", $codEvento, $cpfParticipante);
                    mysqli_stmt_execute($stmtUpdate);
                    mysqli_stmt_close($stmtUpdate);

                    echo json_encode([
                        'sucesso' => true,
                        'mensagem' => 'Certificado emitido com sucesso',
                        'codigo_verificacao' => $codigoVerificacao,
                        'arquivo' => $caminhoRelativo
                    ]);
                } else {
                    echo json_encode(['sucesso' => false, 'erro' => 'falha_geracao_pdf', 'detalhe' => $resultado['error'] ?? 'Erro desconhecido']);
                }
            } catch (Throwable $e) {
                echo json_encode(['sucesso' => false, 'erro' => 'erro_certificacao', 'detalhe' => $e->getMessage()]);
            }
            mysqli_close($conexao);
            exit;
        } elseif ($acao === 'excluir') {
            $consultaUpdate = "UPDATE inscricao 
                               SET status = 'cancelada' 
                               WHERE cod_evento = ? AND CPF = ? AND status = 'ativa'";
            $mensagemSucesso = 'Participante excluído com sucesso';

            $stmtUpdate = mysqli_prepare($conexao, $consultaUpdate);

            if ($stmtUpdate) {
                mysqli_stmt_bind_param($stmtUpdate, "is", $codEvento, $cpfParticipante);

                if (mysqli_stmt_execute($stmtUpdate)) {
                    $linhasAfetadas = mysqli_stmt_affected_rows($stmtUpdate);
                    mysqli_stmt_close($stmtUpdate);

                    if ($linhasAfetadas > 0) {
                        echo json_encode(['sucesso' => true, 'mensagem' => $mensagemSucesso]);
                    } else {
                        echo json_encode(['sucesso' => false, 'erro' => 'nenhuma_alteracao']);
                    }
                } else {
                    echo json_encode(['sucesso' => false, 'erro' => 'falha_execucao', 'detalhe' => mysqli_stmt_error($stmtUpdate)]);
                    mysqli_stmt_close($stmtUpdate);
                }
            } else {
                echo json_encode(['sucesso' => false, 'erro' => 'falha_preparacao', 'detalhe' => mysqli_error($conexao)]);
            }

            mysqli_close($conexao);
            exit;
        } elseif ($acao === 'confirmar_presenca_organizacao') {
            // Verifica se é organizador ou colaborador
            $tipoMembro = null;

            // Verifica se é organizador principal
            $sqlVerificaOrg = "SELECT 1 FROM organiza WHERE cod_evento = ? AND CPF = ?";
            $stmtVerificaOrg = mysqli_prepare($conexao, $sqlVerificaOrg);
            mysqli_stmt_bind_param($stmtVerificaOrg, "is", $codEvento, $cpfParticipante);
            mysqli_stmt_execute($stmtVerificaOrg);
            $resultVerificaOrg = mysqli_stmt_get_result($stmtVerificaOrg);

            if (mysqli_fetch_assoc($resultVerificaOrg)) {
                $tipoMembro = 'organizador';
            } else {
                $tipoMembro = 'colaborador';
            }
            mysqli_stmt_close($stmtVerificaOrg);

            // Atualiza a tabela correta
            if ($tipoMembro === 'organizador') {
                $consultaUpdate = "UPDATE organiza 
                                   SET presenca_confirmada = 1 
                                   WHERE cod_evento = ? AND CPF = ?";
            } else {
                $consultaUpdate = "UPDATE colaboradores_evento 
                                   SET presenca_confirmada = 1 
                                   WHERE cod_evento = ? AND CPF = ?";
            }

            $mensagemSucesso = 'Presença confirmada com sucesso';

            $stmtUpdate = mysqli_prepare($conexao, $consultaUpdate);

            if ($stmtUpdate) {
                mysqli_stmt_bind_param($stmtUpdate, "is", $codEvento, $cpfParticipante);

                if (mysqli_stmt_execute($stmtUpdate)) {
                    $linhasAfetadas = mysqli_stmt_affected_rows($stmtUpdate);
                    mysqli_stmt_close($stmtUpdate);

                    if ($linhasAfetadas > 0) {
                        echo json_encode(['sucesso' => true, 'mensagem' => $mensagemSucesso]);
                    } else {
                        echo json_encode(['sucesso' => false, 'erro' => 'nenhuma_alteracao']);
                    }
                } else {
                    echo json_encode(['sucesso' => false, 'erro' => 'falha_execucao', 'detalhe' => mysqli_stmt_error($stmtUpdate)]);
                    mysqli_stmt_close($stmtUpdate);
                }
            } else {
                echo json_encode(['sucesso' => false, 'erro' => 'falha_preparacao', 'detalhe' => mysqli_error($conexao)]);
            }

            mysqli_close($conexao);
            exit;
        } elseif ($acao === 'emitir_certificado_organizacao') {
            // Verifica se é organizador ou colaborador e busca dados
            $tipoMembro = null;
            $dadosPresenca = null;

            // Primeiro verifica se é organizador principal
            $consultaOrg = "SELECT o.presenca_confirmada, u.Nome, u.Email 
                            FROM organiza o
                            JOIN usuario u ON o.CPF = u.CPF
                            WHERE o.cod_evento = ? AND o.CPF = ?";
            $stmtOrg = mysqli_prepare($conexao, $consultaOrg);
            mysqli_stmt_bind_param($stmtOrg, "is", $codEvento, $cpfParticipante);
            mysqli_stmt_execute($stmtOrg);
            $resultOrg = mysqli_stmt_get_result($stmtOrg);
            $dadosOrg = mysqli_fetch_assoc($resultOrg);
            mysqli_stmt_close($stmtOrg);

            if ($dadosOrg) {
                $tipoMembro = 'organizador';
                $dadosPresenca = $dadosOrg;
            } else {
                // Se não for organizador, verifica se é colaborador
                $consultaColab = "SELECT ce.presenca_confirmada, u.Nome, u.Email 
                                  FROM colaboradores_evento ce
                                  JOIN usuario u ON ce.CPF = u.CPF
                                  WHERE ce.cod_evento = ? AND ce.CPF = ?";
                $stmtColab = mysqli_prepare($conexao, $consultaColab);
                mysqli_stmt_bind_param($stmtColab, "is", $codEvento, $cpfParticipante);
                mysqli_stmt_execute($stmtColab);
                $resultColab = mysqli_stmt_get_result($stmtColab);
                $dadosColab = mysqli_fetch_assoc($resultColab);
                mysqli_stmt_close($stmtColab);

                if ($dadosColab) {
                    $tipoMembro = 'colaborador';
                    $dadosPresenca = $dadosColab;
                }
            }

            if (!$dadosPresenca) {
                echo json_encode(['sucesso' => false, 'erro' => 'membro_nao_encontrado', 'mensagem' => 'Membro da organização não encontrado']);
                mysqli_close($conexao);
                exit;
            }

            if (!$dadosPresenca['presenca_confirmada']) {
                echo json_encode(['sucesso' => false, 'erro' => 'presenca_nao_confirmada', 'mensagem' => 'A presença precisa ser confirmada antes de emitir o certificado']);
                mysqli_close($conexao);
                exit;
            }

            // Buscar dados do evento (incluindo tipo_certificado)
            $consultaEvento = "SELECT nome, inicio, conclusao, lugar, duracao, tipo_certificado FROM evento WHERE cod_evento = ?";
            $stmtEvento = mysqli_prepare($conexao, $consultaEvento);
            mysqli_stmt_bind_param($stmtEvento, "i", $codEvento);
            mysqli_stmt_execute($stmtEvento);
            $resultEvento = mysqli_stmt_get_result($stmtEvento);
            $dadosEvento = mysqli_fetch_assoc($resultEvento);
            mysqli_stmt_close($stmtEvento);

            if (!$dadosEvento) {
                echo json_encode(['sucesso' => false, 'erro' => 'evento_nao_encontrado']);
                mysqli_close($conexao);
                exit;
            }

            // Buscar nome do organizador
            $consultaOrganizador = "SELECT u.Nome FROM organiza o 
                                    JOIN usuario u ON o.CPF = u.CPF 
                                    WHERE o.cod_evento = ? LIMIT 1";
            $stmtOrg = mysqli_prepare($conexao, $consultaOrganizador);
            mysqli_stmt_bind_param($stmtOrg, "i", $codEvento);
            mysqli_stmt_execute($stmtOrg);
            $resultOrg = mysqli_stmt_get_result($stmtOrg);
            $dadosOrganizador = mysqli_fetch_assoc($resultOrg);
            mysqli_stmt_close($stmtOrg);

            // Gerar certificado usando o sistema de certificação
            try {
                require_once __DIR__ . '/../Certificacao/config.php';
                require_once __DIR__ . '/../Certificacao/ProcessadorTemplate.php';
                require_once __DIR__ . '/../Certificacao/RepositorioCertificados.php';

                $autoload = __DIR__ . '/../Certificacao/bibliotecas/vendor/autoload.php';
                $processador = new \CEU\Certificacao\ProcessadorTemplate($autoload);
                $repositorio = new \CEU\Certificacao\RepositorioCertificados($conexao);
                $repositorio->garantirEsquema();

                // Verifica se já existe certificado para este CPF + evento
                $certificadoExistente = $repositorio->buscarPorCpfEvento($cpfParticipante, $codEvento);
                $codigoVerificacao = $certificadoExistente['cod_verificacao'] ?? $repositorio->gerarCodigoUnico(8);
                $excluirPdfAnterior = !empty($certificadoExistente); // Flag para deletar PDF antigo se reatualizar

                // Dados para o template
                $dados = [
                    'NomeParticipante' => $dadosPresenca['Nome'],
                    'Email' => $dadosPresenca['Email'],
                    'NumeroCPF' => $cpfParticipante,
                    'NomeEvento' => $dadosEvento['nome'],
                    'Categoria' => strtolower($dadosEvento['tipo_certificado'] ?? 'sem certificacao'),
                    'NomeOrganizador' => $dadosOrganizador['Nome'] ?? 'CEU',
                    'LocalEvento' => $dadosEvento['lugar'] ?? 'Online',
                    'Data' => date('d/m/Y', strtotime($dadosEvento['inicio'])),
                    'DataEvento' => date('d/m/Y', strtotime($dadosEvento['inicio'])),
                    'CargaHoraria' => $dadosEvento['duracao'] ? $dadosEvento['duracao'] . ' horas' : 'A definir',
                    'TipoCertificado' => $dadosEvento['tipo_certificado'] ?? 'Sem certificacao',
                    'CodigoVerificacao' => $codigoVerificacao,
                    'CodigoAutenticador' => $codigoVerificacao,
                    'TipoParticipacao' => 'Organizador'
                ];

                // Procura por templates em ordem de prioridade
                $templatesPath = __DIR__ . '/../Certificacao/templates/';
                $possiveisTemplates = [
                    'ModeloExemploOrganizador.pptx',
                    'ModeloExemploOrganizador.docx',
                    'certificado_organizador.pptx',
                    'certificado_organizador.docx',
                    'certificado_padrao.pptx',
                    'certificado_padrao.docx',
                    'ModeloExemplo.pptx',
                    'certificado_organizador.odt',
                    'certificado_padrao.odt'
                ];

                $templatePath = null;
                foreach ($possiveisTemplates as $template) {
                    $caminho = $templatesPath . $template;
                    if (file_exists($caminho)) {
                        $templatePath = $caminho;
                        break;
                    }
                }

                if (!$templatePath) {
                    throw new Exception('Nenhum template de certificado encontrado na pasta templates/');
                }

                // Diretório de saída
                $dirCertificados = __DIR__ . '/../Certificacao/certificados/';
                if (!is_dir($dirCertificados)) {
                    mkdir($dirCertificados, 0755, true);
                }

                // Se certificado existe, remove o arquivo antigo
                if ($excluirPdfAnterior && !empty($certificadoExistente['arquivo'])) {
                    $caminhoAntigoCompleto = __DIR__ . '/../' . $certificadoExistente['arquivo'];
                    if (file_exists($caminhoAntigoCompleto)) {
                        @unlink($caminhoAntigoCompleto);
                    }
                }

                $nomeArquivo = 'Certificado_CEU_' . $codigoVerificacao . '.pdf';
                $caminhoCompleto = $dirCertificados . $nomeArquivo;
                $caminhoRelativo = 'Certificacao/certificados/' . $nomeArquivo;

                // Gerar PDF em background (não-bloqueante)
                $resultado = $processador->gerarPdfDeModeloBackground($templatePath, $dados, $caminhoCompleto);

                if ($resultado['success']) {
                    // Salvar no repositório
                    $repositorio->salvarCertificado(
                        $codigoVerificacao,
                        $caminhoRelativo,
                        'organizador',
                        'organizacao',
                        $dados,
                        $cpfParticipante,
                        $codEvento
                    );

                    // Atualizar status no banco - na tabela correta
                    if ($tipoMembro === 'organizador') {
                        $consultaUpdate = "UPDATE organiza 
                                           SET certificado_emitido = 1 
                                           WHERE cod_evento = ? AND CPF = ?";
                    } else {
                        $consultaUpdate = "UPDATE colaboradores_evento 
                                           SET certificado_emitido = 1 
                                           WHERE cod_evento = ? AND CPF = ?";
                    }

                    $stmtUpdate = mysqli_prepare($conexao, $consultaUpdate);
                    mysqli_stmt_bind_param($stmtUpdate, "is", $codEvento, $cpfParticipante);
                    mysqli_stmt_execute($stmtUpdate);
                    mysqli_stmt_close($stmtUpdate);

                    echo json_encode([
                        'sucesso' => true,
                        'mensagem' => 'Certificado emitido com sucesso',
                        'codigo_verificacao' => $codigoVerificacao,
                        'arquivo' => $caminhoRelativo
                    ]);
                } else {
                    echo json_encode(['sucesso' => false, 'erro' => 'falha_geracao_pdf', 'detalhe' => $resultado['error'] ?? 'Erro desconhecido']);
                }
            } catch (Throwable $e) {
                echo json_encode(['sucesso' => false, 'erro' => 'erro_certificacao', 'detalhe' => $e->getMessage()]);
            }
            mysqli_close($conexao);
            exit;
        } else {
            echo json_encode(['sucesso' => false, 'erro' => 'acao_invalida']);
            exit;
        }
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['sucesso' => false, 'erro' => 'erro_interno', 'detalhe' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Evento</title>
    <link rel="stylesheet" href="../styleGlobal.css" />
    <link rel="stylesheet" href="../styleGlobalMobile.css" media="(max-width: 767px)" />
</head>
<style>
    a,
    button {
        cursor: pointer;
        text-decoration: none;
        border: none;
        background: none;
        font-family: inherit;
        padding: 0;
        color: inherit;
    }

    img {
        max-width: 100%;
        display: block;
    }

    /* Força largura consistente em todas as situações */
    body .container-lista,
    #main-content .container-lista,
    div .container-lista {
        width: 85vw !important;
        max-width: 1600px !important;
        margin: 50px auto !important;
        padding: 40px 48px !important;
        background-color: var(--caixas);
        border-radius: 16px;
        box-shadow: 0px 4px 20px 0px rgba(0, 0, 0, 0.6);
        display: flex;
        flex-direction: column;
        gap: 25px;
        color: var(--preto);
        box-sizing: border-box !important;
    }

    /* Quando o menu lateral está aberto */
    #main-content.shifted .container-lista,
    #main-content.filtro-shifted .container-lista,
    #main-content.shifted.filtro-shifted .container-lista {
        width: calc(85vw - 250px) !important;
        max-width: 1400px !important;
        margin-left: auto !important;
        margin-right: 50px !important;
    }

    .cabecalho-lista {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 30px;
    }

    .titulo {
        color: var(--branco);
        font-family: "Inter", sans-serif;
        font-weight: 700;
        font-size: 44px;
        line-height: 1.2;
        text-align: center;
        text-shadow: 0px 4px 20px rgba(0, 0, 0, 0.6);
        margin: 0;
    }

    .dados-evento {
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 20px 30px;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        width: 100%;
        margin-bottom: 30px;
    }

    .dados-evento-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px 20px;
    }

    .dado-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    @media (max-width: 1200px) {
        .dados-evento-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        /* SOBRESCREVER PARA MOBILE - MANTER FUNDO MAS REMOVER PADDING */
        body .container-lista,
        #main-content .container-lista,
        div .container-lista {
            width: calc(100% - 12px) !important;
            max-width: calc(100% - 12px) !important;
            margin: 6px !important;
            padding: 0 6px !important;
            border-radius: 8px !important;
            display: block !important;
            gap: 0 !important;
        }
        
        #main-content.shifted .container-lista,
        #main-content.filtro-shifted .container-lista,
        #main-content.shifted.filtro-shifted .container-lista {
            width: calc(100% - 12px) !important;
            max-width: calc(100% - 12px) !important;
            margin: 6px !important;
            padding: 0 6px !important;
        }
        
        .dados-evento-grid {
            grid-template-columns: 1fr;
        }
    }

    .dado-label {
        color: rgba(255, 255, 255, 0.8);
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .dado-valor {
        color: var(--branco);
        font-size: 16px;
        font-weight: 500;
        text-shadow: 0px 2px 4px rgba(0, 0, 0, 0.3);
    }

    /* Container principal de gerenciamento */
    .container-gerenciamento {
        background-color: rgba(255, 255, 255, 0.05);
        border-radius: 16px;
        padding: 32px;
        border: 2px solid rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
    }

    .secao-gerenciamento {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .secao-titulo {
        color: var(--branco);
        font-size: 20px;
        font-weight: 600;
        text-align: center;
        margin: 0 0 8px 0;
        text-shadow: 0px 2px 8px rgba(0, 0, 0, 0.3);
    }

    .divisor-secao {
        height: 2px;
        background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.3), transparent);
        margin: 16px 0;
    }

    /* Centralização da barra de pesquisa */
    .container-gerenciamento .barra-pesquisa-container {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
    }

    .container-gerenciamento .barra-pesquisa {
        width: 100%;
        max-width: 580px;
        display: flex;
        justify-content: center;
    }

    .grade-acoes-gerenciamento {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
        width: 100%;
        max-width: 900px;
        margin: 0 auto;
    }

    @media (max-width: 768px) {
        .grade-acoes-gerenciamento {
            grid-template-columns: 1fr;
        }
    }

    .botao-acao {
        background-color: var(--branco);
        border-radius: 8px;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 13px;
        white-space: nowrap;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        flex-shrink: 0;
        min-height: 48px;
    }

    .botao-acao:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .botao-acao img {
        height: 20px;
        width: 20px;
        flex-shrink: 0;
    }

    .botao-acao span {
        white-space: nowrap;
        font-size: 13px;
    }

    .acoes-em-massa {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 20px;
        width: 100%;
    }

    .botao-em-massa {
        padding: 8px 24px;
        border-radius: 600px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 16px;
        color: var(--branco);
        transition: opacity 0.3s ease;
    }

    /* Esconde botões de ação em massa quando nada está selecionado */
    .botao-em-massa.botao-verde,
    .botao-em-massa.botao-azul,
    .botao-em-massa.botao-vermelho {
        display: none;
    }

    /* Mostra botões quando há seleção */
    .acoes-em-massa.com-selecao .botao-em-massa.botao-verde,
    .acoes-em-massa.com-selecao .botao-em-massa.botao-azul,
    .acoes-em-massa.com-selecao .botao-em-massa.botao-vermelho {
        display: flex;
    }

    .botao-em-massa.botao-branco {
        background-color: var(--branco);
        color: #000;
    }

    .botao-em-massa.botao-verde {
        background-color: var(--verde);
    }

    .botao-em-massa.botao-vermelho {
        background-color: var(--vermelho);
    }

    .botao-em-massa.botao-azul {
        background-color: var(--botao);
    }

    .botao-em-massa img {
        height: 22.5px;
    }

    .botao-em-massa.botao-azul img {
        filter: brightness(0) invert(1);
    }

    .contador-participantes {
        text-align: center;
        padding: 14px 24px;
        background: linear-gradient(135deg, #6598D2 0%, #5080BE 100%);
        border-radius: 10px;
        backdrop-filter: blur(10px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .contador-participantes span {
        color: var(--branco);
        font-size: 16px;
        font-weight: 600;
        text-shadow: 0px 2px 8px rgba(0, 0, 0, 0.3);
    }

    .envoltorio-tabela {
        overflow-x: auto;
        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        background-color: var(--tabela_participantes);
        margin-top: 20px;
    }

    .tabela-participantes {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background-color: var(--tabela_participantes);
        border: 1px solid var(--azul-escuro);
        border-radius: 12px;
        overflow: hidden;
        font-size: 15px;
    }

    .Titulo_Tabela {
        color: var(--branco);
    }

    .tabela-participantes th,
    .tabela-participantes td {
        padding: 10px 12px;
        text-align: left;
        vertical-align: middle;
        border-bottom: 1px solid var(--azul-escuro);
    }

    .tabela-participantes th {
        border-right: 1px solid var(--azul-escuro);
        font-size: 14px;
    }

    .tabela-participantes td {
        border-right: 1px solid var(--azul-escuro);
        font-size: 13.5px;
    }

    .tabela-participantes th:last-child,
    .tabela-participantes td:last-child {
        border-right: none;
    }

    .tabela-participantes tbody tr:last-child td {
        border-bottom: none;
    }

    .tabela-participantes thead {
        font-weight: bold;
        background: #6598D2;
    }

    .tabela-participantes tbody tr {
        transition: background-color 0.2s ease;
    }

    .tabela-participantes tbody tr:hover {
        background-color: rgba(var(--azul-escuro-rgb, 20, 40, 80), 0.05);
    }

    .tabela-participantes th:first-child {
        text-align: center;
        border-top-left-radius: 12px;
        width: 50px;
    }

    .tabela-participantes th:last-child {
        border-top-right-radius: 12px;
    }

    .tabela-participantes td.coluna-selecionar {
        text-align: center;
        width: 50px;
    }

    .coluna-dados {
        max-width: 350px;
    }

    .coluna-dados p {
        margin: 0 0 3px 0;
        font-size: 13px;
        line-height: 1.4;
    }

    .coluna-dados p:last-child {
        margin-bottom: 0;
    }

    .coluna-dados strong {
        font-weight: 600;
        font-size: 12px;
        color: #555;
    }

    .grupo-acoes,
    .grupo-status {
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 180px;
    }

    .botao-acao-tabela {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 4px 10px;
        border-radius: 600px;
        color: var(--branco);
        width: 100%;
        font-size: 12px;
        font-weight: 600;
    }

    .botao-acao-tabela.botao-verde {
        background-color: var(--verde);
    }

    .botao-acao-tabela.botao-vermelho {
        background-color: var(--vermelho);
    }

    .botao-acao-tabela.botao-neutro {
        background-color: var(--caixas);
    }

    .botao-acao-tabela img {
        height: 18px;
        width: 18px;
    }

    .linha-status {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        font-size: 12px;
    }

    .linha-status>span:first-child {
        font-weight: 600;
        color: #444;
        min-width: 70px;
    }

    .emblema-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 10px;
        border-radius: 600px;
        color: var(--branco);
        white-space: nowrap;
        font-size: 11px;
        font-weight: 600;
    }

    .emblema-status.confirmado {
        background-color: var(--verde);
    }

    .emblema-status.negado {
        background-color: var(--vermelho);
    }

    .emblema-status img {
        height: 16px;
        width: 16px;
        flex-shrink: 0;
        object-fit: contain;
    }

    .rodape-lista {
        display: flex;
        justify-content: center;
        margin-top: 28px;
    }

    .checkbox-selecionar {
        width: 20px;
        height: 20px;
        accent-color: var(--azul-escuro);
        cursor: pointer;
        transition: transform 0.2s ease;
    }

    .checkbox-selecionar:hover {
        transform: scale(1.1);
    }

    .linha-selecionada {
        background-color: rgba(var(--azul-escuro-rgb, 20, 40, 80), 0.15) !important;
        box-shadow: inset 0 0 0 2px var(--azul-escuro);
    }

    .rodape-lista {
        display: flex;
        justify-content: center;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 2px solid rgba(255, 255, 255, 0.1);
    }

    .botao-voltar {
        background-color: var(--botao);
        color: var(--branco);
        font-weight: 700;
        padding: 1em 2em;
        line-height: 1;
        text-align: center;
        min-height: 2.25em;
        border-radius: 8px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .botao-voltar:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
    }

    /* Modal Editar Dados */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background-color: rgba(0, 0, 0, 0.6);
        z-index: 10000;
        backdrop-filter: blur(4px);
    }

    .modal-overlay.ativo {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-editar {
        background-color: var(--branco);
        border-radius: 12px;
        padding: 24px;
        max-width: 600px;
        width: 90%;
        max-height: 70vh;
        min-height: auto;
        overflow-y: auto;
        overflow-x: hidden;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        position: relative;
        z-index: 10001;
        display: flex;
        flex-direction: column;
    }

    .modal-editar::-webkit-scrollbar {
        width: 6px;
    }

    .modal-editar::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.05);
        border-radius: 10px;
    }

    .modal-editar::-webkit-scrollbar-thumb {
        background: rgba(0, 0, 0, 0.2);
        border-radius: 10px;
    }

    .modal-editar::-webkit-scrollbar-thumb:hover {
        background: rgba(0, 0, 0, 0.3);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        color: #6598D2;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(101, 152, 210, 0.1);
    }

    .modal-header h2 {
        margin: 0;
        color: #6598D2;
        font-size: 20px;
        flex: 1;
    }

    .btn-fechar-modal {
        background: none;
        border: none;
        font-size: 28px;
        color: var(--azul-escuro);
        cursor: pointer;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        transition: background-color 0.2s ease;
    }

    .btn-fechar-modal:hover {
        background-color: rgba(0, 0, 0, 0.1);
    }

    .form-group {
        margin-bottom: 14px;
    }

    .form-group label {
        display: block;
        margin-bottom: 6px;
        color: #6598D2;
        font-weight: 600;
        font-size: 13px;
    }

    .form-group input {
        width: 100%;
        padding: 10px;
        border: 1px solid var(--azul-escuro);
        border-radius: 6px;
        font-size: 14px;
        box-sizing: border-box;
        background-color: var(--branco);
    }

    .form-group input:focus {
        outline: none;
        border-color: var(--azul-escuro);
        box-shadow: 0 0 0 3px rgba(20, 40, 80, 0.1);
    }

    .form-group input:disabled {
        background-color: #f5f5f5;
        color: #666;
        cursor: not-allowed;
    }

    .modal-footer {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 20px;
        padding-top: 12px;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        flex-wrap: wrap;
    }

    .btn-modal {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: opacity 0.2s ease;
    }

    .btn-modal:hover {
        opacity: 0.9;
    }

    .btn-cancelar {
        background-color: var(--vermelho);
        color: var(--branco);
    }

    .btn-salvar {
        background-color: var(--verde);
        color: var(--branco);
    }

    /* ==== Sistema de Abas ==== */
    .container-abas {
        display: flex;
        gap: 12px;
        margin-bottom: 24px;
        border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        padding-bottom: 0;
        justify-content: center;
    }

    .aba-botao {
        padding: 12px 24px;
        font-size: 16px;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.6);
        background: transparent;
        border: none;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        bottom: -2px;
    }

    @media (max-width: 768px) {
        .container-abas {
            flex-wrap: wrap !important;
            gap: 8px !important;
            justify-content: center !important;
        }

        .aba-botao {
            padding: 10px 16px !important;
            font-size: 14px !important;
            flex: 0 1 calc(50% - 4px) !important;
        }

        .botao-voltar {
            width: 100% !important;
            padding: 12px 16px !important;
            margin: 12px 6px !important;
            font-size: 14px !important;
        }
    }

    .aba-botao:hover {
        color: rgba(255, 255, 255, 0.9);
    }

    .aba-botao.ativa {
        color: var(--branco);
        border-bottom-color: var(--azul-escuro);
    }

    .conteudo-aba {
        display: none;
    }

    .conteudo-aba.ativa {
        display: block;
    }

    /* Ajustes para a aba de organização */
    .tipo-membro {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .tipo-organizador {
        background-color: var(--botao);
        color: var(--branco);
    }

    .tipo-colaborador {
        background-color: var(--caixas);
        color: var(--branco);
    }

    /* Ajuste de colunas da tabela de organização - agora com 4 colunas igual participantes */
    #aba-organizacao .tabela-participantes th:nth-child(1),
    #aba-organizacao .tabela-participantes td:nth-child(1) {
        width: 50px;
        text-align: center;
    }

    #aba-organizacao .tabela-participantes th:nth-child(2),
    #aba-organizacao .tabela-participantes td:nth-child(2) {
        max-width: 350px;
    }

    #aba-organizacao .tabela-participantes th:nth-child(3),
    #aba-organizacao .tabela-participantes td:nth-child(3) {
        min-width: 180px;
    }

    #aba-organizacao .tabela-participantes th:nth-child(4),
    #aba-organizacao .tabela-participantes td:nth-child(4) {
        min-width: 180px;
    }

    /* ==== ESTILOS PARA CONTEÚDO DINÂMICO (Filtros, etc) ==== */
    .controles-filtro {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
        justify-content: center;
        width: 100%;
    }

    .campo-filtro {
        padding: 10px 14px;
        border: 1px solid var(--borda, #ddd);
        border-radius: 8px;
        font-size: 14px;
        color: var(--texto);
        background: var(--cartao, #fff);
        min-width: 200px;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    .campo-filtro:focus {
        outline: none;
        border-color: var(--botao, #4CAF50);
        box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
    }

    .campo-filtro::placeholder {
        color: var(--texto-secundario, #999);
    }

    .botao-limpar {
        padding: 10px 20px;
        background: var(--vermelho, #dc3545);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        cursor: pointer;
        transition: background 0.3s ease, transform 0.2s ease;
    }

    .botao-limpar:hover {
        background: #c82333;
        transform: translateY(-2px);
    }

    .grupo-acoes-tabela {
        display: flex;
        flex-direction: column;
        gap: 8px;
        align-items: center;
    }

    .botao-acao-tabela {
        padding: 8px 12px;
        border: none;
        border-radius: 6px;
        font-size: 13px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .botao-acao-tabela:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .botao-acao-tabela img {
        width: 16px;
        height: 16px;
    }

    .botao-acao-tabela.botao-verde {
        background: var(--verde, #4CAF50);
        color: white;
    }

    .botao-acao-tabela.botao-verde img {
        filter: brightness(0) invert(1);
    }

    .botao-acao-tabela.botao-azul {
        background: var(--botao, #2196F3);
        color: white;
    }

    .botao-acao-tabela.botao-azul img {
        filter: brightness(0) invert(1);
    }

    .botao-acao-tabela.botao-vermelho {
        background: var(--vermelho, #dc3545);
        color: white;
    }

    .botao-acao-tabela.botao-vermelho img {
        filter: brightness(0) invert(1);
    }

    .botao-acao-tabela.botao-neutro {
        background: var(--fundo-secundario, var(--caixas));
        color: #fff;
    }

    .botao-acao-tabela.botao-neutro img {
        filter: brightness(0) invert(1);
    }

    .botao-acao-tabela.botao-cinza {
        background: #b0bec5;
        color: white;
        cursor: not-allowed;
        opacity: 0.7;
    }

    .botao-acao-tabela.botao-cinza:hover {
        background: #b0bec5;
        cursor: not-allowed;
    }

    .botao-acao-tabela.botao-cinza img {
        filter: brightness(0) invert(1);
        opacity: 0.7;
    }

    button:disabled {
        cursor: not-allowed !important;
    }

    .emblema-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }

    .emblema-status.confirmado {
        background: #4CAF50;
        color: white;
    }

    .emblema-status.pendente {
        background: var(--botao, #2196F3);
        color: white;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .emblema-status.pendente:hover {
        background: #1976D2;
        transform: translateY(-2px);
    }

    .emblema-status img {
        width: 14px;
        height: 14px;
        filter: brightness(0) invert(1);
    }

    /* ===== ESTILOS PARA LAYOUT COMPACTO ===== */
    .secao-superior-compacta {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 12px;
    }

    .barra-pesquisa-wrapper-compacta {
        width: 100%;
        margin-bottom: 4px;
    }

    .barra-pesquisa-wrapper-compacta .barra-pesquisa-container {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
    }

    .barra-pesquisa-wrapper-compacta .barra-pesquisa {
        width: 100%;
        max-width: 580px;
        display: flex;
        justify-content: center;
    }

    .acoes-rapidas-wrapper {
        width: 100%;
    }

    .secao-titulo-compacta {
        color: var(--branco);
        font-size: 18px;
        font-weight: 600;
        text-align: center;
        margin: 0 0 8px 0;
        text-shadow: 0px 2px 8px rgba(0, 0, 0, 0.3);
    }

    .secao-acoes-massa-compacta {
        margin-top: 8px;
        margin-bottom: 12px;
    }

    .secao-acoes-massa-compacta .secao-titulo-compacta {
        margin-bottom: 8px;
    }

    /* Melhorar visibilidade dos botões */
    .botao-acao {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .botao-acao:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
        transform: translateY(-2px);
    }

    /* Melhorar grade de ações */
    .grade-acoes-gerenciamento {
        gap: 10px !important;
    }

    /* ===== RESPONSIVIDADE PARA TABLETS (768px - 1024px) ===== */
    @media (min-width: 769px) and (max-width: 1024px) {
        .secao-superior-compacta {
            gap: 12px;
            margin-bottom: 14px;
        }

        .barra-pesquisa-wrapper-compacta .barra-pesquisa {
            max-width: 500px;
        }

        .secao-titulo-compacta {
            font-size: 17px;
            margin-bottom: 6px;
        }

        .secao-acoes-massa-compacta {
            margin-top: 6px;
            margin-bottom: 10px;
        }

        .grade-acoes-gerenciamento {
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 10px !important;
        }

        .botao-acao {
            padding: 10px 14px;
            font-size: 12px;
            min-height: 44px;
        }

        .botao-acao img {
            height: 18px;
            width: 18px;
        }

        .acoes-em-massa {
            gap: 12px;
        }

        .botao-em-massa {
            padding: 6px 20px;
            font-size: 14px;
        }

        .tabela-participantes th,
        .tabela-participantes td {
            padding: 8px 10px !important;
            font-size: 13px !important;
        }
    }

    /* ===== RESPONSIVIDADE PARA CELULARES (até 768px) ===== */
    @media (max-width: 768px) {
        .secao-superior-compacta {
            gap: 8px;
            margin-bottom: 10px;
        }

        .barra-pesquisa-wrapper-compacta {
            margin-bottom: 6px;
        }

        .barra-pesquisa-wrapper-compacta .barra-pesquisa {
            max-width: 100%;
        }

        .secao-titulo-compacta {
            font-size: 16px;
            margin-bottom: 6px;
        }

        .secao-acoes-massa-compacta {
            margin-top: 6px;
            margin-bottom: 10px;
        }

        .secao-gerenciamento {
            gap: 8px;
        }

        .grade-acoes-gerenciamento {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 8px !important;
        }

        .botao-acao {
            flex-direction: column;
            padding: 10px 8px;
            font-size: 11px;
            min-height: 70px;
            gap: 4px;
        }

        .botao-acao span {
            font-size: 11px;
            text-align: center;
            line-height: 1.2;
        }

        .botao-acao img {
            height: 24px;
            width: 24px;
        }

        .acoes-em-massa {
            flex-direction: column;
            gap: 10px;
            align-items: stretch;
        }

        .botao-em-massa {
            width: 100%;
            justify-content: center;
            padding: 10px 16px;
            font-size: 14px;
        }

        .contador-participantes {
            padding: 10px 16px;
        }

        .contador-participantes span {
            font-size: 14px;
        }

        .envoltorio-tabela {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-top: 12px;
        }

        .envoltorio-tabela::-webkit-scrollbar {
            height: 8px;
        }

        .envoltorio-tabela::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        .envoltorio-tabela::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 4px;
        }

        .tabela-participantes {
            min-width: 600px;
            font-size: 12px;
        }

        .tabela-participantes th,
        .tabela-participantes td {
            padding: 8px 6px !important;
            font-size: 11px !important;
        }

        .coluna-dados {
            max-width: 200px;
        }

        .coluna-dados p {
            font-size: 11px;
            margin: 0 0 2px 0;
        }

        .coluna-dados strong {
            font-size: 10px;
        }

        .grupo-acoes,
        .grupo-status {
            min-width: 140px;
            gap: 4px;
        }

        .botao-acao-tabela {
            padding: 4px 8px;
            font-size: 10px;
        }

        .botao-acao-tabela img {
            height: 14px;
            width: 14px;
        }

        .linha-status {
            font-size: 10px;
            gap: 4px;
        }

        .linha-status>span:first-child {
            min-width: 60px;
            font-size: 10px;
        }

        .emblema-status {
            padding: 2px 8px;
            font-size: 9px;
        }

        .emblema-status img {
            height: 12px;
            width: 12px;
        }
    }

    /* ===== RESPONSIVIDADE PARA CELULARES PEQUENOS (até 480px) ===== */
    @media (max-width: 480px) {
        .secao-superior-compacta {
            gap: 6px;
            margin-bottom: 8px;
        }

        .secao-titulo-compacta {
            font-size: 15px;
            margin-bottom: 4px;
        }

        .grade-acoes-gerenciamento {
            grid-template-columns: 1fr !important;
            gap: 6px !important;
        }

        .botao-acao {
            min-height: 60px;
            padding: 8px;
        }

        .botao-acao span {
            font-size: 10px;
        }

        .botao-acao img {
            height: 20px;
            width: 20px;
        }

        .tabela-participantes th,
        .tabela-participantes td {
            padding: 6px 8px !important;
            font-size: 11px !important;
        }
    }

    /* ===== AJUSTES ADICIONAIS PARA RESPONSIVIDADE ===== */

    /* Barra de pesquisa responsiva */
    @media (max-width: 768px) {
        .campo-pesquisa {
            font-size: 14px !important;
            padding: 0 12px !important;
        }

        .botao-pesquisa {
            width: 50px !important;
            height: 50px !important;
        }

        .icone-pesquisa {
            width: 50px !important;
            height: 50px !important;
        }
    }

    @media (max-width: 480px) {
        .campo-pesquisa {
            font-size: 13px !important;
            padding: 0 10px !important;
        }

        .botao-pesquisa {
            width: 45px !important;
            height: 45px !important;
        }

        .icone-pesquisa {
            width: 45px !important;
            height: 45px !important;
        }
    }

    /* Modais responsivos */
    @media (max-width: 768px) {
        .modal-editar {
            width: 95% !important;
            padding: 24px !important;
            max-height: 95vh !important;
        }

        .modal-header h2 {
            font-size: 20px !important;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            font-size: 13px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            font-size: 14px !important;
            padding: 10px !important;
        }

        .modal-footer {
            flex-direction: column;
            gap: 8px;
        }

        .btn-modal {
            width: 100%;
            padding: 12px;
        }
    }

    /* ===== TRANSFORMAR TABELA EM CARDS NO MOBILE ===== */
    @media (max-width: 768px) {
        /* Oculta a tabela e mostra cards */
        .envoltorio-tabela {
            overflow: visible !important;
        }

        .envoltorio-tabela::before {
            display: none;
        }

        .tabela-participantes {
            display: none;
        }

        /* Container de cards */
        .envoltorio-tabela::after {
            content: '';
            display: block;
        }

        /* Cria os cards a partir das linhas da tabela */
        .tabela-participantes tbody tr {
            display: block;
            background: var(--branco);
            border: 1px solid var(--azul-escuro);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .tabela-participantes tbody tr:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .tabela-participantes tbody tr td {
            display: block;
            border: none !important;
            padding: 8px 0 !important;
            text-align: left !important;
        }

        .tabela-participantes tbody tr td::before {
            content: attr(data-label);
            font-weight: 600;
            color: var(--azul-escuro);
            display: block;
            margin-bottom: 4px;
            font-size: 12px;
            text-transform: uppercase;
        }

        .tabela-participantes tbody tr td:first-child {
            border-bottom: 2px solid var(--azul-escuro);
            padding-bottom: 12px !important;
            margin-bottom: 8px;
            text-align: center !important;
        }

        .tabela-participantes tbody tr td:first-child::before {
            content: none;
        }

        .tabela-participantes thead {
            display: none;
        }

        /* Ajusta checkbox de seleção no card */
        .tabela-participantes tbody tr td.coluna-selecionar {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Ajusta grupo de ações e status para melhor visualização em card */
        .grupo-acoes,
        .grupo-status {
            min-width: auto;
            width: 100%;
        }

        .botao-acao-tabela {
            width: 100%;
            justify-content: center;
        }

        .linha-status {
            justify-content: space-between;
        }
    }

    @media (max-width: 480px) {
        .tabela-participantes tbody tr {
            padding: 12px;
            margin-bottom: 10px;
        }

        .tabela-participantes tbody tr td {
            padding: 6px 0 !important;
        }

        .tabela-participantes tbody tr td::before {
            font-size: 11px;
        }

        .coluna-dados p {
            font-size: 12px !important;
        }

        .botao-acao-tabela {
            padding: 8px 12px !important;
            font-size: 11px !important;
        }
    }
</style>

<body>
    <div id="main-content">
        <div class="container-lista">
            <header class="cabecalho-lista">
                <h1 class="titulo" id="nome-evento">Gerenciar Evento</h1>

                <div class="dados-evento">
                    <div class="dados-evento-grid">
                        <div class="dado-item">
                            <span class="dado-label">Categoria</span>
                            <span class="dado-valor" id="evento-categoria">-</span>
                        </div>
                        <div class="dado-item">
                            <span class="dado-label">Local</span>
                            <span class="dado-valor" id="evento-lugar">-</span>
                        </div>
                        <div class="dado-item">
                            <span class="dado-label">Modalidade</span>
                            <span class="dado-valor" id="evento-modalidade">-</span>
                        </div>
                        <div class="dado-item">
                            <span class="dado-label">Duração</span>
                            <span class="dado-valor" id="evento-duracao">-</span>
                        </div>
                        <div class="dado-item">
                            <span class="dado-label">Início do Evento</span>
                            <span class="dado-valor" id="evento-inicio">-</span>
                        </div>
                        <div class="dado-item">
                            <span class="dado-label">Término do Evento</span>
                            <span class="dado-valor" id="evento-conclusao">-</span>
                        </div>
                        <div class="dado-item">
                            <span class="dado-label">Início das Inscrições</span>
                            <span class="dado-valor" id="evento-inicio-inscricao">-</span>
                        </div>
                        <div class="dado-item">
                            <span class="dado-label">Fim das Inscrições</span>
                            <span class="dado-valor" id="evento-fim-inscricao">-</span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Sistema de Abas -->
            <div class="container-abas">
                <button class="aba-botao ativa" data-aba="participantes">Participantes</button>
                <button class="aba-botao" data-aba="organizacao">Organização</button>
            </div>

            <!-- Container Principal de Gerenciamento -->
            <div class="container-gerenciamento">

                <!-- ABA: PARTICIPANTES -->
                <div class="conteudo-aba ativa" id="aba-participantes">
                    <div style="text-align: center; padding: 40px; color: var(--texto);">
                        <p>Carregando conteúdo...</p>
                    </div>
                </div>
                <!-- FIM ABA: PARTICIPANTES -->

                <!-- ABA: ORGANIZAÇÃO -->
                <div class="conteudo-aba" id="aba-organizacao">
                    <div style="text-align: center; padding: 40px; color: var(--texto);">
                        <p>Carregando conteúdo...</p>
                    </div>
                </div>
                <!-- FIM ABA: ORGANIZAÇÃO -->

            </div>

            <!-- Rodapé com botão de voltar -->
            <footer class="rodape-lista">
                <button type="button" class="botao botao-voltar" onclick="voltarParaEventos()">Voltar</button>
            </footer>

        </div>
    </div>

    <!-- Modal Enviar Mensagem para CPF Específico -->
    <div class="modal-overlay" id="modalEnviarMensagemCPF" onclick="fecharModalSeForFundo(event, 'modalEnviarMensagemCPF')">
        <div class="modal-editar">
            <div class="modal-header">
                <h2>Enviar Mensagem para CPF Específico</h2>
                <button class="btn-fechar-modal" onclick="fecharModalMensagemCPF()">&times;</button>
            </div>
            <form id="formEnviarMensagemCPF" onsubmit="enviarMensagemCPF(event)">
                <div class="form-group">
                    <label for="msg-cpf-destinatario">CPF do Destinatário*</label>
                    <input type="text" id="msg-cpf-destinatario" maxlength="11" pattern="[0-9]{11}" placeholder="Digite o CPF (apenas números)" required>
                    <small style="color: #666;">Digite o CPF do usuário que enviou a mensagem (apenas números, sem pontos ou traços)</small>
                </div>

                <div class="form-group">
                    <label for="msg-titulo-cpf">Título da Notificação*</label>
                    <input type="text" id="msg-titulo-cpf" maxlength="100" required>
                </div>

                <div class="form-group">
                    <label for="msg-conteudo-cpf">Mensagem*</label>
                    <textarea id="msg-conteudo-cpf" rows="6" style="width: 100%; padding: 12px; border: 1px solid var(--azul-escuro); border-radius: 8px; font-size: 15px; font-family: inherit; resize: vertical;" maxlength="500" required></textarea>
                    <small style="color: #666;">Máximo 500 caracteres</small>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-modal btn-cancelar" onclick="fecharModalMensagemCPF()">Cancelar</button>
                    <button type="submit" class="btn-modal btn-salvar">Enviar Notificação</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Dados -->
    <div class="modal-overlay" id="modalEditarDados" onclick="fecharModalSeForFundo(event, 'modalEditarDados')">
        <div class="modal-editar">
            <div class="modal-header">
                <h2>Editar Dados do Participante</h2>
                <button class="btn-fechar-modal" onclick="fecharModal()">&times;</button>
            </div>
            <form id="formEditarDados" onsubmit="salvarEdicao(event)">
                <input type="hidden" id="edit-cpf">

                <div class="form-group">
                    <label for="edit-nome">Nome Completo*</label>
                    <input type="text" id="edit-nome" required>
                </div>

                <div class="form-group">
                    <label for="edit-email">E-mail*</label>
                    <input type="email" id="edit-email" required>
                </div>

                <div class="form-group">
                    <label for="edit-ra">Registro Acadêmico (RA)</label>
                    <input type="text" id="edit-ra" maxlength="7">
                </div>

                <div class="form-group">
                    <label for="edit-cpf-display">CPF</label>
                    <input type="text" id="edit-cpf-display" disabled>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-modal btn-cancelar" onclick="fecharModal()">Cancelar</button>
                    <button type="submit" class="btn-modal btn-salvar">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modais-globais"></div>

    <script>
        // Evita redeclaração de variáveis globais quando página é recarregada dinamicamente
        if (typeof codEventoAtual === 'undefined') {
            var codEventoAtual = null;
        }
        if (typeof todosParticipantes === 'undefined') {
            var todosParticipantes = [];
        }
        if (typeof participantesSelecionados === 'undefined') {
            var participantesSelecionados = new Set();
        }
        if (typeof todosOrganizacao === 'undefined') {
            var todosOrganizacao = [];
        }
        if (typeof abaAtual === 'undefined') {
            var abaAtual = 'participantes';
        }

        // Declarações stub das funções de importar/exportar (serão sobrescritas pelo ConteudoParticipantes.php)
        if (typeof importarListaPresenca === 'undefined') {
            var importarListaPresenca = function() {
                console.log('Aguardando carregamento do conteúdo...');
            };
        }
        if (typeof exportarListaPresenca === 'undefined') {
            var exportarListaPresenca = function() {
                console.log('Aguardando carregamento do conteúdo...');
            };
        }
        if (typeof importarListaInscritos === 'undefined') {
            var importarListaInscritos = function() {
                console.log('Aguardando carregamento do conteúdo...');
            };
        }
        if (typeof exportarListaInscritos === 'undefined') {
            var exportarListaInscritos = function() {
                console.log('Aguardando carregamento do conteúdo...');
            };
        }
        if (typeof fecharModalFormato === 'undefined') {
            var fecharModalFormato = function() {};
        }
        if (typeof fecharModalImportacao === 'undefined') {
            var fecharModalImportacao = function() {};
        }
        if (typeof executarExportacao === 'undefined') {
            var executarExportacao = function() {
                console.log('Aguardando carregamento do conteúdo...');
            };
        }
        if (typeof selecionarArquivoImportacao === 'undefined') {
            var selecionarArquivoImportacao = function() {
                console.log('Aguardando carregamento do conteúdo...');
            };
        }
        if (typeof fecharModalSeForFundo === 'undefined') {
            var fecharModalSeForFundo = function() {};
        }

        // ==== SISTEMA DE ABAS ====
        function inicializarAbas() {
            const botoesAbas = document.querySelectorAll('.aba-botao');

            botoesAbas.forEach(botao => {
                botao.addEventListener('click', () => {
                    const nomeAba = botao.getAttribute('data-aba');
                    trocarAba(nomeAba);
                });
            });

            // Carrega o conteúdo da aba inicial
            // Restaura a última aba ativa salva no localStorage
            const abaSalva = localStorage.getItem('gerenciar_evento_aba_ativa');
            const abaInicial = abaSalva || 'participantes';

            // Ativa a aba correta
            trocarAba(abaInicial);

            // ==== LISTENER GLOBAL DE RESIZE PARA RECARREGAR CONTEÚDO AO MUDAR ENTRE MOBILE/DESKTOP ====
            if (!window.__gerenciarEventoResizeAttached) {
                window.__gerenciarEventoResizeAttached = true;
                let lastIsMobile = window.matchMedia('(max-width: 768px)').matches;
                
                window.addEventListener('resize', () => {
                    const nowIsMobile = window.matchMedia('(max-width: 768px)').matches;
                    
                    // Se mudou entre mobile e desktop
                    if (nowIsMobile !== lastIsMobile) {
                        lastIsMobile = nowIsMobile;
                        console.log('Mudança entre mobile/desktop detectada. Re-renderizando aba atual...');
                        
                        // Re-carrega a aba atual chamando a função apropriada
                        if (abaAtual === 'participantes' && typeof renderizarParticipantes === 'function') {
                            renderizarParticipantes();
                        } else if (abaAtual === 'organizacao' && typeof renderizarOrganizacao === 'function') {
                            renderizarOrganizacao();
                        }
                    }
                });
            }
        }

        function trocarAba(nomeAba) {
            abaAtual = nomeAba;

            // Salva a aba atual no localStorage para manter após reload
            localStorage.setItem('gerenciar_evento_aba_ativa', nomeAba);

            // Atualiza botões das abas
            document.querySelectorAll('.aba-botao').forEach(btn => {
                btn.classList.remove('ativa');
            });
            document.querySelector(`[data-aba="${nomeAba}"]`).classList.add('ativa');

            // Atualiza conteúdo das abas
            document.querySelectorAll('.conteudo-aba').forEach(conteudo => {
                conteudo.classList.remove('ativa');
            });
            const abaElement = document.getElementById(`aba-${nomeAba}`);
            abaElement.classList.add('ativa');

            // Carrega conteúdo dinamicamente
            carregarConteudoAba(nomeAba, abaElement);
        }

        // ==== FUNÇÃO PARA CARREGAR CONTEÚDO DINÂMICO DAS ABAS ====
        function carregarConteudoAba(nomeAba, abaElement) {
            // Define o arquivo de conteúdo para cada aba
            const arquivosConteudo = {
                'participantes': 'ConteudoParticipantes.php',
                'organizacao': 'ConteudoOrganizacao.php'
            };

            const arquivo = arquivosConteudo[nomeAba];
            if (!arquivo) {
                return;
            }

            // Verifica se já foi carregado (procura por elementos específicos)
            const temConteudo = abaElement.querySelector('.secao-gerenciamento');
            if (temConteudo) {
                // Mas chama a função de carregamento de dados se existir
                if (nomeAba === 'participantes' && typeof carregarParticipantes === 'function') {
                    carregarParticipantes();
                } else if (nomeAba === 'organizacao' && typeof carregarOrganizacao === 'function') {
                    carregarOrganizacao();
                }
                return;
            }

            abaElement.innerHTML = '<div style="text-align: center; padding: 40px; color: var(--texto);"><p>⏳ Carregando...</p></div>';

            // Carrega o conteúdo via AJAX
            fetch(arquivo)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(html => {
                    abaElement.innerHTML = html;

                    // Executa scripts dentro do HTML carregado
                    executarScriptsDoConteudo(abaElement);
                })
                .catch(erro => {
                    console.error('Erro ao carregar conteúdo:', erro);
                    abaElement.innerHTML = `
                        <div style="text-align: center; padding: 40px; color: var(--texto);">
                            <p>❌ Erro ao carregar conteúdo</p>
                            <p style="font-size: 0.9rem; color: var(--texto-secundario);">${erro.message}</p>
                            <button class="botao" onclick="carregarConteudoAba('${nomeAba}', document.getElementById('aba-${nomeAba}'))">
                                Tentar Novamente
                            </button>
                        </div>
                    `;
                });
        }

        // ==== FUNÇÃO PARA EXECUTAR SCRIPTS DO CONTEÚDO CARREGADO ====
        function executarScriptsDoConteudo(elemento) {
            const scripts = elemento.querySelectorAll('script');

            scripts.forEach((scriptAntigo) => {
                const scriptNovo = document.createElement('script');
                if (scriptAntigo.src) {
                    scriptNovo.src = scriptAntigo.src;
                } else {
                    scriptNovo.textContent = scriptAntigo.textContent;
                }
                scriptAntigo.parentNode.replaceChild(scriptNovo, scriptAntigo);
            });
        }

        // Função para voltar - volta para a página do evento
        function voltarParaEventos() {
            if (!codEventoAtual) {
                // Se não tem código do evento, vai para meus eventos
                if (typeof window.carregarPagina === 'function') {
                    window.carregarPagina('meusEventos');
                } else if (window.parent && window.parent !== window && typeof window.parent.carregarPagina === 'function') {
                    window.parent.carregarPagina('meusEventos');
                } else {
                    window.location.href = 'ContainerOrganizador.php?pagina=meusEventos';
                }
                return;
            }

            // Volta para a página do evento específico
            if (typeof window.carregarPagina === 'function') {
                window.carregarPagina('eventoOrganizado', codEventoAtual);
            } else if (window.parent && window.parent !== window && typeof window.parent.carregarPagina === 'function') {
                window.parent.carregarPagina('eventoOrganizado', codEventoAtual);
            } else {
                window.location.href = 'ContainerOrganizador.php?pagina=eventoOrganizado&cod_evento=' + codEventoAtual;
            }
        }

        // Função de inicialização
        function inicializarListaParticipantes() {
            // Tenta pegar da URL primeiro
            const urlParams = new URLSearchParams(window.location.search);
            let codFromUrl = urlParams.get('cod_evento');
            const responderCpf = urlParams.get('responder_cpf');

            // Se não vier da URL, tenta pegar da variável global (quando carregado via AJAX)
            if (!codFromUrl && window.codigoEventoParaGerenciar) {
                codFromUrl = window.codigoEventoParaGerenciar;
            }

            codEventoAtual = codFromUrl;

            // Se há parâmetro para responder, abre o modal após um pequeno delay
            if (responderCpf) {
                setTimeout(() => {
                    if (typeof window.abrirModalMensagemCPF === 'function') {
                        window.abrirModalMensagemCPF(responderCpf);
                    } else if (typeof abrirModalMensagemCPF === 'function') {
                        abrirModalMensagemCPF(responderCpf);
                    }
                    // Remove o parâmetro da URL sem recarregar
                    const newUrl = window.location.pathname + '?pagina=gerenciarEvento&cod_evento=' + codEventoAtual;
                    window.history.replaceState({}, '', newUrl);
                }, 800);
            }

            if (!codEventoAtual) {
                alert('Erro: Evento não identificado');
                voltarParaEventos();
                return;
            }

            // Verifica se o elemento principal existe antes de continuar
            const containerLista = document.querySelector('.container-lista');

            if (!containerLista) {
                setTimeout(inicializarListaParticipantes, 100);
                return;
            }

            // Carrega dados do evento PRIMEIRO (antes das abas)
            carregarDadosEvento();

            // Depois inicializa as abas
            inicializarAbas();
            inicializarEventos();
        }

        // ==== FUNÇÃO PARA CARREGAR DADOS DO EVENTO ====
        function carregarDadosEvento() {
            fetch(`GerenciarEvento.php?action=buscar&cod_evento=${codEventoAtual}`)
                .then(response => response.json())
                .then(dados => {
                    if (!dados.sucesso) {
                        alert('Erro ao carregar dados do evento: ' + (dados.erro || 'Erro desconhecido'));
                        return;
                    }

                    // Atualiza título e dados gerais do evento
                    if (dados.evento) {
                        const titulo = document.getElementById('nome-evento');
                        if (titulo) {
                            titulo.textContent = `${dados.evento.nome}`;
                        }

                        // Preenche dados gerais do evento
                        document.getElementById('evento-categoria').textContent = dados.evento.categoria || '-';
                        document.getElementById('evento-lugar').textContent = dados.evento.lugar || '-';
                        document.getElementById('evento-modalidade').textContent = dados.evento.modalidade || '-';
                        document.getElementById('evento-inicio').textContent = dados.evento.inicio || '-';
                        document.getElementById('evento-conclusao').textContent = dados.evento.conclusao || '-';
                        document.getElementById('evento-inicio-inscricao').textContent = dados.evento.inicio_inscricao || '-';
                        document.getElementById('evento-fim-inscricao').textContent = dados.evento.fim_inscricao || '-';

                        const duracao = dados.evento.duracao;
                        document.getElementById('evento-duracao').textContent = duracao ? `${duracao}h` : '-';
                    }
                })
                .catch(erro => {
                    console.error('Erro ao carregar dados do evento:', erro);
                    alert('Erro ao carregar dados do evento. Tente novamente.');
                });
        }

        // Função para limpar estado ao sair da página
        window.limparGerenciarEvento = function() {
            window.__gerenciarEventoInicializado = false;
            codEventoAtual = null;
            todosParticipantes = [];
            participantesSelecionados = new Set();
            todosOrganizacao = [];
            abaAtual = 'participantes';
        };

        // Expõe funções globalmente para serem chamadas pelo Container e ConteudoParticipantes
        window.inicializarListaParticipantes = inicializarListaParticipantes;
        window.importarListaPresenca = importarListaPresenca;
        window.exportarListaPresenca = exportarListaPresenca;
        window.importarListaInscritos = importarListaInscritos;
        window.exportarListaInscritos = exportarListaInscritos;
        window.fecharModalFormato = fecharModalFormato;
        window.fecharModalImportacao = fecharModalImportacao;
        window.executarExportacao = executarExportacao;
        window.selecionarArquivoImportacao = selecionarArquivoImportacao;

        // Inicializa SEMPRE quando o script for executado
        // (isso acontece quando a página é carregada dinamicamente pelo Container)
        // Limpa flag anterior para permitir nova inicialização
        window.__gerenciarEventoInicializado = false;

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', inicializarListaParticipantes);
        } else {
            // DOM já está pronto ou página carregada via fetch
            setTimeout(inicializarListaParticipantes, 50);
        }

        // Nota: renderizarParticipantes() agora está em ConteudoParticipantes.php

        function atualizarVisibilidadeBotoesAcao() {
            const acoesEmMassa = document.querySelector('.acoes-em-massa');
            if (acoesEmMassa) {
                if (participantesSelecionados.size > 0) {
                    acoesEmMassa.classList.add('com-selecao');
                } else {
                    acoesEmMassa.classList.remove('com-selecao');
                }
            }
        }

        function atualizarTextoBotaoToggle() {
            const txtToggle = document.getElementById('texto-toggle-selecao');
            if (txtToggle) {
                const todosSelecionados = participantesSelecionados.size === todosParticipantes.length && participantesSelecionados.size > 0;
                txtToggle.textContent = todosSelecionados ? 'Desselecionar Todos' : 'Selecionar Todos';
            }
        }

        function inicializarEventos() {
            if (!window.__listaDocChangeBound) {
                window.__listaDocChangeBound = true;
                document.addEventListener('change', function(e) {
                    if (e.target.classList && e.target.classList.contains('checkbox-selecionar')) {
                        const tr = e.target.closest('tr');
                        tr.classList.toggle('linha-selecionada', e.target.checked);
                        e.target.checked ? participantesSelecionados.add(e.target.value) : participantesSelecionados.delete(e.target.value);
                        atualizarVisibilidadeBotoesAcao();
                        atualizarTextoBotaoToggle();
                    }

                    // Atualizar mensagem no modal de mensagem ao alternar "Enviar para todos"
                    if (e.target && e.target.id === 'msg-todos') {
                        const msgSel = document.getElementById('msg-selecionados');
                        if (!e.target.checked && participantesSelecionados.size > 0) {
                            msgSel.textContent = `Enviando para ${participantesSelecionados.size} participante(s) selecionado(s)`;
                            msgSel.style.display = 'block';
                        } else if (msgSel) {
                            msgSel.style.display = 'none';
                        }
                    }
                });
            }

            const btnToggle = document.getElementById('botao-toggle-selecao');
            const txtToggle = document.getElementById('texto-toggle-selecao');
            if (btnToggle && !btnToggle.dataset.bound) {
                btnToggle.dataset.bound = '1';
                btnToggle.addEventListener('click', function() {
                    if (todosParticipantes.length === 0) {
                        alert('Não há participantes inscritos neste evento');
                        return;
                    }

                    const todosSelecionados = participantesSelecionados.size === todosParticipantes.length && participantesSelecionados.size > 0;

                    if (todosSelecionados) {
                        // Desselecionar todos
                        document.querySelectorAll('.checkbox-selecionar').forEach(cb => {
                            cb.checked = false;
                            cb.closest('tr').classList.remove('linha-selecionada');
                            participantesSelecionados.delete(cb.value);
                        });
                        txtToggle.textContent = 'Selecionar Todos';
                    } else {
                        // Selecionar todos
                        participantesSelecionados.clear();
                        document.querySelectorAll('.checkbox-selecionar').forEach(cb => {
                            cb.checked = true;
                            cb.closest('tr').classList.add('linha-selecionada');
                            participantesSelecionados.add(cb.value);
                        });
                        txtToggle.textContent = 'Desselecionar Todos';
                    }

                    atualizarVisibilidadeBotoesAcao();
                });
            }

            const campoPesquisa = document.getElementById('busca-participantes');
            const btnPesquisa = document.querySelector('.botao-pesquisa');
            if (campoPesquisa && btnPesquisa) {
                const atualizarMensagemSemResultados = (existeVisivel) => {
                    const tbody = document.getElementById('tbody-participantes');
                    if (!tbody) return;
                    const idMsg = 'linha-sem-resultados-busca';
                    const existente = document.getElementById(idMsg);
                    if (existeVisivel) {
                        if (existente) existente.remove();
                        return;
                    }
                    // Se não há visíveis e ainda não existe a linha de mensagem, adiciona
                    if (!existente) {
                        const tr = document.createElement('tr');
                        tr.id = idMsg;
                        const td = document.createElement('td');
                        td.colSpan = 4;
                        td.style.textAlign = 'center';
                        td.style.padding = '30px';
                        td.style.color = 'var(--botao)';
                        td.textContent = 'Nenhum participante encontrado para a busca';
                        tr.appendChild(td);
                        tbody.appendChild(tr);
                    }
                };

                const filtrar = () => {
                    const tbody = document.getElementById('tbody-participantes');
                    if (!tbody) return;
                    // Se não há participantes carregados, não faz nada (renderização já mostra a mensagem padrão)
                    if (todosParticipantes.length === 0) {
                        return;
                    }
                    const termo = (campoPesquisa.value || '').toLowerCase();
                    let visiveis = 0;
                    // Considera apenas linhas de participantes (que possuem data-cpf)
                    tbody.querySelectorAll('tr').forEach(linha => {
                        if (!linha.hasAttribute('data-cpf')) return; // ignora mensagens
                        const match = linha.textContent.toLowerCase().includes(termo);
                        linha.style.display = match ? '' : 'none';
                        if (match) visiveis++;
                    });
                    atualizarMensagemSemResultados(visiveis > 0);
                };

                if (!btnPesquisa.dataset.bound) {
                    btnPesquisa.dataset.bound = '1';
                    btnPesquisa.addEventListener('click', (e) => {
                        e.preventDefault();
                        filtrar();
                    });
                }
                if (!campoPesquisa.dataset.bound) {
                    campoPesquisa.dataset.bound = '1';
                    campoPesquisa.addEventListener('keydown', e => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            filtrar();
                        }
                    });
                    campoPesquisa.addEventListener('input', filtrar);
                }
            }

            // Botões de ação do topo (Participantes)
            const bindsTopo = [{
                    id: 'btn-adicionar-participante',
                    fn: abrirModalAdicionar
                },
                {
                    id: 'btn-importar-presenca',
                    fn: importarListaPresenca
                },
                {
                    id: 'btn-exportar-presenca',
                    fn: exportarListaPresenca
                },
                {
                    id: 'btn-enviar-mensagem',
                    fn: abrirModalMensagem
                },
                {
                    id: 'btn-importar-inscritos',
                    fn: importarListaInscritos
                },
                {
                    id: 'btn-exportar-inscritos',
                    fn: exportarListaInscritos
                }
            ];
            bindsTopo.forEach(({
                id,
                fn
            }) => {
                const el = document.getElementById(id);
                if (el && !el.dataset.bound) {
                    el.dataset.bound = '1';
                    el.addEventListener('click', fn);
                }
            });

            // Botões de ação da aba de Organização
            // NOTA: Os botões de ação da organização são agora gerenciados pelo ConteudoOrganizacao.php
            // que carrega dinamicamente com a função inicializarAcoesRapidas()

            // Botões de ação em massa
            const bindsMassa = [{
                    id: 'btn-confirmar-presencas-massa',
                    fn: confirmarPresencasEmMassa
                },
                {
                    id: 'btn-emitir-certificados-massa',
                    fn: emitirCertificadosEmMassa
                },
                {
                    id: 'btn-excluir-participantes-massa',
                    fn: excluirParticipantesEmMassa
                }
            ];
            bindsMassa.forEach(({
                id,
                fn
            }) => {
                const el = document.getElementById(id);
                if (el && !el.dataset.bound) {
                    el.dataset.bound = '1';
                    el.addEventListener('click', fn);
                }
            });

            // Verificar se CPF existe no sistema
            const addCpfInput = document.getElementById('add-cpf');
            if (addCpfInput && !addCpfInput.dataset.bound) {
                addCpfInput.dataset.bound = '1';
                addCpfInput.addEventListener('input', function(e) {
                    let valor = e.target.value.replace(/\D/g, '');
                    if (valor.length <= 11) {
                        valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
                        valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
                        valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                        e.target.value = valor;
                    }
                });
                addCpfInput.addEventListener('blur', verificarCPFExistente);
            }

        }

        function confirmarPresenca(cpf) {
            if (!confirm('Confirmar presença deste participante?')) return;

            fetch('GerenciarEvento.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'confirmar_presenca',
                        cod_evento: codEventoAtual,
                        cpf: cpf
                    })
                })
                .then(r => r.json())
                .then(d => {
                    if (d.sucesso) {
                        alert('Presença confirmada com sucesso!');
                        carregarParticipantes();
                    } else {
                        alert('Erro: ' + (d.erro || 'Erro desconhecido'));
                    }
                })
                .catch(() => alert('Erro ao confirmar presença'));
        }

        function excluirParticipante(cpf) {
            if (!confirm('Tem certeza que deseja excluir este participante?')) return;

            fetch('GerenciarEvento.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'excluir',
                        cod_evento: codEventoAtual,
                        cpf: cpf
                    })
                })
                .then(r => r.json())
                .then(d => {
                    if (d.sucesso) {
                        alert('Participante excluído com sucesso!');
                        carregarParticipantes();
                    } else {
                        alert('Erro: ' + (d.erro || 'Erro desconhecido'));
                    }
                })
                .catch(() => alert('Erro ao excluir participante'));
        }

        function editarDados(cpf) {
            const participante = todosParticipantes.find(p => p.cpf === cpf);
            if (!participante) {
                alert('Participante não encontrado');
                return;
            }

            // Preenche o formulário
            document.getElementById('edit-cpf').value = participante.cpf;
            document.getElementById('edit-cpf-display').value = formatarCPF(participante.cpf);
            document.getElementById('edit-nome').value = participante.nome;
            document.getElementById('edit-email').value = participante.email;
            document.getElementById('edit-ra').value = participante.ra || '';

            // Abre o modal
            const modal = document.getElementById('modalEditarDados');
            const modaisGlobais = document.getElementById('modais-globais');
            if (modal && modaisGlobais && modal.parentElement.id !== 'modais-globais') {
                modaisGlobais.appendChild(modal);
            }
            document.body.style.overflow = 'hidden';
            modal.classList.add('ativo');
        }

        function fecharModal() {
            document.getElementById('modalEditarDados').classList.remove('ativo');
            document.getElementById('formEditarDados').reset();
            document.body.style.overflow = '';
        }

        // fecharModalSeForFundo está definido como stub no início e será sobrescrito pelo ConteudoParticipantes.php
        // Aqui apenas estendemos a funcionalidade para os modais locais
        if (typeof window.fecharModalSeForFundoOriginal === 'undefined') {
            window.fecharModalSeForFundoOriginal = fecharModalSeForFundo;
        }
        fecharModalSeForFundo = function(event, modalId) {
            if (event.target.id === modalId) {
                if (modalId === 'modalEditarDados') fecharModal();
                else if (modalId === 'modalAdicionarParticipante') fecharModalAdicionar();
                else if (modalId === 'modalEnviarMensagem') fecharModalMensagem();
                else if (typeof window.fecharModalSeForFundoOriginal === 'function') {
                    window.fecharModalSeForFundoOriginal(event, modalId);
                }
            }
        };

        function formatarCPF(cpf) {
            return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
        }

        async function salvarEdicao(event) {
            event.preventDefault();

            const cpf = document.getElementById('edit-cpf').value;
            const nome = document.getElementById('edit-nome').value;
            const email = document.getElementById('edit-email').value;
            const ra = document.getElementById('edit-ra').value;

            try {
                const response = await fetch('GerenciarEvento.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'editar_dados',
                        cpf: cpf,
                        nome: nome,
                        email: email,
                        ra: ra
                    })
                });

                const data = await response.json();

                if (data.sucesso) {
                    alert('Dados atualizados com sucesso!');
                    fecharModal();
                    carregarParticipantes(); // Recarrega a lista
                } else {
                    alert('Erro ao atualizar dados: ' + (data.erro || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao salvar alterações');
            }
        }

        function verificarCertificado(cpf) {
            // Buscar código de verificação do certificado deste participante no evento atual
            const codEvento = getCodigoEvento();

            fetch(`GerenciarEvento.php?action=buscar_codigo_certificado&cpf=${cpf}&cod_evento=${codEvento}`)
                .then(response => response.json())
                .then(data => {
                    if (data.sucesso && data.codigo_verificacao) {
                        // Navega para visualizar o certificado dentro do container
                        window.location.href = `ContainerOrganizador.php?pagina=visualizarCertificadoGerenciar&codigo=${encodeURIComponent(data.codigo_verificacao)}&cod_evento=${codEvento}`;
                    } else {
                        alert(data.mensagem || 'Certificado não encontrado para este participante.');
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar certificado:', error);
                    alert('Erro ao buscar certificado. Tente novamente.');
                });
        }

        // ========== MODAL ADICIONAR PARTICIPANTE ==========
        function abrirModalAdicionar() {
            const modal = document.getElementById('modalAdicionarParticipante');
            const modaisGlobais = document.getElementById('modais-globais');
            if (modal && modaisGlobais && modal.parentElement.id !== 'modais-globais') {
                modaisGlobais.appendChild(modal);
            }
            document.body.style.overflow = 'hidden';
            modal.classList.add('ativo');
        }

        function fecharModalAdicionar() {
            document.getElementById('modalAdicionarParticipante').classList.remove('ativo');
            document.getElementById('formAdicionarParticipante').reset();
            document.getElementById('msg-cpf-existente').style.display = 'none';
            // Habilita campos novamente
            document.getElementById('add-nome').disabled = false;
            document.getElementById('add-email').disabled = false;
            document.getElementById('add-ra').disabled = false;
            document.body.style.overflow = '';
        }

        async function verificarCPFExistente() {
            const cpfInput = document.getElementById('add-cpf');
            const cpf = cpfInput.value.replace(/\D/g, '');

            if (cpf.length !== 11) return;

            try {
                const response = await fetch(`GerenciarEvento.php?action=verificar_cpf&cpf=${cpf}`);
                const data = await response.json();

                if (data.existe) {
                    // Preenche automaticamente os campos
                    document.getElementById('add-nome').value = data.usuario.nome;
                    document.getElementById('add-email').value = data.usuario.email;
                    document.getElementById('add-ra').value = data.usuario.ra || '';

                    // Desabilita campos (não pode modificar dados de usuário cadastrado)
                    document.getElementById('add-nome').disabled = true;
                    document.getElementById('add-email').disabled = true;
                    document.getElementById('add-ra').disabled = true;
                    document.getElementById('add-cpf').disabled = true;

                    document.getElementById('msg-cpf-existente').style.display = 'block';
                } else {
                    // Habilita campos para usuário não cadastrado
                    document.getElementById('add-nome').disabled = false;
                    document.getElementById('add-email').disabled = false;
                    document.getElementById('add-ra').disabled = false;
                    document.getElementById('msg-cpf-existente').style.display = 'none';
                }
            } catch (error) {
                console.error('Erro ao verificar CPF:', error);
            }
        }

        async function salvarNovoParticipante(event) {
            event.preventDefault();

            const cpf = document.getElementById('add-cpf').value.replace(/\D/g, '');
            const nome = document.getElementById('add-nome').value;
            const email = document.getElementById('add-email').value;
            const ra = document.getElementById('add-ra').value;

            if (cpf.length !== 11) {
                alert('CPF inválido');
                return;
            }

            try {
                const response = await fetch('GerenciarEvento.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'adicionar_participante',
                        cod_evento: codEventoAtual,
                        cpf: cpf,
                        nome: nome,
                        email: email,
                        ra: ra
                    })
                });

                const data = await response.json();

                if (data.sucesso) {
                    alert('Participante adicionado com sucesso!');
                    fecharModalAdicionar();
                    carregarParticipantes();
                } else {
                    alert('Erro ao adicionar participante: ' + (data.erro || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao adicionar participante');
            }
        }

        // ========== MODAL ENVIAR MENSAGEM ==========
        function abrirModalMensagem() {
            const modal = document.getElementById('modalEnviarMensagem');
            const modaisGlobais = document.getElementById('modais-globais');
            if (modal && modaisGlobais && modal.parentElement.id !== 'modais-globais') {
                modaisGlobais.appendChild(modal);
            }
            
            const msgSel = document.getElementById('msg-selecionados');
            const checkTodos = document.getElementById('msg-todos');

            if (participantesSelecionados.size > 0) {
                checkTodos.checked = false;
                msgSel.textContent = `Enviando para ${participantesSelecionados.size} participante(s) selecionado(s)`;
                msgSel.style.display = 'block';
            } else {
                checkTodos.checked = true;
                msgSel.style.display = 'none';
            }

            document.body.style.overflow = 'hidden';
            modal.classList.add('ativo');
        }

        function fecharModalMensagem() {
            document.getElementById('modalEnviarMensagem').classList.remove('ativo');
            document.getElementById('formEnviarMensagem').reset();
            document.body.style.overflow = '';
        }

        // ========== MODAL ENVIAR MENSAGEM PARA CPF ESPECÍFICO ==========
        window.abrirModalMensagemCPF = function(cpfPreenchido = '') {
            const modal = document.getElementById('modalEnviarMensagemCPF');

            // Move o modal para fora do conteudo-dinamico
            const modaisGlobais = document.getElementById('modais-globais');
            if (modal && modaisGlobais && modal.parentElement.id !== 'modais-globais') {
                modaisGlobais.appendChild(modal);
            }

            const inputCPF = document.getElementById('msg-cpf-destinatario');

            if (cpfPreenchido) {
                inputCPF.value = cpfPreenchido.replace(/\D/g, '');
                inputCPF.dataset.ehResposta = '1';
            } else {
                inputCPF.value = '';
                inputCPF.dataset.ehResposta = '0';
            }

            document.getElementById('formEnviarMensagemCPF').reset();
            if (cpfPreenchido) {
                inputCPF.value = cpfPreenchido.replace(/\D/g, '');
                inputCPF.dataset.ehResposta = '1';
            }

            document.body.style.overflow = 'hidden';
            modal.classList.add('ativo');
        };

        window.fecharModalMensagemCPF = function() {
            document.getElementById('modalEnviarMensagemCPF').classList.remove('ativo');
            document.getElementById('formEnviarMensagemCPF').reset();
            const inputCPF = document.getElementById('msg-cpf-destinatario');
            if (inputCPF) {
                inputCPF.dataset.ehResposta = '0';
            }
            document.body.style.overflow = '';
        };

        window.enviarMensagemCPF = async function(event) {
            event.preventDefault();

            const cpfDestinatario = document.getElementById('msg-cpf-destinatario').value.replace(/\D/g, '');
            const titulo = document.getElementById('msg-titulo-cpf').value;
            const conteudo = document.getElementById('msg-conteudo-cpf').value;

            if (cpfDestinatario.length !== 11) {
                alert('CPF deve conter 11 dígitos');
                return;
            }

            if (!confirm(`Enviar notificação para o CPF ${cpfDestinatario.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')}?`)) {
                return;
            }

            try {
                // Verifica se é uma resposta (quando o CPF foi preenchido automaticamente)
                const inputCPF = document.getElementById('msg-cpf-destinatario');
                const ehResposta = inputCPF && inputCPF.dataset.ehResposta === '1' ? '1' : '0';

                const response = await fetch('GerenciarEvento.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'enviar_notificacao_cpf',
                        cod_evento: codEventoAtual,
                        cpf_destinatario: cpfDestinatario,
                        titulo: titulo,
                        conteudo: conteudo,
                        eh_resposta: ehResposta
                    })
                });

                const data = await response.json();

                if (data.sucesso) {
                    const nomeDestinatario = data.nome_destinatario ? ` (${data.nome_destinatario})` : '';
                    alert(`Notificação enviada com sucesso para o CPF ${cpfDestinatario.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')}${nomeDestinatario}!`);
                    fecharModalMensagemCPF();
                } else {
                    if (data.erro === 'usuario_nao_encontrado') {
                        alert('CPF não encontrado no sistema. Verifique se o CPF está correto.');
                    } else {
                        alert('Erro ao enviar notificação: ' + (data.mensagem || data.erro || 'Erro desconhecido'));
                    }
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao enviar notificação');
            }
        };

        async function enviarMensagemParticipantes(event) {
            event.preventDefault();

            const titulo = document.getElementById('msg-titulo').value;
            const conteudo = document.getElementById('msg-conteudo').value;
            const enviarTodos = document.getElementById('msg-todos').checked;

            const destinatarios = enviarTodos ?
                todosParticipantes.map(p => p.cpf) :
                Array.from(participantesSelecionados);

            if (destinatarios.length === 0) {
                alert('Selecione pelo menos um participante');
                return;
            }

            if (!confirm(`Enviar notificação para ${destinatarios.length} participante(s)?`)) {
                return;
            }

            try {
                const response = await fetch('GerenciarEvento.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'enviar_notificacao',
                        cod_evento: codEventoAtual,
                        titulo: titulo,
                        conteudo: conteudo,
                        destinatarios: JSON.stringify(destinatarios)
                    })
                });

                const data = await response.json();

                if (data.sucesso) {
                    alert(`Notificação enviada com sucesso para ${data.total_enviadas} participante(s)!`);
                    fecharModalMensagem();
                } else {
                    alert('Erro ao enviar notificação: ' + (data.erro || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao enviar notificação');
            }
        }

        // ========== AÇÕES EM MASSA ==========
        async function confirmarPresencasEmMassa() {
            if (todosParticipantes.length === 0) {
                alert('Não há participantes inscritos neste evento');
                return;
            }

            if (participantesSelecionados.size === 0) {
                alert('Selecione pelo menos um participante');
                return;
            }

            if (!confirm(`Confirmar presença de ${participantesSelecionados.size} participante(s)?`)) {
                return;
            }

            let confirmados = 0;
            let erros = 0;

            for (const cpf of participantesSelecionados) {
                try {
                    const response = await fetch('GerenciarEvento.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'confirmar_presenca',
                            cod_evento: codEventoAtual,
                            cpf: cpf
                        })
                    });

                    const data = await response.json();
                    if (data.sucesso) {
                        confirmados++;
                    } else {
                        erros++;
                    }
                } catch (error) {
                    erros++;
                    console.error('Erro ao confirmar presença:', error);
                }
            }

            alert(`Operação concluída!\nConfirmados: ${confirmados}\nErros: ${erros}`);
            participantesSelecionados.clear();
            carregarParticipantes();
        }

        async function emitirCertificadosEmMassa() {
            if (todosParticipantes.length === 0) {
                alert('Não há participantes inscritos neste evento');
                return;
            }

            if (participantesSelecionados.size === 0) {
                alert('Selecione pelo menos um participante');
                return;
            }

            if (!confirm(`Emitir certificado para ${participantesSelecionados.size} participante(s)?\n\nAtenção: Apenas participantes com presença confirmada receberão o certificado.`)) {
                return;
            }

            let emitidos = 0;
            let erros = 0;
            let semPresenca = 0;

            for (const cpf of participantesSelecionados) {
                try {
                    const response = await fetch('GerenciarEvento.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'emitir_certificado',
                            cod_evento: codEventoAtual,
                            cpf: cpf
                        })
                    });

                    const data = await response.json();
                    if (data.sucesso) {
                        emitidos++;
                    } else if (data.erro === 'presenca_nao_confirmada') {
                        semPresenca++;
                    } else {
                        erros++;
                    }
                } catch (error) {
                    erros++;
                    console.error('Erro ao emitir certificado:', error);
                }
            }

            let mensagem = `Operação concluída!\nCertificados emitidos: ${emitidos}`;
            if (semPresenca > 0) {
                mensagem += `\nSem presença confirmada: ${semPresenca}`;
            }
            if (erros > 0) {
                mensagem += `\nErros: ${erros}`;
            }

            alert(mensagem);
            participantesSelecionados.clear();
            carregarParticipantes();
        }

        async function excluirParticipantesEmMassa() {
            if (todosParticipantes.length === 0) {
                alert('Não há participantes inscritos neste evento');
                return;
            }

            if (participantesSelecionados.size === 0) {
                alert('Selecione pelo menos um participante');
                return;
            }

            if (!confirm(`ATENÇÃO: Excluir ${participantesSelecionados.size} participante(s)?\n\nEsta ação não pode ser desfeita!`)) {
                return;
            }

            let excluidos = 0;
            let erros = 0;

            for (const cpf of participantesSelecionados) {
                try {
                    const response = await fetch('GerenciarEvento.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'excluir',
                            cod_evento: codEventoAtual,
                            cpf: cpf
                        })
                    });

                    const data = await response.json();
                    if (data.sucesso) {
                        excluidos++;
                    } else {
                        erros++;
                    }
                } catch (error) {
                    erros++;
                    console.error('Erro ao excluir participante:', error);
                }
            }

            alert(`Operação concluída!\nExcluídos: ${excluidos}\nErros: ${erros}`);
            participantesSelecionados.clear();
            carregarParticipantes();
        }
    </script>
</body>

</html>