# MCP endpoint (AI assistants)

How an AI assistant such as Claude uses BAssist directly: what the endpoint is, how it is secured, which tools it offers, and how to add one.

**Read first:** [api-platform.md](api-platform.md). The MCP endpoint reuses its tokens, permissions and services; this page only covers what is different.

## Purpose

MCP (Model Context Protocol) is an open standard that lets an AI assistant discover and call an application's functions. BAssist publishes a list of tools; the assistant reads their descriptions and calls them when the conversation needs them.

With it, a user can ask an assistant to check a project's readiness, draft stakeholder needs and requirements into the spine, write scenarios, or pull the Gherkin for a feature before coding it. The assistant does that in BAssist as the user, instead of describing what the user should click.

The JSON API and the MCP endpoint differ only in who the client is:

| | JSON API | MCP endpoint |
|---|---|---|
| Client | Code written in advance (script, CI, app) | An assistant deciding at runtime |
| Discovery | A developer reads [api-platform.md](api-platform.md) | The server sends the tool list, descriptions and input schemas |
| Shape | Resource routes | A small set of task-shaped tools |
| Descriptions | For developers | Read by the model, so they decide how well it uses the tool |

## Architecture

```
   AI assistant (MCP client)
            │  POST /mcp   JSON-RPC, Authorization: Bearer <personal API token>
            ▼
   auth:sanctum ── mcp.channel ── throttle           routes/ai.php
            ▼
   App\Mcp\Servers\BAssistServer                     name, instructions, tool list
            ▼
   App\Mcp\Tools\*Tool                               read arguments, call a service, return the result
            │
            ├── ProjectInsightsService               readiness, traceability, acceptance plan, Gherkin, lineage
            └── EntityRecordService                  describe, list, get, create, update, delete
                         │
                         ▼
        role permissions · edit-DTO validation · tenant payload guard · repositories · TenantScope
```

The rules from the API platform apply unchanged:

- **No business logic in a tool.** A tool validates its own arguments, calls a service and returns the result. `ProjectInsightsService` is the same class the API controllers call, so the web page, the API and the assistant always see the same numbers.
- **The assistant is the user.** It signs in with that user's personal API token. Tenant isolation, role permissions and validation run in the services, so a tool cannot skip them.
- **Read-only tokens stay read only.** Every write tool calls `requireWrite()`, which refuses a token without the `write` ability. (`api.ability` is not used on this route because every MCP call is a `POST`.)
- **Changes are visible.** `mcp.channel` marks the request, and each history entry written during it is stored with `via = mcp` and shown as "via AI assistant" under the user's name.

### Files

| File | Role |
|---|---|
| `routes/ai.php` | Registers `/mcp` and its middleware. Loaded by the `laravel/mcp` service provider |
| `app/Mcp/Servers/BAssistServer.php` | Server name, the instructions the assistant receives, and the tool list |
| `app/Mcp/Tools/BAssistTool.php` | Base class: `respond()` (not-found handling) and `requireWrite()` |
| `app/Mcp/Tools/*Tool.php` | One class per tool |
| `app/Services/ProjectInsightsService.php` | Derived project views (shared with the API controllers) |
| `app/Services/EntityRecordService.php` | Entity CRUD addressed by entity name |
| `app/Http/Middleware/MarkMcpChannel.php` | `mcp.channel` |
| `app/Support/RequestChannel.php` | The channel of the current request |
| `database/migrations/2026_10_04_140000_add_via_to_activity_log.php` | Adds `activity_log.via` |
| `tests/Feature/McpServerTest.php` | Tests |

The package is `laravel/mcp` (Laravel's own). It implements the protocol: handshake, tool listing, message format, errors. Only `routes/ai.php` and `app/Mcp/` depend on it. Without the package installed the rest of the application runs normally and `/mcp` simply does not exist.

## Tools

| Tool | Kind | What it does |
|---|---|---|
| `list-projects` | read | Projects the user can see, with ids |
| `get-readiness` | read | Gaps by severity, progress per lineage level, score |
| `get-lineage` | read | Parents, children, gaps and the five-level rail for one record |
| `get-traceability` | read | The traceability matrix; `orphans_only`, `gap` |
| `get-acceptance-plan` | read | Scenarios and acceptance criteria as checks |
| `get-gherkin` | read | `.feature` documents for a feature or a whole project |
| `describe-entity` | read | Entities the user may use; fields, rules and filters of one entity |
| `list-records` | read | Records of an entity; `project_id`, `filters`, `search`, `limit` |
| `get-record` | read | One full record |
| `create-record` | write | Create a record; `data` holds the fields |
| `update-record` | write | Partial update: only the fields sent change |
| `delete-record` | write, destructive | Delete a record |

Design choices worth knowing before changing them:

- **Generic record tools instead of one tool per entity.** Twelve tools cover more than twenty entities, and a new entity needs no MCP work. `describe-entity` gives the assistant the field list and validation rules from the entity's edit DTO, so they never drift from the forms.
- **`update-record` merges.** The JSON API replaces the whole record on `PUT`. An assistant that sent one field would blank the others, so `EntityRecordService::update()` loads the current edit data and overlays what was sent before validating.
- **Lists are compact.** `list-records` returns scalar fields only, long text cut to 300 characters, 50 rows by default (200 maximum). `get-record` returns everything.
- **Hidden entities.** `EntityRecordService::HIDDEN` (`Tenant`) is never offered, whatever the role.
- **Annotations.** Read tools carry `readOnlyHint`; `delete-record` carries `destructiveHint`. Clients use these to decide when to ask the user for confirmation.

### What the assistant is told

`BAssistServer::$instructions` is sent to the assistant when it connects. It explains the five lineage levels and how to work (start from readiness, describe before writing, link every record to its parent, never invent business facts, record unknowns as open assumptions). Edit it when the method changes; keep it short, because it is sent on every connection.

## Connecting a client

1. The user creates a token under **their name → API tokens**. Read only is enough to review a project; tick write access to let the assistant create and change records.
2. Point the client at `https://<host>/mcp` with the header `Authorization: Bearer <token>`.

For Claude Code:

```bash
claude mcp add --transport http bassist https://bassist.example.com/mcp \
  --header "Authorization: Bearer 12|k3J…"
```

A client on the same machine can use a local address such as `http://localhost:8000/mcp`. A client running on the internet (for example an assistant in a web browser) can only reach a server that is itself reachable from the internet.

To explore the tools by hand:

```bash
php artisan mcp:inspector mcp
```

## Adding a tool

1. **Decide whether one is needed.** A new entity needs nothing. A new derived view does.
2. **Put the logic in a service**, with its authorization, so the web page or API can use it too.
3. **Create the tool** in `app/Mcp/Tools/`, extending `BAssistTool`:
   - `$name` (kebab-case), `$title`, `$description`. Write the description for the model: what the tool returns and when to use it.
   - `schema()` describes each argument; `handle()` validates them again with `$request->validate()`.
   - Wrap the body in `$this->respond(fn () => [...])`.
   - Add `#[IsReadOnly]` for a read tool. For a write tool call `$this->requireWrite($request)` first and add `#[IsDestructive]` if it removes data.
4. **Register it** in `BAssistServer::$tools`.
5. **Test it** in `tests/Feature/McpServerTest.php`: as a user, against another tenant's record, with a role that lacks the permission, and (write tools) over HTTP with a read-only token.
6. **Document it** in the table above.

## Testing

```bash
php artisan test --filter=McpServerTest
```

Two styles are used. Tool behaviour is tested directly (`BAssistServer::actingAs($user)->tool(...)`). Token abilities and the history label are tested over HTTP with real tokens, because they depend on the route's middleware. The tests are skipped with a message when `laravel/mcp` is not installed.

## Deployment notes

- `composer require laravel/mcp`, then `php artisan migrate` (adds `activity_log.via`).
- HTTPS only, as for the API.
- The route is limited to 120 requests a minute per user (`throttle:120,1` in `routes/ai.php`).
- If routes are cached, run `php artisan route:cache` again after deploying.

## Not included yet

| Topic | Status |
|---|---|
| OAuth sign-in | Needed before a public multi-tenant launch, so users can connect without pasting a token. `laravel/mcp` supports it through Laravel Passport (`Mcp::oauthRoutes()`); personal tokens keep working alongside |
| Review tools (approve / request changes) | Not exposed; approvals stay a human action in the web UI |
| Comments | Not exposed |
| MCP resources and prompts | Tools only |
