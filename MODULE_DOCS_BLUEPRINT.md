# 📘 Módulo de Documentação Completo (Blueprint & Especificação de IA)

> **Instrução para IAs e Desenvolvedores:** Este documento contém a especificação técnica completa, arquitetura e código-fonte do **Módulo de Documentação Interativo com Busca em Tempo Real e Páginas Single**. Utilize este arquivo como diretriz (System Prompt) para replicar identicamente este módulo em qualquer novo projeto web PHP/MVC, Node.js, Python ou Laravel.

---

## 📐 1. Arquitetura e Estrutura de Arquivos

O módulo é composto por **5 componentes principais**:

```text
├── src/
│   ├── Models/
│   │   └── DocArticle.php           # Repositório de artigos e metadados
│   ├── Controllers/
│   │   └── DocsController.php       # Controller (index e show)
│   ├── Views/
│   │   └── pages/
│   │       └── docs/
│   │           ├── index.php        # Tela principal com cards e busca live
│   │           └── show.php         # Tela single detalhada (2 colunas)
│   └── Routes/
│       └── web.php                  # Registro das rotas GET /docs e GET /docs/{slug}
```

---

## 🤖 2. Prompt de Prompting para IAs (Copiar & Colar)

Para mandar uma IA criar este módulo em um novo sistema, cole o seguinte prompt:

```markdown
Por favor, crie um Módulo de Documentação completo em MVC com as seguintes características:

1. Rota `/docs` (Listagem Principal):
   - Banner de topo com título e campo de busca live (`onkeyup`) que filtra os cards em tempo real.
   - Pílulas de filtro por categorias ("Todos", "Arquitetura", "Instalação", "Segurança", etc.).
   - Grid responsivo de cards com título, resumo, categoria, tempo estimado de leitura e botão "Ler Artigo Completo".
   - Cada card deve ser clicável e redirecionar para a rota single `/docs/{slug}`.

2. Rota `/docs/{slug}` (Página Single Detalhada):
   - Layout de 2 colunas (3fr 1fr): coluna esquerda com conteúdo rico HTML e coluna direita com índice lateral de atalhos.
   - Breadcrumbs de navegação ("Documentação / Categoria / Tópico").
   - Botão "← Voltar para a Central de Documentação".

3. Estrutura de Código:
   - Model `DocArticle` com método `all()` e `findBySlug($slug)`.
   - Controller `DocsController` com ações `index()` e `show($slug)`.
   - Estilização em Vanilla CSS moderna (Glassmorphism, dark/light mode com variáveis CSS).
```

---

## 💻 3. Código-Fonte Completo dos Arquivos

### 📄 Arquivo 1: `src/Models/DocArticle.php`

```php
<?php

namespace App\Models;

class DocArticle
{
    private static array $articles = [
        'arquitetura' => [
            'slug' => 'arquitetura',
            'title' => '1. Arquitetura Modular 1 para N',
            'category' => 'arquitetura',
            'category_name' => 'Arquitetura',
            'icon' => 'cpu',
            'summary' => 'Entenda como o sistema utiliza o padrão Driver Pattern para integrar a Origem Central a N destinos.',
            'read_time' => '4 min de leitura',
            'content_html' => '
                <h3>Visão Geral da Arquitetura</h3>
                <p>O sistema foi projetado em arquitetura MVC nativa em PHP 8.x para conectar uma Origem Central a múltiplos ERPs de Destino.</p>
                <h4>O Padrão Driver Pattern</h4>
                <p>Cada integração possui sua própria classe driver isolada.</p>
            '
        ],
        'criptografia-seguranca' => [
            'slug' => 'criptografia-seguranca',
            'title' => '8. Criptografia AES-256 e Segurança',
            'category' => 'seguranca',
            'category_name' => 'Criptografia',
            'icon' => 'shield-check',
            'summary' => 'Como a chave APP_KEY do arquivo .env protege senhas e tokens salvos no banco de dados.',
            'read_time' => '4 min de leitura',
            'content_html' => '
                <h3>Proteção de Dados Sensíveis (AES-256-CBC)</h3>
                <p>Todas as credenciais são salvas no banco criptografadas com AES-256-CBC.</p>
                <h4>🔑 Como Gerar a APP_KEY via Terminal:</h4>
                <pre class="json-viewer">php -r "echo \'APP_KEY=base64:\' . base64_encode(random_bytes(32)) . PHP_EOL;"</pre>
                <h4>Via Script Utilitário:</h4>
                <pre class="json-viewer">php bin/generate-key.php</pre>
            '
        ],
        'troubleshooting' => [
            'slug' => 'troubleshooting',
            'title' => '9. Logs e Resolução de Erros (Troubleshooting)',
            'category' => 'testes',
            'category_name' => 'Troubleshooting',
            'icon' => 'file-text',
            'summary' => 'Como diagnosticar códigos de erro HTTP (401, 403, 404, 500) e permissões SQLite.',
            'read_time' => '5 min de leitura',
            'content_html' => '
                <h3>Diagnóstico de Erros Comuns</h3>
                <h4>Erro "attempt to write a readonly database" (Permissões SQLite)</h4>
                <p>Execute no terminal Linux / VPS para dar permissão de escrita ao servidor web:</p>
                <pre class="json-viewer">chmod -R 777 database storage</pre>
            '
        ]
    ];

    public static function all(): array
    {
        return self::$articles;
    }

    public static function findBySlug(string $slug): ?array
    {
        return self::$articles[$slug] ?? null;
    }
}
```

---

### 📄 Arquivo 2: `src/Controllers/DocsController.php`

```php
<?php

namespace App\Controllers;

use App\Core\View;
use App\Models\DocArticle;

class DocsController
{
    public function index(): void
    {
        $articles = DocArticle::all();

        View::render('docs/index', [
            'title' => 'Documentação Completa - Sistema',
            'articles' => $articles
        ]);
    }

    public function show(string $slug): void
    {
        $article = DocArticle::findBySlug($slug);

        if (!$article) {
            http_response_code(404);
            die("<h1>404 - Artigo Não Encontrado</h1><a href='/docs'>← Voltar</a>");
        }

        $allArticles = DocArticle::all();

        View::render('docs/show', [
            'title' => $article['title'] . ' - Documentação',
            'article' => $article,
            'allArticles' => $allArticles
        ]);
    }
}
```

---

### 📄 Arquivo 3: `src/Views/pages/docs/index.php` (Listagem com Busca Live)

```html
<?php
/**
 * @var array $articles
 */
?>
<!-- Banner Superior de Busca -->
<div class="glass-panel" style="padding:32px; margin-bottom:28px;">
    <div style="max-width:800px;">
        <span class="badge badge-completed">CENTRAL DE CONHECIMENTO</span>
        <h2 style="font-size:1.8rem; font-weight:800; margin:8px 0;">Documentação Oficial</h2>
        
        <!-- Campo de Busca em Tempo Real -->
        <div style="position:relative; margin-top:20px;">
            <i data-lucide="search" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:var(--primary);"></i>
            <input type="text" 
                   id="docsSearchInput" 
                   onkeyup="filterDocs()" 
                   class="form-control" 
                   placeholder="Digite o termo para buscar (ex: cron, cnpj, token, sqlite)..." 
                   style="padding-left:48px; height:50px; font-size:1rem; border-radius:12px;">
        </div>
    </div>
</div>

<!-- Filtro por Categorias -->
<div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:28px;" id="docsCategoryPills">
    <button type="button" class="btn btn-primary btn-sm doc-filter-btn active" onclick="filterCategory('all', event)">
        Todos os Tópicos (<?= count($articles) ?>)
    </button>
    <button type="button" class="btn btn-secondary btn-sm doc-filter-btn" onclick="filterCategory('arquitetura', event)">
        Arquitetura
    </button>
    <button type="button" class="btn btn-secondary btn-sm doc-filter-btn" onclick="filterCategory('seguranca', event)">
        Criptografia
    </button>
</div>

<!-- Grid de Cards de Documentação -->
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap:24px;" id="docsCardsContainer">
    <?php foreach ($articles as $slug => $a): ?>
        <div class="glass-panel doc-card" 
             data-category="<?= $a['category'] ?>" 
             onclick="window.location.href='<?= url('/docs/' . $slug) ?>'"
             style="padding:28px; display:flex; flex-direction:column; cursor:pointer; transition:transform 0.2s ease, border-color 0.2s ease;">
            
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <i data-lucide="<?= htmlspecialchars($a['icon']) ?>" style="width:24px; height:24px; color:var(--primary);"></i>
                <span class="badge badge-processing"><?= htmlspecialchars($a['category_name']) ?></span>
            </div>

            <h3 class="doc-card-title" style="font-size:1.2rem; font-weight:700; margin-bottom:8px;">
                <?= htmlspecialchars($a['title']) ?>
            </h3>

            <p class="doc-card-text" style="font-size:0.88rem; color:var(--text-muted); margin-bottom:20px;">
                <?= htmlspecialchars($a['summary']) ?>
            </p>

            <div style="margin-top:auto; display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--border-color); padding-top:14px;">
                <span style="font-size:0.78rem; color:var(--text-dim);"><?= htmlspecialchars($a['read_time']) ?></span>
                <a href="<?= url('/docs/' . $slug) ?>" class="btn btn-secondary btn-sm" onclick="event.stopPropagation();">
                    Ler Artigo Completo →
                </a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
function filterDocs() {
    const query = document.getElementById('docsSearchInput').value.toLowerCase().trim();
    const cards = document.querySelectorAll('.doc-card');
    cards.forEach(card => {
        const fullText = card.innerText.toLowerCase();
        card.style.display = (!query || fullText.includes(query)) ? 'flex' : 'none';
    });
}

function filterCategory(cat, evt) {
    document.getElementById('docsSearchInput').value = '';
    document.querySelectorAll('.doc-filter-btn').forEach(btn => btn.classList.replace('btn-primary', 'btn-secondary'));
    if (evt) evt.currentTarget.classList.replace('btn-secondary', 'btn-primary');

    document.querySelectorAll('.doc-card').forEach(card => {
        const cardCat = card.getAttribute('data-category');
        card.style.display = (cat === 'all' || cardCat === cat) ? 'flex' : 'none';
    });
}
</script>
```

---

### 📄 Arquivo 4: `src/Views/pages/docs/show.php` (Página Single em 2 Colunas)

```html
<?php
/**
 * @var array $article
 * @var array $allArticles
 */
?>
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <div style="font-size:0.85rem; color:var(--text-muted);">
        <a href="<?= url('/docs') ?>">Documentação</a> / 
        <span><?= htmlspecialchars($article['category_name']) ?></span> / 
        <strong><?= htmlspecialchars($article['title']) ?></strong>
    </div>

    <a href="<?= url('/docs') ?>" class="btn btn-secondary btn-sm">
        ← Voltar para a Central
    </a>
</div>

<div style="display:grid; grid-template-columns: 3fr 1fr; gap:28px;">
    <!-- Coluna Esquerda: Conteúdo Completo -->
    <div class="glass-panel" style="padding:36px;">
        <h1 style="font-size:1.8rem; font-weight:800; margin-bottom:10px;"><?= htmlspecialchars($article['title']) ?></h1>
        <p style="font-size:1rem; color:var(--text-muted); margin-bottom:24px;"><?= htmlspecialchars($article['summary']) ?></p>

        <div class="doc-single-body" style="line-height:1.7;">
            <?= $article['content_html'] ?>
        </div>
    </div>

    <!-- Coluna Direita: Menu de Atalhos -->
    <div class="glass-panel" style="padding:24px;">
        <h4 style="font-size:1rem; font-weight:700; margin-bottom:16px;">Tópicos da Documentação</h4>
        <div style="display:flex; flex-direction:column; gap:8px;">
            <?php foreach ($allArticles as $s => $item): ?>
                <a href="<?= url('/docs/' . $s) ?>" 
                   style="padding:10px 12px; border-radius:8px; font-size:0.85rem; text-decoration:none; <?= $s === $article['slug'] ? 'background:rgba(56, 189, 248, 0.15); color:var(--primary); font-weight:700;' : 'color:var(--text-muted);' ?>">
                    <?= htmlspecialchars($item['title']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
```

---

### 📄 Arquivo 5: Registro das Rotas (`src/Routes/web.php`)

```php
// Módulo de Documentação
$router->get('/docs', [DocsController::class, 'index'], [AuthMiddleware::class]);
$router->get('/docs/{slug}', [DocsController::class, 'show'], [AuthMiddleware::class]);
```

---

## 🎯 4. Como Replicar em Outras Linguagens (Node.js / Express, Python / FastAPI / Laravel)

1. **Laravel:** Copie a array de `$articles` para uma Controller `DocsController` e retorne views `view('docs.index')` e `view('docs.show')`.
2. **Node.js (Express / EJS / React):** Retorne os artigos em um endpoint `/api/docs` e use o `filterDocs()` no front-end em React/Vue.
3. **Python (FastAPI / Jinja2):** Registre a rota `@app.get("/docs")` e retorne os dados no template Jinja.

---
*DOCUMENTATION_MODULE_BLUEPRINT.md - Criado com sucesso no diretório raiz do projeto.*
