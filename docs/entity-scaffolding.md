# Entity Scaffolding

Use `make:entity` to scaffold convention-based CRUD entities.

```bash
php artisan make:entity Product --profile=generic --fields="name:string,description:text?" --display=name
php artisan make:entity Product --profile=hybrid --fields="name:string,description:text?" --display=name
php artisan make:entity Product --profile=custom --fields="name:string,category_id:foreignId:Category:select" --display=name --nav
```

## Profiles

- `generic`: model, repository, edit DTO, view DTO, and migration.
- `hybrid`: generic files plus page and modal view overrides.
- `custom`: hybrid files plus web/API controller overrides and `config/crud.php` wiring.

## Base Entity Convention

Generated models extend `App\Models\BaseModel`, which owns the shared entity conventions:

- primary key: `id`
- timestamps enabled: `created_at`, `updated_at`
- timestamp casts: `datetime`
- audit user columns: `created_by`, `updated_by`, `deleted_by`
- soft deletes enabled with `deleted_at`

Generated migrations include `$table->timestamps()`, nullable audit user foreign keys, and `$table->softDeletes()` by default.
`BaseModel` exposes `createdBy()`, `updatedBy()`, and `deletedBy()` relations.

## Current Field Syntax

```text
field:type
field:type?
field:type:formType
field:foreignId:RelatedModel:select
field:type:formType:nullable
```

## Next Placeholders

- Add interactive prompts when `--fields` is omitted.
- Add factory, seeder, policy, request, and relation generation flags.
- Expand field metadata for list/detail/form visibility.
- Improve config merging when a model already exists in `config/crud.php`.

## Test Placeholders

- `generic` profile creates only the minimum discoverable CRUD files.
- `hybrid` profile adds page and modal override views.
- `custom` profile adds controllers and config wiring.
- Existing files are skipped unless `--force` is used.
- `--dry-run` writes nothing.
