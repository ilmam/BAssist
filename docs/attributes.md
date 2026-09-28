# DTO and model attributes

PHP `#[…]` markers on DTO properties (and on model relation methods) tell the framework what to put on forms, tables, and details.

If you have not added an entity yet, start with [quick-start.md](quick-start.md). Naming rules: [conventions.md](conventions.md).

**Remember:** `{Model}Data` = create/edit form. `{Model}ViewData` = table + details page.

| Attribute | Put it on | What it does |
|-----------|-----------|--------------|
| `#[Form('text')]` | `{Model}Data` property | A create/edit control |
| `#[ListForm('text')]` | `{Model}Data` property | Same as `Form` **and** a table column |
| `#[InList]` | `{Model}ViewData` property | A table column (opt-in) |
| `#[Hide]` | `{Model}ViewData` property | Skip this property on details (and list discovery) |
| `#[Value('code')]` | nested ViewData property | Use `related.code` instead of the default display field |
| `#[Relation('BelongsTo')]` | model **method** | Registers an Eloquent relation (not a form field) |
| `#[Attachable]` | model **class** | Enables file storage (also `use HasAttachments`); add `#[Form('attachments')]` on the edit DTO |
| `#[OneOf('a', 'b')]` | `{Model}Data` **class** | Exactly one of the listed fields must be filled in. Other validation: [validation.md](validation.md) |

`List` cannot be a PHP class name, so the list marker is `InList`.

---

## Form and Quick Create (`{Model}Data`)

```php
#[Form('text')]
#[Form('select', 'RelatedModel')]
#[Form('select', 'RelatedModel', ktSelect: false)] // native <select>, not the enhanced widget
#[Form('textarea', hideQuick: true)]
#[Form('text', readonly: true)]
#[Form('attachments', hideQuick: true)]
```

- Every `Form` / `ListForm` field appears in **Quick Create** unless you set `hideQuick: true`. Hidden Quick Create fields are still submitted as hidden inputs using the DTO default.
- Select fields use the enhanced select from `config('ui.forms.select')` by default. Override with `ktSelect: true|false`, or type `kt-select`.
- `section: 'traceability'` (or another key) groups consecutive fields into a bordered panel (no section title).
- If the DTO has `project_id` and sticky project context is on, that field is hidden and filled for you.
- Column width is optional. Override with `uiSpan: 12` or `uiSpan: ['md' => 6, 'lg' => 4]`. See [ui-views.md — override spans](ui-views.md#override-spans).
- `readonly: true` renders the control disabled (not submitted). Empty readonly values are omitted on create.
- Full create/edit shows all form fields (same half-width layout as Quick Create).

**Optional status / priority traits:** leave `status_id` / `priority_id` as `null` on the DTO (`hideQuick: true` if they should not show). On create, `HasEntityStatus` / `AppliesDefaultPriority` fill defaults. Do not use those traits if the model has its own string lifecycle.

`ListForm` takes the same arguments as `Form`:

```php
#[ListForm('text')]
public string $title = '';

#[ListForm('select', 'Status', hideQuick: true)]
public ?int $status_id = null;
```

Edit DTOs need `Form` / `ListForm` / `InList` only — not `Value` or `Hide`. Those belong on `{Model}ViewData`.

---

## Details (`{Model}ViewData`)

Every **public** property is shown on the details page **except** `#[Hide]`.

- **Scalar:** included unless `#[Hide]`.
- **Nested Spatie Data:** included unless `#[Hide]`; the framework shows `{relation}.{displayField}` instead of the foreign key. The matching `*_id` is skipped when the relation property exists.
- **Override:** `#[Value('code')]` forces `project.code` instead of the default (`name` → `title` → `category` → `label` → first `InList` scalar). Bare `#[Value]` is unused — do not add it.

```php
#[InList]
public ?RelatedViewData $related = null;   // → related.name

#[Value('code')]
public ?RelatedViewData $related = null;   // → related.code

#[Hide]
public ?int $workspace_id = null;          // not on details
```

Typical `#[Hide]` targets: `id`, `workspace_id`, `tenant_id`, `*_count`, `is_orphan`. (`id` is still added separately as a list column via `listColumns()`.)

---

## Table vs details

| | `InList` / `ListForm` | Details |
|--|--|--------|
| Used for | Table columns | Details page / view modal |
| Inclusion | You opt in | All public props except `#[Hide]` |
| If you mark none | Table falls back to details fields | — |
| Nested | Collapses to the display field | Same; optional `#[Value('…')]` |

---

## Hide

```php
#[Hide]
public ?int $workspace_id = null;

#[Hide]
public bool $is_orphan = false;
```

Excludes the property from details (and from list discovery). Does **not** affect forms — use `hideQuick` for Quick Create.

---

## Relation (on the Eloquent model)

```php
#[Relation('BelongsTo')]
public function related() { ... }
```

Used by `App\Models\Concerns\RelationsManagerTrait` to list Eloquent relations. Unrelated to form/table display.

---

## Attachable (on the Eloquent model)

```php
#[RoutableAttribute]
#[Attachable]
class FunctionalRequirement extends BaseModel
{
    use HasAttachments;
}
```

Storage only. The create/edit control is a normal Form field on `{Model}Data`:

```php
#[Form('attachments', hideQuick: true)]
public mixed $attachments = null;
```

Same options as every other field: `hideQuick`, `help`, `uiSpan`, `section`, `readonly`. Not a database column — files save with the entity form (choose files, Save). Existing files can be marked Remove and drop on save. Details pages list downloads only. Config: `config/attachments.php`. Need Spine requirement types (Business Need, Objective, Stakeholder Need, FR, NFR, Feature) opt in already.

---

## Cheatsheet

```
{Model}Data
  Form / ListForm  → form fields (hideQuick / readonly → Quick Create)
  InList / ListForm → optional table columns on the edit DTO
  (no Value / Hide)

{Model}ViewData
  InList           → table columns (opt-in)
  public props     → details (all except Hide)
  Hide             → exclude
  Nested *ViewData → related.{name|title|…}; optional Value('…')
```

After changing attributes in **production**:

```bash
php artisan dto:clear-metadata
php artisan dto:cache-metadata
```

See [dto-metadata.md](dto-metadata.md).
