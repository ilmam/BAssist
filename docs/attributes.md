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
#[Form('textarea', hideQuick: true)]
#[Form('textarea', quickSpan: 12)]
#[Form('select', 'Priority', quickSpan: 6)]
```

- Every `Form` / `ListForm` field **appears in Quick Create by default**.
- `hideQuick: true` opts out: the field is not shown in the Quick Create UI and is submitted as a hidden input using the DTO property default. Do **not** use `quickSpan: 0` to hide — hiding stays `hideQuick: true`.
- `quickSpan` (default `4`) is the 12-column grid span on Quick Create (typically `3`, `4`, `6`, or `12`; values are clamped to `1`–`12`). Ignored when `hideQuick: true`. Use `12` for full-width visible fields (e.g. textareas).
- Full create/edit modals still show all form fields (span does not apply there).
- **Status / priority:** leave `status_id` / `priority_id` as `null` on the DTO (with `hideQuick: true` if hidden). On create, `BaseModel` sets draft status; models with `priority_id` use `AppliesDefaultPriority` (medium via `EntityPriority::defaultId()`).

`ListForm` accepts the same arguments as `Form`:

```php
#[ListForm('text')]
public string $title = '';

#[ListForm('select', 'Status', hideQuick: true)]
public ?int $status_id = null;

#[ListForm('textarea', quickSpan: 12)]
public string $summary = '';
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
  Form / ListForm  → formFields (+ hideQuick / quickSpan → Quick Create)
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
