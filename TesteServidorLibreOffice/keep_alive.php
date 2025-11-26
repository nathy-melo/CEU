<?php
/**
 * Keep-Alive para LibreOffice
 * Mantém LibreOffice aberto por 10 minutos
 * Execute via Windows Task Scheduler a cada 1 minuto
 * 
 * Tarefa agendada:
 * schtasks /create /tn "CEU_LibreOffice_KeepAlive" /tr "php C:\xampp\htdocs\CEU\Certificacao\keep_alive.php" /sc minute /mo 1 /f
 */

$lockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ceu_libreoffice_keepalive.lock';
$timeoutSegundos = 600; // 10 minutos

// Verifica se já tem um processo LibreOffice rodando
function verificarProcessoLibreOffice() {
    if (stripos(PHP_OS, 'WIN') === 0) {
        // Windows
        $output = shell_exec('tasklist /FI "IMAGENAME eq soffice.exe" 2>NUL');
        return strpos($output, 'soffice.exe') !== false;
    } else {
        // Linux/Mac
        $output = shell_exec('pgrep soffice');
        return !empty(trim($output));
    }
}

// Se LibreOffice não está rodando, inicia um processo vazio para "aquecimento"
if (!verificarProcessoLibreOffice()) {
    $tempDir = sys_get_temp_dir();
    $dummyFile = $tempDir . DIRECTORY_SEPARATOR . 'ceu_keepalive_dummy.odt';
    
    // Cria arquivo dummy se não existir
    if (!file_exists($dummyFile)) {
        file_put_contents($dummyFile, '');
    }
    
    // Inicia LibreOffice em background
    if (stripos(PHP_OS, 'WIN') === 0) {
        // Windows - encontra caminho do LibreOffice
        $sofficePaths = [
            'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files\\LibreOffice\\program\\scalc.exe',
        ];
        
        $soffice = null;
        foreach ($sofficePaths as $path) {
            if (file_exists($path)) {
                $soffice = $path;
                break;
            }
        }
        
        if ($soffice) {
            // Inicia LibreOffice em modo listener (servidor)
            $cmd = 'START /B "" "' . $soffice . '" --headless --accept="socket,host=localhost,port=2002;urp;" 2>NUL';
            popen($cmd, 'r');
            
            // Log
            file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ceu_keepalive.log', 
                date('Y-m-d H:i:s') . " - LibreOffice iniciado\n", FILE_APPEND);
        }
    } else {
        // Linux/Mac
        $cmd = 'soffice --headless --accept="socket,host=localhost,port=2002;urp;" > /dev/null 2>&1 &';
        popen($cmd, 'r');
        
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ceu_keepalive.log', 
            date('Y-m-d H:i:s') . " - LibreOffice iniciado\n", FILE_APPEND);
    }
}

// Cria/atualiza lock file com timestamp
touch($lockFile);

// Log de status
$logFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ceu_keepalive.log';
file_put_contents($logFile, 
    date('Y-m-d H:i:s') . " - Keep-Alive ativo (LibreOffice rodando: " . (verificarProcessoLibreOffice() ? 'SIM' : 'NÃO') . ")\n", 
    FILE_APPEND);

// Limpa old lock files (mais de 10 min)
$files = glob(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ceu_libreoffice_keepalive.lock*');
foreach ($files as $file) {
    if (time() - filemtime($file) > $timeoutSegundos) {
        @unlink($file);
        
        // Mata processo LibreOffice se expirou
        if (stripos(PHP_OS, 'WIN') === 0) {
            shell_exec('taskkill /IM soffice.exe /F 2>NUL');
        } else {
            shell_exec('pkill soffice 2>/dev/null');
        }
        
        file_put_contents($logFile, 
            date('Y-m-d H:i:s') . " - LibreOffice finalizado (timeout 10 min)\n", 
            FILE_APPEND);
    }
}

exit("OK");
