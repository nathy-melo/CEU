<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Verifica se o usuário está logado
if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
    echo json_encode(['erro' => 'Usuário não autenticado']);
    exit;
}

// Verifica se é organizador
if (!isset($_SESSION['organizador']) || $_SESSION['organizador'] != 1) {
    echo json_encode(['erro' => 'Usuário não é organizador']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

require_once('../BancoDados/conexao.php');

$cpfOrganizadorLogado = $_SESSION['cpf'];

// Recebe os dados do formulário
$codigoEvento = isset($_POST['cod_evento']) ? intval($_POST['cod_evento']) : 0;
$nomeEvento = $_POST['nome'] ?? '';
$localEvento = $_POST['local'] ?? '';
$dataInicio = $_POST['data_inicio'] ?? '';
$dataFim = $_POST['data_fim'] ?? '';
$horarioInicio = $_POST['horario_inicio'] ?? '';
$horarioFim = $_POST['horario_fim'] ?? '';
$dataInicioInscricao = $_POST['data_inicio_inscricao'] ?? '';
$dataFimInscricao = $_POST['data_fim_inscricao'] ?? '';
$horarioInicioInscricao = $_POST['horario_inicio_inscricao'] ?? '';
$horarioFimInscricao = $_POST['horario_fim_inscricao'] ?? '';
$publicoAlvo = $_POST['publico_alvo'] ?? '';
$categoriaEvento = $_POST['categoria'] ?? '';
$modalidadeEvento = $_POST['modalidade'] ?? '';
$certificadoEvento = $_POST['certificado'] ?? '';
$descricaoEvento = $_POST['descricao'] ?? '';

// Validação básica dos campos obrigatórios
if ($codigoEvento <= 0) {
    echo json_encode(['erro' => 'Código do evento inválido']);
    exit;
}

if (
    empty($nomeEvento) || empty($localEvento) || empty($dataInicio) || empty($dataFim) ||
    empty($horarioInicio) || empty($horarioFim) || empty($publicoAlvo) ||
    empty($categoriaEvento) || empty($modalidadeEvento) || empty($certificadoEvento) || empty($descricaoEvento)
) {
    echo json_encode(['erro' => 'Todos os campos são obrigatórios']);
    exit;
}

// Verifica se o organizador tem permissão para editar este evento
$consultaVerificaPermissao = "SELECT COUNT(*) as total FROM organiza WHERE cod_evento = ? AND CPF = ?";
$declaracaoVerificaPermissao = mysqli_prepare($conexao, $consultaVerificaPermissao);
mysqli_stmt_bind_param($declaracaoVerificaPermissao, "is", $codigoEvento, $cpfOrganizadorLogado);
mysqli_stmt_execute($declaracaoVerificaPermissao);
$resultadoVerificacao = mysqli_stmt_get_result($declaracaoVerificaPermissao);
$linhaVerificacao = mysqli_fetch_assoc($resultadoVerificacao);
mysqli_stmt_close($declaracaoVerificaPermissao);

if ($linhaVerificacao['total'] == 0) {
    echo json_encode(['erro' => 'Você não tem permissão para editar este evento']);
    mysqli_close($conexao);
    exit;
}

// Combina data e hora para criar timestamps completos
$dataHoraInicio = $dataInicio . ' ' . $horarioInicio . ':00';
$dataHoraConclusao = $dataFim . ' ' . $horarioFim . ':00';

// Combina data e hora das inscrições (se fornecidas)
$inicioInscricao = null;
$fimInscricao = null;
if (!empty($dataInicioInscricao) && !empty($horarioInicioInscricao)) {
    $inicioInscricao = $dataInicioInscricao . ' ' . $horarioInicioInscricao . ':00';
}
if (!empty($dataFimInscricao) && !empty($horarioFimInscricao)) {
    $fimInscricao = $dataFimInscricao . ' ' . $horarioFimInscricao . ':00';
}

// Calcula duração do evento em horas
$objetoDataInicio = new DateTime($dataHoraInicio);
$objetoDataConclusao = new DateTime($dataHoraConclusao);
$intervaloTempo = $objetoDataInicio->diff($objetoDataConclusao);
$duracaoEmHoras = ($intervaloTempo->days * 24) + $intervaloTempo->h + ($intervaloTempo->i / 60);

// Converte certificado para formato booleano do banco
$certificadoBooleano = ($certificadoEvento === 'Sim' || $certificadoEvento == 1) ? 1 : 0;

// Processa upload de múltiplas novas imagens (se houver)
$novasImagens = [];
$caminhoNovaImagemPrincipal = null;
$deveAtualizarImagem = false;

if (isset($_FILES['imagens_evento']) && !empty($_FILES['imagens_evento']['name'][0])) {
    $totalImagens = count($_FILES['imagens_evento']['name']);
    $tamanhoMaximo = 10 * 1024 * 1024; // 10MB em bytes
    $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    for ($i = 0; $i < $totalImagens; $i++) {
        // Verifica se houve erro no upload
        if ($_FILES['imagens_evento']['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }
        
        $nomeArquivo = $_FILES['imagens_evento']['name'][$i];
        $tmpName = $_FILES['imagens_evento']['tmp_name'][$i];
        $tamanhoArquivo = $_FILES['imagens_evento']['size'][$i];
        $extensao = strtolower(pathinfo($nomeArquivo, PATHINFO_EXTENSION));
        
        // Valida tamanho do arquivo
        if ($tamanhoArquivo > $tamanhoMaximo) {
            $tamanhoMB = round($tamanhoArquivo / 1024 / 1024, 2);
            echo json_encode(['erro' => "A imagem '{$nomeArquivo}' excede o limite de 10MB. Tamanho: {$tamanhoMB}MB"]);
            mysqli_close($conexao);
            exit;
        }
        
        // Valida extensão
        if (!in_array($extensao, $extensoesPermitidas)) {
            echo json_encode(['erro' => "Formato de imagem não permitido em '{$nomeArquivo}'. Use: JPG, JPEG, PNG, GIF ou WEBP"]);
            mysqli_close($conexao);
            exit;
        }
        
        // Gera nome único
        $nomeUnico = uniqid() . '_' . time() . '_' . $i . '.' . $extensao;
        $destino = '../ImagensEventos/' . $nomeUnico;
        
        if (move_uploaded_file($tmpName, $destino)) {
            $caminhoCompleto = 'ImagensEventos/' . $nomeUnico;
            $novasImagens[] = [
                'caminho' => $caminhoCompleto,
                'ordem' => $i,
                'principal' => ($i === 0) ? 1 : 0
            ];
            
            // A primeira imagem é a principal
            if ($i === 0) {
                $caminhoNovaImagemPrincipal = $caminhoCompleto;
            }
            
            $deveAtualizarImagem = true;
        } else {
            echo json_encode(['erro' => "Erro ao fazer upload da imagem '{$nomeArquivo}'"]);
            mysqli_close($conexao);
            exit;
        }
    }
    
    // Se há novas imagens, remove as antigas
    if ($deveAtualizarImagem) {
        // Remove imagem antiga da tabela evento
        $consultaImagemAntiga = "SELECT imagem FROM evento WHERE cod_evento = ?";
        $declaracaoImagemAntiga = mysqli_prepare($conexao, $consultaImagemAntiga);
        mysqli_stmt_bind_param($declaracaoImagemAntiga, "i", $codigoEvento);
        mysqli_stmt_execute($declaracaoImagemAntiga);
        $resultadoImagemAntiga = mysqli_stmt_get_result($declaracaoImagemAntiga);
        $linhaImagemAntiga = mysqli_fetch_assoc($resultadoImagemAntiga);
        mysqli_stmt_close($declaracaoImagemAntiga);
        
        if ($linhaImagemAntiga && !empty($linhaImagemAntiga['imagem'])) {
            $caminhoImagemAntiga = '../' . $linhaImagemAntiga['imagem'];
            if (file_exists($caminhoImagemAntiga)) {
                @unlink($caminhoImagemAntiga);
            }
        }
        
        // Remove imagens antigas da tabela imagens_evento
        $consultaImagensAntigas = "SELECT caminho_imagem FROM imagens_evento WHERE cod_evento = ?";
        $declaracaoImagensAntigas = mysqli_prepare($conexao, $consultaImagensAntigas);
        mysqli_stmt_bind_param($declaracaoImagensAntigas, "i", $codigoEvento);
        mysqli_stmt_execute($declaracaoImagensAntigas);
        $resultadoImagensAntigas = mysqli_stmt_get_result($declaracaoImagensAntigas);
        
        while ($linhaImg = mysqli_fetch_assoc($resultadoImagensAntigas)) {
            $caminhoImg = '../' . $linhaImg['caminho_imagem'];
            if (file_exists($caminhoImg)) {
                @unlink($caminhoImg);
            }
        }
        mysqli_stmt_close($declaracaoImagensAntigas);
        
        // Deleta registros antigos da tabela imagens_evento
        $consultaDelete = "DELETE FROM imagens_evento WHERE cod_evento = ?";
        $declaracaoDelete = mysqli_prepare($conexao, $consultaDelete);
        mysqli_stmt_bind_param($declaracaoDelete, "i", $codigoEvento);
        mysqli_stmt_execute($declaracaoDelete);
        mysqli_stmt_close($declaracaoDelete);
    }
}

// Garante que as colunas de inscrição existem (compatível com MySQL < 8 que não suporta IF NOT EXISTS em ADD COLUMN)
function garantirColunaEvento(mysqli $cx, string $coluna, string $definicao) {
    $escCol = mysqli_real_escape_string($cx, $coluna);
    $res = mysqli_query($cx, "SHOW COLUMNS FROM evento LIKE '$escCol'");
    if ($res && mysqli_num_rows($res) === 0) {
        mysqli_query($cx, "ALTER TABLE evento ADD COLUMN `$coluna` $definicao");
    }
    if ($res) { mysqli_free_result($res); }
}
garantirColunaEvento($conexao, 'inicio_inscricao', 'DATETIME NULL');
garantirColunaEvento($conexao, 'fim_inscricao', 'DATETIME NULL');

// Atualiza evento no banco de dados
if ($deveAtualizarImagem) {
    // Query com atualização de imagem
    $consultaAtualizacao = "UPDATE evento SET 
                    categoria = ?, 
                    nome = ?, 
                    lugar = ?, 
                    descricao = ?, 
                    publico_alvo = ?, 
                    inicio = ?, 
                    conclusao = ?, 
                    duracao = ?, 
                    certificado = ?, 
                    modalidade = ?,
                    imagem = ?,
                    inicio_inscricao = ?,
                    fim_inscricao = ?
                  WHERE cod_evento = ?";

    $declaracaoAtualizacao = mysqli_prepare($conexao, $consultaAtualizacao);
    mysqli_stmt_bind_param(
        $declaracaoAtualizacao,
        "sssssssdissssi",
        $categoriaEvento,
        $nomeEvento,
        $localEvento,
        $descricaoEvento,
        $publicoAlvo,
        $dataHoraInicio,
        $dataHoraConclusao,
        $duracaoEmHoras,
        $certificadoBooleano,
        $modalidadeEvento,
        $caminhoNovaImagemPrincipal,
        $inicioInscricao,
        $fimInscricao,
        $codigoEvento
    );
    
    // Após executar o UPDATE, insere as novas imagens na tabela imagens_evento
    if (mysqli_stmt_execute($declaracaoAtualizacao)) {
        if (!empty($novasImagens)) {
            $sqlImagem = "INSERT INTO imagens_evento (cod_evento, caminho_imagem, ordem, principal) VALUES (?, ?, ?, ?)";
            $stmtImagem = mysqli_prepare($conexao, $sqlImagem);
            
            foreach ($novasImagens as $img) {
                mysqli_stmt_bind_param($stmtImagem, "isii", $codigoEvento, $img['caminho'], $img['ordem'], $img['principal']);
                mysqli_stmt_execute($stmtImagem);
            }
            mysqli_stmt_close($stmtImagem);
        }
    }
} else {
    // Query sem atualização de imagem
    $consultaAtualizacao = "UPDATE evento SET 
                    categoria = ?, 
                    nome = ?, 
                    lugar = ?, 
                    descricao = ?, 
                    publico_alvo = ?, 
                    inicio = ?, 
                    conclusao = ?, 
                    duracao = ?, 
                    certificado = ?, 
                    modalidade = ?,
                    inicio_inscricao = ?,
                    fim_inscricao = ?
                  WHERE cod_evento = ?";

    $declaracaoAtualizacao = mysqli_prepare($conexao, $consultaAtualizacao);
    // Tipos corretos: categoria(s), nome(s), lugar(s), descricao(s), publico_alvo(s), inicio(s), conclusao(s), duracao(d), certificado(i), modalidade(s), inicio_inscricao(s), fim_inscricao(s), cod_evento(i)
    mysqli_stmt_bind_param(
        $declaracaoAtualizacao,
        "sssssssdisssi",
        $categoriaEvento,
        $nomeEvento,
        $localEvento,
        $descricaoEvento,
        $publicoAlvo,
        $dataHoraInicio,
        $dataHoraConclusao,
        $duracaoEmHoras,
        $certificadoBooleano,
        $modalidadeEvento,
        $inicioInscricao,
        $fimInscricao,
        $codigoEvento
    );
}

if (mysqli_stmt_execute($declaracaoAtualizacao)) {
    mysqli_stmt_close($declaracaoAtualizacao);
    mysqli_close($conexao);

    echo json_encode(['sucesso' => true, 'mensagem' => 'Evento atualizado com sucesso!']);
} else {
    mysqli_stmt_close($declaracaoAtualizacao);
    mysqli_close($conexao);

    echo json_encode(['erro' => 'Erro ao atualizar evento: ' . mysqli_error($conexao)]);
}
