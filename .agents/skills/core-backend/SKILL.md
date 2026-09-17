---
name: estrutura
description: Guia para criar sistemas, módulos e páginas dentro do WP Masters Core — um MVC PHP sem framework. Use quando for criar rotas, controllers, models, views, middlewares, helpers, serviços, CLI scripts ou testes nesta base de código.
license: proprietary
---

# SKILL — Criando Sistemas no WP Masters Core (MVC PHP sem framework)

Este documento ensina a construir qualquer funcionalidade nova seguindo **exatamente** a estrutura de pastas e as convenções do core atual. Antes de criar qualquer arquivo, leia este guia e o `AGENTS.md` na raiz.

## 1. Arquitetura em uma frase

Todas as requisições entram por **`public/index.php`** (front controller) → **`src/bootstrap.php`** (autoload, erros, DB, aliases globais) → **`src/config.php`** (`.env`, constantes, sessão) → **`src/Routes/`** → **`App\Core\Router`** executa middlewares globais → resolve a rota para **Controller → Model → View**.

```
public/index.php
  └─ src/bootstrap.php   (autoload vendor, error handlers, PDO, class_alias globais)
       └─ src/config.php (carrega .env, define ROOT_DIR/SITE_URL/SYSTEM_VERSION, sessão)
            └─ src/Routes/web.php + src/Routes/api.php   (arrays de rotas)
                 └─ App\Core\Router::run()
                      ├─ Middlewares globais (SecurityHeaders, Inactivity, CSRF)
                      ├─ Middlewares da rota (Auth, Admin, RateLimit)
                      └─ Controller → Model (Repository/PDO) → View (layouts/pages/components)
```

## 2. Mapa de pastas — o que vai em cada uma

### `src/` — raiz da aplicação (PSR-4: namespace `App\`)

| Pasta | O que vai lá | Exemplos reais |
|---|---|---|
| `src/Core/` | **Núcleo do framework.** Classes de infraestrutura reutilizáveis: Router, Database (singleton PDO), View (engine), Controller (base abstrata), Repository (base de models), Auth, CSRF, Cache, Logger, RateLimiter, TOTP, Pagination, MiddlewareInterface. **Não coloque lógica de negócio aqui.** | `Router.php`, `Database.php`, `Controller.php`, `Repository.php`, `Auth.php`, `CSRF.php`, `Cache.php` |
| `src/Core/Auth/` | Sub-núcleo de autorização. | `PermissionManager.php` (RBAC) |
| `src/Models/` | **Acesso a dados.** Cada model estende `App\Core\Repository`, declara `protected string $table` (sempre prefixo `cp_`) e expõe métodos com prepared statements. Nada de SQL cru em controllers/views. | `UserModel`, `LogModel`, `NotificationModel`, `SettingModel`, `DocArticle` (dados estáticos) |
| `src/Controllers/` | **HTTP.** Um controller por módulo, estende `App\Core\Controller`. Usa `$this->render()` (resolve `Views/pages/<modulo>/<view>.php`), `$this->jsonResponse()`, `Controller::asset()`. Orquestra models/services e retorna; não escreve SQL. | `AuthController`, `UsersController`, `DocsController`, `CronController` |
| `src/Views/` | **Apresentação.** PHP puro (sem template engine). Três subpastas: | |
| `src/Views/layouts/` | Shell da página: `header.php`/`footer.php` (painel admin) e `public_header.php`/`public_footer.php` (páginas públicas). O header carrega CSS/JS, sidebar, topbar, CSRF meta. | `header.php`, `footer.php` |
| `src/Views/pages/<modulo>/` | **Uma pasta por módulo**, nome = controller sem sufixo `Controller` em minúsculas. Um arquivo por ação renderizada (`index.php`, `show.php`, `not_found.php`…). | `pages/users/index.php`, `pages/docs/show.php`, `pages/auth/login.php` |
| `src/Views/components/` | Pedaços reutilizáveis renderizados com `View::component('nome', $data)`. | `page_header.php`, `stat_card.php`, `empty_state.php`, `lightbox.php` |
| `src/Routes/` | **Definição de rotas.** Arquivos que retornam `array` de rotas: `[METHOD, '/path/{param}', [Controller::class, 'metodo'], [middlewares]]`. `web.php` = páginas, `api.php` = endpoints `/api/*`. | `web.php`, `api.php` |
| `src/Middlewares/` | **Filtros por rota.** Cada um implementa `handle(): bool` (retorna `false` para bloquear/redirecionar). Globais ficam hardcoded no `Router::run()`; por-rota são declaradas no array da rota. | `AuthMiddleware`, `AdminMiddleware`, `RateLimitMiddleware`, `CSRFMiddleware` |
| `src/Helpers/` | **Classes utilitárias sem estado** (ou com estado leve), chamadas de qualquer camada. `SecurityHelper` é carregada no bootstrap e expõe funções globais `e()`/`sanitize_input()`. | `SecurityHelper`, `Crypto`, `ImageHelper`, `Nonce`, `Recaptcha`, `EvolutionApi`, `ThemeHelper`, `SitemapGenerator`, `Mailer`, `Notification` |
| `src/Services/` | **Lógica de negócio complexa** desacoplada de HTTP (filas, integrações). Controllers chamam services; services não conhecem `$_POST`. | `NotificationQueue`, `Queue/` (QueueFactory, QueueWorker, drivers) |
| `src/DTO/` | **Objetos de transferência de dados** — `final readonly class` com propriedades tipadas e factory estática que aplica validação/escape. | `SearchResultDTO` |

### Raiz

| Pasta/Arquivo | O que vai lá |
|---|---|
| `public/` | **Document root do servidor.** Só o que deve ser acessível pelo navegador: `index.php` (front controller), `.htaccess`, assets, uploads, sitemap. |
| `public/assets/css/` | `gentelella.css` (base), `theme/*.css` (temas), `custom.css`, `src/*.css` (fontes de build) → `dist/*.min.css` (bundle), `modules/*.min.css` (**editados direto**, sem fonte) e `manifest.json` (hash de cache-busting). |
| `public/assets/js/` | `src/*.js` (fontes) → `dist/admin-bundle.min.js` (bundle), `vendor/` (lucide etc). |
| `public/assets/vendor/` | Bibliotecas de terceiros (lucide, etc). |
| `bin/` | **CLI entrypoints.** Scripts executáveis por cron/supervisord/manualmente. **Sempre carregam `src/bootstrap.php` com `define('CLI_MODE', true)` antes.** Nunca recriem `includes/`. | `queue-work.php`, `cron-queue.php`, `cron-backup.php`, `cron-sitemap.php`, `queue-restart.php`, `build-assets.php` |
| `database/` | Schema e migrações: `migrations.php` (array id-keyed, aplicado uma vez, rastreado em `cp_migrations`) e `core_database.sql`. |
| `storage/` | **Dados de runtime** (gitignored): `logs/`, `cache/`, `backups/`. Acesse via `Controller::storagePath()` ou `ROOT_PATH . '/storage/…'`. |
| `tests/` | Testes PHPUnit: `tests/Unit/…`. Config em `phpunit.xml` na raiz. |
| `.env` / `.env.example` | Configuração de ambiente (gitignored; `.env.example` é a referência). |
| `vendor/` | Dependências Composer (não versionar). |

## 3. Fluxo completo: criar um módulo novo (passo a passo)

Exemplo: módulo **"Relatórios"** (`/reports`).

1. **Model** — `src/Models/ReportModel.php`:
   ```php
   <?php
   declare(strict_types=1);
   namespace App\Models;

   use App\Core\Repository;

   class ReportModel extends Repository {
       protected string $table = 'cp_reports';

       public function getMonthly(int $year): array {
           $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE YEAR(created_at) = ?");
           $stmt->execute([$year]);
           return $stmt->fetchAll();
       }
   }
   ```

2. **Controller** — `src/Controllers/ReportsController.php`, estendendo `App\Core\Controller`:
   ```php
   <?php
   declare(strict_types=1);
   namespace App\Controllers;

   use App\Core\Controller;
   use App\Models\ReportModel;

   class ReportsController extends Controller {
       public function index(): void {
           $model = new ReportModel(\App\Core\Database::getInstance());
           $this->render('index', ['reports' => $model->getMonthly((int)date('Y'))]);
       }

       public function export(): void {
           // … $this->jsonResponse([...]) ou downloadPrivateFile()
       }
   }
   ```

3. **View** — `src/Views/pages/reports/index.php` (pasta = nome do controller sem sufixo). Escapar sempre com `e()`:
   ```php
   <?php foreach ($reports as $r): ?>
   <div class="card shadow-sm"><h5><?php echo e($r['title']); ?></h5></div>
   <?php endforeach; ?>
   ```

4. **Rotas** — em `src/Routes/web.php` (página) e `src/Routes/api.php` (endpoint), seguindo o padrão:
   ```php
   ['GET', '/reports', [\App\Controllers\ReportsController::class, 'index'], [$auth, $admin]],
   ['POST', '/api/reports/export', [\App\Controllers\ReportsController::class, 'export'], [$auth, $admin]],
   ```

5. **Sidebar + título** — adicionar link em `src/Views/layouts/header.php` (com `Auth::can('…')` se houver permissão) e entrada em `$page_titles`.

6. **CSS do módulo** — se precisar, criar `public/assets/css/modules/reports.min.css` (**minificado, editado direto**) e registrar o hash no `public/assets/css/manifest.json`:
   ```bash
   php -r "echo substr(md5_file('public/assets/css/modules/reports.min.css'), 0, 8), PHP_EOL;"
   ```

7. **Testes (opcional mas recomendado)** — `tests/Unit/…` com PHPUnit; rodar `vendor/bin/phpunit`.

8. **Verificação** — `composer check` (lint + PHPStan nível 5 do projeto) **precisa passar**; `php -l` em cada view.

## 4. Convenções obrigatórias (baseadas no core real)

- **`declare(strict_types=1);`** na primeira linha de **todo** arquivo PHP.
- **PSR-4**: namespace `App\` → `src/` (autoload do Composer). Nada de autoloader manual.
- Nomes de classe em inglês (PascalCase); UI, comentários e colunas em português; mensagens de commit em inglês.
- **Controllers estendem `App\Core\Controller`**; **Models estendem `App\Core\Repository`** com `$table` prefixada `cp_`.
- **SQL somente via prepared statements** (PDO). Colunas usadas em `ORDER BY`/`LIMIT` devem ser validadas contra allowlist (ver `UserModel::getPaginated()`).
- **Senhas e Criptografia**: Nunca use `password_hash()` com `PASSWORD_DEFAULT` diretamente nem algoritmos legados (MD5, SHA1). Use sempre `SecurityHelper::hashPassword($senha)` (que utiliza **Argon2id** com memory_cost 64MB, time_cost 4, threads 1) e `SecurityHelper::verifyPassword()`. No login, utilize `SecurityHelper::passwordNeedsRehash()` para migração transparente de hashes legados.
- **Escape em views** com `e()` / `sanitize_input()` (globais do `SecurityHelper`). Nunca interpolar dados de usuário cru em `innerHTML`/`echo`.
- **CSRF**: formulários enviam `csrf_token` oculto; fetches JS enviam `window.CSRF_TOKEN` (header `X-CSRF-Token` ou campo no body). O `CSRFMiddleware` valida todo POST exceto `/api/webhooks/` e `/api/auth/check-availability`.
- **Auditoria**: toda ação relevante registra `\App\Core\Logger::log($action, $description)` (prefixos: `login`, `create_`, `edit_`, `delete_`, `save_`, `security_`…).
- **Assets**: em views usar `\App\Core\Controller::asset('/assets/css/…')` (cache-busting automático).
- **Sessão/banco**: `Auth::isLoggedIn()`, `Auth::can('permissao')`; `App\Core\Database::getInstance()` ou alias global `DB`; `Cache::get/set`.
- **Entradas CLI/cron**: carregar `src/bootstrap.php` com `define('CLI_MODE', true)` — nunca recriar `includes/`.

## 5. Onde cada tipo de arquivo NÃO deve ir

| Tipo de arquivo | NUNCA em |
|---|---|
| SQL cru em controllers/views | `src/Controllers`, `src/Views` |
| Lógica de negócio em controllers | `src/Controllers` (use `src/Services`) |
| Config/secrets hardcoded | qualquer lugar (use `.env`) |
| Scripts CLI | `public/` (vão em `bin/`) |
| Arquivos de runtime/upload | fora de `storage/` ou `public/uploads/` |
| Template engine/Laravel/Symfony | **não introduzir** — o projeto é PHP puro sem framework |

## 6. Tema Gentelella — CSS Variables e Dark Mode

O projeto usa **Gentelella** como tema base (`public/assets/css/gentelella.css`). Todas as cores, fontes e sombras são definidas como CSS custom properties em `:root`. Nunca use cores hardcoded — sempre referencie as variáveis.

### Variáveis principais (`:root`)

```css
--primary: #1abb9c;        /* Cor principal (teal) */
--primary-lt: #1abb9c0f;   /* Versão transparente */
--primary-dk: #169f85;     /* Versão escura */
--body-bg: #f5f7fb;        /* Fundo da página */
--bg-surface: #fff;         /* Fundo de cards */
--border-color: #e6e7eb;    /* Bordas */
--text: #1e2633;            /* Texto principal */
--text-secondary: #626d7d;  /* Texto secundário */
--text-muted: #7e8896;      /* Texto desabilitado */
--radius: 6px;              /* Border radius */
--shadow: #1e26330a 0 2px 4px 0;
--shadow-card: 0 0 0 1px var(--border-translucent), #1e26330a 0 2px 4px 0;
--font: "Inter", -apple-system, BlinkMacSystemFont, sans-serif;
```

### Cores de semáforo

```css
--blue: #066fd1;    --blue-lt: #066fd10f;
--green: #2fb344;   --green-lt: #2fb3440f;
--yellow: #f59f00;  --yellow-lt: #f59f000f;
--red: #d63939;     --red-lt: #d639390f;
--purple: #ae3ec9;  --purple-lt: #ae3ec90f;
```

### Dark Mode

Ativado via atributo `data-theme="dark"` no `<html>`. Sobrescreve variáveis:

```css
[data-theme=dark] {
    --body-bg: #0f1623;
    --bg-surface: #1a2332;
    --border-color: #ffffff14;
    --text: #e6ebf2;
    --text-muted: #8a93a3;
    --shadow: #0006 0 2px 4px 0;
}
```

**Toggle no JS** (`common.js`):
```js
function toggleTheme() {
  var root = document.documentElement;
  root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
  localStorage.setItem('pq_theme', root.dataset.theme);
}
```

**Ícones de tema** — dois SVGs no botão `.theme-toggle`: `.theme-icon-light` e `.theme-icon-dark`. Um é ocultado via CSS dependendo do tema atual.

### Regra de ouro

- **Sempre use** `var(--primary)`, `var(--red)`, etc. — nunca `#1abb9c` direto
- **Classes utilitárias**: `.text-blue`, `.text-red`, `.text-emerald`, `.text-amber`, `.text-purple` (em `quebras.min.css`)
- **Backgrounds transparentes**: usar a variável `-lt` (ex: `var(--primary-lt)`)

## 7. Layouts — Autenticado vs Público

O projeto tem dois pares de layout:

### Layout autenticado (admin)
- **`src/Views/layouts/header.php`** — Sidebar + topbar + busca + avatar dropdown. Espera: `$title`, `$user`, `$csrfToken`, `$page`, `$content`.
- **`src/Views/layouts/footer.php`** — Toast container, `window.ME`, `window.CSRF_TOKEN`, `window.SETTINGS`, carrega `common.js` + `apexcharts` + JS da página.

### Layout público (login, 404)
- **`src/Views/layouts/public_header.php`** — `<head>` mínimo (fonts, CSS, CSRF meta). Sem sidebar, sem topbar. Espera: `$title`, `$content`.
- **`src/Views/layouts/public_footer.php`** — Apenas `window.CSRF_TOKEN`. Sem toast, sem scripts.

### Como renderizar

```php
// Página autenticada (Controller)
$this->render('usuarios/index', [
    'title' => 'Usuarios',
    'user' => Auth::user(),
    'csrfToken' => CSRF::token(),
    'page' => 'usuarios',   // controla qual .js carregar
], 'header');

// Página pública (Controller)
$this->render('auth/login', ['title' => 'Entrar'], 'public_header');
```

### Tela de Login

A view `src/Views/pages/auth/login.php` usa um layout split:
- **Lado esquerdo** (`.auth-brand`) — painel da marca com logo, título e features
- **Lado direito** (`.auth-panel`) — formulário card com usuário, senha, eye toggle, erro e botão

O formulário faz `fetch()` direto para `POST /api/auth/login` com JSON body + header `X-CSRF-Token`. Suporta 3 mensagens de erro: campos vazios, credenciais inválidas, erro de rede.

## 8. Toast — Notificações Flutuantes

Container fixo no canto inferior direito. Chamada global: `toast(msg, tipo)`.

### HTML (em `footer.php`)
```html
<div class="toast-host">
  <div id="toast" class="toast toast-success"></div>
</div>
```

### JS (`common.js`)
```js
function toast(msg, kind) {
  var t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'toast ' + (kind === 'error' ? 'toast-error' : 'toast-success');
  t.classList.remove('show');
  void t.offsetWidth; // force reflow
  t.classList.add('show');
  clearTimeout(toast._timer);
  toast._timer = setTimeout(function () { t.classList.remove('show'); }, 2800);
}
```

### Tipos suportados
- `toast('Salvo!', 'success')` — borda verde (padrão)
- `toast('Erro', 'error')` — borda vermelha
- `toast('Atenção', 'warning')` — borda amarela

### CSS (em `gentelella.css`)
```css
.toast-host { position:fixed; bottom:16px; right:16px; z-index:1000; pointer-events:none; }
.toast { opacity:0; transform:translateY(8px); transition:opacity .18s, transform .18s; }
.toast.show { opacity:1; transform:translateY(0); }
.toast-success { border-left:3px solid var(--green); }
.toast-error   { border-left:3px solid var(--red); }
```

## 9. Paginação Client-Side

Todas as tabelas usam paginação 100% no cliente. O tamanho da página é configurável via `/configuracoes` (armazenado em `cp_settings`).

### Estado global (`common.js`)
```js
var PAGE_SIZE = (window.SETTINGS && parseInt(window.SETTINGS.per_page, 10)) || 10;
var PAGINATION = {}; // { chave: paginaAtual }
```

### Função `paginated(key, items)`
Fatiça o array e retorna `{ page, total, current, pages }`:
```js
var pg = paginated('hist', rows);
// pg.page   → array da página atual
// pg.total  → total de itens
// pg.current → página atual (1-based)
// pg.pages  → total de páginas
```

### Função `renderPagination(key, total, current, pages, onPage)`
Renderiza os botões de paginação num container `.pagination-footer`:
```js
renderPagination('histPagination', pg.total, pg.current, pg.pages, function (p) {
  PAGINATION.hist = p;    // salva nova página
  renderHistorico();      // re-renderiza
});
```

### Padrão de uso num módulo
```js
function renderHistorico() {
  api('GET', '/api/historico').then(function (rows) {
    var pg = paginated('hist', rows);
    document.getElementById('histBody').innerHTML = pg.page.map(function (q) {
      // ... renderiza linhas ...
    }).join('');
    renderPagination('histPagination', pg.total, pg.current, pg.pages, function (p) {
      PAGINATION.hist = p;
      renderHistorico();
    });
  });
}
```

### HTML necessário na view
```html
<div class="pagination-footer" id="histPagination"></div>
```

## 10. Busca Global com Destaque

Busca em tempo real via input no topbar. Results aparecem num painel dropdown agrupados por módulo.

### Configuração dos grupos (`common.js`)
```js
var SEARCH_GROUPS = [
  { key: 'quebras',   label: 'Quebras',   icon: 'Q', cls: 'search-group-teal' },
  { key: 'historico', label: 'Historico', icon: 'H', cls: 'search-group-blue' },
  { key: 'auditoria', label: 'Auditoria', icon: 'A', cls: 'search-group-purple' },
  { key: 'usuarios',  label: 'Usuarios',  icon: 'U', cls: 'search-group-green' }
];
```

### Fluxo
1. Usuário digita no `#globalSearch` (debounce 250ms)
2. `GET /api/search?q=...` retorna resultados agrupados
3. Painel `.search-panel` é populado com links
4. Cada link tem `?d=<id>&q=<query>` na URL

### Destaque do item clicado (`highlightFromUrl`)
Quando a página carrega com `?d=123&q=termo` na URL:
1. `highlightFromUrl('#histBody tr', 'id')` busca a row com `data-id="123"`
2. Adiciona classe `.row-highlight` (animação de flash verde, 3.2s)
3. Faz `scrollIntoView({ behavior: 'smooth', block: 'center' })`
4. Mostra barra de aviso `.search-notice` no topo da página

### CSS do destaque
```css
tr.row-highlight {
  outline: 2px solid var(--primary);
  background: var(--primary-lt) !important;
  animation: rowFlash 3.2s ease;
}
@keyframes rowFlash {
  0%, 70% { background: var(--primary-lt); }
  100%     { background: transparent; }
}
```

## 11. Dropdown de Perfil (Avatar no Topbar)

Avatar clicável no topbar que abre menu com "Perfil" e "Sair".

### HTML (`header.php`)
```html
<div class="tb-avatar-wrap">
  <div class="tb-avatar" id="uAvatarTop">U</div>
  <div class="tb-dropdown" id="userDropdown" hidden>
    <div class="tb-dropdown-header" id="dropdownUserName">Nome</div>
    <button class="tb-dropdown-item" onclick="window.location.href='/'">
      <!-- SVG icon --> Perfil
    </button>
    <div class="tb-dropdown-divider"></div>
    <button class="tb-dropdown-item tb-dropdown-danger" onclick="logout()">
      <!-- SVG icon --> Sair
    </button>
  </div>
</div>
```

### JS toggle (`common.js`)
```js
avatarTop.addEventListener('click', function (e) {
  e.stopPropagation();
  userDropdown.hidden = !userDropdown.hidden;
});
document.addEventListener('click', function () {
  userDropdown.hidden = true;  // fecha ao clicar fora
});
```

### CSS (`quebras.min.css`)
```css
.tb-avatar-wrap { position: relative; }
.tb-dropdown {
  position: absolute; top: calc(100% + 8px); right: 0;
  min-width: 160px; background: var(--bg-surface);
  border: 1px solid var(--border-color); border-radius: var(--radius);
  box-shadow: var(--shadow-card); z-index: 200; padding: 4px 0;
}
.tb-dropdown-item:hover { background: var(--body-bg); }
.tb-dropdown-danger { color: var(--red); }
.tb-dropdown-danger:hover { background: var(--red-lt); }
```

## 12. Checklist antes de entregar

- [ ] `declare(strict_types=1)` em todo PHP novo
- [ ] Rotas registradas em `web.php` e/ou `api.php` com middlewares corretos (rota pública X auth X admin)
- [ ] View na pasta `pages/<modulo>/` e link/título no `header.php`
- [ ] CSS de módulo (se houver) minificado + hash no `manifest.json`
- [ ] Nenhum `innerHTML` com dado de usuário sem escape; `e()` em todas as saídas
- [ ] SQL 100% preparado (PDO) e sem interpolação de input
- [ ] `composer check` verde (lint + PHPStan); `php -l` nas views editadas
- [ ] CLI novo passa por `src/bootstrap.php` com `CLI_MODE`