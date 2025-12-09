<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="theme-color" content="#6598D2" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="mobile-web-app-capable" content="yes" />
    <link rel="manifest" href="/CEU/manifest.json" />
    <link rel="icon" type="image/png" href="/CEU/Imagens/CEU-Logo-1x1.png" />
    <link rel="apple-touch-icon" href="/CEU/Imagens/CEU-Logo-1x1.png" />
    <title>CEU - Controle de Eventos Unificado</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <script src="/CEU/pwa-config.js" defer></script>
    <script>
        // Verificação silenciosa do banco de dados ao carregar a página
        document.addEventListener('DOMContentLoaded', () => {
            fetch('./BancoDados/VerificarBancoDados.php?verificar=1')
                .then(response => response.text())
                .then(text => {
                    // Remove qualquer HTML/texto antes do JSON e faz parse tolerante
                    let jsonText = (text || '').trim();
                    let jsonStart = jsonText.indexOf('{');
                    if (jsonStart > 0) jsonText = jsonText.substring(jsonStart);
                    try {
                        return JSON.parse(jsonText);
                    } catch (e) {
                        console.warn('Resposta não-JSON (verificar):', text);
                        return {
                            erro: true,
                            mensagem: 'Resposta não-JSON do servidor',
                            raw: (text || '').slice(0, 500)
                        };
                    }
                })
                .then(data => {
                    console.log('Verificação do BD:', data);

                    // Se retornou erro de conexão
                    if (data.erro) {
                        alert('❌ ERRO DE CONEXÃO\n\n' + data.mensagem + '\n\nPor favor:\n1. Abra o XAMPP Control Panel\n2. Inicie o MySQL\n3. Recarregue esta página');
                        return;
                    }

                    if (!data.bancoExiste) {
                        // Banco não existe
                        if (confirm('⚠️ BANCO DE DADOS NÃO ENCONTRADO!\n\nO banco de dados CEU_bd não existe.\n\nDeseja criar e importar o banco de dados agora?\n(Isso executará o arquivo BancodeDadosCEU.sql)')) {
                            atualizarBanco();
                        } else {
                            alert('❌ Não é possível continuar sem o banco de dados.');
                        }
                    } else if (!data.atualizado) {
                        // Banco existe mas está desatualizado
                        let mensagem = '⚠️ BANCO DE DADOS DESATUALIZADO!\n\n';
                        mensagem += 'Diferenças encontradas:\n';
                        data.diferencas.forEach(dif => {
                            mensagem += '• ' + dif + '\n';
                        });
                        mensagem += '\nDeseja atualizar o banco de dados agora?';

                        if (confirm(mensagem)) {
                            atualizarBanco();
                        } else {
                            console.log('Usuário optou por não atualizar o banco.');
                        }
                    } else {
                        // Tudo OK - não faz nada, página continua normalmente
                        console.log('✅ Banco de dados está atualizado!');

                        // Verifica se é a primeira vez que o usuário acessa o site
                        verificarPrimeiroAcesso();
                    }
                })
                .catch(error => {
                    console.error('Erro ao verificar BD:', error);
                    alert('❌ ERRO AO VERIFICAR BANCO DE DADOS\n\nNão foi possível conectar ao servidor.\n\nVerifique se:\n• XAMPP está rodando\n• MySQL está iniciado\n• Servidor Apache está rodando\n\nDetalhes: ' + error.message);
                });

            // Verifica se é a primeira vez que o usuário acessa o site
            function verificarPrimeiroAcesso() {
                const primeiroAcesso = localStorage.getItem('ceu_primeiro_acesso');

                if (!primeiroAcesso) {
                    // É a primeira vez, mostra aviso
                    setTimeout(() => {
                        mostrarAvisoPrimeiroAcesso();
                    }, 1000); // Delay de 1 segundo para não conflitar com outros avisos
                }
            }

            function mostrarAvisoPrimeiroAcesso() {
                const overlay = document.createElement('div');
                overlay.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.7);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10000;
                    animation: fadeIn 0.3s ease;
                `;

                const modal = document.createElement('div');
                modal.style.cssText = `
                    background: white;
                    padding: 30px;
                    border-radius: 12px;
                    max-width: 600px;
                    width: 90%;
                    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
                    animation: slideIn 0.3s ease;
                `;

                modal.innerHTML = `
                    <h2 style="color: #6598D2; margin-bottom: 20px; font-size: 24px; text-align: center;">
                        👋 Bem-vindo ao CEU!
                    </h2>
                    <div style="margin-bottom: 20px; line-height: 1.6; color: #333;">
                        <p style="margin-bottom: 15px;">
                            Parece que esta é a <strong>primeira vez</strong> que você acessa o sistema.
                        </p>
                        <p style="margin-bottom: 15px;">
                            Para garantir uma instalação correta e entender todas as funcionalidades, 
                            recomendamos fortemente a leitura da documentação:
                        </p>
                        <ul style="margin: 15px 0 15px 20px; color: #555;">
                            <li style="margin-bottom: 8px;">
                                <strong>README.md</strong> - Visão geral do projeto
                            </li>
                            <li style="margin-bottom: 8px;">
                                <strong>Tutorial_Instalacao.md</strong> - Guia passo a passo completo
                        </ul>
                        <p style="margin-top: 15px; padding: 12px; background: #e3f2fd; border-left: 4px solid #6598D2; border-radius: 4px;">
                            💡 <strong>Dica:</strong> Você encontra esses arquivos na pasta raiz do projeto!
                        </p>
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: center; margin-top: 25px;">
                        <button id="btnEntendi" style="
                            background: #6598D2;
                            color: white;
                            border: none;
                            padding: 12px 30px;
                            border-radius: 6px;
                            cursor: pointer;
                            font-size: 16px;
                            font-weight: 600;
                            transition: background 0.3s ease;
                        ">
                            Entendi!
                        </button>
                        <button id="btnNaoMostrar" style="
                            background: #888;
                            color: white;
                            border: none;
                            padding: 12px 30px;
                            border-radius: 6px;
                            cursor: pointer;
                            font-size: 16px;
                            transition: background 0.3s ease;
                        ">
                            Não mostrar novamente
                        </button>
                    </div>
                `;

                const style = document.createElement('style');
                style.textContent = `
                    @keyframes fadeIn {
                        from { opacity: 0; }
                        to { opacity: 1; }
                    }
                    @keyframes slideIn {
                        from { transform: translateY(-50px); opacity: 0; }
                        to { transform: translateY(0); opacity: 1; }
                    }
                    #btnEntendi:hover {
                        background: #4F6C8C !important;
                    }
                    #btnNaoMostrar:hover {
                        background: #666 !important;
                    }
                `;

                document.head.appendChild(style);
                overlay.appendChild(modal);
                document.body.appendChild(overlay);

                // Eventos dos botões
                document.getElementById('btnEntendi').addEventListener('click', () => {
                    overlay.style.animation = 'fadeOut 0.3s ease';
                    setTimeout(() => overlay.remove(), 300);
                });

                document.getElementById('btnNaoMostrar').addEventListener('click', () => {
                    localStorage.setItem('ceu_primeiro_acesso', 'false');
                    overlay.style.animation = 'fadeOut 0.3s ease';
                    setTimeout(() => overlay.remove(), 300);
                });

                // Fechar ao clicar fora
                overlay.addEventListener('click', (e) => {
                    if (e.target === overlay) {
                        overlay.style.animation = 'fadeOut 0.3s ease';
                        setTimeout(() => overlay.remove(), 300);
                    }
                });

                // Adicionar animação de fadeOut
                const fadeOutStyle = document.createElement('style');
                fadeOutStyle.textContent = `
                    @keyframes fadeOut {
                        from { opacity: 1; }
                        to { opacity: 0; }
                    }
                `;
                document.head.appendChild(fadeOutStyle);
            }


            function atualizarBanco() {
                console.log('Atualizando banco de dados...');

                fetch('./BancoDados/VerificarBancoDados.php?atualizar=1')
                    .then(response => response.text())
                    .then(text => {
                        // Remove qualquer HTML/texto antes do JSON e faz parse tolerante
                        let jsonText = (text || '').trim();
                        let jsonStart = jsonText.indexOf('{');
                        if (jsonStart > 0) jsonText = jsonText.substring(jsonStart);
                        try {
                            return JSON.parse(jsonText);
                        } catch (e) {
                            console.warn('Resposta não-JSON (atualizar):', text);
                            return {
                                sucesso: false,
                                erro: 'Resposta não-JSON do servidor',
                                raw: (text || '').slice(0, 500)
                            };
                        }
                    })
                    .then(data => {
                        console.log('Resultado da atualização:', data);

                        // Após atualizar, verifica novamente se ficou tudo OK
                        fetch('./BancoDados/VerificarBancoDados.php?verificar=1')
                            .then(response => response.text())
                            .then(text => {
                                let jsonText = (text || '').trim();
                                let jsonStart = jsonText.indexOf('{');
                                if (jsonStart > 0) jsonText = jsonText.substring(jsonStart);
                                try {
                                    return JSON.parse(jsonText);
                                } catch (e) {
                                    console.warn('Resposta não-JSON (pós verificação):', text);
                                    return {
                                        atualizado: false,
                                        diferencas: ['Resposta inválida do servidor após atualização']
                                    };
                                }
                            })
                            .then(verificacao => {
                                console.log('Verificação pós-atualização:', verificacao);

                                if (verificacao.atualizado) {
                                    // Sucesso!
                                    alert('✅ Banco de dados atualizado com sucesso!');
                                } else {
                                    // Ainda tem problemas
                                    let mensagem = '⚠️ Atualização parcial realizada.\n\n';

                                    if (verificacao.diferencas && verificacao.diferencas.length > 0) {
                                        mensagem += 'Problemas que ainda existem:\n';
                                        verificacao.diferencas.forEach(dif => {
                                            mensagem += '• ' + dif + '\n';
                                        });
                                    }

                                    if (data.erros && data.erros.length > 0) {
                                        mensagem += '\nErros durante a atualização:\n';
                                        data.erros.slice(0, 3).forEach(erro => {
                                            mensagem += '• ' + erro.substring(0, 100) + '\n';
                                        });
                                    }

                                    alert(mensagem);
                                }
                            });
                    })
                    .catch(error => {
                        console.error('Erro ao atualizar BD:', error);
                        alert('❌ Erro ao atualizar banco de dados.\n\n' + error.message);
                    });
            }
        });
    </script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        :root {
            --branco: #FFFFFF;
            --preto: #000000;
            --botao: #6598D2;
            --caixas: #4F6C8C;
            --fundo: #D1EAFF;
            --cinza-escuro: #333333;
            --cinza-medio: #888888;
            --azul-escuro: #0a1449;
            --azul-claro: #8ad7da;
            --vermelho: #ff0000;
            --verde: #2c9533;
            --vermelho-escuro: #b20000;
            --sombra-padrao: rgba(0, 0, 0, 0.6);
            --sombra-forte: rgba(0, 0, 0, 0.8);
            --sombra-leve: rgba(0, 0, 0, 0.5);
            --tabela_participantes: #deeaff;
            --botao-escuro: #154d8d;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--preto);
            overflow-x: hidden;
            background-color: var(--fundo);
        }

        /* Seção Principal de Boas-Vindas */
        .secao-principal {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--fundo);
            overflow: hidden;
            padding: 2rem;
        }

        /* Padrão geométrico de losangos */
        .secao-principal::before {
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
        }

        .conteudo-boas-vindas {
            position: relative;
            z-index: 10;
            display: grid;
            grid-template-columns: 400px 1fr;
            gap: 5rem;
            max-width: 1600px;
            width: 100%;
            align-items: center;
            animation: surgirDeBaixo 1s ease-out;
        }

        .texto-apresentacao {
            text-align: left;
            padding-right: 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
        }

        .logotipo-ceu {
            width: 200px;
            height: auto;
            margin-bottom: 2.5rem;
            align-self: center;
        }

        h1 {
            font-size: clamp(2.5rem, 5.5vw, 4rem);
            font-weight: 800;
            color: var(--azul-escuro);
            margin-bottom: 1.8rem;
            line-height: 1.1;
            letter-spacing: -0.03em;
            text-align: center;
            width: 100%;
        }

        .texto-subtitulo {
            font-size: clamp(1.05rem, 2.2vw, 1.35rem);
            color: var(--cinza-escuro);
            margin-bottom: 3rem;
            line-height: 1.8;
            font-weight: 400;
            text-align: center;
            width: 100%;
        }

        /* Carrossel de Imagens em Destaque - Proporção 16:9 */
        .carrossel-imagens-destaque {
            position: relative;
            width: 100%;
            aspect-ratio: 16 / 9;
            border-radius: 16px;
            overflow: hidden;
            box-shadow:
                0 25px 70px rgba(59, 130, 246, 0.15),
                0 10px 30px rgba(59, 130, 246, 0.1);
        }

        .container-carrossel {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .trilha-slides {
            display: flex;
            height: 100%;
            transition: transform 0.7s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: transform;
        }

        .slide-imagem {
            min-width: 100%;
            height: 100%;
            position: relative;
            flex-shrink: 0;
        }

        .slide-imagem img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.7s ease;
        }

        .slide-imagem.ativo img {
            transform: scale(1.02);
        }

        /* Slides clonados para efeito infinito */
        .slide-imagem.clonado {
            pointer-events: none;
        }

        /* Botões de navegação do carrossel */
        .botao-navegacao-carrossel {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            width: 55px;
            height: 55px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--botao);
            transition: all 0.3s ease;
            z-index: 20;
        }

        .botao-navegacao-carrossel:hover {
            color: var(--branco);
            transform: translateY(-50%) scale(1.1);
        }

        .botao-anterior {
            left: 2rem;
        }

        .botao-proximo {
            right: 2rem;
        }

        /* Indicadores de posição do carrossel */
        .indicadores-carrossel {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 0.7rem;
            z-index: 20;
        }

        .ponto-indicador {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(101, 152, 210, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .ponto-indicador:hover {
            background: rgba(101, 152, 210, 0.6);
            transform: scale(1.2);
        }

        .ponto-indicador.ativo {
            background: var(--botao);
            width: 30px;
            border-radius: 5px;
        }

        /* Botões de Chamada para Ação */
        .grupo-botoes-acao {
            display: flex;
            gap: 1rem;
            align-items: center;
            justify-content: center;
            width: 100%;
        }

        .botao-acao {
            padding: 1rem 2.5rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            letter-spacing: 0.3px;
            white-space: nowrap;
            cursor: pointer;
        }

        .botao-primario {
            background: var(--botao);
            color: var(--branco);
            box-shadow: 0 4px 15px rgba(101, 152, 210, 0.3);
        }

        .botao-primario:hover {
            background: var(--botao-escuro);
            box-shadow: 0 6px 20px rgba(101, 152, 210, 0.4);
            transform: scale(1.05);
        }

        .botao-secundario {
            background: var(--branco);
            color: var(--botao);
            border: 2px solid var(--botao);
        }

        .botao-secundario:hover {
            background: var(--botao);
            color: var(--branco);
            transform: scale(1.05);
        }

        /* Seção de Recursos da Plataforma */
        .secao-recursos {
            padding: 6rem 2rem;
            background: var(--branco);
        }

        .cabecalho-secao {
            text-align: center;
            max-width: 800px;
            margin: 0 auto 4rem;
        }

        .cabecalho-secao h2 {
            font-size: clamp(2rem, 4vw, 2.5rem);
            font-weight: 700;
            color: var(--azul-escuro);
            margin-bottom: 1rem;
        }

        .cabecalho-secao p {
            font-size: 1.1rem;
            color: var(--cinza-escuro);
            line-height: 1.7;
        }

        .grade-recursos {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .cartao-recurso {
            padding: 2.5rem;
            background: var(--branco);
            border-radius: 16px;
            border: 1px solid rgba(101, 152, 210, 0.1);
            transition: all 0.3s ease;
        }

        .cartao-recurso:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(101, 152, 210, 0.15);
            border-color: var(--botao);
        }

        .icone-recurso {
            width: 50px;
            height: 50px;
            background: var(--botao);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 1.5rem;
            line-height: 1;
        }

        .icone-recurso img {
            width: 60%;
            height: 60%;
            object-fit: contain;
        }

        .cartao-recurso h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--azul-escuro);
            margin-bottom: 1rem;
        }

        .cartao-recurso p {
            color: var(--cinza-escuro);
            line-height: 1.6;
        }

        /* Seção Final de Conversão */
        .secao-conversao-final {
            padding: 6rem 2rem;
            background: linear-gradient(135deg, var(--botao) 0%, var(--botao-escuro) 100%);
            text-align: center;
            color: var(--branco);
        }

        .secao-conversao-final h2 {
            font-size: clamp(2rem, 4vw, 2.5rem);
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .secao-conversao-final p {
            font-size: 1.1rem;
            margin-bottom: 2.5rem;
            opacity: 0.95;
        }

        .secao-conversao-final .botao-acao {
            background: var(--branco);
            color: var(--botao);
            font-size: 1.1rem;
            padding: 1.2rem 3rem;
        }

        .secao-conversao-final .botao-acao:hover {
            background: var(--tabela_participantes);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
            transform: scale(1.05);
        }

        /* Seção Sobre Nós */
        .secao-sobre {
            padding: 6rem 2rem;
            background: var(--fundo);
        }

        .cartao-sobre {
            max-width: 900px;
            margin: 0 auto;
            background: var(--branco);
            border-radius: 16px;
            padding: 3rem;
            box-shadow: 0 10px 30px rgba(101, 152, 210, 0.15);
        }

        .cartao-sobre h2 {
            font-size: clamp(2rem, 4vw, 2.5rem);
            font-weight: 700;
            color: var(--azul-escuro);
            text-align: center;
            margin-bottom: 2rem;
        }

        .cartao-sobre h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--botao);
            text-align: center;
            margin: 2rem 0 1rem;
        }

        .cartao-sobre p {
            font-size: 1.05rem;
            line-height: 1.8;
            color: var(--cinza-escuro);
            margin-bottom: 1.5rem;
            text-align: justify;
        }
    </style>
</head>

<body>
    <!-- Hero Section -->
    <section class="secao-principal">
        <div class="conteudo-boas-vindas">
            <div class="texto-apresentacao">
                <img src="./Imagens/CEU-Logo.png" alt="CEU Logo" class="logotipo-ceu">
                <h1>Controle de Eventos Unificado</h1>
                <p class="texto-subtitulo">
                    Gerencie seus eventos de forma inteligente e eficiente.
                    Inscrições, certificados e controle total em uma única plataforma.
                </p>
                <div class="grupo-botoes-acao">
                    <a href="./PaginasPublicas/ContainerPublico.php?pagina=inicio" class="botao-acao botao-primario">Começar Agora</a>
                    <a href="#sobre" class="botao-acao botao-secundario">Saiba Mais</a>
                </div>
                <div class="grupo-botoes-acao" style="margin-top: 1rem;">
                    <a href="./Certificacao/verificar.php" class="botao-acao botao-secundario" style="width: 100%; justify-content: center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 0.5rem;">
                            <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2" />
                            <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                        Verificar Certificado
                    </a>
                </div>
            </div>

            <div class="carrossel-imagens-destaque">
                <div class="container-carrossel">
                    <div class="trilha-slides">
                        <div class="slide-imagem">
                            <img src="./Imagens/Imagem_1-Carrossel_Inicial.png" alt="CEU - Slide 1">
                        </div>
                        <div class="slide-imagem">
                            <img src="./Imagens/Imagem_2-Carrossel_Inicial.png" alt="CEU - Slide 2">
                        </div>
                        <div class="slide-imagem">
                            <img src="./Imagens/Imagem_3-Carrossel_Inicial.png" alt="CEU - Slide 3">
                        </div>
                        <div class="slide-imagem">
                            <img src="./Imagens/Imagem_4-Carrossel_Inicial.png" alt="CEU - Slide 4">
                        </div>
                    </div>
                    <button class="botao-navegacao-carrossel botao-anterior">‹</button>
                    <button class="botao-navegacao-carrossel botao-proximo">›</button>
                    <div class="indicadores-carrossel">
                        <div class="ponto-indicador ativo"></div>
                        <div class="ponto-indicador"></div>
                        <div class="ponto-indicador"></div>
                        <div class="ponto-indicador"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Seção de Recursos e Vantagens da Plataforma -->
    <section class="secao-recursos">
        <div class="cabecalho-secao">
            <h2>Recursos e Vantagens do CEU</h2>
            <p>Tudo que você precisa para gerenciar eventos de sucesso em uma única plataforma moderna e intuitiva, ou seja, perfeita!</p>
        </div>
        <div class="grade-recursos">
            <div class="cartao-recurso">
                <div class="icone-recurso"><img src="./Imagens/CSV.svg"></div>
                <h3>Gestão de Inscrições</h3>
                <p>Gerencie inscrições profissionalmente com validação fácil e controle de participantes.</p>
            </div>
            <div class="cartao-recurso">
                <div class="icone-recurso"><img src="./Imagens/Chapeu-de-Graduacao.svg"></div>
                <h3>Certificados Automáticos</h3>
                <p>Seus participantes merecem certificados chiques! Criação automática e com autenticação!</p>
            </div>
            <div class="cartao-recurso">
                <div class="icone-recurso"><img src="./Imagens/gratis.svg"></div>
                <h3>100% Gratuito</h3>
                <p>Plataforma completamente gratuita. Acesso total sem se preocupar com assinsatura.</p>
            </div>
            <div class="cartao-recurso">
                <div class="icone-recurso"><img src="./Imagens/celular-portatil.svg"></div>
                <h3>Totalmente Responsivo</h3>
                <p>No PC, no celular, no tablet... Usufra um pedaço do CEU em diversos meios acessíveis!</p>
            </div>
            <div class="cartao-recurso">
                <div class="icone-recurso"><img src="./Imagens/lampada-acesa.svg"></div>
                <h3>Interface Intuitiva</h3>
                <p>Interface tão simples que nem vai precisar chamar o suporte técnico (mas temos um ótimo, caso precise).</p>
            </div>
            <div class="cartao-recurso">
                <div class="icone-recurso"><img src="./Imagens/trancar.svg"></div>
                <h3>Segurança</h3>
                <p>Seus dados são protegidos. Não precisa ter medo, somente você tem acesso e controle.</p>
            </div>
        </div>
    </section>

    <!-- Seção Sobre Nós -->
    <section class="secao-sobre" id="sobre">
        <div class="cartao-sobre">
            <h2>Sobre Nós</h2>
            <h3>Bem-vindo ao CEU!</h3>
            <p>
                Este site foi criado como parte da disciplina "Projetec" que tem o objetivo de criar um site ou
                aplicação que ajude em algum problema que nos afeta. O desenvolvimento do nosso site nasceu da
                dificuldade que encontramos em completar e categorizar corretamente nossa carga horária complementar
                ao longo do Ensino Médio.
            </p>
            <p>
                Nós somos um grupo de 8 estudantes (Ana Clara, Caike, Jean, Júlia, Nathally, Pâmela, Roxane e
                Victória) do Instituto Federal de Minas Gerais no Campus Sabará e o nosso objetivo é facilitar a
                criação e a certificação de eventos com praticidade e qualidade, garantindo que a pré e
                pós-realização dos mesmos sejam descomplicadas para todos os envolvidos. Ao longo do processo,
                exploramos ferramentas como o "Figma" para garantir que esse espaço fosse acessível e intuitivo.
            </p>
            <p>
                Esperamos que o site seja útil e facilite a experiência com a realização de eventos. Se tiver
                dúvidas ou sugestões, estamos abertos para ouvi-las!
            </p>
        </div>
    </section>

    <!-- Seção Final de Conversão -->
    <section class="secao-conversao-final">
        <h2>Pronto para Transformar Seus Eventos?</h2>
        <p>Junte-se a centenas de organizadores que já confiam no CEU para gerenciar seus eventos.</p>
        <a href="./PaginasPublicas/ContainerPublico.php?pagina=login" class="botao-acao">Comece Gratuitamente</a>
    </section>

    <script>
        // Gerenciador do Carrossel de Imagens com efeito infinito
        class GerenciadorCarrosselImagens {
            constructor() {
                this.trilha = document.querySelector('.trilha-slides');
                this.containerSlides = document.querySelector('.trilha-slides');
                this.slidesOriginais = Array.from(document.querySelectorAll('.slide-imagem'));
                this.botaoAnterior = document.querySelector('.botao-anterior');
                this.botaoProximo = document.querySelector('.botao-proximo');
                this.indicadores = document.querySelectorAll('.ponto-indicador');

                this.indiceAtual = 0;
                this.totalSlides = this.slidesOriginais.length;
                this.intervaloReproducaoAutomatica = null;
                this.estaEmTransicao = false;

                this.inicializar();
            }

            inicializar() {
                this.criarLoopInfinito();
                this.indiceAtual = 1;
                this.trilha.style.transition = 'none';
                this.trilha.style.transform = `translateX(-${this.indiceAtual * 100}%)`;
                this.trilha.offsetHeight;

                setTimeout(() => {
                    this.trilha.style.transition = 'transform 0.7s cubic-bezier(0.4, 0, 0.2, 1)';
                }, 50);

                this.botaoAnterior.addEventListener('click', () => this.slideAnterior());
                this.botaoProximo.addEventListener('click', () => this.proximoSlide());

                this.indicadores.forEach((indicador, indice) => {
                    indicador.addEventListener('click', () => this.irParaSlide(indice));
                });

                document.addEventListener('keydown', (e) => {
                    if (e.key === 'ArrowLeft') this.slideAnterior();
                    if (e.key === 'ArrowRight') this.proximoSlide();
                });

                this.adicionarSuporteToque();
                this.iniciarReproducaoAutomatica();

                const carrossel = document.querySelector('.carrossel-imagens-destaque');
                carrossel.addEventListener('mouseenter', () => this.pararReproducaoAutomatica());
                carrossel.addEventListener('mouseleave', () => this.iniciarReproducaoAutomatica());

                this.configurarObservadorIntersecao();
                this.atualizarIndicadores();
            }

            criarLoopInfinito() {
                const cloneUltimo = this.slidesOriginais[this.totalSlides - 1].cloneNode(true);
                cloneUltimo.classList.add('clonado');
                this.trilha.insertBefore(cloneUltimo, this.trilha.firstChild);

                const clonePrimeiro = this.slidesOriginais[0].cloneNode(true);
                clonePrimeiro.classList.add('clonado');
                this.trilha.appendChild(clonePrimeiro);

                this.slides = Array.from(document.querySelectorAll('.slide-imagem'));
            }

            adicionarSuporteToque() {
                let inicioX = 0;
                let fimX = 0;

                this.trilha.addEventListener('touchstart', (e) => {
                    inicioX = e.touches[0].clientX;
                });

                this.trilha.addEventListener('touchend', (e) => {
                    fimX = e.changedTouches[0].clientX;
                    const diferenca = inicioX - fimX;

                    if (Math.abs(diferenca) > 50) {
                        if (diferenca > 0) {
                            this.proximoSlide();
                        } else {
                            this.slideAnterior();
                        }
                    }
                });
            }

            configurarObservadorIntersecao() {
                const observador = new IntersectionObserver((entradas) => {
                    entradas.forEach(entrada => {
                        if (entrada.isIntersecting) {
                            this.iniciarReproducaoAutomatica();
                        } else {
                            this.pararReproducaoAutomatica();
                        }
                    });
                });

                observador.observe(document.querySelector('.carrossel-imagens-destaque'));
            }

            atualizarIndicadores() {
                let indiceReal = this.indiceAtual - 1;
                if (indiceReal < 0) indiceReal = this.totalSlides - 1;
                if (indiceReal >= this.totalSlides) indiceReal = 0;

                this.indicadores.forEach((indicador, indice) => {
                    indicador.classList.toggle('ativo', indice === indiceReal);
                });
            }

            proximoSlide() {
                if (this.estaEmTransicao) return;
                this.estaEmTransicao = true;

                this.indiceAtual++;
                this.trilha.style.transform = `translateX(-${this.indiceAtual * 100}%)`;
                this.atualizarIndicadores();

                this.trilha.addEventListener('transitionend', () => {
                    if (this.indiceAtual >= this.slides.length - 1) {
                        this.trilha.style.transition = 'none';
                        this.indiceAtual = 1;
                        this.trilha.style.transform = `translateX(-${this.indiceAtual * 100}%)`;

                        setTimeout(() => {
                            this.trilha.style.transition = 'transform 0.7s cubic-bezier(0.4, 0, 0.2, 1)';
                        }, 50);
                    }
                    this.estaEmTransicao = false;
                }, {
                    once: true
                });
            }

            slideAnterior() {
                if (this.estaEmTransicao) return;
                this.estaEmTransicao = true;

                this.indiceAtual--;
                this.trilha.style.transform = `translateX(-${this.indiceAtual * 100}%)`;
                this.atualizarIndicadores();

                this.trilha.addEventListener('transitionend', () => {
                    if (this.indiceAtual <= 0) {
                        this.trilha.style.transition = 'none';
                        this.indiceAtual = this.totalSlides;
                        this.trilha.style.transform = `translateX(-${this.indiceAtual * 100}%)`;

                        setTimeout(() => {
                            this.trilha.style.transition = 'transform 0.7s cubic-bezier(0.4, 0, 0.2, 1)';
                        }, 50);
                    }
                    this.estaEmTransicao = false;
                }, {
                    once: true
                });
            }

            irParaSlide(indice) {
                if (this.estaEmTransicao) return;
                this.indiceAtual = indice + 1;
                this.trilha.style.transform = `translateX(-${this.indiceAtual * 100}%)`;
                this.atualizarIndicadores();
            }

            iniciarReproducaoAutomatica() {
                this.pararReproducaoAutomatica();
                this.intervaloReproducaoAutomatica = setInterval(() => {
                    this.proximoSlide();
                }, 4000);
            }

            pararReproducaoAutomatica() {
                if (this.intervaloReproducaoAutomatica) {
                    clearInterval(this.intervaloReproducaoAutomatica);
                    this.intervaloReproducaoAutomatica = null;
                }
            }
        }

        // Inicializar quando o DOM estiver carregado
        document.addEventListener('DOMContentLoaded', () => {
            new GerenciadorCarrosselImagens();
        });
    </script>
</body>

</html>