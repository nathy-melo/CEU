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

// Verifica se o código do evento foi fornecido
if (!isset($_GET['cod_evento']) || empty($_GET['cod_evento'])) {
    echo json_encode(['erro' => 'Código do evento não fornecido']);
    exit;
}

require_once('../BancoDados/conexao.php');

$cpfOrganizadorLogado = $_SESSION['cpf'];
$codigoEventoBuscado = intval($_GET['cod_evento']);

// Consulta SQL para buscar o evento e verificar se o organizador tem permissão
$consultaSQL = "SELECT 
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
            evento.imagem,
            usuario.Nome as nome_organizador
        FROM evento
        INNER JOIN organiza ON evento.cod_evento = organiza.cod_evento
        INNER JOIN usuario ON organiza.CPF = usuario.CPF
        WHERE evento.cod_evento = ? AND organiza.CPF = ?
        LIMIT 1";

$declaracaoPreparada = mysqli_prepare($conexao, $consultaSQL);

if (!$declaracaoPreparada) {
    echo json_encode(['erro' => 'Erro ao preparar consulta: ' . mysqli_error($conexao)]);
    mysqli_close($conexao);
    exit;
}

mysqli_stmt_bind_param($declaracaoPreparada, "is", $codigoEventoBuscado, $cpfOrganizadorLogado);
mysqli_stmt_execute($declaracaoPreparada);
$resultadoConsulta = mysqli_stmt_get_result($declaracaoPreparada);

if ($dadosEvento = mysqli_fetch_assoc($resultadoConsulta)) {
    // Formatar datas para exibição e processamento
    $dataHoraInicio = new DateTime($dadosEvento['inicio']);
    $dataHoraConclusao = new DateTime($dadosEvento['conclusao']);

    // Formatar certificado para exibição
    $textoTemCertificado = $dadosEvento['certificado'] == 1 ? 'Sim' : 'Não';

    // Determinar status do evento baseado nas datas
    $dataHoraAtual = new DateTime();
    $statusEvento = '';
    if ($dataHoraAtual < $dataHoraInicio) {
        $statusEvento = 'Previsto';
    } elseif ($dataHoraAtual >= $dataHoraInicio && $dataHoraAtual <= $dataHoraConclusao) {
        $statusEvento = 'Em andamento';
    } else {
        $statusEvento = 'Finalizado';
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
        'certificado' => $textoTemCertificado,
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
        'horario_fim' => $dataHoraConclusao->format('H:i')
    ];

    mysqli_stmt_close($declaracaoPreparada);
    mysqli_close($conexao);

    echo json_encode(['sucesso' => true, 'evento' => $eventoFormatado], JSON_UNESCAPED_UNICODE);
} else {
    mysqli_stmt_close($declaracaoPreparada);
    mysqli_close($conexao);
    echo json_encode(['erro' => 'Evento não encontrado ou você não tem permissão para visualizá-lo']);
}
