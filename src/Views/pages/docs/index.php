<?php
declare(strict_types=1);

use App\Core\Controller;
/**
 * @var string $csrfToken
 * @var array<string, mixed> $user
 */
$csrfToken = $csrfToken ?? '';
$user = $user ?? [];
?>

<div class="docs-app" id="docsApp">

  <!-- ─── TOP TOOLBAR ─────────────────────────────────── -->
  <header class="docs-header">
    <div class="docs-header-left">
      <div class="docs-header-title-wrap">
        <div class="docs-header-icon" id="currentViewIcon">
          <?php echo icon('document', ['size' => 20]); ?>
        </div>
        <div>
          <h1 class="docs-header-title" id="listTitle">Todos os Documentos</h1>
          <div class="docs-header-subtitle" id="listSubtitle">Carregando acervo...</div>
        </div>
      </div>
    </div>

    <div class="docs-header-center">
      <div class="docs-search-wrapper">
        <?php echo icon('search', ['class' => 'docs-search-icon', 'size' => 16]); ?>
        <input type="text" id="docsSearch" class="docs-search-input" placeholder="Buscar por título, conteúdo ou tag..." autocomplete="off" />
        <span class="docs-search-shortcut" title="Atalho de busca">/</span >
        <button type="button" class="docs-search-clear" id="docsSearchClear" hidden title="Limpar busca">×</button>
      </div>
    </div>

    <div class="docs-header-right">
      <!-- Alternador de visualização (Cards / Tabela) -->
      <div class="docs-view-switcher" role="group" aria-label="Modo de visualização">
        <button type="button" class="view-btn" id="btnViewGrid" onclick="Docs.setViewMode('grid')" title="Visualização em Grade">
          <?php echo icon('grid', ['size' => 16]); ?>
        </button>
        <button type="button" class="view-btn active" id="btnViewList" onclick="Docs.setViewMode('list')" title="Visualização em Lista / Tabela">
          <?php echo icon('list', ['size' => 16]); ?>
        </button>
      </div>

      <!-- Seletor de Ordenação -->
      <select id="docsSort" class="docs-sort-select no-searchable" onchange="Docs.handleSortChange(this.value)" title="Ordenar documentos">
        <option value="updated_desc">Recentes primeiro</option>
        <option value="updated_asc">Antigos primeiro</option>
        <option value="title_asc">Título (A-Z)</option>
        <option value="title_desc">Título (Z-A)</option>
        <option value="versions_desc">Mais editados</option>
      </select>

      <button type="button" class="btn btn-secondary btn-sm" id="btnHeaderNewFolder" onclick="Docs.openCatModal()" title="Criar nova pasta de organização">
        <?php echo icon('folderPlus', ['class' => 'btn-svg', 'size' => 14]); ?>
        <span>Nova Pasta</span>
      </button>

      <button type="button" class="btn btn-primary btn-sm" id="btnHeaderNewDoc" onclick="Docs.newDoc()" title="Criar novo documento (Atalho: N)">
        <?php echo icon('plus', ['class' => 'btn-svg', 'size' => 14]); ?>
        <span>Novo Documento</span>
      </button>
    </div>
  </header>

  <!-- ─── LAYOUT CORPO (SIDEBAR + MAIN) ─────────────── -->
  <div class="docs-body">

    <!-- Sidebar de Pastas & Tags -->
    <aside class="docs-nav-sidebar" id="docsSidebar">
      <div class="sidebar-section sidebar-nav-section">
        <div class="sidebar-section-header">
          <span>NAVEGAÇÃO</span>
          <button type="button" class="sidebar-action-btn" onclick="Docs.openCatModal()" title="Adicionar pasta">+</button>
        </div>

        <div class="folder-nav-list" id="folderTree">
          <!-- Renderizado via JS -->
        </div>
      </div>

      <div class="sidebar-section sidebar-tags-section" id="sidebarTagsSection">
        <div class="sidebar-section-header">
          <span>TAGS EM ALTA</span>
        </div>
        <div class="tag-cloud" id="tagCloud">
          <!-- Tags renderizadas via JS -->
        </div>
      </div>

      <div class="sidebar-stats-card" id="sidebarStatsCard">
        <div class="stats-item">
          <span class="stats-num" id="statTotalDocs">0</span>
          <span class="stats-label">Documentos</span>
        </div>
        <div class="stats-divider"></div>
        <div class="stats-item">
          <span class="stats-num" id="statTotalCats">0</span>
          <span class="stats-label">Pastas</span>
        </div>
        <div class="stats-divider"></div>
        <div class="stats-item">
          <span class="stats-num" id="statTotalTags">0</span>
          <span class="stats-label">Tags</span>
        </div>
      </div>
    </aside>

    <!-- Área Principal -->
    <main class="docs-content-area" id="docsContentArea">
      <!-- Tag Filter Indicator (quando ativo) -->
      <div class="active-filter-bar" id="activeFilterBar" hidden>
        <div class="active-filter-info">
          <span>Filtrando por:</span>
          <div class="active-filter-chips" id="activeFilterChips"></div>
        </div>
        <button type="button" class="btn-clear-filter" onclick="Docs.clearAllTagFilters()">Limpar filtros ✕</button>
      </div>

      <!-- Banner Informativo da Lixeira -->
      <div class="docs-trash-banner" id="docsTrashBanner" hidden>
        <div class="trash-banner-info">
          <span class="trash-banner-icon"><?php echo icon('trash', ['size' => 20]); ?></span>
          <div>
            <strong>Lixeira de Documentos</strong>
            <p>Itens na lixeira podem ser restaurados ou permanentemente excluídos.</p>
          </div>
        </div>
        <div class="trash-banner-actions">
          <button type="button" class="btn btn-sm btn-danger" onclick="Docs.emptyTrashModal()">
            <?php echo icon('trash', ['class' => 'btn-svg', 'size' => 14]); ?>
            <span>Esvaziar Lixeira</span>
          </button>
        </div>
      </div>

      <!-- Container de Grade (Cards) -->
      <div class="docs-grid-container" id="docsGrid" hidden>
        <!-- Renderizado dinamicamente -->
      </div>

      <!-- Container de Lista / Tabela -->
      <div class="card" id="docsTableWrapper">
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th style="width: 40px"></th>
                <th data-sort="title" class="sortable-th">Título <span class="sort-icon"></span></th>
                <th data-sort="category" class="sortable-th">Pasta <span class="sort-icon"></span></th>
                <th data-sort="tags" class="sortable-th">Tags <span class="sort-icon"></span></th>
                <th data-sort="author" class="sortable-th">Autor <span class="sort-icon"></span></th>
                <th data-sort="versions" class="sortable-th">Versões <span class="sort-icon"></span></th>
                <th data-sort="updated" class="sortable-th">Atualização <span class="sort-icon"></span></th>
                <th class="th-actions">Ações</th>
              </tr>
            </thead>
            <tbody id="docsTableBody">
              <!-- Linhas renderizadas dinamicamente -->
            </tbody>
          </table>
        </div>
        <div class="card-footer">
          <div class="docs-pagination" id="docsTablePagination"></div>
        </div>
      </div>

      <!-- Estado Vazio -->
      <div class="docs-empty-state" id="docsEmptyState" hidden>
        <div class="empty-state-icon">
          <?php echo icon('document', ['size' => 48]); ?>
        </div>
        <h3 class="empty-state-title" id="emptyTitle">Nenhum documento encontrado</h3>
        <p class="empty-state-desc" id="emptyDesc">Crie um novo documento ou selecione outra pasta no menu lateral.</p>
        <button type="button" class="btn btn-primary" id="emptyNewDocBtn" onclick="Docs.newDoc()">Criar Primeiro Documento</button>
      </div>

    </main>
  </div>
</div>

<!-- ─── DOCEDITOR PRO (MODAL FULLSCREEN DE EDIÇÃO & SPLIT) ───────────── -->
<div class="doceditor-overlay" id="editorOverlay" hidden>
  <div class="doceditor-container">

    <!-- Topbar do Editor -->
    <header class="doceditor-topbar">
      <div class="doceditor-topbar-left">
        <button type="button" class="doceditor-btn-back" onclick="Docs.closeEditor()" title="Voltar (Esc)">
          <?php echo icon('arrowLeft', ['size' => 16]); ?>
          <span>Fechar</span>
        </button>
        <div class="doceditor-divider"></div>
        <input type="text" id="docTitle" class="doceditor-title-input" placeholder="Título do documento..." autocomplete="off" />
      </div>

      <div class="doceditor-actions-wrapper">
        <button type="button" class="doceditor-scroll-arrow left" onclick="Docs.scrollActionsLeft()" title="Rolar opções para a esquerda">&#8249;</button>

        <div class="doceditor-topbar-right" id="doceditorActionsScroll">
          <div class="doceditor-cat-select-wrap">
            <?php echo icon('folder', ['class' => 'select-icon', 'size' => 15]); ?>
            <select id="docCategory" class="doceditor-select" title="Pasta do Documento"></select>
          </div>

          <!-- Dropdown Agendamento & Validade -->
          <div id="docSchedulePicker" class="doceditor-dropdown-wrap">
            <button type="button" class="btn btn-sm btn-outline doceditor-btn-schedule" id="btnDocSchedule" onclick="Docs.toggleScheduleDropdown()" title="Agendar publicação e data de validade/expiração">
              <?php echo icon('clock', ['size' => 13]); ?>
              <span id="docScheduleLabel">Agendamento &amp; Validade</span>
            </button>
            <div id="docScheduleDropdown" class="doceditor-dropdown-menu doceditor-schedule-dropdown" hidden>
              <div class="doceditor-dropdown-header-title">
                <span>AGENDAMENTO &amp; VALIDADE</span>
              </div>
              <div class="doceditor-form-group">
                <label class="doceditor-field-label">Agendar Publicação</label>
                <input type="datetime-local" id="docPublishAt" class="form-control form-control-sm doceditor-field-input" />
                <span class="doceditor-field-help">O documento ficará agendado até esta data.</span>
              </div>
              <div>
                <label class="doceditor-field-label">Data de Validade / Revisão</label>
                <input type="datetime-local" id="docExpiresAt" class="form-control form-control-sm doceditor-field-input" />
                <span class="doceditor-field-help">Após a data, o documento é marcado para revisão.</span>
              </div>
            </div>
          </div>

          <label class="toggle-switch" title="Tornar documento livremente visível na Biblioteca sem login">
            <input type="checkbox" id="chkDocIsPublic" />
            <span class="toggle-slider"></span>
            <span class="doceditor-toggle-label"><?php echo icon('globe', ['size' => 13]); ?> Público</span>
          </label>

          <label class="toggle-switch" title="Restringir acesso por localização/unidade">
            <input type="checkbox" id="chkDocIsRestricted" onchange="Docs.toggleRestricted(this.checked)" />
            <span class="toggle-slider"></span>
            <span class="doceditor-toggle-label"><?php echo icon('location', ['size' => 13]); ?> Restringir</span>
          </label>

          <div id="docLocationPicker" class="doceditor-dropdown-wrap" hidden>
            <button type="button" class="btn btn-sm btn-outline doceditor-btn-locations" id="btnDocLocations" onclick="Docs.toggleLocationDropdown()" title="Selecionar localizações permitidas">
              <?php echo icon('location', ['size' => 13]); ?>
              <span id="docLocationsLabel">Localizações (0)</span>
            </button>
            <div id="docLocationsDropdown" class="doceditor-dropdown-menu doceditor-picker-dropdown" hidden>
              <div class="doceditor-picker-header">
                <span>LOCALIZAÇÕES AUTORIZADAS</span>
                <span id="docLocationsCountBadge" class="badge-location-count"></span>
              </div>
              <div class="doceditor-picker-search-wrap">
                <?php echo icon('search', ['size' => 13, 'class' => 'doceditor-picker-search-icon']); ?>
                <input type="text" id="docLocationSearchInput" placeholder="Buscar localização..." autocomplete="off" class="doceditor-picker-search-input" oninput="Docs.filterLocations(this.value)" />
              </div>
              <div id="docLocationsList" class="doceditor-picker-list"></div>
            </div>
          </div>

          <div id="docAreaPicker" class="doceditor-dropdown-wrap" hidden>
            <button type="button" class="btn btn-sm btn-outline doceditor-btn-areas" id="btnDocAreas" onclick="Docs.toggleAreaDropdown()" title="Selecionar áreas/setores permitidos">
              <?php echo icon('area', ['size' => 13]); ?>
              <span id="docAreasLabel">Áreas (0)</span>
            </button>
            <div id="docAreasDropdown" class="doceditor-dropdown-menu doceditor-picker-dropdown" hidden>
              <div class="doceditor-picker-header">
                <span>ÁREAS AUTORIZADAS</span>
                <span id="docAreasCountBadge" class="badge-area-count"></span>
              </div>
              <div class="doceditor-picker-search-wrap">
                <?php echo icon('search', ['size' => 13, 'class' => 'doceditor-picker-search-icon']); ?>
                <input type="text" id="docAreaSearchInput" placeholder="Buscar área/setor..." autocomplete="off" class="doceditor-picker-search-input" oninput="Docs.filterAreas(this.value)" />
              </div>
              <div id="docAreasList" class="doceditor-picker-list"></div>
            </div>
          </div>

          <button type="button" class="btn btn-sm btn-outline-primary" id="btnDocConvertWordPdf" onclick="Docs.triggerConvertWordPdf()" title="Importar e converter arquivo Word (.docx/.doc), txt ou PDF para Markdown">
            <?php echo icon('upload', ['class' => 'btn-svg', 'size' => 14]); ?>
            <span>Word / PDF / TXT</span>
          </button>

          <input type="file" id="convertWordPdfInput" accept=".docx,.doc,.pdf,.txt,.md,.markdown" hidden />

          <button type="button" class="btn btn-sm btn-outline" id="btnDocUpload" onclick="Docs.openUploadModal()" title="Anexar arquivo ou imagem">
            <?php echo icon('paperclip', ['class' => 'btn-svg', 'size' => 14]); ?>
            <span>Anexo</span>
          </button>

          <button type="button" class="btn btn-sm btn-outline" id="btnToggleVersions" onclick="Docs.toggleVersions()" title="Histórico de Versões">
            <?php echo icon('history', ['class' => 'btn-svg', 'size' => 14]); ?>
            <span>Histórico</span>
          </button>

          <!-- Dropdown de Exportação -->
          <div class="doceditor-dropdown-wrap">
            <button type="button" class="btn btn-sm btn-outline" id="btnExportDoc" onclick="Docs.toggleExportMenu(this)" title="Opções de Exportação">
              <?php echo icon('download', ['class' => 'btn-svg', 'size' => 14]); ?>
              <span>Exportar</span>
            </button>
            <div class="doceditor-dropdown-menu" id="exportDropdownMenu" hidden>
              <button type="button" class="dropdown-item" onclick="Docs.exportMarkdown()">
                <?php echo icon('file', ['size' => 14]); ?>
                <span>Baixar arquivo .MD</span>
              </button>
              <button type="button" class="dropdown-item" onclick="Docs.printDocument()">
                <?php echo icon('printer', ['size' => 14]); ?>
                <span>Imprimir / Salvar PDF</span>
              </button>
              <button type="button" class="dropdown-item" onclick="Docs.copyMarkdown()">
                <?php echo icon('copy', ['size' => 14]); ?>
                <span>Copiar Markdown</span>
              </button>
            </div>
          </div>

          <button type="button" class="btn btn-sm btn-outline" id="btnShareDoc" onclick="Docs.openShareModal()" hidden title="Compartilhar documento com outros usuários">
            <?php echo icon('share', ['size' => 13, 'style' => 'margin-right:4px']); ?>
            <span>Compartilhar</span>
          </button>

          <button type="button" class="btn btn-sm btn-outline-primary" id="btnSubmitApproval" onclick="Docs.openApprovalModal()" hidden title="Solicitar aprovação deste documento a outro usuário">
            <?php echo icon('check', ['size' => 13, 'style' => 'margin-right:4px']); ?>
            <span>Submeter p/ Aprovação</span>
          </button>

          <button type="button" class="btn btn-sm btn-danger-outline" id="btnDeleteDoc" onclick="Docs.deleteDoc()" hidden title="Excluir documento permanentemente">
            <?php echo icon('trash', ['class' => 'btn-svg', 'size' => 14]); ?>
          </button>

          <button type="button" class="btn btn-sm btn-primary" id="btnSaveDoc" onclick="Docs.saveDoc()" title="Salvar alterações (Ctrl+S)">
            <?php echo icon('check', ['class' => 'btn-svg', 'size' => 14]); ?>
            <span>Salvar</span>
          </button>
        </div>

        <button type="button" class="doceditor-scroll-arrow right" onclick="Docs.scrollActionsRight()" title="Rolar opções para a direita">&#8250;</button>
      </div>
    </header>

    <!-- Barra de Ferramentas de Formatação (Markdown Toolbar) -->
    <div class="doceditor-toolbar">
      <div class="toolbar-group">
        <button type="button" class="toolbar-btn" onclick="Docs.insertFormat('bold')" title="Negrito (Ctrl+B)"><b>B</b></button>
        <button type="button" class="toolbar-btn" onclick="Docs.insertFormat('italic')" title="Itálico (Ctrl+I)"><i>I</i></button>
        <button type="button" class="toolbar-btn" onclick="Docs.insertFormat('strike')" title="Tachado"><s>S</s></button>
        <button type="button" class="toolbar-btn" onclick="Docs.insertFormat('code_inline')" title="Código Inline"><code>&lt;&gt;</code></button>
      </div>

      <div class="toolbar-separator"></div>

      <div class="toolbar-group">
        <button type="button" class="toolbar-btn" onclick="Docs.insertFormat('h1')" title="Título H1">H1</button>
        <button type="button" class="toolbar-btn" onclick="Docs.insertFormat('h2')" title="Título H2">H2</button>
        <button type="button" class="toolbar-btn" onclick="Docs.insertFormat('h3')" title="Título H3">H3</button>
      </div>

      <div class="toolbar-separator"></div>

      <div class="toolbar-group">
        <button type="button" class="toolbar-btn" onclick="Docs.insertFormat('ul')" title="Lista com marcadores">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        </button>
        <button type="button" class="toolbar-btn" onclick="Docs.insertFormat('ol')" title="Lista numerada">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/></svg>
        </button>
        <button type="button" class="toolbar-btn" onclick="Docs.insertFormat('task')" title="Lista de Tarefas (Checklist)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        </button>
      </div>

      <div class="toolbar-separator"></div>

      <div class="toolbar-group">
        <button type="button" class="toolbar-btn" onclick="Docs.insertFormat('indent')" title="Parágrafo / Recuo (Tab)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" y1="6" x2="11" y2="6"/><line x1="21" y1="12" x2="11" y2="12"/><line x1="21" y1="18" x2="11" y2="18"/><polyline points="3 8 7 12 3 16"/><line x1="7" y1="12" x2="3" y2="12"/></svg>
        </button>
        <button type="button" class="toolbar-btn" onclick="Docs.insertFormat('outdent')" title="Diminuir recuo (Shift+Tab)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" y1="6" x2="11" y2="6"/><line x1="21" y1="12" x2="11" y2="12"/><line x1="21" y1="18" x2="11" y2="18"/><polyline points="7 8 3 12 7 16"/><line x1="3" y1="12" x2="7" y2="12"/></svg>
        </button>
      </div>

      <div class="toolbar-separator"></div>

      <div class="toolbar-group">
        <button type="button" class="toolbar-btn" onclick="Docs.insertFormat('quote')" title="Citação / Callout">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.75-2-2-2H4c-1.25 0-2 .75-2 2v6c0 7 3 10 3 10z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.75-2-2-2h-4c-1.25 0-2 .75-2 2v6c0 7 3 10 3 10z"/></svg>
        </button>
        <button type="button" class="toolbar-btn" onclick="Docs.insertFormat('code_block')" title="Bloco de Código">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
        </button>
        <button type="button" class="toolbar-btn" onclick="Docs.insertFormat('table')" title="Tabela Markdown">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/></svg>
        </button>
        <button type="button" class="toolbar-btn" onclick="Docs.insertFormat('hr')" title="Linha Horizontal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/></svg>
        </button>
      </div>

      <div class="toolbar-separator"></div>

      <div class="toolbar-group">
        <button type="button" class="toolbar-btn" onclick="Docs.insertFormat('link')" title="Inserir Link (Ctrl+K)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        </button>
        <button type="button" class="toolbar-btn" onclick="Docs.openUploadModal()" title="Inserir Imagem / Anexo">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        </button>
        <button type="button" class="toolbar-btn" onclick="Docs.insertFormat('psk')" title="Inserir Senha Oculta / PSK (Revela no hover e criptografa ao salvar)">
          <?php echo icon('key', ['size' => 13]); ?>
        </button>
      </div>

      <!-- Abas de Modo (Editar / Split / Preview) -->
      <div class="doceditor-mode-tabs">
        <button type="button" class="mode-tab active" id="tabModeEdit" onclick="Docs.switchEditorMode('edit')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
          <span>Editar</span>
        </button>
        <button type="button" class="mode-tab" id="tabModeSplit" onclick="Docs.switchEditorMode('split')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="3" x2="12" y2="21"/></svg>
          <span>Split Screen</span>
        </button>
        <button type="button" class="mode-tab" id="tabModePreview" onclick="Docs.switchEditorMode('preview')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          <span>Leitura</span>
        </button>
      </div>
    </div>

    <!-- Barra de Tags & Nota de Versão -->
    <div class="doceditor-meta-bar">
      <div class="meta-tags-section">
        <span class="meta-label">Tags:</span>
        <div class="meta-tags-container" id="tagsContainer">
          <div class="tag-input-autocomplete-wrapper">
            <input type="text" id="tagInput" class="meta-tag-input" placeholder="+ tag (pressione Enter)" autocomplete="off" />
            <div id="tagSuggestionsDropdown" class="tag-suggestions-dropdown" hidden></div>
          </div>
        </div>
      </div>
      <div class="meta-version-note-section">
        <span class="meta-label">Nota da versão:</span>
        <input type="text" id="docVersionNote" class="meta-version-input" placeholder="Ex: Adicionada seção de requisitos..." />
      </div>
    </div>

    <!-- Área de Edição e Preview (Split-ready) -->
    <div class="doceditor-workspace mode-edit" id="editorWorkspace">
      <!-- Painel de Código / Markdown -->
      <div class="workspace-pane pane-editor" id="paneEditor">
        <textarea id="docContent" class="doceditor-textarea" placeholder="Comece a escrever seu documento em Markdown...&#10;&#10;# Título&#10;Seu texto aqui..."></textarea>
      </div>

      <!-- Painel de Live Preview -->
      <div class="workspace-pane pane-preview" id="panePreview">
        <div class="preview-content markdown-body" id="previewContent">
          <!-- Renderizado via marked -->
        </div>
      </div>
    </div>

    <!-- Barra de Status Inferior -->
    <footer class="doceditor-status-bar">
      <div class="status-left">
        <span class="status-badge" id="statusWordCount">0 palavras</span>
        <span class="status-bullet">•</span>
        <span class="status-badge" id="statusCharCount">0 caracteres</span>
        <span class="status-bullet">•</span>
        <span class="status-badge" id="statusReadTime">~1 min de leitura</span>
      </div>
      <div class="status-right">
        <span class="status-save-state" id="statusSaveState">Nenhuma alteração</span>
      </div>
    </footer>

  </div>

  <!-- ─── PAINEL LATERAL DE HISTÓRICO DE VERSÕES ────────── -->
  <aside class="doceditor-version-sidebar" id="versionSidebar" hidden>
    <div class="version-sidebar-header">
      <div class="version-sidebar-title">
        <?php echo icon('history', ['size' => 18]); ?>
        <h3>Histórico de Versões</h3>
      </div>
      <button type="button" class="btn-close-sidebar" onclick="Docs.toggleVersions()" title="Fechar histórico">×</button>
    </div>
    <div class="version-sidebar-body" id="versionList">
      <!-- Lista de versões renderizada via JS -->
    </div>
  </aside>
</div>

<!-- ─── MODAL DE NOVA / EDITAR PASTA ──────────────────────── -->
<div class="modal-backdrop-custom" id="catModalOverlay" hidden>
  <div class="modal-dialog-custom">
    <div class="modal-header-custom">
      <div class="modal-header-title">
        <span class="modal-icon-badge" id="catModalIconBadge">
          <?php echo icon('folder', ['size' => 20]); ?>
        </span>
        <h3 id="catModalTitle">Nova Pasta</h3>
      </div>
      <button type="button" class="modal-btn-close" onclick="Docs.closeCatModal()">×</button>
    </div>

    <div class="modal-body-custom">
      <div class="form-group-custom">
        <label for="catName">Nome da Pasta *</label>
        <input type="text" id="catName" class="form-input-custom" placeholder="Ex: Manuais de Operação" autocomplete="off" />
      </div>

      <div class="form-group-custom">
        <label for="catParent">Pasta Superior (Hierarquia)</label>
        <select id="catParent" class="form-input-custom">
          <option value="">Nenhuma (Pasta Raiz)</option>
        </select>
      </div>

      <!-- Seletor de Ícone Nativo SVG -->
      <div class="form-group-custom">
        <label>Ícone da Pasta</label>
        <div class="icon-selector-grid" id="iconSelectorGrid">
          <button type="button" class="icon-opt active" data-icon="folder" onclick="Docs.selectCatIcon('folder', this)">
            <?php echo \App\Helpers\IconHelper::get('folder', ['class' => 'icon-opt-svg']); ?>
            <span>Geral</span>
          </button>
          <button type="button" class="icon-opt" data-icon="doc" onclick="Docs.selectCatIcon('doc', this)">
            <?php echo \App\Helpers\IconHelper::get('doc', ['class' => 'icon-opt-svg']); ?>
            <span>Docs</span>
          </button>
          <button type="button" class="icon-opt" data-icon="auth" onclick="Docs.selectCatIcon('auth', this)">
            <?php echo \App\Helpers\IconHelper::get('auth', ['class' => 'icon-opt-svg']); ?>
            <span>Segurança</span>
          </button>
          <button type="button" class="icon-opt" data-icon="compute" onclick="Docs.selectCatIcon('compute', this)">
            <?php echo \App\Helpers\IconHelper::get('compute', ['class' => 'icon-opt-svg']); ?>
            <span>Sistemas</span>
          </button>
          <button type="button" class="icon-opt" data-icon="network" onclick="Docs.selectCatIcon('network', this)">
            <?php echo \App\Helpers\IconHelper::get('network', ['class' => 'icon-opt-svg']); ?>
            <span>Redes</span>
          </button>
          <button type="button" class="icon-opt" data-icon="devops" onclick="Docs.selectCatIcon('devops', this)">
            <?php echo \App\Helpers\IconHelper::get('devops', ['class' => 'icon-opt-svg']); ?>
            <span>DevOps</span>
          </button>
          <button type="button" class="icon-opt" data-icon="db" onclick="Docs.selectCatIcon('db', this)">
            <?php echo \App\Helpers\IconHelper::get('db', ['class' => 'icon-opt-svg']); ?>
            <span>Dados</span>
          </button>
          <button type="button" class="icon-opt" data-icon="tool" onclick="Docs.selectCatIcon('tool', this)">
            <?php echo \App\Helpers\IconHelper::get('tool', ['class' => 'icon-opt-svg']); ?>
            <span>Ferramentas</span>
          </button>
          <button type="button" class="icon-opt" data-icon="idea" onclick="Docs.selectCatIcon('idea', this)">
            <?php echo \App\Helpers\IconHelper::get('idea', ['class' => 'icon-opt-svg']); ?>
            <span>Ideias</span>
          </button>
          <button type="button" class="icon-opt" data-icon="goal" onclick="Docs.selectCatIcon('goal', this)">
            <?php echo \App\Helpers\IconHelper::get('goal', ['class' => 'icon-opt-svg']); ?>
            <span>Metas</span>
          </button>
          <button type="button" class="icon-opt" data-icon="rocket" onclick="Docs.selectCatIcon('rocket', this)">
            <?php echo \App\Helpers\IconHelper::get('rocket', ['class' => 'icon-opt-svg']); ?>
            <span>Projetos</span>
          </button>
          <button type="button" class="icon-opt" data-icon="book" onclick="Docs.selectCatIcon('book', this)">
            <?php echo \App\Helpers\IconHelper::get('book', ['class' => 'icon-opt-svg']); ?>
            <span>Guias</span>
          </button>
          <button type="button" class="icon-opt" data-icon="docker" onclick="Docs.selectCatIcon('docker', this)">
            <?php echo \App\Helpers\IconHelper::get('docker', ['class' => 'icon-opt-svg']); ?>
            <span>Docker</span>
          </button>
          <button type="button" class="icon-opt" data-icon="workflow" onclick="Docs.selectCatIcon('workflow', this)">
            <?php echo \App\Helpers\IconHelper::get('workflow', ['class' => 'icon-opt-svg']); ?>
            <span>Automação</span>
          </button>
          <button type="button" class="icon-opt" data-icon="kanban" onclick="Docs.selectCatIcon('kanban', this)">
            <?php echo \App\Helpers\IconHelper::get('kanban', ['class' => 'icon-opt-svg']); ?>
            <span>Kanban</span>
          </button>
          <button type="button" class="icon-opt" data-icon="robot" onclick="Docs.selectCatIcon('robot', this)">
            <?php echo \App\Helpers\IconHelper::get('robot', ['class' => 'icon-opt-svg']); ?>
            <span>IA / Robô</span>
          </button>
          <button type="button" class="icon-opt" data-icon="truck" onclick="Docs.selectCatIcon('truck', this)">
            <?php echo \App\Helpers\IconHelper::get('truck', ['class' => 'icon-opt-svg']); ?>
            <span>Logística</span>
          </button>
          <button type="button" class="icon-opt" data-icon="cloud" onclick="Docs.selectCatIcon('cloud', this)">
            <?php echo \App\Helpers\IconHelper::get('cloud', ['class' => 'icon-opt-svg']); ?>
            <span>Cloud</span>
          </button>
          <button type="button" class="icon-opt" data-icon="code" onclick="Docs.selectCatIcon('code', this)">
            <?php echo \App\Helpers\IconHelper::get('code', ['class' => 'icon-opt-svg']); ?>
            <span>Código</span>
          </button>
          <button type="button" class="icon-opt" data-icon="terminal" onclick="Docs.selectCatIcon('terminal', this)">
            <?php echo \App\Helpers\IconHelper::get('terminal', ['class' => 'icon-opt-svg']); ?>
            <span>Terminal</span>
          </button>
          <button type="button" class="icon-opt" data-icon="calendar" onclick="Docs.selectCatIcon('calendar', this)">
            <?php echo \App\Helpers\IconHelper::get('calendar', ['class' => 'icon-opt-svg']); ?>
            <span>Agenda</span>
          </button>
          <button type="button" class="icon-opt" data-icon="chart" onclick="Docs.selectCatIcon('chart', this)">
            <?php echo \App\Helpers\IconHelper::get('chart', ['class' => 'icon-opt-svg']); ?>
            <span>Relatórios</span>
          </button>
          <button type="button" class="icon-opt" data-icon="users" onclick="Docs.selectCatIcon('users', this)">
            <?php echo \App\Helpers\IconHelper::get('users', ['class' => 'icon-opt-svg']); ?>
            <span>Equipe</span>
          </button>
          <button type="button" class="icon-opt" data-icon="mail" onclick="Docs.selectCatIcon('mail', this)">
            <?php echo \App\Helpers\IconHelper::get('mail', ['class' => 'icon-opt-svg']); ?>
            <span>E-mail</span>
          </button>
          <button type="button" class="icon-opt" data-icon="server" onclick="Docs.selectCatIcon('server', this)">
            <?php echo \App\Helpers\IconHelper::get('server', ['class' => 'icon-opt-svg']); ?>
            <span>Servidor</span>
          </button>
          <button type="button" class="icon-opt" data-icon="home" onclick="Docs.selectCatIcon('home', this)">
            <?php echo \App\Helpers\IconHelper::get('home', ['class' => 'icon-opt-svg']); ?>
            <span>Início</span>
          </button>
          <button type="button" class="icon-opt" data-icon="shield" onclick="Docs.selectCatIcon('shield', this)">
            <?php echo \App\Helpers\IconHelper::get('shield', ['class' => 'icon-opt-svg']); ?>
            <span>Proteção</span>
          </button>
          <button type="button" class="icon-opt" data-icon="link" onclick="Docs.selectCatIcon('link', this)">
            <?php echo \App\Helpers\IconHelper::get('link', ['class' => 'icon-opt-svg']); ?>
            <span>Integrações</span>
          </button>
          <button type="button" class="icon-opt" data-icon="bell" onclick="Docs.selectCatIcon('bell', this)">
            <?php echo \App\Helpers\IconHelper::get('bell', ['class' => 'icon-opt-svg']); ?>
            <span>Notificações</span>
          </button>
          <button type="button" class="icon-opt" data-icon="linux" onclick="Docs.selectCatIcon('linux', this)">
            <?php echo \App\Helpers\IconHelper::get('linux', ['class' => 'icon-opt-svg']); ?>
            <span>Linux</span>
          </button>
          <button type="button" class="icon-opt" data-icon="windows" onclick="Docs.selectCatIcon('windows', this)">
            <?php echo \App\Helpers\IconHelper::get('windows', ['class' => 'icon-opt-svg']); ?>
            <span>Windows</span>
          </button>
          <button type="button" class="icon-opt" data-icon="android" onclick="Docs.selectCatIcon('android', this)">
            <?php echo \App\Helpers\IconHelper::get('android', ['class' => 'icon-opt-svg']); ?>
            <span>Android</span>
          </button>
          <button type="button" class="icon-opt" data-icon="apple" onclick="Docs.selectCatIcon('apple', this)">
            <?php echo \App\Helpers\IconHelper::get('apple', ['class' => 'icon-opt-svg']); ?>
            <span>Apple</span>
          </button>
        </div>
        <input type="hidden" id="catIcon" value="folder" />
      </div>

      <!-- Seletor de Cor da Pasta -->
      <div class="form-group-custom">
        <label>Cor da Pasta</label>
        <div class="color-selector-grid" id="colorSelectorGrid">
          <!-- Linha 1: Verdes e Teals -->
          <button type="button" class="color-opt active" data-color="#1abb9c" style="--c: #1abb9c" onclick="Docs.selectCatColor('#1abb9c', this)" title="Verde Água"></button>
          <button type="button" class="color-opt" data-color="#0d9488" style="--c: #0d9488" onclick="Docs.selectCatColor('#0d9488', this)" title="Teal Escuro"></button>
          <button type="button" class="color-opt" data-color="#14b8a6" style="--c: #14b8a6" onclick="Docs.selectCatColor('#14b8a6', this)" title="Teal Vibrante"></button>
          <button type="button" class="color-opt" data-color="#10b981" style="--c: #10b981" onclick="Docs.selectCatColor('#10b981', this)" title="Esmeralda"></button>
          <button type="button" class="color-opt" data-color="#059669" style="--c: #059669" onclick="Docs.selectCatColor('#059669', this)" title="Verde Floresta"></button>
          <button type="button" class="color-opt" data-color="#22c55e" style="--c: #22c55e" onclick="Docs.selectCatColor('#22c55e', this)" title="Verde Claro"></button>
          <button type="button" class="color-opt" data-color="#84cc16" style="--c: #84cc16" onclick="Docs.selectCatColor('#84cc16', this)" title="Lima"></button>
          <!-- Linha 2: Cianos e Azuis -->
          <button type="button" class="color-opt" data-color="#06b6d4" style="--c: #06b6d4" onclick="Docs.selectCatColor('#06b6d4', this)" title="Ciano"></button>
          <button type="button" class="color-opt" data-color="#0891b2" style="--c: #0891b2" onclick="Docs.selectCatColor('#0891b2', this)" title="Ciano Escuro"></button>
          <button type="button" class="color-opt" data-color="#0284c7" style="--c: #0284c7" onclick="Docs.selectCatColor('#0284c7', this)" title="Azul Céu"></button>
          <button type="button" class="color-opt" data-color="#3b82f6" style="--c: #3b82f6" onclick="Docs.selectCatColor('#3b82f6', this)" title="Azul Royal"></button>
          <button type="button" class="color-opt" data-color="#2563eb" style="--c: #2563eb" onclick="Docs.selectCatColor('#2563eb', this)" title="Azul Cobalto"></button>
          <button type="button" class="color-opt" data-color="#1d4ed8" style="--c: #1d4ed8" onclick="Docs.selectCatColor('#1d4ed8', this)" title="Azul Marinho"></button>
          <button type="button" class="color-opt" data-color="#6366f1" style="--c: #6366f1" onclick="Docs.selectCatColor('#6366f1', this)" title="Índigo"></button>
          <!-- Linha 3: Violetas, Roxos e Rosas -->
          <button type="button" class="color-opt" data-color="#8b5cf6" style="--c: #8b5cf6" onclick="Docs.selectCatColor('#8b5cf6', this)" title="Violeta"></button>
          <button type="button" class="color-opt" data-color="#7c3aed" style="--c: #7c3aed" onclick="Docs.selectCatColor('#7c3aed', this)" title="Violeta Profundo"></button>
          <button type="button" class="color-opt" data-color="#a855f7" style="--c: #a855f7" onclick="Docs.selectCatColor('#a855f7', this)" title="Roxo"></button>
          <button type="button" class="color-opt" data-color="#c026d3" style="--c: #c026d3" onclick="Docs.selectCatColor('#c026d3', this)" title="Fúcsia"></button>
          <button type="button" class="color-opt" data-color="#d946ef" style="--c: #d946ef" onclick="Docs.selectCatColor('#d946ef', this)" title="Magenta"></button>
          <button type="button" class="color-opt" data-color="#ec4899" style="--c: #ec4899" onclick="Docs.selectCatColor('#ec4899', this)" title="Rosa"></button>
          <button type="button" class="color-opt" data-color="#db2777" style="--c: #db2777" onclick="Docs.selectCatColor('#db2777', this)" title="Rosa Escuro"></button>
          <!-- Linha 4: Vermelhos, Âmbar, Laranjas e Neutros -->
          <button type="button" class="color-opt" data-color="#f43f5e" style="--c: #f43f5e" onclick="Docs.selectCatColor('#f43f5e', this)" title="Carmim"></button>
          <button type="button" class="color-opt" data-color="#ef4444" style="--c: #ef4444" onclick="Docs.selectCatColor('#ef4444', this)" title="Vermelho"></button>
          <button type="button" class="color-opt" data-color="#dc2626" style="--c: #dc2626" onclick="Docs.selectCatColor('#dc2626', this)" title="Rubi"></button>
          <button type="button" class="color-opt" data-color="#ea580c" style="--c: #ea580c" onclick="Docs.selectCatColor('#ea580c', this)" title="Laranja"></button>
          <button type="button" class="color-opt" data-color="#f59e0b" style="--c: #f59e0b" onclick="Docs.selectCatColor('#f59e0b', this)" title="Âmbar"></button>
          <button type="button" class="color-opt" data-color="#64748b" style="--c: #64748b" onclick="Docs.selectCatColor('#64748b', this)" title="Ardósia"></button>
          <button type="button" class="color-opt" data-color="#334155" style="--c: #334155" onclick="Docs.selectCatColor('#334155', this)" title="Chumbo"></button>
        </div>
        <input type="hidden" id="catColor" value="#1abb9c" />
      </div>

      <div class="form-group-custom">
        <label for="catSortOrder">Ordem de Exibição</label>
        <input type="number" id="catSortOrder" class="form-input-custom" value="0" min="0" />
      </div>
    </div>

    <div class="modal-footer-custom">
      <button type="button" class="btn btn-secondary" onclick="Docs.closeCatModal()">Cancelar</button>
      <button type="button" class="btn btn-primary" onclick="Docs.saveCategory()">Salvar Pasta</button>
    </div>
    <input type="hidden" id="catEditId" value="" />
  </div>
</div>

<!-- ─── MODAL DE UPLOAD DE ANEXOS / MÍDIAS ─────────────────── -->
<div class="modal-backdrop-custom" id="uploadModalOverlay" hidden>
  <div class="modal-dialog-custom">
    <div class="modal-header-custom">
      <div class="modal-header-title">
        <span class="modal-icon-badge"><?php echo icon('paperclip', ['size' => 16]); ?></span>
        <h3>Enviar Arquivos & Imagens</h3>
      </div>
      <button type="button" class="modal-btn-close" onclick="Docs.closeUploadModal()">×</button>
    </div>

    <div class="modal-body-custom">
      <div class="upload-dropzone" id="uploadZone">
        <div class="dropzone-icon">
          <?php echo icon('upload', ['size' => 36]); ?>
        </div>
        <p class="dropzone-main-text">Arraste e solte seus arquivos aqui</p>
        <p class="dropzone-sub-text">ou <span>clique para navegar no computador</span> (PNG, JPG, SVG, PDF, DOCX, TXT)</p>
        <input type="file" id="fileInput" hidden />
      </div>

      <div id="uploadProgress" class="upload-progress-bar" hidden>
        <div class="progress-bar-fill" id="uploadProgressBarFill"></div>
      </div>

      <div id="uploadResult" class="upload-result-box" hidden>
        <!-- Preenchido via JS com preview e botão de inserir -->
      </div>
    </div>

    <div class="modal-footer-custom">
      <button type="button" class="btn btn-secondary" onclick="Docs.closeUploadModal()">Concluir</button>
    </div>
  </div>
</div>

<!-- ─── MODAL DE COMPARTILHAMENTO DE DOCUMENTOS ──────────── -->
<div class="modal-backdrop-custom" id="shareModalOverlay" hidden>
  <div class="modal-dialog-custom" style="max-width: 560px">
    <div class="modal-header-custom">
      <div class="modal-header-title">
        <span class="modal-icon-badge">
          <?php echo icon('users', ['class' => 'badge-svg', 'size' => 16]); ?>
        </span>
        <div>
          <h3 id="shareModalDocTitle" style="margin:0;font-size:1.1rem;font-weight:700;">Compartilhar Documento</h3>
          <p style="font-size:0.8rem;color:var(--docs-text-mut);margin:3px 0 0;" id="shareModalDocAuthor"></p>
        </div>
      </div>
      <button type="button" class="modal-btn-close" onclick="Docs.closeShareModal()">×</button>
    </div>

    <div class="modal-body-custom">
      <div id="shareAddSection">
        <label style="font-weight:600;font-size:0.82rem;margin-bottom:6px;display:block;">Adicionar Colaborador com Permissão de Edição</label>
        
        <!-- Campo de Busca com Autocomplete -->
        <div class="user-autocomplete-wrapper" style="position:relative;margin-bottom:8px;">
          <div style="display:flex;gap:8px;align-items:center;">
            <input type="text" id="shareUserSearchInput" class="form-input-custom" placeholder="Digite para buscar usuário..." autocomplete="off" style="flex:1;" />
            <button type="button" class="btn btn-primary" onclick="Docs.addSelectedUser()" id="btnAddShareUser" disabled style="white-space:nowrap;">
              <span>Conceder Acesso</span>
            </button>
          </div>
          <div id="shareUserDropdownList" class="user-autocomplete-dropdown" hidden></div>
        </div>

        <div id="selectedUserChip" class="selected-user-chip" hidden>
          <span id="selectedUserName"></span>
          <button type="button" onclick="Docs.clearSelectedUser()" class="btn-clear-user">✕</button>
        </div>

        <p style="font-size:0.75rem;color:var(--docs-text-mut);margin-top:6px;">
          Usuários com a role <strong>Editor</strong> adicionados aqui poderão editar e salvar versões deste documento.
        </p>
      </div>

      <div class="stats-divider" style="margin:16px 0;width:100%;height:1px;background:var(--border);"></div>

      <div>
        <label style="font-weight:600;font-size:0.82rem;margin-bottom:8px;display:block;">Colaboradores Atuais</label>
        <div id="shareListContainer" class="share-collaborators-list">
          <!-- Renderizado dinamicamente via JS -->
        </div>
      </div>
    </div>

    <div class="modal-footer-custom">
      <button type="button" class="btn btn-secondary" onclick="Docs.closeShareModal()">Fechar</button>
    </div>
  </div>
</div>

<!-- ─── MODAL DE SUBMISSÃO PARA APROVAÇÃO ─────────────── -->
<div class="modal-backdrop-custom" id="approvalModalOverlay" hidden>
  <div class="modal-dialog-custom" style="max-width: 560px">
    <div class="modal-header-custom">
      <div class="modal-header-title">
        <span class="modal-icon-badge">
          <?php echo icon('check', ['class' => 'badge-svg', 'size' => 16]); ?>
        </span>
        <div>
          <h3 id="approvalModalDocTitle" style="margin:0;font-size:1.1rem;font-weight:700;">Submeter para Aprovação</h3>
          <p style="font-size:0.8rem;color:var(--docs-text-mut);margin:3px 0 0;" id="approvalModalDocAuthor"></p>
        </div>
      </div>
      <button type="button" class="modal-btn-close" onclick="Docs.closeApprovalModal()">×</button>
    </div>

    <div class="modal-body-custom">
      <div id="approvalPendingStatus" hidden style="background:rgba(217,119,6,0.08);border:1px solid rgba(217,119,6,0.35);border-radius:8px;padding:12px 14px;margin-bottom:14px;">
        <p style="margin:0 0 8px;font-size:0.85rem;color:#b45309;font-weight:600;" id="approvalPendingText"></p>
        <button type="button" class="btn btn-sm btn-danger-outline" onclick="Docs.cancelApprovalRequest()" title="Cancelar solicitação pendente">
          Cancelar solicitação
        </button>
      </div>

      <div id="approvalAddSection">
        <label style="font-weight:600;font-size:0.82rem;margin-bottom:6px;display:block;">Selecionar Aprovador</label>

        <div class="user-autocomplete-wrapper" style="position:relative;margin-bottom:8px;">
          <div style="display:flex;gap:8px;align-items:center;">
            <input type="text" id="approvalUserSearchInput" class="form-input-custom" placeholder="Digite para buscar usuário..." autocomplete="off" style="flex:1;" />
            <button type="button" class="btn btn-primary" onclick="Docs.submitForApproval()" id="btnConfirmSubmitApproval" disabled style="white-space:nowrap;">
              <span>Enviar Solicitação</span>
            </button>
          </div>
          <div id="approvalUserDropdownList" class="user-autocomplete-dropdown" hidden></div>
        </div>

        <div id="approvalSelectedUserChip" class="selected-user-chip" hidden>
          <span id="approvalSelectedUserName"></span>
          <button type="button" onclick="Docs.clearApprovalUser()" class="btn-clear-user">✕</button>
        </div>

        <p style="font-size:0.75rem;color:var(--docs-text-mut);margin-top:6px;">
          Selecione um <strong>Editor</strong>, <strong>Aprovador</strong> ou <strong>Admin</strong> (exceto você, o autor).
          A pessoa indicada receberá uma notificação no sistema e um e-mail com botão de aprovação.
        </p>
      </div>
    </div>

    <div class="modal-footer-custom">
      <button type="button" class="btn btn-secondary" onclick="Docs.closeApprovalModal()">Fechar</button>
    </div>
  </div>
</div>

