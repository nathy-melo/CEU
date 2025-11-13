<?php
// Evita qualquer output antes do JSON
error_reporting(0);
ini_set('display_errors', 0);

// Modo API: ativa proteção para sempre responder JSON mesmo em fatais
$__API_MODE__ = (isset($_GET['verificar']) || isset($_GET['atualizar']));
$__EXECUTADOS_TOTAL__ = 0; // usado para reportar em caso de erro fatal
if ($__API_MODE__) {
    // Limpa buffers existentes e inicia um novo
    if (function_exists('ob_get_level')) {
        while (@ob_get_level() > 0) { @ob_end_clean(); }
    }
    ob_start();
    register_shutdown_function(function () use ($__API_MODE__) {
        // Captura erros fatais e devolve JSON
        $err = error_get_last();
        if ($__API_MODE__ && $err) {
            while (@ob_get_level() > 0) { @ob_end_clean(); }
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'sucesso' => false,
                'erro' => 'Erro fatal do PHP durante a operação',
                'executados' => (int)($GLOBALS['__EXECUTADOS_TOTAL__'] ?? 0),
                'detalhes' => [
                    'type' => $err['type'] ?? 0,
                    'message' => $err['message'] ?? '',
                    'file' => $err['file'] ?? '',
                    'line' => $err['line'] ?? 0
                ]
            ], JSON_UNESCAPED_UNICODE);
        }
    });
}

/**
 * Verificação Simples do Banco de Dados
 * Compara o banco atual com o arquivo BancodeDadosCEU.sql
 */

// Conecta ao MySQL sem especificar banco (para poder criar se necessário)
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "CEU_bd";

// Tenta conectar sem especificar o banco primeiro
$conexaoServidor = @mysqli_connect($servidor, $usuario, $senha);

if (!$conexaoServidor) {
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'erro' => true,
        'mensagem' => 'Não foi possível conectar ao servidor MySQL. Verifique se o XAMPP está rodando.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Verifica se o banco de dados existe
function verificarBancoExiste($conexaoServidor, $nomeBanco) {
    $resultado = mysqli_query($conexaoServidor, "SHOW DATABASES LIKE '$nomeBanco'");
    return $resultado && mysqli_num_rows($resultado) > 0;
}

// Cria o banco de dados
function criarBanco($conexaoServidor, $nomeBanco) {
    $sql = "CREATE DATABASE IF NOT EXISTS `$nomeBanco` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    return mysqli_query($conexaoServidor, $sql);
}

// Lê o arquivo SQL e extrai estrutura esperada (tabelas e colunas)
function parseSqlSchema($caminhoArquivo) {
    if (!file_exists($caminhoArquivo)) {
        return ['erro' => 'Arquivo SQL não encontrado', 'database' => null, 'tables' => []];
    }
    $sql = file_get_contents($caminhoArquivo);
    // Remove comentários /* ... */ e linhas começando com --
    $sql = preg_replace('/\/(\*[^*]*\*+(?:[^\/*][^*]*\*+)*\/)/s', '', $sql);
    $sql = preg_replace('/--[^\n]*\n/', "\n", $sql);

    // Normaliza quebras de linha
    $sql = str_replace(["\r\n", "\r"], "\n", $sql);

    $schema = [ 'database' => null, 'tables' => [] ]; // tables: nome => ['create' => string, 'columns' => [col => def]]

    // Captura o banco após USE ...
    if (preg_match('/\buse\s+`?([a-zA-Z0-9_]+)`?\s*;/i', $sql, $m)) {
        $schema['database'] = $m[1];
    }

    // Captura blocos de CREATE TABLE ... ( ... );
    // Regex mais robusta que captura até o ponto e vírgula final
    if (preg_match_all('/create\s+table\s+(?:if\s+not\s+exists\s+)?`?([a-zA-Z0-9_]+)`?\s*\(((?:[^()]+|\((?:[^()]+|\([^()]*\))*\))*)\)\s*([^;]*);/is', $sql, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $mt) {
            $tabela = $mt[1];
            $conteudo = $mt[2];
            $opcoes = $mt[3]; // ENGINE, DEFAULT CHARSET, etc.
            $schema['tables'][$tabela] = $schema['tables'][$tabela] ?? ['create' => '', 'columns' => [], 'options' => ''];
            
            // Guarda o bloco CREATE completo
            $blocoCompleto = trim($mt[0]);
            $schema['tables'][$tabela]['create'] = $blocoCompleto;
            $schema['tables'][$tabela]['options'] = trim($opcoes);
            
            // Percorre linhas internas do create de forma mais precisa
            $linhas = preg_split('/,\s*\n/', $conteudo);
            foreach ($linhas as $linha) {
                $linha = trim($linha);
                $linha = rtrim($linha, ',');
                if ($linha === '') continue;
                
                $lower = strtolower($linha);
                
                // Ignora constraints/keys/foreigns/primary etc
                if (preg_match('/^\s*(primary\s+key|foreign\s+key|unique\s+(?:key|index)?|constraint|key\s+|index\s+|check\s*\()/i', $lower)) {
                    continue;
                }
                
                // Captura nome da coluna (entre crase ou primeira palavra)
                if (preg_match('/^\s*`?([a-zA-Z0-9_]+)`?\s+(.+)$/i', $linha, $mc)) {
                    $col = $mc[1];
                    $def = trim($mc[2]);
                    // Guarda definição completa da coluna (tipo, NOT NULL, DEFAULT, etc.)
                    $schema['tables'][$tabela]['columns'][$col] = "`$col` $def";
                }
            }
        }
    }

    // Captura ALTER TABLE ... ADD COLUMN ...
    if (preg_match_all('/alter\s+table\s+`?([a-zA-Z0-9_]+)`?\s+add\s+column\s+(?:if\s+not\s+exists\s+)?(`?[a-zA-Z0-9_]+`?\s+[^;\n]+)\s*;?/i', $sql, $am, PREG_SET_ORDER)) {
        foreach ($am as $a) {
            $tabela = $a[1];
            $def = trim($a[2]); // ex: `Telefone` varchar(20) NULL
            // Extrai nome da coluna
            if (preg_match('/^`?([a-zA-Z0-9_]+)`?/i', $def, $mc)) {
                $col = $mc[1];
                $schema['tables'][$tabela] = $schema['tables'][$tabela] ?? ['create' => '', 'columns' => []];
                if (!isset($schema['tables'][$tabela]['columns'][$col])) {
                    $schema['tables'][$tabela]['columns'][$col] = $def;
                }
            }
        }
    }

    // Mantém mapa col => definição para ALTER dinâmico

    return $schema;
}

// Verifica diferenças entre o banco atual e o arquivo SQL (apenas o que FALTA)
function verificarDiferencas($conexaoServidor, $nomeBanco, $caminhoArquivo) {
    $diferencas = [];
    $schema = parseSqlSchema($caminhoArquivo);
    if (isset($schema['erro'])) {
        return [ $schema['erro'] ];
    }

    // Se o SQL define um banco, usa-o como referência
    if (!empty($schema['database'])) {
        $nomeBanco = $schema['database'];
    }

    if (!mysqli_select_db($conexaoServidor, $nomeBanco)) {
        return ["Não foi possível selecionar o banco de dados '$nomeBanco'"];
    }

    // Tabelas esperadas vindas do arquivo
    foreach ($schema['tables'] as $tabela => $info) {
        $colunas = array_keys($info['columns']);
        $resTab = mysqli_query($conexaoServidor, "SHOW TABLES LIKE '" . mysqli_real_escape_string($conexaoServidor, $tabela) . "'");
        if (!$resTab || mysqli_num_rows($resTab) == 0) {
            $diferencas[] = "Tabela '$tabela' não existe";
            // Não tenta checar colunas se a tabela nem existe
            continue;
        }
        
        // Pega todas as colunas da tabela atual no banco
        $colunasAtuais = [];
        $resColsAtual = mysqli_query($conexaoServidor, "SHOW FULL COLUMNS FROM `$tabela`");
        if ($resColsAtual) {
            while ($row = mysqli_fetch_assoc($resColsAtual)) {
                $colunasAtuais[$row['Field']] = $row;
            }
        }
        
        // Verifica apenas colunas FALTANTES (não verifica tipos ou extras)
        foreach ($colunas as $coluna) {
            if (!isset($colunasAtuais[$coluna])) {
                $diferencas[] = "Coluna '$tabela.$coluna' não existe";
            }
        }
    }

    return $diferencas;
}

// Aplica diferenças com base no schema do arquivo (cria apenas o que FALTA)
function aplicarDiferencas($conexaoServidor, $nomeBanco, $caminhoArquivo) {
    $schema = parseSqlSchema($caminhoArquivo);
    if (isset($schema['erro'])) {
        return ['sucesso' => false, 'executados' => 0, 'erros' => [$schema['erro']]];
    }
    if (!empty($schema['database'])) {
        $nomeBanco = $schema['database'];
    }
    mysqli_select_db($conexaoServidor, $nomeBanco);

    $executados = 0;
    $erros = [];

    foreach ($schema['tables'] as $tabela => $info) {
        $resTab = mysqli_query($conexaoServidor, "SHOW TABLES LIKE '" . mysqli_real_escape_string($conexaoServidor, $tabela) . "'");
        if (!$resTab || mysqli_num_rows($resTab) == 0) {
            // Criar a tabela usando o bloco CREATE completo
            $create = $info['create'] ?? '';
            if ($create) {
                // Remove "IF NOT EXISTS" se já existir na string para evitar duplicação
                $create = preg_replace('/IF\s+NOT\s+EXISTS/i', '', $create);
                // Adiciona IF NOT EXISTS de forma limpa
                $create = preg_replace('/CREATE\s+TABLE\s+/i', 'CREATE TABLE IF NOT EXISTS ', $create);
                
                if (@mysqli_query($conexaoServidor, $create)) {
                    $executados++;
                } else {
                    $erro = mysqli_error($conexaoServidor);
                    // Ignora erro se a tabela já existe
                    if (stripos($erro, 'already exists') === false) {
                        $erros[] = 'Erro ao criar tabela ' . $tabela . ': ' . $erro;
                    }
                }
            }
            continue;
        }

        // Adicionar apenas colunas faltantes
        foreach ($info['columns'] as $coluna => $def) {
            $resCol = mysqli_query($conexaoServidor, "SHOW COLUMNS FROM `$tabela` LIKE '" . mysqli_real_escape_string($conexaoServidor, $coluna) . "'");
            if (!$resCol || mysqli_num_rows($resCol) == 0) {
                $defClean = rtrim($def, ",\n\r ");
                if (preg_match('/^`?([a-zA-Z0-9_]+)`?\s*(.*)$/', $defClean, $mm)) {
                    $colName = $mm[1];
                    $resto = trim($mm[2]);
                    $sqlAlter = "ALTER TABLE `$tabela` ADD COLUMN `$colName` $resto";
                } else {
                    $sqlAlter = "ALTER TABLE `$tabela` ADD COLUMN $defClean";
                }
                
                if (@mysqli_query($conexaoServidor, $sqlAlter)) {
                    $executados++;
                } else {
                    $erro = mysqli_error($conexaoServidor);
                    // Ignora erro se a coluna já existe
                    if (stripos($erro, 'duplicate column') === false) {
                        $erros[] = 'Erro ao adicionar coluna ' . $tabela . '.' . $coluna . ': ' . $erro;
                    }
                }
            }
        }
    }

    $GLOBALS['__EXECUTADOS_TOTAL__'] = ($GLOBALS['__EXECUTADOS_TOTAL__'] ?? 0) + $executados;
    return ['sucesso' => empty($erros), 'executados' => $executados, 'erros' => $erros];
}

// Executa o arquivo SQL (apenas INSERTs e comandos de dados)
function executarArquivoSQL($conexaoServidor, $nomeBanco, $caminhoArquivo) {
    if (!file_exists($caminhoArquivo)) {
        return ['sucesso' => false, 'erro' => 'Arquivo SQL não encontrado: ' . $caminhoArquivo];
    }

    $sql = file_get_contents($caminhoArquivo);

    // Remove comentários
    $sql = preg_replace('/--[^\n]*\n/', "\n", $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

    // Seleciona o banco
    mysqli_select_db($conexaoServidor, $nomeBanco);

    // Separa comandos por ponto e vírgula
    $comandos = explode(';', $sql);

    $erros = [];
    $executados = 0;

    foreach ($comandos as $comando) {
        $comando = trim($comando);
        if ($comando === '') continue;

        $inicio = strtolower(ltrim($comando));

        // Ignora DDL de tabela (tratadas em aplicarDiferencas)
        $ehDDL = (
            stripos($inicio, 'create table') === 0 ||
            stripos($inicio, 'alter table') === 0 ||
            stripos($inicio, 'drop table') === 0 ||
            stripos($inicio, 'truncate table') === 0
        );

        // Ignora comandos de nível de banco/transação e meta
        $ehNivelBancoOuTransacao = (
            stripos($inicio, 'drop database') === 0 ||
            stripos($inicio, 'create database') === 0 ||
            stripos($inicio, 'use ') === 0 ||
            stripos($inicio, 'show ') === 0 ||
            stripos($inicio, 'start transaction') === 0 ||
            stripos($inicio, 'commit') === 0 ||
            stripos($inicio, 'rollback') === 0 ||
            stripos($inicio, 'set ') === 0 ||
            stripos($inicio, 'delimiter') === 0
        );

        if ($ehDDL || $ehNivelBancoOuTransacao) {
            continue;
        }

        if (!@mysqli_query($conexaoServidor, $comando)) {
            $erro = mysqli_error($conexaoServidor);

            // Ignora erros comuns que não são problemas reais
            $ignorarErros = [
                'already exists',
                'duplicate',
                'multiple primary key'
            ];

            $deveIgnorar = false;
            foreach ($ignorarErros as $textoIgnorar) {
                if (stripos($erro, $textoIgnorar) !== false) {
                    $deveIgnorar = true;
                    break;
                }
            }

            if (!$deveIgnorar && !empty($erro)) {
                $preview = substr(preg_replace('/\s+/', ' ', $comando), 0, 120);
                $erros[] = $preview . '... → ' . $erro;
            }
        } else {
            $executados++;
        }
    }

    $GLOBALS['__EXECUTADOS_TOTAL__'] = $executados;

    return [
        'sucesso' => empty($erros),
        'executados' => $executados,
        'erros' => $erros
    ];
}

// Retorna resultado em JSON
if (isset($_GET['verificar'])) {
    // Limpa qualquer output anterior de forma segura
    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) { @ob_end_clean(); }
    }
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    
    $bancoExiste = verificarBancoExiste($conexaoServidor, $banco);
    
    if (!$bancoExiste) {
        echo json_encode([
            'bancoExiste' => false,
            'diferencas' => ['Banco de dados CEU_bd não existe']
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // Verifica se o banco está vazio
        mysqli_select_db($conexaoServidor, $banco);
        $resTabelas = mysqli_query($conexaoServidor, "SHOW TABLES");
        $numTabelas = $resTabelas ? mysqli_num_rows($resTabelas) : 0;
        
        if ($numTabelas === 0) {
            echo json_encode([
                'bancoExiste' => true,
                'atualizado' => false,
                'bancoVazio' => true,
                'diferencas' => ['Banco de dados está vazio - nenhuma tabela encontrada']
            ], JSON_UNESCAPED_UNICODE);
        } else {
            $diferencas = verificarDiferencas($conexaoServidor, $banco, __DIR__ . '/BancodeDadosCEU.sql');
            
            // Adiciona estatísticas
            $schema = parseSqlSchema(__DIR__ . '/BancodeDadosCEU.sql');
            $tabelasEsperadas = count($schema['tables']);
            $tabelasEncontradas = $numTabelas;
            
            echo json_encode([
                'bancoExiste' => true,
                'atualizado' => empty($diferencas),
                'bancoVazio' => false,
                'diferencas' => $diferencas,
                'estatisticas' => [
                    'tabelasEsperadas' => $tabelasEsperadas,
                    'tabelasEncontradas' => $tabelasEncontradas,
                    'diferencasTotal' => count($diferencas)
                ]
            ], JSON_UNESCAPED_UNICODE);
        }
    }
    
    mysqli_close($conexaoServidor);
    exit;
}

// Executa atualização se solicitado
if (isset($_GET['atualizar'])) {
    // Limpa qualquer output anterior de forma segura
    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) { @ob_end_clean(); }
    }
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    
    // Verifica se o banco existe, se não, cria
    if (!verificarBancoExiste($conexaoServidor, $banco)) {
        if (!criarBanco($conexaoServidor, $banco)) {
            echo json_encode([
                'sucesso' => false,
                'erro' => 'Não foi possível criar o banco de dados: ' . mysqli_error($conexaoServidor)
            ], JSON_UNESCAPED_UNICODE);
            mysqli_close($conexaoServidor);
            exit;
        }
    }
    
    $caminhoSQL = __DIR__ . '/BancodeDadosCEU.sql';
    
    // Verifica diferenças antes de aplicar
    $diferencasAntes = verificarDiferencas($conexaoServidor, $banco, $caminhoSQL);
    
    // Aplica diferenças (cria tabelas e colunas faltantes)
    $rDiff = aplicarDiferencas($conexaoServidor, $banco, $caminhoSQL);
    
    // Executa INSERTs e outros comandos de dados
    $rExec = executarArquivoSQL($conexaoServidor, $banco, $caminhoSQL);
    
    // Verifica diferenças depois de aplicar
    $diferencasDepois = verificarDiferencas($conexaoServidor, $banco, $caminhoSQL);
    
    // Junta erros de ambas as operações
    $todosErros = array_merge($rDiff['erros'] ?? [], $rExec['erros'] ?? []);
    
    // Considera sucesso se não há mais diferenças estruturais
    $sucesso = empty($diferencasDepois);
    
    $resultado = [
        'sucesso' => $sucesso,
        'executados' => ($rDiff['executados'] + $rExec['executados']),
        'erros' => $todosErros,
        'diferencasAntes' => count($diferencasAntes),
        'diferencasDepois' => count($diferencasDepois),
        'detalheDiferencasRestantes' => $diferencasDepois,
        'mensagem' => $sucesso ? 'Banco de dados atualizado com sucesso!' : 'Algumas diferenças ainda permanecem'
    ];
    
    mysqli_close($conexaoServidor);
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    exit;
}

// Endpoint para verificar se usuário existe por CPF
if (isset($_GET['action']) && $_GET['action'] === 'verificar_usuario' && isset($_GET['cpf'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    $cpf = $_GET['cpf'];
    
    // Conecta ao banco
    if (!verificarBancoExiste($conexaoServidor, $banco)) {
        echo json_encode(['existe' => false, 'erro' => 'Banco de dados não existe']);
        mysqli_close($conexaoServidor);
        exit;
    }
    
    mysqli_select_db($conexaoServidor, $banco);
    
    try {
        $sql = "SELECT CPF, Nome, Email, RA FROM usuario WHERE CPF = ?";
        $stmt = mysqli_prepare($conexaoServidor, $sql);
        
        if (!$stmt) {
            echo json_encode(['existe' => false, 'erro' => 'Erro ao preparar consulta']);
            mysqli_close($conexaoServidor);
            exit;
        }
        
        mysqli_stmt_bind_param($stmt, "s", $cpf);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $usuario = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($usuario) {
            echo json_encode([
                'existe' => true,
                'usuario' => [
                    'cpf' => $usuario['CPF'],
                    'nome' => $usuario['Nome'],
                    'email' => $usuario['Email'],
                    'ra' => $usuario['RA'] ?? ''
                ]
            ]);
        } else {
            echo json_encode(['existe' => false]);
        }
    } catch (Exception $e) {
        echo json_encode(['existe' => false, 'erro' => $e->getMessage()]);
    }
    
    mysqli_close($conexaoServidor);
    exit;
}

mysqli_close($conexaoServidor);
?>
