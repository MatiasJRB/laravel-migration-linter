# Changelog

All notable changes to this project will be documented in this file. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [0.1.3] - 2026-08-30

### Added

- Documented the original compatibility gap for established Laravel applications that still depend on PHP-Parser 4.
- Added an advisory-first adoption guide with a four-to-six-week, per-rule measurement process.

### Changed

- Clarified that framework independence and dual PHP-Parser 4/5 support are deliberate compatibility features, not a promise to support every historical Laravel or PHP release.

## [0.1.2] - 2026-08-30

### Fixed

- Replaced `Name::getParts()` with the cross-version `Name::getLast()` API required by PHP-Parser 4.15.
- Pinned the compatibility smoke test to exact PHP-Parser 4.15.0 instead of the newest 4.x release.

## [0.1.1] - 2026-08-30

### Added

- Added runtime and clean-consumer compatibility with PHP-Parser 4.15+ alongside PHP-Parser 5.6+.

## [0.1.0] - 2026-08-09

### Added

- AST analysis of anonymous and named Laravel migration `up()` methods.
- Built-in rules for destructive DDL, required columns, indexes, foreign keys, raw SQL, data mutations, dynamic syntax, and unknown operations.
- Hot-table policy, rule suppression, and custom Blueprint macro allow-list.
- Console, JSON, and Markdown output with stable exit codes.
- Standalone CLI and reusable PHP API.
- PHPUnit, PHPStan, CI, security policy, and contributor documentation.

[Unreleased]: https://github.com/MatiasJRB/laravel-migration-linter/compare/v0.1.3...HEAD
[0.1.3]: https://github.com/MatiasJRB/laravel-migration-linter/compare/v0.1.2...v0.1.3
[0.1.2]: https://github.com/MatiasJRB/laravel-migration-linter/compare/v0.1.1...v0.1.2
[0.1.1]: https://github.com/MatiasJRB/laravel-migration-linter/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/MatiasJRB/laravel-migration-linter/releases/tag/v0.1.0
