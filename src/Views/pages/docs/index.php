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
      <div class="sidebar-section">
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
          <span class="filter-chip" id="filterChipText"></span>
        </div>
        <button type="button" class="btn-clear-filter" onclick="Docs.clearFilter()">Remover filtro ✕</button>
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
          <input type="text" id="tagInput" class="meta-tag-input" placeholder="+ tag (pressione Enter)" />
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

