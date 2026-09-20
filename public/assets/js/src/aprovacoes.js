/**
 * ==========================================================================
 * DOCMANAGER PRO - MÓDULO DE APROVAÇÕES DE DOCUMENTOS
 * ==========================================================================
 */
'use strict';

var Approvals = (function () {
  var ALL_DOCS = [];
  var STATS = {};
  var CURRENT_STATUS = 'pendente';
  var VIEW_MODE = localStorage.getItem('approvals_view') || 'list';
  var SEARCH_TIMER = null;
  var CURRENT_PREVIEW_DOC = null;
  var CURRENT_REJECT_ID = null;
  var CURRENT_PAGE = 1;
  var PER_PAGE = 10;

  var ICONS_MAP = Icons.getCategoryMap();

  /* ─── INIT ──────────────────────────────────────────────────── */

  function init() {
    applyViewMode(VIEW_MODE, false);
    bindEvents();
    var tableEl = document.getElementById('approvalsTable');
    if (tableEl && typeof TableSort !== 'undefined') {
      TableSort.init(tableEl, {
        onSort: function () {
          renderTable();
        }
      });
    }
    loadApprovals();
  }

  function bindEvents() {
    var searchInput = document.getElementById('approvalsSearch');
    if (searchInput) {
      searchInput.addEventListener('input', function () {
        clearTimeout(SEARCH_TIMER);
        SEARCH_TIMER = setTimeout(loadApprovals, 250);
      });
    }

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        closePreviewModal();
        closeRejectModal();
      }
    });
  }

  /* ─── VIEW MODE TOGGLE ──────────────────────────────────────── */

  function setViewMode(mode) {
    VIEW_MODE = mode;
    localStorage.setItem('approvals_view', mode);
    applyViewMode(mode, true);
  }

  function applyViewMode(mode, rerender) {
    var btnCard = document.getElementById('approvalsBtnCard');
    var btnList = document.getElementById('approvalsBtnList');
    var grid = document.getElementById('approvalsGrid');
    var tableWrapper = document.getElementById('approvalsTableWrapper');

    if (btnCard) btnCard.classList.toggle('active', mode === 'card');
    if (btnList) btnList.classList.toggle('active', mode === 'list');

    if (mode === 'list') {
      if (grid) grid.hidden = true;
      if (tableWrapper) tableWrapper.hidden = false;
    } else {
      if (grid) grid.hidden = false;
      if (tableWrapper) tableWrapper.hidden = true;
    }

    if (rerender) renderView();
  }

  /* ─── TABS & STATUS FILTER ──────────────────────────────────── */

  function setTab(status, el) {
    CURRENT_STATUS = status;
    var tabs = document.querySelectorAll('.approval-tab');
    tabs.forEach(function (tab) {
      tab.classList.toggle('active', tab.getAttribute('data-status') === status);
    });
    var statCards = document.querySelectorAll('.approval-stat-card');
    statCards.forEach(function (card) { card.classList.remove('active'); });
    loadApprovals();
  }

  function filterStatus(status, el) {
    CURRENT_STATUS = status;
    var statCards = document.querySelectorAll('.approval-stat-card');
    statCards.forEach(function (card) {
      card.classList.toggle('active', card === el);
    });
    var tabs = document.querySelectorAll('.approval-tab');
    tabs.forEach(function (tab) {
      tab.classList.toggle('active', tab.getAttribute('data-status') === status);
    });
    loadApprovals();
  }

  /* ─── DATA LOADING ──────────────────────────────────────────── */

  function loadApprovals() {
    var q = (document.getElementById('approvalsSearch') ? document.getElementById('approvalsSearch').value : '').trim();
    var url = '/api/approvals?status=' + encodeURIComponent(CURRENT_STATUS) + '&q=' + encodeURIComponent(q);

    api('GET', url).then(function (res) {
      ALL_DOCS = res.docs || [];
      STATS = res.stats || {};
      updateCounters();
      renderView();
    }).catch(function (err) {
      toast(err.message || 'Erro ao carregar aprovações.', 'error');
    });
  }

  function updateCounters() {
    var statPending  = document.getElementById('statPendingCount');
    var statApproved = document.getElementById('statApprovedCount');
    var statRejected = document.getElementById('statRejectedCount');
    var statTotal    = document.getElementById('statTotalCount');
    var tabBadge     = document.getElementById('tabBadgePending');

    if (statPending)  statPending.textContent  = STATS.total_pending  || 0;
    if (statApproved) statApproved.textContent = STATS.total_approved || 0;
    if (statRejected) statRejected.textContent = STATS.total_rejected || 0;
    if (statTotal)    statTotal.textContent    = STATS.total_docs     || 0;
    if (tabBadge)     tabBadge.textContent     = STATS.total_pending  || 0;
  }

  /* ─── RENDER DISPATCHER ─────────────────────────────────────── */

  function renderView() {
    var emptyState = document.getElementById('approvalsEmptyState');
    var grid = document.getElementById('approvalsGrid');
    var tableWrapper = document.getElementById('approvalsTableWrapper');
    if (!grid) return;

    if (!ALL_DOCS.length) {
      grid.innerHTML = '';
      grid.hidden = true;
      if (tableWrapper) tableWrapper.hidden = true;
      if (emptyState) emptyState.hidden = false;
      return;
    }
    if (emptyState) emptyState.hidden = true;

    if (VIEW_MODE === 'list') {
      grid.hidden = true;
      if (tableWrapper) tableWrapper.hidden = false;
      renderTable();
    } else {
      if (tableWrapper) tableWrapper.hidden = true;
      grid.hidden = false;
      renderCards(grid);
    }
  }

  /* ─── HELPERS: badges & actions ─────────────────────────────── */

  function buildStatusBadge(status) {
    if (status === 'aprovado') {
      return '<span class="approval-badge approved" style="display:inline-flex;align-items:center;gap:3px;">' + Icons.get('check', { size: 11 }) + 'Publicado</span>';
    }
    if (status === 'rejeitado') {
      return '<span class="approval-badge rejected" style="display:inline-flex;align-items:center;gap:3px;">' + Icons.get('close', { size: 11 }) + 'Rejeitado</span>';
    }
    return '<span class="approval-badge pending" style="display:inline-flex;align-items:center;gap:3px;">' + Icons.get('clock', { size: 11 }) + 'Aguardando</span>';
  }

  function buildActionsHtml(d) {
    if (d.status === 'pendente') {
      return '<button type="button" class="table-action-btn" onclick="event.stopPropagation();Approvals.previewDoc(' + d.id + ')" title="Revisar documento">'
        + Icons.eye + '</button>'
        + '<button type="button" class="table-action-btn" style="color:var(--danger,#d63939)" onclick="event.stopPropagation();Approvals.openRejectModal(' + d.id + ')" title="Rejeitar">'
        + Icons.close + '</button>'
        + '<button type="button" class="table-action-btn" style="color:var(--docs-primary,#1abb9c)" onclick="event.stopPropagation();Approvals.approveDoc(' + d.id + ')" title="Aprovar e publicar">'
        + Icons.check + '</button>';
    }
    var html = '<button type="button" class="table-action-btn" onclick="event.stopPropagation();Approvals.previewDoc(' + d.id + ')" title="Visualizar / Revisar">'
      + Icons.eye + '</button>';
    if (d.status === 'rejeitado') {
      html += '<button type="button" class="table-action-btn" style="color:var(--docs-primary,#1abb9c)" onclick="event.stopPropagation();Approvals.approveDoc(' + d.id + ')" title="Reavaliar e Aprovar">'
        + Icons.check + '</button>';
    }
    return html;
  }

  /* ─── RENDER: CARDS ─────────────────────────────────────────── */

  function renderCards(grid) {
    var html = '';
    for (var i = 0; i < ALL_DOCS.length; i++) {
      var d = ALL_DOCS[i];
      var catIcon = d.category_icon && ICONS_MAP[d.category_icon] ? ICONS_MAP[d.category_icon] : ICONS_MAP['folder'];
      var catBadge = d.category_name
        ? '<span class="doc-category-badge">' + catIcon + ' ' + esc(d.category_name) + '</span>'
        : '<span class="doc-category-badge" style="background:var(--docs-surface-2);color:var(--docs-text-mut)">' + ICONS_MAP['folder'] + ' Geral</span>';

      var cleanPreview = stripMarkdown(d.content || '').substring(0, 160);

      var versionHtml = d.version_count > 0
        ? '<span style="font-size:0.7rem;color:var(--docs-text-mut);background:var(--docs-surface-2);padding:1px 6px;border-radius:4px;">v' + d.version_count + '</span>'
        : '';

      var rejectionHtml = '';
      if (d.status === 'rejeitado' && d.rejection_reason) {
        rejectionHtml = '<div class="approval-rejection-callout">'
          + '<strong>Motivo da rejeição:</strong> ' + esc(d.rejection_reason)
          + '</div>';
      }

      html += '<div class="approval-card-item">'
        + '<div class="card-top">'
        + catBadge
        + buildStatusBadge(d.status)
        + '</div>'
        + '<h3 class="card-title">' + esc(d.title) + '</h3>'
        + '<div class="card-preview">' + (cleanPreview ? esc(cleanPreview) : '<em>Documento sem conteúdo</em>') + '</div>'
        + rejectionHtml
        + '<div class="card-footer-info">'
        + '<span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;margin-right:2px"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Autor: <strong>' + esc(d.author_name || 'Desconhecido') + '</strong></span>'
        + '<span>🕒 Enviado em: ' + formatDate(d.updated_at) + '</span>'
        + versionHtml
        + (d.status === 'rejeitado' && d.rejected_by_name
            ? '<span style="color:var(--danger,#d63939)">↩ Rejeitado por: ' + esc(d.rejected_by_name) + '</span>'
            : (d.approved_by_name ? '<span>🛡️ Aprovado por: ' + esc(d.approved_by_name) + '</span>' : ''))
        + '</div>'
        + '<div class="card-actions-bar">'
        + buildActionsHtml(d)
        + '</div>'
        + '</div>';
    }
    grid.innerHTML = html;
  }

  /* ─── RENDER: TABLE (LIST VIEW) ─────────────────────────────── */

  function renderTable() {
    var tbody = document.getElementById('approvalsTableBody');
    if (!tbody) return;

    var docsToRender = ALL_DOCS.slice();

    // Ordenação com TableSort se ativa
    if (typeof TableSort !== 'undefined' && TableSort.getSort) {
      var sortState = TableSort.getSort('approvalsTable');
      if (sortState && sortState.col) {
        docsToRender = TableSort.sortArray(docsToRender, sortState.col, sortState.dir, function (d, col) {
          if (col === 'title') return d.title || '';
          if (col === 'category') return d.category_name || '';
          if (col === 'author') return d.author_name || '';
          if (col === 'status') return d.status || '';
          if (col === 'updated') return d.updated_at || '';
          return d.id;
        });
      }
    }

    var totalItems = docsToRender.length;
    var totalPages = Math.ceil(totalItems / PER_PAGE) || 1;
    if (CURRENT_PAGE > totalPages) CURRENT_PAGE = totalPages;
    if (CURRENT_PAGE < 1) CURRENT_PAGE = 1;

    var startIndex = (CURRENT_PAGE - 1) * PER_PAGE;
    var endIndex = Math.min(startIndex + PER_PAGE, totalItems);
    var pageDocs = docsToRender.slice(startIndex, endIndex);

    var html = '';
    for (var i = 0; i < pageDocs.length; i++) {
      var d = pageDocs[i];
      var catIcon = d.category_icon && ICONS_MAP[d.category_icon] ? ICONS_MAP[d.category_icon] : ICONS_MAP['folder'];
      var catColor = d.category_color || 'var(--docs-primary, #1abb9c)';
      var catHtml = d.category_name
        ? '<span class="doc-category-badge" style="font-size:0.75rem;padding:0.18rem 0.55rem;border-left:3px solid ' + esc(catColor) + ';"><span style="color:' + esc(catColor) + '">' + catIcon + '</span> ' + esc(d.category_name) + '</span>'
        : '<span style="color:var(--docs-text-mut);font-size:0.75rem">—</span>';

      var rejNote = (d.status === 'rejeitado' && d.rejection_reason)
        ? '<div style="font-size:0.7rem;color:var(--danger,#d63939);margin-top:2px;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="' + esc(d.rejection_reason) + '">↩ ' + esc(d.rejection_reason) + '</div>'
        : '';

      var docIcon = '<span style="color:' + esc(catColor) + '">' + (d.category_icon && ICONS_MAP[d.category_icon] ? ICONS_MAP[d.category_icon] : ICONS_MAP['doc']) + '</span>';

      html += '<tr>'
        + '<td style="width: 40px; text-align: center;"><span style="font-size:1.1rem;display:inline-flex;align-items:center;">' + docIcon + '</span></td>'
        + '<td>'
        + '<div class="table-doc-title" onclick="Approvals.previewDoc(' + d.id + ')" style="cursor:pointer;font-weight:600;color:var(--docs-text);" title="Clique para revisar ' + esc(d.title) + '">'
        + esc(d.title)
        + rejNote
        + '</div>'
        + '</td>'
        + '<td>' + catHtml + '</td>'
        + '<td><span style="font-size:0.78rem;color:var(--docs-text-mut);font-weight:500;display:inline-flex;align-items:center;gap:4px;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>' + esc(d.author_name || 'Admin') + '</span></td>'
        + '<td>' + buildStatusBadge(d.status) + (d.version_count > 0 ? ' <span style="font-size:0.7rem;color:var(--docs-text-mut);margin-left:2px;">v' + d.version_count + '</span>' : '') + '</td>'
        + '<td><span style="font-size:0.8rem;color:var(--docs-text-mut);white-space:nowrap">' + formatDate(d.updated_at) + '</span></td>'
        + '<td style="text-align:right;white-space:nowrap">'
        + '<div class="table-actions">'
        + buildActionsHtml(d)
        + '</div>'
        + '</td>'
        + '</tr>';
    }

    tbody.innerHTML = html;

    if (typeof renderPagination === 'function') {
      renderPagination('approvalsPagination', totalItems, CURRENT_PAGE, totalPages, function (p) {
        CURRENT_PAGE = p;
        renderTable();
      });
    }
  }

  /* ─── MODAL: PREVIEW E COMPARAÇÃO DE VERSÕES (GIT DIFF) ──────── */

  var CURRENT_PREVIEW_TAB = 'diff';

  function computeLineDiff(oldText, newText) {
    var oldLines = (oldText || '').replace(/\r\n/g, '\n').split('\n');
    var newLines = (newText || '').replace(/\r\n/g, '\n').split('\n');

    var M = oldLines.length;
    var N = newLines.length;

    if (oldText === newText) {
      var same = [];
      for (var k = 0; k < M; k++) {
        same.push({ type: 'same', oldLine: oldLines[k], newLine: newLines[k], oldNum: k + 1, newNum: k + 1 });
      }
      return same;
    }

    var dp = [];
    for (var i = 0; i <= M; i++) {
      dp.push(new Int32Array(N + 1));
    }

    for (var i = 1; i <= M; i++) {
      for (var j = 1; j <= N; j++) {
        if (oldLines[i - 1] === newLines[j - 1]) {
          dp[i][j] = dp[i - 1][j - 1] + 1;
        } else {
          dp[i][j] = dp[i - 1][j] > dp[i][j - 1] ? dp[i - 1][j] : dp[i][j - 1];
        }
      }
    }

    var i = M, j = N;
    var ops = [];
    while (i > 0 || j > 0) {
      if (i > 0 && j > 0 && oldLines[i - 1] === newLines[j - 1]) {
        ops.unshift({ type: 'same', oldLine: oldLines[i - 1], newLine: newLines[j - 1], oldNum: i, newNum: j });
        i--; j--;
      } else if (j > 0 && (i === 0 || dp[i][j - 1] >= dp[i - 1][j])) {
        ops.unshift({ type: 'add', newLine: newLines[j - 1], newNum: j });
        j--;
      } else {
        ops.unshift({ type: 'delete', oldLine: oldLines[i - 1], oldNum: i });
        i--;
      }
    }

    return ops;
  }

  function renderSideBySideDiff(ops, oldVersionLabel, newVersionLabel) {
    var leftRows = [];
    var rightRows = [];
    var addCount = 0;
    var delCount = 0;

    var k = 0;
    while (k < ops.length) {
      var op = ops[k];
      if (op.type === 'same') {
        leftRows.push({ num: op.oldNum, text: op.oldLine, type: 'same' });
        rightRows.push({ num: op.newNum, text: op.newLine, type: 'same' });
        k++;
      } else if (op.type === 'delete') {
        if (k + 1 < ops.length && ops[k + 1].type === 'add') {
          var nextAdd = ops[k + 1];
          leftRows.push({ num: op.oldNum, text: op.oldLine, type: 'delete' });
          rightRows.push({ num: nextAdd.newNum, text: nextAdd.newLine, type: 'add' });
          delCount++;
          addCount++;
          k += 2;
        } else {
          leftRows.push({ num: op.oldNum, text: op.oldLine, type: 'delete' });
          rightRows.push({ num: '', text: '', type: 'empty' });
          delCount++;
          k++;
        }
      } else if (op.type === 'add') {
        leftRows.push({ num: '', text: '', type: 'empty' });
        rightRows.push({ num: op.newNum, text: op.newLine, type: 'add' });
        addCount++;
        k++;
      }
    }

    var leftHtml = '';
    var rightHtml = '';

    for (var r = 0; r < leftRows.length; r++) {
      var l = leftRows[r];
      var rg = rightRows[r];

      var lClass = l.type === 'delete' ? 'diff-line-delete' : (l.type === 'empty' ? 'diff-line-empty' : 'diff-line-same');
      var lSymbol = l.type === 'delete' ? '-' : ' ';
      leftHtml += '<tr class="' + lClass + '">'
        + '<td class="diff-line-num">' + (l.num || '') + '</td>'
        + '<td class="diff-line-symbol">' + lSymbol + '</td>'
        + '<td class="diff-line-content">' + esc(l.text) + '</td>'
        + '</tr>';

      var rClass = rg.type === 'add' ? 'diff-line-add' : (rg.type === 'empty' ? 'diff-line-empty' : 'diff-line-same');
      var rSymbol = rg.type === 'add' ? '+' : ' ';
      rightHtml += '<tr class="' + rClass + '">'
        + '<td class="diff-line-num">' + (rg.num || '') + '</td>'
        + '<td class="diff-line-symbol">' + rSymbol + '</td>'
        + '<td class="diff-line-content">' + esc(rg.text) + '</td>'
        + '</tr>';
    }

    var statsHtml = '<div style="display:flex;align-items:center;gap:0.5rem;font-size:0.78rem;font-weight:600;">'
      + '<span style="color:#166534;background:#dcfce7;padding:3px 9px;border-radius:12px;">+' + addCount + ' adições</span>'
      + '<span style="color:#991b1b;background:#fee2e2;padding:3px 9px;border-radius:12px;">-' + delCount + ' remoções</span>'
      + '</div>';

    var containerHtml = '<div class="diff-wrapper">'
      + '<div class="diff-grid">'
      + '<div class="diff-pane diff-pane-left">'
      + '<div class="diff-pane-header"><span>◀ ' + esc(oldVersionLabel) + '</span></div>'
      + '<table class="diff-table"><tbody>' + leftHtml + '</tbody></table>'
      + '</div>'
      + '<div class="diff-pane diff-pane-right">'
      + '<div class="diff-pane-header"><span>▶ ' + esc(newVersionLabel) + '</span></div>'
      + '<table class="diff-table"><tbody>' + rightHtml + '</tbody></table>'
      + '</div>'
      + '</div>'
      + '</div>';

    return {
      html: containerHtml,
      statsHtml: statsHtml
    };
  }

  function switchPreviewTab(tab) {
    DocDiff.switchTab(tab);
  }

  function onVersionSelectChange() {
    DocDiff.onVersionSelectChange();
  }

  function compareVersionIndex(idx) {
    DocDiff.compareVersionIndex(idx);
  }

  function previewDoc(id) {
    DocDiff.open(id, { showApprovalButtons: true });
  }

  function closePreviewModal() {
    DocDiff.close();
  }

  /* ─── APPROVE / REJECT ──────────────────────────────────────── */

  function approveDoc(id) {
    showConfirm({
      title: 'Aprovar e Publicar Documento?',
      message: 'O documento será publicado imediatamente e ficará visível para todos os usuários com acesso de leitura.',
      confirmText: 'Aprovar e Publicar',
      confirmClass: 'btn btn-primary'
    }, function () {
      api('POST', '/api/approvals/' + id + '/approve', {}).then(function () {
        toast('Documento aprovado e publicado com sucesso!', 'success');
        closePreviewModal();
        loadApprovals();
      }).catch(function (err) {
        toast(err.message || 'Erro ao aprovar documento.', 'error');
      });
    });
  }

  function approveFromPreview() {
    var doc = DocDiff.getCurrentDoc();
    if (!doc) return;
    approveDoc(doc.id);
  }

  function openRejectModal(id) {
    CURRENT_REJECT_ID = id;
    var modal = document.getElementById('rejectReasonModal');
    var input = document.getElementById('rejectReasonText');
    if (input) input.value = '';
    if (modal) modal.hidden = false;
    if (input) input.focus();
  }

  function openRejectFromPreview() {
    var doc = DocDiff.getCurrentDoc();
    if (!doc) return;
    openRejectModal(doc.id);
  }

  function closeRejectModal() {
    var modal = document.getElementById('rejectReasonModal');
    if (modal) modal.hidden = true;
    CURRENT_REJECT_ID = null;
  }

  function confirmReject() {
    if (!CURRENT_REJECT_ID) return;
    var reason = (document.getElementById('rejectReasonText') ? document.getElementById('rejectReasonText').value : '').trim();
    if (!reason) {
      toast('Por favor, informe a justificativa da rejeição.', 'error');
      document.getElementById('rejectReasonText').focus();
      return;
    }

    api('POST', '/api/approvals/' + CURRENT_REJECT_ID + '/reject', { reason: reason }).then(function () {
      toast('Documento rejeitado e devolvido para ajustes.', 'info');
      closeRejectModal();
      closePreviewModal();
      loadApprovals();
    }).catch(function (err) {
      toast(err.message || 'Erro ao rejeitar documento.', 'error');
    });
  }

  /* ─── UTILS ─────────────────────────────────────────────────── */

  function esc(s) {
    if (s === null || s === undefined) return '';
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function stripMarkdown(md) {
    if (!md) return '';
    return md
      .replace(/!\[.*?\]\(.*?\)/g, '')
      .replace(/\[(.*?)\]\(.*?\)/g, '$1')
      .replace(/`{1,3}.*?`{1,3}/gs, '')
      .replace(/#{1,6}\s+/g, '')
      .replace(/(\*\*|__)(.*?)\1/g, '$2')
      .replace(/(\*|_)(.*?)\1/g, '$2')
      .replace(/~~(.*?)~~/g, '$1')
      .replace(/^\s*[-*+]\s+/gm, '')
      .replace(/^\s*\d+\.\s+/gm, '')
      .replace(/^\s*>\s+/gm, '')
      .replace(/\n+/g, ' ')
      .trim();
  }

  function parseMarkdown(md) {
    if (!md) return '';
    if (typeof window.parseMarkdown === 'function' && window.parseMarkdown !== parseMarkdown) {
      return window.parseMarkdown(md);
    }
    if (typeof window.marked === 'function') {
      return window.marked.parse(md);
    }
    if (window.marked && typeof window.marked.parse === 'function') {
      return window.marked.parse(md);
    }
    return esc(md).replace(/\n/g, '<br>');
  }

  function formatDate(d) {
    if (!d) return '—';
    try {
      var date = new Date(d.replace(' ', 'T'));
      if (isNaN(date.getTime())) return d;
      return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' })
        + ' ' + date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    } catch (e) {
      return d;
    }
  }

  return {
    init:                init,
    setViewMode:         setViewMode,
    setTab:              setTab,
    filterStatus:        filterStatus,
    previewDoc:          previewDoc,
    closePreviewModal:   closePreviewModal,
    approveDoc:          approveDoc,
    approveFromPreview:  approveFromPreview,
    openRejectModal:     openRejectModal,
    openRejectFromPreview: openRejectFromPreview,
    closeRejectModal:    closeRejectModal,
    confirmReject:       confirmReject,
    switchPreviewTab:    switchPreviewTab,
    onVersionSelectChange: onVersionSelectChange,
    compareVersionIndex: compareVersionIndex,
  };
})();

document.addEventListener('DOMContentLoaded', Approvals.init);
