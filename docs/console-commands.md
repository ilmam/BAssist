# Custom Console Commands

This project defines five custom Artisan commands. This page is the single
reference for all of them; deeper topic guides are linked where they exist.

| Command | Purpose | Deep-dive |
|---------|---------|-----------|
| [`make:entity`](#makeentity) | Scaffold a convention-based CRUD entity | [entity-scaffolding.md](entity-scaffolding.md) |
| [`entity:eject`](#entityeject) | Promote an existing entity up the scaffold ladder | [entity-scaffolding.md](entity-scaffolding.md) |
| [`entity:materialize-form`](#entitymaterialize-form) | Regenerate per-entity form blades with explicit `Form::field` lines | [entity-scaffolding.md](entity-scaffolding.md) |
| [`dto:cache-metadata`](#dtocache-metadata) | Warm the DTO attribute metadata cache | [dto-metadata.md](dto-metadata.md) |
| [`dto:clear-metadata`](#dtoclear-metadata) | Clear the DTO attribute metadata cache | [dto-metadata.md](dto-metadata.md) |

All command classes live in `app/Console/Commands/`. The entity commands
share `app/Console/Commands/Concerns/EntityScaffoldTrait.php` (see
[Shared internals](#shared-internals-entityscaffoldtrait)). Each class also
carries a verbose PHPDoc block at the top of the file — that in-code docblock
and this page are kept in sync.

> `data:cache-structure`, referenced in some "next steps" output, is **not** a
> custom command — it ships with the `spatie/laravel-data` package.

---

## `make:entity`

Scaffold every artifact a CRUD entity needs so it is immediately discoverable
and routable by the convention layer (`App\Support\CrudEntityRegistry` and
`CrudRouteRegistrar`).

### The scaffold ladder (profiles)

Profiles describe how much of the entity the framework manages for you versus
how much lives physically on disk and is owned by you:

```
virtual  ──►  hybrid  ──►  material
(least owned)            (fully owned)
```

| Profile | What is generated | Views | Controllers | Owns |
|---------|-------------------|-------|-------------|------|
| `virtual` *(default)* | Model, Repository, `{Model}Data`, `{Model}ViewData`, migration | Shared `pages/generic/*` + `pages/modals/*` | Shared `CrudController` | Backend only |
| `hybrid` | virtual **+** 6 per-entity blades | `pages/{resource}/*` (form blades contain explicit `Form::field` lines) | Shared `CrudController` | Backend + views |
| `material` | hybrid **+** `{Model}Controller` + `Api/{Model}Controller`, wired in `config/crud.php` | own | own | Everything |

### Signature

```bash
php artisan make:entity {name}
    [--profile=virtual]
    [--fields=]
    [--display=]
    [--nav] [--no-nav]
    [--force] [--dry-run]
```

### Arguments & options

| Option | Description |
|--------|-------------|
| `name` *(required)* | Studly-cased model name, e.g. `Product`. |
| `--profile=` | `virtual` \| `hybrid` \| `material`. Default `virtual`. |
| `--fields=` | Comma-separated field specs (see [Field syntax](#field-syntax)). Defaults to `name:string`. |
| `--display=` | Field used as the display label / list column. Must be one of `--fields`. Defaults to the first field. |
| `--nav` | Force-add the entity to CRUD navigation config. |
| `--no-nav` | Do **not** add to navigation (internal entities). Navigation is added by default when neither flag is given. |
| `--force` | Overwrite generated files that already exist. |
| `--dry-run` | Print what would be generated without writing. |

### Field syntax

```text
field:type                          # e.g. name:string
field:type?                         # nullable shorthand, e.g. description:text?
field:type:formType                 # e.g. status:string:select
field:type:formType:nullable        # explicit nullable
field:foreignId:RelatedModel:select # relation, e.g. category_id:foreignId:Category:select
```

**Supported db types:** `string`, `text`, `integer`, `bigInteger`, `decimal`,
`float`, `double`, `boolean`, `date`, `dateTime`, `timestamp`, `foreignId`
(plus aliases `int`, `bool`, `biginteger`, `datetime`, `foreignid`).

**Supported form types:** `text`, `textarea`, `select`, `checkbox`, `radio`,
`file`, `image`, `dropzone`, `tree`, `date`, `datetime-local`, `number`,
`email`, `password`. When omitted, a sensible form type is inferred from the db
type.

### Examples

```bash
php artisan make:entity Country
php artisan make:entity Product --fields="name:string,price:decimal,description:text?" --display=name
php artisan make:entity Order --profile=material --fields="reference:string,customer_id:foreignId:Customer:select"
php artisan make:entity Log --no-nav --dry-run
```

### config/crud.php handling

When navigation is requested or the `material` profile is used, the command
inserts — or **replaces**, if an entry already exists — the model's entry in
`config/crud.php`. Replacing (rather than skipping) prevents stale keys, such as
a leftover `controller` from a previous `material` scaffold, from pointing at a
class that no longer exists.

### After running

The command prints recommended follow-up steps:

```bash
php artisan migrate
php artisan dto:cache-metadata --class=App\\Data\\{Model}Data
php artisan dto:cache-metadata --class=App\\Data\\{Model}ViewData
php artisan data:cache-structure
```

---

## `entity:eject`

Where `make:entity` **creates** a new entity, `entity:eject` takes one that
already exists and **promotes** it up the ownership ladder, generating only the
artifacts that are still missing. This is the "eject" operation: once you eject,
you own the generated files and the generic layer stops managing them.

### Level detection (automatic)

The command inspects the filesystem to decide the current level:

| Condition | Detected level |
|-----------|----------------|
| `{Model}Controller` exists | `material` |
| Per-entity blades exist (`pages/{resource}/list.blade.php`) | `hybrid` |
| Neither | `virtual` |

### Promotion behaviour

| Current | Default (one step) | With `--full` |
|---------|--------------------|---------------|
| `virtual` | → `hybrid` (creates 6 blades; forms materialized) | → `material` (blades + controllers) |
| `hybrid` | → `material` (controllers + config) | → `material` |
| `material` | no-op | no-op |

### Signature

```bash
php artisan entity:eject {name} [--full] [--force] [--dry-run]
```

| Option | Description |
|--------|-------------|
| `name` *(required)* | Studly-cased model name. The Model and Repository must already exist (created by `make:entity`); otherwise the command aborts with a hint. |
| `--full` | Eject all the way to `material` in a single step. |
| `--force` | Overwrite existing files; skips the interactive overwrite confirmation. |
| `--dry-run` | Print the per-file plan without writing anything. |

### Safety

- Prints a per-file plan (create/overwrite) before doing anything.
- Without `--force`, prompts for confirmation if any target file already exists.
- Controllers are generated via Laravel's own `make:controller`, then patched to
  extend `CrudController` (no duplicate controller stubs to maintain).
- `config/crud.php` is only touched when controllers are added, and existing
  `nav`/`home` settings on the entry are preserved.

### Examples

```bash
php artisan entity:eject Country            # virtual → hybrid
php artisan entity:eject Country --full     # → material in one step
php artisan entity:eject Country --dry-run  # preview only
php artisan entity:eject Country --full --force
```

Hybrid and material profiles generate **materialized** form blades: the same
`form-card` / `modal-content` shell as the generic templates, but with one
`Form::field(...)` line per `Form` on the edit DTO instead of
`<x-form :fieldsArray="$formFields">`. See [`entity:materialize-form`](#entitymaterialize-form)
to refresh forms after DTO changes.

---

## `entity:materialize-form`

Regenerate an entity's **form page** and **modal form** blades from DTO
metadata. Each field marked with `Form` on `{Model}Data` becomes
an explicit `Form::field($type, $fieldName, $dto->{$fieldName} ?? null, $list, null)`
line. Select fields also get a repository lookup for their option list.

Use this when you changed the edit DTO and want owned form markup updated
without re-ejecting list/details blades.

This command is also invoked automatically when `entity:eject` or
`make:entity --profile=hybrid|material` creates form blades — see
[entity-scaffolding.md](entity-scaffolding.md#materialized-forms).

### Signature

```bash
php artisan entity:materialize-form {name} [--force] [--dry-run]
```

| Option | Description |
|--------|-------------|
| `name` *(required)* | Studly-cased model name. Model, Repository, and `{Model}Data` must exist. |
| `--force` | Overwrite existing form blades. |
| `--dry-run` | Print the plan without writing. |

### Examples

```bash
php artisan entity:materialize-form Category
php artisan entity:materialize-form Category --force --dry-run
```

### Output files

- `resources/views/pages/{resource}/form.blade.php`
- `resources/views/pages/{resource}/modals/form.blade.php`

### Implementation

| Piece | Location |
|-------|----------|
| Command | `app/Console/Commands/MaterializeEntityFormCommand.php` |
| Generator | `app/Support/EntityFormMaterializer.php` |
| Page stub | `stubs/entity/view-form.stub` (`DummyFormBody` placeholder) |
| Modal stub | `stubs/entity/modal-form.stub` (`DummyModalFormBody` placeholder) |

The generator expands the `<x-form>` block into inline markup matching
`themes/{theme}/components/form.blade.php`: `Form::open`, `@method`, hidden
`id`, one `Form::field` per DTO field, footer buttons, `Form::close`.

---

## `dto:cache-metadata`

Pre-compute and cache the DTO attribute metadata (list columns, detail values
and form fields resolved from PHP attributes on `App\Data\*` classes) so
requests do not pay the reflection cost. See [dto-metadata.md](dto-metadata.md)
for the full caching model.

### Signature

```bash
php artisan dto:cache-metadata [--class=]
```

| Option | Description |
|--------|-------------|
| `--class=` | Fully-qualified DTO class to cache in isolation, e.g. `App\Data\CountryData`. When omitted, every Data class in the configured directories is warmed. |

### Behaviour

- **With `--class`:** validates the class exists, then caches just that schema.
  Fails if the class cannot be found.
- **Without `--class`:** discovers all Data classes, warms them, and lists what
  was cached. Warns (but succeeds) when none are found.

### Examples

```bash
php artisan dto:cache-metadata
php artisan dto:cache-metadata --class="App\Data\CountryData"
```

---

## `dto:clear-metadata`

The inverse of `dto:cache-metadata`. Drops cached schema entries so they are
rebuilt from the current attributes. Run it whenever a Data class's attributes
change (new/renamed property, changed `Form`, etc.).

### Signature

```bash
php artisan dto:clear-metadata [--class=]
```

| Option | Description |
|--------|-------------|
| `--class=` | Fully-qualified DTO class to clear in isolation. When omitted, **all** cached DTO metadata entries are cleared. |

### Examples

```bash
php artisan dto:clear-metadata
php artisan dto:clear-metadata --class="App\Data\CountryData"
```

---

## Shared internals: `EntityScaffoldTrait`

`app/Console/Commands/Concerns/EntityScaffoldTrait.php` holds the logic shared by
`make:entity`, `entity:eject`, and `entity:materialize-form`, keeping scaffold
commands in lock-step (same file layout, same form materialization, same
`config/crud.php` mechanics, same controller generation).

**Host requirement:** the consuming command must define `--force` and `--dry-run`
options, which the trait reads.

| Helper | Responsibility |
|--------|----------------|
| `stub($name, $replace)` | Load `stubs/entity/{name}.stub` and apply a placeholder → value map. |
| `viewFiles($resource, $replace, $model)` | The six per-entity blades (list, form, details + view/form/delete modals). Form blades use materialized field lines. |
| `materializedFormFiles($resource, $replace, $model)` | Form page + modal form only (used by `entity:materialize-form`). |
| `materializedFormReplacements($model)` | Builds `DummyFormBody` / `DummyModalFormBody` via `EntityFormMaterializer`. |
| `makeControllers($model)` | Delegate to Laravel's `make:controller` for web + API controllers, then patch them to extend `CrudController` and drop the unused `Request` import. |
| `writeFiles($files)` | Write files, honouring `--force` (skip existing unless forced) and `--dry-run` (report only). |
| `buildCrudConfigEntry($model, $options)` | Build the PHP lines for one `config/crud.php` model entry (optional controller keys, home, nav label/icons). LF line endings. |
| `updateCrudConfig($model, $entry)` | Insert a new entry, or **replace** an existing one in place. Line endings are normalised to LF so the regexes work on both Windows (CRLF) and Unix files. |

### `EntityFormMaterializer`

`app/Support/EntityFormMaterializer.php` reads `Form` metadata
from the entity's edit DTO (`{Model}Data`) and renders blade fragments with
explicit `Form::field(...)` calls. Invoked when hybrid form stubs are built —
not at request time.

### Why controllers are not stub files

Laravel's built-in `make:controller` already produces a clean controller. Rather
than maintaining duplicate `controller.stub` / `api-controller.stub` files, the
trait calls `make:controller` and then rewrites the generated class to extend
`CrudController`. This removes redundancy while producing identical output.
