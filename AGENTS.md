# AGENTS.md

## Project

MVC PHP sem framework + MariaDB. Namespace PSR-4: `App\` → `src/`.

## Request flow

```
public/index.php → src/bootstrap.php (autoload, error handlers, DB, class_alias)
  → src/config.php (.env, constants, session)
    → src/Routes/web.php + api.php → Router::run() → Middlewares → Controller → Model → View
```

## Critical conventions

- **Every PHP file**: first line `declare(strict_types=1);`
- **SQL**: prepared statements only. Never raw SQL in controllers/views.
- **Passwords**: `SecurityHelper::hashPassword()` (Argon2id). Never `password_hash()`.
- **Escape output**: `e()` / `sanitize_input()` in all views. Never echo unescaped user data.
- **CSRF**: forms → `csrf_token` field; JS fetch → `X-CSRF-Token` header.
- **Table prefix**: all models use `cp_` prefix (e.g. `cp_users`, `cp_settings`).
- **CLI scripts**: `define('CLI_MODE', true);` before requiring `src/bootstrap.php`.
- **No framework**: pure PHP. No Laravel, Symfony, Blade, Twig, etc.
- **No includes/**: there is no `includes/` directory. Use `src/bootstrap.php`.

## Routes

`src/Routes/web.php` and `src/Routes/api.php` return arrays:
```php
['METHOD', '/path/{param}', [Controller::class, 'method'], [Middleware::class]]
```

Global CSRF + security headers hardcoded in `Router::run()`. CSRF exemptions listed in `Router::CSRF_EXEMPT`.

## Models

Extend `App\Core\Repository`. Declare `protected string $table` with `cp_` prefix. Use `$this->db->prepare()`.

## Controllers

Extend `App\Core\Controller`. Methods: `$this->render()`, `$this->jsonResponse()`, `$this->jsonOk()`, `$this->jsonError()`, `$this->redirect()`, `Controller::asset()`.

## Views

- Layouts: `src/Views/layouts/header.php` + `footer.php` (admin), `public_header.php` + `public_footer.php` (public)
- Pages: `src/Views/pages/<module>/` (folder name = controller name lowercase, without `Controller` suffix)
- Components: `src/Views/components/`

## Commands

```bash
composer lint          # PHP syntax check (src, public, bin)
vendor/bin/phpunit     # Run tests (tests/Unit/)
composer migrate       # Run pending migrations (bin/migrate.php)
composer seed          # Seed data
composer build-assets  # Compile & minify CSS/JS bundles (bin/build_assets.php)
```

PHPStan (level 5):
```bash
vendor/bin/phpstan analyse
```

## PHPStan paths

Only analyzes: `src/Core`, `src/Controllers`, `src/Models`, `src/Middlewares`, `src/Helpers`. See `phpstan.neon`.

## Gotchas

- `src/Helpers/globals.php` defines `e()`, `sanitize_input()`, `csrf_field()` globally — loaded by both bootstrap and phpstan-bootstrap.
- `Controller::asset()` reads `public/assets/css/manifest.json` for cache-busting.
- Asset compilation: `composer build-assets` (ou `php bin/build_assets.php`) utiliza `matthiasmullie/minify` para compilar e minificar `gentelella.css`, `table.min.css`, `docs.min.css` e `reports.min.css` em `public/assets/css/utils.min.css` e atualiza os hashes do `manifest.json` automaticamente.
- `Router` matches routes with regex. `{param}` captures become controller method arguments.
- Database singleton: `Database::getInstance()` or global alias `DB`.
- `bin/migrate.php` creates `cp_migrations` table on first run.
- CSS: use CSS variables (`var(--primary)`, etc.), never hardcoded colors. Theme: Gentelella with dark mode support.
- Session lifetime enforced via `$_SESSION['last_activity']` in config.php.
- Error handler in bootstrap.php returns JSON for API, hides details in production.

## Reference

Full skill guide: `.agents/skills/core-backend/SKILL.md`
