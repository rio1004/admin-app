---
name: laravel-admin-dashboard
description: Implement or extend a permission-controlled Laravel admin dashboard using an existing React starter kit and MySQL, including users, roles, items, and categories.
---

# Laravel Admin Dashboard

Use this skill for the admin dashboard described in the development
prompt. Read the repository's AGENTS.md first. Treat the prompt as the
feature specification and AGENTS.md as project-wide guidance.

## Inspect before implementation

Identify:
- Installed Laravel, React, and authorization package versions.
- Authentication and account registration behavior.
- Inertia or other existing request/rendering conventions.
- UI components, navigation, form helpers, and notifications.
- Database schema, migrations, storage disks, and test commands.

Reuse existing implementations where possible. Check official
version-appropriate documentation when package integration is unclear.

## Establish the authorization model

Create a compact resource/action permission matrix from the requested
features. Use it consistently across routes, policies, backend props,
and React action visibility.

Reuse the installed permission system. If none exists, select a
compatible spatie/laravel-permission version.

Keep permissions code-defined and roles administrator-managed.
Restrict role assignment separately from ordinary user editing.

Implement Admin role and final-administrator protections across user
deletion, role reassignment, and role changes. Account for concurrent
mutations when preserving the final administrator.

## Build complete feature slices

Implement each resource through its full path:
schema, model relationships, validation, authorization, controller,
routes, React screens, and relevant tests.

Start with categories before items because item forms depend on
category selection. Use the prompt's field definitions and deletion
rules; do not invent additional modules.

Use server pagination and allowlisted sorting. Avoid loading entire
user or item tables into the browser.

## Handle files deliberately

Represent images as storage paths in the database and expose usable
URLs separately.

Distinguish three edit intentions:
- No replacement or removal: keep the existing image.
- New upload: replace the image.
- Explicit removal: clear the image.

Define how conflicting replacement/removal inputs are handled.
Preserve the old file if the database update fails, clean up new
uploads on failure, and remove obsolete files after a successful
commit. Report cleanup failures without falsely reporting a failed
database save.

## Verify the behavior

Exercise authorized and unauthorized requests, including forged role
assignment fields. Verify slug collision handling, category deletion
restrictions, password updates, and image lifecycle.

Run repository checks appropriate to the changes. Use a disposable
MySQL test database when available; document any database-engine
verification gap.

## Deliver

Summarize implemented behavior, verification results, migration and
storage commands, and explicit first-admin setup.

State unfinished work or environmental blockers clearly. Do not
replace implementation with a plan when the user requested a build.