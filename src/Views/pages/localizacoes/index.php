<div class="page-header-row mb-16">
  <div>
    <h1 class="page-title">Gestão de Localizações</h1>
    <p class="page-pretitle" style="margin-top: 4px; text-transform: none; letter-spacing: normal; font-weight: 400; font-size: 13px;">Cadastre unidades, filiais e departamentos para controlar a restrição geográfica de documentos.</p>
  </div>
  <div class="page-actions" style="display: flex; gap: 0.75rem; align-items: center;">
    <div style="position: relative; width: 220px;">
      <input type="text" id="locationSearchInput" class="form-control" placeholder="Buscar localização..." style="padding-left: 28px; height: 36px; font-size: 0.85rem;" oninput="filtrarLocalizacoes()" />
      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; left: 9px; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    </div>
    <button class="btn btn-primary" onclick="abrirModalLocalizacao()">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Nova Localização
    </button>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table" id="locationTable">
      <thead>
        <tr>
          <th data-sort="name" class="sortable-th">Nome da Unidade / Filial <span class="sort-icon"></span></th>
          <th data-sort="description" class="sortable-th">Descrição <span class="sort-icon"></span></th>
          <th data-sort="users_count" class="sortable-th" style="text-align: center;">Usuários <span class="sort-icon"></span></th>
          <th data-sort="docs_count" class="sortable-th" style="text-align: center;">Documentos <span class="sort-icon"></span></th>
          <th data-sort="status" class="sortable-th">Status <span class="sort-icon"></span></th>
          <th class="th-actions" style="text-align: right;">Ações</th>
        </tr>
      </thead>
      <tbody id="locationTableBody">
        <tr>
          <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">
            Carregando localizações...
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  <div class="card-footer">
    <div class="pagination-footer" id="locationPagination"></div>
  </div>
</div>

<!-- ===== Modal: Localização ===== -->
<div class="modal-backdrop" id="modalLocationBG" hidden>
  <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modalLocationTitulo">
    <div class="modal-header">
      <h3 class="modal-title" id="modalLocationTitulo" style="display: flex; align-items: center; gap: 0.5rem;">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary);"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        <span>Nova Localização</span>
      </h3>
      <button class="modal-close" onclick="fecharModalLocalizacao()" title="Fechar" aria-label="Fechar modal">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="fLocId" />
      <div class="form-group">
        <label class="form-label" for="fLocName">Nome da Localização / Unidade <span class="required">*</span></label>
        <input type="text" id="fLocName" class="form-control" placeholder="Ex: Matriz São Paulo, Filial Curitiba, Unidade Centro..." />
      </div>
      <div class="form-group">
        <label class="form-label" for="fLocDesc">Descrição / Observações</label>
        <textarea id="fLocDesc" class="form-control" rows="3" placeholder="Informações adicionais, endereço ou setor correspondente..."></textarea>
      </div>
      <div class="form-group">
        <label class="form-label" for="fLocStatus">Status</label>
        <select id="fLocStatus" class="form-control">
          <option value="ativo">Ativo</option>
          <option value="inativo">Inativo</option>
        </select>
        <div class="form-hint">Localizações inativas não aparecem na seleção de novos documentos ou usuários.</div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="fecharModalLocalizacao()">Cancelar</button>
      <button class="btn btn-primary" id="btnSalvarLoc" onclick="salvarLocalizacao()">Salvar</button>
    </div>
  </div>
</div>
