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
      Icons.get('key', {size: 13, class: 'psk-icon'}) +
      '<span class="psk-masked">••••••••</span>' +
      '<span class="psk-revealed">' + safeText + '</span>' +
      '<button type="button" class="psk-copy-btn" onclick="copySecretPsk(this, event)" title="Copiar senha">' +
        Icons.get('copy', {size: 13}) +
      '</button>' +
    '</span>';
  });

  var html = '';
  if (typeof marked !== 'undefined' && typeof marked.parse === 'function') {
    html = marked.parse(processed);
  } else {
    html = '<div style="white-space:pre-wrap;">' + processed + '</div>';
  }

  // Sanitizar HTML contra XSS usando DOMPurify (se disponível)
  if (typeof DOMPurify !== 'undefined') {
    html = DOMPurify.sanitize(html, {
      ALLOWED_TAGS: [
        'h1','h2','h3','h4','h5','h6','p','br','hr','pre','code','blockquote',
        'ul','ol','li','a','strong','em','b','i','u','s','del','ins',
        'table','thead','tbody','tr','th','td','caption','colgroup','col',
        'img','figure','figcaption','span','div','details','summary',
        'sub','sup','mark','small','abbr','kbd','var','samp',
      ],
      ALLOWED_ATTR: [
        'href','src','alt','title','class','id','style','width','height',
        'target','rel','colspan','rowspan','scope','align','valign',
        'data-psk','onclick','type',
      ],
      ALLOW_DATA_ATTR: false,
    });
  }

  return html;
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
    var copyBtn = btn;
    if (copyBtn) {
      var prevHtml = copyBtn.innerHTML;
      copyBtn.innerHTML = Icons.get('check', {size: 13});
      setTimeout(function () { copyBtn.innerHTML = prevHtml; }, 1600);
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

  var totalCount = Number(total || 0);
  var totalPages = Math.max(1, parseInt(pages, 10) || 1);
  var curPage = Math.max(1, Math.min(parseInt(current, 10) || 1, totalPages));

  var from = totalCount === 0 ? 0 : (curPage - 1) * PAGE_SIZE + 1;
  var to = totalCount === 0 ? 0 : Math.min(curPage * PAGE_SIZE, totalCount);

  var html = '<div class="pagination-info">Mostrando <strong>' + from + '</strong> a <strong>' + to + '</strong> de <strong>' + totalCount + '</strong></div>';
  html += '<div class="pagination-controls">';

  // Botão Anterior
  var prevDisabled = curPage <= 1 ? ' disabled' : '';
  html += '<button type="button" class="pg-btn' + prevDisabled + '" data-pg="' + (curPage - 1) + '"' + prevDisabled + ' title="Anterior">‹</button>';

  if (totalPages <= 1) {
    html += '<button type="button" class="pg-btn active" data-pg="1">1</button>';
  } else {
    var seen = {};
    for (var p = 1; p <= totalPages; p++) {
      if (p === 1 || p === totalPages || Math.abs(p - curPage) <= 1) {
        seen[p] = true;
        html += '<button type="button" class="pg-btn' + (p === curPage ? ' active' : '') + '" data-pg="' + p + '">' + p + '</button>';
      } else if (!seen[p]) {
        var prevShown = p - 1;
        if (!seen[prevShown] || (p < totalPages && !seen[p + 1])) {
          seen[p] = true;
          html += '<span class="pg-ellipsis">…</span>';
        }
      }
    }
  }

  // Botão Próximo
  var nextDisabled = curPage >= totalPages ? ' disabled' : '';
  html += '<button type="button" class="pg-btn' + nextDisabled + '" data-pg="' + (curPage + 1) + '"' + nextDisabled + ' title="Próxima">›</button>';
  html += '</div>';
  el.innerHTML = html;

  el.querySelectorAll('[data-pg]:not([disabled])').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var targetPage = parseInt(btn.getAttribute('data-pg'), 10);
      if (targetPage >= 1 && targetPage <= totalPages && targetPage !== curPage && typeof onPage === 'function') {
        onPage(targetPage);
      }
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
  fetchOpenIssuesCount();
  setInterval(fetchNotifications, 8000);
  setInterval(fetchOpenIssuesCount, 15000);
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

function fetchOpenIssuesCount() {
  var badge = document.getElementById('sidebarIssuesBadge');
  if (!badge) return;
  api('GET', '/api/issues?status=aberto&q=').then(function (res) {
    var stats = res.stats || {};
    var count = typeof stats.abertas !== 'undefined' ? stats.abertas : (res.issues ? res.issues.length : 0);
    if (count > 0) {
      badge.textContent = count;
      badge.hidden = false;
    } else {
      badge.hidden = true;
    }
  }).catch(function () {});
}
