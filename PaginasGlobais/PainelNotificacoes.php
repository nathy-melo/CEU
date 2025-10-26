<?php
// Painel de Notificações - Layout inspirado no padrão do site
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Notificações</title>
    <link rel="stylesheet" href="../styleGlobal.css">
    <style>
        /* ==== Seção Principal ==== */
        .secao-painel {
            flex: 1 0 auto;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: 1.75rem 0.9rem;
        }

        /* ==== Cartão Principal ==== */
        .cartao-painel {
            background-color: var(--caixas);
            color: var(--branco);
            border-radius: 0.9rem;
            box-shadow: 0 0.2rem 0.9rem 0 var(--sombra-padrao);
            padding: 1.8rem 0.9rem;
            max-width: 62rem;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 3rem 0;
        }

        /* ==== Cabeçalho do Painel ==== */
        .painel-cabecalho {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        .titulo-painel {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 1.75rem;
            line-height: 1.32;
            letter-spacing: -0.05em;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .contador-notificacoes {
            background-color: var(--botao);
            color: var(--branco);
            padding: 0.4rem 1rem;
            border-radius: 1.25rem;
            font-size: 0.875rem;
            font-weight: 600;
            white-space: nowrap;
        }

        /* ==== Filtros ==== */
        .painel-filtros {
            width: 100%;
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background-color: rgba(0, 0, 0, 0.15);
            border-radius: 0.5rem;
        }

        .btn-filtro {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--branco);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0.55rem 1rem;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn-filtro:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .btn-filtro.ativo {
            background-color: var(--botao);
            border-color: var(--botao);
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(101, 152, 210, 0.4);
        }

        /* ==== Lista de Notificações ==== */
        .painel-conteudo {
            width: 100%;
            max-width: 57.3rem;
            min-height: 20rem;
        }

        .lista-notificacoes {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        /* ==== Item de Notificação ==== */
        .notificacao-item {
            background-color: rgba(255, 255, 255, 0.05);
            border-left: 4px solid var(--botao);
            border-radius: 0.5rem;
            padding: 1rem 1.2rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .notificacao-item:hover {
            background-color: rgba(255, 255, 255, 0.08);
            transform: translateX(3px);
        }

        .notificacao-item.lida {
            opacity: 0.6;
            border-left-color: rgba(101, 152, 210, 0.4);
        }

        .notificacao-topo {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.6rem;
        }

        .notificacao-tipo-badge {
            background-color: var(--botao);
            color: var(--branco);
            padding: 0.25rem 0.7rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .notificacao-tipo-badge.inscricao { background-color: #4CAF50; }
        .notificacao-tipo-badge.desinscricao { background-color: #FF5722; }
        .notificacao-tipo-badge.evento_cancelado { background-color: #F44336; }
        .notificacao-tipo-badge.evento_prestes_iniciar { background-color: #FF9800; }
        .notificacao-tipo-badge.novo_participante { background-color: #2196F3; }

        .notificacao-mensagem {
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 0.8rem;
            color: var(--branco);
        }

        .notificacao-rodape {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            opacity: 0.7;
        }

        .notificacao-data {
            font-style: italic;
        }

        .btn-marcar-lida {
            background-color: transparent;
            border: 1px solid var(--botao);
            color: var(--branco);
            padding: 0.3rem 0.8rem;
            border-radius: 0.25rem;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-marcar-lida:hover {
            background-color: var(--botao);
            color: #FFF;
        }

        /* ==== Mensagem Vazia ==== */
        .painel-vazio {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--branco);
            opacity: 0.5;
            font-size: 1.05rem;
        }

        .painel-vazio::before {
            content: "";
            display: block;
            width: 64px;
            height: 64px;
            margin: 0 auto 1rem auto;
            background-image: url('../Imagens/notif-vazio.svg');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            filter: brightness(0) invert(1);
        }

        /* ==== Botão Voltar ==== */
        .botao-voltar {
            display: inline-block;
            background-color: var(--botao);
            color: var(--branco);
            border-radius: 0.25rem;
            padding: 0.8rem 2.3rem;
            text-decoration: none;
            font-weight: 700;
            font-size: 1rem;
            text-align: center;
            margin-top: 2rem;
            border: none;
            cursor: pointer;
        }

        /* ==== Scrollbar Personalizada ==== */
        .painel-conteudo::-webkit-scrollbar {
            width: 0.5rem;
        }

        .painel-conteudo::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
        }

        .painel-conteudo::-webkit-scrollbar-thumb {
            background: var(--botao);
            border-radius: 0.5rem;
        }

        .painel-conteudo::-webkit-scrollbar-thumb:hover {
            background: var(--azul-claro);
        }

        /* ==== Responsividade ==== */
        @media (max-width: 768px) {
            .cartao-painel {
                padding: 1.2rem 0.7rem;
            }

            .titulo-painel {
                font-size: 1.3rem;
            }

            .painel-cabecalho {
                flex-direction: column;
                gap: 0.8rem;
                align-items: flex-start;
            }

            .contador-notificacoes {
                align-self: flex-end;
            }

            .painel-filtros {
                gap: 0.4rem;
            }

            .btn-filtro {
                font-size: 0.8rem;
                padding: 0.45rem 0.7rem;
            }
        }
    </style>
</head>
<body>
    <div id="main-content">
        <div class="secao-painel">
            <div class="cartao-painel">
                <!-- Cabeçalho -->
                <div class="painel-cabecalho">
                    <h1 class="titulo-painel">
                        <img src="../Imagens/notif-geral.svg" style="width: 28px; height: 28px; vertical-align: middle;"> Painel de Notificações
                    </h1>
                    <span class="contador-notificacoes" id="contador-notificacoes">
                        0 não lidas
                    </span>
                </div>

                <!-- Filtros -->
                <div class="painel-filtros">
                    <button class="btn-filtro ativo" data-tipo="todas">
                        Todas
                    </button>
                    <button class="btn-filtro" data-tipo="inscricao">
                        <img src="../Imagens/notif-inscricao.svg" style="width: 14px; height: 14px; vertical-align: middle; margin-right: 4px;"> Inscrição
                    </button>
                    <button class="btn-filtro" data-tipo="desinscricao">
                        <img src="../Imagens/notif-desinscricao.svg" style="width: 14px; height: 14px; vertical-align: middle; margin-right: 4px;"> Desinscrição
                    </button>
                    <button class="btn-filtro" data-tipo="evento_cancelado">
                        <img src="../Imagens/notif-cancelado.svg" style="width: 14px; height: 14px; vertical-align: middle; margin-right: 4px;"> Cancelado
                    </button>
                    <button class="btn-filtro" data-tipo="evento_prestes_iniciar">
                        <img src="../Imagens/notif-relogio.svg" style="width: 14px; height: 14px; vertical-align: middle; margin-right: 4px;"> Iniciando
                    </button>
                    <button class="btn-filtro" data-tipo="novo_participante">
                        <img src="../Imagens/notif-usuario.svg" style="width: 14px; height: 14px; vertical-align: middle; margin-right: 4px;"> Novo Participante
                    </button>
                </div>

                <!-- Conteúdo / Lista de Notificações -->
                <div class="painel-conteudo">
                    <div class="lista-notificacoes" id="lista-notificacoes">
                        <div class="painel-vazio">
                            Carregando notificações...
                        </div>
                    </div>
                </div>

                <!-- Botão Voltar -->
                <button class="botao botao-voltar" id="btn-voltar">
                    Voltar
                </button>
            </div>
        </div>
    </div>
</body>
</html>
