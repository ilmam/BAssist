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

## Optional framework features (reusable)

Part of the framework: a new project gets them without writing code. Each is switched on per entity.

| Feature | Switch on with | Doc |
|---------|----------------|-----|
| File attachments | `#[Attachable]` + `HasAttachments` on the model | [attributes.md](attributes.md) |
| Comments (threads, @mentions, resolve) | `#[Commentable]` on the model | [collaboration.md](collaboration.md) |
| History (who changed what, old → new) | `#[Tracked]` on the model | [collaboration.md](collaboration.md) |
| Review (Approve / Request changes) | `#[Approvable]` on the model | [collaboration.md](collaboration.md) |
| Same screen as page and pop-up, collapsible sections | `<x-record-view>`, `<x-record-form>`, `<x-section>` | [ux-features.md](ux-features.md#page-and-pop-up-are-the-same-screen) |
| Pinned list columns, status badges, code chips, empty states, quick edit, Ctrl+K | automatic in the datatable / layout | [ux-features.md](ux-features.md) |

**Not yet portable — read before copying to another project:** comments and review still carry two BAssist assumptions (a `projects` table, BABOK status names). See "What is framework and what is BAssist" in [collaboration.md](collaboration.md).

## Application features (BAssist-specific)

These are not part of the reusable CRUD framework; they only make sense for a BABOK tool.

| When | Doc |
|------|-----|
| Home page, readiness panel, lineage rail, traceability graph | [ux-features.md](ux-features.md) |
| Comments and sign-offs printed in the BABOK documents / PDF export | [collaboration.md](collaboration.md) |
| Calling BAssist from another program: API tokens, readiness / traceability / lineage / Gherkin endpoints, adding an endpoint | [api-platform.md](api-platform.md) |
| Letting an AI assistant read and maintain the spine: the MCP endpoint, its tools, adding a tool | [mcp.md](mcp.md) |

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
