# Project access: inviting people to one project

**When do I care?** When you change who can see or edit a project, add an entity that must respect grants, or touch tenant scoping. Users of a tenant already see all of that tenant's projects; this is for **inviting people to a single project**, including people from outside the tenant.

## What people get

| Level | Can do |
|-------|--------|
| `view` | Read everything in the project, use exports (full package, BABOK documents, UI designs). |
| `comment` | `view` plus add and reply to comment threads. Closing a thread is a sign-off (approve permission), which guests never have. |
| `edit` | `comment` plus create, update and delete records. |

Never for guests, at any level: approve/sign off, edit the project itself, workspaces, member management, personal API tokens, MCP, admin screens.

The inviter can change a level, resend, revoke or remove a member at any time; it applies on the person's **next request** (grants are never cached across requests).

## Who can invite

A super-admin, or a user of the **project's own tenant** whose role can update `Project` (`ProjectAccess::canManageMembers`). Guests cannot. Change that one method to tighten or widen the rule.

## The three layers

1. **Visibility** (`Tenancy`, `TenantScope`, each model's `applyTenantConstraint`). A person sees their tenant's projects **plus** projects they hold a grant on. `Tenancy::visibleProjectIdsQuery($tenantId)` is the single source; `Tenancy::scopeTenantId()` is `0` for a tenantless person with grants (matches no workspace, so only grants contribute) and `null` when there is nothing to show. `Project` and `Workspace` widen explicitly; everything else follows through its project.
2. **Entity abilities** (`EntityAccess::can`). Guests (`users.is_guest`) have no role: `ProjectAccess::guestAllows` answers from their highest grant. `Workspace` and `Tenant` are blocked; `Project`, `Priority`, `Status` are read-only.
3. **Per-record write guard** (`EnforcesProjectAccess` on `BaseModel`, `Comment`, `Approval`, `Attachment`). `EntityAccess` is global and cannot tell "edit project A, read project B", so on `saving`/`deleting` the record's project (current and original, so it cannot be moved) is checked with `ProjectAccess::assertCan`. It only bites on projects **outside the person's own tenant**; on their own tenant the role decides, as before. Models owned another way override `owningProjectIds()` (`Project`, `Scenario`, `Attachment`).

## Guests and tenants

`users.is_guest` accounts are created from an invitation: no tenant, no role. `AssignDefaultTenant` skips them, otherwise they would be put into the shared tenant on first request. A guest can hold grants on projects of several tenants, and an existing tenant user can be granted a project of another tenant (their own tenant stays as it was).

## Invitations

`ProjectInvitationService` creates a `project_invitations` row with only the **hash** of a random link token, and emails the link (`invitations.show`). Re-inviting the same address updates the pending row and issues a new link; the old link stops working. Links expire after `tenancy.invitations.ttl_days` (7). Someone with an account must sign in as the invited address before accepting; someone without one registers on the page and becomes a guest.

## Adding an entity

An entity with a `project_id` column needs nothing: it is scoped through its project and guarded by `BaseModel`. An entity owned another way (no `project_id`) must override `applyTenantConstraint` (visibility) **and** `owningProjectIds()` (writes), or guests could read or change it across projects. A model that does not extend `BaseModel` but can be written by guests must `use EnforcesProjectAccess`.

## Known limits

- Bulk query updates (`Model::query()->update(...)`) bypass model events and therefore the write guard; use model saves for anything a guest can reach.
- The coarse ability of a guest is their highest grant, so with `edit` on one project and `view` on another the UI shows edit controls on both; the server refuses the writes on the read-only one.
- A payload that references a parent record in another project the person can see (but not edit) is accepted; only the record's own project is checked.

Tests: `GuestVisibilityTest`, `GuestPagesTest`, `GuestWriteAccessTest`, `GuestCommentsTest`, `GuestRestrictionsTest`, `ProjectMembersTest`, `ProjectInvitationTest`.
