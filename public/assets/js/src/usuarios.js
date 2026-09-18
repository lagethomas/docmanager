/**
 * Módulo Usuários (Administração) — CRUD de usuários, somente Admin.
 */
'use strict';

var USERS = [];
var ROLES = [];
var deleteTarget = { type: null, id: null };

var ICON_PENCIL = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>';
var ICON_TRASH = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>';

function loadUsers() {
  return api('GET', '/api/users').then(function (data) { USERS = data || []; renderAdminUsers(); });
}

function loadRoles() {
  return api('GET', '/api/roles').then(function (data) {
    ROLES = data || [];
    renderRoleOptions();
  });
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

function renderAdminUsers() {
  var sortState = TableSort.getSort('userTable');
  var users = USERS.slice();

  if (sortState.col) {
    users = TableSort.sortArray(users, sortState.col, sortState.dir, function(u, col) {
      if (col === 'username') return u.login;
      if (col === 'name') return u.name;
      if (col === 'role') return u.role;
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
    var row = '<tr data-id="' + esc(login) + '">';
    row += '<td class="cell-mono">' + esc(login) + '</td>';
    row += '<td class="cell-strong">' + esc(u.name) + '</td>';
    row += '<td>' + roleLabel + '</td>';
    row += '<td><span class="' + statusCls + '">' + statusLabel + '</span></td>';
    row += '<td><div class="table-actions">'
      + '<button class="table-action-btn" title="Editar" aria-label="Editar '
      + esc(login) + '" onclick="abrirModalUsuario(\'' + esc(login) + '\')">'
      + ICON_PENCIL + '</button>';
    if (login !== 'admin') {
      row += '<button class="table-action-btn del" title="Excluir" aria-label="Excluir '
        + esc(login) + '" onclick="confirmDeleteUser(\'' + esc(login) + '\')">'
        + ICON_TRASH + '</button>';
    }
    row += '</div></td></tr>';
    return row;
  }).join('') : '<tr><td colspan="5">'
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
  var btn = document.getElementById('btnSalvarUser');

  if (!login || !nome || (!isEdit && !pass)) {
    toast('Preencha usuário, nome e senha.', 'error');
    if (!login) document.getElementById('fUserLogin').focus();
    else if (!nome) document.getElementById('fUserNome').focus();
    else if (!pass) document.getElementById('fUserPass').focus();
    return;
  }

  var payload = { name: nome, password: pass, role: role, status: status };
  var req = isEdit
    ? api('PUT', '/api/users/' + encodeURIComponent(login), payload)
    : api('POST', '/api/users', { login: login, name: nome, password: pass, role: role, status: status });

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
  loadUsers().catch(function (e) { toast(e.message, 'error'); });

  var userTable = document.getElementById('userTable');
  if (userTable) {
    TableSort.init(userTable, {
      onSort: function() { renderAdminUsers(); }
    });
  }
});