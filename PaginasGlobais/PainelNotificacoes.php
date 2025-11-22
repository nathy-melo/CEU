<?php
// Painel de Notificações - Layout inspirado no padrão do site
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Notificações</title>
    <link rel="stylesheet" href="../styleGlobal.css" />
    <link rel="stylesheet" href="../styleGlobalMobile.css" media="(max-width: 767px)" />
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
            position: relative;
        }

        .btn-excluir-notificacao {
            background: rgba(255, 0, 0, 0.1);
            color: #ff6b6b;
            border: 1px solid rgba(255, 107, 107, 0.3);
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.2rem;
            font-weight: bold;
            transition: all 0.2s ease;
            padding: 0;
            line-height: 1;
            flex-shrink: 0;
        }

        .btn-excluir-notificacao:hover {
            background: rgba(255, 0, 0, 0.2);
            border-color: #ff6b6b;
            transform: scale(1.1);
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
        .notificacao-tipo-badge.mensagem-participante { background-color: #9C27B0; }

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

        /* ==== Estilos para Mensagem de Participante ==== */
        .notif-mensagem-participante {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .notif-remetente {
            background: rgba(101, 152, 210, 0.15);
            padding: 0.6rem 0.8rem;
            border-radius: 0.4rem;
            border-left: 3px solid var(--botao);
        }

        .notif-remetente strong {
            color: var(--botao);
            font-weight: 700;
        }

        .notif-remetente small {
            display: block;
            margin-top: 0.3rem;
            opacity: 0.8;
            font-size: 0.85rem;
        }

        .notif-evento {
            background: rgba(255, 255, 255, 0.05);
            padding: 0.5rem 0.8rem;
            border-radius: 0.4rem;
        }

        .notif-evento strong {
            color: var(--azul-claro);
            font-weight: 600;
        }

        .notif-conteudo {
            background: rgba(0, 0, 0, 0.2);
            padding: 0.8rem;
            border-radius: 0.4rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .notif-conteudo strong {
            color: var(--branco);
            font-weight: 600;
            display: block;
            margin-bottom: 0.5rem;
        }

        .notif-texto-mensagem {
            background: rgba(255, 255, 255, 0.03);
            padding: 0.6rem;
            border-radius: 0.3rem;
            line-height: 1.6;
            color: var(--branco);
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .btn-responder-mensagem {
            background: var(--botao) !important;
            color: white !important;
            border: none !important;
            padding: 0.5rem 1rem !important;
            border-radius: 0.4rem !important;
            cursor: pointer !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            transition: all 0.2s ease !important;
            white-space: nowrap !important;
        }

        .btn-responder-mensagem:hover {
            background: var(--azul-claro) !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 2px 8px rgba(101, 152, 210, 0.4) !important;
        }

        /* ==== Estilos para Thread de Mensagens (estilo Gmail) ==== */
        .notif-cabecalho-thread {
            background: rgba(101, 152, 210, 0.1);
            padding: 0.8rem;
            border-radius: 0.5rem;
            border-left: 4px solid var(--botao);
            margin-bottom: 1rem;
        }

        .notif-cabecalho-thread-compacto {
            background: rgba(101, 152, 210, 0.08);
            padding: 0.6rem 0.8rem;
            border-radius: 0.4rem;
            border-left: 3px solid var(--botao);
            margin-bottom: 0.75rem;
        }

        .notif-evento-thread {
            font-weight: 600;
            color: var(--botao);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.5rem;
        }

        .notif-thread-contador {
            font-size: 0.85rem;
            font-weight: 400;
            opacity: 0.8;
            color: var(--azul-claro);
        }

        .notif-participantes-thread {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .notif-ultima-mensagem {
            margin-bottom: 0.5rem;
        }

        .btn-expandir-thread {
            background: rgba(101, 152, 210, 0.1);
            color: var(--botao);
            border: 1px solid rgba(101, 152, 210, 0.3);
            padding: 0.5rem 0.8rem;
            border-radius: 0.4rem;
            cursor: pointer;
            font-size: 0.875rem;
            width: 100%;
            margin-top: 0.5rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-expandir-thread:hover {
            background: rgba(101, 152, 210, 0.2);
            border-color: var(--botao);
        }

        .thread-icon {
            font-size: 0.75rem;
            transition: transform 0.2s ease;
        }

        .notif-thread-completa {
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .notif-thread-mensagens {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .notif-mensagem-thread-item {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            transition: all 0.2s ease;
        }

        .notif-mensagem-thread-item.minha-mensagem {
            background: rgba(101, 152, 210, 0.15);
            border-left: 3px solid var(--botao);
            margin-left: 1rem;
        }

        .notif-mensagem-thread-item.outra-mensagem {
            background: rgba(255, 255, 255, 0.05);
            border-left: 3px solid var(--azul-claro);
            margin-right: 1rem;
        }

        .notif-mensagem-thread-cabecalho {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .notif-mensagem-thread-remetente {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }

        .notif-mensagem-thread-remetente strong {
            color: var(--botao);
            font-weight: 700;
            font-size: 0.95rem;
        }

        .notif-mensagem-thread-remetente small {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.8rem;
        }

        .notif-mensagem-thread-conteudo {
            line-height: 1.6;
            color: var(--branco);
            white-space: pre-wrap;
            word-wrap: break-word;
            padding: 0.5rem 0;
        }

        .notif-thread-separador {
            height: 1px;
            background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.2), transparent);
            margin: 0.75rem 0;
        }

        /* ==== Modal de Resposta ==== */
        .modal-resposta-mensagem {
            display: none;
            position: fixed;
            inset: 0;
            background: var(--fundo-escuro-transparente);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal-resposta-mensagem.ativo {
            display: flex;
        }

        .modal-resposta-mensagem .conteudo {
            background: var(--caixas);
            color: var(--texto);
            width: 100%;
            max-width: 32rem;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.35);
        }

        .modal-resposta-mensagem .cabecalho {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-weight: 800;
            font-size: 1.25rem;
        }

        .modal-resposta-mensagem button.fechar {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--texto);
            transition: opacity 0.2s;
        }

        .modal-resposta-mensagem button.fechar:hover {
            opacity: 0.7;
        }

        .modal-resposta-mensagem .form-group {
            margin-bottom: 1rem;
        }

        .modal-resposta-mensagem label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--texto);
        }

        .modal-resposta-mensagem input[type="text"],
        .modal-resposta-mensagem textarea {
            width: 100%;
            padding: 0.75rem;
            border-radius: 0.5rem;
            border: 1px solid var(--borda-clara);
            background: var(--fundo-claro-transparente);
            color: var(--texto);
            font-size: 0.95rem;
            font-family: inherit;
        }

        .modal-resposta-mensagem textarea {
            min-height: 8rem;
            resize: vertical;
        }

        .modal-resposta-mensagem .contador-caracteres {
            text-align: right;
            font-size: 0.85rem;
            color: var(--texto);
            margin-top: 0.5rem;
            opacity: 0.7;
        }

        .modal-resposta-mensagem .contador-caracteres.limite-alcancado {
            color: var(--vermelho);
            opacity: 1;
            font-weight: 600;
        }

        .modal-resposta-mensagem .acoes {
            margin-top: 1rem;
            display: flex;
            gap: 0.75rem;
            justify-content: space-between;
        }

        .modal-resposta-mensagem .botao {
            background: var(--botao);
            color: var(--branco);
            border: none;
            border-radius: 0.5rem;
            padding: 0.6rem 1.2rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .modal-resposta-mensagem .botao:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .modal-resposta-mensagem .botao-secundario {
            background: var(--vermelho);
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
                    <button class="btn-filtro" data-tipo="mensagem_participante">
                        <img src="../Imagens/Carta.svg" style="width: 14px; height: 14px; vertical-align: middle; margin-right: 4px; filter: invert(1);"> Mensagens
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

    <!-- Modal de Resposta Í  Mensagem -->
    <div id="modal-resposta-mensagem" class="modal-resposta-mensagem">
        <div class="conteudo" onclick="event.stopPropagation()">
            <div class="cabecalho">
                <span>Responder Mensagem</span>
                <button type="button" class="fechar" onclick="fecharModalResposta()" aria-label="Fechar">Í—</button>
            </div>
            <form id="form-resposta-mensagem" onsubmit="enviarRespostaMensagem(event)">
                <div class="form-group">
                    <label for="resposta-cpf-destinatario">CPF do Destinatário</label>
                    <input type="text" id="resposta-cpf-destinatario" readonly style="background: rgba(255, 255, 255, 0.1); opacity: 0.7;">
                </div>
                <div class="form-group">
                    <label for="resposta-titulo">Título da Notificação*</label>
                    <input type="text" id="resposta-titulo" maxlength="100" required placeholder="Ex: Resposta Í  sua mensagem">
                </div>
                <div class="form-group" id="grupo-mensagem-original" style="display: none;">
                    <label>Mensagem Original:</label>
                    <div id="resposta-mensagem-original" style="background: rgba(0, 0, 0, 0.2); padding: 0.75rem; border-radius: 0.4rem; border-left: 3px solid var(--botao); margin-top: 0.5rem; font-size: 0.9rem; line-height: 1.5; white-space: pre-wrap; word-wrap: break-word;"></div>
                </div>
                <div class="form-group">
                    <label for="resposta-conteudo">Mensagem*</label>
                    <textarea id="resposta-conteudo" maxlength="500" required placeholder="Digite sua resposta (máx. 500 caracteres)"></textarea>
                    <div id="contador-resposta" class="contador-caracteres">0 / 500</div>
                </div>
                <div class="acoes">
                    <button type="button" class="botao botao-secundario" onclick="fecharModalResposta()">Cancelar</button>
                    <button type="submit" class="botao">Enviar Resposta</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../PaginasGlobais/PainelNotificacoes.js"></script>
</body>
</html>


