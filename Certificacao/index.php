<?php

/**
 * Instalador do Sistema de Certificados - CEU
 * Verifica e instala automaticamente as dependências necessárias
 */

// Caminhos das bibliotecas Composer dentro de Certificacao/bibliotecas
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
    $mensagemErro = 'Bibliotecas PHP necessárias não encontradas.';
  }
} else {
  if ($composerExists) {
    $mensagemErro = 'Pasta vendor não encontrada.';
  } else {
    $mensagemErro = 'Composer não configurado.';
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $dependenciasOk ? 'Sistema de Certificados - CEU' : 'Instalador - Sistema de Certificados CEU'; ?></title>
  <link rel="stylesheet" href="../styleGlobal.css">
  <link rel="stylesheet" href="../styleGlobalMobile.css" media="(max-width: 767px)">

  <?php if (!$dependenciasOk): ?>
    <script>
      window.addEventListener('DOMContentLoaded', function() {
        alert('⚙️ Configuração Necessária\n\nO sistema de certificados precisa instalar algumas dependências. Clique no botão "Instalar Dependências" para continuar.');
      });
    </script>
  <?php endif; ?>

  <style>
    /* Estilos específicos do instalador */
    .secao-instalador {
      flex: 1 0 auto;
      display: flex;
      justify-content: center;
      align-items: center;
      width: 100%;
      padding: 1.75rem 0.9rem;
      min-height: 100vh;
    }

    .cartao-instalador {
      background-color: var(--caixas);
      color: var(--branco);
      border-radius: 0.9rem;
      box-shadow: 0 0.2rem 0.9rem 0 var(--sombra-padrao);
      padding: 2.5rem 2rem;
      max-width: 50rem;
      width: 100%;
      margin: 2rem auto;
    }

    .titulo-instalador {
      font-family: 'Inter', sans-serif;
      font-weight: 700;
      font-size: 2rem;
      line-height: 1.2;
      text-align: center;
      margin: 0 0 1.5rem 0;
      color: var(--branco);
    }

    .conteudo-instalador {
      font-family: 'Inter', sans-serif;
      font-weight: 500;
      font-size: 1.05rem;
      line-height: 1.6;
      text-align: center;
      margin-bottom: 2rem;
      color: var(--branco);
    }

    .status-instalacao {
      background-color: rgba(0, 0, 0, 0.2);
      padding: 1rem;
      border-radius: 0.5rem;
      margin: 1.5rem 0;
      font-size: 0.95rem;
      line-height: 1.5;
      display: none;
    }

    .status-instalacao.visivel {
      display: block;
    }

    .status-instalacao.sucesso {
      background-color: rgba(44, 149, 51, 0.2);
      border: 1px solid var(--verde);
    }

    .status-instalacao.erro {
      background-color: rgba(255, 0, 0, 0.2);
      border: 1px solid var(--vermelho);
    }

    .status-instalacao.carregando {
      background-color: rgba(101, 152, 210, 0.2);
      border: 1px solid var(--botao);
    }

    .btn-instalador {
      background-color: var(--botao);
      color: var(--branco);
      border: none;
      border-radius: 0.5rem;
      padding: 1rem 2.5rem;
      font-family: 'Inter', sans-serif;
      font-weight: 600;
      font-size: 1.05rem;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 0.2rem 0.5rem var(--sombra-padrao);
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      margin: 1rem auto;
    }

    .btn-instalador:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 0.4rem 0.8rem var(--sombra-padrao);
      background-color: #7aabdb;
    }

    .btn-instalador:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    .spinner {
      display: inline-block;
      width: 1rem;
      height: 1rem;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      border-top-color: var(--branco);
      animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }

    .painel-ferramentas {
      margin-top: 2rem;
      padding: 1.5rem;
      background-color: rgba(0, 0, 0, 0.2);
      border-radius: 0.5rem;
      text-align: left;
    }

    .painel-ferramentas h3 {
      margin: 0 0 1rem 0;
      font-size: 1.2rem;
      text-align: center;
    }

    .linha-ferramentas {
      margin: 0.8rem 0;
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      align-items: center;
    }

    .linha-ferramentas button {
      background-color: rgba(101, 152, 210, 0.3);
      color: var(--branco);
      border: 1px solid var(--botao);
      border-radius: 0.3rem;
      padding: 0.5rem 1rem;
      font-size: 0.85rem;
      cursor: pointer;
      transition: background-color 0.2s;
    }

    .linha-ferramentas button:hover {
      background-color: rgba(101, 152, 210, 0.5);
    }

    .linha-ferramentas input[type="text"],
    .linha-ferramentas input[type="number"] {
      padding: 0.5rem;
      border-radius: 0.3rem;
      border: 1px solid var(--botao);
      background-color: rgba(0, 0, 0, 0.2);
      color: var(--branco);
      font-size: 0.85rem;
    }

    .linha-ferramentas label {
      font-size: 0.85rem;
      display: flex;
      align-items: center;
      gap: 0.3rem;
    }

    #resultado {
      background-color: rgba(0, 0, 0, 0.4);
      color: #ddd;
      padding: 1rem;
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 0.3rem;
      max-height: 20rem;
      overflow: auto;
      font-family: 'Consolas', 'Courier New', monospace;
      font-size: 0.8rem;
      line-height: 1.4;
      margin-top: 1rem;
      white-space: pre-wrap;
      word-wrap: break-word;
    }

    .mensagem-sucesso {
      background-color: var(--caixas);
      color: var(--branco);
      border-radius: 0.9rem;
      box-shadow: 0 0.2rem 0.9rem 0 var(--sombra-padrao);
      padding: 2.5rem 2rem;
      max-width: 40rem;
      width: 100%;
      text-align: center;
      margin: 2rem auto;
    }

    .mensagem-sucesso h2 {
      font-size: 2.5rem;
      margin: 0 0 1rem 0;
    }

    .mensagem-sucesso p {
      font-size: 1.1rem;
      line-height: 1.6;
      margin: 0.5rem 0;
    }

    .icone-sucesso {
      font-size: 4rem;
      margin-bottom: 1rem;
    }

    @media (max-width: 767px) {
      .cartao-instalador {
        padding: 1.5rem 1rem;
      }

      .titulo-instalador {
        font-size: 1.5rem;
      }

      .conteudo-instalador {
        font-size: 0.95rem;
      }

      .btn-instalador {
        padding: 0.8rem 1.5rem;
        font-size: 0.95rem;
      }

      .linha-ferramentas {
        flex-direction: column;
        align-items: stretch;
      }

      .linha-ferramentas button,
      .linha-ferramentas input {
        width: 100%;
      }
    }
  </style>
</head>

<body>
  <?php if (!$dependenciasOk): ?>
    <!-- Instalador -->
    <section class="secao-instalador">
      <div class="cartao-instalador">
        <h1 class="titulo-instalador">⚙️ Instalador do Sistema de Certificados</h1>
        <p class="conteudo-instalador">
          Bem-vindo ao instalador automático! As bibliotecas PHP necessárias ainda não foram configuradas.
          Clique no botão abaixo para instalar automaticamente.
        </p>

        <div style="text-align: center;">
          <button class="btn-instalador" id="btnInstall" onclick="instalarDependencias()">
            <span id="btnText">🚀 Instalar Dependências</span>
          </button>
        </div>

        <div class="status-instalacao" id="installStatus"></div>

        <!-- Painel de Ferramentas de Diagnóstico -->
        <details class="painel-ferramentas">
          <summary style="cursor: pointer; font-weight: 600; margin-bottom: 1rem; text-align: center;">
            🔧 Ferramentas de Diagnóstico (Clique para expandir)
          </summary>

          <div class="linha-ferramentas">
            <button onclick="acao('verificar_composer')">Verificar Composer</button>
            <button onclick="acao('instalar_composer')">Instalar Composer</button>
            <button onclick="acao('instalar_dependencias')">Instalar Dependências</button>
            <button onclick="acao('verificar_instalacao')">Verificar Instalação</button>
          </div>

          <div class="linha-ferramentas">
            <button onclick="acao('verificar_fonte_inter')">Verificar Fonte Inter</button>
            <button onclick="acao('instalar_fonte_inter')">Instalar Fonte Inter</button>
            <button onclick="acao('status_git')">Status Git</button>
          </div>

          <div class="linha-ferramentas">
            <label>
              <input type="checkbox" id="removerArtefatos">
              Remover instalador.log
            </label>
            <button onclick="limparInstalador()">Limpar Instalador</button>
          </div>

          <div class="linha-ferramentas">
            <input type="text" id="msgLog" placeholder="Mensagem do log" style="flex: 1; min-width: 200px;">
            <button onclick="criarLog()">Criar Log</button>
          </div>

          <div class="linha-ferramentas">
            <label>
              Linhas:
              <input type="number" id="linhas" value="200" min="1" max="2000" style="width: 80px;">
            </label>
            <button onclick="lerLog()">Ler Log</button>
            <button onclick="acao('limpar_log')">Limpar Log</button>
            <button onclick="acao('auto_test')">Auto Test</button>
          </div>

          <pre id="resultado"></pre>
        </details>
      </div>
    </section>

    <script>
      async function instalarDependencias() {
        const btn = document.getElementById('btnInstall');
        const btnText = document.getElementById('btnText');
        const status = document.getElementById('installStatus');

        btn.disabled = true;
        status.className = 'status-instalacao visivel carregando';
        status.textContent = '🔍 Verificando ambiente...';
        btnText.innerHTML = '<span class="spinner"></span> Verificando...';

        try {
          // Verifica se o Composer está disponível
          const verificarResp = await fetch('instalador.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'action=verificar_composer'
          });
          const verificarData = await verificarResp.json();

          if (!verificarData.composer_disponivel) {
            status.textContent = '📥 Composer não encontrado. Instalando Composer local...';
            btnText.innerHTML = '<span class="spinner"></span> Instalando Composer...';

            const instalarComposerResp = await fetch('instalador.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
              },
              body: 'action=instalar_composer'
            });
            const instalarComposerData = await instalarComposerResp.json();

            if (!instalarComposerData.success) {
              status.className = 'status-instalacao visivel erro';
              status.innerHTML = '❌ Erro ao instalar Composer:<br>' + instalarComposerData.message;
              btnText.textContent = '🔄 Tentar Novamente';
              btn.disabled = false;
              return;
            }

            status.textContent = '✅ Composer instalado com sucesso!';
            await new Promise(r => setTimeout(r, 800));
          } else {
            status.textContent = '✅ Composer encontrado!';
            await new Promise(r => setTimeout(r, 400));
          }

          // Instala as dependências
          status.textContent = '📦 Instalando bibliotecas PHP (PhpPresentation, mPDF)...';
          btnText.innerHTML = '<span class="spinner"></span> Instalando bibliotecas...';

          const instalarResp = await fetch('instalador.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'action=instalar_dependencias'
          });
          const instalarData = await instalarResp.json();

          if (!instalarData.success) {
            status.className = 'status-instalacao visivel erro';
            status.innerHTML = '❌ Erro ao instalar dependências:<br>' + instalarData.message;
            btnText.textContent = '🔄 Tentar Novamente';
            btn.disabled = false;
            return;
          }

          status.textContent = '🔎 Verificando instalação...';
          await new Promise(r => setTimeout(r, 400));

          // Verifica se tudo foi instalado corretamente
          const verificarInstResp = await fetch('instalador.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'action=verificar_instalacao'
          });
          const verificarInstData = await verificarInstResp.json();

          if (verificarInstData.success) {
            status.className = 'status-instalacao visivel sucesso';
            status.innerHTML = '🎉 <strong>Instalação concluída com sucesso!</strong><br>Todas as dependências foram instaladas corretamente.<br>Recarregando a página...';
            btnText.textContent = '✅ Concluído!';
            setTimeout(() => window.location.reload(), 2000);
          } else {
            status.className = 'status-instalacao visivel erro';
            status.textContent = '⚠️ Instalação concluída, mas algumas verificações falharam. Tente recarregar a página.';
            btnText.textContent = '🔄 Recarregar Página';
            btn.disabled = false;
            btn.onclick = () => window.location.reload();
          }
        } catch (error) {
          status.className = 'status-instalacao visivel erro';
          status.textContent = '❌ Erro de comunicação: ' + error.message;
          btnText.textContent = '🔄 Tentar Novamente';
          btn.disabled = false;
        }
      }

      async function acao(acao, body = '') {
        const resultado = document.getElementById('resultado');
        if (resultado) {
          resultado.textContent = 'Executando: ' + acao + '...\n';
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
              const json = JSON.parse(text);
              resultado.textContent += '\nJSON formatado:\n' + JSON.stringify(json, null, 2);
            } catch (e) {
              // Não é JSON, ignora
            }
          }
        } catch (e) {
          if (resultado) {
            resultado.textContent += 'Erro: ' + e.message;
          }
        }
      }

      function limparInstalador() {
        const remover = document.getElementById('removerArtefatos').checked;
        acao('limpar_instalador', 'remover_artefatos=' + (remover ? '1' : '0'));
      }

      function criarLog() {
        const msg = document.getElementById('msgLog').value.trim();
        if (!msg) {
          alert('Informe uma mensagem para o log');
          return;
        }
        acao('criar_log', 'mensagem=' + encodeURIComponent(msg));
      }

      function lerLog() {
        const linhas = parseInt(document.getElementById('linhas').value || '200', 10);
        acao('ler_log', 'linhas=' + encodeURIComponent(String(linhas)));
      }
    </script>

  <?php else: ?>
    <!-- Sistema instalado com sucesso -->
    <section class="secao-instalador">
      <div class="mensagem-sucesso">
        <div class="icone-sucesso">✅</div>
        <h2>Sistema Pronto!</h2>
        <p>
          O sistema de certificados está instalado e configurado corretamente.
        </p>
        <p style="margin-top: 1.5rem; font-size: 0.95rem; opacity: 0.9;">
          Todas as dependências necessárias foram instaladas:<br>
          <strong>PhpPresentation</strong> e <strong>mPDF</strong>
        </p>
        <div style="margin-top: 2rem;">
          <a href="../index.php" class="btn-instalador" style="text-decoration: none;">
            🏠 Voltar para o Site
          </a>
        </div>
      </div>
    </section>
  <?php endif; ?>

</body>

</html>