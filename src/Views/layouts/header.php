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
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        <span class="nav-text">Início</span>
      </a>
      <a class="nav-link<?php echo navActive($page, 'docs'); ?>" href="<?php echo e(Controller::url('/docs')); ?>" data-rail-label="Documentação">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        <span class="nav-text">Documentação</span>
      </a>
      <?php if (($u['role'] ?? '') === 'Admin' || ($u['role'] ?? '') === 'Aprovador'): ?>
      <a class="nav-link<?php echo navActive($page, 'aprovacoes'); ?>" href="<?php echo e(Controller::url('/aprovacoes')); ?>" data-rail-label="Aprovações">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        <span class="nav-text">Aprovações</span>
        <span class="nav-badge" id="sidebarPendingBadge" hidden>0</span>
      </a>
      <a class="nav-link<?php echo navActive($page, 'relatorios'); ?>" href="<?php echo e(Controller::url('/relatorios')); ?>" data-rail-label="Relatórios">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        <span class="nav-text">Relatórios</span>
      </a>
      <?php endif; ?>
    </div>
    <div class="nav-group" id="navAdminGroup"<?php echo ($u['role'] ?? '') !== 'Admin' ? ' hidden' : ''; ?>>
      <div class="nav-label">Administração</div>
      <a class="nav-link<?php echo navActive($page, 'usuarios'); ?>" href="<?php echo e(Controller::url('/usuarios')); ?>" data-rail-label="Administração">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <span class="nav-text">Usuários</span>
      </a>
      <a class="nav-link<?php echo navActive($page, 'configuracoes'); ?>" href="<?php echo e(Controller::url('/configuracoes')); ?>" data-rail-label="Configurações">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        <span class="nav-text">Configurações</span>
      </a>
      <a class="nav-link<?php echo navActive($page, 'sistema_docs'); ?>" href="<?php echo e(Controller::url('/sistema-docs')); ?>" data-rail-label="Manual">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
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
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      </button>
    </div>
  </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop" hidden onclick="toggleSidebar()"></div>

<!-- ===== Topbar ===== -->
<header class="topbar">
  <div class="topbar-left" style="display: flex; align-items: center; gap: 0.75rem; flex: 1; max-width: 480px;">
    <button class="sidebar-toggle" onclick="toggleSidebar()" title="Alternar menu lateral" aria-label="Alternar menu lateral">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <div class="topbar-global-search" style="position: relative; width: 100%;">
      <div style="position: relative; display: flex; align-items: center;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; left: 10px; width: 14px; height: 14px; color: var(--text-muted); pointer-events: none;">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
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
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
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
      <svg class="theme-icon-light" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
      <svg class="theme-icon-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
    </button>
    <div class="tb-avatar-wrap">
      <div class="tb-avatar" id="uAvatarTop">U</div>
      <div class="tb-dropdown" id="userDropdown" hidden>
        <div class="tb-dropdown-header" id="dropdownUserName">Usuario</div>
        <button class="tb-dropdown-item" onclick="window.location.href='/'">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          Perfil
        </button>
        <div class="tb-dropdown-divider"></div>
        <button class="tb-dropdown-item tb-dropdown-danger" onclick="logout()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
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