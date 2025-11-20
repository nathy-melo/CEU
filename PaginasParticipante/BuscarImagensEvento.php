<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once('../BancoDados/conexao.php');

$codEvento = isset($_GET['cod_evento']) ? (int)$_GET['cod_evento'] : 0;

if ($codEvento <= 0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Código do evento inválido']);
    exit;
}

// Busca todas as imagens do evento ordenadas por ordem e principal
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

// Se não houver imagens na nova tabela, busca da tabela evento (fallback para compatibilidade)
if (empty($imagens)) {
    $sqlEvento = "SELECT imagem FROM evento WHERE cod_evento = ?";
    $stmtEvento = mysqli_prepare($conexao, $sqlEvento);
    mysqli_stmt_bind_param($stmtEvento, "i", $codEvento);
    mysqli_stmt_execute($stmtEvento);
    $resultadoEvento = mysqli_stmt_get_result($stmtEvento);
    $linhaEvento = mysqli_fetch_assoc($resultadoEvento);
    
    if ($linhaEvento && isset($linhaEvento['imagem']) && $linhaEvento['imagem'] !== '' && $linhaEvento['imagem'] !== null) {
        $imagens[] = [
            'id' => 0,
            'caminho' => $linhaEvento['imagem'],
            'ordem' => 0,
            'principal' => true
        ];
    } else {
        // Se não houver imagem, usa a logo padrão do CEU
        $imagens[] = [
            'id' => 0,
            'caminho' => 'ImagensEventos/CEU-ImagemEvento.png',
            'ordem' => 0,
            'principal' => true
        ];
    }
    
    mysqli_stmt_close($stmtEvento);
}

mysqli_close($conexao);

echo json_encode([
    'sucesso' => true,
    'imagens' => $imagens,
    'total' => count($imagens)
]);
?>
