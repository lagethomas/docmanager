<?php
declare(strict_types=1);

define('CLI_MODE', true);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Helpers\SecurityHelper;
use App\Models\UserModel;

$defaultUsers = [
    'admin' => ['Administrador Geral', 'admin@1234', 'Admin', 'ativo'],
];

$model = new UserModel();
foreach ($defaultUsers as $login => [$nome, $senha, $role, $status]) {
    if ($model->loginExists($login)) {
        echo "Usuario {$login} ja existe." . PHP_EOL;
        continue;
    }
    $model->create($login, $nome, SecurityHelper::hashPassword($senha), $role, $status);
    echo "Criado: {$login} ({$nome}, {$role})" . PHP_EOL;
}
