<?php
/**
 * Verificador de Dependências - Sistema de Certificados CEU
 * Verifica se Composer e bibliotecas necessárias estão instaladas
 */

// Caminhos importantes
$vendorPath = __DIR__ . '/../vendor/autoload.php';
$composerJsonPath = __DIR__ . '/../composer.json';
$composerExists = file_exists($composerJsonPath);
$vendorExists = file_exists($vendorPath);

// Verifica se as bibliotecas necessárias estão instaladas
$dependenciasOk = false;
$mensagemErro = '';

if ($vendorExists) {
    require_once $vendorPath;
    
    // Verifica se as classes necessárias existem
    $classesNecessarias = [
        'PhpOffice\PhpPresentation\PhpPresentation',
        'Mpdf\Mpdf'
    ];
    
    $todasClassesExistem = true;
    foreach ($classesNecessarias as $classe) {
        if (!class_exists($classe)) {
            $todasClassesExistem = false;
            break;
        }
    }
    
    $dependenciasOk = $todasClassesExistem;
    
    if (!$dependenciasOk) {
        $mensagemErro = 'Bibliotecas PHP necessárias não encontradas. Execute: <code>composer install</code>';
    }
} else {
    if ($composerExists) {
        $mensagemErro = 'Pasta vendor não encontrada. Execute: <code>composer install</code>';
    } else {
        $mensagemErro = 'Composer não configurado. Siga as instruções abaixo para instalar.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;700&display=swap" rel="stylesheet">
  <title>Certificado - CEU</title>
  <link rel="stylesheet" href="global.css">
  
  <?php if (!$dependenciasOk): ?>
  <script>
    window.addEventListener('DOMContentLoaded', function() {
      alert('⚠️ Configuração necessária!\n\nClique no botão "Instalar Automaticamente" na página para configurar o sistema.');
    });
  </script>
  <?php endif; ?>
  
  <style id="sections-styles">
    /* Página simples - só fundo colorido quando faltam dependências */
    body.sem-dependencias {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      text-align: center;
    }

    body.sem-dependencias .certificate-container {
      display: none;
    }

    .mensagem-config {
      background-color: var(--card-bg-color);
      color: var(--text-color);
      padding: 40px;
      border-radius: 20px;
      max-width: 600px;
      margin: 20px;
    }

    .mensagem-config h2 {
      margin-top: 0;
      font-size: 2rem;
    }

    .mensagem-config p {
      line-height: 1.6;
      margin: 15px 0;
    }

    .mensagem-config code {
      background: rgba(0, 0, 0, 0.2);
      padding: 4px 8px;
      border-radius: 4px;
      font-family: 'Consolas', monospace;
      font-size: 0.9rem;
    }

    .btn-install {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      padding: 18px 45px;
      border-radius: 12px;
      font-weight: 700;
      font-size: 1.15rem;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-top: 25px;
      display: inline-flex;
      align-items: center;
      gap: 12px;
      box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .btn-install:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 35px rgba(102, 126, 234, 0.7);
      background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    }

    .btn-install:active {
      transform: translateY(-1px);
    }

    .btn-install:disabled {
      opacity: 0.7;
      cursor: not-allowed;
      transform: none;
      box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }

    .install-status {
      margin-top: 20px;
      padding: 15px;
      border-radius: 8px;
      font-size: 0.95rem;
      display: none;
    }

    .install-status.loading {
      background: rgba(255, 255, 255, 0.2);
      display: block;
    }

    .install-status.success {
      background: rgba(146, 254, 157, 0.3);
      display: block;
    }

    .install-status.error {
      background: rgba(255, 100, 100, 0.3);
      display: block;
    }

    .spinner {
      display: inline-block;
      width: 16px;
      height: 16px;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      border-top-color: #fff;
      animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* CSS for section section:certificate */
    .certificate-container {
      position: relative;
      width: 100%;
      max-width: 1920px;
      margin: 0 auto;
      padding: clamp(20px, 3vw, 60px) 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .decorative-bg {
      position: absolute;
      z-index: 0;
      pointer-events: none;
    }

    .decorative-bg img {
      display: block;
      width: 100%;
      height: auto;
    }

    .top-left {
      top: clamp(-30px, -2.6vw, -50px);
      left: clamp(-30px, -2.6vw, -50px);
      width: clamp(200px, 36vw, 694px);
    }

    .bottom-right {
      right: clamp(-30px, -2.6vw, -50px);
      bottom: clamp(-30px, -2.6vw, -50px);
      width: clamp(200px, 36vw, 694px);
      transform: rotate(180deg);
    }

    .certificate-card {
      position: relative;
      z-index: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      background-color: var(--card-bg-color);
      border-radius: clamp(25px, 2.6vw, 50px);
      width: clamp(85%, 90%, 1464px);
      max-width: 1464px;
      margin: 0 auto;
      padding: clamp(30px, 3.125vw, 60px) clamp(40px, 4.167vw, 80px);
      box-sizing: border-box;
      text-align: center;
    }

    .title-wrapper {
      margin-bottom: clamp(40px, 6.25vw, 120px);
    }

    .certificate-title {
      font-size: clamp(2rem, 3.67vw, 70.55px);
      font-weight: 700;
      line-height: 1.2;
      letter-spacing: clamp(3px, 0.37vw, 7.06px);
      text-shadow: 0px 4px 20px rgba(0, 0, 0, 0.6);
      margin: 0;
    }

    .certificate-body {
      font-size: clamp(0.875rem, 1.67vw, 32px);
      font-weight: 500;
      line-height: 1.32;
      letter-spacing: -0.64px;
      text-align: left;
      max-width: 1285px;
      margin: 0 0 clamp(40px, 6.25vw, 120px) 0;
    }

    .certificate-body strong {
      font-weight: 700;
    }

    .certificate-date {
      font-size: clamp(0.875rem, 1.67vw, 32px);
      font-weight: 500;
      line-height: 1.32;
      letter-spacing: -0.64px;
      margin: 0 0 clamp(12px, 1.25vw, 24px) 0;
    }

    .certificate-verification {
      font-size: clamp(0.75rem, 0.83vw, 16px);
      font-weight: 500;
      line-height: 1.32;
      letter-spacing: -0.32px;
      max-width: 930px;
      margin: 0 0 clamp(12px, 1.25vw, 24px) 0;
    }

    .logo {
      width: clamp(120px, 16.93vw, 325px);
      height: auto;
      object-fit: contain;
      margin-bottom: clamp(12px, 1.25vw, 24px);
      max-width: 80%;
    }

    .button-wrapper {
      box-shadow: 0px 4px 20px 0px rgba(0, 0, 0, 0.6);
      border-radius: 6px;
      transition: transform 0.2s ease;
    }

    .button-wrapper:hover {
      transform: translateY(-2px);
    }

    .btn-back {
      display: inline-block;
      background-color: var(--button-bg-color);
      color: var(--text-color);
      text-decoration: none;
      font-size: clamp(0.875rem, 1.25vw, 24px);
      font-weight: 500;
      padding: clamp(12px, 0.94vw, 18px) clamp(50px, 5.78vw, 111px);
      border-radius: 6px;
    }

    @media (max-width: 1200px) {
      .certificate-card {
        padding: 40px 50px;
      }

      .title-wrapper {
        margin-bottom: 80px;
      }

      .certificate-body {
        margin-bottom: 80px;
      }
    }

    @media (max-width: 768px) {
      .certificate-container {
        padding: 1rem;
      }

      .certificate-card {
        padding: 30px 20px;
        border-radius: 30px;
      }

      .title-wrapper {
        margin-bottom: 50px;
      }

      .certificate-title {
        letter-spacing: 4px;
      }

      .certificate-body {
        text-align: center;
        margin-bottom: 50px;
      }

      .top-left,
      .bottom-right {
        display: none;
      }

      .btn-back {
        padding: 15px 60px;
      }
    }
  </style>
  <script src="global.js" defer></script>
</head>

<body<?php echo !$dependenciasOk ? ' class="sem-dependencias"' : ''; ?>>
  
  <?php if (!$dependenciasOk): ?>
    <div class="mensagem-config">
      <h2>⚙️ Configuração Necessária</h2>
      <p>As bibliotecas PHP ainda não foram instaladas.</p>
      
      <button class="btn-install" id="btnInstall" onclick="instalarDependencias()">
        <span id="btnText">⚡ Instalar Automaticamente</span>
      </button>
      
      <div class="install-status" id="installStatus"></div>
      
      <p style="margin-top: 30px; font-size: 0.85rem; opacity: 0.8;">
        O sistema instalará o Composer e as bibliotecas automaticamente.<br>
        Ou instale manualmente: <code>composer install</code> em <code>C:\xampp\htdocs\CEU</code>
      </p>
    </div>

    <script>
      async function instalarDependencias() {
        const btn = document.getElementById('btnInstall');
        const btnText = document.getElementById('btnText');
        const status = document.getElementById('installStatus');
        
        btn.disabled = true;
        btnText.innerHTML = '<span class="spinner"></span> Verificando...';
        status.className = 'install-status loading';
        status.textContent = '🔍 Verificando se Composer está disponível...';
        
        try {
          // Passo 1: Verificar se Composer está instalado
          const verificarResp = await fetch('instalador.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=verificar_composer'
          });
          const verificarData = await verificarResp.json();
          
          // Se Composer não está disponível, instala localmente
          if (!verificarData.composer_disponivel) {
            status.textContent = '📥 Composer não encontrado. Instalando localmente...';
            btnText.innerHTML = '<span class="spinner"></span> Instalando Composer...';
            
            const instalarComposerResp = await fetch('instalador.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: 'action=instalar_composer'
            });
            const instalarComposerData = await instalarComposerResp.json();
            
            if (!instalarComposerData.success) {
              status.className = 'install-status error';
              status.innerHTML = '❌ Erro ao instalar Composer: ' + instalarComposerData.message + 
                '<br><br>Instale manualmente: <a href="https://getcomposer.org/download/" target="_blank" style="color: #fff; text-decoration: underline;">Download Composer</a>';
              btnText.textContent = '� Tentar Novamente';
              btn.disabled = false;
              return;
            }
            
            status.textContent = '✅ Composer instalado! Continuando...';
          } else {
            status.textContent = '✅ Composer encontrado!';
          }
          
          // Passo 2: Instalar dependências
          await new Promise(resolve => setTimeout(resolve, 500));
          status.textContent = '📦 Instalando bibliotecas PHP... Isso pode levar alguns minutos.';
          btnText.innerHTML = '<span class="spinner"></span> Instalando Bibliotecas...';
          
          const instalarResp = await fetch('instalador.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=instalar_dependencias'
          });
          const instalarData = await instalarResp.json();
          
          if (!instalarData.success) {
            status.className = 'install-status error';
            status.innerHTML = `❌ Erro ao instalar dependências:<br>${instalarData.message}`;
            btnText.textContent = '🔄 Tentar Novamente';
            btn.disabled = false;
            return;
          }
          
          // Passo 3: Verificar instalação
          status.textContent = '✅ Verificando instalação...';
          
          const verificarInstResp = await fetch('instalador.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=verificar_instalacao'
          });
          const verificarInstData = await verificarInstResp.json();
          
          if (verificarInstData.success) {
            status.className = 'install-status success';
            status.innerHTML = '🎉 Tudo instalado com sucesso!<br>Recarregando página em 2 segundos...';
            btnText.textContent = '✅ Concluído!';
            
            setTimeout(() => window.location.reload(), 2000);
          } else {
            status.className = 'install-status error';
            status.textContent = '⚠️ Instalação concluída, mas algumas classes não foram encontradas. Tente recarregar.';
            btnText.textContent = '🔄 Recarregar Página';
            btn.disabled = false;
            btn.onclick = () => window.location.reload();
          }
          
        } catch (error) {
          status.className = 'install-status error';
          status.textContent = '❌ Erro ao comunicar com o servidor: ' + error.message;
          btnText.textContent = '🔄 Tentar Novamente';
          btn.disabled = false;
        }
      }
    </script>
  <?php endif; ?>

  <section id="certificate">
    <main class="certificate-container">
      <div class="decorative-bg top-left">
        <img src="images/733_4080.svg" alt="decor-top-left">
      </div>
      <div class="decorative-bg bottom-right">
        <img src="images/733_4084.svg" alt="decor-bottom-right">
      </div>

      <div class="certificate-card">
        <div class="title-wrapper">
          <h1 class="certificate-title">Certificado</h1>
        </div>
        <p class="certificate-body">
          Certificamos que <strong>{Nome do Participante}</strong>, participou do(a) <strong>{Nome do Evento}</strong>,
          evento organizado por {Nome do Organizador}, realizado no {Local do Evento} no dia {Data}, com carga horária
          de {X horas}.
        </p>
        <p class="certificate-date">Sabará, {Data}.</p>
        <p class="certificate-verification">
          Este certificado é concedido como comprovação da participação no referido evento, tendo sido registrado na
          plataforma CEU. <br>Sua autenticidade pode ser verificada por meio do código {Código Autenticador} em [domínio
          do site].
        </p>
        <img class="logo" src="images/ac6b1d75b31a5bceb4921444f5111cc5bf3f3370.png" alt="CEU Logo">
        <div class="button-wrapper">
          <a href="#" class="btn-back">Voltar</a>
        </div>
      </div>
    </main>
  </section>
</body>

</html>
