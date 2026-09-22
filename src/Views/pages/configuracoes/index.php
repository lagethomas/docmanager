<div class="page-header-row mb-16">
  <h2 class="page-subtitle" id="pageSubtitle" style="margin:0">Configurações do Sistema</h2>
</div>

<!-- NAVEGAÇÃO DE ABAS DE CONFIGURAÇÕES -->
<div class="settings-tabs-bar" style="display:flex;gap:0.5rem;margin-bottom:1.25rem;border-bottom:2px solid var(--border-color, #e2e8f0);">
  <button type="button" class="btn settings-tab-btn active" id="tabBtnGeral" onclick="SettingsApp.switchTab('geral')" style="padding:0.6rem 1.2rem;font-weight:700;border-radius:8px 8px 0 0;border-bottom:3px solid var(--primary, #1abb9c);background:none;color:var(--primary, #1abb9c);">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px;vertical-align:-2px;"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
    Geral & Documentos
  </button>
  <button type="button" class="btn settings-tab-btn" id="tabBtnTimbrado" onclick="SettingsApp.switchTab('timbrado')" style="padding:0.6rem 1.2rem;font-weight:600;border-radius:8px 8px 0 0;border-bottom:3px solid transparent;background:none;color:var(--text-muted, #64748b);">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px;vertical-align:-2px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    Timbrado
  </button>
</div>

<form id="settingsForm" autocomplete="off">
  <!-- ABA 1: GERAL & GESTÃO DE DOCUMENTOS -->
  <div id="tabContentGeral" class="settings-tab-pane">
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
  </div>

  <!-- ABA 2: TIMBRADO (WORD OU IMAGENS DIRETA DE HEADER E FOOTER) -->
  <div id="tabContentTimbrado" class="settings-tab-pane" hidden>
    <div class="card" style="margin:0">
      <div class="card-header">
        <h3 class="card-title" style="display: flex; align-items: center; gap: 0.5rem;">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px; color: var(--primary);"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          <span>Configuração do Papel Timbrado</span>
        </h3>
      </div>
      <div class="card-body">
        <p style="font-size:0.88rem;color:var(--text-muted);margin-bottom:1.25rem;">
          Você pode configurar o papel timbrado enviando um modelo em formato Word (<code>.docx</code>), que extrai automaticamente o cabeçalho e rodapé, ou enviando diretamente as imagens do <strong>Cabeçalho (Header)</strong> e <strong>Rodapé (Footer)</strong>. O timbrado preencherá 100% da folha na exportação/impressão em PDF.
        </p>

        <!-- Toggle de Ativação do Timbrado -->
        <div class="form-group" style="background:var(--bg-card);border:1px solid var(--border-color);padding:1rem;border-radius:10px;margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;">
          <div>
            <label style="font-weight:700;font-size:0.95rem;display:block;margin-bottom:2px;">Ativar Papel Timbrado na Impressão/PDF</label>
            <span style="font-size:0.8rem;color:var(--text-muted);">Quando ativo, o timbrado é aplicado a 100% sem margens na folha em todas as impressões e exportações PDF.</span>
          </div>
          <label class="toggle-switch">
            <input type="checkbox" id="chkTimbradoEnabled" name="timbrado_enabled" value="1" />
            <span class="toggle-slider"></span>
          </label>
        </div>

        <!-- Painel do Modelo Ativo -->
        <div id="timbradoActiveCard" style="background:rgba(26, 187, 156, 0.08);border:1px solid rgba(26, 187, 156, 0.3);border-radius:10px;padding:1.25rem;margin-bottom:1.5rem;" hidden>
          <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
            <div>
              <div style="font-size:0.75rem;font-weight:700;color:var(--primary);text-transform:uppercase;margin-bottom:4px;">✓ PAPEL TIMBRADO ATIVO</div>
              <div id="lblTimbradoFilename" style="font-weight:700;font-size:1rem;color:var(--text-color);">modelo_timbrado.docx</div>
              <div id="lblTimbradoUpdatedAt" style="font-size:0.78rem;color:var(--text-muted);margin-top:2px;">Atualizado em --/--/----</div>
            </div>
            <div style="display:flex;gap:0.5rem;">
              <a href="/api/settings/timbrado/download" class="btn btn-sm btn-outline" id="btnDownloadWord" target="_blank" title="Baixar arquivo de modelo Word cadastrado">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Baixar Word
              </a>
              <button type="button" class="btn btn-sm btn-danger-outline" onclick="SettingsApp.deleteTimbrado()" title="Remover papel timbrado">
                Excluir
              </button>
            </div>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-top:1rem;">
          <!-- Opção 1: Upload por Modelo Word -->
          <div style="background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;padding:1.25rem;">
            <h4 style="font-size:0.95rem;font-weight:700;margin-top:0;margin-bottom:0.5rem;display:flex;align-items:center;gap:0.5rem;">
              <span>📄 Opção 1: Upload de Modelo Word (.docx)</span>
            </h4>
            <p style="font-size:0.8rem;color:var(--text-muted);margin-bottom:1rem;">
              Envie um arquivo Word com o papel timbrado para extração automática das imagens de cabeçalho e rodapé.
            </p>
            <div class="upload-dropzone" id="timbradoDropzone" style="border:2px dashed var(--border-color);border-radius:10px;padding:1.5rem;text-align:center;cursor:pointer;background:var(--bg-card);transition:all 0.2s ease;">
              <div style="font-size:2rem;margin-bottom:0.3rem;color:var(--primary);">📤</div>
              <h5 style="font-size:0.88rem;font-weight:700;margin:0 0 0.3rem;">Arraste ou clique para selecionar (.docx)</h5>
              <input type="file" id="timbradoFileInput" accept=".docx,.doc" hidden />
            </div>
          </div>

          <!-- Opção 2: Upload Direto por Imagens de Cabeçalho e Rodapé -->
          <div style="background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;padding:1.25rem;">
            <h4 style="font-size:0.95rem;font-weight:700;margin-top:0;margin-bottom:0.5rem;display:flex;align-items:center;gap:0.5rem;">
              <span>🖼️ Opção 2: Upload das Imagens (Header & Footer)</span>
            </h4>
            <p style="font-size:0.8rem;color:var(--text-muted);margin-bottom:1rem;">
              Envie diretamente as imagens PNG ou JPG do Cabeçalho (topo) e do Rodapé (base).
            </p>

            <div style="display:flex;flex-direction:column;gap:0.85rem;">
              <!-- Dropzone Header Image -->
              <div>
                <label style="font-size:0.82rem;font-weight:700;display:block;margin-bottom:4px;color:var(--text-color);">Imagem do Cabeçalho (Header)</label>
                <div class="upload-dropzone" id="dropzoneHeaderImage" style="border:2px dashed var(--border-color);border-radius:10px;padding:0.85rem 1rem;text-align:center;cursor:pointer;background:var(--bg-card);transition:all 0.2s ease;display:flex;align-items:center;justify-content:space-between;gap:0.75rem;">
                  <div style="display:flex;align-items:center;gap:0.6rem;overflow:hidden;">
                    <div style="font-size:1.4rem;color:var(--primary);line-height:1;">🖼️</div>
                    <div style="text-align:left;overflow:hidden;">
                      <div id="lblHeaderImageName" style="font-size:0.82rem;font-weight:700;color:var(--text-color);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Clique para selecionar imagem de topo</div>
                      <div style="font-size:0.72rem;color:var(--text-muted);">PNG, JPG, WEBP ou SVG</div>
                    </div>
                  </div>
                  <button type="button" class="btn btn-sm btn-outline" style="font-size:0.75rem;padding:0.3rem 0.65rem;flex-shrink:0;">Escolher</button>
                  <input type="file" id="timbradoHeaderImageInput" accept="image/png,image/jpeg,image/webp,image/svg+xml" hidden />
                </div>
              </div>

              <!-- Dropzone Footer Image -->
              <div>
                <label style="font-size:0.82rem;font-weight:700;display:block;margin-bottom:4px;color:var(--text-color);">Imagem do Rodapé (Footer)</label>
                <div class="upload-dropzone" id="dropzoneFooterImage" style="border:2px dashed var(--border-color);border-radius:10px;padding:0.85rem 1rem;text-align:center;cursor:pointer;background:var(--bg-card);transition:all 0.2s ease;display:flex;align-items:center;justify-content:space-between;gap:0.75rem;">
                  <div style="display:flex;align-items:center;gap:0.6rem;overflow:hidden;">
                    <div style="font-size:1.4rem;color:var(--primary);line-height:1;">🖼️</div>
                    <div style="text-align:left;overflow:hidden;">
                      <div id="lblFooterImageName" style="font-size:0.82rem;font-weight:700;color:var(--text-color);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Clique para selecionar imagem de rodapé</div>
                      <div style="font-size:0.72rem;color:var(--text-muted);">PNG, JPG, WEBP ou SVG</div>
                    </div>
                  </div>
                  <button type="button" class="btn btn-sm btn-outline" style="font-size:0.75rem;padding:0.3rem 0.65rem;flex-shrink:0;">Escolher</button>
                  <input type="file" id="timbradoFooterImageInput" accept="image/png,image/jpeg,image/webp,image/svg+xml" hidden />
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <div class="form-actions" style="margin-top:1.25rem">
    <button type="submit" class="btn btn-primary" id="saveBtn">Salvar Alterações</button>
  </div>
</form>
