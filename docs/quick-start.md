# Quick start — generic CRUD framework

This Laravel 12 project includes an extra **CRUD layer**. For a typical entity you do **not** write:

- a `Route::resource` entry
- a Form Request class
- a Policy
- a dedicated controller (unless you opt in later)

You add a **model**, a **repository**, and two **DTO** classes. The framework then provides list, create, edit, details, modals, and API/datatable JSON.

This guide is framework-only. Your app’s business meaning belongs in your own docs.

**New to Laravel?** You still need [Laravel’s docs](https://laravel.com/docs) for Eloquent, Blade, and `php artisan`. This page only explains *this* layer.

**Already know Laravel?** Jump to [Add an entity](#add-an-entity).

---

## Words used here

| Term | Meaning |
|------|---------|
| **DTO** | A Spatie Data class in `app/Data/`. It describes fields for forms or lists — it is not the Eloquent model. |
| **Attribute** | PHP `#[Form('text')]` on a DTO property. |
| **Repository** | `app/Repositories/{Model}Repository.php` — the only place HTTP should load/save Eloquent models. |
| **`{Model}`** | StudlyCase name you choose (`Invoice`). **`{resource}`** is the URL (`invoices`). |

---

## What is different from stock Laravel

| In a typical Laravel tutorial | Here |
|-------------------------------|------|
| You register `Route::resource` | You put `#[RoutableAttribute]` on the model; routes are registered for you |
| You write a Form Request | Validation is inferred from `{Model}Data`; declare only special rules ([validation.md](validation.md)) |
| You write an API Resource | List/detail JSON comes from `{Model}ViewData` |
| You write a Policy | `EntityAccess` + `entity_can()` |
| You write `{Model}Controller` | Shared `CrudController` until you need extra actions |

You still use normal Laravel: migrations, Eloquent, Fortify login, `bootstrap/app.php`.

---

## How the pieces fit

```
Model  (#[RoutableAttribute])  +  Repository  +  two DTOs
                              │
                              ▼
                 shared CrudController
                              │
              pages/generic/*   (or your override blades)
                              │
                 themes/{theme}/   (look and feel)
```

How much you own:

| Profile | You maintain | Framework still does |
|---------|----------------|----------------------|
| **virtual** (start here) | PHP + migration only | Routes, one shared controller, shared Blade templates |
| **hybrid** | Plus blades under `pages/{resource}/` | Same shared controller |
| **material** | Plus `{Model}Controller` | List API still shared unless you set `api_controller` |

Stay **virtual** until a screen really cannot be described with DTO attributes.

---

## Add an entity

Pick a `{Model}` name. You do not need an example model in the repo.

### 1. Generate files

```bash
php artisan make:entity {Model} --fields="name:string" --display=name
php artisan migrate
```

In production (or if DTO cache is on), also:

```bash
php artisan dto:cache-metadata --class=App\\Data\\{Model}Data
php artisan dto:cache-metadata --class=App\\Data\\{Model}ViewData
```

You now have:

| File | Role |
|------|------|
| `app/Models/{Model}.php` | Database row (`BaseModel` + `#[RoutableAttribute]`) |
| `app/Repositories/{Model}Repository.php` | Load/save |
| `app/Data/{Model}Data.php` | Create/edit form; its properties also define validation |
| `app/Data/{Model}ViewData.php` | Table columns + details page |
| a migration | Table with id, your fields, timestamps, audit users, soft deletes |

After login, the entity should appear in the CRUD menu (unless you used `--no-nav`).

### 2. Tune the form and the table

On `{Model}Data` (the form):

```php
#[ListForm('text')]
public string $name = '';
```

`ListForm` means: this field is on the **form** and is a **list column**.

On `{Model}ViewData`, use `#[InList]` for extra columns, `#[Hide]` to skip a property on details.

Full list: [attributes.md](attributes.md).

### 3. Put business rules in the repository

`BaseRepository` already implements list/create/update/delete. Override `create` or `update` for uniqueness or defaults.

Do **not** write `{Model}::query()` in a controller. If you need the Eloquent model (for example to load relations):

```php
$this->modelRepository->findModel($id);
$this->modelRepository->findModel($id, ['relationName']);
```

### 4. Change the UI only when generic is not enough

| You need | Do this |
|----------|---------|
| Another field or column | Edit the DTO — stay virtual |
| Different form HTML | `php artisan entity:eject {Model}` (hybrid). After later DTO edits: `entity:materialize-form {Model}` |
| Extra URLs (export, approve, …) | Material: `{Model}Controller` extends `CrudController`. Override `buildEditForm` or `show` — do not copy `create`/`edit` if they match the parent |
| Different look (colors, buttons) | Edit `resources/views/themes/{theme}/`, not `pages/` |

`pages/` templates must not use theme CSS class prefixes (for Metronic, that means no `kt-*`). Use `<x-form-card>`, `<x-form-card-body>`, `<x-form-card-footer>`, `<x-button>`, `Form::field(...)`.

---

## What happens on a request

**List:** browser opens `/{resource}` → `CrudController@index` → generic list Blade → the table loads rows from `GET /api/{resource}`.

**New record (full page):** `/{resource}/create` → generic form.

**New record (modal):** `/{resource}/modal/create` with header `X-Modal-Request` → modal form. The list “quick create” button uses a smaller modal (`modalQuickCreate`).

**Save:** `POST /{resource}` → `EntityValidator` validates against `{Model}Data` → repository `create()`. Invalid input goes back to the form with errors under each field ([validation.md](validation.md)).

If a model has `project_id` or `workspace_id`, the controller may fill those from the current session (“sticky context”). That is optional; not every entity needs it.

If someone opens a modal URL in a new tab (no AJAX header), they are **redirected** to the matching full-page screen (`/{resource}/{id}`, edit, or create). Refresh after opening a modal from a list keeps you on that list, with `#modal=…` in the hash so the overlay can reopen.

To keep an entity on full pages only: `'use_modals' => false` in `config/crud.php`.

---

## Who can see or edit what

There are no Laravel Policies for these entities. The shared controller maps `index`/`show` → view, `create`/`store` → create, `edit`/`update` → update, `destroy` → delete.

In Blade: `entity_can('{Model}', 'update')`.

A user marked super-admin skips these checks.

If you add a **new** controller method (`export`, `approve`, …), register it in `EntityAccess::abilityForControllerMethod`. Otherwise it is treated as **view**.

---

## Next

1. [conventions.md](conventions.md) — file naming (easy to get wrong)
2. [attributes.md](attributes.md) — every `#[…]` you can put on a DTO
3. [docs/README.md](README.md) — full reading list
