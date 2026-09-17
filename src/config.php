<?php
declare(strict_types=1);

/**
 * Carrega o .env via vlucas/phpdotenv, define constantes do ambiente e inicia a sessao.
 * Chamado pelo bootstrap.php. Nunca incluir diretamente via URL.
 */

use Dotenv\Dotenv;

if (!defined('CLI_MODE')) {
    define('CLI_MODE', false);
}

$dotenv = Dotenv::createUnsafeImmutable(dirname(__DIR__));
if (is_file(dirname(__DIR__) . '/.env')) {
    $dotenv->load();
}

define('ROOT_DIR', dirname(__DIR__));
define('SITE_URL', rtrim((string) ($_ENV['SITE_URL'] ?? ''), '/'));
define('SYSTEM_NAME', (string) ($_ENV['SYSTEM_NAME'] ?? 'DocManager'));
define('SYSTEM_VERSION', (string) ($_ENV['SYSTEM_VERSION'] ?? '1.0.0'));
define('APP_KEY', (string) ($_ENV['APP_KEY'] ?? ''));

define('DB_HOST', (string) ($_ENV['DB_HOST'] ?? '127.0.0.1'));
define('DB_PORT', (string) ($_ENV['DB_PORT'] ?? '3306'));
define('DB_NAME', (string) ($_ENV['DB_NAME'] ?? ''));
define('DB_USER', (string) ($_ENV['DB_USER'] ?? ''));
define('DB_PASS', (string) ($_ENV['DB_PASS'] ?? ''));
define('DB_CHARSET', (string) ($_ENV['DB_charset'] ?? $_ENV['DB_CHARSET'] ?? 'utf8mb4'));

define('APP_DEBUG', in_array((string) ($_ENV['APP_ENV'] ?? 'production'), ['dev', 'development', 'local', 'test'], true));

define('SMTP_HOST', (string) ($_ENV['SMTP_HOST'] ?? '127.0.0.1'));
define('SMTP_PORT', (int) ($_ENV['SMTP_PORT'] ?? 25));
define('SMTP_USER', (string) ($_ENV['SMTP_USER'] ?? ''));
define('SMTP_PASS', (string) ($_ENV['SMTP_PASS'] ?? ''));
define('SMTP_FROM', (string) ($_ENV['SMTP_FROM'] ?? 'nao-responda@docmanager.local'));
define('SMTP_FROM_NAME', (string) ($_ENV['SMTP_FROM_NAME'] ?? 'DocManager'));

date_default_timezone_set((string) ($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo'));

if (!CLI_MODE && session_status() === PHP_SESSION_NONE) {
    session_name((string) ($_ENV['SESSION_NAME'] ?? 'portal_quebras_session'));
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
        || (string) ($_ENV['APP_ENV'] ?? '') === 'production';
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => $isHttps,
    ]);
    session_start();

    $lifetime = max(60, (int) ($_ENV['SESSION_LIFETIME'] ?? '480')) * 60;
    if (isset($_SESSION['last_activity']) && (time() - (int) $_SESSION['last_activity']) > $lifetime) {
        $_SESSION = [];
        session_destroy();
        session_start();
    }
    $_SESSION['last_activity'] = time();
}
