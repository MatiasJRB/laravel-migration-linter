# laravel-migration-linter

> Idea / side project. A safety linter for Laravel database migrations.

## The gap

Migration-safety linters exist per ecosystem, but **there is no mature, maintained one for Laravel/PHP**:

- Postgres: `squawk`, `eugene`
- Rails: `strong_migrations`, `online_migrations`
- Django: `django-migration-linter` (~1k★ — proves the demand)
- DB-agnostic: Atlas (`atlas migrate lint`)
- **Laravel/PHP: nothing de-facto.**

## The idea

A Composer package that statically analyzes Laravel migration files (the `Blueprint` API in `up()`) and classifies each change as **safe** (additive, non-locking, backward-compatible) or **unsafe** (drop/rename column, `->change()`, NOT NULL without default, index/FK on a large table, raw data mutation). Conservative by default: anything it can't prove safe is unsafe.

### Use cases
- CI gate on PRs: comment the verdict + the SQL preview.
- Drive a safe auto-apply pipeline (apply additive migrations automatically, gate destructive ones for human review).

### Approach
- Parse the `up()` method (ignore `down()` — never auto-run in prod).
- Rule set over the Blueprint method chain.
- Configurable "hot tables" (large/critical tables where any locking DDL is unsafe).
- Output: JSON + Markdown, exit code for CI.
- Pair with an online-schema-change tool (gh-ost / pt-osc) for the unsafe set.

## Prior art to study
- `django-migration-linter` (Python) — closest analog, good rule taxonomy.
- `strong_migrations` (Ruby) — great docs on *why* each operation is unsafe + the safe alternative.
- Atlas `migrate lint` — analyzer architecture.

## Status
Idea. A working prototype of the classifier exists (≈300 lines of dependency-free PHP). Next: extract into a standalone Composer package with a proper rule registry and tests.
