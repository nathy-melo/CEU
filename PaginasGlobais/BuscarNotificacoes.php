<?php
// Define header antes de qualquer saída
header('Content-Type: application/json; charset=utf-8');

// Inicia sessão e valida autenticação
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autenticado']);
    exit;
}

// Inclui conexão com o banco
require_once '../BancoDados/conexao.php';

if (!$conexao) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro de conexão com banco de dados']);
    exit;
}

$cpf = $_SESSION['cpf'];

// Verifica se é requisição do painel (quer todas) ou do dropdown (só não lidas)
$buscarTodas = isset($_GET['todas']) && $_GET['todas'] === 'true';

if ($buscarTodas) {
    // Busca todas as notificações (para o painel)
    $query = "SELECT id, tipo, mensagem, cod_evento, lida, data_criacao
              FROM notificacoes 
              WHERE CPF = ? 
              ORDER BY data_criacao DESC 
              LIMIT 100";
    
    $stmt = $conexao->prepare($query);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao preparar query: ' . $conexao->error]);
        exit;
    }
    $stmt->bind_param('s', $cpf);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $notificacoes = [];
    while ($row = $resultado->fetch_assoc()) {
        $notificacoes[] = $row;
    }
    $stmt->close();
} else {
    // Busca apenas não lidas (para o dropdown)
    $query = "SELECT id, tipo, mensagem, cod_evento, lida, data_criacao
              FROM notificacoes 
              WHERE CPF = ? AND lida = 0 
              ORDER BY data_criacao DESC 
              LIMIT 100";
    
    $stmt = $conexao->prepare($query);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao preparar query: ' . $conexao->error]);
        exit;
    }
    $stmt->bind_param('s', $cpf);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $todasNotificacoes = [];
    while ($row = $resultado->fetch_assoc()) {
        $todasNotificacoes[] = $row;
    }
    $stmt->close();
    
    // Agrupa mensagens do mesmo diálogo (mesmo evento e mesmos participantes)
    // Mantém apenas a mais recente de cada grupo
    $notificacoes = [];
    $gruposMensagens = []; // Chave: cod_evento|participante1|participante2 (ordenado)
    
    foreach ($todasNotificacoes as $notif) {
        if ($notif['tipo'] === 'mensagem_participante') {
            // Extrai CPF do remetente da mensagem
            // O destinatário é sempre o usuário atual ($cpf)
            $partes = explode('|||', $notif['mensagem']);
            $cpfRemetente = $partes[0] ?? '';
            $codEvento = $notif['cod_evento'] ?? 0;
            
            // Cria chave única para o grupo de diálogo (ordena CPFs para garantir mesma chave em ambos sentidos)
            // Isso agrupa mensagens entre os mesmos dois usuários no mesmo evento
            $participantes = [$cpfRemetente, $cpf];
            sort($participantes); // Ordena para garantir mesma chave independente da direção
            $chaveGrupo = $codEvento . '|' . $participantes[0] . '|' . $participantes[1];
            
            // Se já existe uma notificação deste grupo, mantém apenas a mais recente
            if (!isset($gruposMensagens[$chaveGrupo])) {
                $gruposMensagens[$chaveGrupo] = $notif;
            } else {
                // Compara datas e mantém a mais recente
                $dataExistente = strtotime($gruposMensagens[$chaveGrupo]['data_criacao']);
                $dataNova = strtotime($notif['data_criacao']);
                if ($dataNova > $dataExistente) {
                    $gruposMensagens[$chaveGrupo] = $notif;
                }
            }
        } else {
            // Notificações não-mensagem são adicionadas normalmente
            $notificacoes[] = $notif;
        }
    }
    
    // Adiciona as mensagens agrupadas (apenas a mais recente de cada grupo)
    foreach ($gruposMensagens as $notif) {
        $notificacoes[] = $notif;
    }
    
    // Ordena por data (mais recente primeiro) e limita a 50
    usort($notificacoes, function($a, $b) {
        return strtotime($b['data_criacao']) - strtotime($a['data_criacao']);
    });
    $notificacoes = array_slice($notificacoes, 0, 50);
}

$conexao->close();

// Garante que o JSON está em UTF-8
echo json_encode([
    'sucesso' => true,
    'total' => count($notificacoes),
    'notificacoes' => $notificacoes
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

