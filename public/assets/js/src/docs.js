/**
 * ==========================================================================
 * DOCMANAGER PRO - MÓDULO JAVASCRIPT DE GESTÃO DE DOCUMENTOS
 * ==========================================================================
 */
'use strict';

var Docs = (function () {
  // Estado
  var ALL_DOCS = [];
  var ALL_CATS = [];
  var ALL_TAGS = [];
  var STATS = {};
  var CURRENT_DOC = null;
  var CURRENT_TAGS = [];
  var ACTIVE_FOLDER = null;
  var ACTIVE_TAG = null;
  var IS_TRASH = false;
  var IS_FAVORITES = false;
  var IS_SHARED = false;
  var IS_SHARED_BY_ME = false;
  var CURRENT_SORT = 'updated_desc';
  var CURRENT_VIEW = localStorage.getItem('docmanager_view') || 'grid';
  var CURRENT_PAGE = 1;
  var PER_PAGE = 10;
  var EDITOR_MODE = 'split';
  var IS_VIEW_ONLY = false;
  var SEARCH_TIMER = null;
  var CURRENT_SHARE_DOC_ID = null;
  var ELIGIBLE_SHARE_USERS = [];
  var SELECTED_SHARE_USER_ID = null;

  var SVG_DOC = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>';
  var SVG_FOLDER = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>';
  var SVG_TRASH = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>';
  var SVG_STAR = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
  var SVG_SHARE = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>';

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

  function isReadOnlyRole() {
    var r = window.ME && window.ME.role;
    return r === 'Visualizador' || r === 'Aprovador';
  }

  // ─── INICIALIZAÇÃO ──────────────────────────────────
  function init() {
    applyRoleRestrictions();
    bindEvents();
    bindShareAutocomplete();
    loadAll().then(function () {
      var urlParams = new URLSearchParams(window.location.search);
      var editId = urlParams.get('edit');
      if (editId) {
        openDoc(parseInt(editId, 10), 'split');
      } else {
        var filterParam = urlParams.get('filter');
        if (filterParam === 'shared') {
          filterShared();
        } else if (filterParam === 'shared_by_me') {
          filterSharedByMe();
        } else if (filterParam === 'favorites') {
          filterFavorites();
        }
      }
    });
    setViewMode(CURRENT_VIEW);

    var docsTable = document.querySelector('#docsTableWrapper table.table');
    if (docsTable) {
      TableSort.init(docsTable, {
        onSort: function(col, dir) {
          ALL_DOCS = TableSort.sortArray(ALL_DOCS, col, dir, function(d, c) {
            if (c === 'title') return d.title;
            if (c === 'category') return d.category_name;
            if (c === 'tags') return d.tags ? d.tags.join(', ') : '';
            if (c === 'author') return d.author_name;
            if (c === 'versions') return d.version_count || 0;
            if (c === 'updated') return d.updated_at;
            return '';
          });
          var tableBody = document.getElementById('docsTableBody');
          renderTableView(tableBody);
        }
      });
    }
  }

  function applyRoleRestrictions() {
    if (isReadOnlyRole()) {
      var btnFolder = document.getElementById('btnHeaderNewFolder');
      var btnDoc = document.getElementById('btnHeaderNewDoc');
      var btnSideFolder = document.getElementById('btnSidebarNewFolder');
      var btnEmptyDoc = document.getElementById('emptyNewDocBtn');

      if (btnFolder) btnFolder.hidden = true;
      if (btnDoc) btnDoc.hidden = true;
      if (btnSideFolder) btnSideFolder.hidden = true;
      if (btnEmptyDoc) btnEmptyDoc.hidden = true;
    }
  }

  function bindEvents() {
    // Busca instantânea
    var searchInput = document.getElementById('docsSearch');
    var searchClear = document.getElementById('docsSearchClear');
    if (searchInput) {
      searchInput.addEventListener('input', function () {
        if (searchClear) searchClear.hidden = !searchInput.value;
        clearTimeout(SEARCH_TIMER);
        SEARCH_TIMER = setTimeout(loadDocs, 250);
      });
    }

    if (searchClear) {
      searchClear.addEventListener('click', function () {
        searchInput.value = '';
        searchClear.hidden = true;
        loadDocs();
      });
    }

    // Input de Tags no Editor
    var tagInput = document.getElementById('tagInput');
    if (tagInput) {
      tagInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ',') {
          e.preventDefault();
          var val = tagInput.value.trim().replace(/,/g, '');
          if (val && CURRENT_TAGS.indexOf(val) === -1) {
            CURRENT_TAGS.push(val);
            renderTags();
          }
          tagInput.value = '';
        } else if (e.key === 'Backspace' && !tagInput.value && CURRENT_TAGS.length) {
          CURRENT_TAGS.pop();
          renderTags();
        }
      });
    }

    // Live update do Editor (Estatísticas e Live Preview)
    var docContent = document.getElementById('docContent');
    if (docContent) {
      docContent.addEventListener('input', function () {
        updateWordStats();
        if (EDITOR_MODE === 'split' || EDITOR_MODE === 'preview') {
          renderMarkdownPreview();
        }
        var status = document.getElementById('statusSaveState');
        if (status) status.textContent = 'Alterações não salvas';
      });
    }

    // Upload Dropzone
    var fileInput = document.getElementById('fileInput');
    var zone = document.getElementById('uploadZone');
    if (fileInput && zone) {
      zone.addEventListener('click', function () { fileInput.click(); });
      zone.addEventListener('dragover', function (e) { e.preventDefault(); zone.classList.add('dragover'); });
      zone.addEventListener('dragleave', function () { zone.classList.remove('dragover'); });
      zone.addEventListener('drop', function (e) {
        e.preventDefault();
        zone.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
          fileInput.files = e.dataTransfer.files;
          handleUpload();
        }
      });
      fileInput.addEventListener('change', handleUpload);
    }

    // Atalhos Globais de Teclado
    document.addEventListener('keydown', function (e) {
      // Focar busca ao teclar '/' se não estiver num input/textarea
      if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
        var overlay = document.getElementById('editorOverlay');
        if (!overlay || overlay.hidden) {
          e.preventDefault();
          var search = document.getElementById('docsSearch');
          if (search) { search.focus(); search.select(); }
        }
      }

      // Atalho 'N' para novo documento fora de inputs
      if ((e.key === 'n' || e.key === 'N') && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
        var overlayN = document.getElementById('editorOverlay');
        if (!overlayN || overlayN.hidden) {
          e.preventDefault();
          newDoc();
        }
      }

      // Esc para fechar o editor ou modais
      if (e.key === 'Escape') {
        var catModal = document.getElementById('catModalOverlay');
        var upModal = document.getElementById('uploadModalOverlay');
        var edOverlay = document.getElementById('editorOverlay');

        if (catModal && !catModal.hidden) { closeCatModal(); return; }
        if (upModal && !upModal.hidden) { closeUploadModal(); return; }
        if (edOverlay && !edOverlay.hidden) { closeEditor(); return; }
      }

      // Atalhos dentro do Editor
      var overlaySave = document.getElementById('editorOverlay');
      if (overlaySave && !overlaySave.hidden) {
        if (IS_VIEW_ONLY) {
          return;
        }
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
          e.preventDefault();
          saveDoc();
        }
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
          e.preventDefault();
          insertFormat('bold');
        }
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'i') {
          e.preventDefault();
          insertFormat('italic');
        }
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
          e.preventDefault();
          insertFormat('link');
        }
      }
    });

    // Fechar dropdowns ao clicar fora
    document.addEventListener('click', function (e) {
      var expMenu = document.getElementById('exportDropdownMenu');
      if (expMenu && !expMenu.hidden && !e.target.closest('.doceditor-dropdown-wrap')) {
        expMenu.hidden = true;
      }
    });
  }

  // ─── CARREGAMENTO DE DADOS ──────────────────────────
  function loadAll() {
    return Promise.all([
      loadCategories(),
      loadTags(),
      loadStats(),
      loadDocs()
    ]);
  }

  function loadStats() {
    return api('GET', '/api/docs/stats').then(function (res) {
      STATS = res.stats || {};
      var stDocs = document.getElementById('statTotalDocs');
      var stCats = document.getElementById('statTotalCats');
      var stTags = document.getElementById('statTotalTags');
      if (stDocs) stDocs.textContent = STATS.total_docs || 0;
      if (stCats) stCats.textContent = STATS.total_categories || 0;
      if (stTags) stTags.textContent = STATS.total_tags || 0;
      renderFolderTree();
    }).catch(function () {});
  }

  function loadCategories() {
    return api('GET', '/api/docs/categories').then(function (res) {
      ALL_CATS = res.categories || [];
      renderFolderTree();
      renderCategorySelect();
    });
  }

  function loadTags() {
    return api('GET', '/api/docs/tags').then(function (res) {
      ALL_TAGS = res.tags || [];
      renderTagCloud();
    });
  }

  function loadDocs() {
    var params = new URLSearchParams();
    var q = (document.getElementById('docsSearch').value || '').trim();
    if (q) params.set('q', q);
    if (IS_TRASH) {
      params.set('trash', '1');
    } else if (IS_FAVORITES) {
      params.set('favorites', '1');
    } else if (IS_SHARED) {
      params.set('shared_with_me', '1');
    } else if (IS_SHARED_BY_ME) {
      params.set('shared_by_me', '1');
    } else {
      if (ACTIVE_FOLDER !== null) params.set('category_id', ACTIVE_FOLDER);
      if (ACTIVE_TAG) params.set('tag', ACTIVE_TAG);
    }
    if (CURRENT_SORT) params.set('sort', CURRENT_SORT);

    return api('GET', '/api/docs?' + params.toString()).then(function (res) {
      ALL_DOCS = res.docs || [];
      renderDocs();
      updateHeaderSubtitle();
      renderFolderTree();
    });
  }

  function updateHeaderSubtitle() {
    var subtitle = document.getElementById('listSubtitle');
    if (!subtitle) return;
    var count = ALL_DOCS.length;
    var txt = '';
    if (IS_TRASH) {
      txt = count + (count === 1 ? ' documento na lixeira' : ' documentos na lixeira');
    } else if (IS_FAVORITES) {
      txt = count + (count === 1 ? ' documento favorito' : ' documentos favoritos');
    } else {
      txt = count + (count === 1 ? ' documento encontrado' : ' documentos encontrados');
      if (ACTIVE_TAG) txt += ' com a tag #' + ACTIVE_TAG;
    }
    subtitle.textContent = txt;
  }

  // ─── RENDERIZAÇÃO DA SIDEBAR ────────────────────────
  function renderFolderTree() {
    var tree = document.getElementById('folderTree');
    if (!tree) return;

    var totalDocsCount = STATS.total_docs !== undefined ? STATS.total_docs : ALL_DOCS.length;
    var totalFavCount = STATS.total_favorites !== undefined ? STATS.total_favorites : 0;
    var totalSharedWithMeCount = STATS.shared_with_me !== undefined ? STATS.shared_with_me : 0;
    var totalSharedByMeCount = STATS.shared_by_me !== undefined ? STATS.shared_by_me : 0;

    var html = '<div class="folder-nav-item' + (ACTIVE_FOLDER === null && !ACTIVE_TAG && !IS_TRASH && !IS_FAVORITES && !IS_SHARED && !IS_SHARED_BY_ME ? ' active' : '') + '" onclick="Docs.filterFolder(null)">'
      + '<span class="folder-nav-icon">' + SVG_DOC + '</span>'
      + '<span class="folder-nav-name">Todos os Documentos</span>'
      + '<span class="folder-nav-count">' + totalDocsCount + '</span>'
      + '</div>';

    // Item Favoritos na Sidebar
    html += '<div class="folder-nav-item fav-nav-item' + (IS_FAVORITES ? ' active' : '') + '" onclick="Docs.filterFavorites()">'
      + '<span class="folder-nav-icon" style="color:#f59e0b">' + SVG_STAR + '</span>'
      + '<span class="folder-nav-name">Favoritos</span>'
      + '<span class="folder-nav-count fav-count-badge">' + totalFavCount + '</span>'
      + '</div>';

    // Itens de Compartilhamento na Sidebar (Apenas para Admin e Editor)
    var isViewer = (window.ME && window.ME.role === 'Visualizador');
    if (!isViewer) {
      // Item Compartilhados Comigo na Sidebar
      html += '<div class="folder-nav-item shared-nav-item' + (IS_SHARED ? ' active' : '') + '" onclick="Docs.filterShared()">'
        + '<span class="folder-nav-icon" style="color:#6366f1">' + SVG_SHARE + '</span>'
        + '<span class="folder-nav-name">Compartilhados Comigo</span>'
        + '<span class="folder-nav-count shared-count-badge">' + totalSharedWithMeCount + '</span>'
        + '</div>';

      // Item Compartilhados por Mim na Sidebar
      html += '<div class="folder-nav-item shared-by-me-nav-item' + (IS_SHARED_BY_ME ? ' active' : '') + '" onclick="Docs.filterSharedByMe()">'
        + '<span class="folder-nav-icon" style="color:#0ea5e9">' + SVG_SHARE + '</span>'
        + '<span class="folder-nav-name">Compartilhados por Mim</span>'
        + '<span class="folder-nav-count shared-by-me-count-badge">' + totalSharedByMeCount + '</span>'
        + '</div>';
    }

    html += '<div class="folder-nav-item' + (ACTIVE_FOLDER === -1 && !IS_FAVORITES && !IS_SHARED && !IS_SHARED_BY_ME && !IS_TRASH ? ' active' : '') + '" onclick="Docs.filterFolder(-1)">'
      + '<span class="folder-nav-icon">' + SVG_FOLDER + '</span>'
      + '<span class="folder-nav-name">Sem Pasta (Raiz)</span>'
      + '</div>';

    function renderNode(cat, depth) {
      var icon = ICONS_MAP[cat.icon] || SVG_FOLDER;
      var catColor = cat.color || 'var(--docs-primary)';
      var count = cat.doc_count || 0;
      var hasChildren = cat.children && cat.children.length > 0;
      var paddingLeft = (0.6 + depth * 0.9) + 'rem';

      var actionsPopup = '';
      if (!isReadOnlyRole()) {
        actionsPopup = '<div class="folder-actions-popup">'
          + '<button type="button" class="f-act-btn" onclick="event.stopPropagation();Docs.newDoc(' + cat.id + ')" title="Novo doc nesta pasta">+</button>'
          + '<button type="button" class="f-act-btn" onclick="event.stopPropagation();Docs.openCatModal(' + cat.id + ')" title="Editar pasta">✎</button>'
          + '<button type="button" class="f-act-btn del" onclick="event.stopPropagation();Docs.deleteCategory(' + cat.id + ')" title="Excluir pasta">✕</button>'
          + '</div>';
      }

      html += '<div class="folder-nav-item' + (ACTIVE_FOLDER === (intVal(cat.id)) ? ' active' : '') + '"'
        + ' onclick="Docs.filterFolder(' + cat.id + ')"'
        + ' style="padding-left:' + paddingLeft + '">'
        + '<span class="folder-nav-icon" style="color:' + esc(catColor) + '">' + icon + '</span>'
        + '<span class="folder-nav-name" title="' + esc(cat.name) + '">' + esc(cat.name) + '</span>'
        + actionsPopup
        + '<span class="folder-nav-count">' + count + '</span>'
        + '</div>';

      if (hasChildren) {
        for (var i = 0; i < cat.children.length; i++) {
          renderNode(cat.children[i], depth + 1);
        }
      }
    }

    var roots = ALL_CATS.filter(function (c) { return !c.parent_id; });
    for (var i = 0; i < roots.length; i++) {
      renderNode(roots[i], 0);
    }

    if (!isReadOnlyRole()) {
      var trashCount = STATS.total_trash || 0;
      html += '<div class="folder-nav-item trash-item' + (IS_TRASH ? ' active' : '') + '" onclick="Docs.filterTrash()">'
        + '<span class="folder-nav-icon">' + SVG_TRASH + '</span>'
        + '<span class="folder-nav-name">Lixeira</span>'
        + '<span class="folder-nav-count">' + trashCount + '</span>'
        + '</div>';
    }

    tree.innerHTML = html;
  }

  function renderTagCloud() {
    var cloud = document.getElementById('tagCloud');
    if (!cloud) return;
    if (!ALL_TAGS.length) {
      cloud.innerHTML = '<span style="font-size:0.75rem;color:var(--text-muted)">Nenhuma tag criada</span>';
      return;
    }

    var html = '';
    for (var i = 0; i < ALL_TAGS.length; i++) {
      var t = ALL_TAGS[i];
      var isActive = ACTIVE_TAG === t.name;
      html += '<button type="button" class="tag-cloud-chip' + (isActive ? ' active' : '') + '" onclick="Docs.filterTag(\'' + esc(t.name) + '\')">'
        + '#' + esc(t.name)
        + ' <span class="tag-cloud-count">' + (t.usage_count || 0) + '</span>'
        + '</button>';
    }
    cloud.innerHTML = html;
  }

  function renderCategorySelect() {
    var sel = document.getElementById('docCategory');
    if (!sel) return;
    sel.innerHTML = '<option value="">Sem Pasta (Raiz)</option>';

    var flat = flattenCats(ALL_CATS, 0);
    for (var i = 0; i < flat.length; i++) {
      var prefix = '';
      for (var d = 0; d < flat[i]._depth; d++) prefix += '— ';
      var opt = document.createElement('option');
      opt.value = flat[i].id;
      opt.textContent = prefix + flat[i].name;
      sel.appendChild(opt);
    }
  }

  function flattenCats(cats, depth) {
    var result = [];
    for (var i = 0; i < cats.length; i++) {
      var c = Object.assign({}, cats[i]);
      c._depth = depth;
      result.push(c);
      if (c.children && c.children.length) {
        result = result.concat(flattenCats(c.children, depth + 1));
      }
    }
    return result;
  }

  // ─── FILTROS & ORDENAÇÃO ────────────────────────────
  function filterFolder(catId) {
    IS_TRASH = false;
    IS_FAVORITES = false;
    IS_SHARED = false;
    IS_SHARED_BY_ME = false;
    CURRENT_PAGE = 1;
    var trashBanner = document.getElementById('docsTrashBanner');
    if (trashBanner) trashBanner.hidden = true;

    ACTIVE_FOLDER = catId !== null ? intVal(catId) : null;
    ACTIVE_TAG = null;
    updateFilterIndicator();
    renderFolderTree();
    renderTagCloud();

    var titleEl = document.getElementById('listTitle');
    var iconEl = document.getElementById('currentViewIcon');

    if (ACTIVE_FOLDER === null) {
      if (titleEl) titleEl.textContent = 'Todos os Documentos';
      if (iconEl) iconEl.innerHTML = SVG_DOC;
    } else if (ACTIVE_FOLDER === -1) {
      if (titleEl) titleEl.textContent = 'Documentos sem Pasta';
      if (iconEl) iconEl.innerHTML = SVG_FOLDER;
    } else {
      var cat = findCat(ALL_CATS, ACTIVE_FOLDER);
      if (titleEl) titleEl.textContent = cat ? cat.name : 'Pasta Selecionada';
      if (iconEl) iconEl.innerHTML = cat ? (ICONS_MAP[cat.icon] || SVG_FOLDER) : SVG_FOLDER;
    }

    loadDocs();
  }

  function filterFavorites() {
    IS_TRASH = false;
    IS_FAVORITES = true;
    IS_SHARED = false;
    IS_SHARED_BY_ME = false;
    CURRENT_PAGE = 1;
    var trashBanner = document.getElementById('docsTrashBanner');
    if (trashBanner) trashBanner.hidden = true;

    ACTIVE_FOLDER = null;
    ACTIVE_TAG = null;
    updateFilterIndicator();
    renderFolderTree();
    renderTagCloud();

    var titleEl = document.getElementById('listTitle');
    var iconEl = document.getElementById('currentViewIcon');
    if (titleEl) titleEl.textContent = 'Documentos Favoritos';
    if (iconEl) iconEl.innerHTML = '<span style="color:#f59e0b">' + SVG_STAR + '</span>';

    loadDocs();
  }

  function filterShared() {
    IS_TRASH = false;
    IS_FAVORITES = false;
    IS_SHARED = true;
    IS_SHARED_BY_ME = false;
    CURRENT_PAGE = 1;
    var trashBanner = document.getElementById('docsTrashBanner');
    if (trashBanner) trashBanner.hidden = true;

    ACTIVE_FOLDER = null;
    ACTIVE_TAG = null;
    updateFilterIndicator();
    renderFolderTree();
    renderTagCloud();

    var titleEl = document.getElementById('listTitle');
    var iconEl = document.getElementById('currentViewIcon');
    if (titleEl) titleEl.textContent = 'Compartilhados Comigo';
    if (iconEl) iconEl.innerHTML = '<span style="color:#6366f1">' + SVG_SHARE + '</span>';

    loadDocs();
  }

  function filterSharedByMe() {
    IS_TRASH = false;
    IS_FAVORITES = false;
    IS_SHARED = false;
    IS_SHARED_BY_ME = true;
    CURRENT_PAGE = 1;
    var trashBanner = document.getElementById('docsTrashBanner');
    if (trashBanner) trashBanner.hidden = true;

    ACTIVE_FOLDER = null;
    ACTIVE_TAG = null;
    updateFilterIndicator();
    renderFolderTree();
    renderTagCloud();

    var titleEl = document.getElementById('listTitle');
    var iconEl = document.getElementById('currentViewIcon');
    if (titleEl) titleEl.textContent = 'Compartilhados por Mim';
    if (iconEl) iconEl.innerHTML = '<span style="color:#0ea5e9">' + SVG_SHARE + '</span>';

    loadDocs();
  }

  function filterTag(tagName) {
    IS_TRASH = false;
    IS_FAVORITES = false;
    IS_SHARED = false;
    IS_SHARED_BY_ME = false;
    CURRENT_PAGE = 1;
    var trashBanner = document.getElementById('docsTrashBanner');
    if (trashBanner) trashBanner.hidden = true;

    if (ACTIVE_TAG === tagName) {
      ACTIVE_TAG = null;
    } else {
      ACTIVE_TAG = tagName;
    }
    updateFilterIndicator();
    renderTagCloud();
    loadDocs();
  }

  function filterTrash() {
    IS_TRASH = true;
    IS_FAVORITES = false;
    IS_SHARED = false;
    IS_SHARED_BY_ME = false;
    CURRENT_PAGE = 1;
    ACTIVE_FOLDER = null;
    ACTIVE_TAG = null;
    updateFilterIndicator();
    renderFolderTree();
    renderTagCloud();

    var titleEl = document.getElementById('listTitle');
    var iconEl = document.getElementById('currentViewIcon');
    var trashBanner = document.getElementById('docsTrashBanner');

    if (titleEl) titleEl.textContent = 'Lixeira de Documentos';
    if (iconEl) iconEl.innerHTML = SVG_TRASH;
    if (trashBanner) trashBanner.hidden = false;

    loadDocs();
  }

  function clearFilter() {
    IS_TRASH = false;
    IS_FAVORITES = false;
    IS_SHARED = false;
    IS_SHARED_BY_ME = false;
    CURRENT_PAGE = 1;
    var trashBanner = document.getElementById('docsTrashBanner');
    if (trashBanner) trashBanner.hidden = true;

    ACTIVE_FOLDER = null;
    ACTIVE_TAG = null;
    updateFilterIndicator();
    renderFolderTree();
    renderTagCloud();
    var titleEl = document.getElementById('listTitle');
    if (titleEl) titleEl.textContent = 'Todos os Documentos';
    loadDocs();
  }

  function updateFilterIndicator() {
    var bar = document.getElementById('activeFilterBar');
    var chip = document.getElementById('filterChipText');
    if (!bar || !chip) return;

    if (IS_TRASH) {
      bar.hidden = false;
      chip.textContent = 'Lixeira de Documentos';
    } else if (IS_FAVORITES) {
      bar.hidden = false;
      chip.textContent = '⭐ Documentos Favoritos';
    } else if (IS_SHARED) {
      bar.hidden = false;
      chip.textContent = '👥 Compartilhados Comigo';
    } else if (IS_SHARED_BY_ME) {
      bar.hidden = false;
      chip.textContent = '📤 Compartilhados por Mim';
    } else if (ACTIVE_TAG) {
      bar.hidden = false;
      chip.textContent = 'Tag #' + ACTIVE_TAG;
    } else if (ACTIVE_FOLDER !== null) {
      bar.hidden = false;
      if (ACTIVE_FOLDER === -1) {
        chip.textContent = 'Sem Pasta (Raiz)';
      } else {
        var cat = findCat(ALL_CATS, ACTIVE_FOLDER);
        chip.textContent = 'Pasta: ' + (cat ? cat.name : '#' + ACTIVE_FOLDER);
      }
    } else {
      bar.hidden = true;
    }
  }

  function setViewMode(mode) {
    CURRENT_VIEW = mode === 'list' ? 'list' : 'grid';
    localStorage.setItem('docmanager_view', CURRENT_VIEW);

    var btnGrid = document.getElementById('btnViewGrid');
    var btnList = document.getElementById('btnViewList');
    var gridContainer = document.getElementById('docsGrid');
    var tableContainer = document.getElementById('docsTableWrapper');

    if (btnGrid) btnGrid.classList.toggle('active', CURRENT_VIEW === 'grid');
    if (btnList) btnList.classList.toggle('active', CURRENT_VIEW === 'list');

    if (gridContainer) gridContainer.hidden = (CURRENT_VIEW !== 'grid');
    if (tableContainer) tableContainer.hidden = (CURRENT_VIEW !== 'list');

    renderDocs();
  }

  function handleSortChange(val) {
    CURRENT_SORT = val;
    CURRENT_PAGE = 1;
    loadDocs();
  }

  // ─── RENDERIZAÇÃO DE DOCUMENTOS ─────────────────────
  function renderDocs() {
    var grid = document.getElementById('docsGrid');
    var tableBody = document.getElementById('docsTableBody');
    var emptyState = document.getElementById('docsEmptyState');

    if (!ALL_DOCS.length) {
      if (grid) grid.innerHTML = '';
      if (tableBody) tableBody.innerHTML = '';
      if (emptyState) emptyState.hidden = false;
      return;
    }

    if (emptyState) emptyState.hidden = true;

    if (CURRENT_VIEW === 'grid') {
      renderGridView(grid);
    } else {
      renderTableView(tableBody);
    }
  }

  function renderGridView(grid) {
    if (!grid) return;
    var html = '';
    var myRole = (window.ME && window.ME.role) || '';
    var myId = window.ME && window.ME.id ? parseInt(window.ME.id, 10) : 0;

    for (var i = 0; i < ALL_DOCS.length; i++) {
      var d = ALL_DOCS[i];
      var cleanPreview = stripMarkdown(d.content || '').substring(0, 140);
      var catIcon = d.category_icon && ICONS_MAP[d.category_icon] ? ICONS_MAP[d.category_icon] : SVG_FOLDER;
      var catColor = d.category_color || 'var(--docs-primary)';
      var catBadge = d.category_name
        ? '<span class="doc-category-badge" style="border-left: 3px solid ' + esc(catColor) + ';"><span style="color:' + esc(catColor) + '">' + catIcon + '</span> ' + esc(d.category_name) + '</span>'
        : '<span class="doc-category-badge" style="background:var(--docs-surface-2);color:var(--docs-text-mut)">' + SVG_FOLDER + ' Geral</span>';

      var tagsHtml = '';
      if (d.tags && d.tags.length) {
        for (var t = 0; t < d.tags.length; t++) {
          tagsHtml += '<span class="doc-tag-pill" onclick="event.stopPropagation();Docs.filterTag(\'' + esc(d.tags[t]) + '\')">#' + esc(d.tags[t]) + '</span>';
        }
      }

      var versionBadge = d.version_count > 1
        ? '<span class="doc-version-tag" title="' + d.version_count + ' versões salvas">v' + d.version_count + '</span>'
        : '';

      var isOwner = (d.is_owner || (myId > 0 && parseInt(d.created_by, 10) === myId));
      var canEdit = !isReadOnlyRole() && (myRole === 'Admin' || isOwner || !!d.can_edit);
      var canManageShares = (myRole === 'Admin' || isOwner || !!d.can_manage_shares);

      var favBtn = '<button type="button" class="table-action-btn ' + (d.is_favorite ? 'is-fav active' : '') + '" onclick="Docs.toggleFavorite(' + d.id + ', event)" title="' + (d.is_favorite ? 'Remover dos favoritos' : 'Adicionar aos favoritos') + '">'
        + '<svg viewBox="0 0 24 24" fill="' + (d.is_favorite ? '#f59e0b' : 'none') + '" stroke="' + (d.is_favorite ? '#f59e0b' : 'currentColor') + '" stroke-width="2" style="width:14px;height:14px"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>'
        + '</button>';

      var actionsHtml = '';
      if (isReadOnlyRole()) {
        actionsHtml = favBtn + '<button type="button" class="table-action-btn" onclick="event.stopPropagation();Docs.viewDoc(' + d.id + ')" title="Visualizar Documento (Leitura)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>';
      } else if (IS_TRASH) {
        actionsHtml = '<button type="button" class="table-action-btn restore-btn" onclick="event.stopPropagation();Docs.restoreDoc(' + d.id + ')" title="Restaurar Documento"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg></button>'
          + (myRole === 'Admin' ? '<button type="button" class="table-action-btn del" onclick="event.stopPropagation();Docs.forceDeleteDoc(' + d.id + ')" title="Excluir Definitivamente"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg></button>' : '');
      } else {
        actionsHtml = favBtn + '<button type="button" class="table-action-btn" onclick="event.stopPropagation();Docs.viewDoc(' + d.id + ')" title="Visualizar Documento (Leitura)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>';

        if (canEdit) {
          actionsHtml += '<button type="button" class="table-action-btn" onclick="event.stopPropagation();Docs.openDoc(' + d.id + ', \'split\')" title="Editar Documento"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>'
            + '<button type="button" class="table-action-btn" onclick="event.stopPropagation();Docs.duplicateDoc(' + d.id + ')" title="Duplicar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button>';
        }

        if (canManageShares) {
          actionsHtml += '<button type="button" class="table-action-btn" onclick="event.stopPropagation();Docs.openShareModal(' + d.id + ')" title="Compartilhar Documento"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg></button>'
            + '<button type="button" class="table-action-btn del" onclick="event.stopPropagation();Docs.deleteDocDirect(' + d.id + ')" title="Mover para a Lixeira"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg></button>';
        }
      }

      var statusBadge = '';
      if (d.status === 'pendente') {
        statusBadge = '<span class="doc-status-badge pending"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:11px;height:11px;margin-right:2px"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Pendente</span>';
      } else if (d.status === 'rejeitado') {
        statusBadge = '<span class="doc-status-badge rejected"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:11px;height:11px;margin-right:2px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>Rejeitado</span>';
      }

      var sharedBadge = '';
      if (d.is_shared_by_me) {
        sharedBadge = '<span class="doc-status-badge badge-sharing" title="Você está compartilhando este documento com ' + (d.shares_count || 1) + ' usuário(s)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:11px;height:11px;margin-right:2px"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>Compartilhando (' + (d.shares_count || 1) + ')</span>';
      } else if (d.is_shared_with_me || (myRole === 'Editor' && !isOwner && canEdit)) {
        sharedBadge = '<span class="doc-status-badge badge-shared-with-me" title="Compartilhado com você"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:11px;height:11px;margin-right:2px"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>Compartilhado</span>';
      }

      var cardClickMode = canEdit ? 'split' : 'preview';
      var cardClickAction = isReadOnlyRole() ? ('Docs.viewDoc(' + d.id + ')') : ('Docs.openDoc(' + d.id + ', \'' + cardClickMode + '\')');

      html += '<div class="doc-card-pro">'
        + '<div>'
        + '<div class="doc-card-header">'
        + '<div style="display:flex;align-items:center;gap:4px;flex-wrap:wrap;">' + catBadge + statusBadge + sharedBadge + '</div>'
        + '<div class="table-actions">'
        + actionsHtml
        + '</div>'
        + '</div>'
        + '<div class="doc-card-title">' + esc(d.title) + '</div>'
        + '<div class="doc-card-preview">' + (cleanPreview ? esc(cleanPreview) : '<em>Documento sem conteúdo adicional</em>') + '</div>'
        + '</div>'
        + '<div>'
        + (tagsHtml ? '<div class="doc-card-tags">' + tagsHtml + '</div>' : '')
        + '<div class="doc-card-footer">'
        + '<span class="doc-footer-author" title="Autor do documento"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;margin-right:2px"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>' + esc(d.author_name || 'Admin') + '</span>'
        + '<div class="doc-footer-meta">'
        + versionBadge
        + '<span>' + formatDate(d.updated_at) + '</span>'
        + '</div>'
        + '</div>'
        + '</div>'
        + '</div>';
    }

    grid.innerHTML = html;
  }

  function renderTableView(tableBody) {
    if (!tableBody) return;
    var html = '';
    var myRole = (window.ME && window.ME.role) || '';
    var myId = window.ME && window.ME.id ? parseInt(window.ME.id, 10) : 0;

    var totalItems = ALL_DOCS.length;
    var totalPages = Math.ceil(totalItems / PER_PAGE) || 1;
    if (CURRENT_PAGE > totalPages) CURRENT_PAGE = totalPages;
    if (CURRENT_PAGE < 1) CURRENT_PAGE = 1;

    var startIndex = (CURRENT_PAGE - 1) * PER_PAGE;
    var endIndex = Math.min(startIndex + PER_PAGE, totalItems);
    var pageDocs = ALL_DOCS.slice(startIndex, endIndex);

    for (var i = 0; i < pageDocs.length; i++) {
      var d = pageDocs[i];
      var catIcon = d.category_icon && ICONS_MAP[d.category_icon] ? ICONS_MAP[d.category_icon] : SVG_FOLDER;
      var catColor = d.category_color || 'var(--docs-primary)';
      var catName = d.category_name 
        ? ('<span style="color:' + esc(catColor) + '">' + catIcon + '</span> ' + esc(d.category_name)) 
        : (SVG_FOLDER + ' Geral');
      var docIcon = '<span style="color:' + esc(catColor) + '">' + (d.category_icon && ICONS_MAP[d.category_icon] ? ICONS_MAP[d.category_icon] : SVG_DOC) + '</span>';

      var tagsHtml = '';
      if (d.tags && d.tags.length) {
        var allTagsText = d.tags.map(function (t) { return '#' + t; }).join(', ');
        tagsHtml = '<span class="doc-tag-count-badge" title="' + esc(allTagsText) + '">' + d.tags.length + '</span>';
      } else {
        tagsHtml = '<span style="color:var(--docs-text-mut);font-size:0.75rem">—</span>';
      }

      var statusBadgeTable = '';
      if (d.status === 'pendente') {
        statusBadgeTable += ' <span class="doc-status-badge pending" style="font-size:0.65rem;padding:0.1rem 0.35rem;">Pendente</span>';
      } else if (d.status === 'rejeitado') {
        statusBadgeTable += ' <span class="doc-status-badge rejected" style="font-size:0.65rem;padding:0.1rem 0.35rem;">Rejeitado</span>';
      }
      if (d.is_shared_by_me) {
        statusBadgeTable += ' <span class="doc-status-badge badge-sharing" style="font-size:0.65rem;padding:0.1rem 0.35rem;" title="Você está compartilhando este documento com ' + (d.shares_count || 1) + ' usuário(s)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:10px;height:10px;vertical-align:-1px;margin-right:2px"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>Compartilhando (' + (d.shares_count || 1) + ')</span>';
      } else if (d.is_shared_with_me) {
        statusBadgeTable += ' <span class="doc-status-badge badge-shared-with-me" style="font-size:0.65rem;padding:0.1rem 0.35rem;" title="Compartilhado com você"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:10px;height:10px;vertical-align:-1px;margin-right:2px"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>Compartilhado</span>';
      }

      var isOwner = (d.is_owner || (myId > 0 && parseInt(d.created_by, 10) === myId));
      var canEdit = !isReadOnlyRole() && (myRole === 'Admin' || isOwner || !!d.can_edit);
      var canManageShares = (myRole === 'Admin' || isOwner || !!d.can_manage_shares);

      var favBtn = '<button type="button" class="table-action-btn ' + (d.is_favorite ? 'is-fav active' : '') + '" onclick="Docs.toggleFavorite(' + d.id + ', event)" title="' + (d.is_favorite ? 'Remover dos favoritos' : 'Adicionar aos favoritos') + '">'
        + '<svg viewBox="0 0 24 24" fill="' + (d.is_favorite ? '#f59e0b' : 'none') + '" stroke="' + (d.is_favorite ? '#f59e0b' : 'currentColor') + '" stroke-width="2" style="width:13px;height:13px"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>'
        + '</button>';

      var actionsHtml = '';
      if (isReadOnlyRole()) {
        actionsHtml = favBtn + '<button type="button" class="table-action-btn" onclick="event.stopPropagation();Docs.viewDoc(' + d.id + ')" title="Visualizar (Leitura)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>';
      } else if (IS_TRASH) {
        actionsHtml = '<button type="button" class="table-action-btn restore-btn" onclick="event.stopPropagation();Docs.restoreDoc(' + d.id + ')" title="Restaurar Documento"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg></button>'
          + (myRole === 'Admin' ? '<button type="button" class="table-action-btn del" onclick="event.stopPropagation();Docs.forceDeleteDoc(' + d.id + ')" title="Excluir Definitivamente"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg></button>' : '');
      } else {
        actionsHtml = favBtn + '<button type="button" class="table-action-btn" onclick="event.stopPropagation();Docs.viewDoc(' + d.id + ')" title="Visualizar (Leitura)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>';

        if (canEdit) {
          actionsHtml += '<button type="button" class="table-action-btn" onclick="event.stopPropagation();Docs.openDoc(' + d.id + ', \'split\')" title="Editar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>'
            + '<button type="button" class="table-action-btn" onclick="event.stopPropagation();Docs.duplicateDoc(' + d.id + ')" title="Duplicar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button>';
        }

        if (canManageShares) {
          actionsHtml += '<button type="button" class="table-action-btn" onclick="event.stopPropagation();Docs.openShareModal(' + d.id + ')" title="Compartilhar Documento"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg></button>'
            + '<button type="button" class="table-action-btn del" onclick="event.stopPropagation();Docs.deleteDocDirect(' + d.id + ')" title="Mover para a Lixeira"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg></button>';
        }
      }

      var titleAction = isReadOnlyRole() ? ('Docs.viewDoc(' + d.id + ')') : ('Docs.openDoc(' + d.id + ', \'' + (canEdit ? 'split' : 'preview') + '\')');

      html += '<tr>'
        + '<td><span style="font-size:1.1rem;display:inline-flex;align-items:center;">' + docIcon + '</span></td>'
        + '<td><div class="table-doc-title" style="font-weight:600;color:var(--docs-text)">' + esc(d.title) + statusBadgeTable + '</div></td>'
        + '<td><span style="font-size:0.8rem;display:inline-flex;align-items:center;gap:4px;">' + catName + '</span></td>'
        + '<td><div style="display:flex;gap:3px;flex-wrap:wrap">' + tagsHtml + '</div></td>'
        + '<td><span style="font-size:0.78rem;color:var(--docs-text-mut);font-weight:500;display:inline-flex;align-items:center;gap:3px;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>' + esc(d.author_name || 'Admin') + '</span></td>'
        + '<td><span class="doc-version-tag">v' + (d.version_count || 1) + '</span></td>'
        + '<td><span style="font-size:0.78rem;color:var(--docs-text-mut)">' + formatDate(d.updated_at) + '</span></td>'
        + '<td>'
        + '<div class="table-actions">'
        + actionsHtml
        + '</div>'
        + '</td>'
        + '</tr>';
    }

    tableBody.innerHTML = html;
    renderPagination(totalItems, totalItems > 0 ? (startIndex + 1) : 0, endIndex, totalPages);
  }

  function renderPagination(totalItems, from, to, totalPages) {
    var pagEl = document.getElementById('docsTablePagination');
    if (!pagEl) return;

    if (totalItems <= 0) {
      pagEl.innerHTML = '';
      return;
    }

    var html = '<div class="pagination-info">'
      + 'Mostrando <strong>' + from + '</strong> a <strong>' + to + '</strong> de <strong>' + totalItems + '</strong> documentos'
      + '</div>';

    html += '<div class="pagination-controls">';
    
    // Botão Anterior
    html += '<button type="button" class="pagination-btn' + (CURRENT_PAGE <= 1 ? ' disabled' : '') + '"'
      + (CURRENT_PAGE > 1 ? ' onclick="Docs.changePage(' + (CURRENT_PAGE - 1) + ')"' : ' disabled') + '>'
      + '← Anterior'
      + '</button>';

    // Páginas
    for (var p = 1; p <= totalPages; p++) {
      if (totalPages > 7) {
        if (p > 1 && p < totalPages && Math.abs(p - CURRENT_PAGE) > 2) {
          if (p === 2 || p === totalPages - 1) {
            html += '<span class="pagination-ellipsis">...</span>';
          }
          continue;
        }
      }
      html += '<button type="button" class="pagination-btn' + (p === CURRENT_PAGE ? ' active' : '') + '" onclick="Docs.changePage(' + p + ')">' + p + '</button>';
    }

    // Botão Próximo
    html += '<button type="button" class="pagination-btn' + (CURRENT_PAGE >= totalPages ? ' disabled' : '') + '"'
      + (CURRENT_PAGE < totalPages ? ' onclick="Docs.changePage(' + (CURRENT_PAGE + 1) + ')"' : ' disabled') + '>'
      + 'Próximo →'
      + '</button>';

    html += '</div>';

    pagEl.innerHTML = html;
  }

  function changePage(p) {
    CURRENT_PAGE = p;
    renderDocs();
  }

  // ─── DOCEDITOR PRO ──────────────────────────────────
  function newDoc(initialCatId) {
    if (isReadOnlyRole()) return;
    CURRENT_DOC = null;
    CURRENT_TAGS = [];

    var titleInput = document.getElementById('docTitle');
    var contentInput = document.getElementById('docContent');
    var catSelect = document.getElementById('docCategory');
    var verNoteInput = document.getElementById('docVersionNote');

    if (titleInput) titleInput.value = '';
    if (contentInput) contentInput.value = '';
    if (verNoteInput) verNoteInput.value = '';
    if (catSelect) {
      if (initialCatId) catSelect.value = initialCatId;
      else if (ACTIVE_FOLDER && ACTIVE_FOLDER > 0) catSelect.value = ACTIVE_FOLDER;
      else catSelect.value = '';
    }

    var btnShare = document.getElementById('btnShareDoc');
    if (btnShare) btnShare.hidden = true;
    var btnDel = document.getElementById('btnDeleteDoc');
    if (btnDel) btnDel.hidden = true;
    var btnSave = document.getElementById('btnSaveDoc');
    if (btnSave) btnSave.hidden = false;

    setViewOnlyMode(false);
    renderTags();
    updateWordStats();
    renderMarkdownPreview();
    switchEditorMode('split');
    showEditorOverlay();

    var status = document.getElementById('statusSaveState');
    if (status) status.textContent = 'Novo documento';

    if (titleInput) titleInput.focus();
  }

  function openDoc(id, initialMode) {
    api('GET', '/api/docs/' + id).then(function (res) {
      CURRENT_DOC = res.doc;
      CURRENT_TAGS = (CURRENT_DOC.tags || []).slice();

      var myRole = (window.ME && window.ME.role) || '';
      var canUserEdit = CURRENT_DOC.can_edit && !isReadOnlyRole();
      var isView = (initialMode === 'preview' || !canUserEdit || isReadOnlyRole());
      var modeToSet = isView ? 'preview' : (initialMode || 'split');

      var titleInput = document.getElementById('docTitle');
      var contentInput = document.getElementById('docContent');
      var catSelect = document.getElementById('docCategory');
      var verNoteInput = document.getElementById('docVersionNote');

      if (titleInput) titleInput.value = CURRENT_DOC.title || '';
      if (contentInput) contentInput.value = CURRENT_DOC.content || '';
      if (verNoteInput) verNoteInput.value = '';
      if (catSelect) catSelect.value = CURRENT_DOC.category_id || '';

      setViewOnlyMode(isView);
      renderTags();
      updateWordStats();
      renderMarkdownPreview();
      switchEditorMode(modeToSet);
      showEditorOverlay();

      var btnShare = document.getElementById('btnShareDoc');
      if (btnShare) {
        btnShare.hidden = !(CURRENT_DOC.can_manage_shares || myRole === 'Admin' || CURRENT_DOC.is_owner);
      }

      var btnDel = document.getElementById('btnDeleteDoc');
      if (btnDel) {
        btnDel.hidden = isView || !(CURRENT_DOC.can_manage_shares || myRole === 'Admin' || CURRENT_DOC.is_owner);
      }

      var btnSave = document.getElementById('btnSaveDoc');
      if (btnSave) {
        btnSave.hidden = isView;
      }

      var status = document.getElementById('statusSaveState');
      if (status) {
        if (!canUserEdit && myRole === 'Editor') {
          status.textContent = '🔒 Somente Leitura (Você não é autor nem colaborador) • Autor: ' + (CURRENT_DOC.author_name || 'Admin');
        } else if (isView) {
          status.textContent = '👁️ Modo de Leitura • Autor: ' + (CURRENT_DOC.author_name || 'Admin') + (CURRENT_DOC.updated_at ? (' • ' + formatDate(CURRENT_DOC.updated_at)) : '');
        } else {
          status.textContent = 'Autor: ' + (CURRENT_DOC.author_name || 'Admin') + ' • Salvo em ' + formatDate(CURRENT_DOC.updated_at);
        }
      }
    }).catch(function (err) {
      toast(err.message || 'Erro ao carregar documento.', 'error');
    });
  }

  function saveDoc() {
    if (IS_VIEW_ONLY || isReadOnlyRole()) {
      toast('Documento aberto apenas em modo de leitura.', 'info');
      return;
    }
    var titleInput = document.getElementById('docTitle');
    var title = (titleInput ? titleInput.value : '').trim();
    if (!title) {
      toast('Por favor, informe o título do documento.', 'error');
      if (titleInput) titleInput.focus();
      return;
    }

    var content = document.getElementById('docContent').value;
    var catVal = document.getElementById('docCategory').value;
    var verNote = (document.getElementById('docVersionNote').value || '').trim();

    var payload = {
      title: title,
      content: content,
      category_id: catVal ? parseInt(catVal, 10) : null,
      version_note: verNote,
      tags: CURRENT_TAGS,
    };

    var promise = CURRENT_DOC
      ? api('PUT', '/api/docs/' + CURRENT_DOC.id, payload)
      : api('POST', '/api/docs', payload);

    promise.then(function (res) {
      if (!CURRENT_DOC && res.id) {
        CURRENT_DOC = { id: res.id };
        var btnDel = document.getElementById('btnDeleteDoc');
        if (btnDel) btnDel.hidden = false;
      }

      document.getElementById('docVersionNote').value = '';
      var status = document.getElementById('statusSaveState');
      if (status) status.textContent = 'Salvo com sucesso!';

      toast('Documento salvo com sucesso!', 'success');
      loadAll();
    }).catch(function (err) {
      toast(err.message || 'Erro ao salvar documento.', 'error');
    });
  }

  function duplicateDoc(id) {
    if (isReadOnlyRole()) return;
    api('POST', '/api/docs/' + id + '/duplicate', {}).then(function (res) {
      toast('Documento duplicado!', 'success');
      loadAll().then(function () {
        if (res.id) openDoc(res.id, 'split');
      });
    }).catch(function (err) {
      toast(err.message || 'Erro ao duplicar.', 'error');
    });
  }

  function deleteDoc() {
    if (!CURRENT_DOC) return;
    deleteDocDirect(CURRENT_DOC.id, function () {
      closeEditor();
    });
  }

  function deleteDocDirect(id, callback) {
    showConfirm({
      title: 'Mover para a Lixeira?',
      message: 'O documento será movido para a lixeira e poderá ser restaurado a qualquer momento.',
      confirmText: 'Mover para Lixeira',
      confirmClass: 'btn btn-danger'
    }, function () {
      api('DELETE', '/api/docs/' + id).then(function () {
        toast('Documento movido para a lixeira.', 'success');
        loadAll();
        if (typeof callback === 'function') callback();
      }).catch(function (err) {
        toast(err.message || 'Erro ao mover documento.', 'error');
      });
    });
  }

  function restoreDoc(id) {
    api('POST', '/api/docs/' + id + '/restore', {}).then(function () {
      toast('Documento restaurado com sucesso!', 'success');
      loadAll();
    }).catch(function (err) {
      toast(err.message || 'Erro ao restaurar documento.', 'error');
    });
  }

  function forceDeleteDoc(id) {
    showConfirm({
      title: 'Excluir Definitivamente?',
      message: 'Esta ação não pode ser desfeita e removerá o documento permanentemente do banco de dados.',
      confirmText: 'Excluir Definitivamente',
      confirmClass: 'btn btn-danger'
    }, function () {
      api('DELETE', '/api/docs/' + id + '/force').then(function () {
        toast('Documento excluído permanentemente.', 'success');
        loadAll();
      }).catch(function (err) {
        toast(err.message || 'Erro ao excluir definitivamente.', 'error');
      });
    });
  }

  function emptyTrashModal() {
    showConfirm({
      title: 'Esvaziar Lixeira?',
      message: 'Todos os documentos na lixeira serão permanentemente excluídos. Deseja continuar?',
      confirmText: 'Sim, esvaziar lixeira',
      confirmClass: 'btn btn-danger'
    }, function () {
      api('POST', '/api/docs/trash/empty', {}).then(function (res) {
        toast('Lixeira esvaziada com sucesso.', 'success');
        loadAll();
      }).catch(function (err) {
        toast(err.message || 'Erro ao esvaziar lixeira.', 'error');
      });
    });
  }

  function setViewOnlyMode(viewOnly) {
    IS_VIEW_ONLY = !!viewOnly;
    var overlay = document.getElementById('editorOverlay');
    var titleInput = document.getElementById('docTitle');
    var catSelect = document.getElementById('docCategory');
    var btnUpload = document.getElementById('btnDocUpload');
    var btnVersions = document.getElementById('btnToggleVersions');
    var btnDel = document.getElementById('btnDeleteDoc');
    var btnSave = document.getElementById('btnSaveDoc');
    var toolbar = document.querySelector('.doceditor-toolbar');
    var tagInput = document.getElementById('tagInput');
    var versionNoteSec = document.querySelector('.meta-version-note-section');
    var textarea = document.getElementById('docContent');

    if (overlay) {
      overlay.classList.toggle('doceditor-view-only', IS_VIEW_ONLY);
    }

    if (IS_VIEW_ONLY) {
      if (titleInput) {
        titleInput.readOnly = true;
        titleInput.setAttribute('tabindex', '-1');
      }
      if (catSelect) catSelect.disabled = true;
      if (btnUpload) btnUpload.hidden = true;
      if (btnVersions) btnVersions.hidden = true;
      if (btnDel) btnDel.hidden = true;
      if (btnSave) btnSave.hidden = true;
      if (toolbar) toolbar.hidden = true;
      if (tagInput) tagInput.hidden = true;
      if (versionNoteSec) versionNoteSec.hidden = true;
      if (textarea) textarea.readOnly = true;
      switchEditorMode('preview');
    } else {
      if (titleInput) {
        titleInput.readOnly = false;
        titleInput.removeAttribute('tabindex');
      }
      if (catSelect) catSelect.disabled = false;
      if (btnUpload) btnUpload.hidden = false;
      if (btnVersions) btnVersions.hidden = false;
      if (btnDel) btnDel.hidden = !CURRENT_DOC;
      if (btnSave) btnSave.hidden = false;
      if (toolbar) toolbar.hidden = false;
      if (tagInput) tagInput.hidden = false;
      if (versionNoteSec) versionNoteSec.hidden = false;
      if (textarea) textarea.readOnly = false;
    }
  }

  function showEditorOverlay() {
    var overlay = document.getElementById('editorOverlay');
    if (overlay) overlay.hidden = false;
    document.body.style.overflow = 'hidden';
  }

  function closeEditor() {
    var overlay = document.getElementById('editorOverlay');
    if (overlay) overlay.hidden = true;
    var vSidebar = document.getElementById('versionSidebar');
    if (vSidebar) vSidebar.hidden = true;
    document.body.style.overflow = '';
    CURRENT_DOC = null;
    setViewOnlyMode(false);
    loadDocs();
  }

  // ─── EDITOR MODES & LIVE PREVIEW ────────────────────
  function switchEditorMode(mode) {
    if (IS_VIEW_ONLY && mode !== 'preview') {
      return;
    }
    EDITOR_MODE = mode;
    var tabEdit = document.getElementById('tabModeEdit');
    var tabSplit = document.getElementById('tabModeSplit');
    var tabPrev = document.getElementById('tabModePreview');
    var workspace = document.getElementById('editorWorkspace');

    if (tabEdit) tabEdit.classList.toggle('active', mode === 'edit');
    if (tabSplit) tabSplit.classList.toggle('active', mode === 'split');
    if (tabPrev) tabPrev.classList.toggle('active', mode === 'preview');

    if (workspace) {
      workspace.className = 'doceditor-workspace mode-' + mode;
    }

    if (mode === 'split' || mode === 'preview') {
      renderMarkdownPreview();
    }
  }

  function renderMarkdownPreview() {
    var preview = document.getElementById('previewContent');
    if (!preview) return;
    var raw = document.getElementById('docContent').value || '';

    if (!raw.trim()) {
      preview.innerHTML = '<div style="color:var(--text-muted);font-style:italic;padding:2rem 0">Nenhum conteúdo para visualização.</div>';
      return;
    }

    if (typeof marked !== 'undefined' && typeof marked.parse === 'function') {
      preview.innerHTML = marked.parse(raw);
    } else {
      preview.innerHTML = '<div style="white-space:pre-wrap;font-family:inherit;line-height:1.7">' + esc(raw) + '</div>';
    }
  }

  function updateWordStats() {
    var content = document.getElementById('docContent').value || '';
    var trimmed = content.trim();
    var words = trimmed === '' ? 0 : trimmed.split(/\s+/).length;
    var chars = content.length;
    var readMinutes = Math.max(1, Math.ceil(words / 200));

    var stWords = document.getElementById('statusWordCount');
    var stChars = document.getElementById('statusCharCount');
    var stTime = document.getElementById('statusReadTime');

    if (stWords) stWords.textContent = words + (words === 1 ? ' palavra' : ' palavras');
    if (stChars) stChars.textContent = chars + (chars === 1 ? ' caractere' : ' caracteres');
    if (stTime) stTime.textContent = '~' + readMinutes + ' min de leitura';
  }

  // ─── TOOLBAR FORMATTING ─────────────────────────────
  function insertFormat(type) {
    if (IS_VIEW_ONLY) return;
    var ta = document.getElementById('docContent');
    if (!ta) return;

    var start = ta.selectionStart;
    var end = ta.selectionEnd;
    var sel = ta.value.substring(start, end);
    var replacement = '';
    var newCursor = start;

    switch (type) {
      case 'bold':
        replacement = '**' + (sel || 'texto em negrito') + '**';
        newCursor = start + 2;
        break;
      case 'italic':
        replacement = '*' + (sel || 'texto em itálico') + '*';
        newCursor = start + 1;
        break;
      case 'strike':
        replacement = '~~' + (sel || 'texto tachado') + '~~';
        newCursor = start + 2;
        break;
      case 'code_inline':
        replacement = '`' + (sel || 'código') + '`';
        newCursor = start + 1;
        break;
      case 'h1':
        replacement = '# ' + (sel || 'Título 1');
        break;
      case 'h2':
        replacement = '## ' + (sel || 'Título 2');
        break;
      case 'h3':
        replacement = '### ' + (sel || 'Título 3');
        break;
      case 'ul':
        replacement = '- ' + (sel || 'Item da lista');
        break;
      case 'ol':
        replacement = '1. ' + (sel || 'Item numerado');
        break;
      case 'task':
        replacement = '- [ ] ' + (sel || 'Tarefa a realizar');
        break;
      case 'quote':
        replacement = '> ' + (sel || 'Citação ou nota importante');
        break;
      case 'code_block':
        replacement = '```javascript\n' + (sel || '// Seu código aqui') + '\n```';
        break;
      case 'table':
        replacement = '| Coluna 1 | Coluna 2 | Coluna 3 |\n| :--- | :---: | ---: |\n| Item A | Detalhe | R$ 100 |\n| Item B | Detalhe | R$ 200 |';
        break;
      case 'hr':
        replacement = '\n---\n';
        break;
      case 'link':
        replacement = '[' + (sel || 'Texto do link') + '](https://exemplo.com)';
        break;
    }

    ta.value = ta.value.substring(0, start) + replacement + ta.value.substring(end);
    ta.focus();
    ta.setSelectionRange(start + replacement.length, start + replacement.length);

    updateWordStats();
    if (EDITOR_MODE === 'split' || EDITOR_MODE === 'preview') {
      renderMarkdownPreview();
    }
  }

  // ─── TAGS NO EDITOR ─────────────────────────────────
  function renderTags() {
    var container = document.getElementById('tagsContainer');
    var input = document.getElementById('tagInput');
    if (!container || !input) return;

    var existing = container.querySelectorAll('.meta-tag-chip');
    for (var i = 0; i < existing.length; i++) existing[i].remove();

    for (var j = 0; j < CURRENT_TAGS.length; j++) {
      var tag = CURRENT_TAGS[j];
      var el = document.createElement('span');
      el.className = 'meta-tag-chip';
      if (IS_VIEW_ONLY) {
        el.textContent = '#' + tag;
      } else {
        el.innerHTML = '#' + esc(tag) + ' <span class="remove-tag" onclick="Docs.removeTag(\'' + esc(tag) + '\')">×</span>';
      }
      container.insertBefore(el, input);
    }
  }

  function removeTag(tagName) {
    CURRENT_TAGS = CURRENT_TAGS.filter(function (t) { return t !== tagName; });
    renderTags();
  }

  // ─── HISTÓRICO DE VERSÕES ───────────────────────────
  function toggleVersions() {
    var sidebar = document.getElementById('versionSidebar');
    if (!sidebar) return;
    sidebar.hidden = !sidebar.hidden;
    if (!sidebar.hidden && CURRENT_DOC) {
      renderVersionsList();
    }
  }

  function renderVersionsList() {
    var container = document.getElementById('versionList');
    if (!container) return;

    if (!CURRENT_DOC || !CURRENT_DOC.versions || !CURRENT_DOC.versions.length) {
      container.innerHTML = '<div style="color:var(--text-muted);font-size:0.8rem;text-align:center;padding:2rem 0">Nenhuma versão anterior registrada.</div>';
      return;
    }

    var versions = CURRENT_DOC.versions;
    var html = '';

    for (var i = 0; i < versions.length; i++) {
      var v = versions[i];
      var vNum = 'v' + (versions.length - i);
      html += '<div class="version-card-pro" onclick="Docs.previewVersion(' + i + ')">'
        + '<div class="version-card-top">'
        + '<span class="version-badge">' + vNum + '</span>'
        + '<span class="version-date">' + formatDate(v.edited_at) + '</span>'
        + '</div>'
        + '<div class="version-author">Por ' + esc(v.editor_name || 'Usuário') + '</div>'
        + (v.version_note ? '<div class="version-note">"' + esc(v.version_note) + '"</div>' : '')
        + '<div class="version-card-actions">'
        + '<button type="button" class="btn-restore-version" onclick="event.stopPropagation();Docs.restoreVersion(' + i + ')">Restaurar versão</button>'
        + '</div>'
        + '</div>';
    }

    container.innerHTML = html;
  }

  function previewVersion(index) {
    if (!CURRENT_DOC || !CURRENT_DOC.versions || !CURRENT_DOC.versions[index]) return;
    var v = CURRENT_DOC.versions[index];
    var ta = document.getElementById('docContent');
    if (ta) {
      ta.value = v.content || '';
      updateWordStats();
      renderMarkdownPreview();
      switchEditorMode('split');
      toast('Exibindo prévia da versão selecionada.', 'info');
    }
  }

  function restoreVersion(index) {
    showConfirm({
      title: 'Restaurar Versão?',
      message: 'Deseja restaurar esta versão histórica para o editor? As alterações não salvas no texto atual serão substituídas.',
      confirmText: 'Restaurar para o Editor',
      confirmClass: 'btn btn-primary'
    }, function () {
      var v = CURRENT_DOC.versions[index];
      document.getElementById('docContent').value = v.content || '';
      document.getElementById('docVersionNote').value = 'Restaurada versão de ' + formatDate(v.edited_at);
      updateWordStats();
      renderMarkdownPreview();
      toast('Versão restaurada no editor. Clique em "Salvar" para confirmar.', 'success');
    });
  }

  // ─── EXPORTAÇÃO & IMPRESSÃO ─────────────────────────
  function toggleExportMenu() {
    var menu = document.getElementById('exportDropdownMenu');
    if (menu) menu.hidden = !menu.hidden;
  }

  function exportMarkdown() {
    var title = (document.getElementById('docTitle').value || 'documento').trim();
    var content = document.getElementById('docContent').value || '';
    var blob = new Blob([content], { type: 'text/markdown;charset=utf-8;' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = slugify(title) + '.md';
    a.click();
    URL.revokeObjectURL(url);
    toggleExportMenu();
    toast('Download do arquivo Markdown iniciado.', 'success');
  }

  function printDocument() {
    toggleExportMenu();
    var title = document.getElementById('docTitle').value || 'Documento';
    var contentHtml = document.getElementById('previewContent').innerHTML;

    var win = window.open('', '_blank');
    win.document.write('<!DOCTYPE html><html><head><title>' + esc(title) + '</title>'
      + '<style>'
      + 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; max-width: 800px; margin: 40px auto; padding: 0 20px; color: #111; }'
      + 'h1 { border-bottom: 2px solid #eaeaea; padding-bottom: 8px; }'
      + 'table { width: 100%; border-collapse: collapse; margin: 16px 0; }'
      + 'th, td { border: 1px solid #ddd; padding: 8px 12px; }'
      + 'th { background: #f7f7f7; }'
      + 'pre { background: #f4f4f4; padding: 12px; border-radius: 6px; overflow-x: auto; }'
      + 'blockquote { border-left: 4px solid #0066cc; margin: 16px 0; padding: 8px 16px; background: #f0f7ff; }'
      + 'img { max-width: 100%; }'
      + '</style>'
      + '</head><body>'
      + '<h1>' + esc(title) + '</h1>'
      + contentHtml
      + '</body></html>');
    win.document.close();
    win.focus();
    setTimeout(function () { win.print(); win.close(); }, 300);
  }

  function copyMarkdown() {
    var content = document.getElementById('docContent').value || '';
    navigator.clipboard.writeText(content).then(function () {
      toggleExportMenu();
      toast('Markdown copiado para a área de transferência!', 'success');
    }).catch(function () {
      toast('Não foi possível copiar.', 'error');
    });
  }

  // ─── GESTÃO DE PASTAS ───────────────────────────────
  function openCatModal(editId) {
    if (isReadOnlyRole()) return;
    var overlay = document.getElementById('catModalOverlay');
    if (!overlay) return;
    overlay.hidden = false;

    var editIdInput = document.getElementById('catEditId');
    var titleEl = document.getElementById('catModalTitle');
    var nameInput = document.getElementById('catName');
    var parentSel = document.getElementById('catParent');
    var sortInput = document.getElementById('catSortOrder');

    editIdInput.value = editId || '';
    titleEl.textContent = editId ? 'Editar Pasta' : 'Nova Pasta';

    // Popular seletor de pais
    parentSel.innerHTML = '<option value="">Nenhuma (Pasta Raiz)</option>';
    var flat = flattenCats(ALL_CATS.filter(function (c) { return !editId || c.id != editId; }), 0);
    for (var i = 0; i < flat.length; i++) {
      var opt = document.createElement('option');
      opt.value = flat[i].id;
      opt.textContent = flat[i].name;
      parentSel.appendChild(opt);
    }

    if (editId) {
      var cat = findCat(ALL_CATS, editId);
      if (cat) {
        nameInput.value = cat.name;
        parentSel.value = cat.parent_id || '';
        sortInput.value = cat.sort_order || 0;
        selectCatIcon(cat.icon || 'folder');
        selectCatColor(cat.color || '#1abb9c');
      }
    } else {
      nameInput.value = '';
      parentSel.value = (ACTIVE_FOLDER && ACTIVE_FOLDER > 0) ? ACTIVE_FOLDER : '';
      sortInput.value = '0';
      selectCatIcon('folder');
      selectCatColor('#1abb9c');
    }

    nameInput.focus();
  }

  function closeCatModal() {
    var overlay = document.getElementById('catModalOverlay');
    if (overlay) overlay.hidden = true;
  }

  function selectCatIcon(icon, btn) {
    var iconInput = document.getElementById('catIcon');
    if (iconInput) iconInput.value = icon;
    var buttons = document.querySelectorAll('#iconSelectorGrid .icon-opt');
    for (var i = 0; i < buttons.length; i++) {
      buttons[i].classList.toggle('active', buttons[i].getAttribute('data-icon') === icon);
    }
  }

  function selectCatColor(color, btn) {
    var colorInput = document.getElementById('catColor');
    if (colorInput) colorInput.value = color;
    var badge = document.getElementById('catModalIconBadge');
    if (badge) badge.style.color = color;
    var buttons = document.querySelectorAll('#colorSelectorGrid .color-opt');
    for (var i = 0; i < buttons.length; i++) {
      buttons[i].classList.toggle('active', buttons[i].getAttribute('data-color') === color);
    }
  }

  function saveCategory() {
    if (isReadOnlyRole()) return;
    var name = (document.getElementById('catName').value || '').trim();
    if (!name) {
      toast('Por favor, informe o nome da pasta.', 'error');
      return;
    }

    var parentId = document.getElementById('catParent').value;
    var icon = (document.getElementById('catIcon') && document.getElementById('catIcon').value) || 'folder';
    var color = (document.getElementById('catColor') && document.getElementById('catColor').value) || '#1abb9c';
    var sortOrder = document.getElementById('catSortOrder').value;
    var editId = document.getElementById('catEditId').value;

    var payload = {
      name: name,
      parent_id: parentId ? parseInt(parentId, 10) : null,
      icon: icon || 'folder',
      color: color || '#1abb9c',
      sort_order: parseInt(sortOrder, 10) || 0,
    };

    var promise = editId
      ? api('PUT', '/api/docs/categories/' + editId, payload)
      : api('POST', '/api/docs/categories', payload);

    promise.then(function () {
      closeCatModal();
      toast('Pasta salva com sucesso!', 'success');
      loadAll();
    }).catch(function (err) {
      toast(err.message || 'Erro ao salvar pasta.', 'error');
    });
  }

  function deleteCategory(id) {
    if (isReadOnlyRole()) return;
    showConfirm({
      title: 'Excluir Pasta?',
      message: 'Deseja excluir esta pasta? Os documentos nela contidos serão movidos automaticamente para a raiz.',
      confirmText: 'Sim, excluir pasta',
      confirmClass: 'btn btn-danger'
    }, function () {
      api('DELETE', '/api/docs/categories/' + id).then(function () {
        toast('Pasta excluída com sucesso.', 'success');
        if (ACTIVE_FOLDER === id) ACTIVE_FOLDER = null;
        loadAll();
      }).catch(function (err) {
        toast(err.message || 'Erro ao excluir pasta.', 'error');
      });
    });
  }

  // ─── UPLOAD DE ANEXOS ───────────────────────────────
  function openUploadModal() {
    var overlay = document.getElementById('uploadModalOverlay');
    if (overlay) overlay.hidden = false;
    var result = document.getElementById('uploadResult');
    if (result) result.hidden = true;
  }

  function closeUploadModal() {
    var overlay = document.getElementById('uploadModalOverlay');
    if (overlay) overlay.hidden = true;
    var fileInput = document.getElementById('fileInput');
    if (fileInput) fileInput.value = '';
    var result = document.getElementById('uploadResult');
    if (result) result.hidden = true;
  }

  function handleUpload() {
    var fileInput = document.getElementById('fileInput');
    if (!fileInput.files || !fileInput.files.length) return;

    var file = fileInput.files[0];
    var fd = new FormData();
    fd.append('file', file);

    var progressBar = document.getElementById('uploadProgress');
    var progressFill = document.getElementById('uploadProgressBarFill');
    if (progressBar) progressBar.hidden = false;
    if (progressFill) progressFill.style.width = '60%';

    fetch('/api/docs/upload', {
      method: 'POST',
      headers: { 'X-CSRF-Token': window.CSRF_TOKEN || '' },
      body: fd,
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (progressBar) progressBar.hidden = true;
        if (data.ok === false) throw new Error(data.error || 'Falha no upload');

        var result = document.getElementById('uploadResult');
        result.hidden = false;

        var isImg = /\.(png|jpg|jpeg|gif|svg|webp)$/i.test(data.name);
        var previewHtml = isImg
          ? '<img src="/' + data.url + '" style="max-height:140px;border-radius:6px;object-fit:contain;margin-bottom:0.5rem" />'
          : '';

        result.innerHTML = previewHtml
          + '<div style="font-size:0.82rem;font-weight:600;color:var(--text)">' + esc(data.name) + ' (' + formatBytes(data.size || file.size) + ')</div>'
          + '<div style="display:flex;gap:0.4rem;margin-top:0.6rem">'
          + '<button type="button" class="btn btn-sm btn-primary" onclick="Docs.insertUpload(\'' + data.url + '\',\'' + esc(data.name) + '\')">Inserir no Documento</button>'
          + '<a href="/' + data.url + '" target="_blank" class="btn btn-sm btn-secondary">Abrir Arquivo</a>'
          + '</div>';

        toast('Arquivo enviado com sucesso!', 'success');
      })
      .catch(function (err) {
        if (progressBar) progressBar.hidden = true;
        toast(err.message || 'Erro no upload.', 'error');
      });
  }

  function insertUpload(url, name) {
    var ta = document.getElementById('docContent');
    if (!ta) return;

    var isImg = /\.(png|jpg|jpeg|gif|svg|webp)$/i.test(name);
    var md = isImg ? '![' + name + '](/' + url + ')' : '[' + name + '](/' + url + ')';

    var pos = ta.selectionStart;
    ta.value = ta.value.substring(0, pos) + md + '\n' + ta.value.substring(pos);
    ta.focus();
    ta.setSelectionRange(pos + md.length + 1, pos + md.length + 1);

    updateWordStats();
    if (EDITOR_MODE === 'split' || EDITOR_MODE === 'preview') {
      renderMarkdownPreview();
    }

    closeUploadModal();
    toast('Anexo inserido com sucesso!', 'success');
  }

  // ─── HELPERS ────────────────────────────────────────
  function findCat(cats, id) {
    var intId = intVal(id);
    for (var i = 0; i < cats.length; i++) {
      if (intVal(cats[i].id) === intId) return cats[i];
      if (cats[i].children && cats[i].children.length) {
        var found = findCat(cats[i].children, intId);
        if (found) return found;
      }
    }
    return null;
  }

  function intVal(v) {
    var n = parseInt(v, 10);
    return isNaN(n) ? 0 : n;
  }

  function formatDate(iso) {
    if (!iso) return '';
    var d = new Date(iso);
    return d.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' });
  }

  function formatBytes(bytes) {
    if (!bytes || bytes === 0) return '0 Bytes';
    var k = 1024;
    var sizes = ['Bytes', 'KB', 'MB', 'GB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
  }

  function stripMarkdown(md) {
    return md
      .replace(/#+\s/g, '')
      .replace(/!\[.*?\]\(.*?\)/g, '')
      .replace(/\[(.*?)\]\(.*?\)/g, '$1')
      .replace(/(\*\*|__)(.*?)\1/g, '$2')
      .replace(/(\*|_)(.*?)\1/g, '$2')
      .replace(/`{1,3}.*?`{1,3}/g, '')
      .replace(/>\s/g, '')
      .replace(/[-*+]\s/g, '')
      .replace(/\n+/g, ' ')
      .trim();
  }

  function slugify(text) {
    return text.toString().toLowerCase()
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .replace(/\s+/g, '-')
      .replace(/[^\w\-]+/g, '')
      .replace(/\-\-+/g, '-')
      .replace(/^-+/, '')
      .replace(/-+$/, '');
  }

  // ─── VISUALIZAÇÃO SINGLE DO DOCUMENTO ──────────────
  function viewDoc(id) {
    window.location.href = '/docs/view/' + id;
  }

  // ─── COMPARTILHAMENTO DE DOCUMENTOS ────────────────
  function openShareModal(docId) {
    var id = docId || (CURRENT_DOC ? CURRENT_DOC.id : null);
    if (!id) return;
    CURRENT_SHARE_DOC_ID = id;
    SELECTED_SHARE_USER_ID = null;
    clearSelectedShareUser();

    var overlay = document.getElementById('shareModalOverlay');
    if (overlay) overlay.hidden = false;

    var titleEl = document.getElementById('shareModalDocTitle');
    var authorEl = document.getElementById('shareModalDocAuthor');
    var searchInput = document.getElementById('shareUserSearchInput');
    var listEl = document.getElementById('shareListContainer');

    if (searchInput) searchInput.value = '';
    if (listEl) listEl.innerHTML = '<div style="font-size:0.8rem;color:var(--docs-text-mut);padding:8px 0;">Carregando colaboradores...</div>';

    var docObj = ALL_DOCS.find(function(item) { return item.id === id; }) || CURRENT_DOC;
    if (docObj) {
      if (titleEl) titleEl.textContent = 'Compartilhar: ' + (docObj.title || 'Documento #' + id);
      if (authorEl) authorEl.textContent = 'Criado por: ' + (docObj.author_name || 'Admin');
    }

    loadShareModalData(id);
  }

  function closeShareModal() {
    CURRENT_SHARE_DOC_ID = null;
    SELECTED_SHARE_USER_ID = null;
    clearSelectedShareUser();
    var overlay = document.getElementById('shareModalOverlay');
    if (overlay) overlay.hidden = true;
  }

  function loadShareModalData(id) {
    Promise.all([
      api('GET', '/api/docs/' + id + '/shares'),
      api('GET', '/api/docs/' + id + '/share-users')
    ]).then(function (results) {
      var sharesRes = results[0];
      var usersRes = results[1];

      ELIGIBLE_SHARE_USERS = usersRes.users || [];
      renderCollaboratorsList(sharesRes.shares || [], sharesRes.can_manage);
    }).catch(function (err) {
      toast(err.message || 'Erro ao carregar compartilhamentos.', 'error');
    });
  }

  function bindShareAutocomplete() {
    var input = document.getElementById('shareUserSearchInput');
    var dropdown = document.getElementById('shareUserDropdownList');
    if (!input || !dropdown) return;

    input.addEventListener('input', function () {
      var q = input.value.trim().toLowerCase();
      if (!q) {
        dropdown.hidden = true;
        dropdown.innerHTML = '';
        return;
      }

      var matches = ELIGIBLE_SHARE_USERS.filter(function (u) {
        return (u.name && u.name.toLowerCase().indexOf(q) !== -1) ||
               (u.login && u.login.toLowerCase().indexOf(q) !== -1);
      });

      if (!matches.length) {
        dropdown.innerHTML = '<div class="autocomplete-empty">Nenhum usuário encontrado para "' + esc(q) + '"</div>';
        dropdown.hidden = false;
        return;
      }

      var html = '';
      matches.forEach(function (u) {
        var initials = (u.name || 'U').substring(0, 2).toUpperCase();
        var roleBadge = u.role ? (' (' + u.role + ')') : '';
        html += '<div class="autocomplete-item" onclick="Docs.selectShareUser(' + u.id + ', \'' + esc(u.name) + '\', \'' + esc(u.login) + '\')">'
          + '<div class="avatar-small" style="width:26px;height:26px;border-radius:50%;background:var(--primary);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:700;">' + esc(initials) + '</div>'
          + '<div class="user-meta">'
          + '<span class="user-name">' + esc(u.name) + '</span>'
          + '<span class="user-login">@' + esc(u.login) + roleBadge + '</span>'
          + '</div>'
          + '</div>';
      });

      dropdown.innerHTML = html;
      dropdown.hidden = false;
    });

    document.addEventListener('click', function (e) {
      if (!input.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.hidden = true;
      }
    });
  }

  function selectShareUser(id, name, login) {
    SELECTED_SHARE_USER_ID = id;
    var dropdown = document.getElementById('shareUserDropdownList');
    var chip = document.getElementById('selectedUserChip');
    var nameEl = document.getElementById('selectedUserName');
    var input = document.getElementById('shareUserSearchInput');
    var btn = document.getElementById('btnAddShareUser');

    if (dropdown) dropdown.hidden = true;
    if (input) input.value = '';
    if (nameEl) nameEl.textContent = '👤 ' + name + ' (@' + login + ')';
    if (chip) chip.hidden = false;
    if (btn) btn.disabled = false;
  }

  function clearSelectedShareUser() {
    SELECTED_SHARE_USER_ID = null;
    var chip = document.getElementById('selectedUserChip');
    var btn = document.getElementById('btnAddShareUser');
    if (chip) chip.hidden = true;
    if (btn) btn.disabled = true;
  }

  function addSelectedUser() {
    if (!CURRENT_SHARE_DOC_ID || !SELECTED_SHARE_USER_ID) return;

    api('POST', '/api/docs/' + CURRENT_SHARE_DOC_ID + '/shares', {
      user_id: SELECTED_SHARE_USER_ID,
      can_edit: true
    }).then(function (res) {
      toast(res.message || 'Compartilhamento realizado com sucesso!', 'success');
      clearSelectedShareUser();
      loadShareModalData(CURRENT_SHARE_DOC_ID);
      loadAll();
    }).catch(function (err) {
      toast(err.message || 'Erro ao compartilhar documento.', 'error');
    });
  }

  function renderCollaboratorsList(shares, canManage) {
    var listEl = document.getElementById('shareListContainer');
    if (!listEl) return;

    if (!shares || !shares.length) {
      listEl.innerHTML = '<div style="font-size:0.82rem;color:var(--docs-text-mut);padding:10px;text-align:center;background:var(--docs-surface-2);border-radius:6px;">Nenhum colaborador adicional. Apenas o autor e Administradores podem editar.</div>';
      return;
    }

    var html = '';
    shares.forEach(function (s) {
      var initials = (s.name || 'U').substring(0, 2).toUpperCase();
      var removeBtn = canManage
        ? '<button type="button" class="btn btn-sm btn-danger-outline" onclick="Docs.removeShareUser(' + s.user_id + ')" title="Remover acesso de edição" style="padding:0.25rem 0.5rem;font-size:0.75rem;">Remover</button>'
        : '';

      html += '<div class="share-collaborator-item" style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:var(--docs-surface-2);border:1px solid var(--border);border-radius:6px;margin-bottom:6px;">'
        + '<div style="display:flex;align-items:center;gap:10px;">'
        + '<div class="avatar-small" style="width:32px;height:32px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.75rem;">' + esc(initials) + '</div>'
        + '<div>'
        + '<div style="font-weight:600;font-size:0.85rem;color:var(--docs-text);">' + esc(s.name) + ' <span style="font-size:0.72rem;color:var(--docs-text-mut);font-weight:normal;">(@' + esc(s.login) + ')</span></div>'
        + '<div style="font-size:0.75rem;color:var(--docs-primary);display:flex;align-items:center;gap:4px;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:11px;height:11px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg> Pode Editar • <span style="color:var(--docs-text-mut);">' + formatDate(s.created_at) + '</span></div>'
        + '</div>'
        + '</div>'
        + removeBtn
        + '</div>';
    });

    listEl.innerHTML = html;
  }

  function removeShareUser(userId) {
    if (!CURRENT_SHARE_DOC_ID || !userId) return;
    showConfirm({
      title: 'Remover Compartilhamento?',
      message: 'O usuário perderá a permissão de editar este documento.',
      confirmText: 'Remover Acesso',
      confirmClass: 'btn btn-danger'
    }, function () {
      api('DELETE', '/api/docs/' + CURRENT_SHARE_DOC_ID + '/shares/' + userId).then(function (res) {
        toast(res.message || 'Compartilhamento removido com sucesso.', 'success');
        loadShareModalData(CURRENT_SHARE_DOC_ID);
        loadAll();
      }).catch(function (err) {
        toast(err.message || 'Erro ao remover compartilhamento.', 'error');
      });
    });
  }

  function toggleFavorite(docId, e) {
    if (e && e.stopPropagation) e.stopPropagation();
    var id = intVal(docId);
    if (!id) return;

    api('POST', '/api/docs/' + id + '/favorite').then(function (res) {
      var isFav = !!res.is_favorite;
      for (var i = 0; i < ALL_DOCS.length; i++) {
        if (ALL_DOCS[i].id === id) {
          ALL_DOCS[i].is_favorite = isFav;
          break;
        }
      }
      if (res.total_favorites !== undefined) {
        STATS.total_favorites = res.total_favorites;
      }
      if (IS_FAVORITES && !isFav) {
        // Se estiver filtrando favoritos e desfavoritou, recarrega a lista
        loadDocs();
      } else {
        renderDocs();
        renderFolderTree();
      }
      toast(res.message || (isFav ? 'Adicionado aos favoritos!' : 'Removido dos favoritos.'), isFav ? 'success' : 'info');
    }).catch(function (err) {
      toast(err.message || 'Erro ao alternar favorito.', 'error');
    });
  }

  // ─── API PÚBLICA ────────────────────────────────────
  return {
    init: init,
    filterFolder: filterFolder,
    filterFavorites: filterFavorites,
    filterShared: filterShared,
    filterSharedByMe: filterSharedByMe,
    filterTag: filterTag,
    filterTrash: filterTrash,
    clearFilter: clearFilter,
    toggleFavorite: toggleFavorite,
    setViewMode: setViewMode,
    handleSortChange: handleSortChange,
    newDoc: newDoc,
    openDoc: openDoc,
    viewDoc: viewDoc,
    saveDoc: saveDoc,
    duplicateDoc: duplicateDoc,
    deleteDoc: deleteDoc,
    deleteDocDirect: deleteDocDirect,
    restoreDoc: restoreDoc,
    forceDeleteDoc: forceDeleteDoc,
    emptyTrashModal: emptyTrashModal,
    closeEditor: closeEditor,
    switchEditorMode: switchEditorMode,
    insertFormat: insertFormat,
    removeTag: removeTag,
    toggleVersions: toggleVersions,
    previewVersion: previewVersion,
    restoreVersion: restoreVersion,
    toggleExportMenu: toggleExportMenu,
    exportMarkdown: exportMarkdown,
    printDocument: printDocument,
    copyMarkdown: copyMarkdown,
    openCatModal: openCatModal,
    closeCatModal: closeCatModal,
    selectCatIcon: selectCatIcon,
    selectCatColor: selectCatColor,
    saveCategory: saveCategory,
    deleteCategory: deleteCategory,
    changePage: changePage,
    openUploadModal: openUploadModal,
    closeUploadModal: closeUploadModal,
    insertUpload: insertUpload,
    openShareModal: openShareModal,
    closeShareModal: closeShareModal,
    selectShareUser: selectShareUser,
    clearSelectedUser: clearSelectedShareUser,
    addSelectedUser: addSelectedUser,
    removeShareUser: removeShareUser,
  };
})();

document.addEventListener('DOMContentLoaded', Docs.init);

