<?php
// Configuração do tempo de sessão para 6 minutos (5min de inatividade + 1min de extensão)
ini_set('session.gc_maxlifetime', 360);
session_set_cookie_params(360);

session_start();

// Verifica se a sessão expirou (permite 5 minutos de inatividade)
if (isset($_SESSION['ultima_atividade']) && (time() - $_SESSION['ultima_atividade'] > 300)) {
    session_unset();
    session_destroy();
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão expirada']);
    exit;
}

// Atualiza o timestamp da última atividade
$_SESSION['ultima_atividade'] = time();

// Define o cabeçalho para JSON
header('Content-Type: application/json');

// Verifica se o usuário está logado
if (!isset($_SESSION['cpf']) || !isset($_SESSION['organizador']) || $_SESSION['organizador'] != 1) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Usuário não autenticado ou não é organizador.'
    ]);
    exit;
}

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Método de requisição inválido.'
    ]);
    exit;
}

// Inclui o arquivo de conexão
require_once('../BancoDados/conexao.php');

// Obtém os dados do formulário
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$telefone = isset($_POST['telefone']) ? trim($_POST['telefone']) : null;
$ra = isset($_POST['ra']) ? trim($_POST['ra']) : null;
$novoCaminhoFoto = null;
$apagarAntiga = false;
$removerSolicitado = isset($_POST['remover_foto']) && $_POST['remover_foto'] === 'true';
$fotoRemovida = false;

// Valida os campos obrigatórios
if (empty($email)) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'E-mail é obrigatório.'
    ]);
    exit;
}

// Valida o formato do e-mail
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'E-mail inválido.'
    ]);
    exit;
}

// Valida o RA (se fornecido, deve ter no máximo 7 caracteres)
if (!empty($ra) && strlen($ra) > 7) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'RA deve ter no máximo 7 caracteres.'
    ]);
    exit;
}

$cpf = $_SESSION['cpf'];

// Verifica se o e-mail já está em uso por outro usuário
$sql_verifica = "SELECT CPF FROM usuario WHERE Email = ? AND CPF != ?";
$stmt_verifica = mysqli_prepare($conexao, $sql_verifica);
mysqli_stmt_bind_param($stmt_verifica, "ss", $email, $cpf);
mysqli_stmt_execute($stmt_verifica);
$resultado_verifica = mysqli_stmt_get_result($stmt_verifica);

if (mysqli_num_rows($resultado_verifica) > 0) {
    mysqli_stmt_close($stmt_verifica);
    mysqli_close($conexao);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Este e-mail já está sendo usado por outro usuário.'
    ]);
    exit;
}
mysqli_stmt_close($stmt_verifica);

// Busca foto atual ANTES de fazer qualquer upload
$fotoAtual = null;
if ($stmt_atual = mysqli_prepare($conexao, 'SELECT FotoPerfil FROM usuario WHERE CPF = ?')) {
    mysqli_stmt_bind_param($stmt_atual, 's', $cpf);
    mysqli_stmt_execute($stmt_atual);
    $res_atual = mysqli_stmt_get_result($stmt_atual);
    if ($row = mysqli_fetch_assoc($res_atual)) { 
        $fotoAtual = $row['FotoPerfil'] ?? null; 
    }
    mysqli_stmt_close($stmt_atual);
}

// Guarda o caminho da foto antiga antes de qualquer atualização
$fotoAntigaParaRemover = $fotoAtual;

// Upload opcional da foto de perfil
if (isset($_FILES['foto_perfil']) && is_array($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        // Se houve upload, ignora solicitação de remoção explícita
        $removerSolicitado = false;
        $arquivo = $_FILES['foto_perfil'];
        
        if ($arquivo['size'] > 2 * 1024 * 1024) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'A imagem deve ter no máximo 2MB']);
            mysqli_close($conexao);
            exit;
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($arquivo['tmp_name']);
        $map = [ 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif' ];
        if (!isset($map[$mime])) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Formato de imagem não suportado. Use JPG, PNG, WEBP ou GIF.']);
            mysqli_close($conexao);
            exit;
        }
        $ext = $map[$mime];
        $destDir = realpath(__DIR__ . '/../ImagensPerfis');
        
        if ($destDir === false) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Diretório de imagens não encontrado.']);
            mysqli_close($conexao);
            exit;
        }
        
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0777, true);
        }
        
        // Gera nome único com timestamp e microsegundos para evitar sobrescrever
        $nomeArquivo = $cpf . '_' . time() . '_' . uniqid() . '.' . $ext;
        $caminhoCompleto = $destDir . DIRECTORY_SEPARATOR . $nomeArquivo;
        
        if (!@move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Falha ao salvar a imagem.']);
            mysqli_close($conexao);
            exit;
        }
        
        $novoCaminhoFoto = 'ImagensPerfis/' . $nomeArquivo;
        $apagarAntiga = !empty($fotoAtual);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro no upload da imagem (código ' . (int)$_FILES['foto_perfil']['error'] . ').']);
        mysqli_close($conexao);
        exit;
    }
}

// Atualiza os dados do usuário
if ($removerSolicitado && !$novoCaminhoFoto) {
    $sql_atualiza = "UPDATE usuario SET Email = ?, RA = ?, FotoPerfil = NULL WHERE CPF = ?";
    $stmt_atualiza = mysqli_prepare($conexao, $sql_atualiza);
    $ra_value = empty($ra) ? null : $ra;
    mysqli_stmt_bind_param($stmt_atualiza, "sss", $email, $ra_value, $cpf);
} elseif ($novoCaminhoFoto) {
    $sql_atualiza = "UPDATE usuario SET Email = ?, RA = ?, FotoPerfil = ? WHERE CPF = ?";
    $stmt_atualiza = mysqli_prepare($conexao, $sql_atualiza);
    $ra_value = empty($ra) ? null : $ra;
    mysqli_stmt_bind_param($stmt_atualiza, "ssss", $email, $ra_value, $novoCaminhoFoto, $cpf);
} else {
    $sql_atualiza = "UPDATE usuario SET Email = ?, RA = ? WHERE CPF = ?";
    $stmt_atualiza = mysqli_prepare($conexao, $sql_atualiza);
    $ra_value = empty($ra) ? null : $ra;
    mysqli_stmt_bind_param($stmt_atualiza, "sss", $email, $ra_value, $cpf);
}

if (mysqli_stmt_execute($stmt_atualiza)) {
    // Atualiza os dados na sessão (mantém o nome como estava)
    $_SESSION['email'] = $email;
    if ($novoCaminhoFoto) {
        $_SESSION['foto_perfil'] = $novoCaminhoFoto;
        // Remove antiga se existir e for diferente
        if ($apagarAntiga && $fotoAtual !== $novoCaminhoFoto) {
            $antigo = realpath(__DIR__ . '/../' . $fotoAtual);
            if ($antigo && is_file($antigo)) {
                @unlink($antigo);
            }
        }
    }
    if ($removerSolicitado && !empty($fotoAtual)) {
        // Apaga antiga e limpa sessão
        $antigo = realpath(__DIR__ . '/../' . $fotoAtual);
        if ($antigo && is_file($antigo)) { @unlink($antigo); }
        unset($_SESSION['foto_perfil']);
        $fotoRemovida = true;
    }
    mysqli_stmt_close($stmt_atualiza);
    mysqli_close($conexao);
    
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Perfil atualizado com sucesso.',
        'dados' => [ 'fotoPerfil' => $novoCaminhoFoto, 'fotoRemovida' => $fotoRemovida ]
    ]);
} else {
    $erro = mysqli_error($conexao);
    mysqli_stmt_close($stmt_atualiza);
    mysqli_close($conexao);
    
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro ao atualizar o perfil: ' . $erro
    ]);
}
?>
