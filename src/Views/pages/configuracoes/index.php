<div class="page-header-row mb-16">
  <h2 class="page-subtitle" id="pageSubtitle" style="margin:0">Configurações do Sistema</h2>
</div>

<form id="settingsForm" autocomplete="off">
  <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:1.25rem">
    
    <!-- Card Geral -->
    <div class="card" style="margin:0">
      <div class="card-header"><h3 class="card-title">⚙️ Geral & Exibição</h3></div>
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
      <div class="card-header"><h3 class="card-title">📄 Gestão de Documentos</h3></div>
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

