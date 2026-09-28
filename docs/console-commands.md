# Custom Artisan commands

Five commands this project adds. If you are new, run `make:entity` from [quick-start.md](quick-start.md) and come back here for flags.

| Command | Purpose | Longer guide |
|---------|---------|----------------|
| [`make:entity`](#makeentity) | Create a CRUD entity | [entity-scaffolding.md](entity-scaffolding.md) |
| [`entity:eject`](#entityeject) | Promote virtual → hybrid → material | [entity-scaffolding.md](entity-scaffolding.md) |
| [`entity:materialize-form`](#entitymaterialize-form) | Refresh owned form blades after DTO edits | [entity-scaffolding.md](entity-scaffolding.md) |
| [`dto:cache-metadata`](#dtocache-metadata) | Warm DTO attribute cache | [dto-metadata.md](dto-metadata.md) |
| [`dto:clear-metadata`](#dtoclear-metadata) | Clear DTO attribute cache | [dto-metadata.md](dto-metadata.md) |
| [`entity:rules`](#entityrules) | Show the validation rules enforced for an entity | [validation.md](validation.md) |

Command classes: `app/Console/Commands/`. Shared helpers: `EntityScaffoldTrait` ([Shared internals](#shared-internals-entityscaffoldtrait)).

`data:cache-structure` is **not** a custom command — it ships with Spatie Laravel Data.

`{Model}` = StudlyCase name. `{resource}` = plural snake_case URL/folder.

---

## `make:entity`

Creates the files the CRUD layer needs (model, repository, two DTOs, migration, optional blades/controller).

### Profiles

```
virtual  ──►  hybrid  ──►  material
```

| Profile | Generated | Views | Controllers |
|---------|-----------|-------|-------------|
| `virtual` *(default)* | Model, Repository, `{Model}Data`, `{Model}ViewData`, migration | Shared `pages/generic/*` | Shared `CrudController` |
| `hybrid` | virtual **+** 6 blades | `pages/{resource}/*` with explicit `Form::field` lines | Shared `CrudController` |
| `material` | hybrid **+** `{Model}Controller` in `config/crud.php` | own | Web controller; API stays on `Api\CrudController` unless you set `'api_controller'` |

### Signature

```bash
php artisan make:entity {name}
    [--profile=virtual]
    [--fields=]
    [--display=]
    [--nav] [--no-nav]
    [--force] [--dry-run]
```

| Option | Description |
|--------|-------------|
| `name` *(required)* | StudlyCase model name (`{Model}`). |
| `--profile=` | `virtual` \| `hybrid` \| `material`. Default `virtual`. |
| `--fields=` | Comma-separated specs (see [Field syntax](#field-syntax)). Default `name:string`. |
| `--display=` | Label / list column. Must be one of `--fields`. Default: first field. |
| `--nav` | Force-add to CRUD navigation. |
| `--no-nav` | Do not add to the menu. Navigation is on by default if neither flag is given. |
| `--force` | Overwrite existing generated files. |
| `--dry-run` | Print the plan; write nothing. |

### Field syntax

```text
field:type                          # name:string
field:type?                         # description:text?
field:type:formType                 # status:string:select
field:type:formType:nullable
field:foreignId:RelatedModel:select # related_id:foreignId:RelatedModel:select
```

**DB types:** `string`, `text`, `integer`, `bigInteger`, `decimal`, `float`, `double`, `boolean`, `date`, `dateTime`, `timestamp`, `foreignId` (aliases `int`, `bool`, `biginteger`, `datetime`, `foreignid`).

**Form types:** `text`, `textarea`, `select`, `checkbox`, `radio`, `file`, `image`, `dropzone`, `attachments`, `tree`, `date`, `datetime-local`, `number`, `email`, `password`. If omitted, a form type is inferred from the DB type.

### Examples

```bash
php artisan make:entity {Model}
php artisan make:entity {Model} --fields="name:string,price:decimal,description:text?" --display=name
php artisan make:entity {Model} --profile=material --fields="reference:string,related_id:foreignId:RelatedModel:select"
php artisan make:entity {Model} --no-nav --dry-run
```

### `config/crud.php`

When navigation is requested or the profile is `material`, the command inserts — or **replaces** — the model’s entry. Replacing avoids a leftover `controller` key pointing at a deleted class.

### After running

```bash
php artisan migrate
php artisan dto:cache-metadata --class=App\\Data\\{Model}Data
php artisan dto:cache-metadata --class=App\\Data\\{Model}ViewData
php artisan data:cache-structure
```

---

## `entity:eject`

Promotes an existing entity. Generates only missing files (you then own them).

### Level detection

| Condition | Level |
|-----------|-------|
| `{Model}Controller` exists | `material` |
| `pages/{resource}/list.blade.php` exists | `hybrid` |
| Neither | `virtual` |

| Current | Default (one step) | `--full` |
|---------|--------------------|----------|
| `virtual` | → hybrid (6 blades; forms materialized) | → material (blades + web controller) |
| `hybrid` | → material (web controller + config) | → material |
| `material` | no-op | no-op |

```bash
php artisan entity:eject {name} [--full] [--force] [--dry-run]
```

| Option | Description |
|--------|-------------|
| `name` | StudlyCase model. Model and Repository must already exist. |
| `--full` | Jump to material in one step. |
| `--force` | Overwrite; skip the confirmation prompt. |
| `--dry-run` | Print the plan; write nothing. |

Safety: prints a per-file plan first. Without `--force`, asks before overwriting. Controllers come from Laravel `make:controller`, then are patched to extend `CrudController`. `config/crud.php` is touched only when a controller is added; existing `nav`/`home` stay.

```bash
php artisan entity:eject {Model}
php artisan entity:eject {Model} --full
php artisan entity:eject {Model} --dry-run
```

---

## `entity:materialize-form`

Regenerates the form page and modal form from `{Model}Data`. Each `#[Form]` becomes a `Form::field(...)` line. Select fields get a repository option list.

Use after you change the edit DTO and do not want to re-eject list/details.

```bash
php artisan entity:materialize-form {name} [--force] [--dry-run]
```

Model, Repository, and `{Model}Data` must exist.

```bash
php artisan entity:materialize-form {Model}
php artisan entity:materialize-form {Model} --force --dry-run
```

Writes:

- `resources/views/pages/{resource}/form.blade.php`
- `resources/views/pages/{resource}/modals/form.blade.php`

Implementation: `MaterializeEntityFormCommand`, `EntityFormMaterializer`, stubs `view-form.stub` / `modal-form.stub`.

---

## `dto:cache-metadata`

Caches form/list/detail schema from PHP attributes so production requests skip reflection. See [dto-metadata.md](dto-metadata.md).

```bash
php artisan dto:cache-metadata
php artisan dto:cache-metadata --class="App\Data\{Model}Data"
```

`--class` caches one class (fails if missing). Without it, warms every Data class in the configured directories.

---

## `dto:clear-metadata`

Inverse of cache. Run when attributes change.

```bash
php artisan dto:clear-metadata
php artisan dto:clear-metadata --class="App\Data\{Model}Data"
```

---

## `entity:rules`

Print the validation rules the framework enforces for an entity's edit DTO, and which level declared each field's rules (`inferred`, `#[OneOf]`, a property attribute such as `#[Max]`, or `rules()`). A note is added when the DTO has an `after()` hook. See [validation.md](validation.md).

```bash
php artisan entity:rules FunctionalRequirement
php artisan entity:rules StateFlow
```

---

## Shared internals: `EntityScaffoldTrait`

`app/Console/Commands/Concerns/EntityScaffoldTrait.php` is used by `make:entity`, `entity:eject`, and `entity:materialize-form`. Host commands must define `--force` and `--dry-run`.

| Helper | Responsibility |
|--------|----------------|
| `stub($name, $replace)` | Load `stubs/entity/{name}.stub` and replace placeholders. |
| `viewFiles(...)` | Six per-entity blades (list, form, details + three modals). |
| `materializedFormFiles(...)` | Form page + modal form only. |
| `makeControllers($model)` | Laravel `make:controller` for the **web** `{Model}Controller`, then patch to extend `CrudController`. API stays on shared `Api\CrudController`. |
| `writeFiles($files)` | Honour `--force` / `--dry-run`. |
| `buildCrudConfigEntry` / `updateCrudConfig` | Insert or replace one `config/crud.php` entry (LF line endings). |

`EntityFormMaterializer` reads `Form` metadata from `{Model}Data` at scaffold time — not on each request.

Controllers are not stub files: the trait calls Laravel’s `make:controller` and rewrites the class to extend `CrudController`.
