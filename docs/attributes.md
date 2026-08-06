# DTO & model attributes

Short PHP attributes drive list columns, detail values, forms, Quick Create, and Eloquent relation inventory.

| Attribute | Target | Purpose |
|-----------|--------|---------|
| `#[InList]` | property | Datatable / list column (opt-in) |
| `#[Value('code')]` / `#[Value(field: 'code')]` | property | Optional nested display-field override on `*ViewData` (bare `#[Value]` not required) |
| `#[Form('text')]` | property | Create/edit control |
| `#[ListForm('text')]` | property | Shortcut for `InList` + `Form` |
| `#[Hide]` | property | Exclude from detail/value (and list) discovery — primary opt-out for view projection |
| `#[Relation('BelongsTo')]` | method | Eloquent relation inventory (models) |

> `List` cannot be a PHP class name (reserved keyword), so the list marker is `InList`.

---

## Form & Quick Create

```php
#[Form('text')]
#[Form('select', 'Project')]
#[Form('select', 'Project', ktSelect: false)] // native kt-input select
#[Form('textarea', hideQuick: true)]
#[Form('text', readonly: true)]
```

- Every `Form` / `ListForm` field **appears in Quick Create by default**.
- `hideQuick: true` opts out: the field is not shown in the Quick Create UI and is submitted as a hidden input using the DTO property default.
- Select fields default to Metronic KTSelect (`config('ui.forms.select')` = `kt`). Override per field with `ktSelect: true|false`, or use type `kt-select` to force enhanced.
- `section: 'traceability'` groups Need Spine / lineage fields into a bordered panel (no section title — fields flow naturally inside the box).
- `project_id` is not shown on create/edit forms; it is submitted as a hidden input from the sticky project context.
- Column spans are **not** required on Form / ListForm (type defaults apply). Rare override: `uiSpan: 12` or `uiSpan: ['md' => 6, 'lg' => 4]`.
- `readonly: true` renders the control disabled/readonly (not submitted). Empty readonly values are omitted from create forms.
- Full create/edit forms show all form fields, laid out half-width like Quick Create (span applies there too).
- **Status / priority:** leave `status_id` / `priority_id` as `null` on the DTO (with `hideQuick: true` if hidden). On create, models with `HasEntityStatus` set draft via `EntityStatus::defaultId()`; models with `priority_id` use `AppliesDefaultPriority` (`EntityPriority::defaultId()` = should / MoSCoW). Entities with their own string lifecycle (e.g. Assumption) do not use `HasEntityStatus`.
- **BABOK scope (who gets Status / MoSCoW):** apply requirements lifecycle `status_id` and MoSCoW `priority_id` to **Stakeholder Needs**, **Solution Requirements** (Functional / Features), and **Change Requests** (CR uses its own status enum + MoSCoW priority). Do **not** put Status or MoSCoW on **Business Objectives** (strategic intent, §6.2) or **Business Needs** (raw problem/opportunity input to Strategy Analysis).

`ListForm` accepts the same arguments as `Form`:

```php
#[ListForm('text')]
public string $title = '';

#[ListForm('select', 'Status', hideQuick: true)]
public ?int $status_id = null;
```

Edit DTOs use `Form` / `ListForm` / `InList` only — no `Value` / `Hide` needed for editing. Detail projection lives on `*ViewData`.

---

## Detail / value projection (ViewData)

On `*ViewData`, **all public properties are included** in detail/view field sets (`getFields()` / `extractValues`) **except** those marked `#[Hide]`.

- **Scalar:** included unless `#[Hide]`.
- **Nested Spatie Data:** included unless `#[Hide]`; expose `{relation}.{displayField}` instead of the FK. Matching `*_id` is skipped when the relation property exists.
- **Override:** optional `#[Value('code')]` / `#[Value(field: 'code')]` forces `project.code` instead of the default heuristic (`name` → `title` → `category` → `label` → first `InList` scalar on the nested DTO). Bare `#[Value]` is not required and should not be used.

```php
#[InList]
public ?ProjectViewData $project = null;   // → project.name

#[Value('code')]
public ?ProjectViewData $project = null;   // → project.code

#[Hide]
public ?int $workspace_id = null;          // excluded from detail projection
```

Typical `#[Hide]` targets: `id`, `workspace_id`, `tenant_id`, `*_count`, `is_orphan`, and other plumbing that should not appear on detail views. (`id` is still prepended separately for list columns via `listColumns()`.)

---

## InList vs detail projection

| | `InList` / `ListForm` | Detail (`value_fields`) |
|--|--|--|
| Used for | Datatable columns (`listColumns`) | Detail / modal values (`getFields`) on `*ViewData` |
| Inclusion | Opt-in | All public props except `#[Hide]` |
| Empty list set | Falls back to value paths | — |
| Nested | Collapses to main display field | Same; optional `#[Value('…')]` override only |

---

## Hide

```php
#[Hide]
public ?int $workspace_id = null;

#[Hide]
public bool $is_orphan = false;
```

Primary way to exclude a property from detail/value discovery (also excluded from list discovery). Does not affect `Form` / Quick Create (use `hideQuick` for that).

---

## Relation (models)

```php
#[Relation('BelongsTo')]
public function project() { ... }
```

Used by `RelationsManagerTrait` to inventory Eloquent relations. Unrelated to DTO list/form/value display.

---

## Mental model

```
Edit DTO (*Data)
  Form / ListForm  → formFields (+ hideQuick / readonly → Quick Create)
  InList / ListForm → (optional; often mirrored on ViewData)
  (no Value / Hide needed for forms)

View DTO (*ViewData)
  InList           → listColumns (opt-in)
  (default)        → getFields / extractValues (all public props)
  Hide             → exclude from detail (and list) discovery
  Nested *ViewData → relation.{name|title|…}; optional Value('…') override
```

After changing attributes in production, run:

```bash
php artisan dto:clear-metadata
php artisan dto:cache-metadata
```

See also [dto-metadata.md](dto-metadata.md).
