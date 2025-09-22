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

    // Buscar eventos (ajuste a ordem se quiser)
    $sql = "SELECT cod_evento, categoria, nome, inicio, certificado FROM evento ORDER BY inicio";
    $res = mysqli_query($conexao, $sql);
    ?>

    <div id="main-content">
        <div class="section-title-wrapper">
            <div class="barra-pesquisa-container">
                <div class="barra-pesquisa">
                    <div class="campo-pesquisa-wrapper">
                        <input class="campo-pesquisa" type="text" placeholder="Procurar (ainda não filtra)" />
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
                    $dataFormatada = date('d/m/y', strtotime($ev['inicio']));
                ?>
                    <a class="botao CaixaDoEvento"
                        style="text-decoration:none;color:inherit;display:block;"
                        href="ContainerPublico.php?pagina=evento&id=<?= (int)$ev['cod_evento'] ?>">
                        <div class="EventoTitulo"><?= htmlspecialchars($ev['nome']) ?></div>
                        <div class="EventoInfo">
                            Categoria: <?= htmlspecialchars($ev['categoria']) ?><br>
                            Data: <?= $dataFormatada ?><br>
                            Certificado: <?= ((int)$ev['certificado'] === 1 ? 'Sim' : 'Não') ?>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="grid-column:1/-1;text-align:center;padding:20px;">Nenhum evento cadastrado.</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>