# Admin dashboard setup and operation

## Requirements and architecture

The installed application uses Laravel 13.38, PHP 8.5, React 19.3, TypeScript, Inertia 3, Fortify, MySQL, Spatie Laravel Permission 7.4, and npm with Vite Plus. Preserve `composer.lock` and `package-lock.json`. PHP needs the normal Laravel extensions plus Fileinfo and GD for upload validation and image tests. Node 22 matches the repository CI configuration.

Laravel owns routing and authorization; React uses the existing layouts and components. There is no separate API/SPA or new state library.

## Fresh local setup

```sh
composer install
npm ci
```

Create `.env` from `.env.example` only if no environment file exists. Set the MySQL database connection, database name, and your own credentials. Never overwrite an existing environment file or commit credentials. Set `APP_URL` to the exact origin used by your browser, including the port; generated public image URLs use this value.

For example, when serving on port 8001, use `APP_URL=http://127.0.0.1:8001` and `DB_CONNECTION=mysql`. Create the application database in MySQL first, then set its name through `DB_DATABASE`.

Generate an application key only for a fresh installation without an existing key:

```sh
php artisan key:generate
php artisan migrate
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan storage:link
```

Migrations add the permission tables, the shared administrator lock row, categories with unique slugs, and items with unique slugs and a restrictive category foreign key. Existing users already have unique emails. Use normal forward migrations against application data; never use destructive database resets to install this dashboard.

Permission seeding is safe to repeat. It preserves custom roles and assignments and creates no users or passwords. Existing application data is not replaced. If configuration was cached before environment changes, run `php artisan config:clear`.

## Explicit first administrator

To promote an existing account without resetting its password:

```sh
php artisan admin:make your-email@example.com
```

To create the first account interactively:

```sh
php artisan admin:make your-email@example.com --create
```

Enter the requested name and a confirmed password. The first-admin command requires at least 12 characters, mixed case, a number, and a symbol for a new account. It does not accept passwords as command arguments or print them. Repeated promotion is idempotent.

There is no default administrator password. The legacy development fixture `AdminUserSeeder` is local-only, explicit, does not grant privileges, and does not overwrite existing credentials; do not use it for production provisioning.

## Build and preview

```sh
npm run build
php artisan serve --host=127.0.0.1 --port=8001
```

Open `/login`, sign in, then visit `/dashboard`. Choose an available local port and keep `APP_URL` aligned. For live frontend development, run `npm run dev` in another terminal. The existing `composer dev` workflow is also available.

Admin pages are `/admin/users`, `/admin/roles`, `/admin/items`, and `/admin/categories`. Navigation and actions reflect the signed-in account's permissions. Create a category before creating items. New users need explicit roles/permissions before they can access the admin workspace.

The dashboard shows current Users, web-guard Roles, Items, and Categories totals only for resources the user may view. It fetches at most five newest items with eager-loaded categories. Hidden counts and recent-item data are omitted server-side. Counts refresh on visits; no polling or real-time service is installed.

## Storage

Item images use the configured `public` disk explicitly, regardless of the default disk. The default local root is `storage/app/public`, served through `public/storage`. If `storage:link` reports an existing link, verify that it points to that root rather than replacing it blindly. Ensure Laravel can write to `storage` and `bootstrap/cache`.

Images accept JPEG, PNG, and WebP content, up to 5 MB and 4096 by 4096 pixels. Paths are generated server-side. Keep/replace/remove behavior, rollback cleanup, and cleanup logging are documented in [items.md](items.md). Image files are publicly accessible by URL; this is not private document storage.

Database changes and old-file cleanup are coordinated with transaction commits. Failed cleanup logs `Item image cleanup failed.` for an operator to retry; there is no persistent cleanup retry queue. Coordinate database and image backups when restoring data.

## Verification

```sh
composer ci:check
npm run build
```

`composer ci:check` runs frontend formatting/lint, TypeScript, Pint, PHPStan, and the full default test suite. Individual checks are `npm run check`, `npm run types:check`, `composer lint:check`, and `composer types:check`.

Tests default to isolated in-memory SQLite and use fake storage for uploads. If the local SQLite PDO extension is installed but disabled, run the tests without changing global PHP settings:

```sh
php -d extension=pdo_sqlite vendor/bin/pest
```

On Windows, verify MySQL using:

```powershell
powershell -NoProfile -File scripts/test-mysql.ps1
```

The script creates a randomly named `admin_app_rbac_test_*` database, verifies isolation, runs the full suite, removes only that disposable database, and restores process environment variables. Its MySQL user needs permission to create/drop the test database. Never point feature tests at application data: they use `RefreshDatabase`. The MySQL integration suite covers concurrent admin demotion/deletion and category deletion versus item insertion. These five MySQL-only cases skip on SQLite. Three existing Fortify feature tests skip because those optional features are disabled.

## Consistency review and limitations

All admin pages reuse the page container, headings, cards, labeled controls, validation alerts, pending-submit states, and empty states. Tables scroll inside their cards on small screens; form grids and dashboard cards collapse to one column. The shared sidebar collapses, highlights the active module on nested pages, and closes after mobile navigation. Inertia navigation exposes a pending status and retains its existing progress bar. Password visibility controls are keyboard-operable.

Window capture failed during the automated desktop/mobile browser review (`window capture timed out` after retry). Responsive source checks, production builds, and backend tests were completed, but screenshot-based visual verification remains a manual follow-up. Check desktop and a 375 px mobile viewport, each CRUD form, validation errors, long names, table scrolling, sidebar open/close, light/dark appearance, and empty collections before release. No screenshot verification is claimed.

Other boundaries:

- Public registration, email verification, and two-factor authentication remain disabled as configured in the starter kit.
- User CRUD prevents self-deletion; the existing profile self-deletion flow retains its password check and final-admin protection.
- Admin password changes rotate remember tokens and invalidate reset tokens; existing authenticated sessions are not forcibly logged out.
- Category selectors and role selectors currently load their available option sets; very large option sets may need a searchable server-paginated selector.
- There is no invitation-email flow, activity audit log, private image access, financial reporting, or background image-cleanup retry worker.
- The optional optimized-font fallback package is not installed; the build may report the existing non-blocking font fallback notice.
- No deployment or publishing was performed.

See [admin-access.md](admin-access.md), [categories.md](categories.md), [items.md](items.md), and [users.md](users.md) for module behavior and invariants.

See [verification.md](verification.md) for the recorded check results and browser-review boundary.
