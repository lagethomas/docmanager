<?php
use App\Core\Controller;
use App\Models\SettingsModel;
$page = $page ?? 'usuarios';
$settings = (new SettingsModel())->allAsMap();
?>

<!-- ===== Toast ===== -->
<div class="toast-host">
  <div id="toast" class="toast toast-success"></div>
</div>

<?php require __DIR__ . '/../components/modal_confirm.php'; ?>
<?php require __DIR__ . '/../components/modal_diff.php'; ?>

<div class="app-footer">Desenvolvido por Thomas Marcelino</div>

<script>
window.ME = <?php echo json_encode($user ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
window.CSRF_TOKEN = <?php echo json_encode($csrfToken ?? '', JSON_UNESCAPED_SLASHES); ?>;
window.SETTINGS = <?php echo json_encode($settings, JSON_UNESCAPED_SLASHES); ?>;
</script>
<script src="<?php echo Controller::asset('/assets/js/src/icons.js'); ?>"></script>
<script src="<?php echo Controller::asset('/assets/js/src/common.js'); ?>"></script>
<?php if (in_array($page, ['docs', 'docs_view', 'aprovacoes', 'home'], true)): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/marked/12.0.1/marked.min.js"></script>
<?php endif; ?>
<?php if ($page !== 'home'): ?>
<script src="<?php echo Controller::asset('/assets/js/src/' . $page . '.js'); ?>"></script>
<?php endif; ?>