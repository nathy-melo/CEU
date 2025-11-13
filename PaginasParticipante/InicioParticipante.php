<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Eventos Acontecendo</title>
    <link rel="stylesheet" href="../styleGlobal.css" />
    <style>
        /* Botões flutuantes no card (inscrever, mensagem, compartilhar) */
        .CaixaDoEvento { position: relative; }
        .AcoesFlutuantes {
            position: absolute;
            bottom: 0.5rem;
            right: 0.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-0.2rem);
            transition: opacity 0.3s ease, visibility 0.3s ease, transform 0.2s ease;
            z-index: 50;
        }
        .CaixaDoEvento:hover .AcoesFlutuantes {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .BotaoAcaoCard {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 999px;
            background: rgba(0,0,0,0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            padding: 0;
            cursor: pointer;
        }
        .BotaoAcaoCard img { width: 1.2rem; height: 1.2rem; filter: invert(1); display: block; }

        /* Botão compartilhar no card (compat) */
        .CaixaDoEvento { position: relative; }
        .BotaoCompartilharCard {
            position: relative; /* agora relativo dentro da pilha */
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 999px;
            background: rgba(0,0,0,0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.2s ease, transform 0.2s ease;
            transform: scale(1);
            border: none;
            padding: 0;
            cursor: pointer;
        }
        .BotaoCompartilharCard img { width: 1.1rem; height: 1.1rem; display: block; filter: invert(1); }

        /* Modal de Compartilhar - mesmo padrão do CartaodoEventoParticipante */
        body.modal-aberto { overflow: hidden !important; }
        body.modal-aberto #main-content { overflow: hidden !important; }
        .modal-compartilhar { display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6); z-index: 10000; align-items: center; justify-content: center; padding: 1rem; }
        .modal-compartilhar.ativo { display: flex; }
        .modal-compartilhar .conteudo { background: var(--caixas); color: var(--texto); width: 100%; max-width: 32rem; border-radius: 1rem; padding: 1.5rem; box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.35); }
        .modal-compartilhar .cabecalho { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; font-weight: 800; font-size: 1.25rem; }
        .modal-compartilhar button.fechar { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--texto); transition: opacity 0.2s; }
        .modal-compartilhar button.fechar:hover { opacity: 0.7; }
        .opcoes-compartilhamento { display: flex; gap: 1rem; justify-content: center; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .btn-compartilhar-app { display: flex; flex-direction: column; align-items: center; gap: 0.4rem; background: none; border: none; cursor: pointer; transition: transform 0.2s; padding: 0.5rem; }
        .btn-compartilhar-app:hover { transform: translateY(-3px); }
        .icone-app { width: 3.5rem; height: 3.5rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; color: white; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2); }
        .icone-whatsapp { background: #25D366; }
        .icone-instagram { background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); }
        .icone-email { background: #EA4335; }
        .icone-x { background: #000000; }
        .icone-copiar { background: var(--botao); }
        .btn-compartilhar-app span { font-size: 0.75rem; color: var(--branco); font-weight: 500; }
        .campo-link { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 0.5rem; padding: 0.75rem; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; }
        .campo-link input { flex: 1; background: transparent; border: none; color: var(--texto); font-size: 0.85rem; outline: none; font-family: monospace; }
        .aviso-compartilhar { background: rgba(66, 135, 245, 0.1); border-left: 3px solid var(--botao); padding: 0.75rem; border-radius: 0.5rem; font-size: 0.8rem; color: var(--texto); line-height: 1.4; }
        .aviso-compartilhar strong { color: var(--botao); }

        /* Modais de confirmação inscrição/desinscrição (padrão dos cartões) */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 10000; align-items: center; justify-content: center; padding: 1rem; }
        .modal-overlay.ativo { display: flex; }
        .modal-cancelamento { background: var(--caixas); border-radius: 1.875rem; box-shadow: 0 0.5rem 2rem rgba(0,0,0,0.4); padding: 1.875rem; max-width: 32rem; width: 90%; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .modal-cancelamento-titulo { color: var(--branco); font-size: 1.5rem; margin: 0 0 2rem 0; text-align: center; font-weight: 600; }
        .modal-cancelamento-botoes { display: flex; flex-direction: row; justify-content: space-between; align-items: center; width: 100%; gap: 1rem; }
        .botao-cancelamento-cancelar, .botao-cancelamento-continuar, .botao-cancelamento-ok { flex: 1; padding: 0.75rem 1.5rem; border: none; border-radius: 0.625rem; font-size: 1.1rem; font-weight: 700; cursor: pointer; box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,0.3); transition: opacity 0.2s, transform 0.15s; }
        .botao-cancelamento-cancelar { background: var(--vermelho) !important; color: var(--branco); }
        .botao-cancelamento-continuar { background-color: #28a745; color: var(--branco); }
        .botao-cancelamento-ok { background: var(--botao); color: var(--branco); width: 100%; }
        .botao-cancelamento-cancelar:hover, .botao-cancelamento-continuar:hover, .botao-cancelamento-ok:hover { opacity: 0.9; transform: translateY(-2px); }

        /* Modal de mensagem ao organizador */
        .modal-mensagem { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 10000; align-items: center; justify-content: center; padding: 1rem; }
        .modal-mensagem.ativo { display: flex; }
        .modal-mensagem .conteudo { background: var(--caixas); color: var(--texto); width: 100%; max-width: 32rem; border-radius: 1rem; padding: 1.25rem; box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.35); }
        .modal-mensagem .cabecalho { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; font-weight: 800; font-size: 1.15rem; }
        .modal-mensagem button.fechar { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--texto); }
        .modal-mensagem textarea { width: 100%; min-height: 8rem; resize: vertical; border-radius: 0.5rem; border: 1px solid rgba(255,255,255,0.15); background: rgba(255,255,255,0.07); color: var(--texto); padding: 0.75rem; font-size: 0.95rem; }
        .modal-mensagem .acoes { margin-top: 0.75rem; display: flex; gap: 0.75rem; justify-content: flex-end; }
        .modal-mensagem .botao-primario { background: var(--botao); color: var(--branco); border: none; border-radius: 0.5rem; padding: 0.6rem 1rem; font-weight: 700; cursor: pointer; }
        .modal-mensagem .botao-secundario { background: var(--vermelho); color: var(--branco); border: none; border-radius: 0.5rem; padding: 0.6rem 1rem; font-weight: 700; cursor: pointer; }

        /* Botão para abrir lista de favoritos (à esquerda da barra de pesquisa) */
        .BotaoFavoritosTrigger {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 999px;
            background: rgba(0,0,0,0.6);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            margin-right: 0.75rem;
        }
        .BotaoFavoritosTrigger img { width: 1.25rem; height: 1.25rem; filter: invert(1); display: block; }

        /* Modal de Favoritos */
        .modal-favoritos { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 10000; align-items: center; justify-content: center; padding: 1rem; }
        .modal-favoritos.ativo { display: flex; }
        .modal-favoritos .conteudo { background: var(--caixas); color: var(--texto); width: 100%; max-width: 48rem; border-radius: 1rem; padding: 1.25rem; box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.35); }
        .modal-favoritos .cabecalho { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; font-weight: 800; font-size: 1.15rem; }
        .modal-favoritos button.fechar { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--texto); }
        .lista-favoritos { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 0.75rem; max-height: 60vh; overflow-y: auto; }
        .favorito-item { background: var(--branco); border-radius: 0.75rem; overflow: hidden; box-shadow: 0 0.15rem 0.75rem rgba(0,0,0,0.25); color: #000; display: flex; flex-direction: column; }
        .favorito-item img { width: 100%; height: 120px; object-fit: cover; }
        .favorito-item .detalhes { padding: 0.6rem 0.75rem; display: flex; flex-direction: column; gap: 0.35rem; }
        .favorito-item .titulo { font-weight: 700; font-size: 0.95rem; color: #000; }
        .favorito-item .meta { font-size: 0.8rem; color: #333; }
        .favorito-item a { text-decoration: none; color: inherit; }
    </style>
</head>
<body>
    <?php
    include_once '../BancoDados/conexao.php';

    // Buscar eventos do banco de dados
    $sql = "SELECT cod_evento, categoria, nome, inicio, conclusao, duracao, certificado, lugar, modalidade, imagem FROM evento ORDER BY inicio";
    $res = mysqli_query($conexao, $sql);

    // Função para formatar strings para os atributos data-*
    function formatar($txt) {
        $map = [
            'Á'=>'A','À'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A','á'=>'a','à'=>'a','â'=>'a','ã'=>'a','ä'=>'a',
            'É'=>'E','È'=>'E','Ê'=>'E','Ë'=>'E','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
            'Í'=>'I','Ì'=>'I','Î'=>'I','Ï'=>'I','í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
            'Ó'=>'O','Ò'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O','ó'=>'o','ò'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
            'Ú'=>'U','Ù'=>'U','Û'=>'U','Ü'=>'U','ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u',
            'Ç'=>'C','ç'=>'c'
        ];
        $txt = strtr($txt ?? '', $map);
        $txt = strtolower($txt);
        $txt = str_replace(' ', '_', $txt);
        return preg_replace('/[^a-z0-9_]/','', $txt);
    }
    ?>

    <div id="main-content">
        <div class="section-title-wrapper">
            <div class="barra-pesquisa-container">
                <!-- Botão de favoritos à esquerda da barra de pesquisa -->
                <button type="button" class="BotaoFavoritosTrigger" id="btn-abrir-favoritos" title="Ver favoritos" aria-label="Ver favoritos">
                    <img src="../Imagens/Medalha_preenchida.svg" alt="Favoritos">
                </button>
                <div class="barra-pesquisa">
                    <div class="campo-pesquisa-wrapper">
                        <input class="campo-pesquisa" type="text" id="busca-eventos" name="busca_eventos" placeholder="Procurar eventos" autocomplete="off" />
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
                <h1 class="section-title">Eventos acontecendo</h1>
            </div>
        </div>

        <div class="container" id="eventos-container">
            <?php if ($res && mysqli_num_rows($res) > 0): ?>
                <?php while ($ev = mysqli_fetch_assoc($res)):
                    $dataInicioISO = date('Y-m-d', strtotime($ev['inicio']));
                    $dataFormatada = date('d/m/y', strtotime($ev['inicio']));
                    $tipo = formatar($ev['categoria']);
                    $local = formatar($ev['lugar']);
                    $modalidadeAttr = formatar($ev['modalidade'] ?? '');
                    
                    // Mapeia duração numérica (horas) para faixas usadas no filtro
                    $duracaoFaixa = '';
                    if (is_numeric($ev['duracao'])) {
                        $h = (float)$ev['duracao'];
                        if ($h < 1) { $duracaoFaixa = 'menos_1h'; }
                        elseif ($h < 2) { $duracaoFaixa = '1h_2h'; }
                        elseif ($h < 4) { $duracaoFaixa = '2h_4h'; }
                        else { $duracaoFaixa = 'mais_5h'; }
                    }
                    
                    // Certificado: simples sim/nao
                    $cert = ((int)$ev['certificado'] === 1) ? 'sim' : 'nao';
                    $certTexto = ($cert === 'sim') ? 'Sim' : 'Não';
                    
                    // Preparar caminho da imagem
                    $imagem_evento = isset($ev['imagem']) && $ev['imagem'] !== '' ? $ev['imagem'] : 'ImagensEventos/CEU-Logo.png';
                    $caminho_imagem = '../' . ltrim($imagem_evento, "/\\");
                ?>
                    <a class="botao CaixaDoEvento" 
                        style="text-decoration:none;color:inherit;display:block;"
                        href="ContainerParticipante.php?pagina=evento&id=<?= (int)$ev['cod_evento'] ?>"
                        data-tipo="<?= htmlspecialchars($tipo) ?>"
                        data-modalidade="<?= htmlspecialchars($modalidadeAttr) ?>"
                        data-localizacao="<?= htmlspecialchars($local) ?>"
                        data-duracao="<?= htmlspecialchars($duracaoFaixa) ?>"
                        data-data="<?= $dataInicioISO ?>"
                        data-certificado="<?= $cert ?>"
                        data-cod-evento="<?= (int)$ev['cod_evento'] ?>">
                        <!-- Ações flutuantes: Inscrever, Favoritar, Mensagem, Compartilhar -->
                        <div class="AcoesFlutuantes">
                            <button type="button" class="BotaoAcaoCard BotaoInscreverCard" title="Inscrever-se rapidamente" aria-label="Inscrever" data-cod="<?= (int)$ev['cod_evento'] ?>" data-inscrito="0">
                                <img src="../Imagens/Circulo_adicionar.svg" alt="Inscrever">
                            </button>
                            <button type="button" class="BotaoAcaoCard BotaoFavoritoCard" title="Favoritar" aria-label="Favoritar" data-cod="<?= (int)$ev['cod_evento'] ?>" data-favorito="0">
                                <img src="../Imagens/Medalha_linha.svg" alt="Favoritar">
                            </button>
                            <button type="button" class="BotaoAcaoCard BotaoMensagemCard" title="Enviar mensagem ao organizador" aria-label="Mensagem" data-cod="<?= (int)$ev['cod_evento'] ?>">
                                <img src="../Imagens/Carta.svg" alt="Mensagem">
                            </button>
                            <button type="button" class="BotaoCompartilharCard botao" title="Compartilhar" aria-label="Compartilhar" data-cod="<?= (int)$ev['cod_evento'] ?>">
                                <img src="../Imagens/Icone_Compartilhar.svg" alt="Compartilhar" />
                            </button>
                        </div>
                        <div class="EventoImagem">
                            <img src="<?= htmlspecialchars($caminho_imagem) ?>" alt="<?= htmlspecialchars($ev['nome']) ?>">
                        </div>
                        <div class="EventoTitulo"><?= htmlspecialchars($ev['nome']) ?></div>
                        <div class="EventoInfo">
                            <ul class="evento-info-list" aria-label="Informações do evento">
                                <li class="evento-info-item">
                                    <span class="evento-info-icone" aria-hidden="true">
                                        <img src="../Imagens/info-categoria.svg" alt="" />
                                    </span>
                                    <span class="evento-info-texto"><span class="evento-info-label">Categoria:</span> <?= htmlspecialchars($ev['categoria']) ?></span>
                                </li>
                                <li class="evento-info-item">
                                    <span class="evento-info-icone" aria-hidden="true">
                                        <img src="../Imagens/info-modalidade.svg" alt="" />
                                    </span>
                                    <span class="evento-info-texto"><span class="evento-info-label">Modalidade:</span> <?= htmlspecialchars($ev['modalidade'] ?? '') ?></span>
                                </li>
                                <li class="evento-info-item">
                                    <span class="evento-info-icone" aria-hidden="true">
                                        <img src="../Imagens/info-data.svg" alt="" />
                                    </span>
                                    <span class="evento-info-texto"><span class="evento-info-label">Data:</span> <?= $dataFormatada ?></span>
                                </li>
                                <li class="evento-info-item">
                                    <span class="evento-info-icone" aria-hidden="true">
                                        <img src="../Imagens/info-local.svg" alt="" />
                                    </span>
                                    <span class="evento-info-texto"><span class="evento-info-label">Local:</span> <?= htmlspecialchars($ev['lugar']) ?></span>
                                </li>
                                <li class="evento-info-item">
                                    <span class="evento-info-icone" aria-hidden="true">
                                        <img src="../Imagens/info-certificado.svg" alt="" />
                                    </span>
                                    <span class="evento-info-texto"><span class="evento-info-label">Certificado:</span> <?= $certTexto ?></span>
                                </li>
                            </ul>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="grid-column:1/-1;text-align:center;padding:20px;">Nenhum evento cadastrado.</p>
            <?php endif; ?>
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

    <!-- Modal Compartilhar (mesmo do CartaodoEventoParticipante) -->
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

    <!-- Modais de confirmação inscrição/desinscrição -->
    <div class="modal-overlay" id="modalConfirmarInscricao">
      <div class="modal-cancelamento" onclick="event.stopPropagation()">
        <h2 class="modal-cancelamento-titulo">Deseja realmente se inscrever neste evento?</h2>
        <div class="modal-cancelamento-botoes">
          <button type="button" class="botao-cancelamento-cancelar" onclick="fecharModalConfirmarInscricao(); event.stopPropagation();">Cancelar</button>
          <button type="button" class="botao-cancelamento-continuar" onclick="confirmarInscricaoRapida(); event.stopPropagation();">Confirmar</button>
        </div>
      </div>
    </div>
    <div class="modal-overlay" id="modalConfirmarDesinscricao">
      <div class="modal-cancelamento" onclick="event.stopPropagation()">
        <h2 class="modal-cancelamento-titulo">Deseja realmente cancelar sua inscrição neste evento?</h2>
        <div class="modal-cancelamento-botoes">
          <button type="button" class="botao-cancelamento-cancelar" onclick="fecharModalConfirmarDesinscricao(); event.stopPropagation();">Não</button>
          <button type="button" class="botao-cancelamento-continuar" onclick="confirmarDesinscricaoRapida(); event.stopPropagation();">Sim, cancelar</button>
        </div>
      </div>
    </div>
    <div class="modal-overlay" id="modalInscricaoConfirmada">
      <div class="modal-cancelamento" onclick="event.stopPropagation()">
        <h2 class="modal-cancelamento-titulo">Inscrição realizada com sucesso!</h2>
        <div class="modal-cancelamento-botoes">
          <button type="button" class="botao-cancelamento-ok" onclick="fecharModalInscricaoConfirmada(); event.stopPropagation();">OK</button>
        </div>
      </div>
    </div>
    <div class="modal-overlay" id="modalDesinscricaoConfirmada">
      <div class="modal-cancelamento" onclick="event.stopPropagation()">
        <h2 class="modal-cancelamento-titulo">Inscrição cancelada com sucesso!</h2>
        <div class="modal-cancelamento-botoes">
          <button type="button" class="botao-cancelamento-ok" onclick="fecharModalDesinscricaoConfirmada(); event.stopPropagation();">OK</button>
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
          <textarea id="texto-mensagem-organizador" maxlength="500" placeholder="Escreva sua mensagem (máx. 500 caracteres)"></textarea>
          <div class="acoes">
            <button class="botao-secundario" type="button" onclick="fecharModalMensagem()">Cancelar</button>
            <button class="botao-primario" type="button" onclick="enviarMensagemOrganizador()">Enviar</button>
          </div>
        </div>
      </div>
    </div>

    <script>
      // Adaptação do código do CartaodoEventoParticipante para lista de cards
      let codEvento = null;
      let codEventoAcao = null; // para inscrição/desinscrição
      let btnInscreverAtual = null; // referência do botão clicado
      const inscricaoCache = new Map();

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
        const teclas = [32,33,34,35,36,37,38,39,40];
        if (teclas.includes(e.keyCode)) e.preventDefault();
      }

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
      document.getElementById('modal-compartilhar').onclick = function(e) { if (e.target === this) fecharModalCompartilhar(); };
      document.addEventListener('keydown', function(e){ if (e.key === 'Escape' || e.key === 'Esc') { fecharModalCompartilhar(); fecharModalMensagem(true); fecharTodosModaisConfirmacao(); } });

      // ====== Inscrição rápida ======
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
          btn.title = 'Inscrever-se rapidamente';
          btn.ariaLabel = 'Inscrever';
        }
      }

      async function verificarInscricao(cod) {
        if (inscricaoCache.has(cod)) return inscricaoCache.get(cod);
        try {
          const r = await fetch(`VerificarInscricao.php?cod_evento=${cod}`, { credentials: 'include' });
          const j = await r.json();
          const val = !!j.inscrito;
          inscricaoCache.set(cod, val);
          return val;
        } catch (e) {
          return false;
        }
      }

      function abrirModalConfirmarInscricao() {
        document.getElementById('modalConfirmarInscricao').classList.add('ativo');
        bloquearScroll();
      }
      function fecharModalConfirmarInscricao() {
        document.getElementById('modalConfirmarInscricao').classList.remove('ativo');
        desbloquearScroll();
      }
      function abrirModalConfirmarDesinscricao() {
        document.getElementById('modalConfirmarDesinscricao').classList.add('ativo');
        bloquearScroll();
      }
      function fecharModalConfirmarDesinscricao() {
        document.getElementById('modalConfirmarDesinscricao').classList.remove('ativo');
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
      function abrirModalDesinscricaoConfirmada() {
        document.getElementById('modalDesinscricaoConfirmada').classList.add('ativo');
        bloquearScroll();
      }
      function fecharModalDesinscricaoConfirmada() {
        document.getElementById('modalDesinscricaoConfirmada').classList.remove('ativo');
        desbloquearScroll();
      }
      function fecharTodosModaisConfirmacao() {
        document.querySelectorAll('.modal-overlay.ativo').forEach(m => m.classList.remove('ativo'));
        desbloquearScroll();
      }

      async function confirmarInscricaoRapida() {
        if (!codEventoAcao) { fecharModalConfirmarInscricao(); return; }
        try {
          const r = await fetch('InscreverEvento.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            credentials: 'include',
            body: new URLSearchParams({ cod_evento: codEventoAcao })
          });
          const j = await r.json();
          fecharModalConfirmarInscricao();
          if (j && j.sucesso) {
            inscricaoCache.set(codEventoAcao, true);
            atualizarIconeInscricao(btnInscreverAtual, true);
            abrirModalInscricaoConfirmada();
          } else {
            alert(j.mensagem || 'Erro ao realizar inscrição.');
          }
        } catch (e) {
          fecharModalConfirmarInscricao();
          alert('Erro ao realizar inscrição.');
        }
      }

      async function confirmarDesinscricaoRapida() {
        if (!codEventoAcao) { fecharModalConfirmarDesinscricao(); return; }
        try {
          const r = await fetch('DesinscreverEvento.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            credentials: 'include',
            body: new URLSearchParams({ cod_evento: codEventoAcao })
          });
          const j = await r.json();
          fecharModalConfirmarDesinscricao();
          if (j && j.sucesso) {
            inscricaoCache.set(codEventoAcao, false);
            atualizarIconeInscricao(btnInscreverAtual, false);
            abrirModalDesinscricaoConfirmada();
          } else {
            alert(j.mensagem || 'Erro ao cancelar inscrição.');
          }
        } catch (e) {
          fecharModalConfirmarDesinscricao();
          alert('Erro ao cancelar inscrição.');
        }
      }

      // ====== Modal de mensagem ao organizador ======
      let codEventoMensagem = null;
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

      // ====== Favoritos ======
      const favoritosSet = new Set();
      let favoritosDados = [];

      function atualizarIconeFavorito(btn, fav) {
        if (!btn) return;
        const img = btn.querySelector('img');
        if (!img) return;
        if (fav) {
          img.src = '../Imagens/Medalha_preenchida.svg';
          img.alt = 'Desfavoritar';
          btn.title = 'Remover dos favoritos';
          btn.setAttribute('data-favorito', '1');
        } else {
          img.src = '../Imagens/Medalha_linha.svg';
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
            // Atualiza ícones nos cards visíveis
            document.querySelectorAll('.CaixaDoEvento').forEach(card => {
              const cod = Number(card.getAttribute('data-cod-evento'));
              const btn = card.querySelector('.BotaoFavoritoCard');
              if (btn) atualizarIconeFavorito(btn, favoritosSet.has(cod));
            });
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
          const img = document.createElement('img');
          const caminho = '../' + (ev.imagem && ev.imagem !== '' ? ev.imagem.replace(/^\\/,'').replace(/^\//,'') : 'ImagensEventos/CEU-Logo.png');
          img.src = caminho;
          img.alt = ev.nome || 'Evento';
          const det = document.createElement('div'); det.className = 'detalhes';
          const t = document.createElement('div'); t.className = 'titulo'; t.textContent = ev.nome || 'Evento';
          const m = document.createElement('div'); m.className = 'meta'; m.textContent = `${ev.categoria || ''} • ${ev.modalidade || ''}`;
          det.appendChild(t); det.appendChild(m);
          a.appendChild(img); a.appendChild(det);
          frag.appendChild(a);
        });
        cont.appendChild(frag);
      }

      // Atualiza ícone do favorito ao passar o mouse no card (usa cache carregado)
      document.addEventListener('mouseenter', function(e){
        const card = e.target.closest('.CaixaDoEvento');
        if (!card) return;
        const cod = Number(card.getAttribute('data-cod-evento'));
        const btn = card.querySelector('.BotaoFavoritoCard');
        if (!btn || !cod) return;
        atualizarIconeFavorito(btn, favoritosSet.has(cod));
      }, true);

      // Clique: compartilhar/inscrever/mensagem já existem; adiciona favorito e abrir modal
      document.addEventListener('click', async function(e){
        // Toggle favorito
        const btnFav = e.target.closest('.BotaoFavoritoCard');
        if (btnFav) {
          e.preventDefault(); e.stopPropagation();
          const cod = Number(btnFav.getAttribute('data-cod')) || 0;
          if (!cod) return;
          try {
            const r = await fetch('ToggleFavorito.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              credentials: 'include',
              body: new URLSearchParams({ cod_evento: cod })
            });
            if (r.status === 401) { alert('Faça login para favoritar eventos.'); return; }
            const j = await r.json();
            if (j && j.sucesso) {
              if (j.favoritado) { favoritosSet.add(cod); }
              else { favoritosSet.delete(cod); }
              atualizarIconeFavorito(btnFav, j.favoritado);
              // Atualiza lista completa em memória (opcional: recarregar do servidor)
              // Aqui, atualizamos localmente removendo/adicionando
              if (j.favoritado) {
                // Se não existir em favoritosDados, não vamos buscar tudo agora; mantém até abrir modal e sincronizar
              } else {
                favoritosDados = favoritosDados.filter(f => Number(f.cod_evento) !== cod);
              }
            } else {
              alert(j.mensagem || 'Não foi possível atualizar favorito.');
            }
          } catch (err) {
            alert('Erro ao atualizar favorito.');
          }
          return;
        }

        // Abrir modal de favoritos (botão no topo)
        if (e.target.closest('#btn-abrir-favoritos')) {
          e.preventDefault(); e.stopPropagation();
          await carregarFavoritos();
          abrirModalFavoritos();
          return;
        }
      }, true);

      // Fechar modal de favoritos ao clicar fora
      const modalFav = document.getElementById('modal-favoritos');
      if (modalFav) {
        modalFav.onclick = function(e) {
          if (e.target === this) fecharModalFavoritos();
        };
      }

      // Carregar favoritos ao iniciar
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', carregarFavoritos);
      } else {
        setTimeout(carregarFavoritos, 50);
      }
    </script>
</body>
</html>