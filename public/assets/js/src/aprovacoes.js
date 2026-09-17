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
  var VIEW_MODE = localStorage.getItem('approvals_view') || 'card';
  var SEARCH_TIMER = null;
  var CURRENT_PREVIEW_DOC = null;
  var CURRENT_REJECT_ID = null;

  var ICONS_MAP = {
    folder: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>',
    doc: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
    auth: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
    compute: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
    network: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
    devops: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
    db: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>',
    tool: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
    idea: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><line x1="9" y1="18" x2="15" y2="18"/><line x1="10" y1="22" x2="14" y2="22"/><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5.76.76 1.23 1.52 1.41 2.5z"/></svg>',
    goal: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
    rocket: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4.5c1.47-1.47 4.5-2 4.5-2"/><path d="M12 15v5s3.03-.55 4.5-2c1.47-1.47 2-4.5 2-4.5"/></svg>',
    book: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>'
  };

  /* ─── INIT ──────────────────────────────────────────────────── */

  function init() {
    applyViewMode(VIEW_MODE, false);
    bindEvents();
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

    if (btnCard) btnCard.classList.toggle('active', mode === 'card');
    if (btnList) btnList.classList.toggle('active', mode === 'list');

    if (grid) {
      if (mode === 'list') {
        grid.classList.add('approvals-list-view');
        grid.classList.remove('approvals-grid-view');
      } else {
        grid.classList.remove('approvals-list-view');
        grid.classList.add('approvals-grid-view');
      }
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
    if (!grid) return;

    if (!ALL_DOCS.length) {
      grid.innerHTML = '';
      if (emptyState) emptyState.hidden = false;
      return;
    }
    if (emptyState) emptyState.hidden = true;

    if (VIEW_MODE === 'list') {
      renderTable(grid);
    } else {
      renderCards(grid);
    }
  }

  /* ─── HELPERS: badges & actions ─────────────────────────────── */

  function buildStatusBadge(status) {
    if (status === 'aprovado') {
      return '<span class="approval-badge approved"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:11px;height:11px;margin-right:2px"><polyline points="20 6 9 17 4 12"/></svg>Publicado</span>';
    }
    if (status === 'rejeitado') {
      return '<span class="approval-badge rejected"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:11px;height:11px;margin-right:2px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>Rejeitado</span>';
    }
    return '<span class="approval-badge pending"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:11px;height:11px;margin-right:2px"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Aguardando</span>';
  }

  function buildActionsHtml(d) {
    if (d.status === 'pendente') {
      return '<button type="button" class="table-action-btn" onclick="event.stopPropagation();Approvals.previewDoc(' + d.id + ')" title="Revisar documento">'
        + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>'
        + '<button type="button" class="table-action-btn" style="color:var(--danger,#d63939)" onclick="event.stopPropagation();Approvals.openRejectModal(' + d.id + ')" title="Rejeitar">'
        + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>'
        + '<button type="button" class="table-action-btn" style="color:var(--docs-primary,#1abb9c)" onclick="event.stopPropagation();Approvals.approveDoc(' + d.id + ')" title="Aprovar e publicar">'
        + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><polyline points="20 6 9 17 4 12"/></svg></button>';
    }
    var html = '<button type="button" class="table-action-btn" onclick="event.stopPropagation();window.location.href=\'/docs/view/' + d.id + '\'" title="Visualizar">'
      + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>';
    if (d.status === 'rejeitado') {
      html += '<button type="button" class="table-action-btn" style="color:var(--docs-primary,#1abb9c)" onclick="event.stopPropagation();Approvals.approveDoc(' + d.id + ')" title="Reavaliar e Aprovar">'
        + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><polyline points="20 6 9 17 4 12"/></svg></button>';
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

  function renderTable(grid) {
    var html = '<div class="table-responsive">'
      + '<table class="table" id="approvalsTable">'
      + '<thead><tr>'
      + '<th style="min-width:260px">Título</th>'
      + '<th style="min-width:140px">Pasta</th>'
      + '<th style="min-width:130px">Autor</th>'
      + '<th style="min-width:130px">Status</th>'
      + '<th style="min-width:130px">Enviado em</th>'
      + '<th style="min-width:110px;text-align:right">Ações</th>'
      + '</tr></thead>'
      + '<tbody>';

    for (var i = 0; i < ALL_DOCS.length; i++) {
      var d = ALL_DOCS[i];
      var catIcon = d.category_icon && ICONS_MAP[d.category_icon] ? ICONS_MAP[d.category_icon] : ICONS_MAP['folder'];
      var catHtml = d.category_name
        ? '<span class="doc-category-badge" style="font-size:0.72rem;padding:0.15rem 0.5rem;">' + catIcon + ' ' + esc(d.category_name) + '</span>'
        : '<span style="color:var(--docs-text-mut);font-size:0.75rem">—</span>';

      var rejNote = (d.status === 'rejeitado' && d.rejection_reason)
        ? '<div style="font-size:0.7rem;color:var(--danger,#d63939);margin-top:2px;max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="' + esc(d.rejection_reason) + '">↩ ' + esc(d.rejection_reason) + '</div>'
        : '';

      html += '<tr>'
        + '<td>'
        + '<div class="table-doc-cell" onclick="Approvals.openPreview(' + d.id + ')" style="cursor:pointer;" title="' + esc(d.title) + '">'
        + '<span class="table-doc-icon">'
        + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>'
        + '</span>'
        + '<div>'
        + '<span class="table-doc-name">' + esc(d.title) + '</span>'
        + rejNote
        + '</div>'
        + '</div>'
        + '</td>'
        + '<td>' + catHtml + '</td>'
        + '<td style="font-size:0.82rem;color:var(--docs-text-mut)">' + esc(d.author_name || '—') + '</td>'
        + '<td style="font-size:0.78rem;">' + buildStatusBadge(d.status) + (d.version_count > 0 ? ' <span style="font-size:0.7rem;color:var(--docs-text-mut);margin-left:2px;">v' + d.version_count + '</span>' : '') + '</td>'
        + '<td style="font-size:0.8rem;color:var(--docs-text-mut);white-space:nowrap">' + formatDate(d.updated_at) + '</td>'
        + '<td style="text-align:right;white-space:nowrap">'
        + '<div style="display:inline-flex;gap:2px;align-items:center">'
        + buildActionsHtml(d)
        + '</div>'
        + '</td>'
        + '</tr>';
    }

    html += '</tbody></table></div>';
    grid.innerHTML = html;
  }

  /* ─── MODAL: PREVIEW ────────────────────────────────────────── */

  function previewDoc(id) {
    api('GET', '/api/docs/' + id).then(function (res) {
      CURRENT_PREVIEW_DOC = res.doc;
      var modal   = document.getElementById('previewApprovalModal');
      var title   = document.getElementById('previewTitle');
      var meta    = document.getElementById('previewMeta');
      var body    = document.getElementById('previewBody');
      var notice  = document.getElementById('previewStatusNotice');
      var actions = document.getElementById('previewModalActions');

      if (title) title.textContent = CURRENT_PREVIEW_DOC.title || '';
      if (meta) {
        var cat    = CURRENT_PREVIEW_DOC.category_name ? ('📁 ' + CURRENT_PREVIEW_DOC.category_name) : '📂 Geral';
        var author = '👤 ' + (CURRENT_PREVIEW_DOC.author_name || 'Autor');
        var date   = '🕒 ' + formatDate(CURRENT_PREVIEW_DOC.updated_at);
        meta.textContent = author + ' • ' + cat + ' • ' + date;
      }

      if (notice) {
        if (CURRENT_PREVIEW_DOC.status === 'rejeitado' && CURRENT_PREVIEW_DOC.rejection_reason) {
          notice.hidden = false;
          notice.className = 'approval-rejection-callout';
          notice.innerHTML = '<strong>Motivo da rejeição anterior:</strong> ' + esc(CURRENT_PREVIEW_DOC.rejection_reason);
        } else {
          notice.hidden = true;
        }
      }

      if (body) {
        var raw = CURRENT_PREVIEW_DOC.content || '';
        if (typeof marked !== 'undefined' && typeof marked.parse === 'function') {
          body.innerHTML = marked.parse(raw);
        } else {
          body.innerHTML = '<div style="white-space:pre-wrap;">' + esc(raw) + '</div>';
        }
      }

      if (actions) {
        actions.hidden = !(CURRENT_PREVIEW_DOC.status === 'pendente' || CURRENT_PREVIEW_DOC.status === 'rejeitado');
      }
      if (modal) modal.hidden = false;
    }).catch(function (err) {
      toast(err.message || 'Erro ao carregar documento.', 'error');
    });
  }

  function closePreviewModal() {
    var modal = document.getElementById('previewApprovalModal');
    if (modal) modal.hidden = true;
    CURRENT_PREVIEW_DOC = null;
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
    if (!CURRENT_PREVIEW_DOC) return;
    approveDoc(CURRENT_PREVIEW_DOC.id);
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
    if (!CURRENT_PREVIEW_DOC) return;
    openRejectModal(CURRENT_PREVIEW_DOC.id);
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
  };
})();

document.addEventListener('DOMContentLoaded', Approvals.init);
