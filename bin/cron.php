<?php
declare(strict_types=1);

define('CLI_MODE', true);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Models\DocumentModel;

$options = getopt('', ['full', 'publish', 'expiration', 'help']);
$runAll = isset($options['full']) || (empty($options['publish']) && empty($options['expiration']) && empty($options['help']));

if (isset($options['help'])) {
    echo "DocManager - Executor de Tarefas Agendadas (Cron)" . PHP_EOL . PHP_EOL;
    echo "Uso: php bin/cron.php [OPÇÕES]" . PHP_EOL . PHP_EOL;
    echo "Opções disponíveis:" . PHP_EOL;
    echo "  --full        Executa todas as rotinas agendadas do sistema (Sequencial completo)" . PHP_EOL;
    echo "  --publish     Executa a publicação automática de documentos agendados" . PHP_EOL;
    echo "  --expiration  Executa a verificação de validade, notificações e e-mails de expiração" . PHP_EOL;
    echo "  --help        Exibe esta mensagem de ajuda" . PHP_EOL;
    exit(0);
}

echo sprintf("[CRON] === Iniciando execução de tarefas (%s) ===" . PHP_EOL, date('Y-m-d H:i:s'));

$model = new DocumentModel();

if ($runAll || isset($options['publish']) || isset($options['expiration'])) {
    $results = $model->processScheduledAndExpired();

    if ($runAll || isset($options['publish'])) {
        echo sprintf("[CRON:PUBLISH] %d documento(s) publicado(s) por agendamento." . PHP_EOL, $results['published']);
    }

    if ($runAll || isset($options['expiration'])) {
        echo sprintf("[CRON:EXPIRATION] %d notificação(ões) e e-mail(s) processado(s) para documentos expirados." . PHP_EOL, $results['expired_notified']);
    }
}

echo sprintf("[CRON] === Execução concluída (%s) ===" . PHP_EOL, date('Y-m-d H:i:s'));
