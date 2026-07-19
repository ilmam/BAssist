# Entity Scaffolding

Use `make:entity` to scaffold convention-based CRUD entities.

```bash
php artisan make:entity Product --profile=virtual --fields="name:string,description:text?" --display=name
php artisan make:entity Product --profile=hybrid  --fields="name:string,description:text?" --display=name
php artisan make:entity Product --profile=material --fields="name:string,category_id:foreignId:Category:select" --display=name
```

## Profiles

Three profiles define how much of the entity the framework still manages vs. how much you own:

```
virtual  ──►  hybrid  ──►  material
(least owned)             (fully owned)
```

- **`virtual`** *(default)*: model, repository, edit DTO, view DTO, migration. Pages are served by the shared `pages/generic/*` templates — nothing to maintain. Best for simple lookup/reference tables.
- **`hybrid`**: virtual files plus 6 per-entity blade files (`pages/{resource}/list`, `form`, `details`, and 3 modals). The blades start as copies of the generic templates and can be freely customised. The `CrudController` is still shared.
- **`material`**: hybrid files plus dedicated `{Model}Controller` and `Api/{Model}Controller` and their entries in `config/crud.php`. Full ownership of every layer.

New entities are added to CRUD navigation by default so the UI is ready after migration/cache steps. Use `--no-nav` for internal entities that should not appear in the menu.
Navigation-ready CRUD entities appear under the `Entities` dropdown. Configure that group in `config/navigation.php`.

## Ejecting a Virtual Entity

Use `entity:eject` to promote an existing virtual entity up the ladder without recreating it from scratch:

```bash
# virtual → hybrid (creates 6 blade files)
php artisan entity:eject Country

# virtual → material  OR  hybrid → material  (adds controllers)
php artisan entity:eject Country --full

# preview without writing
php artisan entity:eject Country --dry-run
php artisan entity:eject Country --full --dry-run
```

Eject is safe to run at any time:
- It detects the entity's current level automatically.
- Existing files are skipped (use `--force` to overwrite).
- The `config/crud.php` entry is updated only when controllers are added, and existing nav/home settings are preserved.

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

## Test Placeholders

- `virtual` profile creates only the minimum discoverable CRUD files.
- `hybrid` profile adds page and modal override views.
- `material` profile adds controllers and config wiring.
- Existing files are skipped unless `--force` is used.
- `--dry-run` writes nothing.
- `entity:eject` correctly detects the current level and promotes one step (or to material with `--full`).
