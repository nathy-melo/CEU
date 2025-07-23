<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventos Acontecendo</title>
    <link rel="stylesheet" href="../styleGlobal.css" />
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #d1eaff;
            font-family: "Inter", sans-serif;
            min-height: 100vh;
            width: 100%;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
        }


        .barra-pesquisa-container {
            max-width: clamp(310px, 86%, 1150px);
            box-sizing: border-box;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }

        .barra-pesquisa {
            display: flex;
            align-items: center;
            width: 100%;
        }

        .campo-pesquisa-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            width: clamp(200px, 39vw, 580px);
            height: clamp(30px, 4vw, 48px);
        }

        .campo-pesquisa {
            width: 100%;
            height: 100%;
            background-color: #ffffff;
            border-radius: clamp(16px, 3vw, 26px);
            padding: 0 clamp(10px, 2vw, 15px);
            display: flex;
            align-items: center;
            font-size: clamp(12px, 1.5vw, 16px);
            color: #000000;
            box-sizing: border-box;
            box-shadow: 0px 5px 21px 0px rgba(0, 0, 0, 0.6);
            font-weight: bold;
        }

        .botao-pesquisa {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: clamp(30px, 5vw, 77px);
            height: clamp(30px, 5vw, 77px);
            background-color: transparent;
            box-shadow: 0px 5px 21px 0px rgba(0, 0, 0, 0.45);
            border-radius: 50%;
            border: none;
            padding: 0;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1;
        }

        .icone-pesquisa {
            width: 100%;
            height: 100%;
            background-color: #0a1449;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .icone-pesquisa img {
            width: 70%;
            height: 70%;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }

        .botao-filtrar {
            width: clamp(80px, 10vw, 145px);
            height: clamp(30px, 4vw, 48px);
            background-color: #ffffff;
            border-radius: clamp(11px, 4vw, 21px);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: clamp(10px, 2vw, 20px);
            font-size: clamp(12px, 1.5vw, 16px);
            color: #000000;
            box-sizing: border-box;
            box-shadow: 0px 5px 21px 0px rgb(0, 0, 0);
        }

        .botao-filtrar span {
            font-weight: bold;
        }

        .botao-filtrar img {
            width: 20%;
            height: auto;
            margin-left: 8px;
        }

        .section-title-wrapper {
            width: 100%;
            padding: 20px 0;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .div-section-title {
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: var(--caixas);
            border-radius: 10px;
            padding: 5px 5px 5px 5px;
            margin-top: 20px;
        }

        .section-title {
            font-size: 1.9rem;
            font-weight: 700;
            color: #ffffff;
            text-shadow: 0px 4px 10px rgb(0, 0, 0);
        }

        .container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 23px;
            max-width: 1200px;
            padding: 0 40px 40px;
            margin: 0 auto;
        }

        .CaixaDoEvento {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 2px 2px 15px rgb(0, 0, 0);
        }

        .EventoTitulo {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 11px;
            color: #0a1449;
        }

        .EventoInfo {
            color: #333;
            line-height: 1.6;
        }

        .BotaoAdicionarEvento {
            width: 100%;
            height: 100%;
            min-height: 90px;
            background: #e6f0fa;
            border: 2px dashed #0a1449;
            border-radius: 10px;
            font-size: 2.5rem;
            color: #0a1449;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s;
            user-select: none;
        }

        .BotaoAdicionarEvento:hover {
            background: #d1eaff;
        }
    </style>
</head>

<body>
    <div id="main-content">
        <div class="section-title-wrapper">
            <div class="barra-pesquisa-container">
                <div class="barra-pesquisa">
                    <div class="campo-pesquisa-wrapper">
                        <div class="campo-pesquisa">Procurar eventos</div>
                        <button class="botao-pesquisa" aria-label="Procurar">
                            <div class="icone-pesquisa">
                                <img src="../Imagens/lupa.png" alt="Lupa">
                            </div>
                        </button>
                    </div>
                    <button class="botao-filtrar">
                        <span>Filtrar</span>
                        <img src="../Imagens/filtro.png" alt="Filtro">
                    </button>
                </div>
            </div>
            <div class="div-section-title">
            <h1 class="section-title">Meus Eventos</h1>
            </div>
        </div>

        <div class="container">
            
            <div class="CaixaDoEvento">
                <button class="BotaoAdicionarEvento">+</button>
            </div>
            
            <div class="CaixaDoEvento">
                <div class="EventoTitulo">Evento 1</div>
                <div class="EventoInfo">Em andamento<br>Data: 07/03/25<br>Certificado: Sim</div>
            </div>
            <div class="CaixaDoEvento">
                <div class="EventoTitulo">Evento 2</div>
                <div class="EventoInfo">Em andamento<br>Data: 17/03/25<br>Certificado: Sim</div>
            </div>
            <div class="CaixaDoEvento">
                <div class="EventoTitulo">Evento 3</div>
                <div class="EventoInfo">Em andamento<br>Data: 20/03/25<br>Certificado: Não</div>
            </div>
            <div class="CaixaDoEvento">
                <div class="EventoTitulo">Evento 4</div>
                <div class="EventoInfo">Em andamento<br>Data: 15/02/25<br>Certificado: Não</div>
            </div>
            <div class="CaixaDoEvento">
                <div class="EventoTitulo">Evento 5</div>
                <div class="EventoInfo">Em andamento<br>Data: 20/02/25<br>Certificado: Sim</div>
            </div>
            <div class="CaixaDoEvento">
                <div class="EventoTitulo">Evento 6</div>
                <div class="EventoInfo">Em andamento<br>Data: 30/02/25<br>Certificado: Sim</div>
            </div>
            <div class="CaixaDoEvento">
                <div class="EventoTitulo">Evento 7</div>
                <div class="EventoInfo">Em andamento<br>Data: 30/02/25<br>Certificado: Sim</div>
            </div>
            <div class="CaixaDoEvento">
                <div class="EventoTitulo">Evento 8</div>
                <div class="EventoInfo">Em andamento<br>Data: 30/02/25<br>Certificado: Sim</div>
            </div>
        </div>
    </div>
</body>

</html>