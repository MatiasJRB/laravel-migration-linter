# Contributing

Thanks for helping make Laravel migrations safer.

## Before opening a change

1. Open an issue for behavior changes or new rules so the safety trade-off can be discussed.
2. Keep rules conservative and database-aware. A green result must never claim knowledge the analyzer does not have.
3. Add a focused fixture or inline migration test for both the unsafe operation and its safe alternative.
4. Preserve rule IDs and JSON fields unless the change is intentionally breaking.

## Local checks

```bash
composer install
composer validate --strict
composer check
composer audit
```

Pull requests should explain:

- The production failure mode being detected.
- Which Laravel syntax is supported.
- Expected false positives and how users can make an explicit exception.
- Why the suggested alternative is safer.

## Code style

- PHP 8.2+, strict types, PSR-12 formatting.
- Prefer small immutable domain objects and deterministic output.
- Do not boot Laravel or connect to a database during analysis.
- Do not suppress PHPStan findings with baselines or ignore comments; fix the underlying type contract.

## Releases

Maintainers update `CHANGELOG.md` and `Version::VERSION`, tag a semantic version, and create a GitHub release after CI succeeds.
