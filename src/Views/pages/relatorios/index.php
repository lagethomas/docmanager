<?php
declare(strict_types=1);

use App\Core\Controller;

/**
 * @var array<string, mixed> $stats
 * @var array<int, string> $actions
 * @var array<int, array<string, mixed>> $usersList
 * @var array<int, array<string, mixed>> $logs
 * @var array<int, array<string, mixed>> $topCategories
 * @var array<int, array<string, mixed>> $topActiveUsers
 */
?>

<div class="reports-page" id="reportsApp">
  <!-- ─── HEADER ───────────────────────────────────────────────────────────── -->
  <div class="page-header-row mb-16" style="display: flex; align-items: center; justify-content: space-between;">
    <div>
      <h1 class="page-title">Relatórios & Trilha de Auditoria</h1>
      <p class="page-pretitle" style="margin-top: 4px; text-transform: none; letter-spacing: normal; font-weight: 400; font-size: 13px;">
        Histórico detalhado de auditoria, acessos, fluxos de aprovação e métricas de desempenho.
      </p>
    </div>
    <div class="reports-header-actions">
      <a href="<?php echo e(Controller::url('/api/relatorios/export-csv')); ?>" class="btn btn-primary" title="Exportar dados de auditoria em CSV">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        <span>Exportar CSV</span>
      </a>
    </div>
  </div>

  <!-- ─── CARDS DE INDICADORES (KPIs) ──────────────────────────────────────── -->
  <div class="reports-kpi-grid">
    <!-- Documentos Cadastrados -->
    <div class="reports-kpi-card">
      <div class="reports-kpi-icon blue">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      </div>
      <div>
        <span class="reports-kpi-val"><?php echo (int) ($stats['total_docs'] ?? 0); ?></span>
        <span class="reports-kpi-lbl">Documentos no Acervo</span>
        <span class="reports-kpi-sub">Total cadastrado</span>
      </div>
    </div>

    <!-- Documentos Aprovados -->
    <div class="reports-kpi-card">
      <div class="reports-kpi-icon teal">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      </div>
      <div>
        <span class="reports-kpi-val"><?php echo (int) ($stats['total_approved'] ?? 0); ?></span>
        <span class="reports-kpi-lbl">Aprovados / Publicados</span>
        <span class="reports-kpi-sub">Disponíveis para consulta</span>
      </div>
    </div>

    <!-- Pendentes -->
    <div class="reports-kpi-card">
      <div class="reports-kpi-icon amber">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      </div>
      <div>
        <span class="reports-kpi-val"><?php echo (int) ($stats['total_pending'] ?? 0); ?></span>
        <span class="reports-kpi-lbl">Pendentes de Revisão</span>
        <span class="reports-kpi-sub">Aguardando aprovador</span>
      </div>
    </div>

    <!-- Registros de Logs -->
    <div class="reports-kpi-card">
      <div class="reports-kpi-icon purple">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      </div>
      <div>
        <span class="reports-kpi-val"><?php echo (int) ($stats['total_logs'] ?? 0); ?></span>
        <span class="reports-kpi-lbl">Eventos Auditados</span>
        <span class="reports-kpi-sub">Trilha completa gravada</span>
      </div>
    </div>
  </div>

  <!-- ─── BARRA DE FILTROS ─────────────────────────────────────────────────── -->
  <div class="reports-filter-bar">
    <!-- Filtro por Tipo de Ação -->
    <div class="reports-filter-group">
      <span class="reports-filter-label">Ação:</span>
      <select id="filterAction" class="reports-filter-select" onchange="ReportsApp.applyFilters()">
        <option value="">Todas as ações</option>
        <?php foreach ($actions as $act): ?>
          <option value="<?php echo e((string) $act); ?>"><?php echo e((string) $act); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Filtro por Usuário -->
    <div class="reports-filter-group">
      <span class="reports-filter-label">Usuário:</span>
      <select id="filterUser" class="reports-filter-select" onchange="ReportsApp.applyFilters()">
        <option value="">Todos os usuários</option>
        <?php foreach ($usersList as $uItem): ?>
          <option value="<?php echo e((string) $uItem['login']); ?>"><?php echo e((string) $uItem['name']); ?> (@<?php echo e((string) $uItem['login']); ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Busca por Palavra-chave -->
    <div class="reports-filter-group" style="flex: 1.5; min-width: 240px;">
      <span class="reports-filter-label">Busca:</span>
      <input type="text" id="filterSearch" class="reports-filter-input" placeholder="Buscar por descrição ou palavra-chave..." oninput="ReportsApp.applyFilters()" />
    </div>

    <button type="button" class="btn btn-secondary btn-sm" onclick="ReportsApp.resetFilters()" title="Limpar todos os filtros">
      Limpar Filtros
    </button>
  </div>

  <!-- ─── DIVISÃO PRINCIPAL (Tabela + Painel Lateral) ──────────────────────── -->
  <div class="reports-main-split">
    <!-- Tabela Principal de Logs -->
    <div class="card">
      <div class="card-header" style="display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-color);">
        <div style="display: flex; align-items: center; gap: 0.5rem;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary);"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
          <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0; color: var(--text);">Trilha de Auditoria Recente</h3>
        </div>
        <span style="font-size: 0.8rem; color: var(--text-muted);" id="logsFilteredCount">Mostrando <?php echo count($logs); ?> registros</span>
      </div>

      <div class="table-responsive">
        <table class="table" id="auditLogsTable">
          <thead>
            <tr>
              <th style="width: 70px;">ID</th>
              <th style="min-width: 140px;">Ação</th>
              <th style="min-width: 260px;">Descrição</th>
              <th style="min-width: 150px;">Usuário</th>
              <th style="min-width: 110px;">IP</th>
              <th style="min-width: 140px; text-align: right;">Data / Hora</th>
            </tr>
          </thead>
          <tbody id="auditLogsBody">
            <?php if (empty($logs)): ?>
              <tr>
                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2.5rem 1rem;">
                  Nenhum registro de auditoria encontrado.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($logs as $log): ?>
                <?php
                  $act = (string) $log['action'];
                  $badgeCls = 'action-badge-delete';
                  if (str_contains($act, 'login')) $badgeCls = 'action-badge-login';
                  elseif (str_contains($act, 'approve')) $badgeCls = 'action-badge-approve';
                  elseif (str_contains($act, 'reject')) $badgeCls = 'action-badge-reject';
                  elseif (str_contains($act, 'create')) $badgeCls = 'action-badge-create';
                  elseif (str_contains($act, 'update') || str_contains($act, 'edit')) $badgeCls = 'action-badge-edit';
                ?>
                <tr data-action="<?php echo e($act); ?>" data-user="<?php echo e((string) $log['user_login']); ?>" data-text="<?php echo e(mb_strtolower((string) $log['description'] . ' ' . (string) $log['user_name'])); ?>">
                  <td style="font-weight: 600; color: var(--text-muted);">#<?php echo e((string) $log['id']); ?></td>
                  <td>
                    <span class="action-badge <?php echo $badgeCls; ?>">
                      <?php echo e($act); ?>

                    </span>
                  </td>
                  <td style="font-weight: 500; color: var(--text);"><?php echo e((string) $log['description']); ?></td>
                  <td>
                    <div style="font-size: 0.85rem; font-weight: 600; color: var(--text);"><?php echo e((string) ($log['user_name'] ?: $log['user_login'] ?: '—')); ?></div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo e((string) ($log['user_login'] ? '@' . $log['user_login'] : '—')); ?></div>
                  </td>
                  <td style="font-size: 0.8rem; color: var(--text-muted); font-family: monospace;"><?php echo e((string) ($log['ip'] ?: '127.0.0.1')); ?></td>
                  <td style="text-align: right; font-size: 0.8rem; color: var(--text-muted); white-space: nowrap;">
                    <?php echo e(date('d/m/Y H:i', strtotime((string) $log['created_at']))); ?>

                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Painel Lateral (Top Categorias e Usuários) -->
    <div>
      <!-- Card Top Categorias -->
      <div class="reports-side-card">
        <div class="reports-side-title">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary);"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
          <span>Top Categorias</span>
        </div>
        <div class="reports-side-list">
          <?php if (empty($topCategories)): ?>
            <div style="font-size: 0.8rem; color: var(--text-muted); text-align: center;">Nenhuma categoria encontrada</div>
          <?php else: ?>
            <?php foreach ($topCategories as $cat): ?>
              <div class="reports-side-item">
                <span class="reports-side-item-name"><?php echo e((string) $cat['name']); ?></span>
                <span class="reports-side-item-badge"><?php echo (int) $cat['total']; ?> docs</span>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Card Top Usuários Ativos -->
      <div class="reports-side-card">
        <div class="reports-side-title">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #3b82f6;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
          <span>Usuários Mais Ativos</span>
        </div>
        <div class="reports-side-list">
          <?php if (empty($topActiveUsers)): ?>
            <div style="font-size: 0.8rem; color: var(--text-muted); text-align: center;">Nenhum usuário registrado</div>
          <?php else: ?>
            <?php foreach ($topActiveUsers as $uAct): ?>
              <div class="reports-side-item">
                <div>
                  <div class="reports-side-item-name"><?php echo e((string) ($uAct['user_name'] ?: $uAct['user_login'])); ?></div>
                  <div style="font-size: 0.72rem; color: var(--text-muted);">@<?php echo e((string) $uAct['user_login']); ?></div>
                </div>
                <span class="reports-side-item-badge" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;"><?php echo (int) $uAct['total']; ?> ações</span>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
var ReportsApp = (function () {
  'use strict';

  function applyFilters() {
    var actVal = document.getElementById('filterAction').value;
    var userVal = document.getElementById('filterUser').value;
    var searchVal = document.getElementById('filterSearch').value.trim().toLowerCase();

    var rows = document.querySelectorAll('#auditLogsBody tr');
    var visibleCount = 0;

    rows.forEach(function (tr) {
      var rAct = tr.getAttribute('data-action') || '';
      var rUser = tr.getAttribute('data-user') || '';
      var rText = tr.getAttribute('data-text') || '';

      var matchAct = !actVal || rAct === actVal;
      var matchUser = !userVal || rUser === userVal;
      var matchSearch = !searchVal || rText.indexOf(searchVal) !== -1;

      if (matchAct && matchUser && matchSearch) {
        tr.style.display = '';
        visibleCount++;
      } else {
        tr.style.display = 'none';
      }
    });

    var countEl = document.getElementById('logsFilteredCount');
    if (countEl) countEl.textContent = 'Mostrando ' + visibleCount + ' registros';
  }

  function resetFilters() {
    document.getElementById('filterAction').value = '';
    document.getElementById('filterUser').value = '';
    document.getElementById('filterSearch').value = '';
    applyFilters();
  }

  return {
    applyFilters: applyFilters,
    resetFilters: resetFilters
  };
})();
</script>
