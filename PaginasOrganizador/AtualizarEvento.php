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
$duracaoEvento = isset($_POST['duracao']) ? floatval($_POST['duracao']) : 0;
$imagensParaRemover = isset($_POST['imagens_remover']) ? json_decode($_POST['imagens_remover'], true) : [];

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

// Verifica se o usuário tem permissão para editar este evento (organizador ou colaborador)
// Primeiro verifica se é organizador na tabela organiza
$consultaVerificaPermissao = "SELECT COUNT(*) as total FROM organiza WHERE cod_evento = ? AND CPF = ?";
$declaracaoVerificaPermissao = mysqli_prepare($conexao, $consultaVerificaPermissao);
mysqli_stmt_bind_param($declaracaoVerificaPermissao, "is", $codigoEvento, $cpfOrganizadorLogado);
mysqli_stmt_execute($declaracaoVerificaPermissao);
$resultadoVerificacao = mysqli_stmt_get_result($declaracaoVerificaPermissao);
$linhaVerificacao = mysqli_fetch_assoc($resultadoVerificacao);
mysqli_stmt_close($declaracaoVerificaPermissao);

$ehOrganizador = ($linhaVerificacao['total'] > 0);

// Se não for organizador, verifica se é colaborador
if (!$ehOrganizador) {
    $consultaColaborador = "SELECT COUNT(*) as total FROM colaboradores_evento WHERE cod_evento = ? AND CPF = ?";
    $declaracaoColaborador = mysqli_prepare($conexao, $consultaColaborador);
    mysqli_stmt_bind_param($declaracaoColaborador, "is", $codigoEvento, $cpfOrganizadorLogado);
    mysqli_stmt_execute($declaracaoColaborador);
    $resultadoColaborador = mysqli_stmt_get_result($declaracaoColaborador);
    $linhaColaborador = mysqli_fetch_assoc($resultadoColaborador);
    mysqli_stmt_close($declaracaoColaborador);
    
    $ehColaborador = ($linhaColaborador['total'] > 0);
    
    if (!$ehColaborador) {
        echo json_encode(['erro' => 'Você não tem permissão para editar este evento']);
        mysqli_close($conexao);
        exit;
    }
}

// Combina data e hora para criar timestamps completos
$dataHoraInicio = $dataInicio . ' ' . $horarioInicio . ':00';
$dataHoraConclusao = $dataFim . ' ' . $horarioFim . ':00';

// Valida formato de data e hora
try {
    $objetoDataInicio = new DateTime($dataHoraInicio);
    $dataConclusaoObj = new DateTime($dataHoraConclusao);
} catch (Exception $e) {
    echo json_encode(['erro' => 'Data ou horário inválidos. Verifique os valores informados.']);
    mysqli_close($conexao);
    exit;
}

// Valida se a data de conclusão não está no passado
$dataHoraAtual = new DateTime();
if ($dataConclusaoObj < $dataHoraAtual) {
    echo json_encode(['erro' => 'Não é possível editar um evento que já foi finalizado']);
    mysqli_close($conexao);
    exit;
}

// Combina data e hora das inscrições (se fornecidas)
$inicioInscricao = null;
$fimInscricao = null;
if (!empty($dataInicioInscricao) && !empty($horarioInicioInscricao)) {
    $inicioInscricao = $dataInicioInscricao . ' ' . $horarioInicioInscricao . ':00';
    try {
        new DateTime($inicioInscricao);
    } catch (Exception $e) {
        echo json_encode(['erro' => 'Data ou horário de início das inscrições inválidos.']);
        mysqli_close($conexao);
        exit;
    }
}
if (!empty($dataFimInscricao) && !empty($horarioFimInscricao)) {
    $fimInscricao = $dataFimInscricao . ' ' . $horarioFimInscricao . ':00';
    try {
        new DateTime($fimInscricao);
    } catch (Exception $e) {
        echo json_encode(['erro' => 'Data ou horário de fim das inscrições inválidos.']);
        mysqli_close($conexao);
        exit;
    }
}

// Usa duração informada manualmente pelo usuário
$duracaoEmHoras = $duracaoEvento;

// Valida: se o evento é no mesmo dia, não pode ter mais de 16 horas
$intervaloTempo = $objetoDataInicio->diff($objetoDataConclusao);
if ($intervaloTempo->days === 0 && $duracaoEmHoras > 16) {
    echo json_encode(['erro' => 'Um evento de um único dia não pode ter mais de 16 horas de duração.']);
    mysqli_close($conexao);
    exit;
}

// Converte certificado para formato booleano do banco
$certificadoBooleano = ($certificadoEvento === 'Sim' || $certificadoEvento == 1) ? 1 : 0;

// Processa remoção de imagens (se houver)
if (!empty($imagensParaRemover) && is_array($imagensParaRemover)) {
    foreach ($imagensParaRemover as $caminhoImagem) {
        // Remove ../ do início se existir
        $caminhoLimpo = str_replace('../', '', $caminhoImagem);
        
        // Remove do banco de dados
        $sqlRemoveImg = "DELETE FROM imagens_evento WHERE cod_evento = ? AND caminho_imagem = ?";
        $stmtRemoveImg = mysqli_prepare($conexao, $sqlRemoveImg);
        mysqli_stmt_bind_param($stmtRemoveImg, "is", $codigoEvento, $caminhoLimpo);
        mysqli_stmt_execute($stmtRemoveImg);
        mysqli_stmt_close($stmtRemoveImg);
        
        // Remove arquivo físico
        $caminhoFisico = '../' . $caminhoLimpo;
        if (file_exists($caminhoFisico)) {
            @unlink($caminhoFisico);
        }
        
        // Se era a imagem principal da tabela evento, limpa
        $sqlCheckPrincipal = "SELECT imagem FROM evento WHERE cod_evento = ? AND imagem = ?";
        $stmtCheckPrincipal = mysqli_prepare($conexao, $sqlCheckPrincipal);
        mysqli_stmt_bind_param($stmtCheckPrincipal, "is", $codigoEvento, $caminhoLimpo);
        mysqli_stmt_execute($stmtCheckPrincipal);
        $resultCheckPrincipal = mysqli_stmt_get_result($stmtCheckPrincipal);
        if (mysqli_num_rows($resultCheckPrincipal) > 0) {
            // Busca outra imagem para ser a principal
            $sqlNovasPrincipais = "SELECT caminho_imagem FROM imagens_evento WHERE cod_evento = ? ORDER BY ordem ASC LIMIT 1";
            $stmtNovasPrincipais = mysqli_prepare($conexao, $sqlNovasPrincipais);
            mysqli_stmt_bind_param($stmtNovasPrincipais, "i", $codigoEvento);
            mysqli_stmt_execute($stmtNovasPrincipais);
            $resultNovasPrincipais = mysqli_stmt_get_result($stmtNovasPrincipais);
            $novaPrincipal = mysqli_fetch_assoc($resultNovasPrincipais);
            
            if ($novaPrincipal) {
                $sqlAtualizaPrincipal = "UPDATE evento SET imagem = ? WHERE cod_evento = ?";
                $stmtAtualizaPrincipal = mysqli_prepare($conexao, $sqlAtualizaPrincipal);
                mysqli_stmt_bind_param($stmtAtualizaPrincipal, "si", $novaPrincipal['caminho_imagem'], $codigoEvento);
                mysqli_stmt_execute($stmtAtualizaPrincipal);
                mysqli_stmt_close($stmtAtualizaPrincipal);
            } else {
                // Não há mais imagens, limpa a principal
                $sqlLimpaPrincipal = "UPDATE evento SET imagem = NULL WHERE cod_evento = ?";
                $stmtLimpaPrincipal = mysqli_prepare($conexao, $sqlLimpaPrincipal);
                mysqli_stmt_bind_param($stmtLimpaPrincipal, "i", $codigoEvento);
                mysqli_stmt_execute($stmtLimpaPrincipal);
                mysqli_stmt_close($stmtLimpaPrincipal);
            }
            mysqli_stmt_close($stmtNovasPrincipais);
        }
        mysqli_stmt_close($stmtCheckPrincipal);
    }
}

// Processa upload de múltiplas novas imagens (se houver)
$novasImagens = [];
$caminhoNovaImagemPrincipal = null;
$deveAtualizarImagem = false;

// Debug: Log de $_FILES
error_log("DEBUG AtualizarEvento.php - FILES recebidos: " . print_r($_FILES, true));

if (isset($_FILES['imagens_evento']) && !empty($_FILES['imagens_evento']['name'][0])) {
    $totalImagens = count($_FILES['imagens_evento']['name']);
    error_log("DEBUG - Total de imagens para upload: " . $totalImagens);
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
            error_log("DEBUG - Imagem salva com sucesso: " . $destino);
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
            error_log("DEBUG - ERRO ao mover arquivo: " . $tmpName . " para " . $destino);
            echo json_encode(['erro' => "Erro ao fazer upload da imagem '{$nomeArquivo}'"]);
            mysqli_close($conexao);
            exit;
        }
    }
    
    // Se há novas imagens, adiciona às existentes
    if ($deveAtualizarImagem) {
        // Busca a maior ordem atual para continuar a numeração
        $consultaMaiorOrdem = "SELECT COALESCE(MAX(ordem), -1) as max_ordem FROM imagens_evento WHERE cod_evento = ?";
        $stmtMaiorOrdem = mysqli_prepare($conexao, $consultaMaiorOrdem);
        mysqli_stmt_bind_param($stmtMaiorOrdem, "i", $codigoEvento);
        mysqli_stmt_execute($stmtMaiorOrdem);
        $resultMaiorOrdem = mysqli_stmt_get_result($stmtMaiorOrdem);
        $linhaMaiorOrdem = mysqli_fetch_assoc($resultMaiorOrdem);
        $ordemInicial = $linhaMaiorOrdem['max_ordem'] + 1;
        mysqli_stmt_close($stmtMaiorOrdem);
        
        // Atualiza a ordem das novas imagens
        foreach ($novasImagens as $idx => &$img) {
            $img['ordem'] = $ordemInicial + $idx;
            $img['principal'] = 0; // Nenhuma nova imagem será principal automaticamente
        }
        unset($img);
        
        // Se for a primeira imagem do evento, define como principal e atualiza a tabela evento
        $consultaContaImagens = "SELECT COUNT(*) as total FROM imagens_evento WHERE cod_evento = ?";
        $stmtContaImagens = mysqli_prepare($conexao, $consultaContaImagens);
        mysqli_stmt_bind_param($stmtContaImagens, "i", $codigoEvento);
        mysqli_stmt_execute($stmtContaImagens);
        $resultContaImagens = mysqli_stmt_get_result($stmtContaImagens);
        $linhaContaImagens = mysqli_fetch_assoc($resultContaImagens);
        mysqli_stmt_close($stmtContaImagens);
        
        if ($linhaContaImagens['total'] == 0 && !empty($novasImagens)) {
            // Primeira imagem do evento - marca como principal e atualiza tabela evento
            $novasImagens[0]['principal'] = 1;
            $caminhoNovaImagemPrincipal = $novasImagens[0]['caminho'];
        }
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
garantirColunaEvento($conexao, 'tipo_certificado', "VARCHAR(50) NULL DEFAULT 'Sem certificacao'");

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
                    fim_inscricao = ?,
                    tipo_certificado = ?
                  WHERE cod_evento = ?";

    $declaracaoAtualizacao = mysqli_prepare($conexao, $consultaAtualizacao);
    mysqli_stmt_bind_param(
        $declaracaoAtualizacao,
        "sssssssdissssis",
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
        $certificadoEvento,
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
                    fim_inscricao = ?,
                    tipo_certificado = ?
                  WHERE cod_evento = ?";

    $declaracaoAtualizacao = mysqli_prepare($conexao, $consultaAtualizacao);
    // Tipos corretos: categoria(s), nome(s), lugar(s), descricao(s), publico_alvo(s), inicio(s), conclusao(s), duracao(d), certificado(i), modalidade(s), inicio_inscricao(s), fim_inscricao(s), tipo_certificado(s), cod_evento(i)
    mysqli_stmt_bind_param(
        $declaracaoAtualizacao,
        "sssssssdissssis",
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
        $certificadoEvento,
        $codigoEvento
    );
}

// Executa a atualização do evento
if (mysqli_stmt_execute($declaracaoAtualizacao)) {
    mysqli_stmt_close($declaracaoAtualizacao);
    
    // Notifica participantes inscritos sobre a alteração do evento
    $sqlParticipantes = "SELECT CPF FROM inscreve WHERE cod_evento = ?";
    $stmtParticipantes = mysqli_prepare($conexao, $sqlParticipantes);
    mysqli_stmt_bind_param($stmtParticipantes, "i", $codigoEvento);
    mysqli_stmt_execute($stmtParticipantes);
    $resultParticipantes = mysqli_stmt_get_result($stmtParticipantes);
    
    // Cria notificações para cada participante inscrito
    if (mysqli_num_rows($resultParticipantes) > 0) {
        $mensagemNotificacao = "O evento '{$nomeEvento}' foi atualizado pelo organizador. Confira as alterações.";
        $sqlNotificacao = "INSERT INTO notificacao (CPF, mensagem, tipo, cod_evento, data_hora) VALUES (?, ?, 'evento_atualizado', ?, NOW())";
        $stmtNotificacao = mysqli_prepare($conexao, $sqlNotificacao);
        
        while ($participante = mysqli_fetch_assoc($resultParticipantes)) {
            mysqli_stmt_bind_param($stmtNotificacao, "ssi", $participante['CPF'], $mensagemNotificacao, $codigoEvento);
            mysqli_stmt_execute($stmtNotificacao);
        }
        mysqli_stmt_close($stmtNotificacao);
    }
    mysqli_stmt_close($stmtParticipantes);
    
    // Insere as novas imagens na tabela imagens_evento (sempre que houver novas imagens)
    if (!empty($novasImagens)) {
        error_log("DEBUG - Inserindo " . count($novasImagens) . " imagens no BD");
        $sqlImagem = "INSERT INTO imagens_evento (cod_evento, caminho_imagem, ordem, principal) VALUES (?, ?, ?, ?)";
        $stmtImagem = mysqli_prepare($conexao, $sqlImagem);
        
        foreach ($novasImagens as $img) {
            error_log("DEBUG - Inserindo imagem: " . $img['caminho'] . " ordem: " . $img['ordem']);
            mysqli_stmt_bind_param($stmtImagem, "isii", $codigoEvento, $img['caminho'], $img['ordem'], $img['principal']);
            $resultado = mysqli_stmt_execute($stmtImagem);
            if (!$resultado) {
                error_log("DEBUG - ERRO ao inserir imagem no BD: " . mysqli_error($conexao));
            }
        }
        mysqli_stmt_close($stmtImagem);
    } else {
        error_log("DEBUG - Nenhuma nova imagem para inserir no BD");
    }
    
    $resposta = ['sucesso' => true, 'mensagem' => 'Evento atualizado com sucesso!'];
    
    // Busca todas as imagens atualizadas do evento
    $sqlImagensAtualizadas = "SELECT caminho_imagem FROM imagens_evento WHERE cod_evento = ? ORDER BY principal DESC, ordem ASC";
    $stmtImagensAtualizadas = mysqli_prepare($conexao, $sqlImagensAtualizadas);
    mysqli_stmt_bind_param($stmtImagensAtualizadas, "i", $codigoEvento);
    mysqli_stmt_execute($stmtImagensAtualizadas);
    $resultImagensAtualizadas = mysqli_stmt_get_result($stmtImagensAtualizadas);
    
    $imagensAtualizadas = [];
    while ($imgAtual = mysqli_fetch_assoc($resultImagensAtualizadas)) {
        $imagensAtualizadas[] = '../' . $imgAtual['caminho_imagem'];
    }
    mysqli_stmt_close($stmtImagensAtualizadas);
    
    // Se não há imagens na tabela imagens_evento, tenta pegar da tabela evento
    if (empty($imagensAtualizadas)) {
        $sqlImagemEvento = "SELECT imagem FROM evento WHERE cod_evento = ?";
        $stmtImagemEvento = mysqli_prepare($conexao, $sqlImagemEvento);
        mysqli_stmt_bind_param($stmtImagemEvento, "i", $codigoEvento);
        mysqli_stmt_execute($stmtImagemEvento);
        $resultImagemEvento = mysqli_stmt_get_result($stmtImagemEvento);
        $linhaImagemEvento = mysqli_fetch_assoc($resultImagemEvento);
        mysqli_stmt_close($stmtImagemEvento);
        
        if ($linhaImagemEvento && !empty($linhaImagemEvento['imagem'])) {
            $imagensAtualizadas[] = '../' . $linhaImagemEvento['imagem'];
        }
    }
    
    if (!empty($imagensAtualizadas)) {
        $resposta['imagens'] = $imagensAtualizadas;
    } else {
        // Fallback para imagem padrão
        $resposta['imagens'] = ['../ImagensEventos/CEU-ImagemEvento.png'];
    }
    
    mysqli_close($conexao);
    echo json_encode($resposta);
} else {
    mysqli_stmt_close($declaracaoAtualizacao);
    mysqli_close($conexao);

    echo json_encode(['erro' => 'Erro ao atualizar evento: ' . mysqli_error($conexao)]);
}
