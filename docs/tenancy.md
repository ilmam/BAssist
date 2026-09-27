# Tenancy — who can see which records

Every signed-in user belongs to one **tenant** (an organization). A user only ever sees, changes or links to records inside their own tenant. This is enforced by the framework, so entity code does not repeat the check.

```
Tenant ─┬─ Workspace ─┬─ Project ─┬─ BusinessNeed, StakeholderNeed, Feature, FR, NFR, Risk, …
        │             │           └─ Feature ── Scenario
        └─ User       └─ …
```

If you are adding an entity, the only rule is in [Adding a tenant-owned entity](#adding-a-tenant-owned-entity).

---

## The three guards

| Guard | Where | What it stops |
|-------|-------|---------------|
| **Tenant scope** | `App\Models\Scopes\TenantScope`, registered by the `BelongsToTenant` trait | Reading, editing or deleting another tenant's record by id: list, show, edit, update, destroy, JSON API, dropdowns, route-model binding (`projects/{project}/…`), relation loads and `whereHas`. A foreign id is a **404**. |
| **Payload guard** | `App\Support\TenantPayloadGuard`, called from `BaseController` and `Api\BaseApiController` on store/update | Saving a record that *points at* another tenant's data: `project_id`, parent ids (`stakeholder_need_id`, `primary_business_need_id`, …), `*_ids` lists and ids inside editor rows (swimlane elements, …). A foreign id is a normal **validation error** on that field. |
| **Default tenant** | `App\Listeners\AssignDefaultTenant` | A signed-in user with no tenant is given one on first sign-in, so nobody lands on an empty app. |

All three ask one class, `App\Support\Tenancy`, for the current tenant, so the rules live in one place.

---

## Rules (`App\Support\Tenancy`)

| Situation | Result |
|-----------|--------|
| No signed-in user (console, seeders, queue jobs, registration) | Not enforced. |
| Signed-in user with a tenant | Everything is confined to that tenant. |
| Signed-in user **without** a tenant | Given the default tenant on first sign-in (see below). With auto-assign off: **sees nothing** (fail closed). |
| Super-admin **without** a tenant | Platform operator: not confined, sees all tenants (mirrors `EntityAccess`, where super-admin skips role checks). Only reachable with auto-assign off. |
| Super-admin **with** a tenant | Confined like everyone else. |
| Inside `Tenancy::bypass(fn () => …)` | Not enforced. For system provisioning only (`TenancyProvisioner` uses it). |

Records under a soft-deleted project or workspace are hidden everywhere.

---

## Default tenant for users without one

Configured in `config/tenancy.php`:

| `.env` key | Default | Meaning |
|------------|---------|---------|
| `TENANCY_MODE` | `shared` | `shared`: new and tenantless users join the default tenant below. `personal`: each user gets their own tenant and default workspace. |
| `TENANCY_AUTO_ASSIGN` | `true` | Give a tenantless signed-in user a tenant on first sign-in (using the mode above). `false`: such users see no data until an admin assigns a tenant. |
| `SHARED_TENANT_SLUG` / `SHARED_TENANT_NAME` | `internal` / `Internal Organization` | The default tenant in `shared` mode. |
| `SHARED_WORKSPACE_SLUG` / `SHARED_WORKSPACE_NAME` | `default` / `Default Workspace` | Its default workspace. |

For personal or single-organization use, keep `TENANCY_MODE=shared` and `TENANCY_AUTO_ASSIGN=true`: every account works in "Internal Organization" and nothing needs configuring.

Assignment happens once, on the `Illuminate\Auth\Events\Authenticated` event, and is saved on the user (`tenant_id`, `workspace_id`).

---

## Adding a tenant-owned entity

Add the trait to the model. That is all.

```php
use App\Models\Concerns\BelongsToTenant;

class Invoice extends BaseModel
{
    use BelongsToTenant;
    // …
}
```

The default assumes the table has a **`project_id`** column. If the model reaches its tenant another way, override one method. Example from `Scenario`, which hangs off a Feature:

```php
public function applyTenantConstraint(Builder $query, int $tenantId): void
{
    $query->whereIn(
        $this->qualifyColumn('feature_id'),
        DB::table('features')
            ->select('features.id')
            ->whereIn('features.project_id', Tenancy::projectIdsQuery($tenantId))
            ->whereNull('features.deleted_at')
    );
}
```

Use the base query builder (`DB::table`, `Tenancy::projectIdsQuery()`, `Tenancy::workspaceIdsQuery()`) inside this method, not Eloquent models, so the scope never recurses.

Shared lookups (`Status`, `Priority`, `Role`) are **not** tenant-owned: do not add the trait.

The repository's `$listTenantScope` is no longer needed for models with the trait (it is skipped for them). It still works for a model that cannot use the trait.

---

## When you need to step outside the scope

| Need | Do |
|------|-----|
| System provisioning (creating tenants/workspaces for a user) | `Tenancy::bypass(fn () => …)` |
| A computation that must see every row, e.g. per-project numbering | `Model::withoutGlobalScope(TenantScope::class)` — see `HasEntityNumber`. Only after the project id itself has been tenant-checked. |
| Check a route-bound project explicitly | `Tenancy::assertProject($project)` (404 unless it belongs to the current tenant) |

Do **not** use `withoutGlobalScopes()` in request code to "fix" a 404: that is the guard working.

---

## Tests

`tests/Feature/TenantIsolationTest.php` seeds two tenants and proves a user of one cannot view, edit, update, delete, list, link to or open project pages of the other (web and API), plus the default-tenant behaviour. Add a case there when you add an entity with its own custom actions.
