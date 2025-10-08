<?php
// Garante que os cookies criados tenham path '/'
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}
session_start();

// Limpa variáveis da sessão
$_SESSION = [];

// Remove cookie de sessão (PHPSESSID) e qualquer outro cookie do site
$cookieParams = session_get_cookie_params();
$sessionCookieName = session_name();
// Apaga o cookie da sessão especificamente (path '/')
setcookie($sessionCookieName, '', time() - 3600, '/');

// Apaga todos os cookies conhecidos no domínio atual (path '/')
if (!empty($_SERVER['HTTP_COOKIE'])) {
    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
    foreach ($cookies as $cookie) {
        $parts = explode('=', $cookie, 2);
        $name = trim($parts[0]);
        if ($name !== '') {
            setcookie($name, '', time() - 3600, '/');
        }
    }
}

// Destroi a sessão
if (session_id() !== '' || isset($_COOKIE[$sessionCookieName])) {
    session_unset();
    session_destroy();
}

// Evita reutilização do ID
session_write_close();

// Redireciona para a página pública de login
header('Location: ../PaginasPublicas/ContainerPublico.php?pagina=login');
exit;