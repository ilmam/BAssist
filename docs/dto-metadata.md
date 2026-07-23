# DTO metadata cache

Forms, datatable column headers, and detail views need to know which properties on a Data class (DTO) are form fields or display columns. That information lives in PHP attributes (see [attributes.md](attributes.md) for the full reference):

- `#[Form('text')]` / `#[ListForm('text')]` — form control type (create/edit; `hideQuick` / `readonly` for Quick Create). Layout spans are not Form attributes — see [ui-views.md](ui-views.md#override-spans)
- Detail/value projection — all public props on `*ViewData` except `#[Hide]` (optional `#[Value('…')]` nested display override)
- `#[InList]` / `#[ListForm]` — datatable columns

Previously, the app discovered those attributes with **PHP reflection on every request**. That is fine for small apps but adds avoidable overhead in production as entity count and traffic grow.

`App\Support\DtoMetadata` centralizes discovery, caches the **schema** (property names and attribute arguments) in Laravel's cache, and reads **values** from live DTO instances at runtime.

---

## How it works

```
┌─────────────────────────────────────────────────────────────┐
│  Deploy or after DTO changes: dto:cache-metadata            │
│  Reflection runs once per DTO class → stored in app cache   │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  Each HTTP request (production)                              │
│  DtoMetadata reads schema from cache → no reflection         │
│  Values read from $dto->property (always runtime)            │
└─────────────────────────────────────────────────────────────┘
```

When cache is **disabled** (default in local dev), each request reflects on first use for that DTO class. That is acceptable for development; enable file cache locally if you prefer parity with production.

### What is cached

| Cached (schema) | Not cached (runtime) |
|-----------------|----------------------|
| Property names with `Form` | Actual field values (`$dto->category`) |
| Attribute arguments (`'text'`, `'select'`, model name) | Select option lists from the database |
| Dot-notation paths for detail/value fields | API/datatable row data |

Schema is **app-wide** — the same for every user. It is stored in Laravel cache, not in session.

### Where it is used

| Feature | API |
|---------|-----|
| Create/edit forms | `DtoMetadata::for($editDto)->formFields()` |
| List column headers | `DtoMetadata::for($viewDto)->listColumns()` |
| Detail / modal views | `$dto->getFields()` → delegates to `DtoMetadata` |
| Legacy helper | `AttributeHelper::getPropertyAttributes(..., 'Form')` → delegates to `DtoMetadata` |

---

## Configuration

File: `config/dto-metadata.php`

| Key | Default | Purpose |
|-----|---------|---------|
| `enabled` | `true` when `APP_ENV=production` | Persist schema in Laravel cache |
| `directories` | `[app_path('Data')]` | Where to scan for Data classes |
| `cache.store` | `CACHE_STORE` / **`file`** | Cache driver — **Redis not required** |
| `cache.prefix` | `dto-metadata` | Key prefix |
| `cache.duration` | `null` (forever) | TTL; `null` = until cleared on deploy |

Environment override:

```env
DTO_METADATA_CACHE_ENABLED=true
CACHE_STORE=file
```

### Cache driver notes

| Driver | Cross-request persistence | Typical use |
|--------|---------------------------|-------------|
| **file** (default) | Yes — `storage/framework/cache/` | Production and local; no extra services |
| **redis** | Yes | Only if your app already uses Redis for cache |
| **array** | No | Tests only |
| **session** | Not used | Wrong scope — metadata is not per-user |

With **`file`** cache, warmed metadata survives across requests without Redis, queues, or any other infrastructure.

---

## Artisan commands

### Warm the cache (deploy / after DTO changes in production)

Discover and cache all Data classes under `config('dto-metadata.directories')`:

```bash
php artisan dto:cache-metadata
```

Cache a single class:

```bash
php artisan dto:cache-metadata --class=App\\Data\\CategoryData
```

**Recommended deploy step** (alongside other optimize commands):

```bash
php artisan config:cache
php artisan route:cache
php artisan dto:cache-metadata
php artisan data:cache-structure   # Spatie Laravel Data (separate cache)
```

### Clear the cache

Clear **all** DTO metadata entries:

```bash
php artisan dto:clear-metadata
```

Clear one class (after changing attributes on that DTO):

```bash
php artisan dto:clear-metadata --class=App\\Data\\CategoryData
```

Then re-warm if you are in production:

```bash
php artisan dto:cache-metadata --class=App\\Data\\CategoryData
```

### When you must clear

Run `dto:clear-metadata` (or clear the specific class) whenever you:

- Add, remove, or rename a DTO property
- Change `Form`, `Hide`, or `Value` on a property
- Add a new `*Data.php` / `*ViewData.php` class and need production to pick it up without waiting for lazy discovery

For **hybrid** entities with materialized form blades, also regenerate owned form markup after changing `Form`:

```bash
php artisan entity:materialize-form Category --force
```

Virtual entities pick up form changes automatically via `$formFields`; hybrid entities bake fields into the blade file. See [entity-scaffolding.md](entity-scaffolding.md#materialized-forms).

You do **not** need to clear when only **data values** change (e.g. editing a category name in the database).

### Nuclear option

```bash
php artisan cache:clear
```

That clears **all** application cache (Spatie data structures, DTO metadata, etc.). Prefer `dto:clear-metadata` for a targeted reset.

---

## Programmatic API

```php
use App\Support\DtoMetadata;

// Form schema
$fields = DtoMetadata::for(CategoryData::class)->formFields();
// ['category' => ['text'], 'description' => ['textarea']]

// List columns (includes `id` when the DTO has a public $id property)
$columns = DtoMetadata::for(CategoryViewData::class)->listColumns();

// Detail view values
$values = DtoMetadata::for($dto)->extractValues($dto);

// Inspect or force-load cached schema
$schema = DtoMetadata::for(CategoryData::class)->schema();

// Maintenance
DtoMetadata::warm();                              // all configured directories
DtoMetadata::clear();                             // all cached classes
DtoMetadata::clear(CategoryData::class);          // one class
```

---

## Adding a new entity

1. Create `App\Data\{Model}Data` with `#[Form]` on editable properties.
2. Create `App\Data\{Model}ViewData` with `#[InList]` for list columns and `#[Hide]` on plumbing to exclude from detail projection.
3. In **local** (cache off): metadata is built on first use via reflection.
4. In **production**: deploy, then run:

```bash
php artisan dto:cache-metadata
```

Or clear and re-warm only the affected classes if you prefer a minimal cache update.

---

## Related caching

This project also uses [Spatie Laravel Data structure caching](https://spatie.be/docs/laravel-data) (`config/data.php`, `php artisan data:cache-structure`). That cache is **separate** from DTO metadata:

| Cache | Command | Purpose |
|-------|---------|---------|
| Spatie data structures | `data:cache-structure` | Validation, transformation, casting |
| DTO metadata | `dto:cache-metadata` | Form fields, list columns, detail field discovery |

After changing DTOs, run **both** clear/warm commands in production, or use `cache:clear` once.

---

## Troubleshooting

**Form or list columns look stale after a code change**

1. `php artisan dto:clear-metadata`
2. `php artisan dto:cache-metadata`
3. If the entity is **hybrid**, refresh materialized form blades: `php artisan entity:materialize-form {Model} --force`
4. If using `config:cache`, rebuild: `php artisan config:cache`

**New DTO class not found by warm command**

- Ensure the class extends `Spatie\LaravelData\Data`
- Ensure the file lives under `app/Data/` (or a path listed in `config/dto-metadata.php`)

**Columns missing `id` on list page**

- `listColumns()` includes `id` only when the DTO declares a public `$id` property (standard for this project's Data classes).

**Do I need Redis?**

- No. The default **file** cache driver is sufficient. Metadata is written to `storage/framework/cache/` and reused on every request until cleared.
