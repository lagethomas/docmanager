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
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div>
          <h1 class="docs-header-title" id="listTitle">Todos os Documentos</h1>
          <div class="docs-header-subtitle" id="listSubtitle">Carregando acervo...</div>
        </div>
      </div>
    </div>

    <div class="docs-header-center">
      <div class="docs-search-wrapper">
        <svg class="docs-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="docsSearch" class="docs-search-input" placeholder="Buscar por título, conteúdo ou tag..." autocomplete="off" />
        <span class="docs-search-shortcut" title="Atalho de busca">/</span >
        <button type="button" class="docs-search-clear" id="docsSearchClear" hidden title="Limpar busca">×</button>
      </div>
    </div>

    <div class="docs-header-right">
      <!-- Alternador de visualização (Cards / Tabela) -->
      <div class="docs-view-switcher" role="group" aria-label="Modo de visualização">
        <button type="button" class="view-btn active" id="btnViewGrid" onclick="Docs.setViewMode('grid')" title="Visualização em Grade">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        </button>
        <button type="button" class="view-btn" id="btnViewList" onclick="Docs.setViewMode('list')" title="Visualização em Lista / Tabela">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        </button>
      </div>

      <!-- Seletor de Ordenação -->
      <select id="docsSort" class="docs-sort-select" onchange="Docs.handleSortChange(this.value)" title="Ordenar documentos">
        <option value="updated_desc">Recentes primeiro</option>
        <option value="updated_asc">Antigos primeiro</option>
        <option value="title_asc">Título (A-Z)</option>
        <option value="title_desc">Título (Z-A)</option>
        <option value="versions_desc">Mais editados</option>
      </select>

      <button type="button" class="btn btn-secondary btn-sm" id="btnHeaderNewFolder" onclick="Docs.openCatModal()" title="Criar nova pasta de organização">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="btn-svg"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
        <span>Nova Pasta</span>
      </button>

      <button type="button" class="btn btn-primary btn-sm" id="btnHeaderNewDoc" onclick="Docs.newDoc()" title="Criar novo documento (Atalho: N)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="btn-svg"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
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
          <span class="trash-banner-icon">🗑️</span>
          <div>
            <strong>Lixeira de Documentos</strong>
            <p>Itens na lixeira podem ser restaurados ou permanentemente excluídos.</p>
          </div>
        </div>
        <div class="trash-banner-actions">
          <button type="button" class="btn btn-sm btn-danger" onclick="Docs.emptyTrashModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="btn-svg"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
            <span>Esvaziar Lixeira</span>
          </button>
        </div>
      </div>

      <!-- Container de Grade (Cards) -->
      <div class="docs-grid-container" id="docsGrid">
        <!-- Renderizado dinamicamente -->
      </div>

      <!-- Container de Lista / Tabela -->
      <div class="card" id="docsTableWrapper" hidden>
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
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
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
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
          <span>Fechar</span>
        </button>
        <div class="doceditor-divider"></div>
        <input type="text" id="docTitle" class="doceditor-title-input" placeholder="Título do documento..." autocomplete="off" />
      </div>

      <div class="doceditor-topbar-right">
        <div class="doceditor-cat-select-wrap">
          <svg class="select-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
          <select id="docCategory" class="doceditor-select" title="Pasta do Documento"></select>
        </div>

        <label class="toggle-switch" title="Tornar documento livremente visível na Base de Conhecimento Pública sem login">
          <input type="checkbox" id="chkDocIsPublic" />
          <span class="toggle-slider"></span>
          <span>🌐 Público</span>
        </label>

        <button type="button" class="btn btn-sm btn-outline" id="btnDocUpload" onclick="Docs.openUploadModal()" title="Anexar arquivo ou imagem">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="btn-svg"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
          <span>Anexo</span>
        </button>

        <button type="button" class="btn btn-sm btn-outline" id="btnToggleVersions" onclick="Docs.toggleVersions()" title="Histórico de Versões">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="btn-svg"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
          <span>Histórico</span>
        </button>

        <!-- Dropdown de Exportação -->
        <div class="doceditor-dropdown-wrap">
          <button type="button" class="btn btn-sm btn-outline" onclick="Docs.toggleExportMenu()" title="Opções de Exportação">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="btn-svg"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            <span>Exportar</span>
          </button>
          <div class="doceditor-dropdown-menu" id="exportDropdownMenu" hidden>
            <button type="button" class="dropdown-item" onclick="Docs.exportMarkdown()">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              <span>Baixar arquivo .MD</span>
            </button>
            <button type="button" class="dropdown-item" onclick="Docs.printDocument()">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
              <span>Imprimir / Salvar PDF</span>
            </button>
            <button type="button" class="dropdown-item" onclick="Docs.copyMarkdown()">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              <span>Copiar Markdown</span>
            </button>
          </div>
        </div>

        <button type="button" class="btn btn-sm btn-outline" id="btnShareDoc" onclick="Docs.openShareModal()" hidden title="Compartilhar documento com outros usuários">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="btn-svg"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
          <span>Compartilhar</span>
        </button>

        <button type="button" class="btn btn-sm btn-danger-outline" id="btnDeleteDoc" onclick="Docs.deleteDoc()" hidden title="Excluir documento permanentemente">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="btn-svg"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
        </button>

        <button type="button" class="btn btn-sm btn-primary" id="btnSaveDoc" onclick="Docs.saveDoc()" title="Salvar alterações (Ctrl+S)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="btn-svg"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
          <span>Salvar</span>
        </button>
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
          <div class="tag-input-autocomplete-wrapper" style="position:relative;display:inline-flex;align-items:center;">
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
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
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
        <span class="modal-icon-badge" id="catModalIconBadge" style="display:flex;align-items:center;justify-content:center;color:var(--docs-primary);">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
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
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            <span>Geral</span>
          </button>
          <button type="button" class="icon-opt" data-icon="doc" onclick="Docs.selectCatIcon('doc', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <span>Docs</span>
          </button>
          <button type="button" class="icon-opt" data-icon="auth" onclick="Docs.selectCatIcon('auth', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <span>Segurança</span>
          </button>
          <button type="button" class="icon-opt" data-icon="compute" onclick="Docs.selectCatIcon('compute', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            <span>Sistemas</span>
          </button>
          <button type="button" class="icon-opt" data-icon="network" onclick="Docs.selectCatIcon('network', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
            <span>Redes</span>
          </button>
          <button type="button" class="icon-opt" data-icon="devops" onclick="Docs.selectCatIcon('devops', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            <span>DevOps</span>
          </button>
          <button type="button" class="icon-opt" data-icon="db" onclick="Docs.selectCatIcon('db', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
            <span>Dados</span>
          </button>
          <button type="button" class="icon-opt" data-icon="tool" onclick="Docs.selectCatIcon('tool', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            <span>Ferramentas</span>
          </button>
          <button type="button" class="icon-opt" data-icon="idea" onclick="Docs.selectCatIcon('idea', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><line x1="9" y1="18" x2="15" y2="18"/><line x1="10" y1="22" x2="14" y2="22"/><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5.76.76 1.23 1.52 1.41 2.5z"/></svg>
            <span>Ideias</span>
          </button>
          <button type="button" class="icon-opt" data-icon="goal" onclick="Docs.selectCatIcon('goal', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
            <span>Metas</span>
          </button>
          <button type="button" class="icon-opt" data-icon="rocket" onclick="Docs.selectCatIcon('rocket', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4.5c1.47-1.47 4.5-2 4.5-2"/><path d="M12 15v5s3.03-.55 4.5-2c1.47-1.47 2-4.5 2-4.5"/></svg>
            <span>Projetos</span>
          </button>
          <button type="button" class="icon-opt" data-icon="book" onclick="Docs.selectCatIcon('book', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            <span>Guias</span>
          </button>
          <button type="button" class="icon-opt" data-icon="docker" onclick="Docs.selectCatIcon('docker', this)">
            <svg viewBox="0 0 640 640" class="icon-opt-svg"><path fill="currentColor" d="M349.9 300.3L283.8 300.3L283.8 240.9L349.9 240.9L349.9 300.3zM349.9 96L283.8 96L283.8 156.7L349.9 156.7L349.9 96zM428.1 240.8L362 240.8L362 300.2L428.1 300.2L428.1 240.8zM271.8 168.7L205.7 168.7L205.7 228.8L271.8 228.8L271.8 168.7zM349.9 168.7L283.8 168.7L283.8 228.8L349.9 228.8L349.9 168.7zM626.7 268.7C612.3 259 579.1 255.5 553.6 260.3C550.3 236.3 536.9 215.4 512.5 196.6L498.5 187.3L489.2 201.3C470.8 229.1 465.8 274.9 485.5 305.1C476.8 309.8 459.7 316.2 437.1 315.8L2.4 315.8C-6.3 366.6 8.2 432.6 46.4 477.9C83.5 521.8 139.1 544.1 211.8 544.1C369.2 544.1 485.7 471.6 540.2 339.9C561.6 340.3 607.8 340 631.5 294.7C633 292.2 638.1 281.5 640 277.6L626.7 268.7zM115.6 240.8L49.6 240.8L49.6 300.2L115.7 300.2L115.7 240.8L115.6 240.8zM193.7 240.8L127.6 240.8L127.6 300.2L193.7 300.2L193.7 240.8zM271.8 240.8L205.7 240.8L205.7 300.2L271.8 300.2L271.8 240.8zM193.7 168.7L127.6 168.7L127.6 228.8L193.7 228.8L193.7 168.7z"/></svg>
            <span>Docker</span>
          </button>
          <button type="button" class="icon-opt" data-icon="workflow" onclick="Docs.selectCatIcon('workflow', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><circle cx="5" cy="6" r="3"/><circle cx="19" cy="6" r="3"/><circle cx="12" cy="18" r="3"/><path d="M5 9v3a3 3 0 0 0 3 3h0"/><path d="M19 9v3a3 3 0 0 1-3 3h0"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
            <span>Automação</span>
          </button>
          <button type="button" class="icon-opt" data-icon="kanban" onclick="Docs.selectCatIcon('kanban', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><rect x="3" y="3" width="5" height="18" rx="1"/><rect x="10" y="3" width="5" height="11" rx="1"/><rect x="17" y="3" width="5" height="14" rx="1"/></svg>
            <span>Kanban</span>
          </button>
          <button type="button" class="icon-opt" data-icon="robot" onclick="Docs.selectCatIcon('robot', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><rect x="3" y="8" width="18" height="12" rx="2"/><circle cx="9" cy="14" r="1.5"/><circle cx="15" cy="14" r="1.5"/><path d="M12 2v4"/><path d="M8 4h8"/><path d="M9 18v2"/><path d="M15 18v2"/></svg>
            <span>IA / Robô</span>
          </button>
          <button type="button" class="icon-opt" data-icon="truck" onclick="Docs.selectCatIcon('truck', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><path d="M1 3h15v13H1z"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            <span>Logística</span>
          </button>
          <button type="button" class="icon-opt" data-icon="cloud" onclick="Docs.selectCatIcon('cloud', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg>
            <span>Cloud</span>
          </button>
          <button type="button" class="icon-opt" data-icon="code" onclick="Docs.selectCatIcon('code', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
            <span>Código</span>
          </button>
          <button type="button" class="icon-opt" data-icon="terminal" onclick="Docs.selectCatIcon('terminal', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg>
            <span>Terminal</span>
          </button>
          <button type="button" class="icon-opt" data-icon="calendar" onclick="Docs.selectCatIcon('calendar', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <span>Agenda</span>
          </button>
          <button type="button" class="icon-opt" data-icon="chart" onclick="Docs.selectCatIcon('chart', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            <span>Relatórios</span>
          </button>
          <button type="button" class="icon-opt" data-icon="users" onclick="Docs.selectCatIcon('users', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <span>Equipe</span>
          </button>
          <button type="button" class="icon-opt" data-icon="mail" onclick="Docs.selectCatIcon('mail', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <span>E-mail</span>
          </button>
          <button type="button" class="icon-opt" data-icon="server" onclick="Docs.selectCatIcon('server', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"/><rect x="2" y="14" width="20" height="8" rx="2" ry="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>
            <span>Servidor</span>
          </button>
          <button type="button" class="icon-opt" data-icon="home" onclick="Docs.selectCatIcon('home', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            <span>Início</span>
          </button>
          <button type="button" class="icon-opt" data-icon="shield" onclick="Docs.selectCatIcon('shield', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <span>Proteção</span>
          </button>
          <button type="button" class="icon-opt" data-icon="link" onclick="Docs.selectCatIcon('link', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            <span>Integrações</span>
          </button>
          <button type="button" class="icon-opt" data-icon="bell" onclick="Docs.selectCatIcon('bell', this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-opt-svg"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <span>Notificações</span>
          </button>
          <button type="button" class="icon-opt" data-icon="linux" onclick="Docs.selectCatIcon('linux', this)">
            <svg viewBox="0 0 640 640" class="icon-opt-svg"><path fill="currentColor" d="M316.9 187.3C317.9 187.8 318.7 189 319.9 189C321 189 322.7 188.6 322.8 187.5C323 186.1 320.9 185.2 319.6 184.6C317.9 183.9 315.7 183.6 314.1 184.5C313.7 184.7 313.3 185.2 313.5 185.6C313.8 186.9 315.8 186.7 316.9 187.3zM295 189C296.2 189 297 187.8 298 187.3C299.1 186.7 301.1 186.9 301.5 185.7C301.7 185.3 301.3 184.8 300.9 184.6C299.3 183.7 297.1 184 295.4 184.7C294.1 185.3 292 186.2 292.2 187.6C292.3 188.6 294 189.1 295 189zM516 467.8C512.4 463.8 510.7 456.2 508.8 448.1C507 440 504.9 431.3 498.3 425.7C497 424.6 495.7 423.6 494.3 422.8C493 422 491.6 421.3 490.2 420.8C499.4 393.5 495.8 366.3 486.5 341.7C475.1 311.6 455.2 285.3 440 267.3C422.9 245.8 406.3 225.4 406.6 195.3C407.1 149.4 411.7 64.1 330.8 64C228.4 63.8 254 167.4 252.9 199.2C251.2 222.6 246.5 241 230.4 263.9C211.5 286.4 184.9 322.7 172.3 360.6C166.3 378.5 163.5 396.7 166.1 413.9C159.6 419.7 154.7 428.6 149.5 434.1C145.3 438.4 139.2 440 132.5 442.4C125.8 444.8 118.5 448.4 114 456.9C111.9 460.8 111.2 465 111.2 469.3C111.2 473.2 111.8 477.2 112.4 481.1C113.6 489.2 114.9 496.8 113.2 501.9C108 516.3 107.3 526.3 111 533.6C114.8 540.9 122.4 544.1 131.1 545.9C148.4 549.5 171.9 548.6 190.4 558.4C210.2 568.8 230.3 572.5 246.3 568.8C257.9 566.2 267.4 559.2 272.2 548.6C284.7 548.5 298.5 543.2 320.5 542C335.4 540.8 354.1 547.3 375.6 546.1C376.2 548.4 377 550.7 378.1 552.8L378.1 552.9C386.4 569.6 401.9 577.2 418.4 575.9C435 574.6 452.5 564.9 466.7 548C480.3 531.6 502.7 524.8 517.6 515.8C525 511.3 531 505.7 531.5 497.5C531.9 489.3 527.1 480.2 516 467.8zM319.8 151.3C329.6 129.1 354 129.5 363.8 150.9C370.3 165.1 367.4 181.8 359.5 191.3C357.9 190.5 353.6 188.7 346.9 186.4C348 185.2 350 183.7 350.8 181.8C355.6 170 350.6 154.8 341.7 154.5C334.4 154 327.8 165.3 329.9 177.5C325.8 175.5 320.5 174 316.9 173.1C315.9 166.2 316.6 158.5 319.8 151.3zM279.1 139.8C289.2 139.8 299.9 154 298.2 173.3C294.7 174.3 291.1 175.8 288 177.9C289.2 169 284.7 157.8 278.4 158.3C270 159 268.6 179.5 276.6 186.4C277.6 187.2 278.5 186.2 270.7 191.9C255.1 177.3 260.2 139.8 279.1 139.8zM265.5 200.5C271.7 195.9 279.1 190.5 279.6 190C284.3 185.6 293.1 175.8 307.5 175.8C314.6 175.8 323.1 178.1 333.4 184.7C339.7 188.8 344.7 189.1 356 194C364.4 197.5 369.7 203.7 366.5 212.2C363.9 219.3 355.5 226.6 343.8 230.3C332.7 233.9 324 246.3 305.6 245.2C301.7 245 298.6 244.2 296 243.1C288 239.6 283.8 232.7 276 228.1C267.4 223.3 262.8 217.7 261.3 212.8C259.9 207.9 261.3 203.8 265.5 200.5zM268.8 534.5C266.1 569.6 224.9 568.9 193.5 552.5C163.6 536.7 124.9 546 117 530.6C114.6 525.9 114.6 517.9 119.6 504.2L119.6 504C122 496.4 120.2 488 119 480.1C117.8 472.3 117.2 465.1 119.9 460.1C123.4 453.4 128.4 451 134.7 448.8C145 445.1 146.5 445.4 154.3 438.9C159.8 433.2 163.8 426 168.6 420.9C173.7 415.4 178.6 412.8 186.3 414C194.4 415.2 201.4 420.8 208.2 430L227.8 465.6C237.3 485.5 270.9 514 268.8 534.5zM267.4 508.6C263.3 502 257.8 495 253 489C260.1 489 267.2 486.8 269.7 480.1C272 473.9 269.7 465.2 262.3 455.2C248.8 437 224 422.7 224 422.7C210.5 414.3 202.9 404 199.4 392.8C195.9 381.6 196.4 369.5 199.1 357.6C204.3 334.7 217.7 312.4 226.3 298.4C228.6 296.7 227.1 301.6 217.6 319.2C209.1 335.3 193.2 372.5 215 401.6C215.6 380.9 220.5 359.8 228.8 340.1C240.8 312.7 266.1 265.2 268.1 227.4C269.2 228.2 272.7 230.6 274.3 231.5C278.9 234.2 282.4 238.2 286.9 241.8C299.3 251.8 315.4 251 329.3 243C335.5 239.5 340.5 235.5 345.2 234C355.1 230.9 363 225.4 367.5 219C375.2 249.4 393.2 293.3 404.7 314.7C410.8 326.1 423 350.2 428.3 379.3C431.6 379.2 435.3 379.7 439.2 380.7C453 345 427.5 306.5 415.9 295.8C411.2 291.2 411 289.2 413.3 289.3C425.9 300.5 442.5 323 448.5 348.3C451.3 359.9 451.8 372 448.9 384C465.3 390.8 484.8 401.9 479.6 418.8C477.4 418.7 476.4 418.8 475.4 418.8C478.6 408.7 471.5 401.2 452.6 392.7C433 384.1 416.6 384.1 414.3 405.2C402.2 409.4 396 419.9 392.9 432.5C390.1 443.7 389.3 457.2 388.5 472.4C388 480.1 384.9 490.4 381.7 501.4C349.6 524.3 305 534.3 267.4 508.6zM524.8 497.1C523.9 513.9 483.6 517 461.6 543.6C448.4 559.3 432.2 568 418 569.1C403.8 570.2 391.5 564.3 384.3 549.8C379.6 538.7 381.9 526.7 385.4 513.5C389.1 499.3 394.6 484.7 395.3 472.9C396.1 457.7 397 444.4 399.5 434.2C402.1 423.9 406.1 417 413.2 413.1C413.5 412.9 413.9 412.8 414.2 412.6C415 425.8 421.5 439.2 433 442.1C445.6 445.4 463.7 434.6 471.4 425.8C480.4 425.5 487.1 424.9 494 430.9C503.9 439.4 501.1 461.2 511.1 472.5C521.7 484.1 525.1 492 524.8 497.1zM269.4 212.7C271.4 214.6 274.1 217.2 277.4 219.8C284 225 293.2 230.4 304.7 230.4C316.3 230.4 327.2 224.5 336.5 219.6C341.4 217 347.4 212.6 351.3 209.2C355.2 205.8 357.2 202.9 354.4 202.6C351.6 202.3 351.8 205.2 348.4 207.7C344 210.9 338.7 215.1 334.5 217.5C327.1 221.7 315 227.7 304.6 227.7C294.2 227.7 285.9 222.9 279.7 218C276.6 215.5 274 213 272 211.1C270.5 209.7 270.1 206.5 267.7 206.2C266.3 206.1 265.9 209.9 269.4 212.7z"/></svg>
            <span>Linux</span>
          </button>
          <button type="button" class="icon-opt" data-icon="windows" onclick="Docs.selectCatIcon('windows', this)">
            <svg viewBox="0 0 24 24" fill="currentColor" class="icon-opt-svg"><path d="M0 3.449L9.75 2.1v9.451H0m10.949-9.602L24 0v11.4H10.949M0 12.6h9.75v9.451L0 20.699M10.949 12.6H24V24l-12.9-1.801"/></svg>
            <span>Windows</span>
          </button>
          <button type="button" class="icon-opt" data-icon="android" onclick="Docs.selectCatIcon('android', this)">
            <svg viewBox="0 0 24 24" fill="currentColor" class="icon-opt-svg"><path d="M6 18c0 .55.45 1 1 1h1v3.5c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5V19h2v3.5c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5V19h1c.55 0 1-.45 1-1V8H6v10zM3.5 8C2.67 8 2 8.67 2 9.5v7c0 .83.67 1.5 1.5 1.5S5 17.33 5 16.5v-7C5 8.67 4.33 8 3.5 8zm17 0c-.83 0-1.5.67-1.5 1.5v7c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5v-7c0-.83-.67-1.5-1.5-1.5zm-4.97-5.84l1.3-1.3c.2-.2.2-.51 0-.71-.2-.2-.51-.2-.71 0l-1.48 1.48A5.84 5.84 0 0012 1c-.96 0-1.86.23-2.66.63L7.85.15c-.2-.2-.51-.2-.71 0-.2.2-.2.51 0 .71l1.31 1.31A5.983 5.983 0 006 7h12c0-2.21-1.2-4.15-2.97-5.16zM10 5H9V4h1v1zm5 0h-1V4h1v1z"/></svg>
            <span>Android</span>
          </button>
          <button type="button" class="icon-opt" data-icon="apple" onclick="Docs.selectCatIcon('apple', this)">
            <svg viewBox="0 0 24 24" fill="currentColor" class="icon-opt-svg"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
            <span>Apple</span>
          </button>
        </div>
        <input type="hidden" id="catIcon" value="folder" />
      </div>

      <!-- Seletor de Cor da Pasta -->
      <div class="form-group-custom">
        <label>Cor da Pasta</label>
        <div class="color-selector-grid" id="colorSelectorGrid">
          <button type="button" class="color-opt active" data-color="#1abb9c" style="--c: #1abb9c" onclick="Docs.selectCatColor('#1abb9c', this)" title="Verde Água"></button>
          <button type="button" class="color-opt" data-color="#3b82f6" style="--c: #3b82f6" onclick="Docs.selectCatColor('#3b82f6', this)" title="Azul"></button>
          <button type="button" class="color-opt" data-color="#6366f1" style="--c: #6366f1" onclick="Docs.selectCatColor('#6366f1', this)" title="Índigo"></button>
          <button type="button" class="color-opt" data-color="#8b5cf6" style="--c: #8b5cf6" onclick="Docs.selectCatColor('#8b5cf6', this)" title="Roxo"></button>
          <button type="button" class="color-opt" data-color="#ec4899" style="--c: #ec4899" onclick="Docs.selectCatColor('#ec4899', this)" title="Rosa"></button>
          <button type="button" class="color-opt" data-color="#ef4444" style="--c: #ef4444" onclick="Docs.selectCatColor('#ef4444', this)" title="Vermelho"></button>
          <button type="button" class="color-opt" data-color="#f59e0b" style="--c: #f59e0b" onclick="Docs.selectCatColor('#f59e0b', this)" title="Âmbar"></button>
          <button type="button" class="color-opt" data-color="#10b981" style="--c: #10b981" onclick="Docs.selectCatColor('#10b981', this)" title="Esmeralda"></button>
          <button type="button" class="color-opt" data-color="#06b6d4" style="--c: #06b6d4" onclick="Docs.selectCatColor('#06b6d4', this)" title="Ciano"></button>
          <button type="button" class="color-opt" data-color="#64748b" style="--c: #64748b" onclick="Docs.selectCatColor('#64748b', this)" title="Cinza"></button>
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
        <span class="modal-icon-badge">📎</span>
        <h3>Enviar Arquivos & Imagens</h3>
      </div>
      <button type="button" class="modal-btn-close" onclick="Docs.closeUploadModal()">×</button>
    </div>

    <div class="modal-body-custom">
      <div class="upload-dropzone" id="uploadZone">
        <div class="dropzone-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
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
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="badge-svg"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
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

