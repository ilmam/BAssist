# DTO & model attributes

Short PHP attributes drive list columns, detail values, forms, Quick Create, and Eloquent relation inventory.

| Attribute | Target | Purpose |
|-----------|--------|---------|
| `#[InList]` | property | Datatable / list column |
| `#[Value]` / `#[Value('code')]` | property | Detail/view display value; on nested Data, show related display field instead of `*_id` |
| `#[Form('text')]` | property | Create/edit control |
| `#[ListForm('text')]` | property | Shortcut for `InList` + `Form` |
| `#[Hide]` | property | Exclude from list/value discovery |
| `#[Relation('BelongsTo')]` | method | Eloquent relation inventory (models) |

> `List` cannot be a PHP class name (reserved keyword), so the list marker is `InList`.

---

## Form & Quick Create

```php
#[Form('text')]
#[Form('select', 'Project')]
#[Form('textarea', hideQuick: true)]
#[Form('text', hideQuick: true, quickDefault: 'problem')]
```

- Every `Form` / `ListForm` field **appears in Quick Create by default**.
- `hideQuick: true` opts out: the field is not shown in the Quick Create UI and is submitted as a hidden input using `quickDefault` (if set) or the DTO property default.
- Full create/edit modals still show all form fields.

`ListForm` accepts the same arguments as `Form`:

```php
#[ListForm('text')]
#[Value]
public string $title = '';

#[ListForm('select', 'Status', hideQuick: true)]
#[Value]
public ?int $status_id = null;
```

`ListForm` does **not** imply `Value` — add `Value` when the property belongs in detail views.

---

## Value (display projection)

Original design: mark what contributes a **display value**.

- **Scalar:** include in detail/modal field sets (`$dto->getFields()`).
- **Nested Spatie Data:** require `#[Value]`; expose `{relation}.{displayField}` instead of the FK. Matching `*_id` is skipped when the relation property exists.
- **Override:** `#[Value('code')]` forces `project.code` instead of the default heuristic (`name` → `title` → `category` → `label` → first `InList` scalar on the nested DTO).

```php
#[InList]
#[Value]
public ?ProjectViewData $project = null;   // → project.name

#[Value('code')]
public ?ProjectViewData $project = null;   // → project.code
```

---

## InList vs Value

| | `InList` / `ListForm` | `Value` |
|--|--|--|
| Used for | Datatable columns (`listColumns`) | Detail / modal values (`getFields`) |
| Empty set | Falls back to all `Value` paths | — |
| Nested | Collapses to main display field | Same; requires `#[Value]` |

---

## Hide

```php
#[Hide]
public ?string $internal_note = null;
```

Excluded from list/value discovery. Does not affect `Form` / Quick Create (use `hideQuick` for that).

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
  Form / ListForm  → formFields (+ hideQuick → Quick Create)
  InList / ListForm → (optional; often mirrored on ViewData)
  Value            → detail if this DTO is also used for display

View DTO (*ViewData)
  InList           → listColumns
  Value            → getFields / extractValues
  Nested *ViewData → relation.{name|title|…} when marked Value
```

After changing attributes in production, run:

```bash
php artisan dto:clear-metadata
php artisan dto:cache-metadata
```

See also [dto-metadata.md](dto-metadata.md).
