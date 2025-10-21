<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Meus Eventos</title>
    <link rel="stylesheet" href="../styleGlobal.css" />
</head>
<style>
    body {
        align-items: flex-start;
        padding-top: 1rem;
    }
</style>

<body>
    <div id="main-content">
        <div class="section-title-wrapper">
            <div class="barra-pesquisa-container">
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
    <script src="../PaginasGlobais/Filtro.js"></script>
    <script src="MeusEventosParticipante.js"></script>
</body>

</html>
