<?php
// API AJAX para verificar certificado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    $codigo = isset($_POST['codigo']) ? strtoupper(trim($_POST['codigo'])) : '';
    
    // Validação básica do código
    if (!$codigo || !preg_match('/^[A-Z0-9]{6,16}$/', $codigo)) {
        echo json_encode([
            'success' => false,
            'message' => 'Código de certificado inválido. Deve conter entre 6 e 16 caracteres alfanuméricos.'
        ]);
        exit;
    }
    
    // Inclui conexão com banco de dados
    require_once '../BancoDados/conexao.php';
    require_once __DIR__ . '/RepositorioCertificados.php';
    
    try {
        // Verifica se conexão existe
        if (!isset($conexao) || !($conexao instanceof mysqli)) {
            throw new Exception('Falha ao conectar com o banco de dados.');
        }
        
        // Busca o certificado no banco de dados
        $repo = new \CEU\Certificacao\RepositorioCertificados($conexao);
        $certificado = $repo->buscarPorCodigo($codigo);
        
        if ($certificado) {
            // Prepara os dados para retorno
            $dadosArray = $certificado['dados_array'] ?? [];
            
            echo json_encode([
                'success' => true,
                'certificado' => [
                    'codigo' => $certificado['cod_verificacao'] ?? '',
                    'participante' => $dadosArray['Participante'] ?? $dadosArray['nome_participante'] ?? 'N/A',
                    'evento' => $dadosArray['NomeEvento'] ?? $dadosArray['nome_evento'] ?? 'N/A',
                    'organizador' => $dadosArray['Organizador'] ?? $dadosArray['nome_organizador'] ?? 'N/A',
                    'data' => $dadosArray['Data'] ?? $dadosArray['data_emissao'] ?? 'N/A',
                    'carga_horaria' => $dadosArray['CargaHoraria'] ?? $dadosArray['carga_horaria'] ?? 'N/A',
                    'local' => $dadosArray['Local'] ?? $dadosArray['local_evento'] ?? 'N/A',
                    'arquivo' => $certificado['arquivo'] ?? ''
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Certificado não encontrado em nossa base de dados.'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao verificar certificado: ' . $e->getMessage()
        ]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Certificado - CEU</title>
    <link rel="icon" type="image/png" href="/CEU/Imagens/CEU-Logo-1x1.png" />
    <style>
        :root {
            --branco: #FFFFFF;
            --preto: #000000;
            --botao: #6598D2;
            --caixas: #4F6C8C;
            --fundo: #D1EAFF;
            --cinza-escuro: #333333;
            --azul-escuro: #0a1449;
            --azul-claro: #8ad7da;
            --verde: #2c9533;
            --vermelho: #ff0000;
            --sombra-padrao: rgba(0, 0, 0, 0.6);
            --sombra-forte: rgba(0, 0, 0, 0.8);
            --sombra-leve: rgba(0, 0, 0, 0.5);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--fundo);
            color: var(--cinza-escuro);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            overflow-x: hidden;
        }

        /* Padrão geométrico de fundo */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image:
                linear-gradient(30deg, rgba(101, 152, 210, 0.05) 12%, transparent 12.5%, transparent 87%, rgba(101, 152, 210, 0.05) 87.5%, rgba(101, 152, 210, 0.05)),
                linear-gradient(150deg, rgba(101, 152, 210, 0.05) 12%, transparent 12.5%, transparent 87%, rgba(101, 152, 210, 0.05) 87.5%, rgba(101, 152, 210, 0.05)),
                linear-gradient(30deg, rgba(101, 152, 210, 0.05) 12%, transparent 12.5%, transparent 87%, rgba(101, 152, 210, 0.05) 87.5%, rgba(101, 152, 210, 0.05)),
                linear-gradient(150deg, rgba(101, 152, 210, 0.05) 12%, transparent 12.5%, transparent 87%, rgba(101, 152, 210, 0.05) 87.5%, rgba(101, 152, 210, 0.05));
            background-size: 80px 140px;
            opacity: 0.5;
            z-index: 0;
        }

        .container {
            position: relative;
            z-index: 1;
            max-width: 600px;
            width: 100%;
            background: var(--branco);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            padding: 3rem;
            animation: surgirDeBaixo 0.6s ease-out;
        }

        @keyframes surgirDeBaixo {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-header img {
            width: 120px;
            height: auto;
            margin-bottom: 1rem;
        }

        .logo-header h1 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--azul-escuro);
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }

        .logo-header p {
            font-size: 1rem;
            color: var(--cinza-escuro);
            font-weight: 500;
            opacity: 0.8;
        }

        .verificacao-form {
            margin-top: 2.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--azul-escuro);
            margin-bottom: 0.5rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            padding: 1rem 1.2rem;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            transition: all 0.3s ease;
            background: var(--branco);
            color: var(--cinza-escuro);
            font-weight: 500;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: var(--botao);
            box-shadow: 0 0 0 4px rgba(101, 152, 210, 0.1);
        }

        .input-wrapper input::placeholder {
            color: #999;
        }

        .btn-verificar {
            width: 100%;
            padding: 1.1rem;
            font-size: 1.05rem;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            background: var(--botao);
            color: var(--branco);
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(101, 152, 210, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-verificar:hover {
            background: var(--azul-escuro);
            box-shadow: 0 6px 20px rgba(101, 152, 210, 0.4);
            transform: translateY(-2px);
        }

        .btn-verificar:active {
            transform: translateY(0);
        }

        .btn-verificar:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Resultado da verificação */
        .resultado-verificacao {
            margin-top: 2rem;
            padding: 1.5rem;
            border-radius: 12px;
            display: none;
            animation: surgirDeBaixo 0.4s ease-out;
        }

        .resultado-verificacao.sucesso {
            background: rgba(44, 149, 51, 0.1);
            border: 2px solid var(--verde);
            display: block;
        }

        .resultado-verificacao.erro {
            background: rgba(255, 0, 0, 0.1);
            border: 2px solid var(--vermelho);
            display: block;
        }

        .resultado-header {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 1rem;
        }

        .resultado-icone {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: bold;
        }

        .sucesso .resultado-icone {
            background: var(--verde);
            color: var(--branco);
        }

        .erro .resultado-icone {
            background: var(--vermelho);
            color: var(--branco);
        }

        .resultado-titulo {
            font-size: 1.2rem;
            font-weight: 700;
        }

        .sucesso .resultado-titulo {
            color: var(--verde);
        }

        .erro .resultado-titulo {
            color: var(--vermelho);
        }

        .resultado-conteudo {
            color: var(--cinza-escuro);
            line-height: 1.6;
        }

        .certificado-info {
            margin-top: 1rem;
            padding: 1rem;
            background: var(--branco);
            border-radius: 8px;
            border-left: 4px solid var(--verde);
        }

        .certificado-info p {
            margin: 0.5rem 0;
            font-size: 0.95rem;
        }

        .certificado-info strong {
            color: var(--azul-escuro);
            font-weight: 600;
        }

        .btn-voltar {
            display: inline-block;
            margin-top: 2rem;
            padding: 0.8rem 2rem;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--botao);
            text-decoration: none;
            border: 2px solid var(--botao);
            border-radius: 8px;
            transition: all 0.3s ease;
            text-align: center;
        }

        .btn-voltar:hover {
            background: var(--botao);
            color: var(--branco);
            transform: translateY(-2px);
        }

        .info-adicional {
            margin-top: 2rem;
            padding: 1.5rem;
            background: rgba(101, 152, 210, 0.08);
            border-radius: 12px;
            border-left: 4px solid var(--botao);
        }

        .info-adicional h3 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--azul-escuro);
            margin-bottom: 0.8rem;
        }

        .info-adicional p {
            font-size: 0.9rem;
            line-height: 1.6;
            color: var(--cinza-escuro);
            margin-bottom: 0.5rem;
        }

        .loader {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: var(--branco);
            animation: spin 0.8s linear infinite;
            margin-left: 0.5rem;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Responsividade */
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .container {
                padding: 2rem 1.5rem;
            }

            .logo-header h1 {
                font-size: 1.6rem;
            }

            .logo-header p {
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="logo-header">
            <img src="/CEU/Imagens/CEU-Logo-1x1.png" alt="CEU Logo">
            <h1>Verificar Certificado</h1>
            <p>Insira o código de autenticação para validar um certificado</p>
        </div>

        <form class="verificacao-form" id="formVerificar">
            <div class="form-group">
                <label for="codigoCertificado">Código de Autenticação</label>
                <div class="input-wrapper">
                    <input
                        type="text"
                        id="codigoCertificado"
                        name="codigo"
                        placeholder="Ex: ABC123DEF456"
                        required
                        maxlength="50"
                        autocomplete="off">
                </div>
            </div>

            <button type="submit" class="btn-verificar" id="btnVerificar">
                <span id="btnTexto">Verificar Certificado</span>
            </button>
        </form>

        <!-- Resultado da verificação (será preenchido dinamicamente) -->
        <div class="resultado-verificacao" id="resultado">
            <div class="resultado-header">
                <div class="resultado-icone" id="resultadoIcone">✓</div>
                <div class="resultado-titulo" id="resultadoTitulo">Certificado Válido</div>
            </div>
            <div class="resultado-conteudo" id="resultadoConteudo">
                <p>Este certificado foi encontrado em nossa base de dados e é válido.</p>
                <div class="certificado-info" id="certificadoInfo">
                    <!-- Informações do certificado serão inseridas aqui -->
                </div>
            </div>
        </div>

        <div class="info-adicional">
            <h3>Como funciona?</h3>
            <p>Cada certificado emitido pelo CEU possui um código único de autenticação.</p>
            <p>Para verificar a autenticidade de um certificado, digite o código que aparece no documento.</p>
            <p>Se o certificado for válido, você verá todas as informações do evento e participante.</p>
        </div>

        <a href="/CEU/index.php" class="btn-voltar">← Voltar para o início</a>
    </div>

    <script>
        const form = document.getElementById('formVerificar');
        const btnVerificar = document.getElementById('btnVerificar');
        const btnTexto = document.getElementById('btnTexto');
        const resultado = document.getElementById('resultado');
        const resultadoIcone = document.getElementById('resultadoIcone');
        const resultadoTitulo = document.getElementById('resultadoTitulo');
        const resultadoConteudo = document.getElementById('resultadoConteudo');
        const certificadoInfo = document.getElementById('certificadoInfo');
        const inputCodigo = document.getElementById('codigoCertificado');

        // Verifica se há código na URL ao carregar a página
        window.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            const codigoURL = params.get('codigo');
            
            if (codigoURL) {
                inputCodigo.value = codigoURL.toUpperCase();
                // Faz a verificação automaticamente
                setTimeout(() => {
                    form.dispatchEvent(new Event('submit'));
                }, 500);
            }
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const codigo = inputCodigo.value.trim();

            if (!codigo) {
                mostrarErro('Por favor, insira um código de certificado.');
                return;
            }

            // Desabilita o botão e mostra loader
            btnVerificar.disabled = true;
            btnTexto.innerHTML = 'Verificando... <span class="loader"></span>';
            resultado.style.display = 'none';

            try {
                // Faz a requisição para o backend - fazer fetch para a mesma página
                const formData = new FormData();
                formData.append('codigo', codigo);
                formData.append('ajax', '1');

                const response = await fetch(window.location.pathname, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    mostrarSucesso(data.certificado);
                } else {
                    mostrarErro(data.message || 'Certificado não encontrado em nossa base de dados.');
                }
            } catch (error) {
                console.error('Erro ao verificar certificado:', error);
                mostrarErro('Erro ao conectar com o servidor. Tente novamente.');
            } finally {
                // Reabilita o botão
                btnVerificar.disabled = false;
                btnTexto.textContent = 'Verificar Certificado';
            }
        });

        function mostrarSucesso(certificado) {
            resultado.className = 'resultado-verificacao sucesso';
            resultadoIcone.textContent = '✓';
            resultadoTitulo.textContent = 'Certificado Válido';

            certificadoInfo.innerHTML = `
                <p><strong>Participante:</strong> ${certificado.participante || 'N/A'}</p>
                <p><strong>Evento:</strong> ${certificado.evento || 'N/A'}</p>
                <p><strong>Organizador:</strong> ${certificado.organizador || 'N/A'}</p>
                <p><strong>Data:</strong> ${certificado.data || 'N/A'}</p>
                <p><strong>Carga Horária:</strong> ${certificado.carga_horaria || 'N/A'} horas</p>
                <p><strong>Local:</strong> ${certificado.local || 'N/A'}</p>
            `;

            resultadoConteudo.innerHTML = `
                <p>Este certificado foi encontrado em nossa base de dados e é válido.</p>
            `;
            resultadoConteudo.appendChild(certificadoInfo);

            resultado.style.display = 'block';
        }

        function mostrarErro(mensagem) {
            resultado.className = 'resultado-verificacao erro';
            resultadoIcone.textContent = '✕';
            resultadoTitulo.textContent = 'Certificado Não Encontrado';
            resultadoConteudo.innerHTML = `<p>${mensagem}</p>`;
            resultado.style.display = 'block';
        }

        // Permite apenas caracteres alfanuméricos no input
        inputCodigo.addEventListener('input', (e) => {
            e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });
    </script>
</body>

</html>