<?php
/**
 * Instalador Automático de Dependências
 * - Baixa composer.phar localmente (sem depender de processos externos)
 * - Executa "composer install" dentro do próprio processo PHP (evita erro mpm_winnt)
 * - Tenta contornar falta de extensões usando --ignore-platform-reqs quando necessário
 */

// Somente erros fatais (para não quebrar JSON)
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);
ini_set('memory_limit', '512M');
set_time_limit(300);

header('Content-Type: application/json; charset=utf-8');

// Retorna JSON em caso de erro fatal
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Erro fatal: ' . $e['message'],
            'file' => $e['file'] ?? null,
            'line' => $e['line'] ?? null
        ]);
    }
});

// Requisição válida
if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Requisição inválida']);
    exit;
}

$action = $_POST['action'];
$projectRoot = dirname(__DIR__); // Pasta CEU
$logPath = __DIR__ . '/instalador.log';

function logMsg($msg) {
    global $logPath;
    $ts = date('Y-m-d H:i:s');
    @file_put_contents($logPath, "[$ts] $msg\n", FILE_APPEND);
}

// Utilitários ---------------------------------------------------------------

function funcDisponivel($name) {
    if (!function_exists($name)) return false;
    $disabled = ini_get('disable_functions');
    if (!$disabled) return true;
    $list = array_map('trim', explode(',', $disabled));
    return !in_array($name, $list, true);
}

function executarComando($comando, $dir) {
    $output = [];
    $returnCode = 0;
    $oldDir = getcwd();
    @chdir($dir);
    @exec($comando . ' 2>&1', $output, $returnCode);
    @chdir($oldDir);
    return [
        'success' => $returnCode === 0,
        'output' => implode("\n", $output),
        'code' => $returnCode
    ];
}

function caminhoPhpExe() {
    $candidatos = [];
    if (defined('PHP_BINARY') && PHP_BINARY) {
        $candidatos[] = PHP_BINARY;
    }
    if (defined('PHP_BINDIR') && PHP_BINDIR) {
        $bin = rtrim(PHP_BINDIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . (stripos(PHP_OS, 'WIN') === 0 ? 'php.exe' : 'php');
        $candidatos[] = $bin;
    }
    $candidatos[] = 'C:\\xampp\\php\\php.exe';
    $candidatos[] = 'php';
    foreach ($candidatos as $c) {
        if ($c === 'php') {
            if (function_exists('exec')) {
                @exec($c . ' -v', $out, $code);
                if ($code === 0) return $c;
            }
            continue;
        }
        if (@file_exists($c)) return $c;
    }
    return 'php';
}

// Composer -----------------------------------------------------------------

function verificarComposer() {
    $output = [];
    $returnCode = 1;
    if (funcDisponivel('exec')) {
        @exec('composer --version 2>&1', $output, $returnCode);
    }
    return $returnCode === 0;
}

function baixarComposerPhar($dir) {
    $url = 'https://getcomposer.org/download/latest-stable/composer.phar';
    logMsg('Baixando composer.phar de ' . $url);
    $target = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'composer.phar';
    $data = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        $data = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 200) {
            logMsg('Falha ao baixar composer.phar. HTTP ' . $code);
            $data = false;
        }
    }
    if ($data === false) {
        $data = @file_get_contents($url);
        if ($data === false) {
            logMsg('file_get_contents falhou para composer.phar');
            return ['success' => false, 'message' => 'Não foi possível baixar composer.phar'];
        }
    }
    if (@file_put_contents($target, $data) === false) {
        logMsg('Falha ao salvar composer.phar');
        return ['success' => false, 'message' => 'Não foi possível salvar composer.phar'];
    }
    logMsg('composer.phar salvo com ' . strlen($data) . ' bytes.');
    return ['success' => true, 'message' => 'composer.phar baixado'];
}

function instalarComposerLocal($dir) {
    // Mantido como fallback: tenta rodar o instalador oficial
    try {
        $oldDir = getcwd();
        if (!@chdir($dir)) {
            return ['success' => false, 'message' => 'Não foi possível acessar o diretório: ' . $dir, 'etapas' => []];
        }
        $installerUrl = 'https://getcomposer.org/installer';
        $installerPath = 'composer-setup.php';
        $installer = function_exists('curl_init')
            ? (function() use ($installerUrl) { $ch=curl_init($installerUrl); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); curl_setopt($ch, CURLOPT_TIMEOUT, 60); $data=curl_exec($ch); curl_close($ch); return $data; })()
            : @file_get_contents($installerUrl);
        if ($installer === false) {
            @chdir($oldDir);
            return ['success' => false, 'message' => 'Falha ao baixar installer do Composer'];
        }
        if (@file_put_contents($installerPath, $installer) === false) {
            @chdir($oldDir);
            return ['success' => false, 'message' => 'Falha ao salvar composer-setup.php'];
        }
        if (!funcDisponivel('exec')) {
            @unlink($installerPath);
            @chdir($oldDir);
            return ['success' => false, 'message' => 'exec() desabilitado'];
        }
        $phpExe = caminhoPhpExe();
        @exec('"' . $phpExe . '" composer-setup.php --quiet 2>&1', $out, $code);
        @unlink($installerPath);
        @chdir($oldDir);
        return $code === 0
            ? ['success' => true, 'message' => 'Composer instalado (composer.phar)']
            : ['success' => false, 'message' => 'Erro ao executar composer-setup.php', 'output' => implode("\n", $out)];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
    }
}

function detectarPhpIniCli() {
    $phpExe = caminhoPhpExe();
    if (!funcDisponivel('exec')) return null;
    @exec('"' . $phpExe . '" --ini 2>&1', $out, $code);
    if ($code === 0) {
        foreach ($out as $line) {
            if (stripos($line, 'Loaded Configuration File') !== false) {
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $path = trim($parts[1]);
                    if ($path && file_exists($path)) return $path;
                }
            }
        }
    }
    $fallback = 'C:\\xampp\\php\\php.ini';
    return file_exists($fallback) ? $fallback : null;
}

function habilitarExtensaoNoIni($iniPath, $ext) {
    if (!$iniPath || !file_exists($iniPath) || !is_writable($iniPath)) {
        return ['success' => false, 'message' => 'php.ini não encontrado ou sem permissão de escrita'];
    }
    $content = @file_get_contents($iniPath);
    if ($content === false) {
        return ['success' => false, 'message' => 'Não foi possível ler php.ini'];
    }
    $backup = $iniPath . '.bak-ceu-' . date('YmdHis');
    @copy($iniPath, $backup);
    $patterns = [
        '/^\s*;\s*extension\s*=\s*(php_)?' . preg_quote($ext, '/') . '2?\b.*$/mi',
        '/^\s*extension\s*=\s*(php_)?' . preg_quote($ext, '/') . '2?\b.*$/mi',
    ];
    $replaced = false;
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, 'extension=' . $ext, $content);
            $replaced = true; break;
        }
    }
    if (!$replaced) { $content .= "\nextension=" . $ext . "\n"; }
    if (@file_put_contents($iniPath, $content) === false) {
        return ['success' => false, 'message' => 'Falha ao salvar php.ini após ajuste'];
    }
    return ['success' => true, 'message' => 'Extensão ' . $ext . ' habilitada no php.ini', 'backup' => $backup];
}

// Ações --------------------------------------------------------------------

switch ($action) {
    case 'verificar_composer':
        try {
            $composerGlobal = verificarComposer();
            $composerLocal = file_exists($projectRoot . '/composer.phar');
            echo json_encode([
                'success' => true,
                'composer_global' => $composerGlobal,
                'composer_local' => $composerLocal,
                'composer_disponivel' => $composerGlobal || $composerLocal,
                'message' => ($composerGlobal || $composerLocal) ? 'Composer encontrado' : 'Composer não encontrado'
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao verificar Composer: ' . $e->getMessage()]);
        }
        break;

    case 'instalar_composer':
        try {
            $res = baixarComposerPhar($projectRoot);
            if (!$res['success']) {
                $res = instalarComposerLocal($projectRoot);
            }
            echo json_encode($res);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao instalar Composer: ' . $e->getMessage()]);
        }
        break;

    case 'instalar_dependencias':
        try {
            $composerLocal = file_exists($projectRoot . '/composer.phar');
            if (!$composerLocal) {
                echo json_encode([
                    'success' => false,
                    'message' => 'composer.phar não encontrado. Use a ação instalar_composer primeiro.'
                ]);
                break;
            }
            if (!file_exists($projectRoot . '/composer.json')) {
                echo json_encode(['success' => false, 'message' => 'Arquivo composer.json não encontrado']);
                break;
            }

            // Executa Composer dentro do mesmo processo PHP
            $cwd = getcwd();
            chdir($projectRoot);
            $bufferOutput = '';
            $exitCode = 1;
            $faltandoGd = !extension_loaded('gd');
            $faltandoMb = !extension_loaded('mbstring');
            $ignoreAll = ($faltandoGd || $faltandoMb);
            try {
                require_once 'phar://composer.phar/src/bootstrap.php';
                $appClass = '\\Composer\\Console\\Application';
                $inputClass = '\\Symfony\\Component\\Console\\Input\\ArrayInput';
                $outputClass = '\\Symfony\\Component\\Console\\Output\\BufferedOutput';
                if (!class_exists($appClass) || !class_exists($inputClass) || !class_exists($outputClass)) {
                    throw new Exception('Composer classes indisponíveis');
                }
                $app = new $appClass();
                $app->setAutoExit(false);
                $args = [
                    'command' => 'install',
                    '--no-interaction' => true,
                    '--prefer-dist' => true,
                ];
                if ($ignoreAll) { $args['--ignore-platform-reqs'] = true; }
                $input = new $inputClass($args);
                $buffer = new $outputClass();
                $exitCode = $app->run($input, $buffer);
                $bufferOutput = $buffer->fetch();
            } catch (
                Throwable $t
            ) {
                $exitCode = 1;
                $bufferOutput = 'Falha ao executar Composer programaticamente: ' . $t->getMessage();
            }
            chdir($cwd);

            if ($exitCode === 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Dependências instaladas com sucesso! (em processo)',
                    'output' => $bufferOutput,
                    'ignored_platform_reqs' => $ignoreAll,
                    'extensoes_carregadas' => [
                        'gd' => extension_loaded('gd'),
                        'mbstring' => extension_loaded('mbstring'),
                    ],
                ]);
            } else {
                $notes = [];
                if ($ignoreAll) {
                    $notes[] = 'Composer foi executado com --ignore-platform-reqs porque gd/mbstring não estão carregadas neste processo do PHP.';
                }
                $iniCli = detectarPhpIniCli();
                if ($faltandoGd) {
                    $r = habilitarExtensaoNoIni($iniCli, 'gd');
                    if (!empty($r['message'])) $notes[] = $r['message'];
                }
                if ($faltandoMb) {
                    $r = habilitarExtensaoNoIni($iniCli, 'mbstring');
                    if (!empty($r['message'])) $notes[] = $r['message'];
                }
                $notes[] = 'Após habilitar extensões no php.ini, reinicie o Apache para carregá-las.';
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao instalar dependências (execução em processo).',
                    'output' => $bufferOutput,
                    'ignored_platform_reqs' => $ignoreAll,
                    'php_ini_cli' => $iniCli,
                    'notas' => $notes
                ]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
        }
        break;

    case 'verificar_instalacao':
        try {
            $vendorPath = $projectRoot . '/vendor/autoload.php';
            if (!file_exists($vendorPath)) {
                echo json_encode(['success' => false, 'message' => 'Vendor não encontrado']);
                break;
            }
            require_once $vendorPath;
            $classesOk = class_exists('PhpOffice\PhpPresentation\PhpPresentation') && class_exists('Mpdf\Mpdf');
            echo json_encode([
                'success' => $classesOk,
                'message' => $classesOk ? 'Todas as dependências estão instaladas!' : 'Algumas classes não foram encontradas'
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao verificar instalação: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Ação não reconhecida']);
}

/**
 * Instalador Automático de Dependências
 * Instala Composer automaticamente se necessário e executa composer install
 */

// Desabilita warnings/notices para não quebrar JSON e aumenta limites
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);
ini_set('memory_limit', '512M');
set_time_limit(300); // 5 minutos de timeout

header('Content-Type: application/json; charset=utf-8');

            if ($composerLocal) {
                // Rodar Composer dentro do mesmo processo PHP (evita spawn de processos no Apache/Windows)
                $cwd = getcwd();
                chdir($projectRoot);
                $bufferOutput = '';
                $exitCode = 1;
                $faltandoGd = !extension_loaded('gd');
                $faltandoMb = !extension_loaded('mbstring');
                $ignoreAll = ($faltandoGd || $faltandoMb);
                try {
                    require_once 'phar://composer.phar/src/bootstrap.php';
                    $appClass = '\\Composer\\Console\\Application';
                    $inputClass = '\\Symfony\\Component\\Console\\Input\\ArrayInput';
                    $outputClass = '\\Symfony\\Component\\Console\\Output\\BufferedOutput';
                    if (!class_exists($appClass) || !class_exists($inputClass) || !class_exists($outputClass)) {
                        throw new Exception('Composer classes indisponíveis');
                    }
                    $app = new $appClass();
                    $app->setAutoExit(false);
                    $args = [
                        'command' => 'install',
                        '--no-interaction' => true,
                        '--prefer-dist' => true,
                    ];
                    if ($ignoreAll) {
                        $args['--ignore-platform-reqs'] = true;
                    }
                    $input = new $inputClass($args);
                    $buffer = new $outputClass();
                    $exitCode = $app->run($input, $buffer);
                    $bufferOutput = $buffer->fetch();
                } catch (\\Throwable $t) {
                    $exitCode = 1;
                    $bufferOutput = 'Falha ao executar Composer programaticamente: ' . $t->getMessage();
                }
                chdir($cwd);

                if ($exitCode === 0) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Dependências instaladas com sucesso! (composer.phar em processo)',
                        'output' => $bufferOutput,
                        'ignored_platform_reqs' => $ignoreAll,
                        'extensoes_carregadas' => [
                            'gd' => extension_loaded('gd'),
                            'mbstring' => extension_loaded('mbstring'),
                        ]
                    ]);
                } else {
                    $notes = [];
                    if ($ignoreAll) {
                        $notes[] = 'Composer foi executado com --ignore-platform-reqs porque gd/mbstring não estão carregadas neste processo do PHP.';
                    }
                    $notes[] = 'Se você acabou de habilitar gd/mbstring no php.ini, reinicie o Apache para que este processo PHP carregue as extensões.';
                    echo json_encode([
                        'success' => false,
                        'message' => 'Erro ao instalar dependências (execução em processo).',
                        'output' => $bufferOutput,
                        'ignored_platform_reqs' => $ignoreAll,
                        'notas' => $notes
                    ]);
                }
    logMsg('Baixando instalador de ' . $installerUrl);
        
        // Usa curl se disponível, senão file_get_contents
        if (function_exists('curl_init')) {
            $ch = curl_init($installerUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            $installer = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200 || $installer === false) {
                logMsg('Erro ao baixar instalador. HTTP: ' . $httpCode);
                chdir($oldDir);
                return [
                    'success' => false,
                    'message' => 'Erro ao baixar instalador (HTTP ' . $httpCode . '). Verifique sua conexão.',
                    'etapas' => $etapas
                ];
            }
        } else {
            $installer = @file_get_contents($installerUrl);
            if ($installer === false) {
                logMsg('file_get_contents falhou ao baixar instalador.');
                chdir($oldDir);
                return [
                    'success' => false,
                    'message' => 'Não foi possível baixar o instalador. Verifique sua conexão com a internet.',
                    'etapas' => $etapas
                ];
            }
        }
        
        if (!@file_put_contents($installerPath, $installer)) {
            logMsg('Falha ao salvar composer-setup.php (permissão?)');
            chdir($oldDir);
            return [
                'success' => false,
                'message' => 'Não foi possível salvar o instalador. Verifique permissões.',
                'etapas' => $etapas
            ];
        }
        
        $etapas[] = "✓ Instalador baixado (" . strlen($installer) . " bytes)";
        logMsg('Instalador salvo com ' . strlen($installer) . ' bytes.');
        
        // Etapa 2: Executar instalador (pula verificação de hash para ser mais rápido)
        $etapas[] = "Instalando Composer...";
        $output = [];
        $returnCode = 1;
        
        // Força output buffer flush
        if (function_exists('ob_implicit_flush')) {
            ob_implicit_flush(true);
        }
        
        if (!funcDisponivel('exec')) {
            logMsg('exec() desabilitado. Tentando baixar composer.phar diretamente.');
            // Fallback: baixar composer.phar direto
            $resPhar = baixarComposerPhar($dir);
            if ($resPhar['success']) {
                @unlink($installerPath);
                chdir($oldDir);
                return [
                    'success' => true,
                    'message' => 'Composer (composer.phar) baixado com sucesso (sem exec).',
                    'etapas' => array_merge($etapas, ['Baixado composer.phar diretamente'])
                ];
            }
            @unlink($installerPath);
            chdir($oldDir);
            return [
                'success' => false,
                'message' => 'A função exec() está desabilitada no PHP e o download direto falhou. Instale o Composer manualmente.',
                'etapas' => $etapas,
                'output' => null
            ];
        }

    $phpExe = caminhoPhpExe();
    $cmd = '"' . $phpExe . '" composer-setup.php --quiet 2>&1';
    @exec($cmd, $output, $returnCode);
        
        if ($returnCode !== 0) {
            logMsg('composer-setup.php retornou código ' . $returnCode . ' Output: ' . implode(' | ', $output));
            @unlink($installerPath);
            chdir($oldDir);
            return [
                'success' => false,
                'message' => 'Erro ao instalar Composer. Código: ' . $returnCode,
                'etapas' => $etapas,
                'output' => implode("\n", $output)
            ];
        }
        
        $etapas[] = "✓ Composer instalado localmente (composer.phar)";
        logMsg('composer.phar criado com sucesso.');
        
        // Verifica se composer.phar foi criado
        if (!file_exists('composer.phar')) {
            logMsg('composer.phar não encontrado após execução do instalador.');
            @unlink($installerPath);
            chdir($oldDir);
            return [
                'success' => false,
                'message' => 'composer.phar não foi criado. Instale manualmente.',
                'etapas' => $etapas,
                'output' => implode("\n", $output)
            ];
        }
        
        // Limpar instalador
        @unlink($installerPath);
        $etapas[] = "✓ Arquivos temporários removidos";
        
        chdir($oldDir);
        
        return [
            'success' => true,
            'message' => 'Composer instalado com sucesso',
            'etapas' => $etapas
        ];
    } catch (Exception $e) {
        if (isset($oldDir)) {
            @chdir($oldDir);
        }
        logMsg('Exception: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erro: ' . $e->getMessage(),
            'etapas' => isset($etapas) ? $etapas : ['Erro inesperado']
        ];
    }
}

function baixarComposerPhar($dir) {
    $url = 'https://getcomposer.org/download/latest-stable/composer.phar';
    logMsg('Tentando baixar composer.phar de ' . $url);
    $target = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'composer.phar';
    $data = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        $data = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 200) {
            logMsg('Falha ao baixar composer.phar. HTTP ' . $code);
            $data = false;
        }
    }
    if ($data === false) {
        $data = @file_get_contents($url);
        if ($data === false) {
            logMsg('file_get_contents falhou para composer.phar');
            return ['success' => false, 'message' => 'Não foi possível baixar composer.phar'];
        }
    }
    if (@file_put_contents($target, $data) === false) {
        logMsg('Falha ao salvar composer.phar');
        return ['success' => false, 'message' => 'Não foi possível salvar composer.phar'];
    }
    logMsg('composer.phar salvo com ' . strlen($data) . ' bytes.');
    return ['success' => true, 'message' => 'composer.phar baixado'];
}

// Detecta php.ini utilizado no CLI (php.exe)
function detectarPhpIniCli() {
    $phpExe = caminhoPhpExe();
    $out = [];
    $code = 1;
    if (funcDisponivel('exec')) {
        @exec('"' . $phpExe . '" --ini 2>&1', $out, $code);
        if ($code === 0) {
            foreach ($out as $line) {
                if (stripos($line, 'Loaded Configuration File') !== false) {
                    $parts = explode(':', $line, 2);
                    if (count($parts) === 2) {
                        $path = trim($parts[1]);
                        if ($path && file_exists($path)) {
                            return $path;
                        }
                    }
                }
            }
        }
    }
    // Fallback comum no XAMPP
    $fallback = 'C:\\xampp\\php\\php.ini';
    return file_exists($fallback) ? $fallback : null;
}

// Habilita uma extensão no php.ini (descomenta/ajusta linha extension=)
function habilitarExtensaoNoIni($iniPath, $ext) {
    if (!$iniPath || !file_exists($iniPath) || !is_writable($iniPath)) {
        return ['success' => false, 'message' => 'php.ini não encontrado ou sem permissão de escrita'];
    }
    $content = @file_get_contents($iniPath);
    if ($content === false) {
        return ['success' => false, 'message' => 'Não foi possível ler php.ini'];
    }
    $backup = $iniPath . '.bak-ceu-' . date('YmdHis');
    @copy($iniPath, $backup);

    $patterns = [
        '/^\s*;\s*extension\s*=\s*(php_)?' . preg_quote($ext, '/') . '2?\b.*$/mi',
        '/^\s*extension\s*=\s*(php_)?' . preg_quote($ext, '/') . '2?\b.*$/mi',
    ];
    $replaced = false;
    foreach ($patterns as $i => $pattern) {
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, 'extension=' . $ext, $content);
            $replaced = true;
            break;
        }
    }
    if (!$replaced) {
        // Adiciona ao final
        $content .= "\nextension=" . $ext . "\n";
    }
    if (@file_put_contents($iniPath, $content) === false) {
        return ['success' => false, 'message' => 'Falha ao salvar php.ini após ajuste'];
    }
    return ['success' => true, 'message' => 'Extensão ' . $ext . ' habilitada no php.ini', 'backup' => $backup];
}

// Verifica se a extensão está carregada no CLI
function extensaoCarregadaCli($ext) {
    $phpExe = caminhoPhpExe();
    $out = [];
    $code = 1;
    if (funcDisponivel('exec')) {
        @exec('"' . $phpExe . '" -m 2>&1', $out, $code);
        if ($code === 0) {
            foreach ($out as $line) {
                if (trim(strtolower($line)) === strtolower($ext)) {
                    return true;
                }
            }
        }
    }
    return false;
}

switch ($action) {
    case 'verificar_composer':
        try {
            $composerGlobal = verificarComposer();
            $composerLocal = file_exists($projectRoot . '/composer.phar');
            
            echo json_encode([
                'success' => true,
                'composer_global' => $composerGlobal,
                'composer_local' => $composerLocal,
                'composer_disponivel' => $composerGlobal || $composerLocal,
                'message' => ($composerGlobal || $composerLocal)
                    ? 'Composer encontrado' 
                    : 'Composer não encontrado'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao verificar Composer: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'instalar_composer':
        try {
            // Primeiro tenta baixar composer.phar diretamente (mais robusto em ambiente web/Windows)
            $resultado = baixarComposerPhar($projectRoot);
            if (!$resultado['success']) {
                // Fallback: tenta o instalador clássico
                $resultado = instalarComposerLocal($projectRoot);
            }
            echo json_encode($resultado);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao instalar Composer: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'instalar_dependencias':
        try {
            // Verifica se composer está disponível (global ou local)
            $composerGlobal = verificarComposer();
            $composerLocal = file_exists($projectRoot . '/composer.phar');
            
            if (!$composerGlobal && !$composerLocal) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Composer não encontrado. Instale o Composer primeiro.'
                ]);
                exit;
            }
            
            // Verifica se composer.json existe
            if (!file_exists($projectRoot . '/composer.json')) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Arquivo composer.json não encontrado'
                ]);
                exit;
            }
            
            if ($composerLocal) {
                // Tenta rodar Composer dentro do mesmo processo PHP (sem criar processos filhos)
                $cwd = getcwd();
                chdir($projectRoot);
                $bufferOutput = '';
                $exitCode = 1;
                try {
                    require_once 'phar://composer.phar/src/bootstrap.php';
                    $appClass = '\\Composer\\Console\\Application';
                    $inputClass = '\\Symfony\\Component\\Console\\Input\\ArrayInput';
                    $outputClass = '\\Symfony\\Component\\Console\\Output\\BufferedOutput';
                    if (!class_exists($appClass) || !class_exists($inputClass) || !class_exists($outputClass)) {
                        throw new Exception('Composer classes indisponíveis');
                    }
                    $app = new $appClass();
                    $app->setAutoExit(false);
                    $input = new $inputClass([
                        'command' => 'install',
                        '--no-interaction' => true,
                        '--prefer-dist' => true,
                    ]);
                    $buffer = new $outputClass();
                    $exitCode = $app->run($input, $buffer);
                    $bufferOutput = $buffer->fetch();
                } catch (\Throwable $t) {
                    // Fallback: tenta via php.exe composer.phar
                    $phpExe = caminhoPhpExe();
                    $cmd = '"' . $phpExe . '" composer.phar install --no-interaction --prefer-dist';
                    $res = executarComando($cmd, $projectRoot);
                    $exitCode = $res['success'] ? 0 : 1;
                    $bufferOutput = $res['output'];
                }
                chdir($cwd);

                if ($exitCode !== 0) {
                    // Detecta faltas de extensões comuns e tenta corrigir
                    $faltandoGd = stripos($bufferOutput, 'ext-gd') !== false;
                    $faltandoMb = stripos($bufferOutput, 'ext-mbstring') !== false;
                    $etapasFix = [];
                    $ini = null;
                    if ($faltandoGd || $faltandoMb) {
                        $ini = detectarPhpIniCli();
                        if ($ini) {
                            if ($faltandoGd) {
                                $r = habilitarExtensaoNoIni($ini, 'gd');
                                $etapasFix[] = $r['message'] ?? 'Tentativa de habilitar gd';
                            }
                            if ($faltandoMb) {
                                $r = habilitarExtensaoNoIni($ini, 'mbstring');
                                $etapasFix[] = $r['message'] ?? 'Tentativa de habilitar mbstring';
                            }
                        } else {
                            $etapasFix[] = 'Não foi possível localizar o php.ini do CLI para habilitar extensões automaticamente.';
                        }
                    }

                    // Reexecuta via CLI (php.exe) após possíveis ajustes
                    $phpExe = caminhoPhpExe();
                    $cmdBase = '"' . $phpExe . '" composer.phar install --no-interaction --prefer-dist';
                    $res2 = executarComando($cmdBase, $projectRoot);
                    if ($res2['success']) {
                        echo json_encode([
                            'success' => true,
                            'message' => 'Dependências instaladas com sucesso' . ($etapasFix ? ' após ajustes no php.ini.' : '.'),
                            'output' => $res2['output'],
                            'fixes' => $etapasFix,
                            'php_ini' => $ini
                        ]);
                        break;
                    }

                    // Último recurso: ignorar requisitos de plataforma detectados
                    $ignoreFlags = [];
                    if ($faltandoGd) $ignoreFlags[] = '--ignore-platform-req=ext-gd';
                    if ($faltandoMb) $ignoreFlags[] = '--ignore-platform-req=ext-mbstring';
                    if (!empty($ignoreFlags)) {
                        $cmdIgnore = $cmdBase . ' ' . implode(' ', $ignoreFlags);
                        $res3 = executarComando($cmdIgnore, $projectRoot);
                        if ($res3['success']) {
                            echo json_encode([
                                'success' => true,
                                'message' => 'Dependências instaladas ignorando requisitos de plataforma (' . implode(', ', $ignoreFlags) . ').',
                                'output' => $res3['output'],
                                'fixes' => $etapasFix,
                                'warning' => 'Recomendado habilitar as extensões no PHP para uso pleno.',
                                'php_ini' => $ini
                            ]);
                            break;
                        }
                        echo json_encode([
                            'success' => false,
                            'message' => 'Erro ao instalar dependências mesmo após ajustes/ignorando requisitos de plataforma',
                            'output' => $res3['output'],
                            'fixes' => $etapasFix,
                            'php_ini' => $ini
                        ]);
                        break;
                    }

                    // Sem flags de ignore e sem sucesso: retorna erro original com contexto
                    echo json_encode([
                        'success' => false,
                        'message' => 'Erro ao instalar dependências com composer.phar',
                        'output' => $bufferOutput,
                        'fixes' => $etapasFix,
                        'php_ini' => $ini
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Dependências instaladas com sucesso! (composer.phar)',
                        'output' => $bufferOutput
                    ]);
                }
            } else {
                // Tenta via composer global no PATH
                $resultado = executarComando('composer install --no-interaction --prefer-dist', $projectRoot);
                if ($resultado['success']) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Dependências instaladas com sucesso! (composer global)',
                        'output' => $resultado['output']
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Erro ao instalar dependências via composer global',
                        'output' => $resultado['output']
                    ]);
                }
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'verificar_instalacao':
        try {
            $vendorPath = $projectRoot . '/vendor/autoload.php';
            
            if (!file_exists($vendorPath)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Vendor não encontrado'
                ]);
                exit;
            }
            
            require_once $vendorPath;
            
            // Verifica classes necessárias
            $classesOk = class_exists('PhpOffice\PhpPresentation\PhpPresentation') 
                      && class_exists('Mpdf\Mpdf');
            
            echo json_encode([
                'success' => $classesOk,
                'message' => $classesOk 
                    ? 'Todas as dependências estão instaladas!' 
                    : 'Algumas classes não foram encontradas'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao verificar instalação: ' . $e->getMessage()
            ]);
        }
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Ação não reconhecida'
        ]);
}
