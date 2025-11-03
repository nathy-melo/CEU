@echo off
chcp 6500
echo ================================
echo    CEU PWA - Controle Firewall
echo ================================
echo.
echo 1. Permitir Apache (para teste mobile)
echo 2. Bloquear Apache (seguranca)
echo 3. Verificar status
echo 4. Sair
echo.
set /p opcao="Escolha uma opcao (1-4): "

if "%opcao%"=="1" (
    echo Permitindo Apache no firewall...
    netsh advfirewall firewall set rule name="Apache HTTP Server" new action=allow
    echo ✅ Apache liberado! Acesse: http://SEU-IP-LOCAL/CEU/
    pause
)

if "%opcao%"=="2" (
    echo Bloqueando Apache no firewall...
    netsh advfirewall firewall set rule name="Apache HTTP Server" new action=block
    echo 🚫 Apache bloqueado! Acesso externo desabilitado.
    pause
)

if "%opcao%"=="3" (
    echo Status atual do Apache:
    netsh advfirewall firewall show rule name="Apache HTTP Server"
    pause
)

if "%opcao%"=="4" (
    exit
)