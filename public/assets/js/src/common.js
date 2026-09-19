/**
 * Core Basic — Módulos compartilhados
 * API, toast, formatação, SLA, sidebar/tema, logout.
 */
'use strict';

var ME = window.ME || null;
var CSRF_TOKEN = window.CSRF_TOKEN || '';

var ROLEMAP = {
  'Admin': { canNew: true, canOpenModal: true, isAdmin: true, movesFrom: null },
};

var fmt = function (x) { return x ? new Date(x).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }) : '-'; };
var fmtFull = function (x) { return x ? new Date(x).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: '2-digit', hour: '2-digit', minute: '2-digit' }) : '-'; };
var minutes = function (a, b) { b = b || new Date(); return Math.round((new Date(b) - new Date(a)) / 60000); };
var esc = function (v) { return String(v == null ? '' : v).replace(/[&<>'"]/g, function (m) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[m]; }); };

function parseMarkdown(text) {
  if (!text) return '';
  // Substitui o alias '}}' no início de linhas pelo recuo de parágrafo HTML &emsp;&emsp;
  var processed = String(text).replace(/(^|\n)[ \t]*}}([ \t]?)/g, '$1&emsp;&emsp;');

  // Processa tags de senhas !psk=MinhaSenha! encapsulando em elemento seguro com hover reveal
  processed = processed.replace(/!psk=([^!\r\n]+)!/g, function (match, psk) {
    var rawPsk = psk.trim();
    var safeAttr = esc(rawPsk);
    var safeText = esc(rawPsk);
    return '<span class="psk-badge" data-psk="' + safeAttr + '" title="Passe o cursor para revelar a senha">' +
      '<i class="fa-solid fa-key psk-icon"></i>' +
      '<span class="psk-masked">••••••••</span>' +
      '<span class="psk-revealed">' + safeText + '</span>' +
      '<button type="button" class="psk-copy-btn" onclick="copySecretPsk(this, event)" title="Copiar senha">' +
        '<i class="fa-regular fa-copy"></i>' +
      '</button>' +
    '</span>';
  });

  if (typeof marked !== 'undefined' && typeof marked.parse === 'function') {
    return marked.parse(processed);
  }
  return '<div style="white-space:pre-wrap;">' + processed + '</div>';
}

function copySecretPsk(btn, event) {
  if (event) {
    event.stopPropagation();
    event.preventDefault();
  }
  var badge = btn.closest('.psk-badge');
  if (!badge) return;
  var psk = badge.getAttribute('data-psk') || '';
  if (!psk) return;

  var copyAction = (navigator.clipboard && navigator.clipboard.writeText)
    ? navigator.clipboard.writeText(psk)
    : new Promise(function (resolve, reject) {
        var ta = document.createElement('textarea');
        ta.value = psk;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        var ok = document.execCommand('copy');
        document.body.removeChild(ta);
        ok ? resolve() : reject(new Error('Erro execCommand'));
      });

  copyAction.then(function () {
    var icon = btn.querySelector('i');
    if (icon) {
      var prevClass = icon.className;
      icon.className = 'fa-solid fa-check';
      setTimeout(function () { icon.className = prevClass; }, 1600);
    }
    if (typeof toast === 'function') {
      toast('Senha copiada!', 'success');
    }
  }).catch(function () {
    if (typeof toast === 'function') {
      toast('Não foi possível copiar a senha.', 'error');
    }
  });
}
window.copySecretPsk = copySecretPsk;
window.parseMarkdown = parseMarkdown;

/* ------------------------------------------------------------------ API */

function api(method, url, body) {
  var opts = { method: method, headers: { 'X-CSRF-Token': CSRF_TOKEN } };
  if (body !== undefined) {
    opts.headers['Content-Type'] = 'application/json';
    opts.body = JSON.stringify(body);
  }
  return fetch(url, opts).then(function (res) {
    return res.json().then(function (json) {
      if (!res.ok) {
        throw new Error(json && json.error ? json.error : ('Erro ' + res.status));
      }
      return json ? json.data : null;
    });
  });
}

/* ------------------------------------------------------------------ Toast */

function toast(msg, kind) {
  var t = document.getElementById('toast');
  if (!t) return;
  t.textContent = msg;
  t.className = 'toast ' + (kind === 'error' ? 'toast-error' : (kind === 'warning' ? 'toast-warning' : 'toast-success'));
  t.classList.remove('show');
  void t.offsetWidth;
  t.classList.add('show');
  clearTimeout(toast._timer);
  toast._timer = setTimeout(function () { t.classList.remove('show'); }, 2800);
}
window.toast = toast;
window.showToast = toast;

/* ------------------------------------------------------------------ Paginação (padrão global) */

var PAGE_SIZE = (window.SETTINGS && parseInt(window.SETTINGS.per_page, 10)) || 10;
var PAGINATION = {}; // { chave: paginaAtual } — mesmo estilo em todas as tabelas

function paginated(key, items) {
  if (!PAGINATION[key] || PAGINATION[key] > Math.max(1, Math.ceil(items.length / PAGE_SIZE))) {
    PAGINATION[key] = 1;
  }
  var start = (PAGINATION[key] - 1) * PAGE_SIZE;
  return {
    page: items.slice(start, start + PAGE_SIZE),
    total: items.length,
    current: PAGINATION[key],
    pages: Math.max(1, Math.ceil(items.length / PAGE_SIZE))
  };
}

/**
 * Renderiza o rodapé de paginação num contêiner (.pagination-footer).
 * @param {string} key chave de estado da página
 * @param {number} total itens totais
 * @param {number} current página atual (1-based)
 * @param {number} pages total de páginas
 * @param {Function} onPage callback(novaPagina)
 */
function renderPagination(key, total, current, pages, onPage) {
  var el = document.getElementById(key);
  if (!el) return;
  if (pages <= 1) { el.innerHTML = ''; return; }

  var from = total === 0 ? 0 : (current - 1) * PAGE_SIZE + 1;
  var to = Math.min(current * PAGE_SIZE, total);
  var html = '<div class="pagination-info">' + from + '–' + to + ' de ' + total + '</div>';
  html += '<div class="pagination-controls">';
  html += '<button class="pg-btn" data-pg="' + (current - 1) + '"' + (current === 1 ? ' disabled' : '') + ' title="Anterior">‹</button>';

  var seen = {};
  for (var p = 1; p <= pages; p++) {
    if (p === 1 || p === pages || Math.abs(p - current) <= 1) {
      seen[p] = true;
      html += '<button class="pg-btn' + (p === current ? ' active' : '') + '" data-pg="' + p + '">' + p + '</button>';
    } else if (!seen[p]) {
      var prevShown = p - 1;
      if (!seen[prevShown] || (p < pages && !seen[p + 1])) {
        seen[p] = true;
        html += '<span class="pg-ellipsis">…</span>';
      }
    }
  }
  html += '<button class="pg-btn" data-pg="' + (current + 1) + '"' + (current === pages ? ' disabled' : '') + ' title="Próxima">›</button>';
  html += '</div>';
  el.innerHTML = html;

  el.querySelectorAll('[data-pg]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var p = parseInt(btn.getAttribute('data-pg'), 10);
      if (p >= 1 && p <= pages && p !== current) { onPage(p); }
    });
  });
}

/* ------------------------------------------------------------------ Sortable Tables */

var TableSort = {
  _state: {},

  init: function(tableEl, options) {
    if (!tableEl) return;
    if (typeof tableEl === 'string') {
      tableEl = document.getElementById(tableEl) || document.querySelector(tableEl);
    }
    if (!tableEl || typeof tableEl.querySelectorAll !== 'function') return;
    if (typeof options === 'function') {
      options = { onSort: options };
    }
    var ths = tableEl.querySelectorAll('th.sortable-th');
    var tableId = tableEl.id || 'table_' + Math.random().toString(36).slice(2, 8);
    this._state[tableId] = { col: null, dir: 'asc' };

    ths.forEach(function(th) {
      th.addEventListener('click', function() {
        var col = th.getAttribute('data-sort');
        if (!col) return;
        var state = TableSort._state[tableId];

        if (state.col === col) {
          state.dir = state.dir === 'asc' ? 'desc' : 'asc';
        } else {
          state.col = col;
          state.dir = 'asc';
        }

        ths.forEach(function(h) { h.classList.remove('sort-asc', 'sort-desc'); });
        th.classList.add(state.dir === 'asc' ? 'sort-asc' : 'sort-desc');

        if (options && typeof options.onSort === 'function') {
          options.onSort(col, state.dir);
        }
      });
    });
  },

  getSort: function(tableId) {
    return this._state[tableId] || { col: null, dir: 'asc' };
  },

  sortArray: function(arr, col, dir, extractFn) {
    return arr.slice().sort(function(a, b) {
      var va = extractFn ? extractFn(a, col) : (a[col] || '');
      var vb = extractFn ? extractFn(b, col) : (b[col] || '');
      if (typeof va === 'string') { va = va.toLowerCase(); vb = (vb || '').toLowerCase(); }
      if (va < vb) return dir === 'asc' ? -1 : 1;
      if (va > vb) return dir === 'asc' ? 1 : -1;
      return 0;
    });
  }
};

/* ------------------------------------------------------------------ Busca global de documentos */

function initGlobalSearch() {
  var input = document.getElementById('globalDocSearchInput') || document.getElementById('globalSearch');
  var panel = document.getElementById('globalDocSearchDropdown') || document.getElementById('searchPanel');
  var clearBtn = document.getElementById('globalDocSearchClear');
  if (!input || !panel) return;

  var timer = null;

  function closePanel() {
    panel.hidden = true;
    panel.innerHTML = '';
    if (clearBtn) clearBtn.hidden = !input.value;
  }

  function run() {
    var q = input.value.trim();
    if (clearBtn) clearBtn.hidden = !q;
    if (q.length < 2) { closePanel(); return; }

    api('GET', '/api/docs?q=' + encodeURIComponent(q)).then(function (data) {
      var docs = (data && data.docs) ? data.docs : [];
      if (!docs.length) {
        panel.innerHTML = '<div style="padding:1rem; text-align:center; color:var(--text-muted); font-size:0.82rem;">Nenhum documento encontrado para "' + esc(q) + '"</div>';
      } else {
        var html = '<div style="padding:0.4rem 0.75rem; font-size:0.72rem; font-weight:700; text-transform:uppercase; color:var(--text-muted); border-bottom:1px solid var(--border-color); background:var(--bg-muted, rgba(0,0,0,0.02));">Documentos (' + docs.length + ')</div>';
        docs.forEach(function (doc) {
          var viewUrl = '/docs/view/' + doc.id;
          var catLabel = doc.category_name ? esc(doc.category_name) : 'Geral';
          var author = doc.author_name ? esc(doc.author_name) : '';
          html += '<a href="' + viewUrl + '" class="tb-dropdown-item" style="display:flex; flex-direction:column; align-items:flex-start; padding:0.6rem 0.85rem; border-bottom:1px solid var(--border-color); text-decoration:none;">'
            + '<div style="font-weight:600; font-size:0.85rem; color:var(--text-color); margin-bottom:2px;">' + esc(doc.title) + '</div>'
            + '<div style="font-size:0.75rem; color:var(--text-muted); display:flex; gap:0.5rem; align-items:center;">'
            + '<span style="background:rgba(26,187,156,0.1); color:var(--primary); padding:1px 6px; border-radius:4px; font-weight:500;">' + catLabel + '</span>'
            + (author ? '<span>Por ' + author + '</span>' : '')
            + '</div></a>';
        });
        panel.innerHTML = html;
      }
      panel.hidden = false;
    }).catch(function () { closePanel(); });
  }

  input.addEventListener('input', function () {
    clearTimeout(timer);
    timer = setTimeout(run, 250);
  });

  input.addEventListener('focus', function () {
    if (input.value.trim().length >= 2) run();
  });

  input.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { closePanel(); input.blur(); }
  });

  document.addEventListener('click', function (e) {
    if (!e.target.closest('.topbar-global-search') && !e.target.closest('.topbar-search')) closePanel();
  });

  // Atalho global '/' para focar a busca quando fora de um campo de texto
  document.addEventListener('keydown', function (e) {
    if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
      e.preventDefault();
      input.focus();
      input.select();
    }
  });
}

function clearGlobalDocSearch() {
  var input = document.getElementById('globalDocSearchInput');
  var panel = document.getElementById('globalDocSearchDropdown');
  var clearBtn = document.getElementById('globalDocSearchClear');
  if (input) input.value = '';
  if (clearBtn) clearBtn.hidden = true;
  if (panel) { panel.hidden = true; panel.innerHTML = ''; }
}

function highlightFromUrl(rowSelector, idAttr) {
  var params = new URLSearchParams(window.location.search);
  var id = params.get('d');
  var q = params.get('q');
  if (!id) return;

  var found = false;
  if (rowSelector) {
    var el = document.querySelector(rowSelector + '[data-id="' + CSS.escape(id) + '"]');
    if (el) {
      found = true;
      el.classList.add('row-highlight');
      setTimeout(function () { el.classList.add('row-highlight-fade'); }, 120);
      el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  var host = document.getElementById('searchNoticeHost');
  if (host) {
    host.innerHTML = '<div class="search-notice">'
      + '<svg class="search-notice-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>'
      + '<div class="search-notice-body">Resultado da busca'
      + (q ? ' para <strong>"' + esc(q) + '"</strong>' : '')
      + ' — <strong>' + esc(id) + '</strong>' + (found ? '' : ' não encontrado nesta página.') + '</div>'
      + '<button class="search-notice-close" onclick="clearSearchNotice()" title="Fechar" aria-label="Fechar">✕</button>'
      + '</div>';
  }
}

function clearSearchNotice() {
  document.querySelectorAll('.row-highlight').forEach(function (el) { el.classList.remove('row-highlight', 'row-highlight-fade'); });
  var host = document.getElementById('searchNoticeHost');
  if (host) host.innerHTML = '';
  try {
    var url = window.location.pathname;
    window.history.replaceState({}, '', url);
  } catch (e) { /* sem history */ }
}

/* ------------------------------------------------------------------ Sidebar / tema / sessão */

function toggleSidebar() {
  if (window.matchMedia('(max-width: 768px)').matches) {
    var sidebar = document.getElementById('sidebar');
    var open = sidebar.classList.toggle('open');
    document.getElementById('sidebarBackdrop').hidden = !open;
    document.body.classList.toggle('sidebar-open', open);
  } else {
    var rail = document.body.classList.toggle('sidebar-rail');
    try { localStorage.setItem('pq_sidebar_rail', rail ? '1' : '0'); } catch (e) { /* sem localStorage */ }
  }
}

function toggleTheme() {
  var root = document.documentElement;
  var dark = root.dataset.theme === 'dark';
  root.dataset.theme = dark ? 'light' : 'dark';
  try { localStorage.setItem('pq_theme', root.dataset.theme); } catch (e) { /* sem localStorage */ }
}

function logout() {
  api('POST', '/api/auth/logout', {}).catch(function () {}).then(function () { window.location.href = '/login'; });
}

/* ------------------------------------------------------------------ Modais (Global) */

var _activeModalTrigger = null;
var _confirmCallback = null;

function openModal(id) {
  var el = document.getElementById(id);
  if (!el) return;
  _activeModalTrigger = document.activeElement;
  el.hidden = false;
  el.classList.add('show');
  document.body.classList.add('modal-open');
}

function closeModal(id) {
  var el = document.getElementById(id);
  if (!el) return;
  el.classList.remove('show');
  el.hidden = true;
  if (!document.querySelector('.modal-backdrop.show')) {
    document.body.classList.remove('modal-open');
  }
  if (_activeModalTrigger && typeof _activeModalTrigger.focus === 'function') {
    _activeModalTrigger.focus();
    _activeModalTrigger = null;
  }
}

/**
 * Exibe o modal de confirmação nativo do sistema com título, mensagem e callback customizáveis.
 * @param {Object|string} options Objeto { title, message, confirmText, confirmClass } ou texto simples
 * @param {Function} [onConfirm] Callback executado se o usuário confirmar
 */
function showConfirm(options, onConfirm) {
  var title = 'Excluir?';
  var message = 'Esta ação não pode ser desfeita.';
  var btnText = 'Sim, excluir';
  var btnClass = 'btn btn-danger';

  if (typeof options === 'string') {
    message = options;
  } else if (typeof options === 'object' && options !== null) {
    if (options.title) title = options.title;
    if (options.message) message = options.message;
    if (options.confirmText) btnText = options.confirmText;
    if (options.confirmClass) btnClass = options.confirmClass;
  }

  var titleEl = document.getElementById('modalConfirmTitulo');
  var textEl = document.getElementById('modalConfirmTexto');
  var btnEl = document.getElementById('btnConfirmDelete');

  if (titleEl) titleEl.textContent = title;
  if (textEl) textEl.textContent = message;
  if (btnEl) {
    btnEl.textContent = btnText;
    btnEl.className = btnClass;
    btnEl.disabled = false;
  }

  _confirmCallback = (typeof options === 'function') ? options : onConfirm;
  openModal('confirmBG');
}

function closeConfirm() {
  closeModal('confirmBG');
  _confirmCallback = null;
}

function handleGlobalConfirm() {
  if (typeof _confirmCallback === 'function') {
    var cb = _confirmCallback;
    _confirmCallback = null;
    closeModal('confirmBG');
    cb();
  } else if (typeof confirmDelete === 'function') {
    confirmDelete();
  } else {
    closeModal('confirmBG');
  }
}

/* ------------------------------------------------------------------ Init comum */

document.addEventListener('DOMContentLoaded', function () {
  try {
    var savedTheme = localStorage.getItem('pq_theme');
    if (savedTheme === 'dark') document.documentElement.dataset.theme = 'dark';
    if (localStorage.getItem('pq_sidebar_rail') === '1' && !window.matchMedia('(max-width: 768px)').matches) {
      document.body.classList.add('sidebar-rail');
    }
  } catch (e) { /* sem localStorage */ }

  if (ME) {
    var uNameEl = document.getElementById('uName');
    if (uNameEl) uNameEl.textContent = ME.name || '';
    var uRoleEl = document.getElementById('uRole');
    if (uRoleEl) uRoleEl.textContent = ME.role || '';
    var uAvatarEl = document.getElementById('uAvatar');
    if (uAvatarEl) uAvatarEl.textContent = (ME.name || 'U').charAt(0).toUpperCase();
    var uAvatarTopEl = document.getElementById('uAvatarTop');
    if (uAvatarTopEl) uAvatarTopEl.textContent = (ME.name || 'U').charAt(0).toUpperCase();
  }

  var dropdownName = document.getElementById('dropdownUserName');
  if (dropdownName && ME) dropdownName.textContent = ME.name || '';

  var avatarTop = document.getElementById('uAvatarTop');
  var userDropdown = document.getElementById('userDropdown');
  if (avatarTop && userDropdown) {
    avatarTop.addEventListener('click', function (e) {
      e.stopPropagation();
      userDropdown.hidden = !userDropdown.hidden;
    });
    document.addEventListener('click', function () {
      userDropdown.hidden = true;
    });
  }

  initGlobalSearch();
  initNotifications();
  fetchPendingApprovalsCount();
  setInterval(fetchPendingApprovalsCount, 15000);

  /* Acessibilidade de modais: Fechar com Escape e clique no backdrop */
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' || e.key === 'Esc') {
      var openModals = document.querySelectorAll('.modal-backdrop.show');
      openModals.forEach(function (modal) {
        closeModal(modal.id);
      });
    }
  });

  document.addEventListener('click', function (e) {
    if (e.target && e.target.classList && e.target.classList.contains('modal-backdrop') && e.target.classList.contains('show')) {
      closeModal(e.target.id);
    }
  });
});

/* ------------------------------------------------------------------ Notificações */

function fetchNotifications() {
  api('GET', '/api/notifications').then(function (d) {
    if (!d) return;
    var badge = document.getElementById('notifBadge');
    var list = document.getElementById('notifList');

    if (badge) {
      var prev = parseInt(badge.textContent, 10) || 0;
      if (d.unread_count > 0) {
        badge.textContent = d.unread_count > 99 ? '99+' : d.unread_count;
        badge.hidden = false;
        if (d.unread_count !== prev) {
          badge.classList.remove('notif-pulse');
          void badge.offsetWidth;
          badge.classList.add('notif-pulse');
        }
      } else {
        badge.hidden = true;
      }
    }

    if (list) {
      var unreadItems = (d.items || []).filter(function (it) { return !it.read_at; });
      if (unreadItems.length === 0) {
        list.innerHTML = '<div style="padding:1.5rem;text-align:center;color:var(--text-muted);font-size:0.85rem">Nenhuma notificação pendente</div>';
      } else {
        var html = '';
        unreadItems.forEach(function (it) {
          var href = it.link ? esc(it.link) : '#';
          html += '<a href="' + href + '" style="display:flex;align-items:flex-start;gap:6px;padding:0.75rem 1rem;border-bottom:1px solid var(--border-color);text-decoration:none;color:inherit;background:var(--primary-lt);">'
            + '<div style="flex:1;min-width:0">'
            + '<div style="font-weight:600;font-size:0.85rem;margin-bottom:2px;color:var(--docs-text)">' + esc(it.title) + '</div>'
            + '<div style="font-size:0.8rem;color:var(--docs-text-mut)">' + esc(it.message) + '</div>'
            + '<div style="font-size:0.7rem;color:var(--docs-text-mut);margin-top:4px">' + fmt(it.created_at) + '</div>'
            + '</div>'
            + '<button data-notif-id="' + it.id + '" onclick="event.preventDefault();event.stopPropagation();markNotificationRead(' + it.id + ')" title="Marcar como lida" style="background:none;border:none;cursor:pointer;padding:2px 4px;color:var(--text-muted);font-size:11px;flex-shrink:0;opacity:0.6" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.6">'
            + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><polyline points="20 6 9 17 4 12"/></svg></button>'
            + '</a>';
        });
        list.innerHTML = html;
      }
    }
  }).catch(function () {});
}

function markAllNotificationsRead() {
  api('POST', '/api/notifications/mark-all-read', {}).then(function () {
    var list = document.getElementById('notifList');
    if (list) {
      list.innerHTML = '<div style="padding:1.5rem;text-align:center;color:var(--text-muted);font-size:0.85rem">Nenhuma notificação pendente</div>';
    }
    var badge = document.getElementById('notifBadge');
    if (badge) badge.hidden = true;
  });
}

function markNotificationRead(id) {
  api('POST', '/api/notifications/mark-read/' + id, {}).then(function () {
    var btn = document.querySelector('[data-notif-id="' + id + '"]');
    if (btn) {
      var item = btn.closest('a');
      if (item) {
        item.style.transition = 'opacity 0.2s, max-height 0.3s';
        item.style.opacity = '0';
        item.style.maxHeight = '0';
        item.style.overflow = 'hidden';
        item.style.padding = '0 1rem';
        item.style.margin = '0';
        setTimeout(function () { item.remove(); }, 300);
      }
    }
    var badge = document.getElementById('notifBadge');
    if (badge) {
      var count = parseInt(badge.textContent, 10) || 1;
      count = Math.max(0, count - 1);
      if (count > 0) {
        badge.textContent = count > 99 ? '99+' : count;
      } else {
        badge.hidden = true;
      }
    }
  });
}

function initNotifications() {
  var btn = document.getElementById('notifBtn');
  var dd = document.getElementById('notifDropdown');
  if (!btn || !dd) return;

  btn.addEventListener('click', function (e) {
    e.stopPropagation();
    dd.hidden = !dd.hidden;
    if (!dd.hidden) {
      fetchNotifications();
    }
  });

  document.addEventListener('click', function () {
    if (dd) dd.hidden = true;
  });

  fetchNotifications();
  setInterval(fetchNotifications, 8000);
}

function fetchPendingApprovalsCount() {
  var badge = document.getElementById('sidebarPendingBadge');
  if (!badge) return;
  api('GET', '/api/approvals?status=pendente&q=').then(function (res) {
    var docs = res.docs || [];
    if (docs.length > 0) {
      badge.textContent = docs.length;
      badge.hidden = false;
    } else {
      badge.hidden = true;
    }
  }).catch(function () {});
}

/* ─── COMPARAÇÃO DE VERSÕES (GIT DIFF GLOBAL) ──────────────── */

var DocDiff = (function () {
  var CURRENT_DOC = null;
  var CURRENT_TAB = 'diff';

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

    return { html: containerHtml, statsHtml: statsHtml };
  }

  function switchTab(tab) {
    CURRENT_TAB = tab;

    var btnDiff = document.getElementById('btnTabDiff');
    var btnRendered = document.getElementById('btnTabRendered');
    var btnHistory = document.getElementById('btnTabHistory');

    var containerDiff = document.getElementById('previewDiffContainer');
    var containerBody = document.getElementById('previewBody');
    var containerHistory = document.getElementById('previewVersionsContainer');
    var diffControls = document.getElementById('diffVersionControls');

    if (btnDiff) btnDiff.className = tab === 'diff' ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-secondary';
    if (btnRendered) btnRendered.className = tab === 'rendered' ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-secondary';
    if (btnHistory) btnHistory.className = tab === 'history' ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-secondary';

    if (containerDiff) containerDiff.hidden = (tab !== 'diff');
    if (containerBody) containerBody.hidden = (tab !== 'rendered');
    if (containerHistory) containerHistory.hidden = (tab !== 'history');
    if (diffControls) diffControls.hidden = (tab !== 'diff');
  }

  function populateVersionSelects(versions) {
    var oldSelect = document.getElementById('diffOldVersionSelect');
    var newSelect = document.getElementById('diffNewVersionSelect');

    if (!oldSelect || !newSelect) return;

    oldSelect.innerHTML = '';
    newSelect.innerHTML = '';

    if (!versions || !versions.length) {
      oldSelect.innerHTML = '<option value="">Sem versão anterior</option>';
      newSelect.innerHTML = '<option value="0">Conteúdo Atual</option>';
      return;
    }

    var total = versions.length;

    for (var i = 0; i < total; i++) {
      var v = versions[i];
      var vNum = total - i;
      var dateStr = fmtFull(v.edited_at);
      var label = 'v' + vNum + ' (' + dateStr + ' - ' + (v.editor_name || 'Autor') + ')';

      var optOld = document.createElement('option');
      optOld.value = String(i);
      optOld.textContent = label;

      var optNew = document.createElement('option');
      optNew.value = String(i);
      optNew.textContent = label;

      oldSelect.appendChild(optOld);
      newSelect.appendChild(optNew);
    }

    newSelect.value = '0';
    if (total >= 2) {
      oldSelect.value = '1';
    } else {
      oldSelect.value = '0';
    }
  }

  function onVersionSelectChange() {
    if (!CURRENT_DOC) return;
    renderSelectedDiff();
  }

  function renderSelectedDiff() {
    var oldSelect = document.getElementById('diffOldVersionSelect');
    var newSelect = document.getElementById('diffNewVersionSelect');
    var diffContainer = document.getElementById('previewDiffContainer');
    var statsBadge = document.getElementById('diffStatsBadge');

    if (!CURRENT_DOC || !diffContainer) return;

    var versions = CURRENT_DOC.versions || [];

    var oldIdx = oldSelect ? parseInt(oldSelect.value, 10) : 1;
    var newIdx = newSelect ? parseInt(newSelect.value, 10) : 0;

    var oldContent = '';
    var oldLabel = 'Versão Anterior';
    var newContent = CURRENT_DOC.content || '';
    var newLabel = 'Nova Alteração (Proposta)';

    if (versions.length > 0) {
      if (!isNaN(newIdx) && versions[newIdx]) {
        newContent = versions[newIdx].content || '';
        var vNumNew = versions.length - newIdx;
        newLabel = 'Versão v' + vNumNew + ' (' + fmtFull(versions[newIdx].edited_at) + ')';
      }

      if (!isNaN(oldIdx) && versions[oldIdx] && oldIdx !== newIdx) {
        oldContent = versions[oldIdx].content || '';
        var vNumOld = versions.length - oldIdx;
        oldLabel = 'Versão v' + vNumOld + ' (' + fmtFull(versions[oldIdx].edited_at) + ')';
      } else if (oldIdx === newIdx) {
        oldContent = newContent;
        oldLabel = newLabel + ' (Sem alteração)';
      }
    }

    if (!oldContent && (!versions || versions.length <= 1)) {
      oldLabel = 'Sem versão anterior (Documento Novo)';
    }

    var ops = computeLineDiff(oldContent, newContent);
    var res = renderSideBySideDiff(ops, oldLabel, newLabel);

    diffContainer.innerHTML = res.html;
    if (statsBadge) statsBadge.innerHTML = res.statsHtml;
  }

  function renderVersionsHistoryList(versions) {
    var container = document.getElementById('previewVersionsContainer');
    if (!container) return;

    if (!versions || !versions.length) {
      container.innerHTML = '<p style="color:var(--docs-text-mut);padding:1rem 0;">Nenhum histórico de edições anteriores registrado.</p>';
      return;
    }

    var html = '<div style="display:flex;flex-direction:column;gap:0.75rem;">';
    var total = versions.length;

    for (var i = 0; i < total; i++) {
      var v = versions[i];
      var vNum = total - i;
      var isLatest = (i === 0);

      html += '<div style="background:var(--docs-bg,#f8fafc);border:1px solid var(--docs-border,#e2e8f0);border-radius:8px;padding:0.85rem 1.1rem;display:flex;align-items:center;justify-content:space-between;">'
        + '<div>'
        + '<div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.25rem;">'
        + '<strong style="font-size:0.95rem;">Versão ' + vNum + '</strong>'
        + (isLatest ? '<span class="badge" style="background:rgba(26,187,156,0.15);color:var(--docs-primary,#1abb9c);font-size:0.75rem;padding:2px 8px;border-radius:10px;">Versão Atual</span>' : '')
        + '</div>'
        + '<div style="font-size:0.8rem;color:var(--docs-text-mut);">'
        + 'Editado por <strong>' + esc(v.editor_name || 'Autor') + '</strong> em ' + fmtFull(v.edited_at)
        + '</div>'
        + (v.version_note ? '<div style="font-size:0.8rem;color:var(--docs-text);margin-top:0.35rem;"><em>" ' + esc(v.version_note) + ' "</em></div>' : '')
        + '</div>'
        + '<button type="button" class="btn btn-sm btn-secondary" onclick="DocDiff.compareVersionIndex(' + i + ')" style="font-size:0.78rem;border-radius:6px;">Comparar com Anterior</button>'
        + '</div>';
    }

    html += '</div>';
    container.innerHTML = html;
  }

  function compareVersionIndex(idx) {
    switchTab('diff');
    var oldSelect = document.getElementById('diffOldVersionSelect');
    var newSelect = document.getElementById('diffNewVersionSelect');
    var versions = (CURRENT_DOC && CURRENT_DOC.versions) || [];
    if (newSelect) newSelect.value = String(idx);
    if (oldSelect) oldSelect.value = String(Math.min(versions.length - 1, idx + 1));
    onVersionSelectChange();
  }

  function open(id, options) {
    options = options || {};
    api('GET', '/api/docs/' + id).then(function (res) {
      CURRENT_DOC = res.doc;
      var modal   = document.getElementById('previewApprovalModal');
      var title   = document.getElementById('previewTitle');
      var meta    = document.getElementById('previewMeta');
      var body    = document.getElementById('previewBody');
      var notice  = document.getElementById('previewStatusNotice');
      var actions = document.getElementById('previewModalActions');

      if (title) title.textContent = CURRENT_DOC.title || '';
      if (meta) {
        var cat    = CURRENT_DOC.category_name ? ('📁 ' + CURRENT_DOC.category_name) : '📂 Geral';
        var author = '👤 ' + (CURRENT_DOC.author_name || 'Autor');
        var date   = '🕒 ' + fmtFull(CURRENT_DOC.updated_at);
        meta.textContent = author + ' • ' + cat + ' • ' + date;
      }

      if (notice) {
        if (CURRENT_DOC.status === 'rejeitado' && CURRENT_DOC.rejection_reason) {
          notice.hidden = false;
          notice.className = 'approval-rejection-callout';
          notice.innerHTML = '<strong>Motivo da rejeição anterior:</strong> ' + esc(CURRENT_DOC.rejection_reason);
        } else {
          notice.hidden = true;
        }
      }

      if (body) {
        var raw = CURRENT_DOC.content || '';
        body.innerHTML = parseMarkdown(raw);
      }

      var versions = CURRENT_DOC.versions || [];
      populateVersionSelects(versions);
      renderSelectedDiff();
      renderVersionsHistoryList(versions);

      switchTab('diff');

      if (actions) {
        var userRole = (window.ME && window.ME.role) || '';
        var canApprove = (userRole === 'Admin' || userRole === 'Aprovador');
        var isPendingOrRejected = (CURRENT_DOC.status === 'pendente' || CURRENT_DOC.status === 'rejeitado');
        actions.hidden = !(options.showApprovalButtons && canApprove && isPendingOrRejected);
      }
      if (modal) modal.hidden = false;
    }).catch(function (err) {
      if (typeof toast === 'function') {
        toast(err.message || 'Erro ao carregar documento.', 'error');
      } else {
        alert(err.message || 'Erro ao carregar documento.');
      }
    });
  }

  function close() {
    var modal = document.getElementById('previewApprovalModal');
    if (modal) modal.hidden = true;
    CURRENT_DOC = null;
  }

  function getCurrentDoc() {
    return CURRENT_DOC;
  }

  return {
    open: open,
    close: close,
    switchTab: switchTab,
    onVersionSelectChange: onVersionSelectChange,
    compareVersionIndex: compareVersionIndex,
    getCurrentDoc: getCurrentDoc
  };
})();

/* ------------------------------------------------------------------ SearchableSelect */

var SearchableSelect = (function () {
  var _instances = new WeakMap();

  function init(selectEl) {
    if (!selectEl || selectEl.dataset.searchableInit) return;
    if (selectEl.classList.contains('no-searchable') || selectEl.getAttribute('data-no-searchable') === 'true') return;

    selectEl.dataset.searchableInit = 'true';
    selectEl.classList.add('searchable-select-native');

    var wrap = document.createElement('div');
    wrap.className = 'searchable-select-wrap';
    if (selectEl.classList.contains('doceditor-select')) wrap.classList.add('is-doceditor');
    if (selectEl.classList.contains('reports-filter-select')) wrap.classList.add('is-reports-filter');
    if (selectEl.disabled) wrap.classList.add('is-disabled');

    var trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'searchable-select-trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');
    if (selectEl.disabled) trigger.disabled = true;

    var labelSpan = document.createElement('span');
    labelSpan.className = 'searchable-select-label';

    var arrowSvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    arrowSvg.setAttribute('class', 'searchable-select-arrow');
    arrowSvg.setAttribute('viewBox', '0 0 24 24');
    arrowSvg.setAttribute('width', '14');
    arrowSvg.setAttribute('height', '14');
    arrowSvg.setAttribute('fill', 'none');
    arrowSvg.setAttribute('stroke', 'currentColor');
    arrowSvg.setAttribute('stroke-width', '2');
    arrowSvg.innerHTML = '<polyline points="6 9 12 15 18 9"></polyline>';

    trigger.appendChild(labelSpan);
    trigger.appendChild(arrowSvg);

    var dropdown = document.createElement('div');
    dropdown.className = 'searchable-select-dropdown';
    dropdown.hidden = true;

    var searchWrap = document.createElement('div');
    searchWrap.className = 'searchable-select-search-wrap';

    var searchIcon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    searchIcon.setAttribute('class', 'searchable-select-search-icon');
    searchIcon.setAttribute('viewBox', '0 0 24 24');
    searchIcon.setAttribute('width', '13');
    searchIcon.setAttribute('height', '13');
    searchIcon.setAttribute('fill', 'none');
    searchIcon.setAttribute('stroke', 'currentColor');
    searchIcon.setAttribute('stroke-width', '2');
    searchIcon.innerHTML = '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>';

    var searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.className = 'searchable-select-search-input';
    searchInput.placeholder = 'Buscar...';
    searchInput.autocomplete = 'off';

    searchWrap.appendChild(searchIcon);
    searchWrap.appendChild(searchInput);

    var optionsList = document.createElement('div');
    optionsList.className = 'searchable-select-options';
    optionsList.setAttribute('role', 'listbox');

    dropdown.appendChild(searchWrap);
    dropdown.appendChild(optionsList);

    if (selectEl.parentNode) {
      selectEl.parentNode.insertBefore(wrap, selectEl);
      wrap.appendChild(selectEl);
      wrap.appendChild(trigger);
      wrap.appendChild(dropdown);
    }

    var instance = {
      selectEl: selectEl,
      wrap: wrap,
      trigger: trigger,
      labelSpan: labelSpan,
      dropdown: dropdown,
      searchInput: searchInput,
      optionsList: optionsList,
      highlightedIndex: -1,

      updateLabel: function () {
        var opt = selectEl.options[selectEl.selectedIndex];
        labelSpan.textContent = opt ? opt.text : (selectEl.getAttribute('placeholder') || 'Selecionar...');
        if (selectEl.disabled) {
          trigger.disabled = true;
          wrap.classList.add('is-disabled');
        } else {
          trigger.disabled = false;
          wrap.classList.remove('is-disabled');
        }
      },

      renderOptions: function (filterText) {
        var query = (filterText || '').trim().toLowerCase();
        optionsList.innerHTML = '';
        instance.highlightedIndex = -1;

        var count = 0;
        var selectedVal = selectEl.value;

        for (var i = 0; i < selectEl.options.length; i++) {
          var opt = selectEl.options[i];
          var text = opt.text;
          var val = opt.value;

          if (query && text.toLowerCase().indexOf(query) === -1) {
            continue;
          }

          count++;
          var optBtn = document.createElement('div');
          optBtn.className = 'searchable-select-option';
          optBtn.setAttribute('role', 'option');
          optBtn.setAttribute('data-value', val);
          optBtn.setAttribute('data-index', i);
          optBtn.textContent = text;
          if (val === selectedVal || (!val && !selectedVal && selectEl.selectedIndex === i)) {
            optBtn.classList.add('is-selected');
          }
          if (opt.disabled) {
            optBtn.classList.add('is-disabled');
          }

          (function (targetVal, optElement) {
            optBtn.addEventListener('click', function (e) {
              e.stopPropagation();
              if (optElement.disabled) return;
              selectEl.value = targetVal;
              instance.updateLabel();
              instance.close();
              selectEl.dispatchEvent(new Event('change', { bubbles: true }));
              if (typeof selectEl.onchange === 'function') {
                selectEl.onchange();
              }
            });
          })(val, opt);

          optionsList.appendChild(optBtn);
        }

        if (count === 0) {
          var empty = document.createElement('div');
          empty.className = 'searchable-select-no-results';
          empty.textContent = 'Nenhum resultado encontrado';
          optionsList.appendChild(empty);
        }
      },

      open: function () {
        if (selectEl.disabled) return;
        closeAll(wrap);

        var modalBody = wrap.closest('.modal-body');
        if (modalBody) modalBody.classList.add('has-open-select');

        var rect = wrap.getBoundingClientRect();
        var spaceBelow = window.innerHeight - rect.bottom;
        if (spaceBelow < 280 && rect.top > 280) {
          dropdown.style.top = 'auto';
          dropdown.style.bottom = 'calc(100% + 4px)';
        } else {
          dropdown.style.top = 'calc(100% + 4px)';
          dropdown.style.bottom = 'auto';
        }

        dropdown.hidden = false;
        wrap.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
        searchInput.value = '';
        instance.renderOptions('');

        setTimeout(function () {
          searchInput.focus();
          var sel = optionsList.querySelector('.searchable-select-option.is-selected');
          if (sel) sel.scrollIntoView({ block: 'nearest' });
        }, 30);
      },

      close: function () {
        dropdown.hidden = true;
        wrap.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
        instance.highlightedIndex = -1;
        var modalBody = wrap.closest('.modal-body');
        if (modalBody) modalBody.classList.remove('has-open-select');
      }
    };

    trigger.addEventListener('click', function (e) {
      e.stopPropagation();
      if (wrap.classList.contains('is-open')) {
        instance.close();
      } else {
        instance.open();
      }
    });

    searchInput.addEventListener('input', function () {
      instance.renderOptions(searchInput.value);
    });

    searchInput.addEventListener('keydown', function (e) {
      var visibleOptions = optionsList.querySelectorAll('.searchable-select-option:not(.is-disabled)');
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (visibleOptions.length) {
          instance.highlightedIndex = (instance.highlightedIndex + 1) % visibleOptions.length;
          visibleOptions.forEach(function (el, idx) {
            el.classList.toggle('is-highlighted', idx === instance.highlightedIndex);
          });
          visibleOptions[instance.highlightedIndex].scrollIntoView({ block: 'nearest' });
        }
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (visibleOptions.length) {
          instance.highlightedIndex = (instance.highlightedIndex - 1 + visibleOptions.length) % visibleOptions.length;
          visibleOptions.forEach(function (el, idx) {
            el.classList.toggle('is-highlighted', idx === instance.highlightedIndex);
          });
          visibleOptions[instance.highlightedIndex].scrollIntoView({ block: 'nearest' });
        }
      } else if (e.key === 'Enter') {
        e.preventDefault();
        if (instance.highlightedIndex >= 0 && visibleOptions[instance.highlightedIndex]) {
          visibleOptions[instance.highlightedIndex].click();
        } else if (visibleOptions.length === 1) {
          visibleOptions[0].click();
        }
      } else if (e.key === 'Escape') {
        instance.close();
        trigger.focus();
      }
    });

    selectEl.addEventListener('change', function () {
      instance.updateLabel();
    });

    if (window.MutationObserver) {
      var observer = new MutationObserver(function () {
        instance.updateLabel();
        if (!dropdown.hidden) {
          instance.renderOptions(searchInput.value);
        }
      });
      observer.observe(selectEl, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled'] });
    }

    var valDesc = Object.getOwnPropertyDescriptor(selectEl, 'value') || Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
    if (valDesc && valDesc.set) {
      try {
        Object.defineProperty(selectEl, 'value', {
          get: function () {
            return valDesc.get.call(this);
          },
          set: function (newVal) {
            valDesc.set.call(this, newVal);
            instance.updateLabel();
          },
          configurable: true
        });
      } catch (err) {}
    }

    instance.updateLabel();
    _instances.set(selectEl, instance);
  }

  function sync(selectEl) {
    var inst = _instances.get(selectEl);
    if (inst) {
      inst.updateLabel();
      inst.renderOptions('');
    }
  }

  function closeAll(exceptWrap) {
    document.querySelectorAll('.searchable-select-wrap.is-open').forEach(function (w) {
      if (w !== exceptWrap) {
        w.classList.remove('is-open');
        var dd = w.querySelector('.searchable-select-dropdown');
        if (dd) dd.hidden = true;
        var tr = w.querySelector('.searchable-select-trigger');
        if (tr) tr.setAttribute('aria-expanded', 'false');
      }
    });
    document.querySelectorAll('.modal-body.has-open-select').forEach(function (mb) {
      if (!mb.querySelector('.searchable-select-wrap.is-open')) {
        mb.classList.remove('has-open-select');
      }
    });
  }

  function initAll(root) {
    var scope = root || document;
    var selects = scope.querySelectorAll('select.form-control, select.searchable-select, select.doceditor-select, select.reports-filter-select');
    selects.forEach(function (sel) {
      init(sel);
    });
  }

  return {
    init: init,
    sync: sync,
    closeAll: closeAll,
    initAll: initAll
  };
})();

window.SearchableSelect = SearchableSelect;

document.addEventListener('click', function (e) {
  if (!e.target.closest('.searchable-select-wrap')) {
    SearchableSelect.closeAll();
  }
});

document.addEventListener('DOMContentLoaded', function () {
  SearchableSelect.initAll();
});

if (window.MutationObserver) {
  var bodyObserver = new MutationObserver(function (mutations) {
    for (var m = 0; m < mutations.length; m++) {
      var added = mutations[m].addedNodes;
      for (var n = 0; n < added.length; n++) {
        var node = added[n];
        if (node.nodeType === 1) {
          if (node.tagName === 'SELECT') {
            SearchableSelect.init(node);
          } else if (node.querySelectorAll) {
            SearchableSelect.initAll(node);
          }
        }
      }
    }
  });
  if (document.body) {
    bodyObserver.observe(document.body, { childList: true, subtree: true });
  } else {
    document.addEventListener('DOMContentLoaded', function () {
      bodyObserver.observe(document.body, { childList: true, subtree: true });
    });
  }
}