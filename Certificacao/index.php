<?php

/**
 * Verificador de Dependências - Sistema de Certificados CEU
 * Verifica se Composer e bibliotecas necessárias estão instaladas
 */

// Caminhos importantes agora dentro de Certificacao/bibliotecas
$vendorPath = __DIR__ . '/bibliotecas/vendor/autoload.php';
$composerJsonPath = __DIR__ . '/bibliotecas/composer.json';
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

  <title>Certificado - CEU</title>

  <?php if (!$dependenciasOk): ?>
    <script>
      window.addEventListener('DOMContentLoaded', function() {
        alert('⚠️ Configuração necessária!\n\nClique no botão "Instalar Automaticamente" na página para configurar o sistema.');
      });
    </script>
  <?php endif; ?>

  <style id="sections-styles">
    /* Estilos básicos e da UI */
    :root {
      --bg-color: #d1eaff;
      --card-bg-color: #4f6c8c;
      --text-color: #ffffff;
      --button-bg-color: #6598d2;
    }

    body {
      margin: 0;
      font-family: 'Inter', sans-serif;
      background-color: var(--bg-color);
      color: var(--text-color);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      box-sizing: border-box;
    }

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
      max-width: 760px;
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
      font-size: 1.05rem;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-top: 25px;
      display: inline-flex;
      align-items: center;
      gap: 12px;
      box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
      text-transform: uppercase;
      letter-spacing: .5px;
    }

    .btn-install:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 35px rgba(102, 126, 234, 0.7);
      background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    }

    .btn-install:disabled {
      opacity: .7;
      cursor: not-allowed;
      transform: none;
      box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }

    .install-status {
      margin-top: 20px;
      padding: 15px;
      border-radius: 8px;
      font-size: .95rem;
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
      border: 2px solid rgba(255, 255, 255, .3);
      border-radius: 50%;
      border-top-color: #fff;
      animation: spin .8s linear infinite;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }

    /* Painel de suporte/diagnóstico */
    .support-panel {
      margin-top: 24px;
      padding: 16px;
      border: 1px solid #444;
      border-radius: 10px;
      text-align: left;
    }

    .support-panel h3 {
      margin: 0 0 8px;
      font-size: 1.1rem;
    }

    .row {
      margin: 8px 0;
    }

    .row button {
      margin: 4px 6px 4px 0;
      padding: 8px 14px;
      font-size: 14px;
    }

    .row input[type="text"],
    .row input[type="number"] {
      padding: 6px;
      font-size: 14px;
    }

    .row label {
      margin-right: 8px;
    }

    pre#resultado {
      background: #111;
      color: #ddd;
      padding: 12px;
      border: 1px solid #333;
      max-height: 320px;
      overflow: auto;
    }

    /* CSS do certificado */
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
      letter-spacing: clamp(3px, .37vw, 7.06px);
      text-shadow: 0 4px 20px rgba(0, 0, 0, .6);
      margin: 0;
    }

    .certificate-body {
      font-size: clamp(.875rem, 1.67vw, 32px);
      font-weight: 500;
      line-height: 1.32;
      letter-spacing: -.64px;
      text-align: left;
      max-width: 1285px;
      margin: 0 0 clamp(40px, 6.25vw, 120px) 0;
    }

    .certificate-date {
      font-size: clamp(.875rem, 1.67vw, 32px);
      font-weight: 500;
      line-height: 1.32;
      letter-spacing: -.64px;
      margin: 0 0 clamp(12px, 1.25vw, 24px) 0;
    }

    .certificate-verification {
      font-size: clamp(.75rem, .83vw, 16px);
      font-weight: 500;
      line-height: 1.32;
      letter-spacing: -.32px;
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
      box-shadow: 0 4px 20px rgba(0, 0, 0, .6);
      border-radius: 6px;
      transition: transform .2s ease;
    }

    .button-wrapper:hover {
      transform: translateY(-2px);
    }

    .btn-back {
      display: inline-block;
      background-color: var(--button-bg-color);
      color: var(--text-color);
      text-decoration: none;
      font-size: clamp(.875rem, 1.25vw, 24px);
      font-weight: 500;
      padding: clamp(12px, .94vw, 18px) clamp(50px, 5.78vw, 111px);
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
      <button class="btn-install" id="btnInstall" onclick="instalarDependencias()"><span id="btnText"><span class="spinner" style="display:none"></span> ⚡ Instalar Automaticamente</span></button>
      <div class="install-status" id="installStatus"></div>
      <div class="support-panel">
        <h3>Ferramentas de Suporte</h3>
        <div class="row">
          <button onclick="acao('verificar_composer')">verificar_composer</button>
          <button onclick="acao('instalar_composer')">instalar_composer</button>
          <button onclick="acao('instalar_dependencias')">instalar_dependencias</button>
          <button onclick="acao('verificar_instalacao')">verificar_instalacao</button>
        </div>
        <div class="row">
          <button onclick="acao('status_git')">status_git</button>
          <label><input type="checkbox" id="removerArtefatos"> remover instalador.log</label>
          <button onclick="limparInstalador()">limpar_instalador</button>
        </div>
        <div class="row">
          <input type="text" id="msgLog" placeholder="Mensagem do log" style="width: 320px;">
          <button onclick="criarLog()">criar_log</button>
        </div>
        <div class="row">
          <label for="linhas">Linhas:</label>
          <input type="number" id="linhas" value="200" min="1" max="2000">
          <button onclick="lerLog()">ler_log</button>
          <button onclick="acao('limpar_log')">limpar_log</button>
          <button onclick="acao('auto_test')">auto_test</button>
        </div>
        <pre id="resultado"></pre>
      </div>
      <p style="margin-top: 18px; font-size: 0.85rem; opacity: 0.8;">
        O sistema instalará o Composer e as bibliotecas automaticamente.<br>
        Ou instale manualmente: <code>composer install</code> em <code>C:\xampp\htdocs\CEU\Certificacao\bibliotecas</code>
      </p>
    </div>

    <script>
      async function instalarDependencias() {
        const btn = document.getElementById('btnInstall');
        const btnText = document.getElementById('btnText');
        const status = document.getElementById('installStatus');
        btn.disabled = true;
        status.className = 'install-status loading';
        btnText.innerHTML = '<span class="spinner"></span> Verificando...';
        try {
          const verificarResp = await fetch('instalador.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'action=verificar_composer'
          });
          const verificarData = await verificarResp.json();
          if (!verificarData.composer_disponivel) {
            status.textContent = '📥 Composer não encontrado. Baixando Composer local (bibliotecas)...';
            btnText.innerHTML = '<span class="spinner"></span> Baixando Composer local...';
            const instalarComposerResp = await fetch('instalador.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
              },
              body: 'action=instalar_composer'
            });
            const instalarComposerData = await instalarComposerResp.json();
            if (!instalarComposerData.success) {
              status.className = 'install-status error';
              status.innerHTML = '❌ Erro ao instalar Composer: ' + instalarComposerData.message;
              btnText.textContent = '🔄 Tentar Novamente';
              btn.disabled = false;
              return;
            }
            status.textContent = '✅ Composer local instalado em bibliotecas! Continuando...';
          } else {
            if (verificarData.composer_global) {
              status.textContent = '✅ Composer global encontrado!';
            } else if (verificarData.composer_local) {
              status.textContent = '✅ Composer local encontrado em bibliotecas!';
            } else {
              status.textContent = '✅ Composer disponível!';
            }
          }
          await new Promise(r => setTimeout(r, 400));
          status.textContent = '📦 Instalando bibliotecas PHP em bibliotecas/vendor...';
          btnText.innerHTML = '<span class="spinner"></span> Instalando dependências...';
          const instalarResp = await fetch('instalador.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
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
          status.textContent = '✅ Verificando instalação...';
          const verificarInstResp = await fetch('instalador.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'action=verificar_instalacao'
          });
          const verificarInstData = await verificarInstResp.json();
          if (verificarInstData.success) {
            status.className = 'install-status success';
            status.innerHTML = '🎉 Tudo instalado com sucesso! Recarregando em 2s...';
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

      async function acao(acao, body = '') {
        const resultado = document.getElementById('resultado');
        if (resultado) {
          resultado.textContent = 'Ação: ' + acao + '...\n';
        }
        try {
          const params = 'action=' + encodeURIComponent(acao) + (body ? '&' + body : '');
          const resp = await fetch('instalador.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: params
          });
          const text = await resp.text();
          if (resultado) {
            resultado.textContent += 'HTTP ' + resp.status + '\n' + text + '\n';
            try {
              resultado.textContent += '\nJSON:\n' + JSON.stringify(JSON.parse(text), null, 2);
            } catch (e) {}
          }
        } catch (e) {
          if (resultado) resultado.textContent += 'Erro: ' + e.message;
        }
      }

      function limparInstalador() {
        const remover = document.getElementById('removerArtefatos').checked;
        acao('limpar_instalador', 'remover_artefatos=' + (remover ? '1' : '0'));
      }

      function criarLog() {
        const msg = document.getElementById('msgLog').value.trim();
        if (!msg) {
          alert('Informe uma mensagem');
          return;
        }
        acao('criar_log', 'mensagem=' + encodeURIComponent(msg));
      }

      function lerLog() {
        const linhas = parseInt(document.getElementById('linhas').value || '200', 10);
        acao('ler_log', 'linhas=' + encodeURIComponent(String(linhas)));
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