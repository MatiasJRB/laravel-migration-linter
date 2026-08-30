# Laravel Migration Linter

[![CI](https://github.com/MatiasJRB/laravel-migration-linter/actions/workflows/ci.yml/badge.svg)](https://github.com/MatiasJRB/laravel-migration-linter/actions/workflows/ci.yml)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

Framework-independent static safety analysis for Laravel database migrations, designed especially for established applications that cannot upgrade their framework tooling just to review a migration. It inspects the `up()` method without booting Laravel or connecting to a database, then reports changes that are destructive, blocking, or unsafe to deploy alongside old application code.

```text
UNSAFE
  ERROR database/migrations/2026_08_09_add_status.php:13 [required-column-without-default] table=orders Adding a required column without a default is not backward-compatible with existing rows and old application code.
    Suggestion: Add the column as nullable, deploy compatible code, backfill it, then enforce the constraint separately.
Scanned 1 file(s): 1 finding(s), 0 error(s).
```

## Why this exists

Laravel makes schema changes easy to express, but whether a change is safe depends on rollout order, table size, and database behavior. A migration can work locally and still lock a production table or break old application instances during a rolling deployment.

This project started from a practical gap: an established Laravel application needed migration safety feedback while its dependency graph still used PHP-Parser 4. The available alternatives either targeted newer dependency stacks, operated on generated SQL instead of Laravel migration PHP, or did not fit an incremental CI rollout.

Laravel Migration Linter therefore stays independent from Laravel itself and deliberately supports both PHP-Parser 4 and 5. It provides a conservative, reviewable gate inspired by tools such as `strong_migrations` and `django-migration-linter`. Anything it cannot prove safe is reported for human review.

The goal is not to keep applications on old versions forever. It is to let teams improve migration safety now, without making a framework or static-analysis upgrade a prerequisite.

## What it detects

- Dropping or renaming tables, columns, indexes, and constraints.
- `->change()` on existing columns.
- Required columns added without `nullable()`, `default()`, or `useCurrent()`.
- Index and foreign-key creation on existing tables, with stricter messaging for configured hot tables.
- Raw SQL and data mutations inside `up()`.
- Column reordering with `after()` or `first()`.
- Dynamic table names, non-inline schema callbacks, and unknown Blueprint macros.

The linter intentionally ignores `down()`: production rollback should be an explicit forward migration, not an automatically executed destructive path.

## Requirements

- PHP 8.2 or newer.
- PHP-Parser 4.15 or 5.6 and newer.
- Laravel-style PHP migration files. Laravel itself is not required at analysis time.

### Compatibility scope

- The CLI has no runtime dependency on Laravel, so it can be added to established and current Laravel applications without changing the framework version.
- Clean-consumer CI runs against exact PHP-Parser 4.15.0 and the PHP-Parser 5.x line to protect both dependency paths.
- The analyzer recognizes common `Schema` and `Blueprint` migration syntax. Project-specific macros can be allow-listed; unknown operations are reported rather than silently treated as safe.
- This is targeted compatibility, not a promise to support every historical Laravel or PHP version. The explicit Composer constraints above are the support contract.

## Installation

The repository is ready to install directly through Composer. Until the package is registered on Packagist, add the GitHub repository once:

```bash
composer config repositories.laravel-migration-linter vcs https://github.com/MatiasJRB/laravel-migration-linter
composer require --dev matiasjrb/laravel-migration-linter:^0.1
```

## Usage

Analyze the default `database/migrations` directory:

```bash
vendor/bin/laravel-migration-linter
```

Analyze explicit files or directories:

```bash
vendor/bin/laravel-migration-linter database/migrations modules/Billing/database/migrations
```

Machine-readable output:

```bash
vendor/bin/laravel-migration-linter --format=json
vendor/bin/laravel-migration-linter --format=markdown --output=build/migration-report.md
```

JSON reports follow the versioned [`resources/report.schema.json`](resources/report.schema.json) contract.

Exit codes are stable:

| Code | Meaning |
| --- | --- |
| `0` | Analysis completed and no unsafe operations were found. |
| `1` | One or more safety findings were produced. |
| `2` | Configuration, path, parsing, or runtime error. |

## Configuration

Copy [`.laravel-migration-linter.example.php`](.laravel-migration-linter.example.php) to `.laravel-migration-linter.php` in the application root:

```php
<?php

return [
    'paths' => ['database/migrations'],
    'hot_tables' => ['users', 'orders', 'payments'],
    'ignore_rules' => [],
    'allowed_blueprint_methods' => ['tenantColumns'],
];
```

- `paths`: files and directories used when no positional paths are passed.
- `hot_tables`: large or critical tables that deserve stronger index and foreign-key warnings.
- `ignore_rules`: explicit rule IDs to suppress after team review.
- `allowed_blueprint_methods`: project-specific Blueprint macros that are considered safe.

Use `--config=path/to/config.php` to load a different file. Unknown keys and invalid values fail with exit code `2` instead of being silently ignored.

## GitHub Actions

```yaml
name: Migration safety

on:
  pull_request:
    paths:
      - "database/migrations/**"

permissions:
  contents: read

jobs:
  lint-migrations:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v7
      - uses: shivammathur/setup-php@v2
        with:
          php-version: "8.3"
          coverage: none
      - run: composer install --no-interaction --prefer-dist
      - run: vendor/bin/laravel-migration-linter --format=markdown --output=build/migration-report.md
```

## Recommended adoption

For an existing production application, start with the linter on migrations added or modified by a pull request. Treat safety findings (`exit 1`) as advisory, but fail on analysis or configuration errors (`exit 2`). Keep database-aware controls such as SQL preview, real apply/rollback tests, and the existing deploy gate authoritative.

After four to six weeks, review findings by rule, record false positives and promote only the rules with enough evidence to blocking status. See the [adoption guide](docs/adoption.md) for a concrete rollout and measurement template.

## Safety model

The analyzer uses [`nikic/php-parser`](https://github.com/nikic/PHP-Parser) rather than regular expressions. It resolves anonymous and named migration classes, analyzes only `up()`, recognizes `Schema::connection(...)`, follows Blueprint method chains, and reports source locations.

See [the rule reference](docs/rules.md) for the complete built-in policy and suggested rollout patterns.

### Deliberate limitations

- It does not connect to a database or estimate actual table size.
- It does not generate SQL because Laravel's SQL depends on the database driver and framework version.
- Inline `Schema::table` and `Schema::create` closures are supported; dynamic callbacks are reported conservatively.
- Custom Blueprint macros must be explicitly allowed or they are reported as unknown.

These constraints keep CI deterministic and prevent a green result from implying more knowledge than static analysis has.

## Library API

```php
use MatiasJRB\LaravelMigrationLinter\Analysis\Linter;
use MatiasJRB\LaravelMigrationLinter\Config\Configuration;

$result = (new Linter())->lint(
    paths: ['database/migrations'],
    configuration: new Configuration(hotTables: ['orders']),
    workingDirectory: getcwd(),
);

if ($result->exitCode() !== 0) {
    // Surface $result->findings and $result->errors in your own integration.
}
```

## Development

```bash
composer install
composer check
```

The test suite covers safe additive migrations, destructive DDL, index and foreign-key operations, data mutations, dynamic syntax, configuration, formatters, and CLI behavior.

## Contributing and security

See [CONTRIBUTING.md](CONTRIBUTING.md) before opening a pull request. Please report suspected vulnerabilities according to [SECURITY.md](SECURITY.md), not through a public issue.

## License

[MIT](LICENSE) © 2026 Matias Rios.
