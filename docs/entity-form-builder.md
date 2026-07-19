# Entity form builder

Create and edit forms need two things: the list of fields to render (with their control types) and, for `select` fields, the option list pulled from the related entity's table. Previously this logic lived **inside `BaseController`** — field discovery plus a loop that resolved each related repository to load dropdown options.

That coupled form-building to the generic controller, meaning:

- Custom controllers had to duplicate the loop to get populated `select` lists.
- The `create()` form never actually populated dropdown options — only `edit` did.

`App\Support\EntityFormBuilder` centralizes this concern so **any** controller (generic or custom) produces identical form fields from a single call.

---

## How it works

```
┌──────────────────────────────────────────────────────────────┐
│  Controller (generic or custom)                              │
│  $this->formBuilder()->fields($dtoClass)                     │
└──────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────┐
│  EntityFormBuilder::fields()                                  │
│  1. DtoMetadata::for($dtoClass)->formFields()  (field schema) │
│  2. populate `select` option lists from related repositories │
│  3. FormHelper::getFormFields()  → view-ready structure       │
└──────────────────────────────────────────────────────────────┘
```

For each field, `FormFieldAttribute($fieldType, $model)` provides the control type (`$options[0]`) and, for a `select`, the related model name (`$options[1]`). The builder resolves that model's repository and calls `getSelectOptions()` to fill `$options['list']`.

---

## Usage

Inside any controller extending `BaseController`:

```php
$formFields = $this->formBuilder()->fields($this->modelRepository->editDto);
```

From anywhere else (custom controller, service, command):

```php
use App\Support\EntityFormBuilder;

$formFields = app(EntityFormBuilder::class)->fields(CategoryData::class);
```

The `formBuilder()` accessor on `BaseController` is a `protected` hook — override it to supply a customized builder for a specific controller.

---

## Design notes

| Concern | Owner |
|---------|-------|
| Which fields exist + their types | `DtoMetadata` (attribute schema) |
| Populating `select` option lists (runtime) | `EntityFormBuilder` |
| Generating explicit `Form::field` lines (scaffold time) | `EntityFormMaterializer` |
| Resolving a related repository | `RepositoryResolver` (injected) |
| Rendering the form | Blade views / `FormBuilder` facade |

- **Single responsibility** — form-field construction and dropdown loading live in one class instead of the controller.
- **Reuse (OCP)** — custom controllers get populated forms for free via one call, with no duplicated loops.
- **Dependency inversion** — the builder receives a `RepositoryResolver` instance (`RepositoryResolver::for()`), so it can be mocked in tests; `BaseController` resolves the builder through the container.

### Where it is used

| Feature | API | Applies when |
|---------|-----|--------------|
| Create form | `EntityFormBuilder::fields($editDto)` | **Virtual** entities (generic `<x-form>`) |
| Edit / modal-edit form | `EntityFormBuilder::fields($editDto)` (via `buildEditForm()`) | **Virtual** entities |
| Materialized form blades | `EntityFormMaterializer` (via `entity:eject` / `entity:materialize-form`) | **Hybrid** entities with owned form blades |

For **virtual** entities, the controller still passes `$formFields` to `<x-form>`, which loops fields at render time.

For **hybrid** entities, form blades contain explicit `Form::field(...)` lines generated from the same DTO metadata. The controller may still pass `$formFields`, but materialized blades do not use it — re-run `entity:materialize-form` after DTO changes.

See [entity-scaffolding.md](entity-scaffolding.md#materialized-forms) and [console-commands.md](console-commands.md#entitymaterialize-form).
