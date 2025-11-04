<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$imgDefault = '../ImagensPerfis/FotodePerfil.webp';
$imgSrc = $imgDefault;
try {
    if (!empty($_SESSION['foto_perfil'])) {
        $stored = $_SESSION['foto_perfil'];
        if (strpos($stored, '/') === false) { $stored = 'ImagensPerfis/' . $stored; }
        $imgSrc = '../' . ltrim($stored, '/');
    } elseif (!empty($_SESSION['cpf'])) {
        require_once '../BancoDados/conexao.php';
        $sql = 'SELECT FotoPerfil FROM usuario WHERE CPF = ? LIMIT 1';
        if ($stmt = mysqli_prepare($conexao, $sql)) {
            mysqli_stmt_bind_param($stmt, 's', $_SESSION['cpf']);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($res)) {
                if (!empty($row['FotoPerfil'])) {
                    $stored = $row['FotoPerfil'];
                    if (strpos($stored, '/') === false) { $stored = 'ImagensPerfis/' . $stored; }
                    $imgSrc = '../' . ltrim($stored, '/');
                    $_SESSION['foto_perfil'] = $stored;
                }
            }
            mysqli_stmt_close($stmt);
        }
        // NÃO fechar $conexao aqui
        // mysqli_close($conexao);
    }
} catch (Throwable $e) { /* usa padrão */ }
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menu Lateral</title>
  <link rel="stylesheet" href="../styleGlobal.css" />
  <script src="MenuO.js"></script>
</head>

<body>

  <div class="Menu expanded">
    <!-- Botão de Notificações (Sino) - FIXADO NO TOPO, FORA DO FLUXO -->
    <button id="botao-notificacoes" class="botao-notificacoes">
      <img src="../Imagens/sino-notificacao.svg" alt="Notificações">
      <span id="notificacoes-badge" class="notificacoes-badge">0</span>
    </button>

    <div class="conteudo">
      <!-- Perfil -->
      <div class="header-menu">
        <div class="perfil">
          <button class="botao-perfil" onclick="carregarPagina('perfil')">
            <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="Foto de perfil">
            <span>Perfil</span>
          </button>
        </div>
      </div>

    <button class="botao-inicio" onclick="carregarPagina('inicio')">Início</button>
    <button class="botao-eventosInscritos" onclick="carregarPagina('eventosInscritos')">Eventos inscritos</button>
      <button class="botao-meusEventos" onclick="carregarPagina('meusEventos')">Meus eventos</button>
      <button class="botao-certificados" onclick="carregarPagina('certificados')">Certificados</button>
      <button class="botao-configuracoes" onclick="carregarPagina('configuracoes')">Configurações</button>
    </div>

    <button class="rodape botao-faleConosco" onclick="carregarPagina('faleconosco')">
      <img src="../Imagens/CEU-Logo.png" alt="Logo CEU">
      <div>Dúvidas ou Sugestões?</div>
      <p>Fale Conosco</p>
    </button>
  </div>

  <!-- Caixa de Notificações (dropdown - FORA do menu) -->
  <div id="notificacoes-caixa" class="notificacoes-dropdown">
    <div class="notificacoes-header">
      <div class="notificacoes-header-titulo">
        <h3>Notificações</h3>
        <a href="javascript:void(0)" class="notificacoes-ver-tudo" id="link-ver-tudo-notificacoes">Ver tudo</a>
      </div>
    </div>
    <div id="notificacoes-lista" class="notificacoes-lista">
      <div class="notificacoes-vazio">Carregando...</div>
    </div>
  </div>


</body>

</html>