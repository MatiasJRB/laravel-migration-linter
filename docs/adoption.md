# Adoption guide

Laravel Migration Linter is intentionally suitable for incremental adoption in an established application. A static analyzer should earn blocking authority from evidence in the target codebase rather than receive it on day one.

## Phase 1: advisory pilot

Run the linter only when a pull request adds or modifies Laravel migration files.

- Keep findings (`exit 1`) advisory and publish the Markdown report in the pull request.
- Fail CI on configuration, path, parse, or runtime errors (`exit 2`), because those mean the migration was not successfully analyzed.
- Keep existing database-aware controls authoritative: project-specific classifiers, generated SQL review, and real apply/rollback tests.
- Do not lint the complete migration history on every pull request. Use a one-time baseline audit for discovery, then focus review on new changes.

## Phase 2: measure for four to six weeks

For every finding, record the review outcome. A small issue, pull-request checklist, or spreadsheet is enough.

| Field | Example |
| --- | --- |
| Rule | `required-column-without-default` |
| Migration | `2026_09_01_add_status_to_orders.php` |
| Review outcome | Valid risk / false positive / accepted exception |
| Resolution | Migration changed / rollout documented / ignored with reason |
| Reviewer note | Existing rows require a backfill before `NOT NULL` |

Measure per rule rather than only globally:

- number of findings;
- number and rate of false positives;
- migrations changed because of the finding;
- accepted exceptions and their recurring context;
- analyzer errors, which should remain at zero.

## Phase 3: promote rules selectively

A rule is a good blocking candidate when it has appeared enough times to evaluate, produces consistently actionable feedback, and has no unexplained false positives in the pilot.

Keep context-dependent rules advisory. Indexes, foreign keys, raw SQL, and operations on hot tables often require database size and rollout context that static analysis cannot know.

Promotion should be reversible and rule-specific. Do not turn every finding into a blocker merely because the tool itself was stable during the pilot.

## Long-term role

The linter complements rather than replaces database-aware validation. Its job is to catch risky intent early from the migration PHP; SQL preview and real database execution remain the stronger evidence for what a particular Laravel and database version will actually do.
