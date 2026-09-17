<?php
declare(strict_types=1);

define('CLI_MODE', true);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Database;

$db = Database::getInstance();

$db->exec(
    'CREATE TABLE IF NOT EXISTS cp_migrations (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_migrations_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$migrations = require dirname(__DIR__) . '/database/migrations.php';
if (!is_array($migrations)) {
    fwrite(STDERR, 'Arquivo de migrações inválido.' . PHP_EOL);
    exit(1);
}

$stmt = $db->query('SELECT name FROM cp_migrations');
$applied = $stmt->fetchAll(\PDO::FETCH_COLUMN);
$appliedMap = is_array($applied) ? array_flip($applied) : [];

$count = 0;
foreach ($migrations as $name => $sql) {
    $key = (string) $name;
    if (isset($appliedMap[$key])) {
        continue;
    }
    $statements = array_filter(array_map('trim', explode(';', (string) $sql)));
    foreach ($statements as $statement) {
        if ($statement === '') {
            continue;
        }
        $db->exec($statement);
    }
    $insert = $db->prepare('INSERT INTO cp_migrations (name) VALUES (?)');
    $insert->execute([$key]);
    echo 'Aplicada migração: ' . $key . PHP_EOL;
    $count++;
}

echo $count === 0 ? 'Nenhuma migração pendente.' . PHP_EOL : $count . ' migração(ões) aplicada(s).' . PHP_EOL;