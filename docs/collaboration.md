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
