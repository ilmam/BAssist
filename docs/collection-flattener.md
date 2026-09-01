# Collection flattener

**When do I care?** Only if you are changing how the list API returns rows. Day-to-day CRUD does not require this class.

The list table (DataTables) expects **flat** rows keyed by dot paths (`related.name`). Repository `getAll()` returns nested DTOs (`related: { id, name }`).

`App\Support\CollectionFlattener` flattens that nested structure for the API.

```php
use App\Support\CollectionFlattener;

$rows = app(CollectionFlattener::class)->flatten($this->modelRepository->getAll());

// Optional: keep only some columns, in this order
$rows = app(CollectionFlattener::class)->flatten($collection, ['id', 'name', 'related.name']);
```

Input may be an array, or any object with `toArray()` (including a Spatie Data collection).

Used by `BaseApiController::index()` when building datatable JSON.
