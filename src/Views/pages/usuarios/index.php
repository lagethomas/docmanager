<div class="page-header-row mb-16">
  <div>
    <h1 class="page-title">Administração de Usuários</h1>
    <p class="page-pretitle" style="margin-top: 4px; text-transform: none; letter-spacing: normal; font-weight: 400; font-size: 13px;">Gerencie os perfis de acesso ao portal. Os administradores principais não podem ser excluídos.</p>
  </div>
  <div class="page-actions">
    <button class="btn btn-primary" onclick="abrirModalUsuario()">＋ Novo Usuário</button>
  </div>
</div>

<div class="card mb-16" style="padding: 0.85rem 1rem;">
  <div style="position: relative; display: flex; align-items: center;">
    <?php echo icon('search', ['style' => 'position: absolute; left: 12px; width: 16px; height: 16px; color: var(--text-muted); pointer-events: none;']); ?>
    <input type="text" id="userSearchInput" class="form-control" placeholder="Buscar por usuário, nome, e-mail, perfil, localização, área..." style="padding-left: 36px; padding-right: 28px; height: 38px; border-radius: 8px;" />
    <button type="button" id="userSearchClear" onclick="clearUserSearch()" hidden style="position: absolute; right: 10px; background: none; border: none; color: var(--text-muted); font-size: 1rem; cursor: pointer;">✕</button>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table" id="userTable">
      <thead>
        <tr>
          <th data-sort="username" class="sortable-th">Usuário <span class="sort-icon"></span></th>
          <th data-sort="name" class="sortable-th">Nome <span class="sort-icon"></span></th>
          <th data-sort="role" class="sortable-th">Perfil <span class="sort-icon"></span></th>
          <th data-sort="location" class="sortable-th">Localização <span class="sort-icon"></span></th>
          <th data-sort="area" class="sortable-th">Área / Setor <span class="sort-icon"></span></th>
          <th data-sort="status" class="sortable-th">Status <span class="sort-icon"></span></th>
          <th class="th-actions">Ações</th>
        </tr>
      </thead>
      <tbody id="adminUserBody"></tbody>
    </table>
  </div>
  <div class="card-footer">
    <div class="pagination-footer" id="userPagination"></div>
  </div>
</div>

<?php require __DIR__ . '/../../components/modal_usuario.php'; ?>