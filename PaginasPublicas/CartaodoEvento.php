<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cartão do Evento</title>
    <link rel="stylesheet" href="../styleGlobal.css" />
    <link rel="stylesheet" href="../styleGlobalMobile.css" media="(max-width: 767px)" />
    <?php
    // Sessão e banco
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    include_once('../BancoDados/conexao.php');

    $id_evento = isset($_GET['id']) ? (int)$_GET['id'] : 1;
    $sql = "SELECT e.*, u.Nome as nome_organizador 
            FROM evento e 
            LEFT JOIN organiza o ON e.cod_evento = o.cod_evento 
            LEFT JOIN usuario u ON o.CPF = u.CPF 
            WHERE e.cod_evento = $id_evento";
    $resultado = mysqli_query($conexao, $sql);

    // Verificar se encontrou o evento
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $evento = mysqli_fetch_assoc($resultado);

        $data_inicio = date('d/m/y', strtotime($evento['inicio']));
        $data_fim = date('d/m/y', strtotime($evento['conclusao']));
        $hora_inicio = date('H:i', strtotime($evento['inicio']));
        $hora_fim = date('H:i', strtotime($evento['conclusao']));

        // Datas de inscrição
        $data_inicio_inscricao = '-';
        $data_fim_inscricao = '-';
        $hora_inicio_inscricao = '-';
        $hora_fim_inscricao = '-';

        if (!empty($evento['inicio_inscricao'])) {
            $data_inicio_inscricao = date('d/m/y', strtotime($evento['inicio_inscricao']));
            $hora_inicio_inscricao = date('H:i', strtotime($evento['inicio_inscricao']));
        }
        if (!empty($evento['fim_inscricao'])) {
            $data_fim_inscricao = date('d/m/y', strtotime($evento['fim_inscricao']));
            $hora_fim_inscricao = date('H:i', strtotime($evento['fim_inscricao']));
        }

        $nome_organizador = isset($evento['nome_organizador']) && $evento['nome_organizador'] !== '' ? $evento['nome_organizador'] : 'Não informado';
    } else {
        // Se não encontrou o evento, usar dados padrão
        $evento = array(
            'nome' => 'Evento não encontrado',
            'lugar' => 'Local não informado',
            'descricao' => 'Descrição não disponível',
            'categoria' => 'Não informado',
            'publico_alvo' => 'Não informado',
            'certificado' => 0,
            'modalidade' => 'Presencial',
            'imagem' => 'ImagensEventos/CEU-ImagemEvento.png'
        );
        $data_inicio = '00/00/00';
        $data_fim = '00/00/00';
        $hora_inicio = '00:00';
        $hora_fim = '00:00';
        $data_inicio_inscricao = '-';
        $data_fim_inscricao = '-';
        $hora_inicio_inscricao = '-';
        $hora_fim_inscricao = '-';
        $nome_organizador = 'Não informado';
    }

    $certificado = (isset($evento['certificado']) && (int)$evento['certificado'] === 1) ? 'Sim' : 'Não';
    $modalidade = isset($evento['modalidade']) && $evento['modalidade'] !== '' ? $evento['modalidade'] : 'Presencial';

    // Ajustar caminho da imagem relativo a esta pasta - usar CEU-Logo.png como padrão
    $imagem_rel = (isset($evento['imagem']) && $evento['imagem'] !== '' && $evento['imagem'] !== null)
        ? $evento['imagem']
        : 'ImagensEventos/CEU-ImagemEvento.png';
    $imagem_src = '../' . ltrim($imagem_rel, "/\\");
    ?>

    <style>
        .cartao-evento {
            background-color: var(--caixas);
            border-radius: 2rem;
            box-shadow: 0 0.15rem 0.75rem 0 rgba(0, 0, 0, 0.4);
            padding: 2rem;
            width: 100%;
            max-width: 60rem;
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            grid-template-rows: repeat(8, auto);
            gap: 1rem;
            margin: 1rem auto 1rem auto;
        }

        /* Campos seguem grid original adaptado */
        .cartao-evento>div {
            background: none;
            border: none;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: stretch;
            font-size: 1rem;
        }

        .grupo-campo label {
            font-weight: 700;
            font-size: 1.05rem;
            line-height: 1.2;
            margin-bottom: 0.35rem;
        }

        .caixa-valor {
            background-color: var(--branco);
            color: var(--preto, #000);
            border-radius: 2rem;
            padding: 0.55rem 0.9rem;
            text-align: center;
            font-size: 0.95rem;
            font-weight: 700;
            min-height: 2.1rem;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 0.15rem 0.75rem 0 rgba(0, 0, 0, 0.35);
        }

        .caixa-descricao {
            min-height: 14rem;
            padding: 1rem;
            text-align: left;
            line-height: 1.4;
            font-weight: 500;
            overflow-y: auto;
            resize: vertical;
            word-break: break-word;
            white-space: pre-line;
            max-width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            padding-top: 1em;
        }

        .Nome {
            grid-column: span 4 / span 4;
        }

        .Organizador {
            grid-column: span 4 / span 4;
            grid-column-start: 5;
        }

        .Local {
            grid-column: span 8 / span 8;
            grid-row-start: 2;
        }

        .DataHorarioInicio {
            grid-column: span 4 / span 4;
            grid-row-start: 3;
        }

        .DataHorarioFim {
            grid-column: span 4 / span 4;
            grid-column-start: 5;
            grid-row-start: 3;
        }

        .DataHorarioInscricaoInicio {
            grid-column: span 4 / span 4;
            grid-row-start: 4;
        }

        .DataHorarioInscricaoFim {
            grid-column: span 4 / span 4;
            grid-column-start: 5;
            grid-row-start: 4;
        }

        /* Container para campos de data e horário */
        .campo-data-horario {
            display: flex;
            gap: 0.5rem;
            align-items: stretch;
        }

        .campo-data-horario .caixa-valor {
            flex: 1;
        }

        .campo-data-horario .caixa-valor:first-child {
            flex: 1.2;
        }

        .campo-data-horario .caixa-valor:last-child {
            flex: 0.8;
        }

        .PublicoAlvo {
            grid-column: span 2 / span 2;
            grid-row-start: 5;
        }

        .Categoria {
            grid-column: span 2 / span 2;
            grid-column-start: 3;
            grid-row-start: 5;
        }

        .Modalidade {
            grid-column: span 2 / span 2;
            grid-column-start: 5;
            grid-row-start: 5;
        }

        .Certificado {
            grid-column: span 2 / span 2;
            grid-column-start: 7;
            grid-row-start: 5;
        }

        .Imagem {
            grid-column: span 4 / span 4;
            grid-row: span 3 / span 3;
            grid-row-start: 6;
            display: flex;
            justify-content: center;
            align-items: center;
            max-height: 16rem;
            min-height: 16rem;
            width: 100%;
            min-width: 0;
        }

        .Descricao {
            grid-column: span 4 / span 4;
            grid-row: span 3 / span 3;
            grid-column-start: 5;
            grid-row-start: 6;
        }

        .BotaoVoltar {
            grid-column: span 2 / span 2;
            grid-row-start: 9;
        }

        .BotaoCompartilhar {
            grid-column: span 2 / span 2;
            grid-column-start: 3;
            grid-row-start: 9;
        }

        .MensagemLogin {
            grid-column: span 3 / span 3;
            grid-column-start: 6;
            grid-row-start: 9;
            align-self: center;
        }

        /* Imagem */
        .campo-imagem {
            background: var(--branco, #fff);
            border-radius: 1.5rem;
            box-shadow: 0 0.15rem 0.75rem 0 rgba(0, 0, 0, 0.25);
            overflow: hidden;
            padding: 0;
            width: 100%;
            min-width: 0;
            height: 16rem;
            max-height: 16rem;
            min-height: 16rem;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .campo-imagem img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            margin: 0;
            padding: 0;
        }

        .botao {
            background-color: var(--botao);
            color: var(--branco, #fff);
            border-radius: 0.35rem;
            padding: 0.75rem 1.5rem;
            font-weight: 700;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            box-shadow: 0 0.15rem 0.55rem 0 rgba(0, 0, 0, 0.25);
            transition: background .25s, transform .15s;
            width: 100%;
        }

        .carrossel-imagens {
            position: relative;
            width: 100%;
            height: 16rem;
            max-height: 16rem;
            min-height: 16rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .carrossel-imagens img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 1.5rem;
            box-shadow: 0 0.15rem 0.75rem 0 rgba(0, 0, 0, 0.25);
        }

        .carrossel-btn {
            background: none;
            color: var(--azul-escuro);
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            width: 2.5rem;
            height: 2.5rem;
            font-size: 2rem;
            cursor: pointer;
            opacity: 0.8;
            z-index: 2;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .carrossel-btn:hover {
            color: var(--botao);
        }

        .carrossel-anterior {
            left: 0.5rem;
        }

        .carrossel-proxima {
            right: 0.5rem;
        }

        /* CSS do Modal de Solicitação */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }

        .modal-overlay.ativo {
            display: flex;
        }

        .modal-editar {
            background-color: var(--caixas);
            border-radius: 1rem;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--azul-escuro);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--texto);
        }

        .btn-fechar-modal {
            background: none;
            border: none;
            font-size: 2rem;
            color: var(--texto);
            cursor: pointer;
            padding: 0;
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-fechar-modal:hover {
            opacity: 0.7;
        }

        .modal-editar form {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--texto);
        }

        .modal-footer {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding-top: 1rem;
        }

        .btn-modal {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-cancelar {
            background-color: #dc3545;
            color: var(--branco);
        }

        .btn-cancelar:hover {
            background-color: #c82333;
        }

        .btn-salvar {
            background-color: #28a745;
            color: var(--branco);
        }

        .btn-salvar:hover {
            background-color: #218838;
        }

        .modal-imagem {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.85);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .modal-imagem-btn-fechar {
            position: absolute;
            top: 2rem;
            right: 2rem;
            background: none;
            border: none;
            font-size: 2.5rem;
            color: var(--branco);
            cursor: pointer;
            z-index: 10001;
        }

        .modal-imagem-btn-anterior {
            left: 2rem;
            top: 50%;
            position: absolute;
        }

        .modal-imagem-btn-proxima {
            right: 2rem;
            top: 50%;
            position: absolute;
        }

        .modal-imagem-img {
            max-width: 90vw;
            max-height: 90vh;
            border-radius: 2rem;
            box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.5);
        }

        /* Modal de Compartilhar */
        body.modal-aberto {
            overflow: hidden !important;
        }

        body.modal-aberto #main-content {
            overflow: hidden !important;
        }

        /* Modal de Compartilhar */
        .modal-compartilhar {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
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
            background: #25D366;
        }

        .icone-instagram {
            background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
        }

        .icone-email {
            background: #EA4335;
        }

        .icone-x {
            background: #000000;
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
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
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
            background: rgba(66, 135, 245, 0.1);
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
    </style>
</head>

<body>
    <div id="main-content">
        <div class="cartao-evento">
            <div class="Nome grupo-campo">
                <label>Nome:</label>
                <div class="caixa-valor"><?php echo htmlspecialchars($evento['nome']); ?></div>
            </div>
            <div class="Organizador grupo-campo">
                <label>Organizado por:</label>
                <div class="caixa-valor"><?php echo htmlspecialchars($nome_organizador); ?></div>
            </div>
            <div class="Local grupo-campo">
                <label>Local:</label>
                <div class="caixa-valor"><?php echo htmlspecialchars($evento['lugar']); ?></div>
            </div>
            <div class="DataHorarioInicio grupo-campo">
                <label>Data e Horário de Início do Evento:</label>
                <div class="campo-data-horario">
                    <div class="caixa-valor"><?php echo $data_inicio; ?></div>
                    <div class="caixa-valor"><?php echo $hora_inicio; ?></div>
                </div>
            </div>
            <div class="DataHorarioFim grupo-campo">
                <label>Data e Horário de Fim do Evento:</label>
                <div class="campo-data-horario">
                    <div class="caixa-valor"><?php echo $data_fim; ?></div>
                    <div class="caixa-valor"><?php echo $hora_fim; ?></div>
                </div>
            </div>
            <div class="DataHorarioInscricaoInicio grupo-campo">
                <label>Início das Inscrições:</label>
                <div class="campo-data-horario">
                    <div class="caixa-valor"><?php echo htmlspecialchars($data_inicio_inscricao); ?></div>
                    <div class="caixa-valor"><?php echo htmlspecialchars($hora_inicio_inscricao); ?></div>
                </div>
            </div>
            <div class="DataHorarioInscricaoFim grupo-campo">
                <label>Fim das Inscrições:</label>
                <div class="campo-data-horario">
                    <div class="caixa-valor"><?php echo htmlspecialchars($data_fim_inscricao); ?></div>
                    <div class="caixa-valor"><?php echo htmlspecialchars($hora_fim_inscricao); ?></div>
                </div>
            </div>
            <div class="PublicoAlvo grupo-campo">
                <label>Público Alvo:</label>
                <div class="caixa-valor"><?php echo htmlspecialchars($evento['publico_alvo'] ?? 'Não informado'); ?></div>
            </div>
            <div class="Categoria grupo-campo">
                <label>Categoria:</label>
                <div class="caixa-valor"><?php echo htmlspecialchars($evento['categoria'] ?? ''); ?></div>
            </div>
            <div class="Modalidade grupo-campo">
                <label>Modalidade:</label>
                <div class="caixa-valor"><?php echo htmlspecialchars($modalidade); ?></div>
            </div>
            <div class="Certificado grupo-campo">
                <label>Certificado:</label>
                <div class="caixa-valor"><?php echo $certificado; ?></div>
            </div>
            <div class="Imagem campo-imagem">
                <div class="carrossel-imagens">
                    <button class="carrossel-btn carrossel-anterior" onclick="mudarImagem(-1)">â®œ</button>
                    <img id="imagem-carrossel" src="<?php echo htmlspecialchars($imagem_src); ?>" alt="Imagem do evento">
                    <button class="carrossel-btn carrossel-proxima" onclick="mudarImagem(1)">â®ž</button>
                </div>
            </div>
            <div class="Descricao grupo-campo">
                <label>Descrição:</label>
                <div class="caixa-valor caixa-descricao"><?php echo htmlspecialchars($evento['descricao']); ?></div>
            </div>
            <div class="BotaoVoltar">
                <button class="botao" onclick="history.back()">Voltar</button>
            </div>
            <div class="BotaoCompartilhar">
                <button class="botao" onclick="abrirModalCompartilhar()">Compartilhar</button>
            </div>
            <div class="MensagemLogin">
                <?php if (isset($_SESSION['cpf']) && !empty($_SESSION['cpf'])): ?>
                    <button id="btn-ser-colaborador" class="botao">Ser Colaborador</button>
                <?php else: ?>
                    <h4 class="MensagemLogin">Acesse uma conta para interagir com o evento!</h4>
                <?php endif; ?>
            </div>
        </div>

        <div id="modal-imagem" class="modal-imagem">
            <button onclick="fecharModalImagem()" class="modal-imagem-btn-fechar">&times;</button>
            <button class="carrossel-btn carrossel-anterior modal-imagem-btn-anterior" onclick="mudarImagemModal(-1)">â®œ</button>
            <img id="imagem-ampliada" src="" alt="Imagem ampliada" class="modal-imagem-img">
            <button class="carrossel-btn carrossel-proxima modal-imagem-btn-proxima" onclick="mudarImagemModal(1)">â®ž</button>
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
                    <strong>â„¹ï¸ Informação:</strong> Compartilhe este evento com seus amigos e familiares!
                </div>
            </div>
        </div>
    </div>

    <script>
        let imagens = [];
        let indiceAtual = 0;
        const codEvento = <?php echo $id_evento; ?>;

        // Carrega as imagens do evento
        async function carregarImagensEvento() {
            try {
                const response = await fetch(`BuscarImagensEvento.php?cod_evento=${codEvento}`);
                const dados = await response.json();

                if (dados.sucesso && dados.imagens && dados.imagens.length > 0) {
                    imagens = dados.imagens.map(img => '../' + img.caminho);
                } else {
                    // Fallback para imagem padrão
                    imagens = ['<?php echo htmlspecialchars($imagem_src); ?>'];
                }

                // Atualiza a imagem inicial
                if (imagens.length > 0) {
                    document.getElementById('imagem-carrossel').src = imagens[0];
                }

                atualizarVisibilidadeSetas();
            } catch (erro) {
                console.error('Erro ao carregar imagens:', erro);
                imagens = ['<?php echo htmlspecialchars($imagem_src); ?>'];
                atualizarVisibilidadeSetas();
            }
        }

        // Mostrar setas apenas quando houver mais de uma imagem
        function atualizarVisibilidadeSetas() {
            const multiple = imagens.length > 1;
            const setDisplay = (sel) => {
                document.querySelectorAll(sel).forEach(el => {
                    el.style.display = multiple ? '' : 'none';
                });
            };
            setDisplay('.carrossel-anterior');
            setDisplay('.carrossel-proxima');
            setDisplay('.modal-imagem-btn-anterior');
            setDisplay('.modal-imagem-btn-proxima');
        }

        function mudarImagem(direcao) {
            if (imagens.length === 0) return;
            indiceAtual = (indiceAtual + direcao + imagens.length) % imagens.length;
            document.getElementById('imagem-carrossel').src = imagens[indiceAtual];
        }

        function mudarImagemModal(direcao) {
            if (imagens.length === 0) return;
            indiceAtual = (indiceAtual + direcao + imagens.length) % imagens.length;
            document.getElementById('imagem-ampliada').src = imagens[indiceAtual];
        }

        document.getElementById('imagem-carrossel').onclick = function(e) {
            e.stopPropagation();
            if (imagens.length > 0) {
                document.getElementById('imagem-ampliada').src = imagens[indiceAtual];
                document.getElementById('modal-imagem').style.display = 'flex';
            }
        };

        function fecharModalImagem() {
            document.getElementById('modal-imagem').style.display = 'none';
        }

        // Fecha o modal ao clicar fora da imagem
        document.getElementById('modal-imagem').onclick = function(e) {
            if (e.target === this) {
                fecharModalImagem();
            }
        };

        // Fecha o modal ao pressionar ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' || e.key === 'Esc') {
                fecharModalImagem();
            }
        });

        // Carrega as imagens quando a página é carregada
        carregarImagensEvento();

        // Botão Ser Colaborador (se logado)
        (function() {
            const btn = document.getElementById('btn-ser-colaborador');
            if (!btn) return;
            btn.addEventListener('click', function() {
                abrirModalSolicitacao();
            });
        })();

        // Modal de Solicitação de Colaboração
        function abrirModalSolicitacao() {
            document.getElementById('modalSolicitarColaboracao').classList.add('ativo');
        }

        function fecharModalSolicitacao() {
            document.getElementById('modalSolicitarColaboracao').classList.remove('ativo');
            document.getElementById('mensagem-solicitacao').value = '';
        }

        async function enviarSolicitacaoColaboracao(event) {
            event.preventDefault();

            const mensagem = document.getElementById('mensagem-solicitacao').value.trim();

            if (!confirm('Confirma o envio da solicitação para ser colaborador deste evento?')) {
                return;
            }

            try {
                const form = new FormData();
                form.append('cod_evento', String(codEvento));
                form.append('mensagem', mensagem);

                const resp = await fetch('../PaginasOrganizador/SolicitarSerColaborador.php', {
                    method: 'POST',
                    body: form
                });
                const data = await resp.json();

                if (!data.sucesso) {
                    let msg = 'Não foi possível enviar a solicitação.';
                    if (data.erro === 'ja_organizador') msg = 'Você já é organizador deste evento.';
                    if (data.erro === 'ja_colaborador') msg = 'Você já é colaborador deste evento.';
                    alert(msg);
                    return;
                }

                alert(data.mensagem || 'Solicitação enviada com sucesso!');
                fecharModalSolicitacao();
            } catch (e) {
                console.error('Falha ao solicitar colaboração', e);
                alert('Falha ao enviar solicitação.');
            }
        }

        // Funções para bloqueio de scroll
        function bloquearScroll() {
            document.body.classList.add('modal-aberto');
            document.addEventListener('wheel', prevenirScroll, {
                passive: false
            });
            document.addEventListener('touchmove', prevenirScroll, {
                passive: false
            });
            document.addEventListener('keydown', prevenirScrollTeclado, false);
        }

        function desbloquearScroll() {
            document.body.classList.remove('modal-aberto');
            document.removeEventListener('wheel', prevenirScroll);
            document.removeEventListener('touchmove', prevenirScroll);
            document.removeEventListener('keydown', prevenirScrollTeclado);
        }

        function prevenirScroll(e) {
            if (document.body.classList.contains('modal-aberto')) {
                e.preventDefault();
            }
        }

        function prevenirScrollTeclado(e) {
            if (!document.body.classList.contains('modal-aberto')) return;
            const teclas = [32, 33, 34, 35, 36, 37, 38, 39, 40];
            if (teclas.includes(e.keyCode)) {
                e.preventDefault();
            }
        }

        // Funções de compartilhamento
        const linkEvento = `${window.location.origin}/CEU/PaginasPublicas/ContainerPublico.php?pagina=evento&cod_evento=${codEvento}`;

        function abrirModalCompartilhar() {
            const modal = document.getElementById('modal-compartilhar');
            modal.classList.add('ativo');
            document.getElementById('link-inscricao').value = linkEvento;
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
            }).catch(() => {
                input.select();
                document.execCommand('copy');
                alert('Link copiado!');
            });
        }

        function compartilharWhatsApp() {
            const texto = `Confira este evento: ${linkEvento}`;
            window.open(`https://wa.me/?text=${encodeURIComponent(texto)}`, '_blank');
        }

        function compartilharInstagram() {
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
            const assunto = 'Confira este evento!';
            const corpo = `Olá! Gostaria de compartilhar este evento com você: ${linkEvento}`;
            window.location.href = `mailto:?subject=${encodeURIComponent(assunto)}&body=${encodeURIComponent(corpo)}`;
        }

        function compartilharX() {
            const texto = `Confira este evento!`;
            window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(texto)}&url=${encodeURIComponent(linkEvento)}`, '_blank');
        }

        // Fecha o modal ao clicar fora do conteúdo
        document.getElementById('modal-compartilhar').onclick = function(e) {
            if (e.target === this) {
                fecharModalCompartilhar();
            }
        };
    </script>

    <!-- Modal Solicitar Ser Colaborador -->
    <div class="modal-overlay" id="modalSolicitarColaboracao">
        <div class="modal-editar" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h2>Solicitar Ser Colaborador</h2>
                <button class="btn-fechar-modal" onclick="fecharModalSolicitacao(); event.stopPropagation();">&times;</button>
            </div>
            <form onsubmit="enviarSolicitacaoColaboracao(event); event.stopPropagation();">
                <div class="form-group">
                    <label for="mensagem-solicitacao">Mensagem para o Organizador (opcional)</label>
                    <textarea
                        id="mensagem-solicitacao"
                        rows="5"
                        maxlength="500"
                        placeholder="Escreva uma mensagem explicando por que deseja colaborar neste evento..."
                        style="width: 100%; padding: 12px; border: 1px solid var(--azul-escuro); border-radius: 8px; font-size: 15px; font-family: inherit; resize: vertical;"></textarea>
                    <small style="color: #666;">Máximo 500 caracteres</small>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-modal btn-cancelar" onclick="fecharModalSolicitacao(); event.stopPropagation();">Cancelar</button>
                    <button type="submit" class="btn-modal btn-salvar" onclick="event.stopPropagation();">Enviar Solicitação</button>
                </div>
            </form>
        </div>
    </div>

</body>

</html>

