# Admin access

The application uses Laravel, the React starter kit, Inertia, Fortify's web session guard, and Spatie Laravel Permission 7. Users, roles, items, categories, and the dashboard share the existing admin layout. See [setup.md](setup.md) for complete installation and verification instructions.

## First administrator

Run migrations, then `php artisan db:seed --class=RolesAndPermissionsSeeder`. Promote an existing account with `php artisan admin:make your-email@example.com`, or create one interactively with `php artisan admin:make your-email@example.com --create`. The command prompts for a name and confirmed password; repeated promotion never resets existing credentials.

The default seeder creates only the code-defined permissions and protected Admin role. It is idempotent and preserves custom roles and assignments. It does not create users. The legacy `AdminUserSeeder` remains an explicitly invoked, local-only fixture requested during development; it is not first-admin setup, never runs from the default seeder, and does not grant privileges.

## Permission model

Permissions are defined in `App\Support\AdminPermissions`:

| Resource   | Permissions                                                                      |
| ---------- | -------------------------------------------------------------------------------- |
| Workspace  | `admin.access`, `dashboard.view`                                                 |
| Roles      | `roles.view`, `roles.create`, `roles.update`, `roles.delete`                     |
| Users      | `users.view`, `users.create`, `users.update`, `users.delete`                     |
| Items      | `items.view`, `items.create`, `items.update`, `items.delete`                     |
| Categories | `categories.view`, `categories.create`, `categories.update`, `categories.delete` |

Every admin endpoint requires authentication, `admin.access`, and its action permission. Dashboard access also requires `dashboard.view`; each count additionally requires that resource's view permission. Recent items are queried only with `items.view`. Counts reflect current records on each visit and exclude roles outside the web guard.

Role changes are restricted to the defined permission set and the actor's own authority. Admin is readable but cannot be renamed, edited, or deleted. There is no blanket role-name authorization bypass.

User forms and `PUT /admin/users/{user}/roles` require `roles.update` for assignment changes. Ordinary profile writes cannot assign roles. Only existing Admin members with the required permissions may change Admin assignments. User credential changes cannot be used to take over higher-privilege accounts. See [users.md](users.md).

At least one Admin member must remain after demotion or deletion. Permission seeding, role writes, user writes, first-admin promotion, and both deletion paths share a singleton `SELECT ... FOR UPDATE` lock inside transactions. Authorization is rechecked after locking. Direct SQL and maintenance scripts can bypass application invariants and must not replace the provided actions.

User CRUD always blocks self-deletion. The existing profile self-deletion flow retains its password check and final-admin protection. Setup does not enable public registration, email verification, or two-factor authentication.

## Related modules

- [Category validation and deletion rules](categories.md)
- [Items and image lifecycle](items.md)
- [Users and role-assignment protections](users.md)
- [Setup, storage, verification, and limitations](setup.md)
