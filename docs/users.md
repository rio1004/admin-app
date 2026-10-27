# User milestone

User CRUD is available at `/admin/users` using the shared admin layout, Inertia forms, inline validation, flash messages, and destructive confirmation dialogs. No schema changes, seeds, or migrations are required: the existing users table already has a unique email index, and role tables were added in the access milestone.

## Fields and listing

- Required name and unique email, up to 255 characters, using the existing profile validation rules. Email is normalized to lowercase to match Fortify behavior. Update uniqueness ignores only the current record.
- Required confirmed password on creation, using the existing application password rules. On update, a blank or omitted password preserves the hash; a supplied password must be confirmed and is hashed through the existing model cast.
- Optional role assignments, handled separately from ordinary user fields. Omitting `roles` preserves assignments; an authorized empty array removes them, subject to final-administrator protection.

Search matches name or email. Role filtering and allowlisted sorting run on the server. Lists paginate at 10 records per page, eager-load role/permission relationships used by authorization, and preserve query parameters. Payloads explicitly select identity and role names/IDs; password hashes, tokens, secrets, and permission relationships are never returned.

## Authorization

All endpoints require authentication, `admin.access`, and the matching `users.view`, `users.create`, `users.update`, or `users.delete` permission. Navigation and action visibility use the same checks.

Role assignment changes additionally require `roles.update`, including when creating a user. Submitting a role field without this permission is rejected even if it is an empty array. Direct permission grants and authentication internals are not accepted. The existing dedicated role-assignment endpoint remains protected by `roles.update`.

Managers cannot edit or delete higher-privilege accounts. They must hold the target's existing permissions, and only Admin members with the required action permissions may manage Admin accounts. This prevents credential changes from bypassing role-assignment restrictions. Role selection only enables grants within the actor's authority; server enforcement remains mandatory.

Account fields and role assignments are written in one transaction using the existing shared access lock. Authorization is rechecked after locking. A rejected role change rolls back name, email, password, and token mutations. Admin has no blanket role-name authorization bypass.

## Deletion and authentication

Self-deletion through User CRUD is always forbidden, even when another administrator exists. The existing profile self-deletion flow remains available with its password check and final-admin protection. At least one Admin member must remain after account deletion or role reassignment. The same transaction lock protects role writes, user writes, and both deletion paths.

Deleted accounts have role/direct-permission pivots, database sessions, and password-reset tokens removed. Changed emails clear verification and old reset tokens, matching the profile's verification behavior. Admin password updates invalidate outstanding reset tokens and rotate the remember token. Login, logout, password reset, registration configuration, and existing profile/security routes are retained. Newly created accounts use normal Fortify login; dashboard access still requires explicit permissions. Existing authenticated sessions are not forcibly logged out by an admin password update.

No invitation emails, new default accounts, or default passwords are introduced. Use the explicit `admin:make` command for first-admin provisioning as documented in [admin-access.md](admin-access.md).

## Verification

```sh
php -d extension=pdo_sqlite vendor/bin/pest
powershell -NoProfile -File scripts/test-mysql.ps1
composer lint:check
composer types:check
npm run check
npm run types:check
npm run build
```

The MySQL script uses a disposable database. Tests cover successful CRUD, authentication/action failures, email uniqueness, password preservation/replacement, role assignment restrictions, credential takeover attempts, atomic rollback, self-deletion blocking, final-admin protection, safe payloads, and existing Fortify login. MySQL subprocess tests also exercise simultaneous administrator demotion, profile self-deletion, and administrators trying to delete one another.

The dashboard now includes real permission-aware user totals. Deployment is not included.
