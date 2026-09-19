<div class="page-header-row mb-16">
  <h2 class="page-subtitle" id="pageSubtitle" style="margin:0">Configurações do Sistema</h2>
</div>

<form id="settingsForm" autocomplete="off">
  <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:1.25rem">
    
    <!-- Card Geral -->
    <div class="card" style="margin:0">
      <div class="card-header">
        <h3 class="card-title" style="display: flex; align-items: center; gap: 0.5rem;">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 18px; height: 18px; color: var(--primary);"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          <span>Geral & Exibição</span>
        </h3>
      </div>
      <div class="card-body">
        <div class="form-group">
          <label class="form-label" for="perPage">Itens por página</label>
          <select id="perPage" class="form-control" name="per_page">
            <option value="10">10 itens</option>
            <option value="15">15 itens</option>
            <option value="20">20 itens</option>
            <option value="25">25 itens</option>
            <option value="50">50 itens</option>
          </select>
          <div class="form-hint">Quantidade de registros exibidos por página nas tabelas de paginação.</div>
        </div>
      </div>
    </div>

    <!-- Card Documentos & Versões -->
    <div class="card" style="margin:0">
      <div class="card-header">
        <h3 class="card-title" style="display: flex; align-items: center; gap: 0.5rem;">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 18px; height: 18px; color: var(--primary);"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
          <span>Gestão de Documentos</span>
        </h3>
      </div>
      <div class="card-body">
        <div class="form-group">
          <label class="form-label" for="docMaxVersions">Limite de versões no histórico</label>
          <select id="docMaxVersions" class="form-control" name="doc_max_versions">
            <option value="5">5 versões anteriores</option>
            <option value="10">10 versões anteriores (Padrão)</option>
            <option value="15">15 versões anteriores</option>
            <option value="20">20 versões anteriores</option>
            <option value="30">30 versões anteriores</option>
            <option value="50">50 versões anteriores</option>
          </select>
          <div class="form-hint">Define a quantidade máxima de versões históricas mantidas por documento antes de podar as mais antigas.</div>
        </div>
      </div>
    </div>

  </div>

  <div class="form-actions" style="margin-top:1.25rem">
    <button type="submit" class="btn btn-primary" id="saveBtn">Salvar Alterações</button>
  </div>
</form>

