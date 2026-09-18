/**
 * ==========================================================================
 * RELATORIOS.JS — Módulo de Relatórios e Trilha de Auditoria
 * ==========================================================================
 */
'use strict';

var ReportsApp = (function () {
  var ALL_LOGS = window.ALL_LOGS || [];
  var currentFiltered = [];
  var PAGE_SIZE = 15;

  function getBadgeClass(act) {
    if (!act) return 'action-badge-delete';
    var a = act.toLowerCase();
    if (a.indexOf('login') !== -1 || a.indexOf('auth') !== -1) return 'action-badge-login';
    if (a.indexOf('approve') !== -1 || a.indexOf('aprova') !== -1) return 'action-badge-approve';
    if (a.indexOf('reject') !== -1 || a.indexOf('rejeit') !== -1) return 'action-badge-reject';
    if (a.indexOf('create') !== -1 || a.indexOf('cria') !== -1) return 'action-badge-create';
    if (a.indexOf('update') !== -1 || a.indexOf('edit') !== -1 || a.indexOf('altera') !== -1) return 'action-badge-edit';
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
    ALL_LOGS = window.ALL_LOGS || [];
    var actEl = document.getElementById('filterAction');
    var userEl = document.getElementById('filterUser');
    var searchEl = document.getElementById('filterSearch');

    var actVal = actEl ? actEl.value : '';
    var userVal = userEl ? userEl.value : '';
    var searchVal = searchEl ? searchEl.value.trim().toLowerCase() : '';

    // 1. Filtragem dos registros
    currentFiltered = ALL_LOGS.filter(function (log) {
      var matchAct = !actVal || log.action === actVal;
      var matchUser = !userVal || log.user_login === userVal;
      var logText = ((log.description || '') + ' ' + (log.user_name || '') + ' ' + (log.user_login || '') + ' ' + (log.ip || '')).toLowerCase();
      var matchSearch = !searchVal || logText.indexOf(searchVal) !== -1;
      return matchAct && matchUser && matchSearch;
    });

    // 2. Ordenação por coluna (TableSort)
    if (typeof TableSort !== 'undefined' && TableSort.getSort) {
      var sortState = TableSort.getSort('auditLogsTable');
      if (sortState && sortState.col) {
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
    }

    // 3. Paginação
    var currentPage = (window.PAGINATION && window.PAGINATION.audit_logs) ? window.PAGINATION.audit_logs : 1;
    var total = currentFiltered.length;
    var totalPages = Math.ceil(total / PAGE_SIZE) || 1;
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    var start = (currentPage - 1) * PAGE_SIZE;
    var pageItems = currentFiltered.slice(start, start + PAGE_SIZE);
    var tbody = document.getElementById('auditLogsBody');

    if (tbody) {
      if (!total) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 3rem 1rem;">' +
          '<div class="empty-state">' +
          '<div style="font-size: 2rem; margin-bottom: 0.5rem;">📋</div>' +
          '<div style="font-weight: 600; font-size: 0.95rem; color: var(--text);">Nenhum registro de auditoria encontrado</div>' +
          '<div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">Tente alterar os filtros de busca acima.</div>' +
          '</div></td></tr>';
      } else {
        tbody.innerHTML = pageItems.map(function (log) {
          var badgeCls = getBadgeClass(log.action);
          var uName = esc(log.user_name || log.user_login || '—');
          var uLogin = log.user_login ? ('@' + esc(log.user_login)) : '—';
          var ip = esc(log.ip || '127.0.0.1');

          return '<tr>' +
            '<td style="font-weight: 600; color: var(--text-muted); font-size: 0.8rem;">#' + esc(log.id) + '</td>' +
            '<td><span class="action-badge ' + badgeCls + '">' + esc(log.action) + '</span></td>' +
            '<td style="font-weight: 500; color: var(--text);">' + esc(log.description) + '</td>' +
            '<td>' +
              '<div style="font-size: 0.85rem; font-weight: 600; color: var(--text);">' + uName + '</div>' +
              '<div style="font-size: 0.72rem; color: var(--text-muted);">' + uLogin + '</div>' +
            '</td>' +
            '<td style="font-size: 0.8rem; color: var(--text-muted); font-family: monospace;">' + ip + '</td>' +
            '<td style="text-align: right; font-size: 0.8rem; color: var(--text-muted); white-space: nowrap;">' + formatDate(log.created_at) + '</td>' +
            '</tr>';
        }).join('');
      }
    }

    // 4. Indicador de registros filtrados
    var countEl = document.getElementById('logsFilteredCount');
    if (countEl) {
      countEl.textContent = 'Mostrando ' + Math.min(start + 1, total) + '–' + Math.min(start + PAGE_SIZE, total) + ' de ' + total + ' registros';
    }

    // 5. Renderizar controles de paginação
    if (typeof renderPagination === 'function') {
      renderPagination('logsPagination', total, currentPage, totalPages, function (p) {
        if (!window.PAGINATION) window.PAGINATION = {};
        window.PAGINATION.audit_logs = p;
        render();
      });
    }
  }

  function applyFilters() {
    if (!window.PAGINATION) window.PAGINATION = {};
    window.PAGINATION.audit_logs = 1;
    render();
  }

  function resetFilters() {
    var actEl = document.getElementById('filterAction');
    var userEl = document.getElementById('filterUser');
    var searchEl = document.getElementById('filterSearch');

    if (actEl) actEl.value = '';
    if (userEl) userEl.value = '';
    if (searchEl) searchEl.value = '';

    if (!window.PAGINATION) window.PAGINATION = {};
    window.PAGINATION.audit_logs = 1;
    render();
  }

  function init() {
    var tableEl = document.getElementById('auditLogsTable');
    if (tableEl && typeof TableSort !== 'undefined') {
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
    resetFilters: resetFilters,
    render: render
  };
})();

document.addEventListener('DOMContentLoaded', function () {
  ReportsApp.init();
});
