<?php
declare(strict_types=1);

/**
 * Bootstrap da aplicação: autoload, erros, configuração, banco e aliases globais.
 * Todo entrypoint (public/index.php, bin/*.php) passa por aqui.
 */

if (!defined('CLI_MODE')) {
    define('CLI_MODE', false);
}

$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
} else {
    // Fallback: sem composer install, autoload PSR-4 manual (App\ => src/)
    spl_autoload_register(static function (string $class): void {
        $prefix = 'App\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }
    });
}

require_once __DIR__ . '/config.php';

error_reporting(E_ALL);
ini_set('display_errors', (CLI_MODE || APP_DEBUG) ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', ROOT_DIR . '/storage/logs/php-errors.log');

set_exception_handler(static function (Throwable $e): void {
    error_log('[EXCEPTION] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (CLI_MODE) {
        fwrite(STDERR, 'Erro: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => false,
        'error' => APP_DEBUG ? $e->getMessage() : 'Erro interno do servidor.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

require_once __DIR__ . '/Helpers/globals.php';

// Aliases globais (convenção do core)
class_alias(\App\Core\Database::class, 'DB');
class_alias(\App\Core\Auth::class, 'Auth');
class_alias(\App\Core\CSRF::class, 'CSRF');