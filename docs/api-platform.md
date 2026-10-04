# API platform

How other programs use BAssist: who can call it, how they sign in, what they can ask for, and how to add more.

**Read this if** you are integrating something with BAssist (a script, a CI job, a single-page or mobile app, a reporting tool, an AI assistant) or adding an API endpoint.

## Purpose

BAssist's value is the Need Spine: the lineage from business need to acceptance, and the gaps in it. Until this layer existed, that was only reachable by a person in a browser. The JSON API could store and fetch records, but only for a logged-in browser session, and the derived views (readiness, traceability, acceptance plan, lineage, assembled Gherkin) had no API at all.

The API platform makes the same information available to programs, under the same rules as the web UI:

- a build pipeline can fail when a feature has no scenarios;
- a developer can pull every `.feature` file into a codebase;
- a dashboard can show readiness across projects;
- an assistant or another tool can read and maintain the spine on a user's behalf.

It is deliberately general purpose. Nothing in it is written for one particular client.

## Architecture

```
            Browser session                  Personal API token
          (cookie, first party)            (Authorization: Bearer …)
                   │                                 │
                   └───────────────┬─────────────────┘
                                   ▼
                         auth:sanctum            who is calling
                                   ▼
                         api.ability             token only: read / write
                                   ▼
            ┌──────────────────────┴───────────────────────┐
            ▼                                              ▼
   Entity CRUD routes                          Derived, read-only routes
   entity.access (role permission)             controller authorizes like its web page
   Api\CrudController                          Api\ProjectInsightsController
            │                                  Api\FeatureGherkinController
            │                                  Api\LineageController
            ▼                                              ▼
       Repositories                            ProjectInsightsService → Services
            └──────────────────────┬───────────────────────┘
                                   ▼
                    Eloquent models + TenantScope       tenant isolation
```

Three rules hold the design together.

**1. The API adds no business logic.** Every derived endpoint calls the service the web page already calls (`ProjectReadinessService`, `TraceabilityMatrixService`, `AcceptancePlanBuilder`, `SpineCascadeService`, `GherkinFeatureAssembler`) and returns its result. The web UI and the API therefore cannot disagree. If a number looks wrong, fix the service, not the controller.

**2. A caller is always a user.** There are no application-level or shared credentials. A token belongs to one user and acts as that user, so the three existing protections apply unchanged:

| Protection | Enforced by | Effect |
|---|---|---|
| Tenant isolation | `TenantScope` on every tenant-owned model, `TenantPayloadGuard` on writes ([tenancy.md](tenancy.md)) | Another tenant's record is a 404 |
| Role permissions | `entity.access` middleware and `EntityAccess::authorize()` | Missing permission is a 403 |
| History | `#[Tracked]` models record the acting user and the channel (`activity_log.via`, set by `RequestChannel`) | A change made by token is attributed to the token's owner and labelled "via API token", or "via AI assistant" when it came through [MCP](mcp.md) |

**3. A token can only narrow access, never widen it.** Abilities are checked in addition to the role, not instead of it.

### Files

| File | Role |
|---|---|
| `routes/api.php` | All API routes, inside `auth:sanctum` + `api.ability` |
| `app/Http/Middleware/EnforceApiTokenAbility.php` | `api.ability`: read tokens may only GET |
| `app/Support/ApiTokenAbility.php` | Ability names and allowed token lifetimes |
| `app/Services/ProjectInsightsService.php` | Authorizes and assembles the derived views; shared by the API controllers and the MCP tools |
| `app/Http/Controllers/Api/ProjectInsightsController.php` | Readiness, traceability, acceptance plan, project Gherkin |
| `app/Http/Controllers/Api/FeatureGherkinController.php` | One feature as a `.feature` document |
| `app/Http/Controllers/Api/LineageController.php` | Lineage of one spine record |
| `app/Http/Controllers/Api/CrudController.php` | Entity CRUD (existing; see [quick-start.md](quick-start.md)) |
| `app/Http/Controllers/ApiTokenController.php` | Profile page: create, list, revoke tokens |
| `app/Support/RequestChannel.php` | Which channel the request came through (web, API token, MCP), for the history |
| `resources/views/pages/profile/api-tokens.blade.php` | That page |
| `tests/Feature/ApiPlatformTest.php`, `ApiTokenPageTest.php` | Tests |

## Signing in

### Browser session

The app's own pages (datatables, forms) call the API with the session cookie. Sanctum treats requests from the domains in `SANCTUM_STATEFUL_DOMAINS` as first party. Sessions are not tokens, so `api.ability` does not restrict them.

### Personal API token

A user creates a token under **their name → API tokens** (`/profile/api-tokens`):

- **Name**: what the token is for.
- **Access**: read only by default; tick "Allow create, update and delete" to add `write`.
- **Expires**: 30, 90 (default), 180 or 365 days. There is no non-expiring option.

The token is shown once. Only its hash is stored (`personal_access_tokens`). Send it on every request:

```
Authorization: Bearer 12|k3J…
Accept: application/json
```

| Ability | Allows |
|---|---|
| `read` | `GET`, `HEAD`, `OPTIONS` |
| `write` | `POST`, `PUT`, `PATCH`, `DELETE` |

Revoking a token on the profile page takes effect on the next request.

### Responses to expect

| Status | Meaning |
|---|---|
| 401 | No token, unknown token, or expired token |
| 403 | The token lacks the ability, or the user's role lacks the permission |
| 404 | No such record, or it belongs to another tenant |
| 422 | Validation failed (`errors` lists the fields) |

## Endpoints

All paths are under `/api`.

### Derived views (read only)

| Method and path | Returns | Needs view permission on |
|---|---|---|
| `GET /projects/{project}/readiness` | `total_gaps`, `items` (each gap: `key`, `label`, `count`, `severity`, `url`), `severity` totals, `spine` progress, `score` | Project |
| `GET /projects/{project}/traceability` | `summary`, `gap_counts`, `coverage`, `rows` (one per trace from business need to scenario/requirement, with `gaps`) | Any of BusinessNeed, BusinessObjective, StakeholderNeed, Feature, FunctionalRequirement |
| `GET /projects/{project}/acceptance-plan` | `summary`, `rows` (test id, source, requirement, rule, check, type, status) | Any of Feature, Scenario, FunctionalRequirement |
| `GET /projects/{project}/gherkin` | `features`: each with `id`, `code`, `title`, `filename`, `gherkin` | Feature |
| `GET /features/{id}/gherkin` | The same document for one feature. `?format=text` returns the raw `.feature` text | Feature |
| `GET /{resource}/{id}/lineage` | `parents`, `gaps`, `groups` (children) and `lineage` (`steps` for the five levels, `complete`, `total`, `next` suggested action) | The entity |

Query parameters:

- `traceability`: `orphans_only=1` keeps only rows with a gap; `gap={key}` keeps rows with that gap.
- `acceptance-plan`: `feature_id`, `stakeholder_need_id`, `type`.
- `lineage`: `{resource}` is the plural snake_case entity name. Supported: `business_needs`, `business_objectives`, `stakeholder_needs`, `features`, `functional_requirements`, `non_functional_requirements`, `scenarios`. Any other entity is a 404.

`url` values inside responses point at the matching web page, so a client can link a person straight to the place to fix a gap.

### Entity CRUD

Every entity in the CRUD registry has the standard resource routes; `{resource}` is plural snake_case (`stakeholder_needs`, `functional_requirements`, …):

| Method and path | Action | Permission |
|---|---|---|
| `GET /{resource}` | List, in datatable JSON shape ([collection-flattener.md](collection-flattener.md)). Filter with `project_id`, `workspace_id` | view |
| `GET /{resource}/{id}` | One record | view |
| `POST /{resource}` | Create; validated by the entity's edit DTO ([validation.md](validation.md)) | create |
| `PUT /{resource}/{id}` | Update | update |
| `DELETE /{resource}/{id}` | Delete | delete |

### Example

```bash
TOKEN='12|k3J…'
BASE='https://bassist.example.com/api'

# Fail a pipeline when the project has critical gaps
curl -s -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json' \
  "$BASE/projects/7/readiness" | jq -e '.severity.critical == 0'

# Pull every feature file into the repo
curl -s -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json' \
  "$BASE/projects/7/gherkin" \
  | jq -r '.features[] | @base64' \
  | while read -r f; do
      echo "$f" | base64 -d | jq -r '.gherkin' > "features/$(echo "$f" | base64 -d | jq -r '.filename')"
    done
```

## Project context for token requests

The web UI keeps a "sticky" workspace and project in the session (`WorkspaceContext`, `ProjectContext`), and several services read it as a default filter. A token request has no session, so the project endpoints take the project from the URL and pin it for the request (`ProjectInsightsController::pin()`). For CRUD list calls, pass `project_id` explicitly.

## Adding an endpoint

1. **Find or write the service.** If a web page already shows the data, call the same service. If not, put the logic in a service first so a page can use it later.
2. **Add a thin controller action** under `app/Http/Controllers/Api/`. It should call the service and return JSON. Nothing else. For a derived project view, put the authorization and assembly in `ProjectInsightsService` so the MCP tools get it too.
3. **Authorize the same way the web page does**, with `EntityAccess::authorize()`. For a project-level route, type-hint `Project $project` (binding is tenant-scoped) and call `Tenancy::assertProject()`.
4. **Register the route** in `routes/api.php`, inside the `auth:sanctum` + `api.ability` group. Use `GET` for anything read only so read tokens can call it.
5. **Test it** in `tests/Feature/ApiPlatformTest.php`: with a read token, with another tenant's record (expect 404) and with a role that lacks the permission (expect 403).
6. **Document it** in the table above.

Do not add an endpoint that bypasses `TenantScope` or issues credentials that are not tied to a user.

## Testing

```bash
php artisan test --filter=ApiPlatformTest
php artisan test --filter=ApiTokenPageTest
```

The tests use real tokens (`createToken()` + `withToken()`), not `Sanctum::actingAs()`, so expiry and abilities are exercised end to end. The Sanctum guard caches its user for the life of the test application; `usingToken()` calls `forgetGuards()` first so switching tokens inside one test works.

## Deployment notes

- `php artisan migrate` creates `personal_access_tokens` if it is missing and adds `activity_log.via`.
- Serve the API over HTTPS only; a bearer token is a password.
- Set `SANCTUM_STATEFUL_DOMAINS` to the host(s) the web UI is served from.
- Optional: set `SANCTUM_TOKEN_PREFIX` (for example `bassist_`) so leaked tokens are recognisable by secret scanners.
- Optional: schedule `php artisan sanctum:prune-expired --hours=24` to delete expired tokens.

## AI assistants

The MCP endpoint (`/mcp`) is a second door onto the same services, for AI assistants. It uses the same tokens and the same rules. See [mcp.md](mcp.md).

## Not included yet

| Topic | Status |
|---|---|
| OAuth sign-in for third-party clients | Planned before a public multi-tenant launch; personal tokens stay for scripts |
| Rate limiting | None configured on the `api` group |
| API versioning (`/api/v1`) | Not introduced; add before publishing the API to outside developers |
| Derived views across several projects | One project per call |
