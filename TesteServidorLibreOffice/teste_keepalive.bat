@echo off
REM Script de teste do Keep-Alive para LibreOffice
REM Executa os testes de verificação do sistema

chcp 65001 >nul 2>&1

:menu
cls
echo.
echo ════════════════════════════════════════════════════
echo TESTE DO SISTEMA KEEP-ALIVE PARA LIBREOFFICE
echo ════════════════════════════════════════════════════
echo.
echo 1. Executar todos os testes
echo 2. Teste 1: Verificar LibreOffice instalado
echo 3. Teste 2: Verificar PHP disponível
echo 4. Teste 3: Verificar arquivo keep_alive.php
echo 5. Teste 4: Executar keep_alive.php
echo 6. Teste 5: Ver arquivo de log
echo 7. Teste 6: Verificar soffice.exe rodando
echo 8. Teste 7: Verificar tarefa agendada
echo 9. Teste 8: Testar porta 2002
echo 10. Sair
echo.
set /p opcao="Escolha uma opcao (1-10): "

if "%opcao%"=="1" goto todos_testes
if "%opcao%"=="2" goto teste1
if "%opcao%"=="3" goto teste2
if "%opcao%"=="4" goto teste3
if "%opcao%"=="5" goto teste4
if "%opcao%"=="6" goto teste5
if "%opcao%"=="7" goto teste6
if "%opcao%"=="8" goto teste7
if "%opcao%"=="9" goto teste8
if "%opcao%"=="10" exit

echo Opcao invalida!
pause
goto menu

REM ========== TODOS OS TESTES ==========
:todos_testes
cls
echo.
echo ════════════════════════════════════════════════════
echo EXECUTANDO TODOS OS TESTES
echo ════════════════════════════════════════════════════
echo.

call :teste1_exec
echo.
call :teste2_exec
echo.
call :teste3_exec
echo.
call :teste4_exec
echo.
call :teste5_exec
echo.
call :teste6_exec
echo.
call :teste7_exec
echo.
call :teste8_exec

echo.
echo ════════════════════════════════════════════════════
echo RESUMO DOS TESTES
echo ════════════════════════════════════════════════════
echo.
echo Se todos os testes passaram (✓):
echo  → Sistema está pronto para usar
echo  → Certificados serão gerados 4-5x mais rápido
echo.
echo Se algum teste falhou (✗):
echo  → Leia a mensagem de erro acima
echo  → Execute setup_keepalive.bat como Administrador
echo  → Verifique se LibreOffice está instalado
echo  → Consulte SETUP_KEEPALIVE.md para troubleshooting
echo.
echo.
echo Pressione qualquer tecla para voltar ao menu...
pause >nul
goto menu

REM ========== TESTE 1 ==========
:teste1
cls
call :teste1_exec
echo.
pause
goto menu

:teste1_exec
echo [TESTE 1] Verificando instalação do LibreOffice...
if exist "C:\Program Files\LibreOffice\program\soffice.exe" (
    echo  ✓ LibreOffice encontrado em: C:\Program Files\LibreOffice\
    exit /b 0
)
if exist "C:\Program Files (x86)\LibreOffice\program\soffice.exe" (
    echo  ✓ LibreOffice encontrado em: C:\Program Files (x86)\LibreOffice\
    exit /b 0
)
echo  ✗ ERRO: LibreOffice não encontrado!
echo    Instale LibreOffice em um dos locais acima.
exit /b 0

REM ========== TESTE 2 ==========
:teste2
cls
call :teste2_exec
echo.
pause
goto menu

:teste2_exec
echo [TESTE 2] Verificando se PHP está disponível...
"C:\xampp\php\php.exe" -v >nul 2>&1
if %errorLevel% equ 0 (
    echo  ✓ PHP disponível
    "C:\xampp\php\php.exe" -v | findstr "PHP"
) else (
    echo  ✗ ERRO: PHP não encontrado em C:\xampp\php\
    echo    Verifique se XAMPP está instalado corretamente.
)
exit /b 0

REM ========== TESTE 3 ==========
:teste3
cls
call :teste3_exec
echo.
pause
goto menu

:teste3_exec
echo [TESTE 3] Verificando arquivo keep_alive.php...
if exist "C:\xampp\htdocs\CEU\Certificacao\keep_alive.php" (
    echo  ✓ Arquivo encontrado em: C:\xampp\htdocs\CEU\Certificacao\keep_alive.php
    echo    Tamanho: 
    for %%A in ("C:\xampp\htdocs\CEU\Certificacao\keep_alive.php") do echo    %%~zA bytes
) else (
    echo  ✗ ERRO: keep_alive.php não encontrado!
    echo    Caminho esperado: C:\xampp\htdocs\CEU\Certificacao\keep_alive.php
)
exit /b 0

REM ========== TESTE 4 ==========
:teste4
cls
call :teste4_exec
echo.
pause
goto menu

:teste4_exec
echo [TESTE 4] Executando keep_alive.php manualmente...
cd /d "C:\xampp\htdocs\CEU\Certificacao" 2>nul || (
    echo  ✗ ERRO: Não consegue acessar C:\xampp\htdocs\CEU\Certificacao
    exit /b 1
)
"C:\xampp\php\php.exe" keep_alive.php >nul 2>&1
if %errorLevel% equ 0 (
    echo  ✓ Script executado com sucesso
    echo    Verifique o log em: %TEMP%\ceu_keepalive.log
) else (
    echo  ✗ ERRO ao executar script!
    echo    Execute como administrador e verifique permissões.
)
exit /b 0

REM ========== TESTE 5 ==========
:teste5
cls
call :teste5_exec
echo.
pause
goto menu

:teste5_exec
echo [TESTE 5] Verificando arquivo de log...
if exist "%TEMP%\ceu_keepalive.log" (
    echo  ✓ Log encontrado em: %TEMP%\ceu_keepalive.log
    echo    Últimas 10 linhas do log:
    echo.
    powershell -Command "Get-Content '%TEMP%\ceu_keepalive.log' | Select-Object -Last 10 | foreach { Write-Host '    ' $_ }"
) else (
    echo  ✗ Log não encontrado em %TEMP%\ceu_keepalive.log
    echo    Execute keep_alive.php primeiro (Teste 4)
)
exit /b 0

REM ========== TESTE 6 ==========
:teste6
cls
call :teste6_exec
echo.
pause
goto menu

:teste6_exec
echo [TESTE 6] Verificando se LibreOffice está rodando...
tasklist | findstr "soffice.exe" >nul 2>&1
if %errorLevel% equ 0 (
    echo  ✓ soffice.exe está rodando
    echo.
    tasklist | findstr "soffice.exe"
) else (
    echo  ✗ soffice.exe NÃO está rodando
    echo    Isso é normal se o Keep-Alive acabou de iniciar.
    echo    Ele começará a rodar na próxima execução agendada.
)
exit /b 0

REM ========== TESTE 7 ==========
:teste7
cls
call :teste7_exec
echo.
pause
goto menu

:teste7_exec
echo [TESTE 7] Verificando tarefa agendada...
schtasks /query /tn "CEU_LibreOffice_KeepAlive" >nul 2>&1
if %errorLevel% equ 0 (
    echo  ✓ Tarefa "CEU_LibreOffice_KeepAlive" existe
    echo.
    schtasks /query /tn "CEU_LibreOffice_KeepAlive" /v /fo list | findstr /C:"Status" /C:"HostName"
) else (
    echo  ✗ Tarefa "CEU_LibreOffice_KeepAlive" NÃO está configurada
    echo    Execute: setup_keepalive.bat como Administrador
    echo    Escolha: Opção 1 (Ligar Keep-Alive)
)
exit /b 0

REM ========== TESTE 8 ==========
:teste8
cls
call :teste8_exec
echo.
pause
goto menu

:teste8_exec
echo [TESTE 8] Testando conexão UNO Socket (porta 2002)...
powershell -Command "try { $socket = New-Object System.Net.Sockets.TcpClient; $socket.Connect('localhost', 2002); Write-Host '  ✓ Porta 2002 respondendo (Keep-Alive ativo!)'; $socket.Close() } catch { Write-Host '  ✗ Porta 2002 não respondendo (Keep-Alive pode estar inativo)' }" 2>nul
exit /b 0
