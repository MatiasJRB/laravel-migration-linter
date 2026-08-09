# Rule reference

Every finding includes a stable rule ID, severity, source location, table name when known, and a safer rollout suggestion. Both warnings and errors make the lint result unsafe and produce exit code `1`; severity communicates likely impact, not whether CI should ignore the finding.

| Rule ID | Severity | Trigger | Safer rollout |
| --- | --- | --- | --- |
| `alter-column` | Error | `->change()` in `Schema::table`. | Expand the schema, backfill, migrate reads/writes, then contract. |
| `column-position` | Warning | `after()` or `first()` on an existing table. | Append the column and avoid relying on physical order. |
| `data-mutation` | Error | Inserts, updates, deletes, upserts, counters, or truncation through `DB` in `up()`, including current Query Builder variants. | Use an idempotent, observable backfill command. |
| `destructive-schema-change` | Error | Drop or rename operations and global foreign-key toggles. | Delay cleanup until all deployed code is compatible. |
| `dynamic-schema-callback` | Error | `Schema::table` or `Schema::create` callback is not an inline closure. | Inline the callback or explicitly ignore after review. |
| `dynamic-table-name` | Error | A table name cannot be resolved to a string literal. | Use a literal so policy and hot-table matching are deterministic. |
| `foreign-key-operation` | Error | A constraint is added to an existing table. | Clean data first, then add and validate the constraint online. |
| `hot-table-foreign-key` | Error | Foreign key on a configured hot table. | Use a database-specific online rollout with monitoring. |
| `hot-table-index` | Error | Index on a configured hot table. | Build the index concurrently or with an online schema tool. |
| `index-operation` | Warning | Index, unique, primary, full-text, spatial, or morph index on an existing table. | Review engine behavior and use an online strategy when necessary. |
| `raw-sql` | Error | Raw database statements or Blueprint expressions such as `rawColumn()`, `computed()`, `storedAs()`, and `virtualAs()` in `up()`. | Move the operation to a reviewed deployment command or suppress explicitly. |
| `required-column-without-default` | Error | Required column added to an existing table without `nullable()`, `default()`, or `useCurrent()`. | Add nullable, deploy compatible code, backfill, then enforce. |
| `table-option-change` | Warning | Engine, collation, charset, comment, or temporary option changed on an existing table. | Review generated DDL and execute with an online tool if it rebuilds. |
| `unknown-operation` | Error | Schema, DB, or Blueprint operation outside the built-in model. | Review it, then allow the macro or ignore the rule explicitly. |

## Suppressions

Suppressions are deliberately centralized in configuration so exceptions remain visible in code review:

```php
return [
    'ignore_rules' => ['index-operation'],
    'allowed_blueprint_methods' => ['tenantColumns'],
];
```

An ignored rule is disabled for the entire run. Prefer a narrow CI job or temporary configuration change, and document why the exception is safe for the target database.
