<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventos Acontecendo</title>
    <link rel="stylesheet" href="../styleGlobal.css" />
</head>

<body>
    <?php
    include_once '../BancoDados/conexao.php';

    // Buscar eventos trazendo campos usados no filtro
    $sql = "SELECT cod_evento, categoria, nome, inicio, conclusao, duracao, certificado, lugar, modalidade, imagem FROM evento ORDER BY inicio";
    $res = mysqli_query($conexao, $sql);

    // Mapear texto e converter para string simples
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
                <div class="barra-pesquisa">
                    <div class="campo-pesquisa-wrapper">
                        <input class="campo-pesquisa" type="text" placeholder="Procurar eventos" />
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
                    $tipo = formatar($ev['categoria']); // liga com os checkboxes tipo_evento
                    $local = formatar($ev['lugar']);    // liga com localizacao
                    $modalidadeAttr = formatar($ev['modalidade'] ?? ''); // liga com modalidade
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
                    
                    // Preparar caminho da imagem
                    $imagem_evento = isset($ev['imagem']) && $ev['imagem'] !== '' ? $ev['imagem'] : 'ImagensEventos/CEU-Logo.png';
                    $caminho_imagem = '../' . ltrim($imagem_evento, "/\\");
                ?>
                    <a class="botao CaixaDoEvento"
                        style="text-decoration:none;color:inherit;display:block;"
                        href="ContainerPublico.php?pagina=evento&id=<?= (int)$ev['cod_evento'] ?>"
                        data-tipo="<?= htmlspecialchars($tipo) ?>"
                        data-localizacao="<?= htmlspecialchars($local) ?>"
                        data-duracao="<?= htmlspecialchars($duracaoFaixa) ?>"
                        data-certificado="<?= $cert ?>"
                        data-data="<?= $dataInicioISO ?>"
                        data-modalidade="<?= htmlspecialchars($modalidadeAttr) ?>"
                    >
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
                                    <span class="evento-info-texto"><span class="evento-info-label">Certificado:</span> <?= ($cert === 'sim' ? 'Sim' : 'Não') ?></span>
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
    
    <!-- Script de Responsividade Mobile -->
    <script src="../PaginasGlobais/ResponsividadeMobile.js"></script>
</body>

</html>