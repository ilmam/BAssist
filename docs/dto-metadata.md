# DTO metadata cache

**When do I care?** On your laptop, usually never — the app reads PHP attributes on first use. In **production**, after you change a DTO (add a field, change `#[Form]`, add a new `*Data.php` file), run the cache commands below or forms/tables can look stale.

Forms, table headers, and details pages need to know which DTO properties are fields or columns. That lives in PHP attributes ([attributes.md](attributes.md)):

- `#[Form]` / `#[ListForm]` — form controls (`hideQuick`, `readonly`, optional `uiSpan`)
- `{Model}ViewData` public properties except `#[Hide]` — details (optional `#[Value('…')]`)
- `#[InList]` / `#[ListForm]` — table columns

`App\Support\DtoMetadata` reads those attributes once, stores the **schema** (property names and attribute arguments) in Laravel’s cache, and reads **values** from live DTO instances on each request.

---

## How it works

```
Deploy or after DTO changes:  php artisan dto:cache-metadata
  Reflection runs once per DTO class → stored in app cache

Each HTTP request in production
  Schema comes from cache (no reflection)
  Field values still come from the live DTO / database
```

When cache is **off** (typical locally), each request reflects on first use of that DTO class. That is fine for development.

### What is cached

| Cached (schema) | Not cached (live data) |
|-----------------|------------------------|
| Property names with `Form` | Actual field values |
| Attribute arguments (`'text'`, `'select'`, related model name) | Dropdown options from the database |
| Dot-notation paths for details | API / table row data |

Schema is the same for every user. It is stored in Laravel cache, not in session.

### Where the code uses it

| Feature | Call |
|---------|------|
| Create/edit forms | `DtoMetadata::for($editDto)->formFields()` |
| Table column headers | `DtoMetadata::for($viewDto)->listColumns()` |
| Details / view modal | `$dto->getFields()` → `DtoMetadata` |

---

## Configuration

File: `config/dto-metadata.php`

| Key | Default | Purpose |
|-----|---------|---------|
| `enabled` | `true` when `APP_ENV=production` | Persist schema in Laravel cache |
| `directories` | `[app_path('Data')]` | Where to scan for Data classes |
| `cache.store` | `CACHE_STORE` / **`file`** | Cache driver — Redis is **not** required |
| `cache.prefix` | `dto-metadata` | Key prefix |
| `cache.duration` | `null` (forever) | `null` = until you clear it |

```env
DTO_METADATA_CACHE_ENABLED=true
CACHE_STORE=file
```

| Driver | Survives across requests? | Typical use |
|--------|---------------------------|-------------|
| **file** (default) | Yes — `storage/framework/cache/` | Local and production |
| **redis** | Yes | Only if the app already uses Redis for cache |
| **array** | No | Tests |

---

## Commands

### Warm (deploy / after DTO changes in production)

```bash
php artisan dto:cache-metadata
php artisan dto:cache-metadata --class=App\\Data\\{Model}Data
```

Recommended with other deploy optimize steps:

```bash
php artisan config:cache
php artisan route:cache
php artisan dto:cache-metadata
php artisan data:cache-structure   # Spatie Laravel Data — a separate cache
```

### Clear

```bash
php artisan dto:clear-metadata
php artisan dto:clear-metadata --class=App\\Data\\{Model}Data
```

Then re-warm in production:

```bash
php artisan dto:cache-metadata --class=App\\Data\\{Model}Data
```

Clear (or clear that class) when you:

- Add, remove, or rename a DTO property
- Change `Form`, `Hide`, or `Value` on a property
- Add a new `*Data.php` / `*ViewData.php` and need production to pick it up

For **hybrid** entities (owned form blades), also:

```bash
php artisan entity:materialize-form {Model} --force
```

Virtual entities pick up form changes automatically via `$formFields`. You do **not** need to clear when only **row values** change (someone edits a name in the UI).

Nuclear option: `php artisan cache:clear` wipes **all** app cache. Prefer `dto:clear-metadata`.

---

## PHP API (advanced)

```php
use App\Support\DtoMetadata;

$fields = DtoMetadata::for(\App\Data\{Model}Data::class)->formFields();
$columns = DtoMetadata::for(\App\Data\{Model}ViewData::class)->listColumns();
$values = DtoMetadata::for($dto)->extractValues($dto);

DtoMetadata::warm();
DtoMetadata::clear();
DtoMetadata::clear(\App\Data\{Model}Data::class);
```

---

## Adding a new entity

1. Create `{Model}Data` with `#[Form]` and `{Model}ViewData` with `#[InList]` / `#[Hide]` (or use `make:entity`).
2. **Local** (cache off): metadata is built on first use.
3. **Production:** after deploy, `php artisan dto:cache-metadata`.

---

## Two caches

This project also uses Spatie’s structure cache (`php artisan data:cache-structure`). After changing DTOs in production, run **both** (or `cache:clear` once).

| Cache | Command | Purpose |
|-------|---------|---------|
| Spatie data structures | `data:cache-structure` | Validation, casting |
| DTO metadata | `dto:cache-metadata` | Form fields, list columns, details discovery |

---

## Troubleshooting

**Form or list columns look stale after a code change**

1. `php artisan dto:clear-metadata`
2. `php artisan dto:cache-metadata`
3. Hybrid entity: `php artisan entity:materialize-form {Model} --force`
4. If you use `config:cache`, run `php artisan config:cache` again

**New DTO class not found by the warm command**

- The class must extend `Spatie\LaravelData\Data`
- The file must live under `app/Data/` (or a path in `config/dto-metadata.php`)

**Table is missing `id`**

- `listColumns()` includes `id` only when the DTO has a public `$id` property.

**Do I need Redis?** No. File cache is enough.
