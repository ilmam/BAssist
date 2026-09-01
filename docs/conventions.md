# Framework conventions

Rules this CRUD layer expects. Framework-only (copy into new projects).

If you have not added an entity yet, start with [quick-start.md](quick-start.md).

`{Model}` = StudlyCase class name. `{resource}` = plural snake_case URL name (`Invoice` → `invoices`).

---

## Files that must exist together

The framework looks in `app/Repositories/` for `*Repository.php` (except `BaseRepository`). For each file it expects:

| Piece | Path / name |
|-------|-------------|
| Model | `app/Models/{Model}.php` |
| Repository | `app/Repositories/{Model}Repository.php` extending `BaseRepository` |
| Edit DTO | `app/Data/{Model}Data.php` — assigned to `$editDto` on the repository |
| View DTO | `app/Data/{Model}ViewData.php` — assigned to `$viewDto` |
| Optional blades | `resources/views/pages/{resource}/…` |

The model class **must exist**. If the model has **no** `#[RoutableAttribute]`, the repository can still be used internally (dropdowns), but **no** list/form URLs are created.

Turn routing off with `#[RoutableAttribute(enabled: false)]`, or `exclude` / `'disabled' => true` in `config/crud.php`.

---

## Two DTO classes

Do not put form controls and detail-only fields on the same class.

| Class | Job | Typical `#[…]` |
|-------|-----|----------------|
| `{Model}Data` | Create/edit form, Quick Create, save validation (`rules()`) | `Form`, `ListForm` |
| `{Model}ViewData` | Table rows and the details page | `InList`, `Hide`, optional `Value` |

- **`ListForm`** = “this is a form field **and** a table column” (put it on the **edit** DTO).
- **Table columns** are opt-in (`InList` / `ListForm`). If you mark none, the table uses the details fields.
- **Details page** shows every public property on ViewData **except** `#[Hide]`.

---

## Repository (database access)

Controllers and Blade should not call `{Model}::query()`. Call the repository:

| You want | Call |
|----------|------|
| One row for the details page | `getById($id)` → View DTO |
| One row for the edit form | `editById($id)` → edit DTO |
| Save / update / delete | `create` / `update` / `delete` |
| Eloquent model + relations (custom action) | `findModel($id, ['relation'])` |
| Dropdown labels | `getSelectOptions()` |

On your `{Model}Repository`:

```php
public $editDto = \App\Data\{Model}Data::class;
public $viewDto = \App\Data\{Model}ViewData::class;

protected array $listFilters = ['name']; // query-string columns the list API accepts
```

Other optional arrays: `$listRelationFilters`, `$listContextFilters`, `$listTenantScope`, `$listWithCounts`, `$listContextRelations` (see `BaseRepository` for comments).

Only **`$fillable`** columns are saved (`filterFillable()`). Extra DTO properties are ignored unless you handle them in `create`/`update`.

Dropdown labels use the model’s `$displayField`. Set `protected $displayField = 'name';` (or `title`).

---

## Models

Generated models extend `BaseModel`: primary key `id`, timestamps, soft deletes, `created_by` / `updated_by` / `deleted_by`. Use `casts()` and merge the parent:

```php
protected function casts(): array
{
    return array_merge(parent::casts(), [
        // your extra casts
    ]);
}
```

Mark Eloquent relations with `#[Relation('BelongsTo')]` (and the real return type) so the framework can list them.

**Optional traits** (add only if the table has those columns):

| Trait | Effect |
|-------|--------|
| `HasEntityStatus` | Fills `status_id` on create if empty |
| `AppliesDefaultPriority` | Fills `priority_id` on create if empty |
| `HasEntityNumber` | Sequential `number` + `code` (implement `entityNumberPrefix()`) |
| `HasAttachments` + `#[Attachable]` | Polymorphic files on the details page (upload / download / delete) |

Keep those ids `null` on the edit DTO so the trait can set them. Use `hideQuick: true` if they should not appear on Quick Create.

**Attachments** are not a DTO field. Add `use HasAttachments` and `#[Attachable]` on the model. Every CRUD entity already has `{resource}/{id}/attachments` routes; the marker turns the details panel and write endpoints on. Files live on the `local` disk (`config/attachments.php`). Upload and remove require **update** on the parent entity; download requires **view**.

---

## Controllers and URLs

- Default: `CrudController` (web) and `Api\CrudController` (table JSON).
- The model is inferred from the route name (`{resource}.index` → `{Model}`).
- Extra web class: `'controller' => …` in `config/crud.php`. Prefer overriding `buildEditForm()` or `show()`, not copying `create`/`edit`.
- New actions need an entry in `EntityAccess::abilityForControllerMethod` (otherwise they count as **view**).
- In Blade: `entity_can('{Model}', 'update')`. Super-admin skips checks.

Useful `config/crud.php` keys (the model must still be discovered): `nav`, `nav_label`, `nav_icon`, `home`, `use_modals`, `controller`, `api_controller`.

---

## Views

- **Virtual:** shared `pages/generic/*` and `pages/modals/*`.
- **Hybrid:** add files under `pages/{resource}/`; they are used automatically.
- Keep `pages/` free of theme CSS prefixes. Put look-and-feel in `themes/{theme}/`.
- Modal URLs without the AJAX header **redirect** to the matching full-page route (`show` / `edit` / `create`). JS overlay opens keep the parent URL and `#modal=…`.
- `'use_modals' => false` makes the table use full-page links.

Helpers: `model_route()`, `model_route_name()`, `model_page_view()`, `model_modal_view()`, `ui_layout()`.

---

## Forms

- Virtual: builder fills `$formFields` → `<x-form :fieldsArray="$formFields">`.
- Hybrid: Blade contains `Form::field(...)` lines. After you change `{Model}Data`, run `php artisan entity:materialize-form {Model}`.
- `#[Form('select', 'RelatedModel')]` loads options from that model’s repository.
- If the DTO has `project_id` and sticky project context is on, that field is hidden and filled for you.
- Column width: [ui-views.md — override spans](ui-views.md#override-spans).

---

## Do not add these for a normal entity

Laravel Form Requests, API Resources, and Policies are not how this CRUD layer works. Use them only if you are leaving the convention path.

See also [attributes.md](attributes.md) and [ui-views.md](ui-views.md).
