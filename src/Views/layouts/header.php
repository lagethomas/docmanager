<?php
/**
 * Layout do painel do Core Basic (Gentelella).
 * Espera: $title, $user, $csrfToken, $page, $content.
 */
use App\Core\Controller;
use App\Core\CSRF;
$page = $page ?? 'usuarios';
$u = $user ?? null;
function navActive(string $current, string $target): string
{
    return $current === $target ? ' active' : '';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?php echo e($title ?? 'DocManager'); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo Controller::asset('/assets/css/gentelella.css'); ?>">
<link rel="stylesheet" href="<?php echo Controller::asset('/assets/css/modules/table.min.css'); ?>">
<link rel="stylesheet" href="<?php echo Controller::asset('/assets/css/modules/docs.min.css'); ?>">
<link rel="stylesheet" href="<?php echo Controller::asset('/assets/css/modules/reports.min.css'); ?>">

<meta name="csrf-token" content="<?php echo e(CSRF::token()); ?>">
</head>
<body>

<!-- ===== Sidebar ===== -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon">D</div>
    <div class="brand-name">Doc<small>Manager</small></div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-group">
      <div class="nav-label">Geral</div>
      <a class="nav-link<?php echo navActive($page, 'home'); ?>" href="<?php echo e(Controller::url('/')); ?>" data-rail-label="Início">
        <?php echo icon('home', ['class' => 'icon', 'size' => 16]); ?>
        <span class="nav-text">Início</span>
      </a>
      <a class="nav-link<?php echo navActive($page, 'docs'); ?>" href="<?php echo e(Controller::url('/docs')); ?>" data-rail-label="Documentação">
        <?php echo icon('doc', ['class' => 'icon', 'size' => 16]); ?>
        <span class="nav-text">Documentação</span>
      </a>
      <a class="nav-link<?php echo navActive($page, 'issues'); ?>" href="<?php echo e(Controller::url('/issues')); ?>" data-rail-label="Issues">
        <?php echo icon('alert', ['class' => 'icon', 'size' => 16]); ?>
        <span class="nav-text">Issues</span>
        <span class="nav-badge" id="sidebarIssuesBadge" hidden>0</span>
      </a>
      <?php if (($u['role'] ?? '') === 'Admin' || ($u['role'] ?? '') === 'Aprovador'): ?>
      <a class="nav-link<?php echo navActive($page, 'aprovacoes'); ?>" href="<?php echo e(Controller::url('/aprovacoes')); ?>" data-rail-label="Aprovações">
        <?php echo icon('checkCircle', ['class' => 'icon', 'size' => 16]); ?>
        <span class="nav-text">Aprovações</span>
        <span class="nav-badge" id="sidebarPendingBadge" hidden>0</span>
      </a>
      <a class="nav-link<?php echo navActive($page, 'relatorios'); ?>" href="<?php echo e(Controller::url('/relatorios')); ?>" data-rail-label="Relatórios">
        <?php echo icon('chart', ['class' => 'icon', 'size' => 16]); ?>
        <span class="nav-text">Relatórios</span>
      </a>
      <?php endif; ?>
    </div>
    <div class="nav-group" id="navAdminGroup"<?php echo ($u['role'] ?? '') !== 'Admin' ? ' hidden' : ''; ?>>
      <div class="nav-label">Administração</div>
      <a class="nav-link<?php echo navActive($page, 'usuarios'); ?>" href="<?php echo e(Controller::url('/usuarios')); ?>" data-rail-label="Administração">
        <?php echo icon('users', ['class' => 'icon', 'size' => 16]); ?>
        <span class="nav-text">Usuários</span>
      </a>
      <a class="nav-link<?php echo navActive($page, 'localizacoes'); ?>" href="<?php echo e(Controller::url('/localizacoes')); ?>" data-rail-label="Localizações">
        <?php echo icon('location', ['class' => 'icon', 'size' => 16]); ?>
        <span class="nav-text">Localizações</span>
      </a>
      <a class="nav-link<?php echo navActive($page, 'areas'); ?>" href="<?php echo e(Controller::url('/areas')); ?>" data-rail-label="Áreas / Setores">
        <?php echo icon('area', ['class' => 'icon', 'size' => 16]); ?>
        <span class="nav-text">Áreas / Setores</span>
      </a>
      <a class="nav-link<?php echo navActive($page, 'configuracoes'); ?>" href="<?php echo e(Controller::url('/configuracoes')); ?>" data-rail-label="Configurações">
        <?php echo icon('settings', ['class' => 'icon', 'size' => 16]); ?>
        <span class="nav-text">Configurações</span>
      </a>
      <a class="nav-link<?php echo navActive($page, 'sistema_docs'); ?>" href="<?php echo e(Controller::url('/sistema-docs')); ?>" data-rail-label="Manual">
        <?php echo icon('help', ['class' => 'icon', 'size' => 16]); ?>
        <span class="nav-text">Manual do Sistema</span>
      </a>
    </div>
  </nav>
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="avatar" id="uAvatar">U</div>
      <div class="sidebar-user-info">
        <div class="name" id="uName">Usuário</div>
        <div class="role" id="uRole">Função</div>
      </div>
      <button class="more-btn" onclick="logout()" title="Sair do portal">
        <?php echo icon('logout', ['size' => 16]); ?>
      </button>
    </div>
  </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop" hidden onclick="toggleSidebar()"></div>

<!-- ===== Topbar ===== -->
<header class="topbar">
  <div class="topbar-left">
    <button class="sidebar-toggle" onclick="toggleSidebar()" title="Alternar menu lateral" aria-label="Alternar menu lateral">
      <?php echo icon('menu', ['size' => 18]); ?>
    </button>
  </div>
  <div class="topbar-center" style="flex: 1; max-width: 540px; margin: 0 auto; display: flex; justify-content: center; position: relative;">
    <div class="topbar-global-search" style="position: relative; width: 100%;">
      <div style="position: relative; display: flex; align-items: center;">
        <?php echo icon('search', ['style' => 'position: absolute; left: 10px; width: 14px; height: 14px; color: var(--text-muted); pointer-events: none;']); ?>
        <input type="text" id="globalDocSearchInput" class="form-control" placeholder="Buscar documentos no sistema... (/)" autocomplete="off" style="padding-left: 30px; padding-right: 28px; height: 34px; font-size: 0.82rem; border-radius: 8px; width: 100%; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color);" />
        <button type="button" id="globalDocSearchClear" onclick="clearGlobalDocSearch()" hidden style="position: absolute; right: 8px; background: none; border: none; color: var(--text-muted); font-size: 0.9rem; cursor: pointer; padding: 2px;">✕</button>
      </div>
      <div id="globalDocSearchDropdown" class="tb-dropdown" hidden style="position: absolute; top: 40px; left: 0; width: 100%; max-height: 360px; overflow-y: auto; z-index: 1050; padding: 0; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.15);"></div>
    </div>
  </div>
  <div class="topbar-right">
    <!-- Sino de Notificações -->
    <div class="notif-wrap" style="position: relative;">
      <button class="tb-btn notif-toggle" id="notifBtn" title="Notificações" aria-label="Notificações" style="position: relative;">
        <?php echo icon('bell', ['size' => 16]); ?>
        <span class="notif-badge" id="notifBadge" hidden>0</span>
      </button>
      <div class="tb-dropdown notif-dropdown" id="notifDropdown" hidden style="width: 320px; right: 0; padding: 0;">
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-color); font-weight: 700; font-size: 0.9rem;">
          <span>Notificações</span>
          <button onclick="markAllNotificationsRead()" style="background: none; border: none; color: var(--primary); font-size: 0.75rem; cursor: pointer; font-weight: 600;">Marcar todas como lidas</button>
        </div>
        <div id="notifList" style="max-height: 320px; overflow-y: auto; padding: 0.5rem 0;">
          <div style="padding: 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.85rem;">Carregando...</div>
        </div>
      </div>
    </div>

    <button class="tb-btn theme-toggle" onclick="toggleTheme()" title="Alternar tema claro/escuro" aria-label="Alternar tema">
      <?php echo icon('sun', ['class' => 'theme-icon-light', 'size' => 16]); ?>
      <?php echo icon('moon', ['class' => 'theme-icon-dark', 'size' => 16]); ?>
    </button>
    <div class="tb-avatar-wrap">
      <div class="tb-avatar" id="uAvatarTop">U</div>
      <div class="tb-dropdown" id="userDropdown" hidden>
        <div class="tb-dropdown-header" id="dropdownUserName">Usuario</div>
        <button class="tb-dropdown-item" onclick="window.location.href='<?php echo e(Controller::url('/perfil')); ?>'">
          <?php echo icon('user', ['size' => 15]); ?>
          Perfil
        </button>
        <div class="tb-dropdown-divider"></div>
        <button class="tb-dropdown-item tb-dropdown-danger" onclick="logout()">
          <?php echo icon('logout', ['size' => 15]); ?>
          Sair
        </button>
      </div>
    </div>
  </div>
</header>

<!-- ===== Conteúdo ===== -->
<main class="main" id="main-content" tabindex="-1">
    <div class="page-wrapper">
    <div id="searchNoticeHost"></div>
    <?php echo $content ?? ''; ?>
  </div>
</main>

<?php require __DIR__ . '/footer.php'; ?>
</body>
</html>