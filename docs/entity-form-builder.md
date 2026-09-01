# Entity form builder

**When do I care?** When a create/edit form should show dropdowns. You usually do not call this yourself — the shared `CrudController` already does.

Create/edit forms need:

1. Which fields to render (from `#[Form]` on `{Model}Data`)
2. For `select` fields, the option list from the related model’s repository

`App\Support\EntityFormBuilder` does both in one call so generic and custom controllers get the same populated form.

```
Controller
  $this->formBuilder()->fields($dtoClass)
        │
        ▼
  1. DtoMetadata → field types
  2. For each select, load options via the related repository
  3. FormHelper → structure the Blade expects
```

`#[Form('select', 'RelatedModel')]` supplies the control type and the related model name. The builder calls that model’s `getSelectOptions()`.

---

## Usage

Inside a controller that extends `BaseController`:

```php
$formFields = $this->formBuilder()->fields($this->modelRepository->editDto);
```

From anywhere else:

```php
use App\Support\EntityFormBuilder;

$formFields = app(EntityFormBuilder::class)->fields(\App\Data\{Model}Data::class);
```

Override `formBuilder()` on a controller if you need a custom builder.

---

## Who owns what

| Concern | Class |
|---------|-------|
| Which fields and types | `DtoMetadata` |
| Filling select options at request time | `EntityFormBuilder` |
| Writing explicit `Form::field` lines at scaffold time | `EntityFormMaterializer` |
| Finding a related repository | `RepositoryResolver` |
| HTML | Blade / `Form` facade |

| Feature | Used when |
|---------|-----------|
| `EntityFormBuilder::fields($editDto)` | **Virtual** entities (`<x-form :fieldsArray="$formFields">`) |
| `EntityFormMaterializer` | **Hybrid** owned form blades (`entity:eject` / `entity:materialize-form`) |

Hybrid blades ignore `$formFields` at render time. After you change `{Model}Data`, run `php artisan entity:materialize-form {Model}`.

See [entity-scaffolding.md](entity-scaffolding.md#materialized-forms).
