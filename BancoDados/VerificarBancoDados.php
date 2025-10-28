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
    if (preg_match_all('/create\s+table\s+if\s+not\s+exists\s+`?([a-zA-Z0-9_]+)`?\s*\((.*?)\)\s*;/is', $sql, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $mt) {
            $tabela = $mt[1];
            $conteudo = $mt[2];
            $schema['tables'][$tabela] = $schema['tables'][$tabela] ?? ['create' => '', 'columns' => []];
            $schema['tables'][$tabela]['create'] = $mt[0]; // bloco completo do CREATE
            // Percorre linhas internas do create
            $linhas = preg_split('/\n/', $conteudo);
            foreach ($linhas as $linha) {
                $linha = trim(trim($linha), ',');
                if ($linha === '') continue;
                $lower = strtolower($linha);
                // Ignora constraints/keys/foreigns/primary etc
                if (preg_match('/^(primary|foreign|unique|constraint|key|check)\b/', $lower)) continue;
                // Captura nome da coluna (entre crase ou primeira palavra)
                if (preg_match('/^`?([a-zA-Z0-9_]+)`?\s+/', $linha, $mc)) {
                    $col = $mc[1];
                    // Guarda definição original da coluna (sem vírgula final)
                    $schema['tables'][$tabela]['columns'][$col] = $linha;
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

// Verifica diferenças entre o banco atual e o arquivo SQL (dinâmico)
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
        // Colunas esperadas
        foreach ($colunas as $coluna) {
            $resCol = mysqli_query($conexaoServidor, "SHOW COLUMNS FROM `$tabela` LIKE '" . mysqli_real_escape_string($conexaoServidor, $coluna) . "'");
            if (!$resCol || mysqli_num_rows($resCol) == 0) {
                $diferencas[] = "Coluna '$tabela.$coluna' não existe";
            }
        }
    }

    return $diferencas;
}

// Aplica diferenças com base no schema do arquivo (cria tabelas/colunas faltantes)
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
                if (!@mysqli_query($conexaoServidor, $create)) {
                    $erros[] = 'Erro ao criar tabela ' . $tabela . ': ' . mysqli_error($conexaoServidor);
                } else {
                    $executados++;
                }
            } else {
                $erros[] = 'Bloco CREATE da tabela ' . $tabela . ' não encontrado no SQL.';
            }
            continue;
        }

        // Adicionar colunas faltantes
        foreach ($info['columns'] as $coluna => $def) {
            $resCol = mysqli_query($conexaoServidor, "SHOW COLUMNS FROM `$tabela` LIKE '" . mysqli_real_escape_string($conexaoServidor, $coluna) . "'");
            if (!$resCol || mysqli_num_rows($resCol) == 0) {
                $defClean = rtrim($def, ",\n\r ");
                if (preg_match('/^`?([a-zA-Z0-9_]+)`?\s*(.*)$/', $defClean, $mm)) {
                    $colName = $mm[1];
                    $resto = trim($mm[2]);
                    $sqlAlter = "ALTER TABLE `$tabela` ADD COLUMN IF NOT EXISTS `$colName` $resto";
                } else {
                    $sqlAlter = "ALTER TABLE `$tabela` ADD COLUMN IF NOT EXISTS $defClean";
                }
                if (!@mysqli_query($conexaoServidor, $sqlAlter)) {
                    $erros[] = 'Erro ao adicionar coluna ' . $tabela . '.' . $coluna . ': ' . mysqli_error($conexaoServidor);
                } else {
                    $executados++;
                }
            }
        }
    }

    $GLOBALS['__EXECUTADOS_TOTAL__'] = ($GLOBALS['__EXECUTADOS_TOTAL__'] ?? 0) + $executados;
    return ['sucesso' => empty($erros), 'executados' => $executados, 'erros' => $erros];
}

// Executa o arquivo SQL
function executarArquivoSQL($conexaoServidor, $nomeBanco, $caminhoArquivo) {
    if (!file_exists($caminhoArquivo)) {
        return ['sucesso' => false, 'erro' => 'Arquivo SQL não encontrado: ' . $caminhoArquivo];
    }
    
    $sql = file_get_contents($caminhoArquivo);
    
    // Remove comentários de linha única
    $sql = preg_replace('/--[^\n]*\n/', "\n", $sql);
    
    // Seleciona o banco (o arquivo SQL usa "use CEU_bd")
    mysqli_select_db($conexaoServidor, $nomeBanco);
    
    // Separa comandos por ponto e vírgula
    $comandos = explode(';', $sql);
    
    $erros = [];
    $executados = 0;
    
    foreach ($comandos as $comando) {
        $comando = trim($comando);
        
        // Pula comandos vazios e comentários
        if (empty($comando) || 
            stripos($comando, 'create database') !== false || 
            stripos($comando, 'use ') === 0 ||
            stripos($comando, 'show tables') !== false) {
            continue;
        }
        
        if (!@mysqli_query($conexaoServidor, $comando)) {
            $erro = mysqli_error($conexaoServidor);
            
            // Lista de erros que devem ser ignorados
            $ignorarErros = [
                'already exists',
                'Duplicate',
                'Unknown column',
                'Duplicate key name',
                'Multiple primary key',
                'Check constraint'
            ];
            
            $deveIgnorar = false;
            foreach ($ignorarErros as $textoIgnorar) {
                if (stripos($erro, $textoIgnorar) !== false) {
                    $deveIgnorar = true;
                    break;
                }
            }
            
            // Só adiciona aos erros se não for um erro ignorável e não estiver vazio
            if (!$deveIgnorar && !empty($erro)) {
                $erros[] = substr($comando, 0, 80) . '... → ' . substr($erro, 0, 100);
            }
        } else {
            $executados++;
        }
    }
    // Armazena total global para uso em shutdown handler
    $GLOBALS['__EXECUTADOS_TOTAL__'] = $executados;
    
    return [
        'sucesso' => true, // Sempre sucesso se chegou até aqui
        'executados' => $executados,
        'erros' => $erros,
        'avisos' => count($erros) > 0 ? 'Alguns comandos geraram avisos mas foram ignorados' : ''
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
        $diferencas = verificarDiferencas($conexaoServidor, $banco, __DIR__ . '/BancodeDadosCEU.sql');
        echo json_encode([
            'bancoExiste' => true,
            'atualizado' => empty($diferencas),
            'diferencas' => $diferencas
        ], JSON_UNESCAPED_UNICODE);
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
    // Aplica diferenças (para quando apenas o CREATE foi alterado)
    $rDiff = aplicarDiferencas($conexaoServidor, $banco, $caminhoSQL);
    // Executa o arquivo completo (para inserts e demais comandos)
    $rExec = executarArquivoSQL($conexaoServidor, $banco, $caminhoSQL);
    $resultado = [
        'sucesso' => ($rDiff['sucesso'] && $rExec['sucesso']),
        'executados' => ($rDiff['executados'] + $rExec['executados']),
        'erros' => array_merge($rDiff['erros'] ?? [], $rExec['erros'] ?? []),
        'avisos' => $rExec['avisos'] ?? ''
    ];
    
    mysqli_close($conexaoServidor);
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    exit;
}

mysqli_close($conexaoServidor);
?>
