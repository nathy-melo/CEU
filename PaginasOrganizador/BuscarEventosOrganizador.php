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

require_once('../BancoDados/conexao.php');

$cpfOrganizadorLogado = $_SESSION['cpf'];

// Consulta SQL para buscar eventos do organizador com JOIN
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
            evento.imagem
        FROM evento
        INNER JOIN organiza ON evento.cod_evento = organiza.cod_evento
        WHERE organiza.CPF = ?
        ORDER BY evento.inicio DESC";

$declaracaoPreparada = mysqli_prepare($conexao, $consultaSQL);

if (!$declaracaoPreparada) {
    echo json_encode(['erro' => 'Erro ao preparar consulta: ' . mysqli_error($conexao)]);
    mysqli_close($conexao);
    exit;
}

mysqli_stmt_bind_param($declaracaoPreparada, "s", $cpfOrganizadorLogado);
mysqli_stmt_execute($declaracaoPreparada);
$resultadoConsulta = mysqli_stmt_get_result($declaracaoPreparada);

$listaEventos = [];

while ($dadosEvento = mysqli_fetch_assoc($resultadoConsulta)) {
    // Formatar datas para processamento
    $dataHoraInicio = new DateTime($dadosEvento['inicio']);
    $dataHoraConclusao = new DateTime($dadosEvento['conclusao']);
    $dataHoraAtual = new DateTime();

    // Determinar status do evento baseado nas datas
    $statusEvento = '';
    if ($dataHoraAtual < $dataHoraInicio) {
        $statusEvento = 'Previsto';
    } elseif ($dataHoraAtual >= $dataHoraInicio && $dataHoraAtual <= $dataHoraConclusao) {
        $statusEvento = 'Em andamento';
    } else {
        $statusEvento = 'Finalizado';
    }

    // Formatar certificado para exibição
    $textoTemCertificado = $dadosEvento['certificado'] == 1 ? 'Sim' : 'Não';

    $listaEventos[] = [
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
        'modalidade' => $dadosEvento['modalidade'],
        'imagem' => $dadosEvento['imagem'],
        'status' => $statusEvento,
        'data_formatada' => $dataHoraInicio->format('d/m/y'),
        'horario_inicio' => $dataHoraInicio->format('H:i'),
        'horario_fim' => $dataHoraConclusao->format('H:i')
    ];
}

mysqli_stmt_close($declaracaoPreparada);
mysqli_close($conexao);

echo json_encode(['sucesso' => true, 'eventos' => $listaEventos], JSON_UNESCAPED_UNICODE);
