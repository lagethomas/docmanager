<?php
/**
 * Layout público (login, 404).
 * Espera: $title, $content.
 */
use App\Core\Controller;
use App\Core\CSRF;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?php echo e($title ?? SYSTEM_NAME); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo Controller::asset('/assets/css/gentelella.css'); ?>">
<link rel="stylesheet" href="<?php echo Controller::asset('/assets/css/modules/table.min.css'); ?>">
<link rel="stylesheet" href="<?php echo Controller::asset('/assets/css/modules/docs.min.css'); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<meta name="csrf-token" content="<?php echo e(CSRF::token()); ?>">
</head>
<body>
<?php echo $content ?? ''; ?>
<?php require __DIR__ . '/public_footer.php'; ?>
</body>
</html>