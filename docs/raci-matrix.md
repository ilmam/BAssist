# Responsibility (RACI) matrix — built, currently hidden

**Status: shelved (November 2026).** The feature is implemented and tested but switched off in the UI. It was judged a nice-to-have that can wait. Nothing was deleted, so it can be switched back on in minutes.

BABOK reference: Guide §10.43.3, Responsibility (RACI) Matrix (Stakeholder List, Map, or Personas).

## What exists

| Piece | Where |
|---|---|
| Tables `raci_items`, `raci_assignments` | `database/migrations/2026_11_10_120000_create_raci_tables.php` (migrated, empty) |
| Models | `app/Models/RaciItem.php`, `app/Models/RaciAssignment.php`; `Stakeholder::raciAssignments()`, `Project::raciItems()` |
| Forms and lists | `RaciItemData`, `RaciAssignmentData` and their view DTOs; repositories in `app/Repositories/` |
| Role values | `app/Support/RaciRole.php` (R, A, C, I; labels Responsible, Accountable, Consulted, Informed) |
| Rules on save | `RaciAssignmentRepository`: only one Accountable per item, one role per stakeholder per item, item and stakeholder must be in the same project |
| Matrix view | `resources/views/pages/projects/partials/raci-matrix.blade.php` (not included anywhere right now) |
| Export data | `ProjectExportService` still loads `raci_items`, so the matrix partial works as soon as it is included again |
| Tests | `tests/Feature/RaciMatrixTest.php` |

The entities are still registered in `config/crud.php` and `config/entity_icons.php`, so their CRUD routes exist. They are simply not linked from anywhere.

## What was switched off

- Menu entries for **RACI Items** and **RACI Assignments** under Requirements Modeling (`config/navigation.php`).
- The matrix in the export pack and in the BABOK document's stakeholder section (the `@include` of `raci-matrix` in `export.blade.php` and `babok/partials/stakeholder-engagement.blade.php`).
- The RACI step in the Stakeholders guide (`resources/help/stakeholders.md`).

## Switching it back on

1. Re-add `['entity' => 'RaciItem']` and `['entity' => 'RaciAssignment']` after `Stakeholder` in `config/navigation.php`, and the same two keys in `tests/Unit/NavProjectFoldersTest.php`.
2. Re-add `@include('pages.projects.partials.raci-matrix')` below the stakeholders table in both views above.
3. Restore the guide step in `resources/help/stakeholders.md`.
4. Optional: an in-app grid page, so the matrix can be edited as a grid instead of one assignment at a time.
