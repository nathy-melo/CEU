@echo off
REM Script para controlar Keep-Alive do LibreOffice
REM Permite ligar, desligar e verificar status

chcp 65001 >nul 2>&1

:menu
cls
echo.
echo ====================================================
echo  CEU - Controle Keep-Alive LibreOffice
echo ====================================================
echo.
echo 1. Ligar Keep-Alive (criar tarefa agendada)
echo 2. Desligar Keep-Alive (remover tarefa agendada)
echo 3. Verificar status
echo 4. Ver log de atividade
echo 5. Sair
echo.
set /p opcao="Escolha uma opcao (1-5): "

REM Verifica se está sendo executado como administrador
net session >nul 2>&1
if %errorLevel% neq 0 (
    cls
    echo.
    echo ====================================================
    echo ERRO: Privilégios de Administrador Necessários
    echo ====================================================
    echo.
    echo Este script deve ser executado como Administrador
    echo.
    echo Como fazer:
    echo  1. Clique com botão direito neste arquivo
    echo  2. Selecione "Executar como administrador"
    echo.
    pause
    exit /b 1
)

if "%opcao%"=="1" (
    call :ligar_keepalive
    goto menu
)

if "%opcao%"=="2" (
    call :desligar_keepalive
    goto menu
)

if "%opcao%"=="3" (
    call :verificar_status
    goto menu
)

if "%opcao%"=="4" (
    call :ver_log
    goto menu
)

if "%opcao%"=="5" (
    exit
)

echo.
echo Opcao invalida! Tente novamente.
pause
goto menu

REM ========== FUNCOES ==========

:ligar_keepalive
cls
echo.
echo ====================================================
echo Ativando Keep-Alive para LibreOffice
echo ====================================================
echo.

echo [1/3] Removendo tarefa anterior (se existir)...
schtasks /delete /tn "CEU_LibreOffice_KeepAlive" /f 2>nul
echo  ✓ Feito

echo.
echo [2/3] Criando nova tarefa agendada...
schtasks /create ^
    /tn "CEU_LibreOffice_KeepAlive" ^
    /tr "C:\xampp\php\php.exe C:\xampp\htdocs\CEU\Certificacao\keep_alive.php" ^
    /sc minute ^
    /mo 1 ^
    /f

if %errorLevel% equ 0 (
    echo  ✓ Tarefa criada com sucesso
    
    echo.
    echo [3/3] Iniciando Keep-Alive pela primeira vez...
    "C:\xampp\php\php.exe" C:\xampp\htdocs\CEU\Certificacao\keep_alive.php >nul 2>&1
    echo  ✓ Feito
    
    echo.
    echo ====================================================
    echo SUCESSO! Keep-Alive está ATIVO
    echo ====================================================
    echo.
    echo Configuração:
    echo  • Tarefa: CEU_LibreOffice_KeepAlive
    echo  • Frequência: A cada 1 minuto
    echo  • Timeout: 10 minutos de inatividade
    echo  • Log: %TEMP%\ceu_keepalive.log
    echo.
    echo Próximos passos:
    echo  • Gere um certificado (deve estar rápido!)
    echo  • Verifique o log: Opção 4 deste menu
    echo.
    pause
) else (
    echo.
    echo ====================================================
    echo ERRO ao criar tarefa!
    echo ====================================================
    echo.
    pause
)
exit /b 0

:desligar_keepalive
cls
echo.
echo ====================================================
echo Desativando Keep-Alive para LibreOffice
echo ====================================================
echo.

echo Confirma que deseja desligar? (S/N)
set /p confirmacao="Resposta: "

if /i "%confirmacao%"=="S" (
    echo.
    echo [1/2] Removendo tarefa agendada...
    schtasks /delete /tn "CEU_LibreOffice_KeepAlive" /f 2>nul
    if %errorLevel% equ 0 (
        echo  ✓ Tarefa removida
    ) else (
        echo  ! Tarefa não encontrada (pode estar já desativada)
    )
    
    echo.
    echo [2/2] Encerrando LibreOffice...
    taskkill /IM soffice.exe /F 2>nul
    if %errorLevel% equ 0 (
        echo  ✓ LibreOffice encerrado
    ) else (
        echo  ! LibreOffice não estava rodando
    )
    
    echo.
    echo ====================================================
    echo SUCESSO! Keep-Alive está INATIVO
    echo ====================================================
    echo.
    echo Keep-Alive foi desativado.
    echo Certificados usarão método tradicional (mais lento).
    echo.
) else (
    echo.
    echo Operação cancelada.
    echo.
)
pause
exit /b 0

:verificar_status
cls
echo.
echo ====================================================
echo Status do Keep-Alive LibreOffice
echo ====================================================
echo.

echo [1/3] Verificando tarefa agendada...
schtasks /query /tn "CEU_LibreOffice_KeepAlive" >nul 2>&1
if %errorLevel% equ 0 (
    echo  ✓ Tarefa ATIVA
    schtasks /query /tn "CEU_LibreOffice_KeepAlive" /v /fo list | findstr /C:"Status" /C:"Executar"
) else (
    echo  ✗ Tarefa INATIVA (não configurada)
)

echo.
echo [2/3] Verificando processo LibreOffice...
tasklist | findstr "soffice.exe" >nul 2>&1
if %errorLevel% equ 0 (
    echo  ✓ LibreOffice RODANDO
    tasklist | findstr "soffice.exe"
) else (
    echo  ✗ LibreOffice NÃO está rodando
    echo    (Pode iniciar na próxima execução agendada)
)

echo.
echo [3/3] Testando porta UNO Socket (2002)...
powershell -Command "$socket = New-Object System.Net.Sockets.TcpClient; try { $socket.Connect('localhost', 2002); Write-Host '  ✓ Porta 2002 RESPONDENDO (Keep-Alive está rápido!)'; $socket.Close() } catch { Write-Host '  ✗ Porta 2002 não respondendo (Keep-Alive pode estar inativo)' }" 2>nul

echo.
echo ====================================================
echo.
pause
exit /b 0

:ver_log
cls
echo.
echo ====================================================
echo Log de Atividade Keep-Alive
echo ====================================================
echo.

if exist "%TEMP%\ceu_keepalive.log" (
    echo Últimas 20 linhas do log:
    echo ────────────────────────────────────────────────────
    powershell -Command "Get-Content '%TEMP%\ceu_keepalive.log' | Select-Object -Last 20"
    echo ────────────────────────────────────────────────────
    echo.
    echo Para ver o log completo, abra:
    echo  %TEMP%\ceu_keepalive.log
) else (
    echo Log não encontrado!
    echo.
    echo Arquivo esperado: %TEMP%\ceu_keepalive.log
    echo Dica: Execute a Opção 1 para ativar Keep-Alive
)

echo.
pause
exit /b 0
