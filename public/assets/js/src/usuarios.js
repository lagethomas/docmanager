/**
 * Módulo Usuários (Administração) — CRUD de usuários, somente Admin.
 */
'use strict';

var USERS = [];
var ROLES = [];
var LOCATIONS = [];
var deleteTarget = { type: null, id: null };

var ICON_PENCIL = Icons.pencil;
var ICON_TRASH = Icons.trash;

function loadUsers() {
  return api('GET', '/api/users').then(function (data) { USERS = data || []; renderAdminUsers(); });
}

function loadRoles() {
  return api('GET', '/api/roles').then(function (data) {
    ROLES = data || [];
    renderRoleOptions();
  });
}

function loadLocations() {
  return api('GET', '/api/locations').then(function (data) {
    LOCATIONS = data || [];
    renderLocationOptions();
  }).catch(function () {});
}

function renderRoleOptions(selectedRole) {
  var select = document.getElementById('fUserRole');
  if (!select) return;
  if (!ROLES.length) {
    select.innerHTML = '<option value="">Nenhum perfil encontrado</option>';
    return;
  }
  select.innerHTML = ROLES.map(function (r) {
    var isSel = (selectedRole && selectedRole === r.name) || (!selectedRole && r.name === 'Admin');
    return '<option value="' + esc(r.name) + '"' + (isSel ? ' selected' : '') + '>' + esc(r.name) + '</option>';
  }).join('');
}

function renderLocationOptions(selectedLocId) {
  var select = document.getElementById('fUserLocation');
  if (!select) return;
  var selId = selectedLocId ? Number(selectedLocId) : null;
  var html = '<option value="">Nenhuma / Sem restrição</option>';
  html += LOCATIONS.map(function (loc) {
    var isSel = selId === Number(loc.id);
    return '<option value="' + loc.id + '"' + (isSel ? ' selected' : '') + '>' + esc(loc.name) + '</option>';
  }).join('');
  select.innerHTML = html;
}

function renderAdminUsers() {
  var sortState = TableSort.getSort('userTable');
  var users = USERS.slice();

  if (sortState.col) {
    users = TableSort.sortArray(users, sortState.col, sortState.dir, function(u, col) {
      if (col === 'username') return u.login;
      if (col === 'name') return u.name;
      if (col === 'role') return u.role;
      if (col === 'location') return u.location_name || '';
      if (col === 'status') return u.status;
      return u.login;
    });
  }

  var logins = users.map(function (u) { return u.login; });
  var pg = paginated('usr', logins);
  document.getElementById('adminUserBody').innerHTML = pg.total ? pg.page.map(function (login) {
    var u = USERS.find(function (x) { return x.login === login; });
    var roleLabel = '<span class="kanban-label kanban-label-purple">' + esc(u.role) + '</span>';
    var statusCls = u.status === 'ativo' ? 'status status-green' : 'status status-red';
    var statusLabel = u.status === 'ativo' ? 'Ativo' : 'Inativo';
    var locBadge = u.location_name
      ? '<span class="badge" style="background:rgba(26,187,156,0.1);color:#1abb9c;font-weight:600;padding:3px 8px;border-radius:6px;font-size:0.75rem;display:inline-flex;align-items:center;gap:4px;">' + Icons.get('location', { size: 11 }) + esc(u.location_name) + '</span>'
      : '<span style="opacity:0.4;font-size:0.8rem;">—</span>';

    var row = '<tr data-id="' + esc(login) + '">';
    row += '<td class="cell-mono">' + esc(login) + '</td>';
    row += '<td class="cell-strong">' + esc(u.name) + '</td>';
    row += '<td>' + roleLabel + '</td>';
    row += '<td>' + locBadge + '</td>';
    row += '<td><span class="' + statusCls + '">' + statusLabel + '</span></td>';
    row += '<td><div class="table-actions">'
      + '<button class="table-action-btn" title="Editar" aria-label="Editar '
      + esc(login) + '" onclick="abrirModalUsuario(\'' + esc(login) + '\')">'
      + Icons.pencil + '</button>';
    if (login !== 'admin') {
      row += '<button class="table-action-btn del" title="Excluir" aria-label="Excluir '
      + esc(login) + '" onclick="confirmDeleteUser(\'' + esc(login) + '\')">'
      + Icons.trash + '</button>';
    }
    row += '</div></td></tr>';
    return row;
  }).join('') : '<tr><td colspan="6">'
    + '<div class="empty-state"><div class="empty-state-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div>'
    + '<div class="empty-state-title">Nenhum usuário encontrado</div>'
    + '</div></td></tr>';
  renderPagination('userPagination', pg.total, pg.current, pg.pages, function (p) {
    PAGINATION.usr = p;
    renderAdminUsers();
  });
  highlightFromUrl('#adminUserBody tr', 'id');
}

/* ------------------------------------------------------------------ Modal de usuário */

function abrirModalUsuario(login) {
  var isEdit = !!login;
  var user = isEdit ? USERS.find(function (u) { return u.login === login; }) : null;
  var isAdminPrincipal = login === 'admin';

  document.getElementById('modalUserTitulo').textContent = isEdit ? 'Editar Usuário' : 'Novo Usuário';
  document.getElementById('uId').value = login || '';
  document.getElementById('fUserLogin').value = login || '';
  document.getElementById('fUserLogin').disabled = isEdit;
  document.getElementById('fUserNome').value = isEdit ? user.name : '';
  document.getElementById('fUserPass').value = '';
  renderRoleOptions(isEdit ? user.role : (ROLES[0] ? ROLES[0].name : 'Admin'));
  renderLocationOptions(isEdit ? user.location_id : null);
  document.getElementById('fUserRole').disabled = isAdminPrincipal;
  document.getElementById('fUserStatus').value = isEdit ? user.status : 'ativo';
  document.getElementById('fUserStatus').disabled = isAdminPrincipal;
  openModal('modalUserBG');
  setTimeout(function () {
    if (isEdit) {
      document.getElementById('fUserNome').focus();
    } else {
      document.getElementById('fUserLogin').focus();
    }
  }, 50);
}

function fecharModalUsuario() { closeModal('modalUserBG'); }

function salvarUsuario() {
  var isEdit = !!document.getElementById('uId').value;
  var login = document.getElementById('fUserLogin').value.trim().toLowerCase();
  var nome = document.getElementById('fUserNome').value.trim();
  var pass = document.getElementById('fUserPass').value.trim();
  var isAdminPrincipal = login === 'admin';
  var role = isAdminPrincipal ? 'Admin' : document.getElementById('fUserRole').value;
  var status = isAdminPrincipal ? 'ativo' : document.getElementById('fUserStatus').value;
  var locVal = document.getElementById('fUserLocation') ? document.getElementById('fUserLocation').value : '';
  var locationId = locVal ? parseInt(locVal, 10) : null;
  var btn = document.getElementById('btnSalvarUser');

  if (!login || !nome || (!isEdit && !pass)) {
    toast('Preencha usuário, nome e senha.', 'error');
    if (!login) document.getElementById('fUserLogin').focus();
    else if (!nome) document.getElementById('fUserNome').focus();
    else if (!pass) document.getElementById('fUserPass').focus();
    return;
  }

  var payload = { name: nome, password: pass, role: role, status: status, location_id: locationId };
  var req = isEdit
    ? api('PUT', '/api/users/' + encodeURIComponent(login), payload)
    : api('POST', '/api/users', Object.assign({ login: login }, payload));

  if (btn) {
    btn.disabled = true;
    btn.textContent = 'Salvando...';
  }

  req.then(function () {
    fecharModalUsuario();
    return loadUsers();
  }).then(function () {
    toast('Usuário ' + (isEdit ? 'atualizado' : 'criado') + ' com sucesso!');
  }).catch(function (e) {
    toast(e.message, 'error');
  }).finally(function () {
    if (btn) {
      btn.disabled = false;
      btn.textContent = 'Salvar';
    }
  });
}

/* ------------------------------------------------------------------ Exclusão de usuário */

function confirmDeleteUser(login) {
  deleteTarget = { type: 'user', id: login };
  openModal('confirmBG');
}

function closeConfirm() { closeModal('confirmBG'); }

function confirmDelete() {
  if (deleteTarget.type === 'user') {
    var btn = document.getElementById('btnConfirmDelete');
    if (btn) {
      btn.disabled = true;
      btn.textContent = 'Excluindo...';
    }
    api('DELETE', '/api/users/' + encodeURIComponent(deleteTarget.id))
      .then(function () {
        closeConfirm();
        return loadUsers();
      })
      .then(function () { toast('Usuário excluído.'); })
      .catch(function (e) { toast(e.message, 'error'); })
      .finally(function () {
        if (btn) {
          btn.disabled = false;
          btn.textContent = 'Sim, excluir';
        }
      });
  }
}

document.addEventListener('DOMContentLoaded', function () {
  loadRoles().catch(function () {});
  loadLocations().catch(function () {});
  loadUsers().catch(function (e) { toast(e.message, 'error'); });

  var userTable = document.getElementById('userTable');
  if (userTable) {
    TableSort.init(userTable, {
      onSort: function() { renderAdminUsers(); }
    });
  }
});