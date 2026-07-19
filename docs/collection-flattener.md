# Collection flattener

The API/datatable endpoint returns nested DTO data — a row can contain a `belongsTo` relation, so `getAll()` yields structures like `{ id, category, owner: { id, name } }`. DataTables needs **flat, single-level rows** keyed by dot paths (`owner.name`).

`App\Support\CollectionFlattener` performs that transformation.

---

## Why it exists

This logic used to live in `App\Traits\DataHelperTrait`, which was mixed into **`BaseRepository`** (and therefore every repository) even though repositories never called it. That gave the data-access layer a grab-bag of unrelated flattening/mapping/filtering methods, most of which were dead code.

The trait has been removed. Flattening is a **serialization concern**, so it now lives in a dedicated single-purpose class owned by the API layer — not the repository.

| Old (`DataHelperTrait`) | Status |
|-------------------------|--------|
| `flatten_collection` / `flatten_collection_array` | → `CollectionFlattener::flatten()` |
| `map_columns` | → `CollectionFlattener` (column selection) |
| `flatten_object`, `squash`, `filter_columns`, `getListArray` | removed (unused dead code) |

---

## Usage

```php
use App\Support\CollectionFlattener;

$rows = app(CollectionFlattener::class)->flatten($this->modelRepository->getAll());
```

Optionally restrict/order the output columns (accepts plain strings or `['field' => 'name']` entries):

```php
$rows = app(CollectionFlattener::class)->flatten($collection, ['id', 'category', 'owner.name']);
```

Input may be any array, or any object exposing `toArray()` (e.g. a Spatie Data collection).

### Where it is used

| Feature | API |
|---------|-----|
| API datatable rows | `BaseApiController::index()` → `CollectionFlattener::flatten()` |
