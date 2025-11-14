<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Meus Eventos</title>
    <link rel="stylesheet" href="../styleGlobal.css" />
    <style>
        /* Botões flutuantes no card */
        .CaixaDoEvento {
            position: relative;
        }

        .AcoesFlutuantes {
            position: absolute;
            bottom: 1.5cqi;
            right: 2cqi;
            display: flex;
            flex-direction: column;
            gap: 2cqi;
            opacity: 0;
            visibility: hidden;
            transform: translateY(100%);
            transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1) 0.1s,
                visibility 0.4s cubic-bezier(0.4, 0, 0.2, 1) 0.1s,
                transform 0.4s cubic-bezier(0.4, 0, 0.2, 1) 0.1s;
            z-index: 50;
        }

        .CaixaDoEvento:hover .AcoesFlutuantes {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .BotaoAcaoCard {
            width: 11cqi;
            height: 11cqi;
            border-radius: 100%;
            background: var(--fundo-escuro-transparente);
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            padding: 0;
            cursor: pointer;
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .BotaoAcaoCard:hover {
            transform: scale(1.1);
            background: var(--fundo-hover-transparente);
        }

        .BotaoAcaoCard img {
            width: 7cqi;
            height: 7cqi;
            filter: invert(1);
            display: block;
        }

        /* Modal de Compartilhar - mesmo padrão do InicioParticipante */
        body.modal-aberto {
            overflow: hidden !important;
        }

        body.modal-aberto #main-content {
            overflow: hidden !important;
        }

        .modal-compartilhar {
            display: none;
            position: fixed;
            inset: 0;
            background: var(--fundo-escuro-transparente);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal-compartilhar.ativo {
            display: flex;
        }

        .modal-compartilhar .conteudo {
            background: var(--caixas);
            color: var(--texto);
            width: 100%;
            max-width: 32rem;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.35);
        }

        .modal-compartilhar .cabecalho {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-weight: 800;
            font-size: 1.25rem;
        }

        .modal-compartilhar button.fechar {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--texto);
            transition: opacity 0.2s;
        }

        .modal-compartilhar button.fechar:hover {
            opacity: 0.7;
        }

        .opcoes-compartilhamento {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .btn-compartilhar-app {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.4rem;
            background: none;
            border: none;
            cursor: pointer;
            transition: transform 0.2s;
            padding: 0.5rem;
        }

        .btn-compartilhar-app:hover {
            transform: translateY(-3px);
        }

        .icone-app {
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .icone-whatsapp {
            background: var(--whatsapp);
        }

        .icone-instagram {
            background: linear-gradient(45deg, var(--instagram-inicio) 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, var(--instagram-fim) 100%);
        }

        .icone-email {
            background: var(--email-vermelho);
        }

        .icone-x {
            background: var(--preto);
        }

        .icone-copiar {
            background: var(--botao);
        }

        .btn-compartilhar-app span {
            font-size: 0.75rem;
            color: var(--branco);
            font-weight: 500;
        }

        .campo-link {
            background: var(--fundo-claro-transparente);
            border: 1px solid var(--borda-clara);
            border-radius: 0.5rem;
            padding: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .campo-link input {
            flex: 1;
            background: transparent;
            border: none;
            color: var(--texto);
            font-size: 0.85rem;
            outline: none;
            font-family: monospace;
        }

        .aviso-compartilhar {
            background: var(--fundo-azul-info);
            border-left: 3px solid var(--botao);
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.8rem;
            color: var(--texto);
            line-height: 1.4;
        }

        .aviso-compartilhar strong {
            color: var(--botao);
        }

        /* Modais de confirmação desinscrição */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: var(--fundo-escuro-transparente);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal-overlay.ativo {
            display: flex;
        }

        .modal-cancelamento {
            background: var(--caixas);
            border-radius: 1.875rem;
            box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.4);
            padding: 1.875rem;
            max-width: 32rem;
            width: 90%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .modal-cancelamento-titulo {
            color: var(--branco);
            font-size: 1.5rem;
            margin: 0 0 2rem 0;
            text-align: center;
            font-weight: 600;
        }

        .modal-cancelamento-botoes {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            gap: 1rem;
        }

        .botao-cancelamento-cancelar,
        .botao-cancelamento-continuar,
        .botao-cancelamento-ok {
            flex: 1;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.625rem;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.3);
            transition: opacity 0.2s, transform 0.15s;
        }

        .botao-cancelamento-cancelar {
            background: var(--vermelho) !important;
            color: var(--branco);
        }

        .botao-cancelamento-continuar {
            background-color: var(--verde);
            color: var(--branco);
        }

        .botao-cancelamento-ok {
            background: var(--botao);
            color: var(--branco);
            width: 100%;
        }

        .botao-cancelamento-cancelar:hover,
        .botao-cancelamento-continuar:hover,
        .botao-cancelamento-ok:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        /* Modal de mensagem ao organizador */
        .modal-mensagem {
            display: none;
            position: fixed;
            inset: 0;
            background: var(--fundo-escuro-transparente);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal-mensagem.ativo {
            display: flex;
        }

        .modal-mensagem .conteudo {
            background: var(--caixas);
            color: var(--texto);
            width: 100%;
            max-width: 32rem;
            border-radius: 1rem;
            padding: 1.25rem;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.35);
        }

        .modal-mensagem .cabecalho {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            font-weight: 800;
            font-size: 1.15rem;
        }

        .modal-mensagem button.fechar {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--texto);
        }

        .modal-mensagem textarea {
            width: 100%;
            min-height: 8rem;
            resize: vertical;
            border-radius: 0.5rem;
            border: 1px solid var(--borda-clara);
            background: var(--fundo-claro-transparente);
            color: var(--texto);
            padding: 0.75rem;
            font-size: 0.95rem;
        }

        .modal-mensagem .acoes {
            margin-top: 0.75rem;
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }

        .modal-mensagem .botao-primario {
            background: var(--botao);
            color: var(--branco);
            border: none;
            border-radius: 0.5rem;
            padding: 0.6rem 1rem;
            font-weight: 700;
            cursor: pointer;
        }

        .modal-mensagem .botao-secundario {
            background: var(--vermelho);
            color: var(--branco);
            border: none;
            border-radius: 0.5rem;
            padding: 0.6rem 1rem;
            font-weight: 700;
            cursor: pointer;
        }

        /* Botão para abrir lista de favoritos */
        .BotaoFavoritosTrigger {
            width: clamp(30px, 4vw, 48px);
            aspect-ratio: 1 / 1;
            flex-shrink: 0;
            border-radius: 100% !important;
            background: var(--fundo-escuro-transparente);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
        }

        .BotaoFavoritosTrigger img {
            width: 1.25rem;
            height: 1.25rem;
            filter: invert(1);
            display: block;
        }

        /* Modal de Favoritos */
        .modal-favoritos {
            display: none;
            position: fixed;
            inset: 0;
            background: var(--fundo-escuro-transparente);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal-favoritos.ativo {
            display: flex;
        }

        .modal-favoritos .conteudo {
            background: var(--caixas);
            color: var(--texto);
            width: 100%;
            max-width: 60rem;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.35);
        }

        .modal-favoritos .cabecalho {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-weight: 800;
            font-size: 1.25rem;
        }

        .modal-favoritos button.fechar {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--texto);
            transition: opacity 0.2s;
        }

        .modal-favoritos button.fechar:hover {
            opacity: 0.7;
        }

        .lista-favoritos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
            max-height: 65vh;
            overflow-y: auto;
            padding: 0.25rem;
        }

        /* Cards de favoritos */
        .favorito-item {
            background-color: var(--branco);
            border-radius: 1cqi;
            padding: 0;
            box-shadow: 0.5cqi 0.5cqi 3cqi var(--sombra-forte);
            display: grid;
            aspect-ratio: 3 / 2;
            position: relative;
            overflow: hidden;
            container-type: inline-size;
            width: 100%;
            min-width: 0;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .favorito-item .AcoesFlutuantes {
            position: absolute;
            bottom: 0.3rem;
            right: 0.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            opacity: 0;
            visibility: hidden;
            transform: translateY(100%);
            transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1) 0.1s,
                visibility 0.4s cubic-bezier(0.4, 0, 0.2, 1) 0.1s,
                transform 0.4s cubic-bezier(0.4, 0, 0.2, 1) 0.1s;
            z-index: 50;
        }

        .favorito-item:hover .AcoesFlutuantes {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .favorito-item-imagem {
            width: 100%;
            height: 100%;
            border-radius: 2cqi 2cqi 0 0;
            aspect-ratio: 3 / 2;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1) 0.1s;
            transform: translateY(0);
            overflow: hidden;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--branco);
        }

        .favorito-item:hover .favorito-item-imagem {
            transform: translateY(-100%);
        }

        .favorito-item-imagem img {
            width: 100%;
            height: 100%;
            max-width: none;
            object-fit: cover;
            object-position: center;
            display: block;
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            flex-shrink: 0;
        }

        .favorito-item:hover .favorito-item-imagem img {
            transform: scale(1.15);
        }

        .favorito-item-titulo {
            font-size: 5cqi;
            font-weight: 800;
            padding: 4cqi 3.5cqi 4cqi;
            color: var(--branco);
            background: var(--botao);
            line-height: 1.2;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1) 0.1s;
            transform: translateY(0);
            text-shadow: 0 0.5cqi 1cqi rgba(0, 0, 0, 0.3);
            letter-spacing: 0.05cqi;
            grid-row: 2 / 3;
            position: relative;
            z-index: 2;
        }

        .favorito-item:hover .favorito-item-titulo {
            -webkit-line-clamp: 1;
            line-clamp: 1;
            transform: translateY(-380%);
        }

        .favorito-item-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            color: var(--cinza-escuro);
            line-height: 1.5;
            padding: 0 3.5cqi 2.5cqi;
            text-align: left;
            overflow: visible;
            word-wrap: break-word;
            display: block;
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) 0.1s;
            pointer-events: none;
            font-weight: 500;
            z-index: 3;
            transform: translateY(100%);
            width: 85%;
        }

        .favorito-item:hover .favorito-item-info {
            opacity: 1;
            transform: translateY(0%);
            pointer-events: auto;
        }

        .favorito-item-info .evento-info-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 1.5cqi;
        }

        .favorito-item-info .evento-info-item {
            display: flex;
            align-items: center;
            gap: 2cqi;
            background: var(--tabela_participantes);
            border-radius: 2cqi;
            padding: 1cqi 1cqi;
            box-shadow: 0 0.4cqi 1.2cqi var(--sombra-leve);
        }

        .favorito-item-info .evento-info-icone {
            width: 6cqi;
            height: 6cqi;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--branco);
            color: var(--botao);
            box-shadow: 0 0.3cqi 0.8cqi var(--sombra-leve) inset;
        }

        .favorito-item-info .evento-info-icone img {
            width: 80%;
            height: 80%;
            display: block;
        }

        .favorito-item-info .evento-info-texto {
            font-size: 4cqi;
            color: var(--cinza-escuro);
            font-weight: 600;
            display: inline-flex;
            gap: 1cqi;
            align-items: baseline;
        }

        .favorito-item-info .evento-info-label {
            color: var(--azul-escuro);
            font-weight: 800;
        }

        .lista-favoritos::-webkit-scrollbar {
            width: 0.5rem;
        }

        .lista-favoritos::-webkit-scrollbar-track {
            background: var(--fundo-claro-transparente);
            border-radius: 0.25rem;
        }

        .lista-favoritos::-webkit-scrollbar-thumb {
            background: var(--botao);
            border-radius: 0.25rem;
        }

        .lista-favoritos::-webkit-scrollbar-thumb:hover {
            background: var(--destaque);
        }
    </style>
</head>

<body>
    <div id="main-content">
        <div class="section-title-wrapper">
            <div class="barra-pesquisa-container">
                <!-- Botão de favoritos à esquerda da barra de pesquisa -->
                <button type="button" class="BotaoFavoritosTrigger botao" id="btn-abrir-favoritos" title="Ver favoritos"
                    aria-label="Ver favoritos">
                    <img src="../Imagens/Medalha_preenchida.svg" alt="Favoritos">
                </button>
                <div class="barra-pesquisa">
                    <div class="campo-pesquisa-wrapper">
                        <input class="campo-pesquisa" type="text" id="busca-meus-eventos" name="busca_meus_eventos" placeholder="Procurar eventos" autocomplete="off" />
                        <button class="botao-pesquisa" aria-label="Procurar">
                            <div class="icone-pesquisa">
                                <img src="../Imagens/lupa.png" alt="Lupa">
                            </div>
                        </button>
                    </div>
                </div>
                <button class="botao botao-filtrar">
                    <span>Filtrar</span>
                    <img src="../Imagens/filtro.png" alt="Filtro">
                </button>
            </div>
            <div class="div-section-title">
                <h1 class="section-title">Meus Eventos</h1>
            </div>
        </div>

        <div class="container" id="eventos-container">
            <!-- Eventos serão carregados via JavaScript -->
        </div>
    </div>

    <!-- Modal Favoritos -->
    <div id="modal-favoritos" class="modal-favoritos">
        <div class="conteudo" onclick="event.stopPropagation()">
            <div class="cabecalho">
                <span>Meus favoritos</span>
                <button type="button" class="fechar" onclick="fecharModalFavoritos()" aria-label="Fechar">×</button>
            </div>
            <div id="lista-favoritos" class="lista-favoritos"></div>
        </div>
    </div>

    <!-- Modal Compartilhar -->
    <div id="modal-compartilhar" class="modal-compartilhar">
        <div class="conteudo">
            <div class="cabecalho">
                <span>Compartilhar</span>
                <button type="button" class="fechar" onclick="fecharModalCompartilhar()" aria-label="Fechar">×</button>
            </div>

            <div class="opcoes-compartilhamento">
                <button class="btn-compartilhar-app" onclick="compartilharWhatsApp()" title="Compartilhar no WhatsApp">
                    <div class="icone-app icone-whatsapp">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                        </svg>
                    </div>
                    <span>WhatsApp</span>
                </button>

                <button class="btn-compartilhar-app" onclick="compartilharInstagram()" title="Compartilhar no Instagram">
                    <div class="icone-app icone-instagram">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
                        </svg>
                    </div>
                    <span>Instagram</span>
                </button>

                <button class="btn-compartilhar-app" onclick="compartilharEmail()" title="Compartilhar por E-mail">
                    <div class="icone-app icone-email">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
                        </svg>
                    </div>
                    <span>E-mail</span>
                </button>

                <button class="btn-compartilhar-app" onclick="compartilharX()" title="Compartilhar no X (Twitter)">
                    <div class="icone-app icone-x">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                        </svg>
                    </div>
                    <span>X</span>
                </button>

                <button class="btn-compartilhar-app" onclick="copiarLink()" title="Copiar Link">
                    <div class="icone-app icone-copiar" id="icone-copiar">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z" />
                        </svg>
                    </div>
                    <span id="texto-copiar">Copiar</span>
                </button>
            </div>

            <div class="campo-link">
                <input type="text" id="link-inscricao" readonly />
            </div>

            <div class="aviso-compartilhar">
                <strong>ℹ️ Informação:</strong> Compartilhe este evento com seus amigos e familiares!
            </div>
        </div>
    </div>

    <!-- Modais de confirmação desinscrição -->
    <div class="modal-overlay" id="modalConfirmarInscricao">
        <div class="modal-cancelamento" onclick="event.stopPropagation()">
            <h2 class="modal-cancelamento-titulo">Deseja se inscrever neste evento?</h2>
            <div class="modal-cancelamento-botoes">
                <button type="button" class="botao-cancelamento-cancelar botao"
                    onclick="fecharModalConfirmarInscricao(); event.stopPropagation();">Cancelar</button>
                <button type="button" class="botao-cancelamento-continuar botao"
                    onclick="confirmarInscricaoRapida(); event.stopPropagation();">Confirmar</button>
            </div>
        </div>
    </div>
    <div class="modal-overlay" id="modalConfirmarDesinscricao">
        <div class="modal-cancelamento" onclick="event.stopPropagation()">
            <h2 class="modal-cancelamento-titulo">Deseja cancelar sua inscrição neste evento?</h2>
            <div class="modal-cancelamento-botoes">
                <button type="button" class="botao-cancelamento-cancelar botao"
                    onclick="fecharModalConfirmarDesinscricao(); event.stopPropagation();">Não</button>
                <button type="button" class="botao-cancelamento-continuar botao"
                    onclick="confirmarDesinscricaoRapida(); event.stopPropagation();">Sim, cancelar</button>
            </div>
        </div>
    </div>
    <div class="modal-overlay" id="modalDesinscricaoConfirmada">
        <div class="modal-cancelamento" onclick="event.stopPropagation()">
            <h2 class="modal-cancelamento-titulo">Inscrição cancelada com sucesso!</h2>
            <div class="modal-cancelamento-botoes">
                <button type="button" class="botao-cancelamento-ok botao"
                    onclick="fecharModalDesinscricaoConfirmada(); event.stopPropagation();">OK</button>
            </div>
        </div>
    </div>
    <div class="modal-overlay" id="modalInscricaoConfirmada">
        <div class="modal-cancelamento" onclick="event.stopPropagation()">
            <h2 class="modal-cancelamento-titulo">Inscrição realizada com sucesso!</h2>
            <div class="modal-cancelamento-botoes">
                <button type="button" class="botao-cancelamento-ok botao"
                    onclick="fecharModalInscricaoConfirmada(); event.stopPropagation();">OK</button>
            </div>
        </div>
    </div>

    <!-- Modal Mensagem ao Organizador -->
    <div id="modal-mensagem" class="modal-mensagem">
        <div class="conteudo" onclick="event.stopPropagation()">
            <div class="cabecalho">
                <span>Enviar mensagem ao organizador</span>
                <button type="button" class="fechar" onclick="fecharModalMensagem()" aria-label="Fechar">×</button>
            </div>
            <div>
                <textarea id="texto-mensagem-organizador" maxlength="500"
                    placeholder="Escreva sua mensagem (máx. 500 caracteres)"></textarea>
                <div class="acoes">
                    <button class="botao-secundario botao" type="button" onclick="fecharModalMensagem()">Cancelar</button>
                    <button class="botao-primario botao" type="button" onclick="enviarMensagemOrganizador()">Enviar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let eventoAtualCompartilhar = null;

        function abrirModalCompartilharEvento(codEvento) {
            eventoAtualCompartilhar = codEvento;
            const modal = document.getElementById('modal-compartilhar');
            if (!modal) return;
            
            const linkInscricao = window.location.origin + '/CEU/PaginasPublicas/ContainerPublico.php?pagina=evento&cod_evento=' + codEvento;
            document.getElementById('link-inscricao').value = linkInscricao;
            
            modal.classList.add('ativo');
            document.body.style.overflow = 'hidden';
        }

        function fecharModalCompartilhar() {
            const modal = document.getElementById('modal-compartilhar');
            if (modal) {
                modal.classList.remove('ativo');
                document.body.style.overflow = '';
            }
            eventoAtualCompartilhar = null;
        }

        function copiarLink() {
            const linkInput = document.getElementById('link-inscricao');
            const textoSpan = document.getElementById('texto-copiar');
            const iconeDiv = document.getElementById('icone-copiar');
            
            linkInput.select();
            
            navigator.clipboard.writeText(linkInput.value).then(() => {
                textoSpan.textContent = '✓ Copiado!';
                iconeDiv.style.background = '#28a745';
                setTimeout(() => {
                    textoSpan.textContent = 'Copiar';
                    iconeDiv.style.background = '';
                }, 2000);
            }).catch(() => {
                try {
                    document.execCommand('copy');
                    textoSpan.textContent = '✓ Copiado!';
                    iconeDiv.style.background = '#28a745';
                    setTimeout(() => {
                        textoSpan.textContent = 'Copiar';
                        iconeDiv.style.background = '';
                    }, 2000);
                } catch (e) {
                    alert('Por favor, copie o link manualmente.');
                }
            });
        }

        function compartilharWhatsApp() {
            const link = document.getElementById('link-inscricao').value;
            const texto = encodeURIComponent('Confira este evento! Inscreva-se aqui: ' + link);
            window.open('https://wa.me/?text=' + texto, '_blank');
        }

        function compartilharInstagram() {
            const link = document.getElementById('link-inscricao').value;
            navigator.clipboard.writeText(link).then(() => {
                alert('Link copiado! Cole no Instagram para compartilhar.\n\nDica: Você pode colar o link na sua bio, em stories ou em posts.');
            }).catch(() => {
                const textarea = document.createElement('textarea');
                textarea.value = link;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                alert('Link copiado! Cole no Instagram para compartilhar.\n\nDica: Você pode colar o link na sua bio, em stories ou em posts.');
            });
        }

        function compartilharEmail() {
            const link = document.getElementById('link-inscricao').value;
            const assunto = encodeURIComponent('Convite para Evento');
            const corpo = encodeURIComponent('Olá!\n\nGostaria de convidá-lo(a) para participar deste evento.\n\nInscreva-se através do link: ' + link + '\n\nAté breve!');
            window.location.href = 'mailto:?subject=' + assunto + '&body=' + corpo;
        }

        function compartilharX() {
            const link = document.getElementById('link-inscricao').value;
            const texto = encodeURIComponent('Confira este evento! 🎉');
            window.open('https://twitter.com/intent/tweet?text=' + texto + '&url=' + encodeURIComponent(link), '_blank');
        }

        // Fecha o modal ao clicar fora
        document.getElementById('modal-compartilhar').onclick = function(e) {
            if (e.target === this) {
                fecharModalCompartilhar();
            }
        };

        // Fecha o modal ao pressionar ESC
        document.addEventListener('keydown', function(e) {
            if ((e.key === 'Escape' || e.key === 'Esc') && document.getElementById('modal-compartilhar').classList.contains('ativo')) {
                fecharModalCompartilhar();
            }
        });

        // Variáveis globais para controle de modais
        let codEvento = null;
        let codEventoAcao = null;
        let btnDesinscreverAtual = null;
        let codEventoMensagem = null;

        // Funções de bloqueio/desbloqueio de scroll
        function bloquearScroll() {
            document.body.classList.add('modal-aberto');
            document.addEventListener('wheel', prevenirScroll, { passive: false });
            document.addEventListener('touchmove', prevenirScroll, { passive: false });
            document.addEventListener('keydown', prevenirScrollTeclado, false);
        }
        function desbloquearScroll() {
            document.body.classList.remove('modal-aberto');
            document.removeEventListener('wheel', prevenirScroll);
            document.removeEventListener('touchmove', prevenirScroll);
            document.removeEventListener('keydown', prevenirScrollTeclado);
        }
        function prevenirScroll(e) { if (document.body.classList.contains('modal-aberto')) { e.preventDefault(); } }
        function prevenirScrollTeclado(e) {
            if (!document.body.classList.contains('modal-aberto')) return;
            const teclas = [32, 33, 34, 35, 36, 37, 38, 39, 40];
            if (teclas.includes(e.keyCode)) e.preventDefault();
        }

        // Modal de compartilhar
        function abrirModalCompartilhar() {
            if (!codEvento) return;
            const modal = document.getElementById('modal-compartilhar');
            const linkEvento = `${window.location.origin}/CEU/PaginasPublicas/EventoPublico.php?codEvento=${codEvento}`;
            const input = document.getElementById('link-inscricao');
            if (input) input.value = linkEvento;
            modal.classList.add('ativo');
            bloquearScroll();
        }
        function fecharModalCompartilhar() {
            const modal = document.getElementById('modal-compartilhar');
            modal.classList.remove('ativo');
            desbloquearScroll();
        }
        function copiarLink() {
            const input = document.getElementById('link-inscricao');
            input.select();
            input.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(input.value).then(() => {
                const iconeCopiar = document.getElementById('icone-copiar');
                const textoCopiar = document.getElementById('texto-copiar');
                iconeCopiar.innerHTML = '<svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>';
                textoCopiar.textContent = 'Copiado!';
                setTimeout(() => {
                    iconeCopiar.innerHTML = '<svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>';
                    textoCopiar.textContent = 'Copiar';
                }, 2000);
            });
        }
        function compartilharWhatsApp() {
            if (!codEvento) return;
            const linkEvento = `${window.location.origin}/CEU/PaginasPublicas/EventoPublico.php?codEvento=${codEvento}`;
            const texto = `Confira este evento: ${linkEvento}`;
            window.open(`https://wa.me/?text=${encodeURIComponent(texto)}`, '_blank');
        }
        function compartilharInstagram() {
            if (!codEvento) return;
            const linkEvento = `${window.location.origin}/CEU/PaginasPublicas/EventoPublico.php?codEvento=${codEvento}`;
            navigator.clipboard.writeText(linkEvento).then(() => {
                alert('Link copiado! Cole no Instagram para compartilhar.');
            }).catch(() => {
                const input = document.getElementById('link-inscricao');
                input.select();
                document.execCommand('copy');
                alert('Link copiado! Cole no Instagram para compartilhar.');
            });
        }
        function compartilharEmail() {
            if (!codEvento) return;
            const linkEvento = `${window.location.origin}/CEU/PaginasPublicas/EventoPublico.php?codEvento=${codEvento}`;
            const assunto = 'Confira este evento!';
            const corpo = `Olá! Gostaria de compartilhar este evento com você: ${linkEvento}`;
            window.location.href = `mailto:?subject=${encodeURIComponent(assunto)}&body=${encodeURIComponent(corpo)}`;
        }
        function compartilharX() {
            if (!codEvento) return;
            const linkEvento = `${window.location.origin}/CEU/PaginasPublicas/EventoPublico.php?codEvento=${codEvento}`;
            const texto = `Confira este evento!`;
            window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(texto)}&url=${encodeURIComponent(linkEvento)}`, '_blank');
        }
        document.getElementById('modal-compartilhar').onclick = function (e) { if (e.target === this) fecharModalCompartilhar(); };

        // Modais de desinscrição
        function abrirModalConfirmarDesinscricao() {
            document.getElementById('modalConfirmarDesinscricao').classList.add('ativo');
            bloquearScroll();
        }
        function fecharModalConfirmarDesinscricao() {
            document.getElementById('modalConfirmarDesinscricao').classList.remove('ativo');
            desbloquearScroll();
        }
        function abrirModalDesinscricaoConfirmada() {
            document.getElementById('modalDesinscricaoConfirmada').classList.add('ativo');
            bloquearScroll();
        }
        function fecharModalDesinscricaoConfirmada() {
            document.getElementById('modalDesinscricaoConfirmada').classList.remove('ativo');
            desbloquearScroll();
        }
        function abrirModalConfirmarInscricao() {
            document.getElementById('modalConfirmarInscricao').classList.add('ativo');
            bloquearScroll();
        }
        function fecharModalConfirmarInscricao() {
            document.getElementById('modalConfirmarInscricao').classList.remove('ativo');
            desbloquearScroll();
        }
        function abrirModalInscricaoConfirmada() {
            document.getElementById('modalInscricaoConfirmada').classList.add('ativo');
            bloquearScroll();
        }
        function fecharModalInscricaoConfirmada() {
            document.getElementById('modalInscricaoConfirmada').classList.remove('ativo');
            desbloquearScroll();
        }
        function fecharTodosModaisConfirmacao() {
            document.querySelectorAll('.modal-overlay.ativo').forEach(m => m.classList.remove('ativo'));
            desbloquearScroll();
        }

        async function confirmarDesinscricaoRapida() {
            if (!codEventoAcao) { fecharModalConfirmarDesinscricao(); return; }
            
            // Salvar o código do evento antes de fazer a requisição
            const codEventoParaDesinscrever = codEventoAcao;
            const btnParaAtualizar = btnDesinscreverAtual;
            
            try {
                const r = await fetch('DesinscreverEvento.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    credentials: 'include',
                    body: new URLSearchParams({ cod_evento: codEventoParaDesinscrever })
                });
                const j = await r.json();
                fecharModalConfirmarDesinscricao();
                if (j && j.sucesso) {
                    // IMPORTANTE: Limpar o cache deste evento específico para forçar atualização
                    inscricaoCache.delete(codEventoParaDesinscrever);
                    // Atualizar cache com o novo valor
                    inscricaoCache.set(codEventoParaDesinscrever, false);
                    
                    if (btnParaAtualizar) {
                        atualizarIconeInscricao(btnParaAtualizar, false);
                    }
                    abrirModalDesinscricaoConfirmada();
                    // Recarrega a lista de eventos após desinscrição
                    setTimeout(() => {
                        // Limpar cache antes de recarregar para garantir dados atualizados
                        if (window.inscricaoCache) {
                            window.inscricaoCache.clear();
                        }
                        if (typeof window.carregarEventosDoServidor === 'function') {
                            window.carregarEventosDoServidor();
                        }
                    }, 500);
                } else {
                    alert(j.mensagem || 'Erro ao cancelar inscrição.');
                }
            } catch (e) {
                fecharModalConfirmarDesinscricao();
                alert('Erro ao cancelar inscrição.');
            } finally {
                // Limpar variáveis após processamento
                codEventoAcao = null;
                btnDesinscreverAtual = null;
            }
        }

        async function confirmarInscricaoRapida() {
            if (!codEventoAcao) { fecharModalConfirmarInscricao(); return; }
            
            // Salvar o código do evento antes de fazer a requisição
            const codEventoParaInscrever = codEventoAcao;
            const btnParaAtualizar = btnDesinscreverAtual;
            
            try {
                const r = await fetch('InscreverEvento.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    credentials: 'include',
                    body: new URLSearchParams({ cod_evento: codEventoParaInscrever })
                });
                const j = await r.json();
                fecharModalConfirmarInscricao();
                if (j && j.sucesso) {
                    // IMPORTANTE: Limpar o cache deste evento específico para forçar atualização
                    inscricaoCache.delete(codEventoParaInscrever);
                    // Atualizar cache com o novo valor
                    inscricaoCache.set(codEventoParaInscrever, true);
                    
                    if (btnParaAtualizar) {
                        atualizarIconeInscricao(btnParaAtualizar, true);
                    }
                    abrirModalInscricaoConfirmada();
                    // Recarrega a lista de eventos após inscrição
                    setTimeout(() => {
                        // Limpar cache antes de recarregar para garantir dados atualizados
                        if (window.inscricaoCache) {
                            window.inscricaoCache.clear();
                        }
                        if (typeof window.carregarEventosDoServidor === 'function') {
                            window.carregarEventosDoServidor();
                        }
                    }, 500);
                } else {
                    alert(j.mensagem || 'Erro ao realizar inscrição.');
                }
            } catch (e) {
                fecharModalConfirmarInscricao();
                alert('Erro ao realizar inscrição.');
            } finally {
                // Limpar variáveis após processamento
                codEventoAcao = null;
                btnDesinscreverAtual = null;
            }
        }

        // Modal de mensagem ao organizador
        function abrirModalMensagem() {
            const m = document.getElementById('modal-mensagem');
            document.getElementById('texto-mensagem-organizador').value = '';
            m.classList.add('ativo');
            bloquearScroll();
        }
        function fecharModalMensagem(skipUnlock) {
            const m = document.getElementById('modal-mensagem');
            m.classList.remove('ativo');
            if (!skipUnlock) desbloquearScroll();
        }
        async function enviarMensagemOrganizador() {
            const texto = (document.getElementById('texto-mensagem-organizador').value || '').trim();
            if (!codEventoMensagem) { fecharModalMensagem(); return; }
            if (texto.length === 0) { alert('Digite sua mensagem.'); return; }
            try {
                const r = await fetch('EnviarMensagemOrganizador.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    credentials: 'include',
                    body: new URLSearchParams({ cod_evento: codEventoMensagem, mensagem: texto })
                });
                const j = await r.json();
                fecharModalMensagem();
                if (j && j.sucesso) {
                    alert('Mensagem enviada ao organizador!');
                } else {
                    alert(j.mensagem || 'Não foi possível enviar a mensagem.');
                }
            } catch (e) {
                fecharModalMensagem();
                alert('Erro ao enviar mensagem.');
            }
        }

        // Favoritos
        const favoritosSet = new Set();
        let favoritosDados = [];

        // Inscrição - cache e funções
        const inscricaoCache = new Map();
        window.inscricaoCache = inscricaoCache;

        function atualizarIconeInscricao(btn, inscrito) {
            if (!btn) return;
            const img = btn.querySelector('img');
            if (!img) return;
            if (inscrito) {
                img.src = '../Imagens/Circulo_check.svg';
                img.alt = 'Inscrito';
                btn.setAttribute('data-inscrito', '1');
                btn.title = 'Cancelar inscrição';
                btn.ariaLabel = 'Cancelar inscrição';
            } else {
                img.src = '../Imagens/Circulo_adicionar.svg';
                img.alt = 'Inscrever';
                btn.setAttribute('data-inscrito', '0');
                btn.title = 'Inscrever-se';
                btn.ariaLabel = 'Inscrever';
            }
        }
        window.atualizarIconeInscricao = atualizarIconeInscricao;

        async function verificarInscricao(cod, forcarAtualizacao = false) {
            // Sempre limpar o cache do evento específico antes de verificar para garantir dados atualizados
            if (forcarAtualizacao) {
                inscricaoCache.delete(cod);
            }
            
            // Se não forçar atualização e tiver no cache, usar cache
            if (!forcarAtualizacao && inscricaoCache.has(cod)) {
                return inscricaoCache.get(cod);
            }
            
            // Sempre verificar do servidor quando forçar atualização ou não tiver cache
            try {
                const r = await fetch(`VerificarInscricao.php?cod_evento=${cod}`, { credentials: 'include' });
                const j = await r.json();
                const val = !!j.inscrito;
                inscricaoCache.set(cod, val);
                return val;
            } catch (e) {
                // Se falhar, usar cache se existir, senão retornar false
                return inscricaoCache.has(cod) ? inscricaoCache.get(cod) : false;
            }
        }

        function atualizarIconeFavorito(btn, fav) {
            if (!btn) return;
            const img = btn.querySelector('img');
            if (!img) return;
            // Usar cache-busting para forçar atualização da imagem
            const timestamp = Date.now();
            if (fav) {
                img.src = `../Imagens/Medalha_preenchida.svg?t=${timestamp}`;
                img.alt = 'Desfavoritar';
                btn.title = 'Remover dos favoritos';
                btn.setAttribute('data-favorito', '1');
            } else {
                img.src = `../Imagens/Medalha_linha.svg?t=${timestamp}`;
                img.alt = 'Favoritar';
                btn.title = 'Adicionar aos favoritos';
                btn.setAttribute('data-favorito', '0');
            }
        }

        async function carregarFavoritos() {
            try {
                const r = await fetch('ListarFavoritos.php', { credentials: 'include' });
                if (r.status === 401) { favoritosSet.clear(); favoritosDados = []; return; }
                const j = await r.json();
                if (j && j.sucesso) {
                    favoritosSet.clear();
                    favoritosDados = j.favoritos || [];
                    for (const f of favoritosDados) favoritosSet.add(Number(f.cod_evento));
                }
            } catch (e) {
                // silencia
            }
        }

        function abrirModalFavoritos() {
            renderizarFavoritos();
            document.getElementById('modal-favoritos').classList.add('ativo');
            bloquearScroll();
        }
        function fecharModalFavoritos() {
            document.getElementById('modal-favoritos').classList.remove('ativo');
            desbloquearScroll();
            // Restaurar o estado do menu após fechar o modal
            // Usar setTimeout para garantir que o DOM foi atualizado
            setTimeout(() => {
                const params = new URLSearchParams(window.location.search);
                const pagina = params.get('pagina') || 'meusEventos';
                if (typeof window.setMenuAtivoPorPagina === 'function') {
                    window.setMenuAtivoPorPagina(pagina);
                } else {
                    // Fallback: tentar novamente após um pequeno delay
                    setTimeout(() => {
                        if (typeof window.setMenuAtivoPorPagina === 'function') {
                            window.setMenuAtivoPorPagina(pagina);
                        }
                    }, 100);
                }
            }, 50);
        }
        
        function renderizarFavoritos() {
            const cont = document.getElementById('lista-favoritos');
            cont.innerHTML = '';
            if (!favoritosDados || favoritosDados.length === 0) {
                cont.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:var(--texto);padding:1rem;">Nenhum evento favoritado.</div>';
                return;
            }
            const frag = document.createDocumentFragment();
            favoritosDados.forEach(ev => {
                const a = document.createElement('a');
                a.href = `ContainerParticipante.php?pagina=evento&id=${ev.cod_evento}`;
                a.className = 'favorito-item';
                // Adicionar onclick ao link para verificar se é clique direto
                a.onclick = function(e) {
                    // Se o clique foi em um botão, não navega
                    if (e.target.closest('.BotaoAcaoCard')) {
                        e.preventDefault();
                        return false;
                    }
                };

                const divAcoes = document.createElement('div');
                divAcoes.className = 'AcoesFlutuantes';

                // Botão Inscrever/Desinscrever
                const btnInscrever = document.createElement('button');
                btnInscrever.type = 'button';
                btnInscrever.className = 'BotaoAcaoCard BotaoInscreverCard botao';
                btnInscrever.title = 'Inscrever-se';
                btnInscrever.setAttribute('aria-label', 'Inscrever');
                btnInscrever.setAttribute('data-cod', ev.cod_evento);
                btnInscrever.setAttribute('data-inscrito', '0');
                btnInscrever.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                };
                const imgInscrever = document.createElement('img');
                imgInscrever.src = '../Imagens/Circulo_adicionar.svg';
                imgInscrever.alt = 'Inscrever';
                btnInscrever.appendChild(imgInscrever);
                divAcoes.appendChild(btnInscrever);

                const btnFavorito = document.createElement('button');
                btnFavorito.type = 'button';
                btnFavorito.className = 'BotaoAcaoCard BotaoFavoritoCard botao';
                btnFavorito.title = 'Remover dos favoritos';
                btnFavorito.setAttribute('aria-label', 'Desfavoritar');
                btnFavorito.setAttribute('data-cod', ev.cod_evento);
                btnFavorito.setAttribute('data-favorito', '1');
                btnFavorito.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                };
                const imgFavorito = document.createElement('img');
                imgFavorito.src = '../Imagens/Medalha_preenchida.svg';
                imgFavorito.alt = 'Desfavoritar';
                btnFavorito.appendChild(imgFavorito);
                divAcoes.appendChild(btnFavorito);

                const btnMensagem = document.createElement('button');
                btnMensagem.type = 'button';
                btnMensagem.className = 'BotaoAcaoCard BotaoMensagemCard botao';
                btnMensagem.title = 'Enviar mensagem ao organizador';
                btnMensagem.setAttribute('aria-label', 'Mensagem');
                btnMensagem.setAttribute('data-cod', ev.cod_evento);
                btnMensagem.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                };
                const imgMensagem = document.createElement('img');
                imgMensagem.src = '../Imagens/Carta.svg';
                imgMensagem.alt = 'Mensagem';
                btnMensagem.appendChild(imgMensagem);
                divAcoes.appendChild(btnMensagem);

                const btnCompartilhar = document.createElement('button');
                btnCompartilhar.type = 'button';
                btnCompartilhar.className = 'BotaoAcaoCard BotaoCompartilharCard botao';
                btnCompartilhar.title = 'Compartilhar';
                btnCompartilhar.setAttribute('aria-label', 'Compartilhar');
                btnCompartilhar.setAttribute('data-cod', ev.cod_evento);
                btnCompartilhar.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                };
                const imgCompartilhar = document.createElement('img');
                imgCompartilhar.src = '../Imagens/Icone_Compartilhar.svg';
                imgCompartilhar.alt = 'Compartilhar';
                btnCompartilhar.appendChild(imgCompartilhar);
                divAcoes.appendChild(btnCompartilhar);

                const divImagem = document.createElement('div');
                divImagem.className = 'favorito-item-imagem';
                const img = document.createElement('img');
                const caminho = '../' + (ev.imagem && ev.imagem !== '' ? ev.imagem.replace(/^\\/, '').replace(/^\//, '') : 'ImagensEventos/CEU-Logo.png');
                img.src = caminho;
                img.alt = ev.nome || 'Evento';
                divImagem.appendChild(img);

                const divTitulo = document.createElement('div');
                divTitulo.className = 'favorito-item-titulo';
                divTitulo.textContent = ev.nome || 'Evento';

                const divInfo = document.createElement('div');
                divInfo.className = 'favorito-item-info';
                const ul = document.createElement('ul');
                ul.className = 'evento-info-list';

                const liCategoria = document.createElement('li');
                liCategoria.className = 'evento-info-item';
                liCategoria.innerHTML = `<span class="evento-info-icone"><img src="../Imagens/info-categoria.svg" alt="" /></span><span class="evento-info-texto"><span class="evento-info-label">Categoria:</span> ${ev.categoria || 'N/A'}</span>`;
                ul.appendChild(liCategoria);

                const liModalidade = document.createElement('li');
                liModalidade.className = 'evento-info-item';
                liModalidade.innerHTML = `<span class="evento-info-icone"><img src="../Imagens/info-modalidade.svg" alt="" /></span><span class="evento-info-texto"><span class="evento-info-label">Modalidade:</span> ${ev.modalidade || 'N/A'}</span>`;
                ul.appendChild(liModalidade);

                if (ev.inicio) {
                    const liData = document.createElement('li');
                    liData.className = 'evento-info-item';
                    const dataFormatada = new Date(ev.inicio).toLocaleDateString('pt-BR');
                    liData.innerHTML = `<span class="evento-info-icone"><img src="../Imagens/info-data.svg" alt="" /></span><span class="evento-info-texto"><span class="evento-info-label">Data:</span> ${dataFormatada}</span>`;
                    ul.appendChild(liData);
                }

                if (ev.lugar) {
                    const liLocal = document.createElement('li');
                    liLocal.className = 'evento-info-item';
                    liLocal.innerHTML = `<span class="evento-info-icone"><img src="../Imagens/info-local.svg" alt="" /></span><span class="evento-info-texto"><span class="evento-info-label">Local:</span> ${ev.lugar}</span>`;
                    ul.appendChild(liLocal);
                }

                const liCert = document.createElement('li');
                liCert.className = 'evento-info-item';
                liCert.innerHTML = `<span class="evento-info-icone"><img src="../Imagens/info-certificado.svg" alt="" /></span><span class="evento-info-texto"><span class="evento-info-label">Certificado:</span> ${ev.certificado == 1 ? 'Sim' : 'Não'}</span>`;
                ul.appendChild(liCert);

                divInfo.appendChild(ul);
                a.appendChild(divAcoes);
                a.appendChild(divImagem);
                a.appendChild(divTitulo);
                a.appendChild(divInfo);
                frag.appendChild(a);
            });
            cont.appendChild(frag);

            // Atualizar status de inscrição nos cards de favoritos
            setTimeout(async () => {
                const codigosFavoritos = favoritosDados.map(ev => ev.cod_evento);
                if (codigosFavoritos.length === 0) return;

                try {
                    const r = await fetch('VerificarInscricoes.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'include',
                        body: JSON.stringify({ eventos: codigosFavoritos })
                    });

                    if (r.status === 401) return;

                    const j = await r.json();
                    if (j && j.sucesso && j.inscricoes) {
                        for (const [codEvento, inscrito] of Object.entries(j.inscricoes)) {
                            const cod = Number(codEvento);
                            if (window.inscricaoCache) window.inscricaoCache.set(cod, inscrito);

                            // Atualizar ícone nos cards de favoritos
                            const btnInscrever = cont.querySelector(`.BotaoInscreverCard[data-cod="${cod}"]`);
                            if (btnInscrever && typeof window.atualizarIconeInscricao === 'function') {
                                window.atualizarIconeInscricao(btnInscrever, inscrito);
                            }
                        }
                    }
                } catch (e) {
                    // Silenciar erro
                }
            }, 100);
        }

        // Listeners de clique
        document.addEventListener('click', async function (e) {
            // Botão de inscrever/desinscrever (também nos favoritos) - MESMO PADRÃO DO InicioParticipante
            const btnInscrever = e.target.closest('.BotaoInscreverCard');
            if (btnInscrever) {
                e.preventDefault(); e.stopPropagation();
                const cod = Number(btnInscrever.getAttribute('data-cod')) || 0;
                if (!cod) return;
                
                // IMPORTANTE: Atualizar as variáveis ANTES de verificar status
                codEventoAcao = cod;
                btnDesinscreverAtual = btnInscrever;
                
                // IMPORTANTE: Forçar atualização do servidor para garantir dados corretos
                const inscrito = await verificarInscricao(cod, true); // forçar atualização
                
                // Atualizar ícone com o valor correto do servidor
                atualizarIconeInscricao(btnInscrever, inscrito);
                
                if (inscrito) {
                    abrirModalConfirmarDesinscricao();
                } else {
                    abrirModalConfirmarInscricao();
                }
                return;
            }

            // Botão de desinscrição
            const btnDesinscrever = e.target.closest('.BotaoDesinscreverCard');
            if (btnDesinscrever) {
                e.preventDefault(); e.stopPropagation();
                const cod = Number(btnDesinscrever.getAttribute('data-cod')) || 0;
                if (!cod) return;
                
                // IMPORTANTE: Atualizar as variáveis ANTES de abrir modal
                codEventoAcao = cod;
                btnDesinscreverAtual = btnDesinscrever;
                
                abrirModalConfirmarDesinscricao();
                return;
            }

            const btnMsg = e.target.closest('.BotaoMensagemCard');
            if (btnMsg) {
                e.preventDefault(); e.stopPropagation();
                const cod = Number(btnMsg.getAttribute('data-cod')) || 0;
                if (!cod) return;
                codEventoMensagem = cod;
                abrirModalMensagem();
                return;
            }

            const btnCompartilhar = e.target.closest('.BotaoCompartilharCard');
            if (btnCompartilhar) {
                e.preventDefault(); e.stopPropagation();
                const cod = Number(btnCompartilhar.getAttribute('data-cod')) || 0;
                if (!cod) return;
                codEvento = cod;
                abrirModalCompartilhar();
                return;
            }

            const btnFav = e.target.closest('.BotaoFavoritoCard');
            if (btnFav) {
                e.preventDefault(); e.stopPropagation();
                
                // Prevenir cliques múltiplos enquanto processa
                if (btnFav.disabled || btnFav.dataset.processing === 'true') {
                    return;
                }
                
                const cod = Number(btnFav.getAttribute('data-cod')) || 0;
                if (!cod) return;
                
                // ATUALIZAÇÃO OTIMISTA: Determinar estado atual e novo estado
                const estadoAtual = btnFav.getAttribute('data-favorito') === '1';
                const novoEstado = !estadoAtual; // Toggle
                
                // ATUALIZAR UI IMEDIATAMENTE (antes do fetch)
                btnFav.dataset.processing = 'true';
                btnFav.dataset.recentlyUpdated = 'true';
                
                // Atualizar favoritosSet imediatamente
                if (novoEstado) { 
                    favoritosSet.add(cod); 
                } else { 
                    favoritosSet.delete(cod);
                    favoritosDados = favoritosDados.filter(f => Number(f.cod_evento) !== cod);
                }
                
                // Atualizar ícone IMEDIATAMENTE
                atualizarIconeFavorito(btnFav, novoEstado);
                const img = btnFav.querySelector('img');
                if (img) {
                    const timestamp = Date.now();
                    if (novoEstado) {
                        img.src = `../Imagens/Medalha_preenchida.svg?t=${timestamp}`;
                    } else {
                        img.src = `../Imagens/Medalha_linha.svg?t=${timestamp}`;
                    }
                }
                
                // Fazer fetch em background
                try {
                    const r = await fetch('ToggleFavorito.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        credentials: 'include',
                        body: new URLSearchParams({ cod_evento: cod })
                    });
                    
                    if (r.status === 401) { 
                        // Reverter mudança se não autenticado
                        if (estadoAtual) { 
                            favoritosSet.add(cod); 
                        } else { 
                            favoritosSet.delete(cod); 
                        }
                        atualizarIconeFavorito(btnFav, estadoAtual);
                        const imgRevert = btnFav.querySelector('img');
                        if (imgRevert) {
                            const timestampRevert = Date.now();
                            if (estadoAtual) {
                                imgRevert.src = `../Imagens/Medalha_preenchida.svg?t=${timestampRevert}`;
                            } else {
                                imgRevert.src = `../Imagens/Medalha_linha.svg?t=${timestampRevert}`;
                            }
                        }
                        btnFav.dataset.recentlyUpdated = 'false';
                        alert('Faça login para favoritar eventos.'); 
                        btnFav.dataset.processing = 'false';
                        return; 
                    }
                    
                    const j = await r.json();
                    if (j && j.sucesso) {
                        // Sincronizar com resposta do servidor (caso haja diferença)
                        if (j.favoritado !== novoEstado) {
                            // Se o servidor retornou um estado diferente, atualizar
                            if (j.favoritado) { 
                                favoritosSet.add(cod); 
                            } else { 
                                favoritosSet.delete(cod);
                                favoritosDados = favoritosDados.filter(f => Number(f.cod_evento) !== cod);
                            }
                            atualizarIconeFavorito(btnFav, j.favoritado);
                            const imgSync = btnFav.querySelector('img');
                            if (imgSync) {
                                const timestampSync = Date.now();
                                if (j.favoritado) {
                                    imgSync.src = `../Imagens/Medalha_preenchida.svg?t=${timestampSync}`;
                                } else {
                                    imgSync.src = `../Imagens/Medalha_linha.svg?t=${timestampSync}`;
                                }
                            }
                        }
                        
                        // Remover a marcação após um delay para garantir que não seja sobrescrito
                        setTimeout(() => {
                            const estadoAtualBtn = btnFav.getAttribute('data-favorito') === '1';
                            const estadoEsperadoSet = favoritosSet.has(cod);
                            if (estadoAtualBtn === estadoEsperadoSet) {
                                btnFav.dataset.recentlyUpdated = 'false';
                            } else {
                                // Se não estiver sincronizado, atualizar
                                atualizarIconeFavorito(btnFav, estadoEsperadoSet);
                                const imgSync2 = btnFav.querySelector('img');
                                if (imgSync2) {
                                    const timestampSync2 = Date.now();
                                    if (estadoEsperadoSet) {
                                        imgSync2.src = `../Imagens/Medalha_preenchida.svg?t=${timestampSync2}`;
                                    } else {
                                        imgSync2.src = `../Imagens/Medalha_linha.svg?t=${timestampSync2}`;
                                    }
                                }
                                setTimeout(() => {
                                    btnFav.dataset.recentlyUpdated = 'false';
                                }, 500);
                            }
                        }, 1500);
                    } else {
                        // Reverter mudança se houver erro
                        if (estadoAtual) { 
                            favoritosSet.add(cod); 
                        } else { 
                            favoritosSet.delete(cod); 
                        }
                        atualizarIconeFavorito(btnFav, estadoAtual);
                        const imgRevert = btnFav.querySelector('img');
                        if (imgRevert) {
                            const timestampRevert = Date.now();
                            if (estadoAtual) {
                                imgRevert.src = `../Imagens/Medalha_preenchida.svg?t=${timestampRevert}`;
                            } else {
                                imgRevert.src = `../Imagens/Medalha_linha.svg?t=${timestampRevert}`;
                            }
                        }
                        btnFav.dataset.recentlyUpdated = 'false';
                        alert(j.mensagem || 'Não foi possível atualizar favorito.');
                    }
                } catch (err) {
                    // Reverter mudança se houver erro de rede
                    console.error('Erro ao atualizar favorito:', err);
                    if (estadoAtual) { 
                        favoritosSet.add(cod); 
                    } else { 
                        favoritosSet.delete(cod); 
                    }
                    atualizarIconeFavorito(btnFav, estadoAtual);
                    const imgRevert = btnFav.querySelector('img');
                    if (imgRevert) {
                        const timestampRevert = Date.now();
                        if (estadoAtual) {
                            imgRevert.src = `../Imagens/Medalha_preenchida.svg?t=${timestampRevert}`;
                        } else {
                            imgRevert.src = `../Imagens/Medalha_linha.svg?t=${timestampRevert}`;
                        }
                    }
                    btnFav.dataset.recentlyUpdated = 'false';
                    alert('Erro ao atualizar favorito.');
                } finally {
                    // Reabilitar botão após processamento
                    btnFav.dataset.processing = 'false';
                }
                return;
            }

            if (e.target.closest('#btn-abrir-favoritos')) {
                e.preventDefault(); e.stopPropagation();
                await carregarFavoritos();
                abrirModalFavoritos();
                return;
            }
        }, true);
        
        // Garantir que o ícone não seja resetado quando o mouse sai do botão
        document.addEventListener('mouseleave', function (e) {
            const btn = e.target.closest('.BotaoFavoritoCard');
            if (!btn) return;
            // Se o botão foi atualizado recentemente, garantir que o estado seja mantido
            if (btn.dataset.recentlyUpdated === 'true') {
                const cod = Number(btn.getAttribute('data-cod')) || 0;
                if (cod) {
                    const estadoEsperado = favoritosSet.has(cod);
                    const estadoAtual = btn.getAttribute('data-favorito') === '1';
                    // Só atualizar se o estado estiver diferente
                    if (estadoAtual !== estadoEsperado) {
                        atualizarIconeFavorito(btn, estadoEsperado);
                        // Forçar atualização visual
                        const img = btn.querySelector('img');
                        if (img) {
                            const timestamp = Date.now();
                            if (estadoEsperado) {
                                img.src = `../Imagens/Medalha_preenchida.svg?t=${timestamp}`;
                            } else {
                                img.src = `../Imagens/Medalha_linha.svg?t=${timestamp}`;
                            }
                        }
                    }
                }
            }
        }, true);

        const modalFav = document.getElementById('modal-favoritos');
        if (modalFav) {
            modalFav.onclick = function (e) { if (e.target === this) fecharModalFavoritos(); };
            const listaFavoritos = document.getElementById('lista-favoritos');
            if (listaFavoritos) {
                listaFavoritos.addEventListener('wheel', function (e) { e.stopPropagation(); }, { passive: false });
                listaFavoritos.addEventListener('touchmove', function (e) { e.stopPropagation(); }, { passive: false });
            }
        }

        document.addEventListener('keydown', function (e) { 
            if (e.key === 'Escape' || e.key === 'Esc') { 
                fecharModalCompartilhar(); 
                fecharModalMensagem(true); 
                fecharTodosModaisConfirmacao(); 
                fecharModalFavoritos();
            } 
        });

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', carregarFavoritos);
        } else {
            setTimeout(carregarFavoritos, 50);
        }
    </script>

    <script src="MeusEventosParticipante.js?v=<?= time() ?>"></script>
</body>

</html>
