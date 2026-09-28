# Framework docs

Read these if you are new to this Laravel CRUD layer. They are **framework-only** (safe to copy into a new project). Application domain docs live elsewhere.

**You should already know** Laravel basics: Eloquent models, migrations, Blade, and `php artisan`. This folder explains the extra CRUD layer on top — not Laravel itself.

## Start here

1. **[quick-start.md](quick-start.md)** — what this layer is and how to add an entity (do this first).
2. **[conventions.md](conventions.md)** — naming rules (files that must exist together).
3. **[attributes.md](attributes.md)** — `#[Form]`, `#[ListForm]`, `#[InList]` on your Data classes.

Then only as needed:

| When | Doc |
|------|-----|
| Changing list/form/modal screens | [ui-views.md](ui-views.md) |
| `make:entity`, eject, materialize | [entity-scaffolding.md](entity-scaffolding.md) then [console-commands.md](console-commands.md) |
| Why dropdowns appear on create/edit | [entity-form-builder.md](entity-form-builder.md) |
| Forms look stale after a DTO change (production cache) | [dto-metadata.md](dto-metadata.md) |
| Datatable JSON shape | [collection-flattener.md](collection-flattener.md) |
| Who can see which records (tenants), adding a tenant-owned entity | [tenancy.md](tenancy.md) |
| How saves are validated, declaring special validation rules | [validation.md](validation.md) |

## Words used everywhere

| Term | Meaning |
|------|---------|
| **Entity** | One kind of record (one Eloquent model + screens). |
| **CRUD** | Create, read, update, delete — list, form, details, save, delete. |
| **DTO** | Data Transfer Object — a Spatie `Data` class in `app/Data/` that describes form fields or list/detail columns. Not the database row. |
| **Attribute** | PHP `#[…]` on a DTO property or model method (not HTML attributes). |
| **Repository** | Class in `app/Repositories/` that talks to the database. Controllers call this, not `Model::query()`. |
| **Blade** | Laravel HTML templates (`.blade.php`). |
| **Virtual / hybrid / material** | How much of the entity you own vs the shared templates — see quick-start. |
| **`{Model}`** | Your StudlyCase class name. **`{resource}`** is the URL name (plural snake_case). |
