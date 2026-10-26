# Project Guest Access Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let tenant owners/admins invite people, including people outside the tenant, to one project with a level of `view`, `comment` or `edit` that the inviter can change or revoke at any time.

**Architecture:** A `project_user` grant table adds a second way to see a project on top of tenant ownership. `Tenancy` and the tenant-owned models' scopes widen from "projects of my tenant" to "projects of my tenant plus projects I hold a grant on". Writes are checked per record by a model trait against the grant level, because `EntityAccess` is global and not project-aware. Invitations (hashed token, email) create the grant and, for new people, a tenantless guest user.

**Tech Stack:** Laravel (PHP 8.3), Eloquent global scopes, Fortify, Blade (`ui_layout()`, `x-card`), PHPUnit with `RefreshDatabase` on in-memory SQLite, `Mail::fake()`.

**Spec:** this file, section "Decisions" (agreed in conversation on 2026-10-25).

## Decisions

- Levels: `view` (read + export), `comment` (view + add/reply/resolve comments), `edit` (comment + create/update/delete records). No `approve` for guests, no member management for guests, no API tokens/MCP for guests, no admin screens for guests.
- Multi-tenant: one person can hold grants on projects of several tenants. An existing tenant user can also be granted a project of another tenant.
- Who can invite or change/revoke: a super-admin, or a user of the **project's own tenant** whose role has `Project` `update` (`EntityAccess::UPDATE`). Assumption: "owners/admins of tenant" maps to this; change `ProjectAccess::canManageMembers` if you want a stricter rule.
- Guests can use the export pack, BABOK documents, UI-design download and other read-only exports.
- Changes by the inviter apply on the person's next request (no caching of grants across requests).

## Global Constraints

- Existing tenant isolation behaviour stays byte-for-byte for users with no grants (`tests/Feature/TenantIsolationTest.php` must keep passing unchanged).
- A `Tenancy::scopeTenantId()` of `0` means "no tenant, grants only". `0` matches no workspace, so only grants contribute. `currentTenantId()` keeps returning `null` for such users.
- Grant levels are the string values `view`, `comment`, `edit` (enum `App\Support\ProjectAccessLevel`).
- Guest users: `users.is_guest = true`, `tenant_id = null`, `role_id = null`; they are never auto-provisioned into a tenant.
- Emails on invitations are stored lowercase and trimmed.
- The working tree already holds unrelated uncommitted changes (`DatatableUi.php`, `database.sqlite`, `navigation.js`, `template.blade.php`, `DatatableUiTest.php`). Every commit step stages only the files its task touched, by explicit path; never `git add -A` or `git commit -a`.
- Tests: `php artisan test --filter=<Class>`; feature tests use `RefreshDatabase`; shared fixtures come from `tests/Concerns/SeedsProjectAccess.php` (Task 1).
- New user-facing strings go through `__('ui.…')` (add keys to the existing `ui` language file, English only).

## Review Focus

- A guest granted project A must not see project B in the **same workspace** or tenant (Task 3 test).
- `@mention` suggestions must not list every tenantless user when the viewer has no tenant (Task 7 test).
- A record cannot be moved by payload `project_id` from a project the person can edit into one they can only view (Task 6 test).
- Downgrading or revoking a grant must take effect on the next request even when the nav tree is cached (Task 9 test).
- Invited email with different letter case, expired/used/revoked token, and an invitation re-sent for the same email+project (Task 10 tests).

---

## File Structure

| File | Responsibility |
|---|---|
| `app/Support/ProjectAccessLevel.php` | enum `view`/`comment`/`edit` with ordering |
| `app/Models/ProjectMember.php` | the grant row (`project_user`) |
| `app/Models/ProjectInvitation.php` | a pending/used/revoked invitation |
| `app/Support/ProjectAccess.php` | all grant questions: level, foreign?, may write, may manage, guest entity abilities |
| `app/Models/Concerns/EnforcesProjectAccess.php` | model trait: per-record write guard |
| `app/Services/ProjectInvitationService.php` | invite, resend, revoke, change level, accept |
| `app/Http/Controllers/ProjectMemberController.php`, `ProjectInvitationController.php`, `InvitationAcceptController.php` | members tab, invite actions, public accept page |
| `app/Mail/ProjectInvitationMail.php`, `resources/views/emails/project-invitation.blade.php` | the email |
| `resources/views/pages/projects/members.blade.php`, `resources/views/pages/invitations/show.blade.php` | UI |
| `docs/project-access.md` | developer doc |
| modified: `Tenancy`, `TenantScope`, `BelongsToTenant`, `Project`, `Workspace`, `Scenario`, `ActivityLog`, `BaseModel`, `Comment`, `Approval`, `Attachment`, `EntityAccess`, `User`, `UserFactory`, `AssignDefaultTenant`, `ProjectContext`, `WorkspaceContext`, `NavTreeBuilder`, `CommentService`, `CommentController`, `ApiTokenController`, `EnforceApiTokenAbility`, and the tenant-filtering sites listed in Task 5 | |

---

### Task 1: Schema, models and test fixtures

**Files:**
- Create: `database/migrations/2026_10_25_100000_add_is_guest_to_users_table.php`, `..._100100_create_project_user_table.php`, `..._100200_create_project_invitations_table.php`
- Create: `app/Support/ProjectAccessLevel.php`, `app/Models/ProjectMember.php`, `app/Models/ProjectInvitation.php`, `tests/Concerns/SeedsProjectAccess.php`
- Modify: `app/Models/User.php`, `database/factories/UserFactory.php`
- Test: `tests/Unit/ProjectAccessLevelTest.php`, `tests/Feature/ProjectMemberSchemaTest.php`

**Interfaces:**
- Produces:
  - `enum App\Support\ProjectAccessLevel: string { View='view'; Comment='comment'; Edit='edit' }` with `rank(): int` (1,2,3), `atLeast(self $min): bool`, `label(): string`, `static values(): array`.
  - `ProjectMember` (table `project_user`): `project_id`, `user_id`, `access` (cast to the enum), `invited_by` nullable; relations `project()`, `user()`, `inviter()`. Unique `(project_id, user_id)`. Not tenant-scoped (it is the grant itself).
  - `ProjectInvitation` (table `project_invitations`): `project_id`, `email`, `access` (enum cast), `token_hash` (unique), `invited_by`, `expires_at`, `accepted_at`, `revoked_at`; scopes `pending()`; method `isUsable(): bool`.
  - `User::$is_guest` (bool cast, fillable, default false); `User::projectMemberships(): HasMany`; `UserFactory::guest()` state (`is_guest` true, `role_id` null, `tenant_id` null, `workspace_id` null).
  - Trait `Tests\Concerns\SeedsProjectAccess` with `seedTenant(string $name): array{tenant, workspace, project, need, fr}` (copy of `TenantIsolationTest::seedTenant`), `analystIn(?Tenant, ?Workspace): User` (copy of that test's helper), `grant(User $user, Project $project, ProjectAccessLevel $level): ProjectMember`, `manager(Tenant $t, Workspace $w): User` (analyst whose role also has `Project` `can_update` true).

- [ ] **Step 1: Write failing unit test** `ProjectAccessLevelTest`: `test_levels_are_ordered` asserts `Edit->atLeast(Comment)`, `Comment->atLeast(View)`, `!View->atLeast(Comment)`; `test_values` asserts `['view','comment','edit']`.
- [ ] **Step 2: Run** `php artisan test --filter=ProjectAccessLevelTest` → FAIL (class missing).
- [ ] **Step 3: Implement** the enum.
- [ ] **Step 4: Write failing feature test** `ProjectMemberSchemaTest`: `test_a_grant_is_unique_per_project_and_user` (second identical insert throws `QueryException`); `test_guest_factory_state_has_no_tenant_or_role`; `test_access_is_cast_to_enum`.
- [ ] **Step 5: Implement** migrations (foreign keys cascade on delete for `project_user`; `invited_by` nullOnDelete), models, `User`/factory changes, the test trait.
- [ ] **Step 6: Run** `php artisan test --filter="ProjectAccessLevelTest|ProjectMemberSchemaTest"` → PASS.
- [ ] **Step 7: Commit** `git add <files this task created or modified> && git commit -m "feat: project grants schema, models and test fixtures"`

---

### Task 2: `ProjectAccess` rules

**Files:**
- Create: `app/Support/ProjectAccess.php`
- Test: `tests/Feature/ProjectAccessTest.php`

**Interfaces:**
- Consumes: Task 1 models/enum/trait.
- Produces (all static, grants read with `DB::table` so no scope recursion; per-request memo, `ProjectAccess::flush()` clears it and is called by the model events in Task 9):
  - `levelFor(?User $user, int $projectId): ?ProjectAccessLevel`
  - `grantedProjectIdsQuery(User $user): \Illuminate\Database\Query\Builder` (select `project_id` from `project_user`)
  - `hasAnyGrant(?User $user): bool`
  - `maxLevel(?User $user): ?ProjectAccessLevel`
  - `isForeign(User $user, int $projectId): bool`: true when the project's tenant (via `projects`→`workspaces`) differs from `$user->tenant_id`, or the user has no tenant.
  - `can(User $user, int $projectId, ProjectAccessLevel $needed): bool`: true when not foreign; else grant level `atLeast($needed)`.
  - `canManageMembers(User $user, Project $project): bool`: not guest; and (super-admin, or `$user->tenant_id` equals the project's tenant and `EntityAccess::can($user,'Project',UPDATE)`).
  - `guestAllows(User $user, string $entity, string $ability): bool` and `const GUEST_BLOCKED_ENTITIES = ['Workspace']`, `const GUEST_READ_ONLY_ENTITIES = ['Project']` (used in Task 6).

- [ ] **Step 1: Write failing tests** in `ProjectAccessTest`: `test_level_for_returns_the_grant_level_or_null`; `test_foreign_project_is_governed_by_the_grant` (guest with Comment: `can(Comment)` true, `can(Edit)` false); `test_own_tenant_project_is_never_foreign` (analyst in tenant A, `can(...Edit)` true with no grant); `test_existing_user_of_another_tenant_is_foreign_for_the_invited_project`; `test_can_manage_members_rules` (manager of the project's tenant true; analyst without Project update false; manager of another tenant false; guest false; super-admin true).
- [ ] **Step 2: Run** `php artisan test --filter=ProjectAccessTest` → FAIL.
- [ ] **Step 3: Implement** `ProjectAccess`. `guestAllows`: false when entity in blocked list or user has no grant; `view` true; `create/update/delete` true only when `maxLevel` is `Edit` and entity not in read-only list; `approve` always false; everything else false.
- [ ] **Step 4: Run** the filter → PASS.
- [ ] **Step 5: Commit** `git add <files this task created or modified> && git commit -m "feat: ProjectAccess rules"`.

---

### Task 3: Widen visibility to granted projects

**Files:**
- Modify: `app/Support/Tenancy.php`, `app/Models/Scopes/TenantScope.php`, `app/Models/Concerns/BelongsToTenant.php`, `app/Models/Project.php:171-174`, `app/Models/Workspace.php:49-52`, `app/Models/Scenario.php:103-112`, `app/Models/ActivityLog.php:42-52`
- Test: `tests/Feature/GuestVisibilityTest.php`

**Interfaces:**
- Consumes: `ProjectAccess::grantedProjectIdsQuery`, `hasAnyGrant`.
- Produces:
  - `Tenancy::visibleProjectIdsQuery(int $tenantId): QueryBuilder`: ids of non-deleted projects whose workspace is in `workspaceIdsQuery($tenantId)` **or** whose id is in the authenticated user's grants (a single query builder, `where(fn => whereIn … orWhereIn …)`, no `union`). `projectIdsQuery` stays as the "owned by tenant" query (provisioning use).
  - `Tenancy::scopeTenantId(): ?int`: `currentTenantId()`, else `0` when the authenticated user has any grant, else `null`.
  - `Tenancy::assertProject(Project $project)` uses `scopeTenantId()` and `visibleProjectIdsQuery`.
  - `Project::applyTenantConstraint` = own-tenant workspaces **or** `id` in grants; `Workspace::applyTenantConstraint` = `tenant_id = X` **or** `id` in workspaces of granted projects; `BelongsToTenant`, `Scenario`, `ActivityLog` swap `projectIdsQuery` for `visibleProjectIdsQuery`. `TenantScope` calls `scopeTenantId()` and keeps the fail-closed `1 = 0` when it is `null`.

- [ ] **Step 1: Write failing tests** in `GuestVisibilityTest` (two tenants via `seedTenant`, plus a second project `Alpha 2` in Alpha's workspace):
  - `test_guest_sees_only_the_granted_project`: guest with `View` on Alpha → `Project::pluck('name')` is exactly `['Alpha project']`; `FunctionalRequirement` rows are only Alpha's; Alpha 2 and Bravo invisible.
  - `test_guest_sees_only_the_granted_workspace`: `Workspace::pluck('name')` equals `['Alpha workspace']`.
  - `test_guest_project_pages_resolve_only_for_granted_project`: `get(route('projects.dashboard', alpha))->assertOk()`; `…alpha2` and `…bravo` `assertNotFound()`; same for `projects.export`.
  - `test_guest_without_grants_sees_nothing`.
  - `test_tenant_user_with_a_foreign_grant_sees_both`: analyst in Bravo + grant on Alpha → sees Bravo's and Alpha's projects, not Alpha 2.
  - `test_revoking_the_grant_hides_the_project_on_the_next_request`: delete the `ProjectMember` then `get(...)->assertNotFound()`.
  - `test_soft_deleted_granted_project_is_hidden`.
- [ ] **Step 2: Run** `php artisan test --filter=GuestVisibilityTest` → FAIL.
- [ ] **Step 3: Implement** the Tenancy/scope/model changes above. Keep the `TenantScope` docblock accurate.
- [ ] **Step 4: Run** `php artisan test --filter="GuestVisibilityTest|TenantIsolationTest"` → PASS (isolation test unchanged).
- [ ] **Step 5: Commit** `git add <files this task created or modified> && git commit -m "feat: tenant scope includes granted projects"`

---

### Task 4: Guests are never auto-provisioned; contexts honour grants

**Files:**
- Modify: `app/Listeners/AssignDefaultTenant.php`, `app/Support/ProjectContext.php:119-133`, `app/Support/WorkspaceContext.php:78-92`
- Test: `tests/Feature/GuestSessionTest.php`

**Interfaces:**
- Consumes: Task 3 scopes (Project/Workspace queries are already confined by `TenantScope`).
- Produces: `ProjectContext::projectForCurrentTenant` and `WorkspaceContext::belongsToCurrentTenant` rely on the scoped models (drop the manual `auth()->user()->tenant_id` filter and the early `null` return).

- [ ] **Step 1: Write failing tests**: `test_authenticated_guest_stays_tenantless` (`actingAs(guest)` then `get(route('projects.dashboard', alpha))`, then `assertNull($guest->fresh()->tenant_id)` while `tenancy.auto_assign` is true); `test_project_context_accepts_a_granted_project_and_ignores_others` (`app(ProjectContext::class)->set($alpha->id)` sets session id; `set($bravo->id)` clears it); `test_workspace_context_accepts_the_granted_workspace_only`.
- [ ] **Step 2: Run** `php artisan test --filter=GuestSessionTest` → FAIL.
- [ ] **Step 3: Implement**: `AssignDefaultTenant::handle` returns early when `$user->is_guest`; update both context classes.
- [ ] **Step 4: Run** the filter plus `NavigationTest` → PASS.
- [ ] **Step 5: Commit** `git add <files this task created or modified> && git commit -m "feat: guests skip tenant auto-provisioning; contexts use scoped lookups"`

---

### Task 5: Replace the remaining hand-written tenant filters

These sites filter on `auth()->user()->tenant_id` themselves, so a guest or a foreign grantee would not see the granted project in them. `Workspace` and `Project` queries are already scoped by `TenantScope` (Task 3), so the explicit filter is removed rather than widened.

**Files (replace the `tenant_id` filter, or the `tenant_id === null` early-out, with the scoped query / `Tenancy::scopeTenantId()`):**
- `app/Helpers/ListUi.php:374-390`
- `app/Http/Controllers/DiagramsController.php:27,35,90,153`
- `app/Http/Controllers/GuardrailsController.php:29,160`
- `app/Http/Controllers/SolutionRequirementsController.php:30,167`
- `app/Http/Controllers/StrategyController.php:27,35,69,117`
- `app/Services/AcceptancePlanBuilder.php:529,556`
- `app/Services/TraceabilityMatrixService.php:913,941`
- `app/Support/NavTreeBuilder.php:46,149` (also add the user's grant ids to `cacheKey()` input or forget the user's tree when a grant changes; Task 9 relies on it)
- Test: `tests/Feature/GuestPagesTest.php`

**Interfaces:**
- Consumes: Task 3. Produces: no new API.

- [ ] **Step 1: Write failing tests** in `GuestPagesTest` (guest with `View` on Alpha, sibling project Alpha 2 with a visibly named requirement): one test per page, each asserting the response is OK and the Alpha project name is present while `Alpha 2` and `Bravo` names are absent: `test_diagrams`, `test_guardrails`, `test_strategy`, `test_solution_requirements`, `test_traceability`, `test_acceptance_plan` (routes `diagrams.index`, `guardrails.index`, `strategy.index`, `solution_requirements.index`, `traceability.index`, `acceptance-plan.index`), `test_navigation_tree_lists_only_the_granted_project`, and `test_list_filter_options_offer_only_the_granted_project` (call `ListUi` options for `project_id`/`workspace_id`).
- [ ] **Step 2: Run** `php artisan test --filter=GuestPagesTest` → FAIL.
- [ ] **Step 3: Implement** the replacements site by site. Do not change behaviour for tenant users without grants.
- [ ] **Step 4: Run** `php artisan test --filter="GuestPagesTest|NavigationTest|TraceabilityViewsTest|GuardrailHubScopeTest|HomeAndReadinessUiTest"` → PASS.
- [ ] **Step 5: Commit** `git add <files this task created or modified> && git commit -m "feat: list, report and nav queries honour project grants"`

---

### Task 6: Abilities for guests and the per-record write guard

**Files:**
- Create: `app/Models/Concerns/EnforcesProjectAccess.php`
- Modify: `app/Support/EntityAccess.php` (`can`, `entitiesFor`), `app/Models/BaseModel.php`, `app/Models/Comment.php`, `app/Models/Approval.php`, `app/Models/Attachment.php`, `app/Models/Scenario.php`, `app/Models/Project.php` (override the hook), `app/Support/ProjectAccess.php` (add `assertWritable`)
- Test: `tests/Feature/GuestWriteAccessTest.php`

**Interfaces:**
- Consumes: Task 2 `ProjectAccess::can`, `guestAllows`, `isForeign`.
- Produces:
  - `EntityAccess::can`: after the super-admin check, `if ($user->is_guest) return ProjectAccess::guestAllows(...)`. `entitiesFor` for a guest iterates `CrudEntityRegistry::all()` through `can` (guests have no role).
  - `ProjectAccess::assertWritable(Model $model, ProjectAccessLevel $required): void`. Skips when no authenticated user or `! Tenancy::enforced()`. For the model's project id (the current value **and**, on update, the original value) it throws `AuthorizationException` unless `can($user, $projectId, $required)`.
  - Trait `EnforcesProjectAccess`: on `saving` and `deleting` calls `assertWritable($this, $this->requiredProjectLevel())`. Hooks with defaults: `requiredProjectLevel(): ProjectAccessLevel` (`Edit`), `owningProjectIds(): list<int>` (the `project_id` attribute and original). `Comment` overrides the level to `Comment`; `Project` returns its own id; `Scenario` resolves through its feature. `ActivityLog` does not use the trait (system record). `BaseModel`, `Comment`, `Approval`, `Attachment` use it.

- [ ] **Step 1: Write failing tests** in `GuestWriteAccessTest` (use `put(route('functional_requirements.update', …))` with a valid payload copied from `TenantIsolationTest`):
  - `test_view_guest_cannot_update_create_or_delete` (403 on update, store, destroy; row unchanged).
  - `test_comment_guest_cannot_update_records`.
  - `test_edit_guest_can_update_create_and_delete_in_the_granted_project`.
  - `test_edit_guest_cannot_move_a_record_into_a_view_only_project` (grants: Edit on Alpha, View on Alpha 2; update with `project_id` of Alpha 2 → 403; project unchanged).
  - `test_guest_cannot_approve_or_create_workspaces_or_edit_the_project` (`review.approve` 403; `workspaces.create` 403; `projects.update` 403).
  - `test_role_user_with_a_foreign_view_grant_cannot_write_there_even_with_a_write_role`.
  - `test_tenant_user_writes_in_own_tenant_are_unaffected`.
- [ ] **Step 2: Run** `php artisan test --filter=GuestWriteAccessTest` → FAIL.
- [ ] **Step 3: Implement** as specified. Keep `BaseModel::deleting`'s `saveQuietly()` working (quiet saves fire no events, the guard already ran in `deleting`).
- [ ] **Step 4: Run** `php artisan test --filter="GuestWriteAccessTest|TenantIsolationTest|FrameworkValidationTest|AttachmentTest"` → PASS.
- [ ] **Step 5: Commit** `git add <files this task created or modified> && git commit -m "feat: grant levels enforced per record; guest abilities"`

---

### Task 7: Comments for guests

**Files:**
- Modify: `app/Services/CommentService.php:312-322` and the mention parsing that reads `tenantUsers()`, `app/Http/Controllers/CommentController.php` (panel data)
- Test: `tests/Feature/GuestCommentsTest.php`

**Interfaces:**
- Consumes: Task 6 (the `Comment` model guard).
- Produces: `CommentService::mentionableUsers(Model $record): Collection` (id, name): users of the record's project's tenant plus users holding a grant on that project, never the viewer; replaces `tenantUsers()` at every call site (delete `tenantUsers` if unused).

- [ ] **Step 1: Write failing tests**: `test_view_guest_cannot_comment` (POST `comments.store` → 403, no row); `test_comment_guest_can_comment_and_reply_and_resolve` (200 and rows exist); `test_comment_guest_can_read_existing_threads`; `test_mention_suggestions_do_not_list_unrelated_tenantless_users` (second guest with a grant on Bravo only is absent for a guest on Alpha; tenant colleagues of Alpha and other Alpha grantees are present).
- [ ] **Step 2: Run** `php artisan test --filter=GuestCommentsTest` → FAIL.
- [ ] **Step 3: Implement** `mentionableUsers` and switch the controller panel and mention parsing to it.
- [ ] **Step 4: Run** `php artisan test --filter="GuestCommentsTest|CommentsTest"` → PASS.
- [ ] **Step 5: Commit** `git add <files this task created or modified> && git commit -m "feat: comment-level guests and project-scoped mention list"`

---

### Task 8: Keep guests out of admin, tokens and the API token channel

**Files:**
- Modify: `app/Http/Controllers/ApiTokenController.php`, `app/Http/Middleware/EnforceApiTokenAbility.php`, the MCP auth entry point found via `app/Mcp` (apply the same rule)
- Test: `tests/Feature/GuestRestrictionsTest.php`

- [ ] **Step 1: Write failing tests**: `test_guest_gets_403_on_admin_users`; `test_guest_cannot_open_or_create_api_tokens` (`profile.api-tokens.index` and `.store` → 403); `test_a_token_owned_by_a_guest_is_rejected_by_the_api` (create a Sanctum token for the guest directly, call an `api.*` route with it → 403); `test_guest_can_open_the_export_pack` (`projects.export`, `projects.babok.index` → 200 for a `View` guest).
- [ ] **Step 2: Run** `php artisan test --filter=GuestRestrictionsTest` → FAIL.
- [ ] **Step 3: Implement**: `abort_if($request->user()?->is_guest, 403)` in the three places.
- [ ] **Step 4: Run** `php artisan test --filter="GuestRestrictionsTest|ApiTokenPageTest|ApiPlatformTest|McpServerTest"` → PASS.
- [ ] **Step 5: Commit** `git add <files this task created or modified> && git commit -m "feat: guests cannot use admin, API tokens or MCP"`

---

### Task 9: Members tab (list, change level, remove)

**Files:**
- Create: `app/Http/Controllers/ProjectMemberController.php`, `resources/views/pages/projects/members.blade.php`
- Modify: `routes/web.php` (inside the `auth` group, near the other `projects/{project}/…` routes), `resources/views/pages/projects/dashboard.blade.php` (a "Members" link shown when `ProjectAccess::canManageMembers`), `app/Models/ProjectMember.php` (on `saved`/`deleted`: `ProjectAccess::flush()` and forget the affected user's nav tree)
- Test: `tests/Feature/ProjectMembersTest.php`

**Interfaces:**
- Produces routes `projects.members.index` (GET `projects/{project}/members`), `projects.members.update` (PATCH `…/members/{member}`, body `access`), `projects.members.destroy` (DELETE). Each action calls `Tenancy::assertProject` then aborts 403 unless `ProjectAccess::canManageMembers`. A `{member}` that does not belong to `{project}` is a 404. `access` validated `in:view,comment,edit`.

- [ ] **Step 1: Write failing tests**: `test_manager_sees_members_and_pending_invitations`; `test_manager_changes_a_level` (row updated); `test_manager_removes_a_member`; `test_analyst_without_project_update_gets_403`; `test_guest_gets_403`; `test_manager_of_another_tenant_gets_404`; `test_member_of_another_project_is_404`; `test_invalid_level_is_rejected`; `test_downgrade_applies_on_the_next_request_even_with_cached_nav` (guest first loads a page so the nav tree is cached; manager downgrades `Edit`→`View`; guest's `put` on a requirement is now 403 and the guest's nav still lists the project); `test_removal_hides_the_project_even_with_cached_nav`.
- [ ] **Step 2: Run** `php artisan test --filter=ProjectMembersTest` → FAIL.
- [ ] **Step 3: Implement** controller, view (table like `pages/admin/users/index.blade.php`: name, email, level select with Save, remove button; guest badge for `is_guest`), routes, model events.
- [ ] **Step 4: Run** `php artisan test --filter="ProjectMembersTest|NavigationTest"` → PASS.
- [ ] **Step 5: Commit** `git add <files this task created or modified> && git commit -m "feat: project members tab"`

---

### Task 10: Email invitations and acceptance

**Files:**
- Create: `app/Services/ProjectInvitationService.php`, `app/Http/Controllers/ProjectInvitationController.php`, `app/Http/Controllers/InvitationAcceptController.php`, `app/Mail/ProjectInvitationMail.php`, `resources/views/emails/project-invitation.blade.php`, `resources/views/pages/invitations/show.blade.php`
- Modify: `routes/web.php` (manager routes in the `auth` group; the two public routes **outside** it), `resources/views/pages/projects/members.blade.php` (invite form, resend, revoke, level change on pending rows), `lang/en/ui.php` (new keys)
- Test: `tests/Feature/ProjectInvitationTest.php`

**Interfaces:**
- Consumes: Tasks 1, 2, 4, 9.
- Produces `ProjectInvitationService`:
  - `invite(Project $project, string $email, ProjectAccessLevel $level, User $by): ProjectInvitation`: lowercases and trims the email; rejects (validation error) an email that already belongs to a member of the project, or to a user of the project's own tenant; if a pending invitation for the same email and project exists it updates level and expiry and resends (one pending row); sends `ProjectInvitationMail`; stores only `hash('sha256', $plainToken)`; expiry from `config('tenancy.invitations.ttl_days', 7)`.
  - `resend(ProjectInvitation $i): void` (new token, new expiry, old link stops working), `revoke(ProjectInvitation $i): void`, `updateLevel(ProjectInvitation $i, ProjectAccessLevel $level): void`.
  - `accept(ProjectInvitation $i, ?User $existing, ?array $registration): User`: existing user must match the invitation email; otherwise creates `User` (`is_guest` true, no tenant, no role, `email_verified_at` now) from `name`/`password`; creates or updates the `ProjectMember` with the invitation's level and `invited_by`; sets `accepted_at`.
  - `findUsable(string $plainToken): ?ProjectInvitation`.
- Routes: `projects.invitations.store` (POST `projects/{project}/invitations`), `.resend`, `.revoke`, `.update` (manager only, same checks as Task 9); public `invitations.show` (GET `invitations/{token}`) and `invitations.accept` (POST), both `throttle:10,1`. Unusable token → 410 view. A logged-in user whose email differs from the invitation sees 403. A guest-visitor with an existing account is sent to login with the invitation as the intended URL.

- [ ] **Step 1: Write failing tests** (`Mail::fake()`): `test_manager_invites_and_mail_is_sent_with_a_link`; `test_token_is_stored_hashed`; `test_email_is_normalised_to_lowercase`; `test_inviting_the_same_email_twice_keeps_one_pending_invitation_and_updates_the_level`; `test_cannot_invite_a_user_of_the_projects_own_tenant`; `test_only_managers_can_invite` (analyst without Project update and a guest both 403); `test_new_person_accepts_and_gets_a_guest_account_and_grant` (account is guest, tenantless, can open the project dashboard, level as invited); `test_pending_level_change_applies_on_acceptance`; `test_existing_user_must_be_logged_in_as_the_invited_email`; `test_existing_guest_invited_to_a_second_tenant_gets_a_second_grant`; `test_expired_revoked_and_used_tokens_are_gone` (410 each); `test_resend_invalidates_the_previous_link`; `test_accept_does_not_log_in_as_anyone_else_and_never_assigns_a_tenant`.
- [ ] **Step 2: Run** `php artisan test --filter=ProjectInvitationTest` → FAIL.
- [ ] **Step 3: Implement** the service, mail, controllers, views and routes. The accept page shows project name, inviter name and level; registration form (name, password, confirmation) reuses the Fortify password rules via `PasswordValidationRules`.
- [ ] **Step 4: Run** `php artisan test --filter="ProjectInvitationTest|ProjectMembersTest"` → PASS.
- [ ] **Step 5: Commit** `git add <files this task created or modified> && git commit -m "feat: email invitations to a project"`

---

### Task 11: Documentation and full verification

**Files:**
- Create: `docs/project-access.md` (levels, who can invite, scope model, write guard, how to add a new entity safely: it needs `project_id` or a `owningProjectIds()` override)
- Modify: `docs/collaboration.md` (Permission row points to it)

- [ ] **Step 1: Write** the doc (the decisions table from this plan, the three layers: visibility scope, entity abilities, per-record guard).
- [ ] **Step 2: Run the whole suite** `php artisan test` → PASS (report any pre-existing failure separately from new ones).
- [ ] **Step 3: Manual check with the app** (use the `run` skill): invite an email, open the mail log (`MAIL_MAILER=log`), accept as a new person, confirm `view` shows no edit controls working, raise to `comment`, then `edit`, then revoke.
- [ ] **Step 4: Commit** `git add <files this task created or modified> && git commit -m "docs: project guest access"`
