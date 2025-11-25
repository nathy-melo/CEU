<?php
// Verificar sessão e autenticação
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['cpf'])) {
    header('Location: ../index.php');
    exit;
}

// Parametros obrigatórios
$codigoVerificacao = isset($_GET['codigo']) ? trim($_GET['codigo']) : '';

if (!$codigoVerificacao) {
    echo "<p style='color: red; padding: 20px;'>Código de verificação inválido ou não fornecido.</p>";
    exit;
}

// Buscar informações do certificado no banco
require_once __DIR__ . '/../BancoDados/conexao.php';

// Buscar certificado pelo código de verificação
$consultaCertificado = "SELECT 
                            c.cod_verificacao,
                            c.cpf,
                            c.cod_evento,
                            c.arquivo,
                            c.modelo,
                            c.tipo,
                            c.criado_em,
                            u.Nome as nome_participante,
                            e.nome as nome_evento
                        FROM certificado c
                        JOIN usuario u ON c.cpf = u.CPF
                        JOIN evento e ON c.cod_evento = e.cod_evento
                        WHERE c.cod_verificacao = ?
                        LIMIT 1";

$stmt = mysqli_prepare($conexao, $consultaCertificado);
mysqli_stmt_bind_param($stmt, "s", $codigoVerificacao);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$certificado = mysqli_fetch_assoc($resultado);
mysqli_stmt_close($stmt);

if (!$certificado) {
    mysqli_close($conexao);
    echo "<p style='color: red; padding: 20px;'>Certificado não encontrado.</p>";
    exit;
}

// Extrair dados do certificado
$cpfParticipante = $certificado['cpf'];
$codEvento = $certificado['cod_evento'];

// Verificar permissão de acesso: só o dono do certificado ou organizador do evento pode visualizar
$cpfUsuario = $_SESSION['cpf'];
$temPermissao = false;

// 1. Verifica se é o próprio dono do certificado
if ($cpfUsuario === $cpfParticipante) {
    $temPermissao = true;
} else {
    // 2. Verifica se é organizador ou colaborador do evento
    $consultaPermissao = "SELECT 1 FROM organiza WHERE cod_evento = ? AND CPF = ?
                          UNION
                          SELECT 1 FROM colaboradores_evento WHERE cod_evento = ? AND CPF = ?
                          LIMIT 1";
    $stmtPermissao = mysqli_prepare($conexao, $consultaPermissao);
    mysqli_stmt_bind_param($stmtPermissao, "isis", $codEvento, $cpfUsuario, $codEvento, $cpfUsuario);
    mysqli_stmt_execute($stmtPermissao);
    $resultPermissao = mysqli_stmt_get_result($stmtPermissao);

    if (mysqli_fetch_assoc($resultPermissao)) {
        $temPermissao = true;
    }
    mysqli_stmt_close($stmtPermissao);
}

if (!$temPermissao) {
    mysqli_close($conexao);
?>
    <!DOCTYPE html>
    <html lang="pt-BR">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acesso Negado</title>
        <style>
            @import url('../styleGlobal.css');
            @import url('../styleGlobalMobile.css') (max-width: 767px);
        </style>
        <style>
            body {
                background: var(--fundo);
                padding: 40px;
            }

            .erro-container {
                max-width: 600px;
                margin: 0 auto;
                background: white;
                padding: 30px;
                border-radius: 8px;
                text-align: center;
            }

            .erro-container h1 {
                color: var(--vermelho);
                margin-bottom: 15px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
            }

            .erro-container h1 svg {
                width: 28px;
                height: 28px;
            }

            .erro-container p {
                color: var(--cinza-escuro);
                margin-bottom: 20px;
            }

            .botao-voltar {
                background: var(--botao);
                color: white;
                padding: 12px 24px;
                border-radius: 6px;
                text-decoration: none;
                display: inline-block;
            }
        </style>
    </head>

    <body>
        <div class='erro-container'>
            <h1>
                <svg width='28' height='28' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'>
                    <rect x='3' y='11' width='18' height='11' rx='2' ry='2' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' />
                    <path d='M7 11V7C7 5.67392 7.52678 4.40215 8.46447 3.46447C9.40215 2.52678 10.6739 2 12 2C13.3261 2 14.5979 2.52678 15.5355 3.46447C16.4732 4.40215 17 5.67392 17 7V11' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' />
                </svg>
                Acesso Negado
            </h1>
            <p>Você não tem permissão para visualizar este certificado.</p>
            <p>Somente o participante ou organizadores do evento podem acessar.</p>
            <a href='javascript:window.close()' class='botao-voltar'>Fechar</a>
        </div>
    </body>

    </html>
<?php
    exit;
}

$arquivoPdf = '../' . $certificado['arquivo'];
$codigoVerificacao = $certificado['cod_verificacao'];
$nomeParticipante = $certificado['nome_participante'];
$nomeEvento = $certificado['nome_evento'];
$dataEmissao = date('d/m/Y H:i', strtotime($certificado['criado_em']));
$tipoCertificado = ucfirst($certificado['tipo']);

// Verifica se o arquivo PDF existe
$arquivoExiste = file_exists($arquivoPdf);
$debugLog = []; // Array para logs de debug

// Se não existe, tenta regenerar silenciosamente
if (!$arquivoExiste) {
    $debugLog[] = "ðŸ” Arquivo não existe: $arquivoPdf";

    try {
        // Buscar dados completos para regeneração
        $queryDados = "SELECT 
                            u.Nome, u.CPF, 
                            e.nome, e.duracao, e.lugar, e.inicio,
                            o.Nome as nome_org
                        FROM usuario u
                        JOIN evento e ON e.cod_evento = ?
                        LEFT JOIN usuario o ON o.CPF = (SELECT CPF FROM organiza WHERE cod_evento = e.cod_evento LIMIT 1)
                        WHERE u.CPF = ?
                        LIMIT 1";

        $stmtDados = mysqli_prepare($conexao, $queryDados);
        mysqli_stmt_bind_param($stmtDados, "is", $certificado['cod_evento'], $certificado['cpf']);
        mysqli_stmt_execute($stmtDados);
        $resDados = mysqli_stmt_get_result($stmtDados);
        $dados = mysqli_fetch_assoc($resDados);
        mysqli_stmt_close($stmtDados);

        $debugLog[] = "📚 Dados do usuário/evento: " . ($dados ? "ENCONTRADOS" : "NÃO ENCONTRADOS");

        if ($dados) {
            // Incluir o autoload de bibliotecas
            $autoloadPath = __DIR__ . '/../Certificacao/bibliotecas/vendor/autoload.php';
            $autoloadExiste = file_exists($autoloadPath);
            $debugLog[] = "📚 Autoload: " . ($autoloadExiste ? "EXISTE" : "NÃO EXISTE") . " em $autoloadPath";

            if ($autoloadExiste) {
                require_once $autoloadPath;
            }

            // Carregar ProcessadorTemplate e tentar gerar
            require_once __DIR__ . '/../Certificacao/ProcessadorTemplate.php';
            $debugLog[] = "… ProcessadorTemplate carregado";

            try {
                $proc = new \CEU\Certificacao\ProcessadorTemplate($autoloadPath);
                $debugLog[] = "… ProcessadorTemplate instanciado";

                // Obter template do modelo
                $modelo = $certificado['modelo'] ?? 'universal';
                $tipo = $certificado['tipo'] ?? 'participante';

                // Buscar arquivo de template (pode ser .docx, .pptx, etc)
                $possiveisExtensoes = ['docx', 'pptx', 'doc', 'ppt'];
                $templatePath = null;

                // Tenta primeiro com o modelo específico
                foreach ($possiveisExtensoes as $ext) {
                    $caminho = __DIR__ . "/../Certificacao/templates/$modelo.$ext";
                    if (file_exists($caminho)) {
                        $templatePath = $caminho;
                        break;
                    }
                }

                // Se não encontrou, usa o modelo padrão baseado no tipo
                if (!$templatePath) {
                    $modeloPadrao = (strtolower($tipo) === 'organizador' || strtolower($tipo) === 'org')
                        ? 'ModeloExemploOrganizador'
                        : 'ModeloExemplo';

                    $debugLog[] = "⚠️ Template '$modelo' não encontrado, tentando padrão: $modeloPadrao";

                    foreach ($possiveisExtensoes as $ext) {
                        $caminho = __DIR__ . "/../Certificacao/templates/$modeloPadrao.$ext";
                        if (file_exists($caminho)) {
                            $templatePath = $caminho;
                            break;
                        }
                    }
                }

                $debugLog[] = "📄 Template final: " . ($templatePath ? "EXISTE em $templatePath" : "NÃO ENCONTRADO");

                if ($templatePath) {
                    // Preparar dados para preenchimento
                    $dadosCert = [
                        'NomeParticipante' => $dados['Nome'],
                        'CPF' => $dados['CPF'],
                        'NomeEvento' => $dados['nome'],
                        'LocalEvento' => $dados['lugar'] ?? '',
                        'DataEvento' => $dados['inicio'] ?? '',
                        'CargaHoraria' => $dados['duracao'] ?? '',
                        'CodigoVerificacao' => $codigoVerificacao,
                        'DataEmissao' => date('d/m/Y H:i')
                    ];

                    // Se for certificado de PARTICIPANTE, adiciona o nome do organizador
                    // Se for de ORGANIZADOR, não adiciona (pois a própria pessoa é o organizador)
                    if (strtolower($tipo) === 'participante') {
                        $dadosCert['NomeOrganizador'] = $dados['nome_org'] ?? '';
                        $dadosCert['CargoOrganizador'] = '';
                    }

                    $debugLog[] = "📄 Tipo de certificado: $tipo";
                    $debugLog[] = "📄 Dados do certificado preparados: " . json_encode($dadosCert, JSON_UNESCAPED_UNICODE);

                    // Diretório de saída
                    $pastaSaida = __DIR__ . '/../Certificacao/certificados';
                    if (!is_dir($pastaSaida)) {
                        mkdir($pastaSaida, 0755, true);
                        $debugLog[] = "📁 Pasta criada: $pastaSaida";
                    } else {
                        $debugLog[] = "📁 Pasta já existe: $pastaSaida";
                    }

                    // Extrair nome do arquivo original da coluna 'arquivo'
                    $arquivoOriginal = basename($certificado['arquivo']);
                    $caminhoSaida = $pastaSaida . '/' . $arquivoOriginal;
                    $debugLog[] = "📎 Caminho de saída: $caminhoSaida";

                    // Gerar PDF
                    $debugLog[] = "🚀 Iniciando geração do PDF...";
                    $resultado = $proc->gerarPdfDeModelo($templatePath, $dadosCert, $caminhoSaida);
                    $debugLog[] = "📋 Resultado da geração: " . json_encode($resultado, JSON_UNESCAPED_UNICODE);

                    // Verifica sucesso (pode ser 'sucesso' ou 'success')
                    $sucesso = ($resultado['sucesso'] ?? $resultado['success'] ?? false);

                    if ($sucesso) {
                        // Atualizar verificação
                        $arquivoExiste = file_exists($arquivoPdf);
                        $debugLog[] = "✅ PDF gerado! Arquivo existe agora? " . ($arquivoExiste ? "SIM" : "NÃO");

                        // Se foi gerado com sucesso, marcar para recarregar a página
                        if ($arquivoExiste) {
                            $debugLog[] = "🔄 Recarregando página para exibir o certificado...";
                            echo "<script>window.location.reload();</script>";
                            exit;
                        }
                    } else {
                        $debugLog[] = "❌ Falha ao gerar PDF: " . ($resultado['erro'] ?? $resultado['error'] ?? 'sem detalhes');
                    }
                } else {
                    $debugLog[] = "❌ Template não encontrado, abortando geração";
                }
            } catch (Exception $e) {
                $debugLog[] = "❌ ERRO ProcessadorTemplate: " . $e->getMessage();
                $debugLog[] = "📍 Arquivo: " . $e->getFile() . " Linha: " . $e->getLine();
            }
        } else {
            $debugLog[] = "❌ Não foi possível buscar dados do evento/usuário";
        }
    } catch (Exception $e) {
        $debugLog[] = "❌ ERRO GERAL: " . $e->getMessage();
        $debugLog[] = "📍 Arquivo: " . $e->getFile() . " Linha: " . $e->getLine();
    }
}
?>

<style>
    #main-content {
        transition: margin-left 0.3s ease;
        width: 100%;
        display: flex;
        justify-content: center;
    }

    .container-certificado {
        padding: 20px;
        width: 100%;
        max-width: 1200px;
        box-sizing: border-box;
    }

    .header-certificado {
        background: var(--caixas);
        padding: 20px 25px;
        border-radius: 6px;
        color: white;
        margin-bottom: 20px;
    }

    .header-certificado h1 {
        margin: 0 0 12px 0;
        font-size: 20px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .header-certificado h1 svg {
        width: 24px;
        height: 24px;
    }

    .header-certificado .info-linha {
        margin: 6px 0;
        font-size: 13px;
        opacity: 0.95;
    }

    .header-certificado .info-linha strong {
        font-weight: 600;
        margin-right: 5px;
    }

    .codigo-inline {
        background: rgba(255, 255, 255, 0.2);
        padding: 2px 8px;
        border-radius: 4px;
        font-weight: bold;
        letter-spacing: 1px;
        cursor: help;
        transition: background 0.2s;
    }

    .codigo-inline:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    .acoes-certificado {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .botao {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 6px;
        text-decoration: none;
        transition: all 0.2s ease;
        cursor: pointer;
        border: none;
        font-size: 13px;
    }

    .botao-azul {
        background: var(--botao);
        color: white;
    }

    .botao-azul:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }

    .botao-verde {
        background: var(--verde);
        color: white;
    }

    .botao-verde:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }

    .botao-cinza {
        background: white;
        color: var(--cinza-escuro);
        border: 1px solid rgba(0, 0, 0, 0.2);
    }

    .botao-cinza:hover {
        background: var(--vermelho);
        color: white;
    }

    .botao svg {
        width: 16px;
        height: 16px;
    }

    .viewer-container {
        background: white;
        border-radius: 6px;
        padding: 0;
        overflow: hidden;
        border: 1px solid rgba(0, 0, 0, 0.1);
    }

    .pdf-embed {
        width: 100%;
        height: 600px;
        border: none;
        display: block;
        background: #fafafa;
    }

    @media (max-width: 768px) {
        .container-certificado {
            padding: 15px;
        }

        .header-certificado {
            padding: 15px 20px;
        }

        .header-certificado h1 {
            font-size: 18px;
        }

        .card-info {
            padding: 15px 20px;
        }

        .info-grid {
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .acoes-certificado {
            flex-direction: column;
        }

        .botao {
            width: 100%;
            justify-content: center;
        }

        .pdf-embed {
            height: 450px;
        }
    }
</style>

<div id="main-content">
    <div class="container-certificado">
        <!-- Header com Informações -->
        <div class="header-certificado">
            <h1>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M14 2V8H20" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M12 18V12" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M9 15H15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Certificado Digital
            </h1>
            <div class="info-linha"><strong>Nome:</strong> <?php echo htmlspecialchars($nomeParticipante); ?></div>
            <div class="info-linha"><strong>Evento:</strong> <?php echo htmlspecialchars($nomeEvento); ?></div>
            <div class="info-linha"><strong>Tipo:</strong> <?php echo htmlspecialchars($tipoCertificado); ?> | <strong>Emitido em:</strong> <?php echo $dataEmissao; ?></div>
            <div class="info-linha">
                <strong>Código:</strong>
                <span class="codigo-inline" title="Use este código para validar a autenticidade do certificado"><?php echo htmlspecialchars($codigoVerificacao); ?></span>
            </div>
        </div>

        <!-- Ações -->
        <div class="acoes-certificado">
            <?php if ($arquivoExiste): ?>
                <a href="<?php echo $arquivoPdf; ?>" download class="botao botao-azul">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M7 10L12 15L17 10" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M12 15V3" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Baixar PDF
                </a>
                <button onclick="imprimirCertificado()" class="botao botao-verde">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 9V2H18V9" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M6 18H4C3.46957 18 2.96086 17.7893 2.58579 17.4142C2.21071 17.0391 2 16.5304 2 16V11C2 10.4696 2.21071 9.96086 2.58579 9.58579C2.96086 9.21071 3.46957 9 4 9H20C20.5304 9 21.0391 9.21071 21.4142 9.58579C21.7893 9.96086 22 10.4696 22 11V16C22 16.5304 21.7893 17.0391 21.4142 17.4142C21.0391 17.7893 20.5304 18 20 18H18" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M18 14H6V22H18V14Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Imprimir
                </button>
            <?php endif; ?>
            <button onclick="window.location.href='ContainerParticipante.php?pagina=certificados'" class="botao botao-cinza">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 12H5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M12 19L5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Voltar
            </button>
        </div>

        <!-- Visualizador do PDF -->
        <?php if ($arquivoExiste): ?>
            <div class="viewer-container">
                <embed
                    src="<?php echo $arquivoPdf; ?>#toolbar=0&navpanes=0&scrollbar=0&view=FitH"
                    type="application/pdf"
                    class="pdf-embed">
            </div>
        <?php else: ?>
            <div class="viewer-container" style="height: 600px; display: flex; align-items: center; justify-content: center; background: #f5f5f5;">
                <div style="text-align: center; padding: 40px; max-width: 500px;">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin: 0 auto 20px; color: #d9534f;">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" />
                        <path d="M12 8V12" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        <circle cx="12" cy="15.5" r="0.5" fill="currentColor" />
                    </svg>
                    <h2 style="color: var(--cinza-escuro); margin: 0 0 15px 0; font-size: 18px;">Arquivo Não Encontrado</h2>
                    <p style="color: var(--cinza-escuro); font-size: 14px; line-height: 1.6;">
                        Não foi possível exibir o certificado. Por favor, tente novamente mais tarde.
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Logs de debug da regeneração do certificado
        <?php if (!empty($debugLog)): ?>
            console.group('DEBUG: Regeneração do Certificado');
            <?php foreach ($debugLog as $log): ?>
                console.log(<?php echo json_encode($log, JSON_UNESCAPED_UNICODE); ?>);
            <?php endforeach; ?>
            console.groupEnd();
        <?php endif; ?>

        function imprimirCertificado() {
            const janela = window.open('<?php echo $arquivoPdf; ?>', '_blank');
            if (janela) {
                janela.onload = function() {
                    janela.print();
                };
            } else {
                alert('Por favor, permita pop-ups para imprimir o certificado.');
            }
        }
    </script>
</div>
</div>