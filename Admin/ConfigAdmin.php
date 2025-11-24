<?php

/**
 * Configurações centralizadas do sistema administrativo CEU
 * Este arquivo centraliza todas as configurações de segurança e sistema
 */

// =====================================
// CONFIGURAÇÕES DE SEGURANÇA
// =====================================

// Credenciais administrativas (APENAS PARA TESTE)
define('ADMIN_USER_HASH', 'b99a59b57641f97c9aa0e5204343aa0ce55564c9c90cdb4cd11001e04123e048'); // infofriends
define('ADMIN_PASS_HASH', 'ef797c8118f02dfb649607dd5d3f8c7623048c9c063d532cc95c5ed7a898a64f'); // 12345678

// Configurações de sessão
define('ADMIN_SESSION_TIMEOUT', 3600); // 1 hora em segundos
define('ADMIN_SESSION_NAME', 'admin_ceu_session');

// Configurações de segurança
define('ENABLE_IP_CHECK', true);        // Verificar mudança de IP
define('ENABLE_ACCESS_LOG', true);      // Log de acessos
define('MAX_LOGIN_ATTEMPTS', 5);        // Máximo de tentativas de login
define('LOGIN_BLOCK_TIME', 900);        // Tempo de bloqueio em segundos (15 min)

// =====================================
// CONFIGURAÇÕES DO SISTEMA
// =====================================

// Caminhos do sistema
define('DB_PATH', '../BancoDados/');
define('ADMIN_PATH', './');
define('ROOT_PATH', '../');

// Configurações de códigos
define('CODE_LENGTH', 8);              // Tamanho dos códigos gerados
define('CODE_CHARSET', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'); // Caracteres permitidos
define('DEFAULT_CODES_TO_GENERATE', 10); // Códigos padrão para gerar

// =====================================
// CONFIGURAÇÕES DE INTERFACE
// =====================================

// Configurações de paginação
define('ITEMS_PER_PAGE', 20);

// Configurações de tema
define('ADMIN_THEME_COLOR', '#667eea');
define('ADMIN_SECONDARY_COLOR', '#764ba2');

// =====================================
// MENSAGENS DO SISTEMA
// =====================================

define('MSG_LOGIN_SUCCESS', 'Login realizado com sucesso.');
define('MSG_LOGIN_INVALID', 'Credenciais inválidas. Acesso negado.');
define('MSG_SESSION_EXPIRED', 'Sessão expirada. Faça login novamente.');
define('MSG_ACCESS_DENIED', 'Acesso não autorizado.');
define('MSG_IP_CHANGED', 'Sessão inválida por mudança de IP.');

// =====================================
// INFORMAÇÕES DO SISTEMA
// =====================================

define('SYSTEM_NAME', 'CEU - Sistema Administrativo');
define('SYSTEM_VERSION', '1.0.0');
define('SYSTEM_ENV', 'DEVELOPMENT'); // DEVELOPMENT, TESTING, PRODUCTION

// =====================================
// FUNÇÕES UTILITÁRIAS
// =====================================

/**
 * Gera hash SHA-256 para uma string
 */
function generateSecureHash($input)
{
    return hash('sha256', $input);
}

/**
 * Verifica se está em ambiente de desenvolvimento
 */
function isDevelopment()
{
    return SYSTEM_ENV === 'DEVELOPMENT';
}

/**
 * Registra log de atividade administrativa
 */
function logAdminActivity($activity, $details = '')
{
    if (ENABLE_ACCESS_LOG) {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        $logEntry = "[$timestamp] Admin Activity: $activity | IP: $ip | Details: $details | User-Agent: " . substr($userAgent, 0, 100);
        error_log($logEntry);
    }
}

/**
 * Formata tempo restante da sessão
 */
function formatSessionTime($seconds)
{
    if ($seconds <= 0) return '0 minutos';

    $minutes = floor($seconds / 60);
    $hours = floor($minutes / 60);
    $minutes = $minutes % 60;

    if ($hours > 0) {
        return "$hours h $minutes min";
    } else {
        return "$minutes minutos";
    }
}

// =====================================
// VALIDAÇÕES DE AMBIENTE
// =====================================

// Verificar se o PHP tem as extensões necessárias
if (!extension_loaded('mysqli')) {
    die('Erro: Extensão MySQLi não está disponível.');
}

if (!extension_loaded('session')) {
    die('Erro: Suporte a sessões não está disponível.');
}

// Log de inicialização do sistema
if (isDevelopment()) {
    logAdminActivity('CONFIG_LOADED', 'Configurações administrativas carregadas');
}
