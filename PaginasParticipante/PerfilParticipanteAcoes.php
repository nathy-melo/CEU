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

require_once '../BancoDados/conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['cpf']) || empty($_SESSION['cpf'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado']);
    exit;
}

$cpf_usuario = $_SESSION['cpf'];
$acao = $_POST['acao'] ?? '';

header('Content-Type: application/json');

switch ($acao) {
    case 'atualizar':
    case 'atualizar_perfil':
        $email = trim($_POST['email'] ?? '');
        $ra = trim($_POST['ra'] ?? '');
        $novoCaminhoFoto = null;
        $removeAnterior = false;
        $removerSolicitado = isset($_POST['remover_foto']) && $_POST['remover_foto'] === 'true';
        $fotoRemovida = false;

        // Validações básicas
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'E-mail válido é obrigatório']);
            break;
        }
        
        // Validar RA (se fornecido)
        if (!empty($ra) && (!is_numeric($ra) || strlen($ra) !== 7)) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'RA deve ter exatamente 7 dígitos']);
            break;
        }
        
        // Buscar dados atuais do usuário
        $sql_user = "SELECT Organizador, FotoPerfil FROM usuario WHERE CPF = ?";
        $stmt_user = mysqli_prepare($conexao, $sql_user);
        if (!$stmt_user) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro na preparação da consulta']);
            break;
        }
        
        mysqli_stmt_bind_param($stmt_user, "s", $cpf_usuario);
        mysqli_stmt_execute($stmt_user);
        $resultado_user = mysqli_stmt_get_result($stmt_user);
        $dadosUsuario = mysqli_fetch_assoc($resultado_user);
        mysqli_stmt_close($stmt_user);
        
        if (!$dadosUsuario) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não encontrado']);
            break;
        }
        
        // Guarda o caminho da foto antiga antes de qualquer atualização
        $fotoAntigaParaRemover = $dadosUsuario['FotoPerfil'] ?? null;
        
        // Verifica se o e-mail já está sendo usado por outro usuário
        $sql_check = "SELECT CPF FROM usuario WHERE Email = ? AND CPF != ?";
        $stmt_check = mysqli_prepare($conexao, $sql_check);
        if ($stmt_check) {
            mysqli_stmt_bind_param($stmt_check, "ss", $email, $cpf_usuario);
            mysqli_stmt_execute($stmt_check);
            $resultado_check = mysqli_stmt_get_result($stmt_check);
            
            if (mysqli_num_rows($resultado_check) > 0) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Este e-mail já está sendo usado por outro usuário']);
                mysqli_stmt_close($stmt_check);
                break;
            }
            mysqli_stmt_close($stmt_check);
        }

        // Upload da foto de perfil (opcional)
        if (isset($_FILES['foto_perfil']) && is_array($_FILES['foto_perfil']) && ($_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK)) {
            // Se usuário enviou nova foto, ignora solicitação de remoção
            $removerSolicitado = false;
            $arquivo = $_FILES['foto_perfil'];
            
            $tamanhoMax = 2 * 1024 * 1024; // 2MB
            if ($arquivo['size'] > $tamanhoMax) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'A imagem deve ter no máximo 2MB']);
                break;
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($arquivo['tmp_name']);
            
            $ext = null;
            $map = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif'
            ];
            if (!isset($map[$mime])) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Formato de imagem não suportado. Use JPG, PNG, WEBP ou GIF.']);
                break;
            }
            $ext = $map[$mime];

            // Diretório de destino
            $destDir = realpath(__DIR__ . '/../ImagensPerfis');
            
            if ($destDir === false) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Diretório de imagens não encontrado.']);
                break;
            }
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0777, true);
            }

            // Gera nome único com timestamp e microsegundos para evitar sobrescrever
            $nomeArquivo = $cpf_usuario . '_' . time() . '_' . uniqid() . '.' . $ext;
            $caminhoCompleto = $destDir . DIRECTORY_SEPARATOR . $nomeArquivo;
            
            if (!@move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Falha ao salvar a imagem. Verifique as permissões da pasta.']);
                break;
            }

            // Caminho relativo salvo no banco (para ser usado via web)
            $novoCaminhoFoto = 'ImagensPerfis/' . $nomeArquivo;
            $removeAnterior = true;
        } elseif (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] !== UPLOAD_ERR_NO_FILE && $_FILES['foto_perfil']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro no upload da imagem (código ' . (int)$_FILES['foto_perfil']['error'] . ').']);
            break;
        }
        
        // Preparar SQL baseado no tipo de usuário e remoção/upload
        $sucessoUpdate = false;
        if ($dadosUsuario['Organizador'] == 1) {
            if ($removerSolicitado && !$novoCaminhoFoto) {
                $sql = "UPDATE usuario SET Email = ?, FotoPerfil = NULL WHERE CPF = ?";
                $stmt = mysqli_prepare($conexao, $sql);
                if ($stmt) { mysqli_stmt_bind_param($stmt, "ss", $email, $cpf_usuario); }
            } elseif ($novoCaminhoFoto) {
                $sql = "UPDATE usuario SET Email = ?, FotoPerfil = ? WHERE CPF = ?";
                $stmt = mysqli_prepare($conexao, $sql);
                if ($stmt) { mysqli_stmt_bind_param($stmt, "sss", $email, $novoCaminhoFoto, $cpf_usuario); }
            } else {
                $sql = "UPDATE usuario SET Email = ? WHERE CPF = ?";
                $stmt = mysqli_prepare($conexao, $sql);
                if ($stmt) { mysqli_stmt_bind_param($stmt, "ss", $email, $cpf_usuario); }
            }
        } else {
            if ($removerSolicitado && !$novoCaminhoFoto) {
                $sql = "UPDATE usuario SET Email = ?, RA = ?, FotoPerfil = NULL WHERE CPF = ?";
                $stmt = mysqli_prepare($conexao, $sql);
                if ($stmt) { $raValue = empty($ra) ? null : $ra; mysqli_stmt_bind_param($stmt, "sss", $email, $raValue, $cpf_usuario); }
            } elseif ($novoCaminhoFoto) {
                $sql = "UPDATE usuario SET Email = ?, RA = ?, FotoPerfil = ? WHERE CPF = ?";
                $stmt = mysqli_prepare($conexao, $sql);
                if ($stmt) { $raValue = empty($ra) ? null : $ra; mysqli_stmt_bind_param($stmt, "ssss", $email, $raValue, $novoCaminhoFoto, $cpf_usuario); }
            } else {
                $sql = "UPDATE usuario SET Email = ?, RA = ? WHERE CPF = ?";
                $stmt = mysqli_prepare($conexao, $sql);
                if ($stmt) { $raValue = empty($ra) ? null : $ra; mysqli_stmt_bind_param($stmt, "sss", $email, $raValue, $cpf_usuario); }
            }
        }
        
        if ($stmt) {
            if (mysqli_stmt_execute($stmt)) {
                $sucessoUpdate = true;
            }
            mysqli_stmt_close($stmt);
        }

        if ($sucessoUpdate) {
            // Remove imagem anterior se necessário (upload) ou se foi solicitada remoção
            if ($removeAnterior && !empty($fotoAntigaParaRemover) && $fotoAntigaParaRemover !== $novoCaminhoFoto) {
                $antigo = realpath(__DIR__ . '/../' . $fotoAntigaParaRemover);
                if ($antigo && is_file($antigo)) { 
                    @unlink($antigo);
                }
            }
            if ($removerSolicitado && !empty($fotoAntigaParaRemover)) {
                $antigo = realpath(__DIR__ . '/../' . $fotoAntigaParaRemover);
                if ($antigo && is_file($antigo)) { 
                    @unlink($antigo);
                }
                unset($_SESSION['foto_perfil']);
                $fotoRemovida = true;
            }
            if ($novoCaminhoFoto) {
                $_SESSION['foto_perfil'] = $novoCaminhoFoto;
            }
            echo json_encode([
                'sucesso' => true, 
                'mensagem' => 'Perfil atualizado com sucesso',
                'dados' => [
                    'email' => $email,
                    'ra' => $ra,
                    'fotoPerfil' => $novoCaminhoFoto,
                    'fotoRemovida' => $fotoRemovida
                ]
            ]);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao atualizar perfil']);
        }
        break;
        
    case 'excluir_conta':
        // Obtém dados do POST
        $senha = isset($_POST['senha']) ? trim($_POST['senha']) : '';
        
        // Se for apenas para verificar se há solicitação pendente
        if (isset($_POST['verificar_pendente']) && $_POST['verificar_pendente'] === 'true') {
            $sql_check = "SELECT id, data_exclusao_programada FROM solicitacoes_exclusao_conta WHERE CPF = ? AND status = 'pendente'";
            $stmt_check = mysqli_prepare($conexao, $sql_check);
            mysqli_stmt_bind_param($stmt_check, "s", $cpf_usuario);
            mysqli_stmt_execute($stmt_check);
            $resultado_check = mysqli_stmt_get_result($stmt_check);
            
            if (mysqli_num_rows($resultado_check) > 0) {
                $solicitacao = mysqli_fetch_assoc($resultado_check);
                mysqli_stmt_close($stmt_check);
                echo json_encode([
                    'pendente' => true,
                    'data_exclusao' => $solicitacao['data_exclusao_programada']
                ]);
            } else {
                mysqli_stmt_close($stmt_check);
                echo json_encode([
                    'pendente' => false
                ]);
            }
            exit;
        }
        
        // Valida senha
        if (empty($senha)) {
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Senha é obrigatória para confirmar a exclusão.'
            ]);
            exit;
        }
        
        // Verifica se a senha está correta
        $sql_verifica = "SELECT Senha, Email, Nome FROM usuario WHERE CPF = ?";
        $stmt_verifica = mysqli_prepare($conexao, $sql_verifica);
        mysqli_stmt_bind_param($stmt_verifica, "s", $cpf_usuario);
        mysqli_stmt_execute($stmt_verifica);
        $resultado = mysqli_stmt_get_result($stmt_verifica);
        $usuario = mysqli_fetch_assoc($resultado);
        mysqli_stmt_close($stmt_verifica);
        
        if (!$usuario || !password_verify($senha, $usuario['Senha'])) {
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Senha incorreta.'
            ]);
            exit;
        }
        
        // Verifica se já existe uma solicitação pendente
        $sql_check = "SELECT id, data_exclusao_programada FROM solicitacoes_exclusao_conta WHERE CPF = ? AND status = 'pendente'";
        $stmt_check = mysqli_prepare($conexao, $sql_check);
        mysqli_stmt_bind_param($stmt_check, "s", $cpf_usuario);
        mysqli_stmt_execute($stmt_check);
        $resultado_check = mysqli_stmt_get_result($stmt_check);
        
        if (mysqli_num_rows($resultado_check) > 0) {
            $solicitacao = mysqli_fetch_assoc($resultado_check);
            mysqli_stmt_close($stmt_check);
            
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Já existe uma solicitação de exclusão pendente para esta conta.',
                'data_exclusao' => $solicitacao['data_exclusao_programada']
            ]);
            exit;
        }
        mysqli_stmt_close($stmt_check);
        
        // Cria a solicitação de exclusão (30 dias a partir de agora)
        $data_exclusao = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $sql_solicita = "INSERT INTO solicitacoes_exclusao_conta (CPF, data_exclusao_programada, status) VALUES (?, ?, 'pendente')";
        $stmt_solicita = mysqli_prepare($conexao, $sql_solicita);
        mysqli_stmt_bind_param($stmt_solicita, "ss", $cpf_usuario, $data_exclusao);
        
        if (mysqli_stmt_execute($stmt_solicita)) {
            mysqli_stmt_close($stmt_solicita);
            
            // TODO: Enviar email de confirmação para o usuário
            
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Solicitação de exclusão criada com sucesso. Sua conta será excluída em 30 dias.',
                'data_exclusao' => $data_exclusao,
                'email' => $usuario['Email']
            ]);
        } else {
            mysqli_stmt_close($stmt_solicita);
            
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Erro ao criar solicitação de exclusão: ' . mysqli_error($conexao)
            ]);
        }
        break;
        
    default:
        echo json_encode(['sucesso' => false, 'mensagem' => 'Ação não reconhecida']);
        break;
}

mysqli_close($conexao);
?>