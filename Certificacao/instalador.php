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
// Alvo agora é a pasta de bibliotecas dentro de Certificacao
$libsRoot = __DIR__ . '/bibliotecas';
if (!is_dir($libsRoot)) { @mkdir($libsRoot, 0775, true); }
// Registrar logs dentro da pasta de bibliotecas
$logPath = $libsRoot . '/instalador.log';

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

// ---------------------------- NOVAS FUNÇÕES (Git/Logs/Testes) ---------------------------------

function caminhoGitExe() {
    $candidatos = ['git', 'C:\\Program Files\\Git\\bin\\git.exe', 'C:\\Program Files\\Git\\cmd\\git.exe'];
    foreach ($candidatos as $g) {
        if ($g === 'git') {
            if (funcDisponivel('exec')) {
                @exec($g . ' --version', $o, $c);
                if ($c === 0) return $g;
            }
        } else if (@file_exists($g)) {
            return $g;
        }
    }
    return null;
}

function rmrf($path) {
    if (!file_exists($path)) return true;
    if (is_file($path) || is_link($path)) return @unlink($path);
    $ok = true;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $file) {
        $ok = $ok && ( $file->isDir() ? @rmdir($file->getRealPath()) : @unlink($file->getRealPath()) );
    }
    return $ok && @rmdir($path);
}

function limpezaInstaladorDir($dir) {
    $res = [ 'success' => true, 'steps' => [] ];
    // 1) Remover vendor/
    if (is_dir($dir . '/vendor')) {
        $ok = rmrf($dir . '/vendor');
        $res['steps'][] = ['acao' => 'remover vendor', 'path' => $dir . '/vendor', 'success' => $ok];
        $res['success'] = $res['success'] && $ok;
    }
    // 2) Remover composer.phar local
    if (file_exists($dir . '/composer.phar')) {
        $ok = @unlink($dir . '/composer.phar');
        $res['steps'][] = ['acao' => 'remover composer.phar', 'path' => $dir . '/composer.phar', 'success' => $ok];
        $res['success'] = $res['success'] && $ok;
    }
    // 3) Reverter composer.lock (se estiver versionado)
    $git = caminhoGitExe();
    if ($git && funcDisponivel('exec') && file_exists($dir . '/composer.lock')) {
        $gitCmd = (strpos($git, ' ') !== false ? '"' . $git . '"' : $git);
        $resetLock = executarComando($gitCmd . ' checkout -- composer.lock', $dir);
        if (!$resetLock['success']) {
            // fallback para reset scoped
            $resetLock = executarComando($gitCmd . ' reset --hard HEAD -- composer.lock', $dir);
        }
        $res['steps'][] = ['acao' => 'reverter composer.lock', 'success' => $resetLock['success'], 'detalhes' => $resetLock];
        $res['success'] = $res['success'] && $resetLock['success'];
    }
    logMsg('Limpeza (escopo bibliotecas): ' . ($res['success'] ? 'OK' : 'FALHOU'));
    return $res;
}

function tailFile($path, $lines = 200) {
    if (!file_exists($path)) return '';
    $data = @file($path);
    if ($data === false) return '';
    $slice = array_slice($data, -max(1, (int)$lines));
    return implode('', $slice);
}

// Ações --------------------------------------------------------------------

switch ($action) {
    case 'verificar_composer':
        try {
            $composerGlobal = verificarComposer();
            $composerLocal = file_exists($libsRoot . '/composer.phar');
            $msg = 'Composer não encontrado';
            if ($composerGlobal) {
                $msg = 'Composer global encontrado';
            } elseif ($composerLocal) {
                $msg = 'Composer local encontrado em bibliotecas';
            }
            echo json_encode([
                'success' => true,
                'composer_global' => $composerGlobal,
                'composer_local' => $composerLocal,
                'composer_disponivel' => $composerGlobal || $composerLocal,
                'libs_root' => $libsRoot,
                'message' => $msg
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao verificar Composer: ' . $e->getMessage()]);
        }
        break;

    case 'instalar_composer':
        try {
            $res = baixarComposerPhar($libsRoot);
            if (!$res['success']) {
                $res = instalarComposerLocal($libsRoot);
            }
            echo json_encode($res);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao instalar Composer: ' . $e->getMessage()]);
        }
        break;

    case 'instalar_dependencias':
        try {
            $composerLocal = file_exists($libsRoot . '/composer.phar');
            if (!$composerLocal) {
                echo json_encode([
                    'success' => false,
                    'message' => 'composer.phar não encontrado em bibliotecas. Use a ação instalar_composer primeiro.'
                ]);
                break;
            }
            if (!file_exists($libsRoot . '/composer.json')) {
                echo json_encode(['success' => false, 'message' => 'Arquivo composer.json não encontrado em bibliotecas']);
                break;
            }
            // Executa Composer dentro do mesmo processo PHP, no diretório de bibliotecas
            $cwd = getcwd();
            chdir($libsRoot);
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
                $args = [ 'command' => 'install', '--no-interaction' => true, '--prefer-dist' => true ];
                if ($ignoreAll) { $args['--ignore-platform-reqs'] = true; }
                $input = new $inputClass($args);
                $buffer = new $outputClass();
                $exitCode = $app->run($input, $buffer);
                $bufferOutput = $buffer->fetch();
            } catch (Throwable $t) {
                $exitCode = 1; $bufferOutput = 'Falha ao executar Composer programaticamente: ' . $t->getMessage();
            }
            chdir($cwd);

            // Limpeza alvo somente na pasta bibliotecas
            $cleanup = limpezaInstaladorDir($libsRoot);

            echo json_encode([
                'success' => ($exitCode === 0),
                'message' => $exitCode === 0 ? 'Dependências instaladas. Limpando artefatos do instalador.' : 'Falha ao instalar dependências. Mesmo assim, limpeza executada.',
                'output' => $bufferOutput,
                'ignored_platform_reqs' => $ignoreAll,
                'extensoes_carregadas' => [ 'gd' => extension_loaded('gd'), 'mbstring' => extension_loaded('mbstring') ],
                'cleanup' => $cleanup,
                'libs_root' => $libsRoot
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
        }
        break;

    case 'verificar_instalacao':
        try {
            $vendorPath = $libsRoot . '/vendor/autoload.php';
            if (!file_exists($vendorPath)) {
                echo json_encode(['success' => false, 'message' => 'Vendor não encontrado em bibliotecas']);
                break;
            }
            require_once $vendorPath;
            $classesOk = class_exists('PhpOffice\\PhpPresentation\\PhpPresentation') && class_exists('Mpdf\\Mpdf');
            echo json_encode([
                'success' => $classesOk,
                'message' => $classesOk ? 'Todas as dependências estão instaladas!' : 'Algumas classes não foram encontradas',
                'libs_root' => $libsRoot
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao verificar instalação: ' . $e->getMessage()]);
        }
        break;

    case 'status_git':
        try {
            $git = caminhoGitExe();
            if (!$git || !funcDisponivel('exec')) {
                echo json_encode(['success' => false, 'message' => 'git não disponível no servidor ou exec() desabilitado.']);
                break;
            }
            $gitCmd = (strpos($git, ' ') !== false ? '"' . $git . '"' : $git);
            $version = executarComando($gitCmd . ' --version', $libsRoot);
            $status = executarComando($gitCmd . ' --no-pager status --porcelain', $libsRoot);
            echo json_encode(['success' => true, 'version' => $version, 'status' => $status, 'dir' => $libsRoot]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro status_git: ' . $e->getMessage()]);
        }
        break;

    case 'limpar_instalador':
        try {
            $removerArtefatos = isset($_POST['remover_artefatos']) && ($_POST['remover_artefatos'] === '1' || $_POST['remover_artefatos'] === 'true');
            $res = limpezaInstaladorDir($libsRoot);
            $removidos = [];
            if ($removerArtefatos) {
                $artefatos = [$logPath];
                foreach ($artefatos as $a) {
                    if (file_exists($a)) { if (@unlink($a)) { $removidos[] = $a; } }
                }
            }
            echo json_encode(['success' => $res['success'], 'message' => 'Limpeza executada', 'detalhes' => $res, 'removidos' => $removidos, 'libs_root' => $libsRoot]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro limpar_instalador: ' . $e->getMessage()]);
        }
        break;

    case 'criar_log':
        try {
            $mensagem = isset($_POST['mensagem']) ? (string)$_POST['mensagem'] : '';
            if ($mensagem === '') { echo json_encode(['success' => false, 'message' => 'mensagem obrigatória']); break; }
            logMsg('[MANUAL] ' . $mensagem);
            echo json_encode(['success' => true, 'message' => 'Log gravado']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro criar_log: ' . $e->getMessage()]);
        }
        break;

    case 'ler_log':
        try {
            $linhas = isset($_POST['linhas']) ? (int)$_POST['linhas'] : 200;
            $conteudo = tailFile($logPath, $linhas);
            echo json_encode(['success' => true, 'linhas' => $linhas, 'conteudo' => $conteudo]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ler_log: ' . $e->getMessage()]);
        }
        break;

    case 'limpar_log':
        try {
            @file_put_contents($logPath, '');
            echo json_encode(['success' => true, 'message' => 'Log limpo']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro limpar_log: ' . $e->getMessage()]);
        }
        break;

    case 'auto_test':
        try {
            $composerGlobal = verificarComposer();
            $composerLocal = file_exists($libsRoot . '/composer.phar');
            $r1 = ['success' => true, 'composer_global' => $composerGlobal, 'composer_local' => $composerLocal, 'composer_disponivel' => $composerGlobal || $composerLocal];
            $r2 = $composerLocal ? ['success' => true, 'message' => 'composer.phar já presente'] : baixarComposerPhar($libsRoot);
            if (!$r2['success']) { $r2 = instalarComposerLocal($libsRoot); }
            // instalar_dependencias resumido
            $cwd = getcwd(); chdir($libsRoot);
            $bufferOutput = ''; $exitCode = 1; $faltandoGd = !extension_loaded('gd'); $faltandoMb = !extension_loaded('mbstring'); $ignoreAll = ($faltandoGd || $faltandoMb);
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
                $args = ['command' => 'install', '--no-interaction' => true, '--prefer-dist' => true];
                if ($ignoreAll) { $args['--ignore-platform-reqs'] = true; }
                $input = new $inputClass($args);
                $buffer = new $outputClass();
                $exitCode = $app->run($input, $buffer); $bufferOutput = $buffer->fetch();
            } catch (Throwable $t) { $exitCode = 1; $bufferOutput = 'Falha ao executar Composer programaticamente: ' . $t->getMessage(); }
            chdir($cwd);
            $cleanup = limpezaInstaladorDir($libsRoot);
            $r3 = ['success' => ($exitCode === 0), 'output' => $bufferOutput, 'ignored_platform_reqs' => $ignoreAll, 'cleanup' => $cleanup];
            echo json_encode(['success' => true, 'etapas' => ['verificar_composer' => $r1, 'instalar_composer' => $r2, 'instalar_dependencias' => $r3], 'libs_root' => $libsRoot]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro auto_test: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Ação não reconhecida']);
}
