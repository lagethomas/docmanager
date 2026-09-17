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
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary);"><line x1="18" y1="20" x2      <div class="table-responsive">
        <table class="table" id="auditLogsTable">
          <thead>
            <tr>
              <th data-sort="id" class="sortable-th" style="width: 80px;">ID <span class="sort-icon"></span></th>
              <th data-sort="action" class="sortable-th" style="min-width: 140px;">Ação <span class="sort-icon"></span></th>
              <th data-sort="description" class="sortable-th" style="min-width: 260px;">Descrição <span class="sort-icon"></span></th>
              <th data-sort="user" class="sortable-th" style="min-width: 150px;">Usuário <span class="sort-icon"></span></th>
              <th data-sort="ip" class="sortable-th" style="min-width: 110px;">IP <span class="sort-icon"></span></th>
              <th data-sort="created_at" class="sortable-th" style="min-width: 140px; text-align: right;">Data / Hora <span class="sort-icon"></span></th>
            </tr>
          </thead>
          <tbody id="auditLogsBody"></tbody>
        </table>
      </div>
      <div class="card-footer">
        <div class="pagination-footer" id="logsPagination"></div>
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
var ALL_LOGS = <?php echo json_encode($logs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?> || [];

var ReportsApp = (function () {
  'use strict';

  var currentFiltered = [];

  function getBadgeClass(act) {
    if (!act) return 'action-badge-delete';
    if (act.indexOf('login') !== -1) return 'action-badge-login';
    if (act.indexOf('approve') !== -1) return 'action-badge-approve';
    if (act.indexOf('reject') !== -1) return 'action-badge-reject';
    if (act.indexOf('create') !== -1) return 'action-badge-create';
    if (act.indexOf('update') !== -1 || act.indexOf('edit') !== -1) return 'action-badge-edit';
    return 'action-badge-delete';
  }

  function formatDate(dtStr) {
    if (!dtStr) return '—';
    var d = new Date(dtStr);
    if (isNaN(d.getTime())) return dtStr;
    var day = String(d.getDate()).padStart(2, '0');
    var month = String(d.getMonth() + 1).padStart(2, '0');
    var year = d.getFullYear();
    var hours = String(d.getHours()).padStart(2, '0');
    var mins = String(d.getMinutes()).padStart(2, '0');
    return day + '/' + month + '/' + year + ' ' + hours + ':' + mins;
  }

  function render() {
    var actVal = document.getElementById('filterAction').value;
    var userVal = document.getElementById('filterUser').value;
    var searchVal = document.getElementById('filterSearch').value.trim().toLowerCase();

    // 1. Filtragem dos registros
    currentFiltered = ALL_LOGS.filter(function (log) {
      var matchAct = !actVal || log.action === actVal;
      var matchUser = !userVal || log.user_login === userVal;
      var logText = ((log.description || '') + ' ' + (log.user_name || '') + ' ' + (log.user_login || '') + ' ' + (log.ip || '')).toLowerCase();
      var matchSearch = !searchVal || logText.indexOf(searchVal) !== -1;
      return matchAct && matchUser && matchSearch;
    });

    // 2. Ordenação por coluna (TableSort)
    var sortState = TableSort.getSort('auditLogsTable');
    if (sortState.col) {
      currentFiltered = TableSort.sortArray(currentFiltered, sortState.col, sortState.dir, function (log, col) {
        if (col === 'id') return parseInt(log.id, 10);
        if (col === 'action') return log.action || '';
        if (col === 'description') return log.description || '';
        if (col === 'user') return log.user_name || log.user_login || '';
        if (col === 'ip') return log.ip || '';
        if (col === 'created_at') return log.created_at || '';
        return log.id;
      });
    }

    // 3. Paginação (utilitário padronizado global paginated)
    var pg = paginated('audit_logs', currentFiltered);
    var tbody = document.getElementById('auditLogsBody');

    if (!pg.total) {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2.5rem 1rem;">' +
        '<div class="empty-state">' +
        '<div class="empty-state-title">Nenhum registro de auditoria encontrado</div>' +
        '</div></td></tr>';
    } else {
      tbody.innerHTML = pg.page.map(function (log) {
        var badgeCls = getBadgeClass(log.action);
        var uName = esc(log.user_name || log.user_login || '—');
        var uLogin = log.user_login ? ('@' + esc(log.user_login)) : '—';
        var ip = esc(log.ip || '127.0.0.1');

        return '<tr>' +
          '<td style="font-weight: 600; color: var(--text-muted);">#' + esc(log.id) + '</td>' +
          '<td><span class="action-badge ' + badgeCls + '">' + esc(log.action) + '</span></td>' +
          '<td style="font-weight: 500; color: var(--text);">' + esc(log.description) + '</td>' +
          '<td>' +
            '<div style="font-size: 0.85rem; font-weight: 600; color: var(--text);">' + uName + '</div>' +
            '<div style="font-size: 0.75rem; color: var(--text-muted);">' + uLogin + '</div>' +
          '</td>' +
          '<td style="font-size: 0.8rem; color: var(--text-muted); font-family: monospace;">' + ip + '</td>' +
          '<td style="text-align: right; font-size: 0.8rem; color: var(--text-muted); white-space: nowrap;">' + formatDate(log.created_at) + '</td>' +
          '</tr>';
      }).join('');
    }

    // 4. Atualização de indicadores de filtro e rodapé de paginação
    var countEl = document.getElementById('logsFilteredCount');
    if (countEl) {
      countEl.textContent = 'Mostrando ' + pg.total + ' registros';
    }

    renderPagination('logsPagination', pg.total, pg.current, pg.pages, function (p) {
      PAGINATION.audit_logs = p;
      render();
    });
  }

  function applyFilters() {
    PAGINATION.audit_logs = 1;
    render();
  }

  function resetFilters() {
    document.getElementById('filterAction').value = '';
    document.getElementById('filterUser').value = '';
    document.getElementById('filterSearch').value = '';
    PAGINATION.audit_logs = 1;
    render();
  }

  function init() {
    var tableEl = document.getElementById('auditLogsTable');
    if (tableEl) {
      TableSort.init(tableEl, {
        onSort: function () {
          render();
        }
      });
    }
    render();
  }

  return {
    init: init,
    applyFilters: applyFilters,
    resetFilters: resetFilters
  };
})();

document.addEventListener('DOMContentLoaded', function () {
  ReportsApp.init();
});
</script>
