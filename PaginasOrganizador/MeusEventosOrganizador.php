<?php
// Sessão já é iniciada pelo ContainerOrganizador.php
include_once '../BancoDados/conexao.php';

// Obter CPF do usuário da sessão (já verificado pelo Container)
$cpfUsuario = $_SESSION['cpf'] ?? '';

// Função para formatar evento (mesma do GerenciadorEventos.php)
function formatarEvento($dadosEvento) {
    $dataHoraInicio = new DateTime($dadosEvento['inicio']);
    $dataHoraConclusao = new DateTime($dadosEvento['conclusao']);
    $dataHoraAtual = new DateTime();
    
    // Determinar status do evento
    if ($dataHoraAtual < $dataHoraInicio) {
        $statusEvento = 'Previsto';
    } elseif ($dataHoraAtual >= $dataHoraInicio && $dataHoraAtual <= $dataHoraConclusao) {
        $statusEvento = 'Em andamento';
    } else {
        $statusEvento = 'Finalizado';
    }
    
    // Formatar certificado
    $textoTemCertificado = $dadosEvento['certificado'] == 1 ? 'Sim' : 'Não';
    
    return [
        'cod_evento' => $dadosEvento['cod_evento'],
        'categoria' => $dadosEvento['categoria'],
        'nome' => $dadosEvento['nome'],
        'lugar' => $dadosEvento['lugar'],
        'descricao' => $dadosEvento['descricao'],
        'publico_alvo' => $dadosEvento['publico_alvo'],
        'inicio' => $dadosEvento['inicio'],
        'conclusao' => $dadosEvento['conclusao'],
        'duracao' => $dadosEvento['duracao'],
        'certificado' => $textoTemCertificado,
        'certificado_numerico' => $dadosEvento['certificado'],
        'modalidade' => $dadosEvento['modalidade'],
        'imagem' => $dadosEvento['imagem'],
        'status' => $statusEvento,
        'data_formatada' => $dataHoraInicio->format('d/m/y'),
        'horario_inicio' => $dataHoraInicio->format('H:i'),
        'horario_fim' => $dataHoraConclusao->format('H:i')
    ];
}

// Função para garantir esquema de colaboradores
function garantirEsquemaColaboradores($conexao) {
    $sql = "CREATE TABLE IF NOT EXISTS colaboradores_evento (
        CPF VARCHAR(11) NOT NULL,
        cod_evento INT NOT NULL,
        PRIMARY KEY (CPF, cod_evento),
        FOREIGN KEY (cod_evento) REFERENCES evento(cod_evento) ON DELETE CASCADE
    )";
    mysqli_query($conexao, $sql);
}

// Inicializar arrays de eventos
$eventosOrganizador = [];
$eventosColaboracao = [];

// Se houver CPF, buscar eventos
if (empty($cpfUsuario)) {
    $eventosOrganizador = [];
    $eventosColaboracao = [];
} else {
    // Buscar eventos do organizador
    $sqlOrganizador = "SELECT 
                evento.cod_evento,
                evento.categoria,
                evento.nome,
                evento.lugar,
                evento.descricao,
                evento.publico_alvo,
                evento.inicio,
                evento.conclusao,
                evento.duracao,
                evento.certificado,
                evento.modalidade,
                evento.imagem
            FROM evento
            INNER JOIN organiza ON evento.cod_evento = organiza.cod_evento
            WHERE organiza.CPF = ?
            ORDER BY evento.inicio DESC";
    
    $stmtOrganizador = mysqli_prepare($conexao, $sqlOrganizador);
    $eventosOrganizador = [];
    if ($stmtOrganizador) {
        mysqli_stmt_bind_param($stmtOrganizador, "s", $cpfUsuario);
        mysqli_stmt_execute($stmtOrganizador);
        $resultadoOrganizador = mysqli_stmt_get_result($stmtOrganizador);
        while ($dadosEvento = mysqli_fetch_assoc($resultadoOrganizador)) {
            $eventosOrganizador[] = formatarEvento($dadosEvento);
        }
        mysqli_stmt_close($stmtOrganizador);
    }
    
    // Buscar eventos de colaboração
    garantirEsquemaColaboradores($conexao);
    $sqlColaboracao = "SELECT 
                e.cod_evento, 
                e.nome, 
                e.inicio, 
                e.conclusao, 
                e.categoria, 
                e.lugar, 
                e.modalidade, 
                e.certificado,
                e.publico_alvo,
                e.descricao,
                e.duracao,
                e.imagem,
                DATE_FORMAT(e.inicio, '%d/%m/%y') as data_formatada,
                CASE 
                    WHEN e.conclusao < NOW() THEN 'Concluído'
                    WHEN e.inicio > NOW() THEN 'Agendado'
                    ELSE 'Em andamento'
                END as status
            FROM colaboradores_evento c
            INNER JOIN evento e ON c.cod_evento = e.cod_evento
            WHERE c.CPF = ?
            AND NOT EXISTS (
                SELECT 1 FROM organiza o 
                WHERE o.cod_evento = e.cod_evento AND o.CPF = ?
            )
            ORDER BY e.inicio DESC";
    
    $stmtColaboracao = mysqli_prepare($conexao, $sqlColaboracao);
    $eventosColaboracao = [];
    if ($stmtColaboracao) {
        mysqli_stmt_bind_param($stmtColaboracao, 'ss', $cpfUsuario, $cpfUsuario);
        mysqli_stmt_execute($stmtColaboracao);
        $resultadoColaboracao = mysqli_stmt_get_result($stmtColaboracao);
        while ($row = mysqli_fetch_assoc($resultadoColaboracao)) {
            $row['tipo_certificado'] = $row['tipo_certificado'] ?? '';
            $tem_certificado = ((int)$row['certificado'] === 1);
            
            if ($tem_certificado) {
                if ($row['tipo_certificado'] === 'Ensino' || $row['tipo_certificado'] === 'Pesquisa' || $row['tipo_certificado'] === 'Extensao') {
                    $row['certificado'] = $row['tipo_certificado'];
                } else {
                    $row['certificado'] = 'Sim';
                }
            } else {
                $row['certificado'] = 'Não';
            }
            $tem_certificado = ((int)$row['certificado'] === 1);
            
            if ($tem_certificado) {
                if ($row['tipo_certificado'] === 'Ensino' || $row['tipo_certificado'] === 'Pesquisa' || $row['tipo_certificado'] === 'Extensao') {
                    $row['certificado'] = $row['tipo_certificado'];
                } else {
                    $row['certificado'] = 'Sim';
                }
            } else {
                $row['certificado'] = 'Não';
            }
            $eventosColaboracao[] = $row;
        }
        mysqli_stmt_close($stmtColaboracao);
    }
}

// Função para formatar strings para os atributos data-*
function formatar($txt) {
    $map = [
        'Í'=>'A','Í€'=>'A','Í‚'=>'A','Í'=>'A','Í„'=>'A','á'=>'a','Í '=>'a','Í¢'=>'a','ã'=>'a','Í¤'=>'a',
        'É'=>'E','Íˆ'=>'E','ÍŠ'=>'E','Í‹'=>'E','é'=>'e','Í¨'=>'e','Íª'=>'e','Í«'=>'e',
        'Í'=>'I','ÍŒ'=>'I','ÍŽ'=>'I','Í'=>'I','í'=>'i','Í¬'=>'i','Í®'=>'i','Í¯'=>'i',
        'Í“'=>'O','Í’'=>'O','Í”'=>'O','Õ'=>'O','Í–'=>'O','ó'=>'o','Í²'=>'o','Í´'=>'o','õ'=>'o','Í¶'=>'o',
        'Ú'=>'U','Í™'=>'U','Í›'=>'U','Íœ'=>'U','ú'=>'u','Í¹'=>'u','Í»'=>'u','Í¼'=>'u',
        'Í‡'=>'C','ç'=>'c'
    ];
    $txt = strtr($txt ?? '', $map);
    $txt = strtolower($txt);
    $txt = str_replace(' ', '_', $txt);
    return preg_replace('/[^a-z0-9_]/','', $txt);
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Eventos</title>
    <link rel="stylesheet" href="../styleGlobal.css" />
    <link rel="stylesheet" href="../styleGlobalMobile.css" media="(max-width: 767px)" />
    <style>
        body {
            align-items: flex-start;
        }

        /* Container principal com posição relativa */
        #main-content {
            position: relative;
            min-height: 100vh;
        }

        /* Barra de pesquisa em posição absoluta - fixa no topo do main-content */
        .barra-pesquisa-fixa {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            z-index: 5;
            width: 100%;
            box-sizing: border-box;
        }

        /* Remove o padding da barra-pesquisa-container para alinhar com eventos inscritos */
        .barra-pesquisa-fixa .barra-pesquisa-container {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        /* Container de conteúdo com padding-top para compensar a barra */
        .conteudo-eventos {
            width: 100%;
            padding-top: 90px; /* Espaço para a barra fixa */
            position: relative;
        }

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
            pointer-events: auto; /* IMPORTANTE: Permitir cliques mesmo quando opacity=0 durante hover */
        }

        .CaixaDoEvento:hover .AcoesFlutuantes {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            pointer-events: auto; /* Garantir que os botões sejam clicáveis */
        }

        .BotaoAcaoCard {
            width: 11cqi;
            height: 11cqi;
            border-radius: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            padding: 0;
            cursor: pointer;
            transition: transform 0.2s ease, background 0.2s ease;
            pointer-events: auto; /* IMPORTANTE: Garantir que o botão seja clicável */
            position: relative; /* Adicionar contexto de posicionamento */
            z-index: 100; /* Colocar acima de qualquer outro elemento */
        }

        .BotaoAcaoCard:hover {
            transform: scale(1.1);
        }

        .BotaoAcaoCard img {
            width: 7cqi;
            height: 7cqi;
            display: block;
        }

        body.modal-aberto { overflow: hidden !important; }
        body.modal-aberto #main-content { overflow: hidden !important; }

        /* Botão para abrir lista de favoritos */
        .BotaoFavoritosTrigger {
            width: clamp(30px, 4vw, 48px);
            aspect-ratio: 1 / 1;
            flex-shrink: 0;
            border-radius: 100% !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
        }
        .BotaoFavoritosTrigger img {
            width: 1.25rem;
            height: 1.25rem;
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
        .modal-favoritos.ativo { display: flex; }
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
        .modal-favoritos button.fechar:hover { opacity: 0.7; }
        .lista-favoritos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
            max-height: 65vh;
            overflow-y: auto;
            padding: 0.25rem;
        }
        .lista-favoritos::-webkit-scrollbar { width: 0.5rem; }
        .lista-favoritos::-webkit-scrollbar-track { background: var(--fundo-claro-transparente); border-radius: 0.25rem; }
        .lista-favoritos::-webkit-scrollbar-thumb { background: var(--botao); border-radius: 0.25rem; }
        .lista-favoritos::-webkit-scrollbar-thumb:hover { background: var(--destaque); }

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
        .modal-mensagem.ativo { display: flex; }
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
        .modal-mensagem .contador-caracteres {
            text-align: right;
            font-size: 0.85rem;
            color: var(--texto);
            margin-top: 0.5rem;
            opacity: 0.7;
        }
        .modal-mensagem .contador-caracteres.limite-alcancado {
            color: var(--vermelho);
            opacity: 1;
            font-weight: 600;
        }
        .modal-mensagem .acoes {
            margin-top: 0.75rem;
            display: flex;
            gap: 0.75rem;
            justify-content: space-between;
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

        /* Modal de Compartilhar */
        .modal-compartilhar {
            display: none;
            position: fixed;
            inset: 0;
            background: var(--fundo-escuro-transparente);
            z-index: 10010;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .modal-compartilhar.ativo { display: flex; }
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
        .modal-compartilhar button.fechar:hover { opacity: 0.7; }
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
        .btn-compartilhar-app:hover { transform: translateY(-3px); }
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
        .icone-whatsapp { background: var(--whatsapp); }
        .icone-instagram {
            background: linear-gradient(45deg, var(--instagram-inicio) 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, var(--instagram-fim) 100%);
        }
        .icone-email { background: var(--email-vermelho); }
        .icone-x { background: var(--preto); }
        .icone-copiar { background: var(--botao); }
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
        .aviso-compartilhar strong { color: var(--botao); }

        /* Cards de favoritos - mesmo estilo do InicioParticipante.php */
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
    </style>
</head>
<body>
    <div id="main-content">
        <!-- Barra de pesquisa fixa -->
        <div class="barra-pesquisa-fixa">
            <div class="section-title-wrapper">
                <div class="barra-pesquisa-container">
                    <!-- Botão de favoritos esquerda da barra de pesquisa -->
                    <button type="button" class="BotaoFavoritosTrigger botao" id="btn-abrir-favoritos" title="Ver favoritos"
                        aria-label="Ver favoritos">
                        <img src="../Imagens/Medalha_preenchida.svg" alt="Favoritos">
                    </button>
                    <div class="barra-pesquisa">
                        <div class="campo-pesquisa-wrapper">
                            <input class="campo-pesquisa" type="text" id="busca-meus-eventos-org"
                                name="busca_meus_eventos_org" placeholder="Procurar eventos" autocomplete="off" />
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
            </div>
        </div>

        <!-- Conteúdo dos eventos -->
        <div class="conteudo-eventos">
            <div class="section-title-wrapper">
                <div class="div-section-title titulo-meus-eventos">
                    <h1 class="section-title">Meus Eventos</h1>
                </div>
            </div>

            <div class="container" id="eventos-container">
                <!-- Botão adicionar evento -->
                <div class="botao CaixaDoEventoAdicionar" onclick="adicionarNovoEvento()">
                    +
                </div>
                
                <!-- Eventos do organizador -->
                <?php if (count($eventosOrganizador) > 0): ?>
                    <?php foreach ($eventosOrganizador as $ev): 
                        $dataInicioISO = date('Y-m-d', strtotime($ev['inicio']));
                        $tipo = formatar($ev['categoria']);
                        $local = formatar($ev['lugar']);
                        $modalidadeAttr = formatar($ev['modalidade'] ?? '');
                        $cert = ($ev['certificado'] === 'Sim') ? 'sim' : 'nao';
                        $imagem_evento = isset($ev['imagem']) && $ev['imagem'] !== '' ? $ev['imagem'] : 'ImagensEventos/CEU-ImagemEvento.png';
                        $caminho_imagem = '../' . ltrim($imagem_evento, "/\\");
                    ?>
                        <div class="botao CaixaDoEvento" 
                            onclick="carregarPagina('eventoOrganizado', <?= (int)$ev['cod_evento'] ?>)"
                            data-tipo="<?= htmlspecialchars($tipo) ?>"
                            data-modalidade="<?= htmlspecialchars($modalidadeAttr) ?>"
                            data-localizacao="<?= htmlspecialchars($local) ?>"
                            data-data="<?= $dataInicioISO ?>"
                            data-certificado="<?= $cert ?>"
                            data-cod-evento="<?= (int)$ev['cod_evento'] ?>">
                            <!-- Ações flutuantes: Favoritar, Mensagem, Compartilhar -->
                            <div class="AcoesFlutuantes">
                                <button type="button" class="BotaoAcaoCard BotaoFavoritoCard botao" title="Favoritar" aria-label="Favoritar"
                                    data-cod="<?= (int)$ev['cod_evento'] ?>" data-favorito="0">
                                    <img src="../Imagens/Medalha_linha.svg" alt="Favoritar">
                                </button>
                                <button type="button" class="BotaoAcaoCard BotaoMensagemCard botao" title="Enviar mensagem ao organizador"
                                    aria-label="Mensagem" data-cod="<?= (int)$ev['cod_evento'] ?>">
                                    <img src="../Imagens/Carta.svg" alt="Mensagem">
                                </button>
                                <button type="button" class="BotaoAcaoCard BotaoCompartilharCard botao" title="Compartilhar"
                                    aria-label="Compartilhar" data-cod="<?= (int)$ev['cod_evento'] ?>">
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
                                            <img src="../Imagens/info-status.svg" alt="" />
                                        </span>
                                        <span class="evento-info-texto"><span class="evento-info-label">Status:</span> <?= htmlspecialchars($ev['status']) ?></span>
                                    </li>
                                    <li class="evento-info-item">
                                        <span class="evento-info-icone" aria-hidden="true">
                                            <img src="../Imagens/info-data.svg" alt="" />
                                        </span>
                                        <span class="evento-info-texto"><span class="evento-info-label">Data:</span> <?= htmlspecialchars($ev['data_formatada']) ?></span>
                                    </li>
                                    <li class="evento-info-item">
                                        <span class="evento-info-icone" aria-hidden="true">
                                            <img src="../Imagens/info-certificado.svg" alt="" />
                                        </span>
                                        <span class="evento-info-texto"><span class="evento-info-label">Certificado:</span> <?php 
                                            $tipo_cert = $ev['tipo_certificado'] ?? '';
                                            $tem_cert = isset($ev['certificado']) && (int)$ev['certificado'] === 1;
                                            if ($tem_cert) {
                                                if ($tipo_cert === 'Ensino' || $tipo_cert === 'Pesquisa' || $tipo_cert === 'Extensão') {
                                                    echo htmlspecialchars($tipo_cert);
                                                } else {
                                                    echo 'Sim';
                                                }
                                            } else {
                                                echo 'Não';
                                            }
                                        ?></span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column:1/-1;text-align:center;padding:5px 0;color:var(--botao);font-size:1.1rem;">
                        Você ainda não criou nenhum evento
                    </div>
                <?php endif; ?>
            </div>

            <!-- Divisória entre seções -->
            <div class="divisoria-secoes" style="width: 100%; height: 1px; background: linear-gradient(to right, transparent, var(--botao) 20%, var(--botao) 80%, transparent); margin: 0 0 0 0;"></div>

            <div class="section-title-wrapper secao-colaboracao">
                <div class="div-section-title titulo-organizacao">
                    <h1 class="section-title">Organização</h1>
                </div>
            </div>

            <div class="container" id="colaboracao-container">
                <!-- Eventos de colaboração -->
                <?php if (count($eventosColaboracao) > 0): ?>
                    <?php foreach ($eventosColaboracao as $ev): 
                        $dataInicioISO = date('Y-m-d', strtotime($ev['inicio']));
                        $tipo = formatar($ev['categoria']);
                        $local = formatar($ev['lugar']);
                        $modalidadeAttr = formatar($ev['modalidade'] ?? '');
                        $cert = ($ev['certificado'] === 'Sim') ? 'sim' : 'nao';
                        $imagem_evento = isset($ev['imagem']) && $ev['imagem'] !== '' ? $ev['imagem'] : 'ImagensEventos/CEU-ImagemEvento.png';
                        $caminho_imagem = '../' . ltrim($imagem_evento, "/\\");
                    ?>
                        <div class="botao CaixaDoEvento" 
                            onclick="carregarPagina('eventoOrganizado', <?= (int)$ev['cod_evento'] ?>)"
                            data-tipo="<?= htmlspecialchars($tipo) ?>"
                            data-modalidade="<?= htmlspecialchars($modalidadeAttr) ?>"
                            data-localizacao="<?= htmlspecialchars($local) ?>"
                            data-data="<?= $dataInicioISO ?>"
                            data-certificado="<?= $cert ?>"
                            data-cod-evento="<?= (int)$ev['cod_evento'] ?>">
                            <!-- Ações flutuantes: Favoritar, Mensagem, Compartilhar -->
                            <div class="AcoesFlutuantes">
                                <button type="button" class="BotaoAcaoCard BotaoFavoritoCard botao" title="Favoritar" aria-label="Favoritar"
                                    data-cod="<?= (int)$ev['cod_evento'] ?>" data-favorito="0">
                                    <img src="../Imagens/Medalha_linha.svg" alt="Favoritar">
                                </button>
                                <button type="button" class="BotaoAcaoCard BotaoMensagemCard botao" title="Enviar mensagem ao organizador"
                                    aria-label="Mensagem" data-cod="<?= (int)$ev['cod_evento'] ?>">
                                    <img src="../Imagens/Carta.svg" alt="Mensagem">
                                </button>
                                <button type="button" class="BotaoAcaoCard BotaoCompartilharCard botao" title="Compartilhar"
                                    aria-label="Compartilhar" data-cod="<?= (int)$ev['cod_evento'] ?>">
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
                                            <img src="../Imagens/info-status.svg" alt="" />
                                        </span>
                                        <span class="evento-info-texto"><span class="evento-info-label">Status:</span> <?= htmlspecialchars($ev['status']) ?></span>
                                    </li>
                                    <li class="evento-info-item">
                                        <span class="evento-info-icone" aria-hidden="true">
                                            <img src="../Imagens/info-data.svg" alt="" />
                                        </span>
                                        <span class="evento-info-texto"><span class="evento-info-label">Data:</span> <?= htmlspecialchars($ev['data_formatada']) ?></span>
                                    </li>
                                    <li class="evento-info-item">
                                        <span class="evento-info-icone" aria-hidden="true">
                                            <img src="../Imagens/info-certificado.svg" alt="" />
                                        </span>
                                        <span class="evento-info-texto"><span class="evento-info-label">Certificado:</span> <?php 
                                            $tipo_cert = $ev['tipo_certificado'] ?? '';
                                            $tem_cert = isset($ev['certificado']) && (int)$ev['certificado'] === 1;
                                            if ($tem_cert) {
                                                if ($tipo_cert === 'Ensino' || $tipo_cert === 'Pesquisa' || $tipo_cert === 'Extensão') {
                                                    echo htmlspecialchars($tipo_cert);
                                                } else {
                                                    echo 'Sim';
                                                }
                                            } else {
                                                echo 'Não';
                                            }
                                        ?></span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column:1/-1;text-align:center;padding:30px 0;color:var(--botao);font-size:1.1rem;">
                        Você não é organizador em nenhum evento ainda.
                    </div>
                <?php endif; ?>
            </div>
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
                <div id="contador-mensagem-organizador" class="contador-caracteres">0 / 500</div>
                <div class="acoes">
                    <button class="botao-secundario botao" type="button" onclick="fecharModalMensagem()">Cancelar</button>
                    <button class="botao-primario botao" type="button" onclick="enviarMensagemOrganizador()">Enviar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Compartilhar -->
    <div id="modal-compartilhar" class="modal-compartilhar">
        <div class="conteudo">
            <div class="cabecalho">
                <span>Compartilhar</span>
                <button type="button" class="fechar" onclick="event.stopPropagation(); fecharModalCompartilhar();" aria-label="Fechar">×</button>
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

    <!-- Scripts são carregados pelo ContainerOrganizador.php -->
</body>

</html>



