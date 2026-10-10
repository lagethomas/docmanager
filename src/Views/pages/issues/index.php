<?php
declare(strict_types=1);

use App\Core\Controller;

$user = $user ?? [];
?>

<div class="container-fluid" style="padding: 1.5rem 0;">
  <!-- Header da Página -->
  <div class="d-flex justify-content-between align-items-center mb-4" style="flex-wrap: wrap; gap: 1rem;">
    <div>
      <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--docs-text, #0f172a); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
        <?php echo icon('alert', ['size' => 24, 'style' => 'color:#d97706;']); ?>
        Gestão de Issues do Acervo
      </h1>
      <p style="font-size: 0.85rem; color: var(--docs-text-mut, #64748b); margin: 0.25rem 0 0 0;">
        Gerencie, trate e acompanhe todas as solicitações de correção e apontamentos de documentos em um único lugar.
      </p>
    </div>
  </div>

  <!-- Cards de Estatísticas -->
  <div class="row g-3 mb-4" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
    <div class="stat-card" style="background: var(--docs-surface, #ffffff); border: 1px solid var(--docs-border, #e2e8f0); border-radius: 12px; padding: 1.25rem; display: flex; align-items: center; justify-content: space-between;">
      <div>
        <div style="font-size: 0.78rem; font-weight: 600; text-transform: uppercase; color: #2563eb; letter-spacing: 0.5px;">Issues Abertas</div>
        <div style="font-size: 1.75rem; font-weight: 800; color: var(--docs-text, #0f172a); margin-top: 4px;" id="statAbertas">0</div>
      </div>
      <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(37, 99, 235, 0.1); color: #2563eb; display: flex; align-items: center; justify-content: center;">
        <?php echo icon('alert', ['size' => 20]); ?>
      </div>
    </div>

    <div class="stat-card" style="background: var(--docs-surface, #ffffff); border: 1px solid var(--docs-border, #e2e8f0); border-radius: 12px; padding: 1.25rem; display: flex; align-items: center; justify-content: space-between;">
      <div>
        <div style="font-size: 0.78rem; font-weight: 600; text-transform: uppercase; color: #d97706; letter-spacing: 0.5px;">Aguardando Aprovação</div>
        <div style="font-size: 1.75rem; font-weight: 800; color: var(--docs-text, #0f172a); margin-top: 4px;" id="statAguardando">0</div>
      </div>
      <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(217, 119, 6, 0.1); color: #d97706; display: flex; align-items: center; justify-content: center;">
        <?php echo icon('checkCircle', ['size' => 20]); ?>
      </div>
    </div>

    <div class="stat-card" style="background: var(--docs-surface, #ffffff); border: 1px solid var(--docs-border, #e2e8f0); border-radius: 12px; padding: 1.25rem; display: flex; align-items: center; justify-content: space-between;">
      <div>
        <div style="font-size: 0.78rem; font-weight: 600; text-transform: uppercase; color: #16a34a; letter-spacing: 0.5px;">Concluídas & Travadas</div>
        <div style="font-size: 1.75rem; font-weight: 800; color: var(--docs-text, #0f172a); margin-top: 4px;" id="statConcluidas">0</div>
      </div>
      <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(22, 163, 74, 0.1); color: #16a34a; display: flex; align-items: center; justify-content: center;">
        <?php echo icon('lock', ['size' => 20]); ?>
      </div>
    </div>

    <div class="stat-card" style="background: var(--docs-surface, #ffffff); border: 1px solid var(--docs-border, #e2e8f0); border-radius: 12px; padding: 1.25rem; display: flex; align-items: center; justify-content: space-between;">
      <div>
        <div style="font-size: 0.78rem; font-weight: 600; text-transform: uppercase; color: var(--docs-text-mut, #64748b); letter-spacing: 0.5px;">Total de Issues</div>
        <div style="font-size: 1.75rem; font-weight: 800; color: var(--docs-text, #0f172a); margin-top: 4px;" id="statTotal">0</div>
      </div>
      <div style="width: 44px; height: 44px; border-radius: 10px; background: var(--docs-surface-2, rgba(0,0,0,0.04)); color: var(--docs-text-mut); display: flex; align-items: center; justify-content: center;">
        <?php echo icon('doc', ['size' => 20]); ?>
      </div>
    </div>
  </div>

  <!-- Barra de Filtros e Abas -->
  <div style="background: var(--docs-surface, #ffffff); border: 1px solid var(--docs-border, #e2e8f0); border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
    <!-- Abas de Status -->
    <div class="btn-group" id="issueStatusTabs" role="group" style="display: flex; gap: 4px; background: var(--docs-surface-2, rgba(0,0,0,0.04)); padding: 4px; border-radius: 8px;">
      <button type="button" class="btn btn-sm active-tab" data-status="aberto" onclick="IssuesModule.setFilter('aberto')" style="padding: 6px 14px; font-weight: 600; border-radius: 6px; font-size: 0.82rem; border: none; cursor: pointer; transition: all 0.2s;">
        Abertas
      </button>
      <button type="button" class="btn btn-sm" data-status="resolvido_pendente" onclick="IssuesModule.setFilter('resolvido_pendente')" style="padding: 6px 14px; font-weight: 600; border-radius: 6px; font-size: 0.82rem; border: none; cursor: pointer; transition: all 0.2s;">
        Aguardando Aprovação
      </button>
      <button type="button" class="btn btn-sm" data-status="concluido" onclick="IssuesModule.setFilter('concluido')" style="padding: 6px 14px; font-weight: 600; border-radius: 6px; font-size: 0.82rem; border: none; cursor: pointer; transition: all 0.2s;">
        Concluídas
      </button>
      <button type="button" class="btn btn-sm" data-status="todos" onclick="IssuesModule.setFilter('todos')" style="padding: 6px 14px; font-weight: 600; border-radius: 6px; font-size: 0.82rem; border: none; cursor: pointer; transition: all 0.2s;">
        Todas
      </button>
    </div>

    <!-- Campo de Busca -->
    <div style="position: relative; min-width: 260px; flex: 1; max-width: 400px;">
      <input type="text" id="txtIssueSearch" class="form-control" placeholder="Buscar por documento, issue ou usuário..." oninput="IssuesModule.onSearchInput()" style="width: 100%; border-radius: 8px; padding: 0.45rem 0.75rem 0.45rem 2.2rem; font-size: 0.85rem; border: 1px solid var(--docs-border, #cbd5e1); background: var(--docs-surface); color: var(--docs-text);" />
      <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--docs-text-mut); pointer-events: none; display: flex; align-items: center;">
        <?php echo icon('search', ['size' => 14]); ?>
      </span>
    </div>
  </div>

  <!-- Lista de Issues -->
  <div id="issuesContainer" style="display: flex; flex-direction: column; gap: 1rem;">
    <div style="text-align: center; padding: 3rem; color: var(--docs-text-mut); background: var(--docs-surface); border: 1px solid var(--docs-border); border-radius: 12px;">
      Carregando lista de issues...
    </div>
  </div>
</div>
