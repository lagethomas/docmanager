/**
 * Módulo Localizações (Administração) — CRUD de localizações para restrição geográfica.
 */
'use strict';

var LOCATIONS = [];
var FILTERED_LOCATIONS = [];

var ICON_PENCIL = '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>';
var ICON_TRASH = '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>';

function loadLocations() {
  return api('GET', '/api/locations/admin').then(function (data) {
    LOCATIONS = data || [];
    filtrarLocalizacoes();
  }).catch(function (err) {
    console.error('Erro ao carregar localizações:', err);
    toast('Erro ao carregar localizações.', 'error');
  });
}

function filtrarLocalizacoes() {
  var input = document.getElementById('locationSearchInput');
  var query = input ? input.value.trim().toLowerCase() : '';

  if (!query) {
    FILTERED_LOCATIONS = LOCATIONS.slice();
  } else {
    FILTERED_LOCATIONS = LOCATIONS.filter(function (l) {
      return (l.name && l.name.toLowerCase().indexOf(query) !== -1) ||
             (l.description && l.description.toLowerCase().indexOf(query) !== -1);
    });
  }

  renderLocationsTable();
}

function renderLocationsTable() {
  var sortState = TableSort.getSort('locationTable');
  var items = FILTERED_LOCATIONS.slice();

  if (sortState.col) {
    items = TableSort.sortArray(items, sortState.col, sortState.dir, function(l, col) {
      if (col === 'name') return l.name;
      if (col === 'description') return l.description;
      if (col === 'users_count') return Number(l.users_count || 0);
      if (col === 'docs_count') return Number(l.docs_count || 0);
      if (col === 'status') return l.status;
      return l.name;
    });
  }

  var ids = items.map(function (l) { return l.id; });
  var pg = paginated('loc', ids);
  var tbody = document.getElementById('locationTableBody');
  if (!tbody) return;

  if (!pg.total) {
    tbody.innerHTML = '<tr><td colspan="6">'
      + '<div class="empty-state" style="padding: 2.5rem 1rem; text-align: center;">'
      + '<div class="empty-state-icon" style="margin-bottom: 0.5rem; color: var(--text-muted);"><svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>'
      + '<div style="font-weight: 600; color: var(--text);">Nenhuma localização encontrada</div>'
      + '<div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">Clique em "Nova Localização" para cadastrar uma unidade ou filial.</div>'
      + '</div></td></tr>';
    var pagContainer = document.getElementById('locationPagination');
    if (pagContainer) pagContainer.innerHTML = '';
    return;
  }

  tbody.innerHTML = pg.page.map(function (id) {
    var loc = LOCATIONS.find(function (x) { return x.id === id; });
    if (!loc) return '';

    var statusCls = loc.status === 'ativo' ? 'status status-green' : 'status status-red';
    var statusLabel = loc.status === 'ativo' ? 'Ativo' : 'Inativo';

    var row = '<tr data-id="' + loc.id + '">';
    row += '<td class="cell-strong" style="display:flex;align-items:center;gap:8px;">'
      + '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary);flex-shrink:0;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>'
      + '<span>' + esc(loc.name) + '</span></td>';
    row += '<td style="color:var(--text-muted);font-size:0.85rem;">' + (loc.description ? esc(loc.description) : '<span style="opacity:0.5;">—</span>') + '</td>';
    row += '<td style="text-align:center;"><span class="badge" style="background:rgba(99, 102, 241, 0.1);color:#6366f1;font-weight:600;padding:2px 8px;border-radius:12px;font-size:0.75rem;">' + Number(loc.users_count || 0) + '</span></td>';
    row += '<td style="text-align:center;"><span class="badge" style="background:rgba(26, 187, 156, 0.1);color:#1abb9c;font-weight:600;padding:2px 8px;border-radius:12px;font-size:0.75rem;">' + Number(loc.docs_count || 0) + '</span></td>';
    row += '<td><span class="' + statusCls + '">' + statusLabel + '</span></td>';
    row += '<td style="text-align:right;"><div class="table-actions" style="justify-content:flex-end;">'
      + '<button class="table-action-btn" title="Editar" aria-label="Editar '
      + esc(loc.name) + '" onclick="abrirModalLocalizacao(' + loc.id + ')">'
      + ICON_PENCIL + '</button>'
      + '<button class="table-action-btn del" title="Excluir" aria-label="Excluir '
      + esc(loc.name) + '" onclick="excluirLocalizacao(' + loc.id + ', \'' + esc(loc.name).replace(/'/g, "\\'") + '\')">'
      + ICON_TRASH + '</button>'
      + '</div></td></tr>';
    return row;
  }).join('');

  var pagContainer = document.getElementById('locationPagination');
  if (pagContainer) {
    pagContainer.innerHTML = renderPagination(pg.current, pg.pages, 'loc', 'goToLocPage');
  }
}

window.goToLocPage = function (page) {
  setPaginationPage('loc', page);
  renderLocationsTable();
};

function abrirModalLocalizacao(id) {
  var titulo = document.getElementById('modalLocationTitulo');
  var fId = document.getElementById('fLocId');
  var fName = document.getElementById('fLocName');
  var fDesc = document.getElementById('fLocDesc');
  var fStatus = document.getElementById('fLocStatus');

  if (id) {
    var loc = LOCATIONS.find(function (x) { return Number(x.id) === Number(id); });
    if (loc) {
      if (titulo) titulo.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary);"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg><span>Editar Localização</span>';
      if (fId) fId.value = loc.id;
      if (fName) fName.value = loc.name || '';
      if (fDesc) fDesc.value = loc.description || '';
      if (fStatus) {
        fStatus.value = loc.status || 'ativo';
        if (window.SearchableSelect) SearchableSelect.sync(fStatus);
      }
    }
  } else {
    if (titulo) titulo.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary);"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg><span>Nova Localização</span>';
    if (fId) fId.value = '';
    if (fName) fName.value = '';
    if (fDesc) fDesc.value = '';
    if (fStatus) {
      fStatus.value = 'ativo';
      if (window.SearchableSelect) SearchableSelect.sync(fStatus);
    }
  }

  openModal('modalLocationBG');
  setTimeout(function() { if (fName) fName.focus(); }, 100);
}

function fecharModalLocalizacao() {
  closeModal('modalLocationBG');
}

function salvarLocalizacao() {
  var fId = document.getElementById('fLocId');
  var fName = document.getElementById('fLocName');
  var fDesc = document.getElementById('fLocDesc');
  var fStatus = document.getElementById('fLocStatus');
  var btn = document.getElementById('btnSalvarLoc');

  var id = fId ? fId.value : '';
  var name = fName ? fName.value.trim() : '';
  var description = fDesc ? fDesc.value.trim() : '';
  var status = fStatus ? fStatus.value : 'ativo';

  if (!name) {
    toast('Informe o nome da localização.', 'warning');
    if (fName) fName.focus();
    return;
  }

  var payload = {
    name: name,
    description: description,
    status: status
  };

  if (btn) {
    btn.disabled = true;
    btn.textContent = 'Salvando...';
  }

  var promise = id ? api('PUT', '/api/locations/' + id, payload) : api('POST', '/api/locations', payload);

  promise.then(function () {
    toast('Localização ' + (id ? 'atualizada' : 'criada') + ' com sucesso!', 'success');
    fecharModalLocalizacao();
    loadLocations();
  }).catch(function (err) {
    var msg = (err && err.message) ? err.message : 'Erro ao salvar localização.';
    toast(msg, 'error');
  }).finally(function () {
    if (btn) {
      btn.disabled = false;
      btn.textContent = 'Salvar';
    }
  });
}

function excluirLocalizacao(id, name) {
  showConfirm({
    title: 'Excluir Localização?',
    message: 'Deseja realmente excluir a localização "' + name + '"?\nUsuários vinculados terão a localização desvinculada.',
    confirmText: 'Sim, excluir',
    confirmClass: 'btn btn-danger'
  }, function () {
    api('DELETE', '/api/locations/' + id).then(function () {
      toast('Localização excluída com sucesso.', 'success');
      loadLocations();
    }).catch(function (err) {
      var msg = (err && err.message) ? err.message : 'Erro ao excluir localização.';
      toast(msg, 'error');
    });
  });
}

// Exporta funções para o escopo global
window.abrirModalLocalizacao = abrirModalLocalizacao;
window.fecharModalLocalizacao = fecharModalLocalizacao;
window.salvarLocalizacao = salvarLocalizacao;
window.excluirLocalizacao = excluirLocalizacao;
window.filtrarLocalizacoes = filtrarLocalizacoes;
window.goToLocPage = goToLocPage;

document.addEventListener('DOMContentLoaded', function () {
  var tableEl = document.getElementById('locationTable');
  if (tableEl && typeof TableSort !== 'undefined') {
    TableSort.init(tableEl, {
      onSort: function () {
        renderLocationsTable();
      }
    });
  }
  loadLocations();

  // Fechar modal ao clicar no fundo
  var modalBG = document.getElementById('modalLocationBG');
  if (modalBG) {
    modalBG.addEventListener('click', function (e) {
      if (e.target === modalBG) {
        fecharModalLocalizacao();
      }
    });
  }

  // Tecla Escape para fechar modal
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modalBG && !modalBG.hidden) {
      fecharModalLocalizacao();
    }
  });
});
