<?php
declare(strict_types=1);

use App\Core\Controller;

/**
 * @var array<string, mixed> $user
 * @var array<string, int> $stats
 * @var array<int, array<string, mixed>> $recentDocs
 * @var array<int, array<string, mixed>> $mostViewedDocs
 */
$user = $user ?? [];
$role = (string) ($user['role'] ?? 'Visualizador');
$name = (string) ($user['name'] ?? 'Usuário');
$initials = strtoupper(substr($name, 0, 2));
$stats = $stats ?? [];
$recentDocs = $recentDocs ?? [];
$mostViewedDocs = $mostViewedDocs ?? [];
$pendingCount = (int) ($stats['total_pending'] ?? $stats['my_pending'] ?? 0);
?>

<div class="dashboard-page" id="dashboardApp">

  <!-- ─── 1. HERO BANNER DE BOAS-VINDAS & AÇÕES RÁPIDAS ─── -->
  <div class="dash-hero-banner">
    <div class="dash-hero-left">
      <div class="dash-hero-avatar">
        <?php echo e($initials); ?>
      </div>
      <div class="dash-hero-greeting">
        <h1 class="dash-hero-title">
          <span>Olá, <?php echo e($name); ?>!</span>
          <span class="dash-hero-role-pill role-<?php echo strtolower($role); ?>">
            <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:currentColor;"></span>
            <?php echo e($role); ?>
          </span>
        </h1>
        <p class="dash-hero-subtitle">
          Bem-vindo ao <strong>DocManager</strong>. Painel consolidado com indicadores de acervo, aprovações e tendências em tempo real.
        </p>
      </div>
    </div>

    <div class="dash-hero-right">
      <?php if ($role === 'Admin' || $role === 'Aprovador'): ?>
        <?php if ($pendingCount > 0): ?>
          <a href="<?php echo e(Controller::url('/aprovacoes')); ?>" class="dash-hero-btn btn-warning-glow" title="Revisar documentos que aguardam aprovação">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span>Fila de Aprovação (<?php echo $pendingCount; ?>)</span>
          </a>
        <?php endif; ?>
      <?php endif; ?>

      <?php if ($role === 'Admin' || $role === 'Editor'): ?>
        <a href="<?php echo e(Controller::url('/docs')); ?>" class="dash-hero-btn btn-primary" title="Criar ou gerenciar documentos no acervo">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          <span>Novo Documento</span>
        </a>
      <?php endif; ?>

      <a href="<?php echo e(Controller::url('/docs')); ?>" class="dash-hero-btn btn-glass" title="Explorar todos os documentos disponíveis">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        <span>Explorar Acervo</span>
      </a>
    </div>
  </div>

  <!-- ─── 2. ATALHOS RÁPIDOS (STRIP) ────────────────────── -->
  <div class="dash-shortcuts-strip">
    <a href="<?php echo e(Controller::url('/docs')); ?>" class="shortcut-chip">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
      <span>Pastas & Categorias</span>
    </a>
    <?php if ($role === 'Admin' || $role === 'Aprovador'): ?>
    <a href="<?php echo e(Controller::url('/aprovacoes')); ?>" class="shortcut-chip">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
      <span>Central de Aprovações</span>
    </a>
    <?php endif; ?>
    <?php if ($role === 'Admin'): ?>
    <a href="<?php echo e(Controller::url('/usuarios')); ?>" class="shortcut-chip">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      <span>Gestão de Usuários</span>
    </a>
    <a href="<?php echo e(Controller::url('/configuracoes')); ?>" class="shortcut-chip">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
      <span>Configurações do Sistema</span>
    </a>
    <?php endif; ?>
  </div>

  <!-- ─── 3. CARDS DE INDICADORES (KPIs) POR PERFIL ────── -->
  <div class="dashboard-metrics-grid">
    <?php if ($role === 'Admin'): ?>
      <!-- Card 1: Total de Documentos -->
      <a href="<?php echo e(Controller::url('/docs')); ?>" class="metric-card accent-blue">
        <div class="metric-top-row">
          <div class="metric-icon bg-blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          </div>
          <span class="metric-badge-tag neutral">Total Cadastrado</span>
        </div>
        <div class="metric-info-wrap">
          <span class="metric-value"><?php echo (int) ($stats['total_docs'] ?? 0); ?></span>
          <span class="metric-label">Documentos no Sistema</span>
          <span class="metric-hint">Base operacional ativa</span>
        </div>
      </a>

      <!-- Card 2: Pendentes de Aprovação -->
      <a href="<?php echo e(Controller::url('/aprovacoes')); ?>" class="metric-card accent-amber">
        <div class="metric-top-row">
          <div class="metric-icon bg-amber">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <?php if (($stats['total_pending'] ?? 0) > 0): ?>
            <span class="metric-badge-tag warning">Requer Atenção</span>
          <?php else: ?>
            <span class="metric-badge-tag success">Fila Zerada</span>
          <?php endif; ?>
        </div>
        <div class="metric-info-wrap">
          <span class="metric-value"><?php echo (int) ($stats['total_pending'] ?? 0); ?></span>
          <span class="metric-label">Pendentes de Aprovação</span>
          <span class="metric-hint">Aguardando revisão de publicador</span>
        </div>
      </a>

      <!-- Card 3: Documentos Publicados -->
      <a href="<?php echo e(Controller::url('/docs')); ?>" class="metric-card accent-teal">
        <div class="metric-top-row">
          <div class="metric-icon bg-teal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          </div>
          <span class="metric-badge-tag success">Disponíveis</span>
        </div>
        <div class="metric-info-wrap">
          <span class="metric-value"><?php echo (int) ($stats['total_approved'] ?? 0); ?></span>
          <span class="metric-label">Documentos Publicados</span>
          <span class="metric-hint">Visíveis para todos os leitores</span>
        </div>
      </a>

      <!-- Card 4: Total de Visualizações -->
      <div class="metric-card accent-purple">
        <div class="metric-top-row">
          <div class="metric-icon bg-purple">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </div>
          <span class="metric-badge-tag neutral">Engajamento</span>
        </div>
        <div class="metric-info-wrap">
          <span class="metric-value"><?php echo number_format((int) ($stats['total_views'] ?? 0), 0, ',', '.'); ?></span>
          <span class="metric-label">Total de Visualizações</span>
          <span class="metric-hint">Leituras registradas no acervo</span>
        </div>
      </div>

      <!-- Card 5: Pastas & Categorias -->
      <a href="<?php echo e(Controller::url('/docs')); ?>" class="metric-card accent-cyan">
        <div class="metric-top-row">
          <div class="metric-icon bg-cyan">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
          </div>
          <span class="metric-badge-tag neutral">Estrutura</span>
        </div>
        <div class="metric-info-wrap">
          <span class="metric-value"><?php echo (int) ($stats['total_categories'] ?? 0); ?></span>
          <span class="metric-label">Pastas / Categorias</span>
          <span class="metric-hint">Divisões temáticas ativas</span>
        </div>
      </a>

      <!-- Card 6: Usuários Cadastrados -->
      <a href="<?php echo e(Controller::url('/usuarios')); ?>" class="metric-card accent-indigo">
        <div class="metric-top-row">
          <div class="metric-icon bg-indigo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </div>
          <span class="metric-badge-tag neutral">Membros</span>
        </div>
        <div class="metric-info-wrap">
          <span class="metric-value"><?php echo (int) ($stats['total_users'] ?? 0); ?></span>
          <span class="metric-label">Usuários no Sistema</span>
          <span class="metric-hint">Contas ativas e autorizadas</span>
        </div>
      </a>

    <?php elseif ($role === 'Aprovador'): ?>
      <!-- Dashboard Aprovador -->
      <a href="<?php echo e(Controller::url('/aprovacoes')); ?>" class="metric-card accent-amber" style="grid-column: span 2;">
        <div class="metric-top-row">
          <div class="metric-icon bg-amber" style="width:52px;height:52px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:26px;height:26px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <span class="metric-badge-tag warning">Fila Prioritária</span>
        </div>
        <div class="metric-info-wrap">
          <span class="metric-value" style="font-size:2.2rem;"><?php echo (int) ($stats['total_pending'] ?? 0); ?></span>
          <span class="metric-label" style="font-size:0.95rem;">Documentos Pendentes de Aprovação</span>
          <span class="metric-hint">Clique para abrir a mesa de revisão e aprovar publicações</span>
        </div>
      </a>

      <a href="<?php echo e(Controller::url('/docs')); ?>" class="metric-card accent-teal">
        <div class="metric-top-row">
          <div class="metric-icon bg-teal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          </div>
          <span class="metric-badge-tag success">Aprovados</span>
        </div>
        <div class="metric-info-wrap">
          <span class="metric-value"><?php echo (int) ($stats['total_approved'] ?? 0); ?></span>
          <span class="metric-label">Documentos Publicados</span>
          <span class="metric-hint">Disponíveis no acervo oficial</span>
        </div>
      </a>

      <div class="metric-card accent-purple">
        <div class="metric-top-row">
          <div class="metric-icon bg-purple">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </div>
          <span class="metric-badge-tag neutral">Acessos</span>
        </div>
        <div class="metric-info-wrap">
          <span class="metric-value"><?php echo number_format((int) ($stats['total_views'] ?? 0), 0, ',', '.'); ?></span>
          <span class="metric-label">Total de Visualizações</span>
          <span class="metric-hint">Leituras registradas</span>
        </div>
      </div>

    <?php elseif ($role === 'Editor'): ?>
      <!-- Dashboard Editor -->
      <a href="<?php echo e(Controller::url('/docs')); ?>" class="metric-card accent-blue">
        <div class="metric-top-row">
          <div class="metric-icon bg-blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </div>
          <span class="metric-badge-tag neutral">Minha Autoria</span>
        </div>
        <div class="metric-info-wrap">
          <span class="metric-value"><?php echo (int) ($stats['my_docs'] ?? 0); ?></span>
          <span class="metric-label">Meus Documentos Criados</span>
          <span class="metric-hint">Produções suas na plataforma</span>
        </div>
      </a>

      <!-- Card Compartilhados Comigo -->
      <a href="<?php echo e(Controller::url('/docs?filter=shared')); ?>" class="metric-card accent-indigo">
        <div class="metric-top-row">
          <div class="metric-icon bg-indigo">
            <i class="fa-solid fa-share-nodes" style="font-size: 18px;"></i>
          </div>
          <span class="metric-badge-tag neutral">Colaboração</span>
        </div>
        <div class="metric-info-wrap">
          <span class="metric-value"><?php echo (int) ($stats['shared_with_me'] ?? 0); ?></span>
          <span class="metric-label">Compartilhados Comigo</span>
          <span class="metric-hint">Documentos com acesso delegado</span>
        </div>
      </a>

      <a href="<?php echo e(Controller::url('/docs')); ?>" class="metric-card accent-amber">
        <div class="metric-top-row">
          <div class="metric-icon bg-amber">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <?php if (($stats['my_pending'] ?? 0) > 0): ?>
            <span class="metric-badge-tag warning">Em Análise</span>
          <?php else: ?>
            <span class="metric-badge-tag success">Tudo Aprovado</span>
          <?php endif; ?>
        </div>
        <div class="metric-info-wrap">
          <span class="metric-value"><?php echo (int) ($stats['my_pending'] ?? 0); ?></span>
          <span class="metric-label">Meus em Aprovação</span>
          <span class="metric-hint">Aguardando parecer do aprovador</span>
        </div>
      </a>

      <a href="<?php echo e(Controller::url('/docs')); ?>" class="metric-card accent-teal">
        <div class="metric-top-row">
          <div class="metric-icon bg-teal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          </div>
          <span class="metric-badge-tag success">Geral</span>
        </div>
        <div class="metric-info-wrap">
          <span class="metric-value"><?php echo (int) ($stats['total_approved'] ?? 0); ?></span>
          <span class="metric-label">Documentos Publicados</span>
          <span class="metric-hint">Acervo geral disponível</span>
        </div>
      </a>

      <a href="<?php echo e(Controller::url('/docs')); ?>" class="metric-card accent-cyan">
        <div class="metric-top-row">
          <div class="metric-icon bg-cyan">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
          </div>
          <span class="metric-badge-tag neutral">Pastas</span>
        </div>
        <div class="metric-info-wrap">
          <span class="metric-value"><?php echo (int) ($stats['total_categories'] ?? 0); ?></span>
          <span class="metric-label">Pastas do Sistema</span>
          <span class="metric-hint">Organização estruturada</span>
        </div>
      </a>

      <!-- Card Meus Favoritos -->
      <a href="<?php echo e(Controller::url('/docs?filter=favorites')); ?>" class="metric-card accent-amber">
        <div class="metric-top-row">
          <div class="metric-icon bg-amber">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          </div>
          <span class="metric-badge-tag neutral">Favoritos</span>
        </div>
        <div class="metric-info-wrap">
          <span class="metric-value"><?php echo (int) ($stats['total_favorites'] ?? 0); ?></span>
          <span class="metric-label">Meus Favoritos</span>
          <span class="metric-hint">Documentos marcados por você</span>
        </div>
      </a>

      <!-- Card Pendentes de Aprovação (Global) -->
      <div class="metric-card accent-indigo">
        <div class="metric-top-row">
          <div class="metric-icon bg-indigo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <?php if (($stats['total_pending'] ?? 0) > 0): ?>
            <span class="metric-badge-tag warning">Fila Ativa</span>
          <?php else: ?>
            <span class="metric-badge-tag success">Fila Zerada</span>
          <?php endif; ?>
        </div>
        <div class="metric-info-wrap">
          <span class="metric-value"><?php echo (int) ($stats['total_pending'] ?? 0); ?></span>
          <span class="metric-label">Pendentes de Aprovação</span>
          <span class="metric-hint">Total geral aguardando revisão</span>
        </div>
      </div>

    <?php else: ?>
      <!-- Dashboard Visualizador -->
      <a href="<?php echo e(Controller::url('/docs')); ?>" class="metric-card accent-teal" style="grid-column: span 2;">
        <div class="metric-top-row">
          <div class="metric-icon bg-teal" style="width:50px;height:50px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:26px;height:26px;"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          </div>
          <span class="metric-badge-tag success">Leitura Liberada</span>
        </div>
        <div class="metric-info-wrap">
          <span class="metric-value" style="font-size:2rem;"><?php echo (int) ($stats['total_approved'] ?? 0); ?></span>
          <span class="metric-label" style="font-size:0.95rem;">Documentos Disponíveis para Leitura</span>
          <span class="metric-hint">Manuais, procedimentos e guias homologados</span>
        </div>
      </a>

      <a href="<?php echo e(Controller::url('/docs')); ?>" class="metric-card accent-cyan">
        <div class="metric-top-row">
          <div class="metric-icon bg-cyan">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
          </div>
          <span class="metric-badge-tag neutral">Categorias</span>
        </div>
        <div class="metric-info-wrap">
          <span class="metric-value"><?php echo (int) ($stats['total_categories'] ?? 0); ?></span>
          <span class="metric-label">Pastas Temáticas</span>
          <span class="metric-hint">Explore por assunto</span>
        </div>
      </a>

      <div class="metric-card accent-purple">
        <div class="metric-top-row">
          <div class="metric-icon bg-purple">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </div>
          <span class="metric-badge-tag neutral">Engajamento</span>
        </div>
        <div class="metric-info-wrap">
          <span class="metric-value"><?php echo number_format((int) ($stats['total_views'] ?? 0), 0, ',', '.'); ?></span>
          <span class="metric-label">Total de Visualizações</span>
          <span class="metric-hint">Histórico geral do acervo</span>
        </div>
      </div>

      <!-- Card Documentos Pendentes de Aprovação (Visualizador) -->
      <div class="metric-card accent-amber">
        <div class="metric-top-row">
          <div class="metric-icon bg-amber">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <?php if (($stats['total_pending'] ?? 0) > 0): ?>
            <span class="metric-badge-tag warning">Aguardando</span>
          <?php else: ?>
            <span class="metric-badge-tag success">Fila Zerada</span>
          <?php endif; ?>
        </div>
        <div class="metric-info-wrap">
          <span class="metric-value"><?php echo (int) ($stats['total_pending'] ?? 0); ?></span>
          <span class="metric-label">Pendentes de Aprovação</span>
          <span class="metric-hint">Documentos aguardando publicação</span>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- ─── 4. TABELAS DE TENDÊNCIAS LADO A LADO ─────────── -->
  <div class="dashboard-tables-split">

    <!-- Tabela 1: 10 Documentos Mais Recentes Publicados -->
    <div class="dashboard-table-panel">
      <div class="panel-header">
        <div class="panel-title-wrap">
          <div class="panel-icon recent">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4.5c1.47-1.47 4.5-2 4.5-2"/><path d="M12 15v5s3.03-.55 4.5-2c1.47-1.47 2-4.5 2-4.5"/></svg>
          </div>
          <div>
            <h2 class="panel-title">Documentos Recentes</h2>
            <span class="panel-subtitle">Últimas publicações homologadas no acervo</span>
          </div>
        </div>
        <a href="<?php echo e(Controller::url('/docs')); ?>" class="panel-link">
          <span>Ver acervo</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
      </div>

      <div class="panel-body">
        <?php if (!empty($recentDocs)): ?>
        <div class="table-responsive">
          <table class="table" id="tableRecentDocs">
            <thead>
              <tr>
                <th data-sort="title" class="sortable-th">Título <span class="sort-icon"></span></th>
                <th data-sort="category" class="sortable-th">Pasta <span class="sort-icon"></span></th>
                <th data-sort="author" class="sortable-th">Autor <span class="sort-icon"></span></th>
                <th data-sort="date" class="sortable-th">Publicado em <span class="sort-icon"></span></th>
                <th class="th-actions">Ação</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentDocs as $d): 
                $catColor = !empty($d['category_color']) ? (string) $d['category_color'] : '#1abb9c';
                $authorName = (string) ($d['author_name'] ?? 'Admin');
                $authorInit = strtoupper(substr($authorName, 0, 2));
              ?>
              <tr>
                <td>
                  <a href="<?php echo e(Controller::url('/docs/view/' . (int) $d['id'])); ?>" class="table-doc-cell" title="<?php echo e($d['title'] ?? ''); ?>">
                    <span class="table-doc-icon">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </span>
                    <span class="table-doc-name"><?php echo e($d['title'] ?? ''); ?></span>
                  </a>
                </td>
                <td>
                  <span class="table-cat-badge">
                    <span class="cat-color-dot" style="background-color: <?php echo e($catColor); ?>;"></span>
                    <?php echo e($d['category_name'] ?? 'Geral'); ?>
                  </span>
                </td>
                <td>
                  <div class="author-chip">
                    <span class="author-avatar-tiny"><?php echo e($authorInit); ?></span>
                    <span class="table-author-text"><?php echo e($authorName); ?></span>
                  </div>
                </td>
                <td>
                  <span class="table-date-text">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <?php echo date('d/m/Y', strtotime((string) ($d['approved_at'] ?? $d['updated_at']))); ?>
                  </span>
                </td>
                <td style="text-align: right;">
                  <a href="<?php echo e(Controller::url('/docs/view/' . (int) $d['id'])); ?>" class="dash-btn-view" title="Abrir em Modo Leitura">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="card-footer">
          <div class="pagination-footer" id="paginationRecentDocs">
            <span>Mostrando <?php echo count($recentDocs); ?> itens recentes</span>
            <a href="<?php echo e(Controller::url('/docs')); ?>" class="panel-link">Ver catálogo completo →</a>
          </div>
        </div>
        <?php else: ?>
        <div class="panel-empty">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
          <strong>Nenhum documento recente</strong>
          <span>Quando novos documentos forem publicados, eles aparecerão aqui.</span>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Tabela 2: 10 Documentos Mais Acessados -->
    <div class="dashboard-table-panel">
      <div class="panel-header">
        <div class="panel-title-wrap">
          <div class="panel-icon popular">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </div>
          <div>
            <h2 class="panel-title">Mais Acessados</h2>
            <span class="panel-subtitle">Documentos com maior engajamento e leituras</span>
          </div>
        </div>
        <a href="<?php echo e(Controller::url('/docs')); ?>" class="panel-link">
          <span>Ver ranking</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
      </div>

      <div class="panel-body">
        <?php if (!empty($mostViewedDocs)): ?>
        <div class="table-responsive">
          <table class="table" id="tablePopularDocs">
            <thead>
              <tr>
                <th data-sort="title" class="sortable-th">Título <span class="sort-icon"></span></th>
                <th data-sort="category" class="sortable-th">Pasta <span class="sort-icon"></span></th>
                <th data-sort="views" class="sortable-th">Visualizações <span class="sort-icon"></span></th>
                <th data-sort="date" class="sortable-th">Atualizado em <span class="sort-icon"></span></th>
                <th class="th-actions">Ação</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($mostViewedDocs as $d): 
                $catColor = !empty($d['category_color']) ? (string) $d['category_color'] : '#8b5cf6';
              ?>
              <tr>
                <td>
                  <a href="<?php echo e(Controller::url('/docs/view/' . (int) $d['id'])); ?>" class="table-doc-cell" title="<?php echo e($d['title'] ?? ''); ?>">
                    <span class="table-doc-icon" style="background: rgba(139, 92, 246, 0.12); color: #8b5cf6;">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </span>
                    <span class="table-doc-name"><?php echo e($d['title'] ?? ''); ?></span>
                  </a>
                </td>
                <td>
                  <span class="table-cat-badge">
                    <span class="cat-color-dot" style="background-color: <?php echo e($catColor); ?>;"></span>
                    <?php echo e($d['category_name'] ?? 'Geral'); ?>
                  </span>
                </td>
                <td>
                  <span class="views-badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <?php echo number_format((int) ($d['views_count'] ?? 0), 0, ',', '.'); ?> views
                  </span>
                </td>
                <td>
                  <span class="table-date-text">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <?php echo date('d/m/Y', strtotime((string) ($d['updated_at']))); ?>
                  </span>
                </td>
                <td style="text-align: right;">
                  <a href="<?php echo e(Controller::url('/docs/view/' . (int) $d['id'])); ?>" class="dash-btn-view" title="Abrir em Modo Leitura">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="card-footer">
          <div class="pagination-footer" id="paginationPopularDocs">
            <span>Top <?php echo count($mostViewedDocs); ?> documentos mais lidos</span>
            <a href="<?php echo e(Controller::url('/docs')); ?>" class="panel-link">Explorar biblioteca →</a>
          </div>
        </div>
        <?php else: ?>
        <div class="panel-empty">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          <strong>Nenhum documento com acessos registrados</strong>
          <span>À medida que os colaboradores lerem os documentos, o ranking será montado aqui.</span>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>

</div>

