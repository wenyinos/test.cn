# JumpHost

PHP domain redirection and management system. No framework, no build tools, no test suite, no composer.

## Architecture

- **`config/config.php`** — application bootstrap, not just config. Defines all core functions (`get_db`, `admin_auth`, `role_guard`, `csrf_token`, `e`, etc.). Every page includes this first.
- **`config/ip_api_config.php`** — external IP geolocation API settings.
- **`index.php`** — public entry point. Routes requests by `HTTP_HOST` to domain rules, logs access, renders templates.
- **`admin/`** — admin panel (login, domains, users, logs, settings, media). Each page calls `admin_auth()`.
- **`templates/`** — redirect page templates (`.php`). Discovered dynamically via `get_templates()` which parses `@label` and `@fields` annotations from PHP comment blocks.
- **`install/`** — installer wizard + SQL schema. **Must be deleted after installation.**

## Routing (`.htaccess`)

- Real files/directories served directly
- `/admin` paths served directly (no rewrite)
- `/config` and `/install` blocked via 403
- Everything else → `index.php`

## Conventions

- **XSS output**: always use `e($str)` — never echo unescaped user data.
- **CSRF**: all admin POST forms must include `csrf_token()` field and call `csrf_verify()` on submission.
- **Auth**: admin pages must call `admin_auth()`. Use `role_guard($roles)` for role-restricted pages.
- **Data scoping**: use `domain_owner_where()` and `user_owner_where()` to filter queries by the current user's role (super/agent/personal).
- **DB**: PDO with `FETCH_ASSOC`, prepared statements only. No ORM.
- **Timezone**: `Asia/Shanghai`.
- **Errors**: `display_errors=0`, `log_errors=1` in production.

## Template system

Templates are plain PHP files in `templates/`. **Only whitelisted templates are usable** — `get_templates()` in `config.php` filters to `['img', 'delay', 'click_delay']`. Adding a new template requires updating this whitelist. To add fields or a display label, add annotations in the file header:

```php
/**
 * @label Display Name
 * @fields url,delay,img
 */
```

`_helpers.php` provides `template_value()`, `template_href()`, `template_nav_links()`, `template_icon_data()` etc.

## Gotchas

- `config/config.php` contains live DB credentials — never commit real values.
- The `install/` directory must be removed post-setup; `.htaccess` blocks it with 403 but physical deletion is the real safeguard.
- No automated tests exist. Verify changes manually through the admin panel and public routing.
- `GEMINI.md` is gitignored and contains a project overview; this file replaces it as the canonical agent reference.
