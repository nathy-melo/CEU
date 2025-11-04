<?php

/**
 * Visualização/Geração universal (DOCX/PPTX/ODT/ODP/FODT/FODP -> PDF via LibreOffice)
 * - Usa preenchimento por ZIP+XML quando aplicável
 * - Converte para PDF via LibreOffice (soffice). Se falhar, informa diagnóstico
 * - Variáveis e nomes em português conforme solicitado
 */

use CEU\Certificacao\ProcessadorTemplate;
use CEU\Certificacao\RepositorioCertificados;

$diretorioBase = __DIR__;

// Carrega configurações em português
$config = require $diretorioBase . '/config.php';
$caminhoAutoload       = $config['caminho_autoload'] ?? ($diretorioBase . '/bibliotecas/vendor/autoload.php');
$diretorioModelos      = $config['diretorio_modelos'] ?? ($diretorioBase . '/templates');
$diretorioCertificados = $config['diretorio_certificados'] ?? ($diretorioBase . '/certificados');
$diretorioTemporario   = $config['diretorio_temporario'] ?? sys_get_temp_dir();

$parametroArquivo = isset($_GET['arquivo']) ? (string)$_GET['arquivo'] : '';

// Resolver o caminho do modelo: se relativo, procurar dentro de templates/
if ($parametroArquivo) {
    $caminhoModelo = $parametroArquivo;
    if (!preg_match('#^([a-zA-Z]:\\\\|/|\\\\)#', $caminhoModelo)) { // não é absoluto
        $talvez = $diretorioBase . '/' . ltrim($caminhoModelo, '/\\');
        if (file_exists($talvez)) {
            $caminhoModelo = $talvez;
        } else {
            $caminhoModelo = rtrim($diretorioModelos, DIRECTORY_SEPARATOR) . '/' . ltrim($caminhoModelo, '/\\');
        }
    }
} else {
    // Candidatos padrão
    $candidatos = [
        $diretorioModelos . '/certificado_padrao.pptx',
        $diretorioModelos . '/ModeloExemplo.pptx',
        $diretorioModelos . '/certificado_padrao.docx',
        $diretorioModelos . '/certificado_padrao.odt',
        $diretorioBase . '/ModeloExemplo.pptx',
    ];
    $caminhoModelo = null;
    foreach ($candidatos as $c) {
        if (file_exists($c)) { $caminhoModelo = $c; break; }
    }
}

if (!is_dir($diretorioCertificados)) { @mkdir($diretorioCertificados, 0775, true); }

$dadosCertificado = [
    'NomeParticipante'   => $_GET['nome'] ?? '',
    'NomeEvento'         => $_GET['evento'] ?? '',
    'NomeOrganizador'    => $_GET['organizador'] ?? 'Equipe CEU',
    'LocalEvento'        => $_GET['local'] ?? '',
    'Data'               => $_GET['data'] ?? date('d/m/Y'),
    'CargaHoraria'       => $_GET['carga'] ?? '',
    // CodigoAutenticador será definido pelo servidor
];

$erroMensagem = null;
$resposta = null;
$arquivoPdf = $diretorioCertificados . '/certificado_universal_' . date('Ymd_His') . '.pdf';
try {
    if (!file_exists($caminhoAutoload)) {
        throw new \Exception('Dependências não encontradas. Abra Certificacao/index.php e conclua a instalação.');
    }
    if (!$caminhoModelo || !file_exists($caminhoModelo)) {
        throw new \Exception('Modelo não encontrado. Informe ?arquivo=templates/meu_modelo.(pptx|docx|odt|odp)');
    }
    // Parâmetros obrigatórios para vincular certificado
    $cpf = isset($_GET['cpf']) ? preg_replace('/\D+/', '', (string)$_GET['cpf']) : '';
    $codEvento = isset($_GET['cod_evento']) ? (int)$_GET['cod_evento'] : 0;
    if (!$cpf || strlen($cpf) !== 11 || $codEvento <= 0) {
        throw new \Exception('Parâmetros obrigatórios ausentes ou inválidos: cpf (11 dígitos) e cod_evento.');
    }

    // Conectar ao banco
    $caminhoConexao = realpath($diretorioBase . '/../BancoDados/conexao.php');
    if (!$caminhoConexao || !file_exists($caminhoConexao)) {
        throw new \Exception('Conexão com banco de dados não encontrada em BancoDados/conexao.php');
    }
    require_once $caminhoConexao; // define $conexao (mysqli)
    if (!isset($conexao) || !($conexao instanceof \mysqli)) {
        throw new \Exception('Falha ao inicializar conexão com o banco de dados');
    }

    // Preparar repositório e código único
    require_once $diretorioBase . '/RepositorioCertificados.php';
    require_once $diretorioBase . '/ProcessadorTemplate.php';
    $repositorio = new RepositorioCertificados($conexao);
    $repositorio->garantirEsquema();

    // Verifica se CPF participou do evento (inscrição ativa)
    $stmt = $conexao->prepare('SELECT e.nome, e.lugar, e.duracao, e.certificado, DATE_FORMAT(e.conclusao, "%d/%m/%Y") as data_formatada FROM evento e INNER JOIN inscricao i ON i.cod_evento = e.cod_evento AND i.status = "ativa" WHERE i.CPF = ? AND e.cod_evento = ? LIMIT 1');
    $stmt->bind_param('si', $cpf, $codEvento);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $dadosEvento = $resultado->fetch_assoc();
    $stmt->close();
    if (!$dadosEvento) {
        throw new \Exception('Participação não encontrada ou inativa para este CPF neste evento.');
    }
    if (!empty($dadosEvento['certificado']) && (int)$dadosEvento['certificado'] === 0) {
        throw new \Exception('Este evento não disponibiliza certificado.');
    }

    // Busca nome do participante
    $stmt = $conexao->prepare('SELECT Nome FROM usuario WHERE CPF = ? LIMIT 1');
    $stmt->bind_param('s', $cpf);
    $stmt->execute();
    $resU = $stmt->get_result();
    $dadosUsuario = $resU->fetch_assoc();
    $stmt->close();

    // Preenche dados do certificado a partir do BD
    if (!empty($dadosUsuario['Nome'])) { $dadosCertificado['NomeParticipante'] = $dadosUsuario['Nome']; }
    if (!empty($dadosEvento['nome'])) { $dadosCertificado['NomeEvento'] = $dadosEvento['nome']; }
    if (!empty($dadosEvento['lugar'])) { $dadosCertificado['LocalEvento'] = $dadosEvento['lugar']; }
    if (!empty($dadosEvento['data_formatada'])) { $dadosCertificado['Data'] = $dadosEvento['data_formatada']; }
    if (!empty($dadosEvento['duracao'])) { $dadosCertificado['CargaHoraria'] = rtrim(rtrim((string)$dadosEvento['duracao'], '0'), '.') . ' horas'; }
    $dadosCertificado['CPF'] = $cpf;

    // Se já existe certificado para este par CPF+evento, reutiliza
    $existente = $repositorio->buscarPorCpfEvento($cpf, $codEvento);
    if ($existente && !empty($existente['arquivo'])) {
        $arquivoPdf = $diretorioBase . '/' . $existente['arquivo'];
        if (!file_exists($arquivoPdf)) {
            // Se o arquivo sumiu, força regeneração
            $arquivoPdf = $diretorioCertificados . '/certificado_universal_' . date('Ymd_His') . '.pdf';
        } else {
            // Pula geração; segue para exibição
            $dadosCertificado['CodigoAutenticador'] = $existente['cod_verificacao'] ?? '';
            $resposta = ['success' => true, 'pdf' => $arquivoPdf];
        }
    }

    if (empty($resposta['success'])) {
        $codigoUnico = $repositorio->gerarCodigoUnico(8);
        $dadosCertificado['CodigoAutenticador'] = $codigoUnico;
        $processador = new ProcessadorTemplate($caminhoAutoload);
        $resposta = $processador->gerarPdfDeModelo($caminhoModelo, $dadosCertificado, $arquivoPdf, $diretorioTemporario);
        if (empty($resposta['success'])) {
            throw new \Exception($resposta['message'] ?? 'Falha ao gerar PDF.');
        }

        $arquivoRelativo = 'certificados/' . basename($arquivoPdf);
        $repositorio->salvarCertificado(
            $dadosCertificado['CodigoAutenticador'],
            $arquivoRelativo,
            basename($caminhoModelo),
            strtoupper(pathinfo($caminhoModelo, PATHINFO_EXTENSION)),
            $dadosCertificado,
            $cpf,
            $codEvento
        );
    }
} catch (\Throwable $e) {
    $erroMensagem = $e->getMessage();
}

?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Certificado Universal</title>
    <style>
        body {
            margin: 0;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
            background: #eef5ff;
            color: #1a1a1a;
        }

        header {
            padding: 16px 20px;
            background: #334b68;
            color: #fff;
        }

        .wrap {
            padding: 16px;
        }

        .alert {
            padding: 12px 14px;
            border-radius: 8px;
            margin: 12px 0;
        }

        .alert.info {
            background: #e6f0ff;
            color: #16365f;
        }

        .alert.error {
            background: #ffebeb;
            color: #7a1f1f;
        }

        .actions {
            margin: 12px 0 20px;
        }

        .btn {
            background: #6598d2;
            color: #fff;
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
        }

        iframe {
            width: 100%;
            height: 75vh;
            border: 0;
            background: #fff;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .1);
            border-radius: 8px;
        }

        code {
            background: #0001;
            padding: 2px 6px;
            border-radius: 6px;
        }
    </style>
</head>

<body>
    <header>
        <strong>CEU · Certificação (Universal)</strong>
    </header>
    <div class="wrap">
        <?php if ($erroMensagem): ?>
            <div class="alert error">Erro: <?= htmlspecialchars($erroMensagem) ?></div>
            <?php if ($resposta): ?>
                <pre><?= htmlspecialchars(json_encode($resposta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></pre>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert info">PDF gerado a partir de <code><?= htmlspecialchars(basename($caminhoModelo)) ?></code></div>
            <div class="actions">
                <a class="btn" href="<?= 'certificados/' . rawurlencode(basename($arquivoPdf)) ?>" download>Baixar PDF</a>
                <a class="btn" href="?arquivo=<?= urlencode(isset($_GET['arquivo']) ? $_GET['arquivo'] : basename($caminhoModelo)) ?>&viewer=pdfjs&<?= http_build_query(array_diff_key($_GET, ['arquivo' => 1, 'viewer' => 1])) ?>">Visualizar sem barras</a>
                <?php if (!empty($dadosCertificado['CodigoAutenticador'])): ?>
                    <a class="btn" href="ver_certificado.php?codigo=<?= urlencode($dadosCertificado['CodigoAutenticador']) ?>">Abrir por código</a>
                <?php endif; ?>
            </div>
            <?php $urlPdf = 'certificados/' . rawurlencode(basename($arquivoPdf)); ?>
            <?php if ((isset($_GET['viewer']) && $_GET['viewer'] === 'pdfjs')): ?>
                <iframe src="pdf_viewer.php?file=<?= rawurlencode(basename($arquivoPdf)) ?>" allowfullscreen></iframe>
            <?php else: ?>
                <!-- Tenta esconder barras do visualizador nativo (nem todos os navegadores respeitam) -->
                <iframe src="<?= $urlPdf ?>#toolbar=0&navpanes=0&scrollbar=0&view=FitH" allowfullscreen></iframe>
            <?php endif; ?>
        <?php endif; ?>
        <div style="margin-top:12px; font-size:.9rem; opacity:.8;">
            Dica: informe um template com ?arquivo=templates/meu_modelo.pptx e parâmetros como ?nome=...&evento=...&data=... · Para ocultar barras do navegador, use o botão "Visualizar sem barras" (usa PDF.js simples).<br>
            Código gerado automaticamente e salvo no banco; utilize o botão "Abrir por código" para simular o acesso público.
        </div>
    </div>
</body>

</html>