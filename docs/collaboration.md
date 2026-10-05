# Collaboration: comments, review & history

**When do I care?** When you add a new entity that should support comments, review or history, or change how approvals behave. The user-facing guide is `resources/help/collaboration.md` (BA Guide → Collaboration).

## Pieces

| Concern | Code | Table(s) |
|---------|------|----------|
| Comment threads, @mentions | `CommentService`, `CommentController`, `pages/partials/comments.blade.php`, `resources/js/comments.js` | `comments`, `comment_mentions` |
| Review decisions | `ApprovalService`, `ReviewController`, `pages/partials/review-bar.blade.php`, `resources/js/review.js` | `approvals` |
| History | `ActivityRecorder`, `Observers/TrackedEntityObserver` | `activity_log` |
| Printing (BABOK docs, export pack) | `Support/PrintComments` + `pages/projects/partials/print-*.blade.php` | — |
| Permission | `EntityAccess::APPROVE`, `role_entity_permissions.can_approve` | — |

All three tables carry `project_id` and use `BelongsToTenant`, so every query is tenant-scoped like the entities themselves. Panels render inside `<x-details-view>`, so every details page and view modal gets them without per-entity Blade.

## What is framework and what is BAssist

The goal: another project copies the framework and adds an attribute, with nothing to rewrite. This is where each piece stands.

| Piece | Status | Where |
|-------|--------|-------|
| Attributes + `EntityFeatures`, observer registration | **Framework** | `app/Attributes/{Commentable,Tracked,Approvable}.php`, `app/Support/EntityFeatures.php`, `AppServiceProvider` |
| History recording and panel | **Framework** | `ActivityRecorder`, `ActivityLog`, `TrackedEntityObserver`, `pages/partials/history.blade.php` (`project_id` is optional in `activity_log`) |
| Comments: threads, mentions, resolve, panel, JS | **Framework, one tie** | `CommentService`, `CommentController`, `pages/partials/comments.blade.php`, `comments.js` |
| Review: decisions, permission, reset on edit, bar, JS | **Framework, two ties** | `ApprovalService`, `ReviewController`, `EntityAccess::APPROVE`, `pages/partials/review-bar.blade.php`, `review.js` |
| Showing the sections on a record | **Framework** | `DetailsView` + `<x-section>` |
| Open threads / sign-offs in BABOK PDFs, "waiting for your review" on the home page | **BAssist** | `PrintComments`, `openThreadsForProject()`, `currentForProject()`, `HomeController` |

**The ties (not yet removed):**

1. **`project_id` is mandatory** in the `comments` and `approvals` tables, with a foreign key to `projects`. A project without a `projects` table cannot run these migrations, and an entity without `project_id` cannot be commented on or approved. To port today: edit the two migrations (make the column nullable or point it at your tenant table) and the `'project_id' =>` lines in `CommentService::add()` and `ApprovalService`.
2. **Review moves items between BABOK statuses**: Approve → *Agreed*, Request changes → *Need Revision*, content edit → *Draft* (`EntityStatus` codes used in `ApprovalService`). To port today: change those three codes, or drop the status change.

**Rule for new work:** do not add an entity name to a list in a service. Add an attribute to the model; if a feature needs a new switch, add an attribute class and a method on `EntityFeatures`.

## Which entities get what

- **Switched on per entity with a model attribute** (same idea as `#[Attachable]`); there is no list to maintain:

  | Attribute (`App\Attributes`) | Gives the entity |
  |---|---|
  | `#[Commentable]` | Comments section, `comments.*` routes accept it |
  | `#[Tracked]` | History section (create / update / delete with old → new values) |
  | `#[Approvable]` | Review bar, **Approve** column in the role matrix, approval reset on content edits (also records history) |

  `App\Support\EntityFeatures` reads the attributes; `AppServiceProvider` attaches `TrackedEntityObserver` to every `#[Tracked]` / `#[Approvable]` model; `DetailsView` shows the matching sections. **A new entity needs only the attribute.**
- In BAssist: all project-owned entities are `#[Commentable] #[Tracked]`; StakeholderNeed, Feature, FunctionalRequirement and NonFunctionalRequirement are also `#[Approvable]`. Change requests keep their own approve-and-taint flow.

To add an entity: put its model name in the list(s). It must have `project_id`; for review it must also have `status_id`.

## Rules worth knowing

- **Approve** → `status_id` = Agreed. **Request changes** (reason required) → Need Revision + an open comment with the reason.
- A new decision supersedes the previous one (`invalidated_reason = superseded`).
- **Re-approval:** on `updated`, if any column outside `ApprovalService::NON_CONTENT` changed and the current decision is *approved*, the approval is invalidated, status goes back to Draft, and an `approval_reset` history entry lists the fields.
  Status/priority changes (quick edits, bulk edits, CR taint) do not reset approvals.
- Setting the status from inside `ApprovalService` saves only `status_id`, which is in `NON_CONTENT`, so it never loops.
- History stores old → new per changed column (long text truncated to 240 chars); `updated_at`, `number` and similar bookkeeping columns are ignored. Wrap bulk maintenance in `app(ActivityRecorder::class)->pause(fn () => …)` to skip logging.
- Printing: controllers register a `PrintComments` instance per request (open threads, current approvals, and the comments on/off flag from `?comments=0`). Item partials ask it per article; the appendix lists only what the document actually printed.

## Tests

`tests/Feature/CommentsTest.php`, `tests/Feature/ReviewAndHistoryTest.php`.

## Comments from the API and from AI assistants

Comments can also be posted and read through the MCP endpoint (`add-comment`, `list-comments`; see [mcp.md](mcp.md#findings-are-comments)). Three things support that:

- **`comments.via`** records the channel a comment came through (null for the web UI, `api`, `mcp`), set from `RequestChannel` in `CommentService::add()`. The panel shows "(via AI assistant)" next to the author.
- **`Project` is `#[Commentable]`**, so a project-wide remark has a home. `CommentService::projectIdOf()` returns the project's own id for it.
- **Readiness** tracks every thread that is not closed, in three lines by who has to act (see below), and `ProjectInsightsService::lineage()` returns `open_comments` (open plus answered) for the record.

`CommentService::listThreads()` and `threadToArray()` are the read side for callers outside the web UI.

## Thread statuses

A thread is `open`, `answered`, `implemented` or `closed` (`App\Support\CommentStatus`, column `comments.status` on the thread row; replies leave it null). The status says who has to act next: an analyst answers an open thread, the decision of an answered thread gets applied, an analyst verifies an implemented one and closes it.

| Piece | Where |
|---|---|
| Constants, `ACTIVE`, `BLOCKING`, badge tone | `app/Support/CommentStatus.php` |
| Transitions | `CommentService::add()` (new thread, replies), `markImplemented()`, `setResolved()` |
| Counts per status | `CommentService::statusCounts()` |
| Columns `status`, `implemented_at`, `implemented_by` and the backfill | `2026_10_05_140000_add_status_to_comments.php` |
| Route `comments.implemented` | `CommentController::implemented()` |

Rules:

- A reply from a person sets the thread to `answered`, also when it was implemented or closed. That is how an analyst sends work back: reply, and it is waiting for implementation again.
- A reply through the MCP channel does not change the status (except that it reopens a closed thread), so an assistant's question is never mistaken for an answer.
- **Mark implemented** needs update permission on the record's entity. **Close** needs the approve permission, because closing is a sign-off. Before this change anyone who could comment could resolve; users without approve can no longer close threads.
- `resolved_at` / `resolved_by` are still written on close, so older code that reads them keeps working. `Comment::currentStatus()` falls back to them for rows without a status.
- The migration backfills existing threads: resolved → closed, unresolved with a reply → answered, otherwise open.
- Readiness keys: `comments_awaiting_answer`, `comments_awaiting_implementation` (both warnings), `comments_awaiting_verification` (info). The former `open_comment_threads` key is gone.

The transition table and the assistant side (`mark-comment-implemented`) are in [mcp.md](mcp.md#findings-are-comments).
