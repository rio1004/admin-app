# Category milestone

Category management is available at `/admin/categories`, using the shared admin layout and existing Inertia forms, dialogs, flash notifications, and pagination conventions.

## Setup

```sh
php artisan migrate
npm run build
```

Category permissions were defined in the role milestone. Existing Admin users already receive them. No category data is seeded.

Fields are a required name (up to 120 characters), an automatically generated read-only slug, and timestamps. Duplicate names are allowed. Slugs are backed by a database unique index: `Cafe`, `Cafe` become `cafe`, `cafe-2`; names that produce no slug use `category`, `category-2`. Renaming never changes the slug. Client-supplied slug changes are rejected. Creation retries a unique-key collision caused by another request.

List/search access requires `admin.access` and `categories.view`. Creation, editing, and deletion require their own `categories.create`, `categories.update`, and `categories.delete` permissions. Every endpoint is protected server-side; UI actions and navigation use matching capabilities. Search matches name or slug. Sorting is allowlisted, pagination is server-side at 10 records per page, and links preserve filters and sorting.

## Item deletion integration

The delete action takes a category row lock inside a transaction, then calls `CategoryItemUsage::hasItems()` using the real `items()` relationship. Categories containing items return a validation error and are preserved.

The item milestone completed this integration:

1. `Category::items()` and `Item::category()` are active relationships.
2. The usage check queries `$category->items()->exists()`.
3. `items.category_id` references `categories.id` with `restrictOnDelete()`; categories cannot cascade-delete items.
4. Foreign-key deletion failures become an actionable category validation error.
5. Real item/category restrictions and concurrent insert/delete behavior have MySQL tests.

See [items.md](items.md) for Item CRUD and image lifecycle behavior. The dashboard displays real category totals only to accounts with categories.view.

## Verification

Run `php -d extension=pdo_sqlite vendor/bin/pest` for the SQLite suite when the extension is installed but not enabled globally. `powershell -NoProfile -File scripts/test-mysql.ps1` runs the full suite on a disposable MySQL database and removes that database afterward. Also run Pint, PHPStan, frontend lint, TypeScript, and the production build using the repository scripts.
