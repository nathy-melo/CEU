<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cartão do Evento</title>
    <link rel="stylesheet" href="../styleGlobal.css" />
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
            'imagem' => 'ImagensEventos/CEU-Logo.png'
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
        : 'ImagensEventos/CEU-Logo.png';
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
                <label>Início Inscrições:</label>
                <div class="campo-data-horario">
                    <div class="caixa-valor"><?php echo htmlspecialchars($data_inicio_inscricao); ?></div>
                    <div class="caixa-valor"><?php echo htmlspecialchars($hora_inicio_inscricao); ?></div>
                </div>
            </div>
            <div class="DataHorarioInscricaoFim grupo-campo">
                <label>Fim Inscrições:</label>
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
                    <button class="carrossel-btn carrossel-anterior" onclick="mudarImagem(-1)">⮜</button>
                    <img id="imagem-carrossel" src="<?php echo htmlspecialchars($imagem_src); ?>" alt="Imagem do evento">
                    <button class="carrossel-btn carrossel-proxima" onclick="mudarImagem(1)">⮞</button>
                </div>
            </div>
            <div class="Descricao grupo-campo">
                <label>Descrição:</label>
                <div class="caixa-valor caixa-descricao"><?php echo htmlspecialchars($evento['descricao']); ?></div>
            </div>
            <div class="BotaoVoltar">
                <button class="botao" onclick="history.back()">Voltar</button>
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
            <button class="carrossel-btn carrossel-anterior modal-imagem-btn-anterior" onclick="mudarImagemModal(-1)">⮜</button>
            <img id="imagem-ampliada" src="" alt="Imagem ampliada" class="modal-imagem-img">
            <button class="carrossel-btn carrossel-proxima modal-imagem-btn-proxima" onclick="mudarImagemModal(1)">⮞</button>
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