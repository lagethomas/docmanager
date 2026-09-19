<?php
declare(strict_types=1);

/**
 * Declara para o PHPStan as constantes definidas em runtime pelo src/config.php.
 * Usado apenas em phpstan.neon (bootstrapFiles). Nunca incluir em produção.
 */

define('CLI_MODE', true);
define('ROOT_DIR', __DIR__);
define('SITE_URL', '');
define('SYSTEM_VERSION', '1.0.0');
define('APP_DEBUG', false);
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'portal_quebras');
define('DB_USER', 'portal_quebras');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('SYSTEM_NAME', 'DocManager');
define('SMTP_FROM', 'no-reply@docmanager.local');
define('SMTP_FROM_NAME', 'DocManager');