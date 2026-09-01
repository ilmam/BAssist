# Entity scaffolding

Artisan commands that create (or promote) a CRUD entity. Full flag reference: [console-commands.md](console-commands.md). First-time walkthrough: [quick-start.md](quick-start.md).

`{Model}` = StudlyCase class name. `{resource}` = plural snake_case folder/URL name.

```bash
php artisan make:entity {Model} --profile=virtual --fields="name:string,description:text?" --display=name
php artisan make:entity {Model} --profile=hybrid  --fields="name:string,description:text?" --display=name
php artisan make:entity {Model} --profile=material --fields="name:string,related_id:foreignId:RelatedModel:select" --display=name
```

---

## Profiles (how much you own)

```
virtual  ──►  hybrid  ──►  material
(least you own)           (most you own)
```

| Profile | You get | Stay here when |
|---------|---------|----------------|
| **virtual** (default) | Model, repository, two DTOs, migration. Screens use shared `pages/generic/*`. Form fields are built at runtime from the DTO (`$formFields`). | Simple lookup tables; DTO attributes are enough |
| **hybrid** | Virtual files **plus** six blades under `pages/{resource}/`. Form blades contain explicit `Form::field(...)` lines. Controller is still shared. | You need custom HTML for this entity |
| **material** | Hybrid **plus** `{Model}Controller` and `'controller'` in `config/crud.php`. Table JSON stays on shared `Api\CrudController` unless you set `'api_controller'`. | Extra URLs (export, approve, …) |

New entities are added to the CRUD menu by default. Use `--no-nav` if it should stay off the menu. Menu group: `config/navigation.php`.

---

## Promote an existing entity (`entity:eject`)

Do not re-run `make:entity`. Eject adds only the files you still lack:

```bash
php artisan entity:eject {Model}            # virtual → hybrid (6 blades)
php artisan entity:eject {Model} --full     # all the way to material (web controller)
php artisan entity:eject {Model} --dry-run  # preview, write nothing
```

- Detects current level from files on disk.
- Skips existing files unless `--force`.
- Updates `config/crud.php` only when a controller is added (keeps existing nav/home).
- Form blades are materialized when going virtual → hybrid.

---

## Materialized forms

**Virtual** forms loop `$formFields` at runtime. **Hybrid** forms bake one `Form::field(...)` line per `#[Form]` into the blade.

| | Virtual | Hybrid |
|--|---------|--------|
| Form page | `pages/generic/form.blade.php` | `pages/{resource}/form.blade.php` |
| Field source | `$formFields` at runtime | `Form::field(...)` in the blade |
| After you change `{Model}Data` | Automatic | Re-run `entity:materialize-form` |

The generated file keeps the generic shell (`<x-form-card>`, toolbar, footer). Only the field list is expanded.

Example lines:

```blade
{{ Form::field('text', 'name', $dto->name ?? null, null, null) }}

@php
    $relatedList = app(\App\Support\RepositoryResolver::class)->for('RelatedModel')->getSelectOptions();
@endphp
{{ Form::field('select', 'related_id', $dto->related_id ?? null, $relatedList, null) }}
```

Refresh owned forms after DTO edits (does not touch list/details):

```bash
php artisan entity:materialize-form {Model}
php artisan entity:materialize-form {Model} --force --dry-run
```

Writes `pages/{resource}/form.blade.php` and `pages/{resource}/modals/form.blade.php`.

---

## What generated models include

Generated models extend `App\Models\BaseModel`:

- primary key `id`
- timestamps and audit user ids (`created_by`, `updated_by`, `deleted_by`) via `casts()` — child models merge `parent::casts()`
- relation inventory: `App\Models\Concerns\RelationsManagerTrait`
- soft deletes (`deleted_at`)

Generated migrations include timestamps, nullable audit foreign keys, and soft deletes. `BaseModel` has `createdBy()`, `updatedBy()`, `deletedBy()`.

---

## Field syntax (`--fields`)

```text
field:type
field:type?
field:type:formType
field:foreignId:RelatedModel:select
field:type:formType:nullable
```

Examples: `name:string`, `description:text?`, `related_id:foreignId:RelatedModel:select`.
