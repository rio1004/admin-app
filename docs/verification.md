# Verification record

Verified on 2026-10-27 for the completed admin dashboard.

| Check                  | Result                                                                          |
| ---------------------- | ------------------------------------------------------------------------------- |
| `composer ci:check`    | Passed: frontend formatting/lint, TypeScript, Pint, PHPStan, full default suite |
| SQLite suite           | 174 passed, 8 skipped, 1133 assertions                                          |
| Disposable MySQL suite | 179 passed, 3 skipped, 1185 assertions                                          |
| `npm run build`        | Passed                                                                          |
| `git diff --check`     | Passed                                                                          |
| Migration status       | All application migrations applied                                              |
| Public storage link    | Verified target: `storage/app/public`                                           |

The default SQLite suite was run with the installed PDO SQLite extension enabled through a temporary process-only PHP configuration. The original php.ini and `.env` were not changed, and the temporary file was removed. The MySQL script created and removed an isolated `admin_app_rbac_test_*` database; application data was not reset.

Eight dashboard-specific cases verify actual totals, web-guard role counts, five newest items, safe item payloads, individual resource permissions, no hidden resource queries, empty data, and updated counts after record changes. The full suites also cover CRUD authorization/validation, role escalation, final-admin concurrency, restrictive item/category foreign keys, and image lifecycle failures.

The three shared skips are existing disabled Fortify feature tests. SQLite additionally skips five MySQL concurrency cases. There are no newly failing tests or remaining formatting/type-check errors. The existing optional optimized-font fallback notice does not fail the production build.

## UI verification boundary

Responsive layouts, overflow boundaries, shared navigation, pending states, empty states, validation feedback, and keyboard controls were reviewed in source and built successfully. The open Chrome application was readable through its accessibility tree, but automated interaction/capture failed with unavailable coordinate geometry and repeated window capture timeouts. Desktop/mobile screenshots and a complete interactive browser walkthrough were therefore not verified.

Use the manual visual checklist and setup instructions in [setup.md](setup.md) before release. No deployment was performed.
