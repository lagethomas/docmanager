/**
 * Módulo Áreas / Setores (Administração) — CRUD de áreas para restrição por setor.
 */
'use strict';

var AREAS = [];
var FILTERED_AREAS = [];

function loadAreas() {
  return api('GET', '/api/areas/admin').then(function (data) {
    AREAS = data || [];
    filtrarAreas();
  }).catch(function (err) {
    console.error('Erro ao carregar áreas:', err);
    toast('Erro ao carregar áreas.', 'error');
  });
}

function filtrarAreas() {
  var input = document.getElementById('areaSearchInput');
  var query = input ? input.value.trim().toLowerCase() : '';

  if (!query) {
    FILTERED_AREAS = AREAS.slice();
  } else {
    FILTERED_AREAS = AREAS.filter(function (a) {
      return (a.name && a.name.toLowerCase().indexOf(query) !== -1) ||
             (a.description && a.description.toLowerCase().indexOf(query) !== -1);
    });
  }

  renderAreasTable();
}

function renderAreasTable() {
  var sortState = TableSort.getSort('areaTable');
  var items = FILTERED_AREAS.slice();

  if (sortState.col) {
    items = TableSort.sortArray(items, sortState.col, sortState.dir, function(a, col) {
      if (col === 'name') return a.name;
      if (col === 'description') return a.description;
      if (col === 'users_count') return Number(a.users_count || 0);
      if (col === 'docs_count') return Number(a.docs_count || 0);
      if (col === 'status') return a.status;
      return a.name;
    });
  }

  var ids = items.map(function (a) { return a.id; });
  var pg = paginated('area', ids);
  var tbody = document.getElementById('areaTableBody');
  if (!tbody) return;

  if (!pg.total) {
    tbody.innerHTML = '<tr><td colspan="6">'
      + '<div class="empty-state" style="padding: 2.5rem 1rem; text-align: center;">'
      + '<div class="empty-state-icon" style="margin-bottom: 0.5rem; color: var(--text-muted);">' + Icons.get('area', { size: 32 }) + '</div>'
      + '<div style="font-weight: 600; color: var(--text);">Nenhuma área ou setor encontrado</div>'
      + '<div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">Clique em "Nova Área / Setor" para cadastrar um setor organizacional.</div>'
      + '</div></td></tr>';
    var pagContainer = document.getElementById('areaPagination');
    if (pagContainer) pagContainer.innerHTML = '';
    return;
  }

  tbody.innerHTML = pg.page.map(function (id) {
    var area = AREAS.find(function (x) { return x.id === id; });
    if (!area) return '';

    var statusCls = area.status === 'ativo' ? 'status status-green' : 'status status-red';
    var statusLabel = area.status === 'ativo' ? 'Ativo' : 'Inativo';

    var row = '<tr data-id="' + area.id + '">';
    row += '<td class="cell-strong" style="display:flex;align-items:center;gap:8px;">'
      + Icons.get('area', { size: 14, color: 'var(--primary)', style: 'flex-shrink:0;' })
      + '<span>' + esc(area.name) + '</span></td>';
    row += '<td style="color:var(--text-muted);font-size:0.85rem;">' + (area.description ? esc(area.description) : '<span style="opacity:0.5;">—</span>') + '</td>';
    row += '<td style="text-align:center;"><span class="badge" style="background:rgba(99, 102, 241, 0.1);color:#6366f1;font-weight:600;padding:2px 8px;border-radius:12px;font-size:0.75rem;">' + Number(area.users_count || 0) + '</span></td>';
    row += '<td style="text-align:center;"><span class="badge" style="background:rgba(26, 187, 156, 0.1);color:#1abb9c;font-weight:600;padding:2px 8px;border-radius:12px;font-size:0.75rem;">' + Number(area.docs_count || 0) + '</span></td>';
    row += '<td><span class="' + statusCls + '">' + statusLabel + '</span></td>';
    row += '<td style="text-align:right;"><div class="table-actions" style="justify-content:flex-end;">'
      + '<button class="table-action-btn" title="Editar" aria-label="Editar '
      + esc(area.name) + '" onclick="abrirModalArea(' + area.id + ')">'
      + Icons.pencil + '</button>'
      + '<button class="table-action-btn del" title="Excluir" aria-label="Excluir '
      + esc(area.name) + '" onclick="excluirArea(' + area.id + ', \'' + esc(area.name).replace(/'/g, "\\'") + '\')">'
      + Icons.trash + '</button>'
      + '</div></td></tr>';
    return row;
  }).join('');

  renderPagination('areaPagination', pg.total, pg.current, pg.pages, function (p) {
    PAGINATION.area = p;
    renderAreasTable();
  });
}

function abrirModalArea(id) {
  var titulo = document.getElementById('modalAreaTitulo');
  var fId = document.getElementById('fAreaId');
  var fName = document.getElementById('fAreaName');
  var fDesc = document.getElementById('fAreaDesc');
  var fStatus = document.getElementById('fAreaStatus');

  if (id) {
    var area = AREAS.find(function (x) { return Number(x.id) === Number(id); });
    if (area) {
      if (titulo) titulo.innerHTML = Icons.get('area', { size: 18, color: 'var(--primary)', style: 'margin-right:6px;' }) + '<span>Editar Área / Setor</span>';
      if (fId) fId.value = area.id;
      if (fName) fName.value = area.name || '';
      if (fDesc) fDesc.value = area.description || '';
      if (fStatus) {
        fStatus.value = area.status || 'ativo';
        if (window.SearchableSelect) SearchableSelect.sync(fStatus);
      }
    }
  } else {
    if (titulo) titulo.innerHTML = Icons.get('area', { size: 18, color: 'var(--primary)', style: 'margin-right:6px;' }) + '<span>Nova Área / Setor</span>';
    if (fId) fId.value = '';
    if (fName) fName.value = '';
    if (fDesc) fDesc.value = '';
    if (fStatus) {
      fStatus.value = 'ativo';
      if (window.SearchableSelect) SearchableSelect.sync(fStatus);
    }
  }

  openModal('modalAreaBG');
  setTimeout(function() { if (fName) fName.focus(); }, 100);
}

function fecharModalArea() {
  closeModal('modalAreaBG');
}

function salvarArea() {
  var fId = document.getElementById('fAreaId');
  var fName = document.getElementById('fAreaName');
  var fDesc = document.getElementById('fAreaDesc');
  var fStatus = document.getElementById('fAreaStatus');
  var btn = document.getElementById('btnSalvarArea');

  var id = fId ? fId.value : '';
  var name = fName ? fName.value.trim() : '';
  var description = fDesc ? fDesc.value.trim() : '';
  var status = fStatus ? fStatus.value : 'ativo';

  if (!name) {
    toast('Informe o nome da área / setor.', 'warning');
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

  var promise = id ? api('PUT', '/api/areas/' + id, payload) : api('POST', '/api/areas', payload);

  promise.then(function () {
    toast('Área / Setor ' + (id ? 'atualizada' : 'criada') + ' com sucesso!', 'success');
    fecharModalArea();
    loadAreas();
  }).catch(function (err) {
    var msg = (err && err.message) ? err.message : 'Erro ao salvar área / setor.';
    toast(msg, 'error');
  }).finally(function () {
    if (btn) {
      btn.disabled = false;
      btn.textContent = 'Salvar';
    }
  });
}

function excluirArea(id, name) {
  showConfirm({
    title: 'Excluir Área / Setor?',
    message: 'Deseja realmente excluir a área / setor "' + name + '"?\nUsuários vinculados terão a área desvinculada.',
    confirmText: 'Sim, excluir',
    confirmClass: 'btn btn-danger'
  }, function () {
    api('DELETE', '/api/areas/' + id).then(function () {
      toast('Área / Setor excluída com sucesso.', 'success');
      loadAreas();
    }).catch(function (err) {
      var msg = (err && err.message) ? err.message : 'Erro ao excluir área / setor.';
      toast(msg, 'error');
    });
  });
}

// Exporta funções para o escopo global
window.abrirModalArea = abrirModalArea;
window.fecharModalArea = fecharModalArea;
window.salvarArea = salvarArea;
window.excluirArea = excluirArea;
window.filtrarAreas = filtrarAreas;

document.addEventListener('DOMContentLoaded', function () {
  var tableEl = document.getElementById('areaTable');
  if (tableEl && typeof TableSort !== 'undefined') {
    TableSort.init(tableEl, {
      onSort: function () {
        renderAreasTable();
      }
    });
  }
  loadAreas();

  // Fechar modal ao clicar no fundo
  var modalBG = document.getElementById('modalAreaBG');
  if (modalBG) {
    modalBG.addEventListener('click', function (e) {
      if (e.target === modalBG) {
        fecharModalArea();
      }
    });
  }

  // Tecla Escape para fechar modal
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modalBG && !modalBG.hidden) {
      fecharModalArea();
    }
  });
});
