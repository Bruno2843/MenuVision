---
paths:
  - 'app/Console/Commands/**'
---

# Commands

## SQLite→PostgreSQL export stays manual and non-destructive
`menuvision:export-sqlite-to-postgres` copies local SQLite into PostgreSQL. It is a manual one-off tool: never wire it into boot, deploy, or `migrate`.

Invariants that must hold: default is `--dry-run` (write only with `--write`); it reads `sqlite_local` (config/database.php) so the destination `DB_*` env cannot leak into the source connection; it never updates/deletes/truncates; a same-id same-data row is a match, a same-id different-data row is a conflict that aborts the whole run inside one transaction; it preserves ids so it is idempotent; it only raises identity sequences via `setval(..., max(id), true)`, never lowers them.

Tests live in tests/Feature/ExportSqliteToPostgresTest.php and skip unless EXPORT_TEST_PG_* env vars point at a disposable PostgreSQL (see phpunit.xml).
